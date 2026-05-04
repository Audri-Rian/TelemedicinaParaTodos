<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\StorePartnerIntegrationRequest;
use App\Jobs\SyncPartnerExamResultsJob;
use App\Jobs\VerifyPartnerConnectionJob;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\IntegrationCredential;
use App\Models\IntegrationEvent;
use App\Models\PartnerIntegration;
use Cron\CronExpression;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
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
            ->where('doctor_id', $doctor->id)
            ->whereIn('partner_integration_id', $doctorPartnerIds)
            ->count();

        $lastSyncRaw = IntegrationEvent::query()
            ->forDoctor($doctor->id)
            ->whereIn('partner_integration_id', $doctorPartnerIds)
            ->max('created_at');

        $lastSync = $lastSyncRaw ? \Illuminate\Support\Carbon::parse($lastSyncRaw) : null;

        $errors24h = IntegrationEvent::query()
            ->forDoctor($doctor->id)
            ->whereIn('partner_integration_id', $doctorPartnerIds)
            ->where('status', IntegrationEvent::STATUS_FAILED)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        $laboratories = $doctor->partnerIntegrations()
            ->where('partner_integrations.type', PartnerIntegration::TYPE_LABORATORY)
            ->with('credential')
            ->get()
            ->values();

        $lastEventByPartner = $this->recentEventsByPartner(
            doctorId: $doctor->id,
            partnerIds: $laboratories->pluck('id'),
            limitPerPartner: 1,
        );

        $laboratories = $laboratories->map(fn (PartnerIntegration $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'slug' => $p->slug,
            'status' => $p->status,
            'last_sync_at' => $lastEventByPartner[$p->id][0]['created_at'] ?? null,
        ]);

        $syncCron = config('integrations.sync.exam_results_cron', '*/15 * * * *');
        $nextSyncAt = (new CronExpression($syncCron))->getNextRunDate()->toIso8601String();

        return Inertia::render('Doctor/Integrations/Hub', [
            'stats' => [
                'activeIntegrations' => $activeCount,
                'syncedExams' => $syncedExams,
                'lastSync' => $lastSync?->toIso8601String(),
                'errors24h' => $errors24h,
            ],
            'laboratories' => $laboratories,
            'nextSyncAt' => $nextSyncAt,
        ]);
    }

    /** Gerenciamento de parceiros — fix N+1 com withCount + eager loading. */
    public function partners(): Response
    {
        $doctor = $this->resolveDoctor();

        $partners = $doctor->partnerIntegrations()
            ->with('credential')
            ->withCount([
                'events as sent_count' => fn ($q) => $q
                    ->where('doctor_id', $doctor->id)
                    ->where('direction', IntegrationEvent::DIRECTION_OUTBOUND),
                'events as received_count' => fn ($q) => $q
                    ->where('doctor_id', $doctor->id)
                    ->where('direction', IntegrationEvent::DIRECTION_INBOUND),
                'events as errors_count' => fn ($q) => $q
                    ->where('doctor_id', $doctor->id)
                    ->where('status', IntegrationEvent::STATUS_FAILED),
            ])
            ->get();

        $recentEventsByPartner = $this->recentEventsByPartner(
            doctorId: $doctor->id,
            partnerIds: $partners->pluck('id'),
            limitPerPartner: 5,
        );

        $partners = $partners
            ->map(function (PartnerIntegration $p) use ($recentEventsByPartner) {
                $recentEvents = $recentEventsByPartner[$p->id] ?? [];

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'type' => $p->type,
                    'status' => $p->status,
                    'base_url' => $p->base_url,
                    'capabilities' => $p->capabilities,
                    'fhir_version' => $p->fhir_version,
                    'last_sync_at' => $recentEvents[0]['created_at'] ?? null,
                    'contact_email' => $p->contact_email,
                    'integration_mode' => $p->pivot?->integration_mode,
                    'stats' => [
                        'sent' => $p->sent_count,
                        'received' => $p->received_count,
                        'errors' => $p->errors_count,
                    ],
                    'recentEvents' => $recentEvents,
                ];
            });

        $doctorPartnerIds = $this->doctorPartnerIdsSubquery($doctor);
        $criticalEvents = IntegrationEvent::query()
            ->forDoctor($doctor->id)
            ->whereIn('partner_integration_id', $doctorPartnerIds)
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

        $connectedPartners = $doctor->partnerIntegrations()
            ->get(['partner_integrations.id', 'partner_integrations.slug'])
            ->map(fn (PartnerIntegration $p) => ['id' => $p->id, 'slug' => $p->slug])
            ->values()
            ->toArray();

        return Inertia::render('Doctor/Integrations/Connect', [
            'availablePartners' => config('integrations.partner_catalog', []),
            'connectedPartners' => $connectedPartners,
        ]);
    }

    /** Cria um parceiro a partir do wizard Connect. */
    public function store(StorePartnerIntegrationRequest $request): RedirectResponse
    {
        $doctor = $this->resolveDoctor();
        $validated = $request->validated();
        $isReceiveOnly = ($validated['integration_mode'] ?? '') === 'receive_only';
        $partnerCatalogEntry = collect(config('integrations.partner_catalog', []))
            ->firstWhere('key', $validated['partner_slug']);

        if (! is_array($partnerCatalogEntry)) {
            return back()
                ->withInput()
                ->withErrors([
                    'partner_slug' => 'Selecione um parceiro válido do catálogo disponível.',
                ]);
        }

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

        $operation = DB::transaction(function () use ($validated, $doctor, $request, $capabilities, $isReceiveOnly, $partnerCatalogEntry) {
            $partner = PartnerIntegration::query()
                ->where('slug', $validated['partner_slug'])
                ->first();

            if (! $partner) {
                $partner = PartnerIntegration::create([
                    'name' => $partnerCatalogEntry['name'] ?? $validated['partner_name'],
                    'slug' => $validated['partner_slug'],
                    'type' => $partnerCatalogEntry['type'] ?? ($validated['partner_type'] ?? PartnerIntegration::TYPE_LABORATORY),
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
                    default => IntegrationCredential::AUTH_API_KEY,
                };

                $partner->credential()->create([
                    'auth_type' => $authType,
                    'client_id' => $validated['auth_method'] !== 'bearer' ? ($validated['client_id'] ?? null) : null,
                    'client_secret' => $validated['client_secret'] ?? null,
                    'access_token' => $validated['bearer_token'] ?? null,
                ]);
            }

            return [
                'partner' => $partner,
                'partner_name' => $partner->name,
                'already_connected' => $alreadyConnected,
            ];
        });

        $baseUrl = $validated['base_url'] ?? null;
        $shouldQueueConnectionCheck = ! $isReceiveOnly
            && ! empty($baseUrl)
            && ! $operation['partner']->isActive();

        if ($shouldQueueConnectionCheck) {
            VerifyPartnerConnectionJob::dispatch(
                partnerId: $operation['partner']->id,
                baseUrl: $baseUrl,
            );
        }

        if ($operation['already_connected']) {
            $flash = ['success' => "Parceiro {$operation['partner_name']} já estava conectado para este médico."];
        } elseif ($isReceiveOnly) {
            $flash = ['warning' => "Parceiro {$operation['partner_name']} salvo em modo pendente. Ative a integração após validar o endpoint do parceiro."];
        } elseif ($shouldQueueConnectionCheck) {
            $flash = ['success' => "Parceiro {$operation['partner_name']} salvo com sucesso. A verificação da conexão foi enfileirada e o status será atualizado automaticamente."];
        } else {
            $flash = ['success' => "Parceiro {$operation['partner_name']} conectado com sucesso!"];
        }

        return redirect()->route('doctor.integrations.partners')->with($flash);
    }

    /** Detalhes de um parceiro — queries consolidadas. */
    public function show(PartnerIntegration $partner): Response
    {
        $doctor = $this->ensureDoctorHasPartner($partner);
        $partner->load('credential');

        $events = IntegrationEvent::where('partner_integration_id', $partner->id)
            ->forDoctor($doctor->id)
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
            ->forDoctor($doctor->id)
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
                'last_sync_at' => $events->first()['created_at'] ?? null,
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

    private function ensureDoctorHasPartner(PartnerIntegration $partner): Doctor
    {
        $doctor = $this->resolveDoctor();
        $isConnected = $doctor->partnerIntegrations()
            ->whereKey($partner->id)
            ->exists();

        if (! $isConnected) {
            abort(404);
        }

        return $doctor;
    }

    private function doctorPartnerIdsSubquery(Doctor $doctor)
    {
        return $doctor->partnerIntegrations()->select('partner_integrations.id');
    }

    /**
     * @param  Collection<int, string>  $partnerIds
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function recentEventsByPartner(string $doctorId, Collection $partnerIds, int $limitPerPartner): array
    {
        if ($partnerIds->isEmpty()) {
            return [];
        }

        $ranked = IntegrationEvent::query()
            ->selectRaw('
                integration_events.id,
                integration_events.partner_integration_id,
                integration_events.event_type,
                integration_events.status,
                integration_events.direction,
                integration_events.error_message,
                integration_events.created_at,
                ROW_NUMBER() OVER (
                    PARTITION BY integration_events.partner_integration_id
                    ORDER BY integration_events.created_at DESC
                ) as row_num
            ')
            ->where('doctor_id', $doctorId)
            ->whereIn('partner_integration_id', $partnerIds)
            ->orderByDesc('created_at');

        return DB::query()
            ->fromSub($ranked, 'ranked_events')
            ->where('row_num', '<=', $limitPerPartner)
            ->orderBy('partner_integration_id')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('partner_integration_id')
            ->map(fn ($events) => $events->map(static fn ($event) => [
                'id' => $event->id,
                'type' => $event->event_type,
                'status' => $event->status,
                'direction' => $event->direction,
                'created_at' => \Illuminate\Support\Carbon::parse($event->created_at)->toIso8601String(),
                'error_message' => $event->error_message,
            ])->values()->all())
            ->toArray();
    }
}
