<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\StorePartnerIntegrationRequest;
use App\Jobs\SyncPartnerExamResultsJob;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\IntegrationCredential;
use App\Models\IntegrationEvent;
use App\Models\PartnerIntegration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DoctorIntegrationsController extends Controller
{
    /** Hub de integrações (rota inicial). */
    public function index(): Response
    {
        $doctor = $this->resolveDoctor();

        $activeCount = $doctor->partnerIntegrations()
            ->where('partner_integrations.status', PartnerIntegration::STATUS_ACTIVE)
            ->count();

        $doctorPartnerIds = $this->doctorPartnerIdsSubquery($doctor);
        $syncedExams = Examination::where('source', Examination::SOURCE_INTEGRATION)
            ->whereIn('partner_integration_id', $doctorPartnerIds)
            ->count();

        $lastSyncRaw = $doctor->partnerIntegrations()
            ->where('partner_integrations.status', PartnerIntegration::STATUS_ACTIVE)
            ->whereNotNull('last_sync_at')
            ->max('partner_integrations.last_sync_at');

        $lastSync = $lastSyncRaw ? \Illuminate\Support\Carbon::parse($lastSyncRaw) : null;

        $errors24h = IntegrationEvent::query()
            ->whereIn('partner_integration_id', $this->doctorPartnerIdsSubquery($doctor))
            ->where('status', IntegrationEvent::STATUS_FAILED)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $laboratories = $doctor->partnerIntegrations()
            ->where('partner_integrations.type', PartnerIntegration::TYPE_LABORATORY)
            ->with('credential')
            ->get()
            ->map(fn (PartnerIntegration $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'status' => $p->status,
                'last_sync_at' => $p->last_sync_at?->toIso8601String(),
            ]);

        return Inertia::render('Doctor/Integrations/Hub', [
            'stats' => [
                'activeIntegrations' => $activeCount,
                'syncedExams' => $syncedExams,
                'lastSync' => $lastSync?->toIso8601String(),
                'errors24h' => $errors24h,
            ],
            'laboratories' => $laboratories,
        ]);
    }

    /** Gerenciamento de parceiros — fix N+1 com withCount + eager loading. */
    public function partners(): Response
    {
        $doctor = $this->resolveDoctor();

        $partners = $doctor->partnerIntegrations()
            ->with(['credential', 'events' => function ($q) {
                $q->orderByDesc('created_at')->limit(5);
            }])
            ->withCount([
                'events as sent_count' => fn ($q) => $q->where('direction', IntegrationEvent::DIRECTION_OUTBOUND),
                'events as received_count' => fn ($q) => $q->where('direction', IntegrationEvent::DIRECTION_INBOUND),
                'events as errors_count' => fn ($q) => $q->where('status', IntegrationEvent::STATUS_FAILED),
            ])
            ->get()
            ->map(function (PartnerIntegration $p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'type' => $p->type,
                    'status' => $p->status,
                    'base_url' => $p->base_url,
                    'capabilities' => $p->capabilities,
                    'fhir_version' => $p->fhir_version,
                    'last_sync_at' => $p->last_sync_at?->toIso8601String(),
                    'contact_email' => $p->contact_email,
                    'integration_mode' => $p->pivot?->integration_mode,
                    'stats' => [
                        'sent' => $p->sent_count,
                        'received' => $p->received_count,
                        'errors' => $p->errors_count,
                    ],
                    'recentEvents' => $p->events->map(fn (IntegrationEvent $e) => [
                        'id' => $e->id,
                        'type' => $e->event_type,
                        'status' => $e->status,
                        'direction' => $e->direction,
                        'created_at' => $e->created_at->toIso8601String(),
                        'error_message' => $e->error_message,
                    ]),
                ];
            });

        $criticalEvents = IntegrationEvent::query()
            ->whereIn('partner_integration_id', $this->doctorPartnerIdsSubquery($doctor))
            ->where('status', IntegrationEvent::STATUS_FAILED)
            ->where('created_at', '>=', now()->subDay())
            ->with('partnerIntegration')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (IntegrationEvent $e) => [
                'id' => $e->id,
                'partner_name' => $e->partnerIntegration?->name ?? 'Desconhecido',
                'event_type' => $e->event_type,
                'error_message' => $e->error_message,
                'created_at' => $e->created_at->toIso8601String(),
            ]);

        return Inertia::render('Doctor/Integrations/Partners', [
            'partners' => $partners,
            'criticalEvents' => $criticalEvents,
        ]);
    }

    public function connect(): Response
    {
        $doctor = $this->resolveDoctor();

        return Inertia::render('Doctor/Integrations/Connect', [
            'connectedSlugs' => $doctor->partnerIntegrations()
                ->pluck('partner_integrations.slug')
                ->toArray(),
        ]);
    }

    /** Cria um parceiro a partir do wizard Connect. */
    public function store(StorePartnerIntegrationRequest $request): RedirectResponse
    {
        $doctor = $this->resolveDoctor();
        $validated = $request->validated();
        $isReceiveOnly = ($validated['integration_mode'] ?? '') === 'receive_only';

        $capabilities = [];
        if ($validated['perm_send_orders'] ?? false) {
            $capabilities[] = 'send_exam_order';
        }
        if ($validated['perm_receive_results'] ?? false) {
            $capabilities[] = 'receive_exam_result';
        }
        if ($validated['perm_webhook'] ?? false) {
            $capabilities[] = 'webhook_result';
        }

        $existingPartner = PartnerIntegration::withTrashed()
            ->where('slug', $validated['partner_slug'])
            ->first();

        if ($existingPartner?->trashed()) {
            return back()
                ->withInput()
                ->withErrors([
                    'partner_slug' => 'Este parceiro está indisponível para novas conexões.',
                ]);
        }

        $operation = DB::transaction(function () use ($validated, $doctor, $request, $capabilities, $isReceiveOnly) {
            $partner = PartnerIntegration::query()
                ->where('slug', $validated['partner_slug'])
                ->first();

            if (! $partner) {
                $partner = PartnerIntegration::create([
                    'name' => $validated['partner_name'],
                    'slug' => $validated['partner_slug'],
                    'type' => $validated['partner_type'] ?? PartnerIntegration::TYPE_LABORATORY,
                    'status' => PartnerIntegration::STATUS_PENDING,
                    'base_url' => $validated['base_url'] ?? null,
                    'fhir_version' => 'R4',
                    'capabilities' => $capabilities,
                    'contact_email' => $validated['contact_email'] ?? null,
                    'connected_by' => $request->user()?->id,
                    'connected_at' => now(),
                ]);
            }

            $alreadyConnected = $doctor->partnerIntegrations()
                ->whereKey($partner->id)
                ->exists();

            $pivotData = [
                'integration_mode' => $validated['integration_mode'],
                'perm_send_orders' => (bool) ($validated['perm_send_orders'] ?? false),
                'perm_receive_results' => (bool) ($validated['perm_receive_results'] ?? false),
                'perm_webhook' => (bool) ($validated['perm_webhook'] ?? false),
                'perm_patient_data' => (bool) ($validated['perm_patient_data'] ?? false),
                'connected_by' => $request->user()?->id,
                'connected_at' => now(),
                'updated_at' => now(),
            ];

            if (! $alreadyConnected) {
                $pivotData['created_at'] = now();
            }

            $doctor->partnerIntegrations()->sync([$partner->id => $pivotData], false);

            // Modo receive_only: não precisa de credenciais para acessar API do parceiro.
            // Credenciais são globais por parceiro neste momento; só cria na primeira conexão.
            if (! $isReceiveOnly && ! empty($validated['auth_method']) && ! $partner->credential()->exists()) {
                $authType = match ($validated['auth_method']) {
                    'oauth2' => IntegrationCredential::AUTH_OAUTH2_CLIENT_CREDENTIALS,
                    'api_key' => IntegrationCredential::AUTH_API_KEY,
                    'bearer' => IntegrationCredential::AUTH_BEARER,
                    'certificate' => IntegrationCredential::AUTH_CERTIFICATE,
                    default => IntegrationCredential::AUTH_API_KEY,
                };

                $partner->credential()->create([
                    'auth_type' => $authType,
                    'client_id' => $validated['auth_method'] !== 'bearer' ? ($validated['client_id'] ?? null) : null,
                    'client_secret' => $validated['client_secret'] ?? null,
                    'access_token' => $validated['bearer_token'] ?? null,
                ]);
            }

            $partner->update(['status' => PartnerIntegration::STATUS_ACTIVE]);

            return [
                'partner_name' => $partner->name,
                'already_connected' => $alreadyConnected,
            ];
        });

        return redirect()->route('doctor.integrations.partners')
            ->with('success', $operation['already_connected']
                ? "Parceiro {$operation['partner_name']} já estava conectado para este médico."
                : "Parceiro {$operation['partner_name']} conectado com sucesso!"
            );
    }

    /** Detalhes de um parceiro — queries consolidadas. */
    public function show(PartnerIntegration $partner): Response
    {
        $this->ensureDoctorHasPartner($partner);
        $partner->load('credential');

        $events = IntegrationEvent::where('partner_integration_id', $partner->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (IntegrationEvent $e) => [
                'id' => $e->id,
                'type' => $e->event_type,
                'status' => $e->status,
                'direction' => $e->direction,
                'resource_type' => $e->resource_type,
                'resource_id' => $e->resource_id,
                'external_id' => $e->external_id,
                'error_message' => $e->error_message,
                'duration_ms' => $e->duration_ms,
                'created_at' => $e->created_at->toIso8601String(),
            ]);

        // Consolidado: 1 query para todos os stats
        $aggregated = IntegrationEvent::where('partner_integration_id', $partner->id)
            ->selectRaw('
                COUNT(CASE WHEN direction = ? THEN 1 END) as sent,
                COUNT(CASE WHEN direction = ? THEN 1 END) as received,
                COUNT(CASE WHEN status = ? THEN 1 END) as errors,
                COUNT(CASE WHEN status = ? THEN 1 END) as successes,
                COUNT(*) as total
            ', [
                IntegrationEvent::DIRECTION_OUTBOUND,
                IntegrationEvent::DIRECTION_INBOUND,
                IntegrationEvent::STATUS_FAILED,
                IntegrationEvent::STATUS_SUCCESS,
            ])
            ->first();

        $successRate = $aggregated->total > 0
            ? round(($aggregated->successes / $aggregated->total) * 100, 1)
            : null;

        return Inertia::render('Doctor/Integrations/Show', [
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'slug' => $partner->slug,
                'type' => $partner->type,
                'status' => $partner->status,
                'base_url' => $partner->base_url,
                'capabilities' => $partner->capabilities,
                'fhir_version' => $partner->fhir_version,
                'last_sync_at' => $partner->last_sync_at?->toIso8601String(),
                'contact_email' => $partner->contact_email,
                'created_at' => $partner->created_at->toIso8601String(),
            ],
            'events' => $events,
            'stats' => [
                'sent' => $aggregated->sent,
                'received' => $aggregated->received,
                'errors' => $aggregated->errors,
                'success_rate' => $successRate,
            ],
        ]);
    }

    /** Dispara sync sob demanda com resposta compatível com Inertia. */
    public function sync(PartnerIntegration $partner): RedirectResponse
    {
        $this->ensureDoctorHasPartner($partner);

        if (! $partner->isActive()) {
            return back()->with('error', 'Parceiro não está ativo.');
        }

        SyncPartnerExamResultsJob::dispatch($partner->id);

        return back()->with('success', 'Sincronização enfileirada. Você será notificado quando terminar.');
    }

    private function resolveDoctor(): Doctor
    {
        $doctor = auth()->user()?->doctor;

        if (! $doctor) {
            abort(403, 'Apenas médicos podem acessar integrações.');
        }

        return $doctor;
    }

    private function ensureDoctorHasPartner(PartnerIntegration $partner): void
    {
        $doctor = $this->resolveDoctor();
        $isConnected = $doctor->partnerIntegrations()
            ->whereKey($partner->id)
            ->exists();

        if (! $isConnected) {
            abort(404);
        }
    }

    private function doctorPartnerIdsSubquery(Doctor $doctor)
    {
        return $doctor->partnerIntegrations()->select('partner_integrations.id');
    }
}
