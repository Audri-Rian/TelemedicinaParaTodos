<?php

namespace App\Services;

use App\Models\User;
use App\Models\Consent;
use App\Models\DataAccessLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Service para operações relacionadas à LGPD
 */
class LGPDService
{
    /**
     * Registra consentimento do usuário
     */
    public function grantConsent(
        User $user,
        string $type,
        string $version = '1.0',
        ?string $description = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): Consent {
        // Revoga consentimentos anteriores do mesmo tipo
        Consent::where('user_id', $user->id)
            ->where('type', $type)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        return Consent::create([
            'user_id' => $user->id,
            'type' => $type,
            'granted' => true,
            'description' => $description,
            'version' => $version,
            'granted_at' => now(),
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);
    }

    /**
     * Revoga consentimento do usuário
     */
    public function revokeConsent(User $user, string $type): bool
    {
        return Consent::where('user_id', $user->id)
            ->where('type', $type)
            ->whereNull('revoked_at')
            ->update([
                'granted' => false,
                'revoked_at' => now(),
            ]) > 0;
    }

    /**
     * Verifica se o usuário tem consentimento ativo
     */
    public function hasActiveConsent(User $user, string $type): bool
    {
        return Consent::where('user_id', $user->id)
            ->where('type', $type)
            ->active()
            ->exists();
    }

    /**
     * Registra acesso a dados pessoais
     */
    public function logDataAccess(
        User $accessor,
        User $dataSubject,
        string $dataType,
        string $action,
        ?string $resourceId = null,
        ?array $accessedFields = null,
        ?string $reason = null
    ): DataAccessLog {
        return DataAccessLog::create([
            'user_id' => $accessor->id,
            'data_subject_id' => $dataSubject->id,
            'data_type' => $dataType,
            'resource_id' => $resourceId,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'reason' => $reason,
            'accessed_fields' => $accessedFields,
        ]);
    }

    /**
     * Exporta todos os dados do usuário (portabilidade)
     */
    public function exportUserData(User $user): array
    {
        $data = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ];

        // Dados do paciente
        if ($user->patient) {
            $data['patient'] = $user->patient->toArray();
        }

        // Dados do médico
        if ($user->doctor) {
            $data['doctor'] = $user->doctor->toArray();
        }

        // Consultas
        $appointments = $user->appointments();
        if ($appointments) {
            $data['appointments'] = $appointments->with(['doctor', 'patient'])->get()->toArray();
        } else {
            $data['appointments'] = [];
        }

        // Prescrições
        $prescriptions = $user->prescriptions();
        $data['prescriptions'] = $prescriptions ? $prescriptions->get()->toArray() : [];

        // Exames
        $examinations = $user->examinations();
        $data['examinations'] = $examinations ? $examinations->get()->toArray() : [];

        // Certificados médicos
        $medicalCertificates = $user->medicalCertificates();
        $data['medical_certificates'] = $medicalCertificates ? $medicalCertificates->get()->toArray() : [];

        // Mensagens
        $data['messages'] = [
            'sent' => $user->sentMessages()->get()->toArray(),
            'received' => $user->receivedMessages()->get()->toArray(),
        ];

        // Consentimentos
        $data['consents'] = $user->consents()->get()->toArray();

        // Logs de acesso aos dados
        $data['data_access_logs'] = DataAccessLog::forUser($user->id)->get()->toArray();

        return $data;
    }

    /**
     * Gera arquivo JSON com dados do usuário
     */
    public function generateDataExportFile(User $user): string
    {
        $data = $this->exportUserData($user);
        $filename = "user_data_{$user->id}_" . now()->format('Y-m-d_His') . '.json';
        $path = "lgpd_exports/{$filename}";

        Storage::disk('local')->put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }

    /**
     * Exclui todos os dados do usuário (direito ao esquecimento)
     */
    public function deleteUserData(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            try {
                // Anonimizar dados em vez de excluir completamente (para manter integridade)
                $user->update([
                    'name' => 'Usuário Excluído',
                    'email' => "deleted_{$user->id}@deleted.local",
                ]);

                // Excluir dados relacionados
                if ($user->patient) {
                    $user->patient->delete();
                }

                if ($user->doctor) {
                    $user->doctor->delete();
                }

                // Anonimizar consultas
                $user->appointments()->update([
                    'notes' => '[Dados anonimizados]',
                ]);

                // Excluir mensagens pessoais
                $user->messages()->delete();

                // Revogar todos os consentimentos
                Consent::where('user_id', $user->id)->update([
                    'granted' => false,
                    'revoked_at' => now(),
                ]);

                // Marcar usuário como excluído
                $user->delete();

                return true;
            } catch (\Exception $e) {
                \Log::error('Erro ao excluir dados do usuário: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    /**
     * Gera relatório de acessos aos dados do usuário
     */
    public function generateAccessReport(User $user, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = DataAccessLog::forUser($user->id);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $logs = $query->with('user')->orderBy('created_at', 'desc')->get();

        return [
            'user_id' => $user->id,
            'period' => [
                'start' => $startDate?->toDateString(),
                'end' => $endDate?->toDateString(),
            ],
            'total_accesses' => $logs->count(),
            'accesses_by_type' => $logs->groupBy('data_type')->map->count(),
            'accesses_by_action' => $logs->groupBy('action')->map->count(),
            'logs' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'accessed_by' => $log->user->name ?? 'Sistema',
                    'data_type' => $log->data_type,
                    'action' => $log->action,
                    'reason' => $log->reason,
                    'accessed_at' => $log->created_at,
                    'ip_address' => $log->ip_address,
                ];
            }),
        ];
    }
}

