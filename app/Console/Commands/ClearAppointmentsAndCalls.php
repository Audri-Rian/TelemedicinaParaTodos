<?php

namespace App\Console\Commands;

use App\Models\Appointments;
use App\Models\Call;
use App\Models\ClinicalNote;
use App\Models\Diagnosis;
use App\Models\Doctor;
use App\Models\Examination;
use App\Models\MedicalCertificate;
use App\Models\MedicalDocument;
use App\Models\Prescription;
use App\Models\Room;
use App\Models\VitalSign;
use App\Services\CallManagerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearAppointmentsAndCalls extends Command
{
    protected $signature = 'appointments:clear
                            {--patient-id= : UUID do paciente}
                            {--doctor-id= : UUID do médico}
                            {--all : Limpar todos os agendamentos (sem filtro de participante)}
                            {--calls-only : Remove apenas videochamadas (calls + rooms), mantém agendamentos}
                            {--with-clinical : Remove também dados clínicos vinculados aos agendamentos}
                            {--with-schedule : Limpa agenda do médico (slots, locais, datas bloqueadas)}
                            {--without-schedule : Não limpar agenda mesmo com --doctor-id}
                            {--confirm : Confirmar sem prompt interativo}';

    protected $description = 'Limpa agendamentos e videochamadas para testes locais (somente ambientes local/testing)';

    public function handle(CallManagerService $callManager): int
    {
        if (! $this->isAllowedEnvironment()) {
            $this->error('Este comando só pode rodar com APP_ENV=local ou testing.');

            return self::FAILURE;
        }

        $patientId = $this->option('patient-id');
        $doctorId = $this->option('doctor-id');
        $all = (bool) $this->option('all');
        $callsOnly = (bool) $this->option('calls-only');
        $withClinical = (bool) $this->option('with-clinical');
        $withSchedule = (bool) $this->option('with-schedule');
        $withoutSchedule = (bool) $this->option('without-schedule');
        $confirm = (bool) $this->option('confirm');

        $clearSchedule = $this->shouldClearSchedule(
            $all,
            $doctorId,
            $callsOnly,
            $withSchedule,
            $withoutSchedule
        );

        if ($clearSchedule && ! $doctorId && ! $all) {
            $this->error('--with-schedule exige --doctor-id= ou --all.');

            return self::FAILURE;
        }

        if (! $all && ! $patientId && ! $doctorId) {
            $this->error('Informe --patient-id=, --doctor-id= ou --all.');

            return self::FAILURE;
        }

        if ($all && ($patientId || $doctorId)) {
            $this->error('Não combine --all com --patient-id ou --doctor-id.');

            return self::FAILURE;
        }

        $scope = $this->describeScope($all, $patientId, $doctorId, $callsOnly, $withClinical, $clearSchedule);

        if (! $confirm && ! $this->confirm("Tem certeza que deseja {$scope}?")) {
            $this->info('Operação cancelada.');

            return self::SUCCESS;
        }

        if ($clearSchedule && ! $this->validateDoctorsExist($all, $doctorId)) {
            return self::FAILURE;
        }

        $appointmentIds = $this->resolveAppointmentIds($all, $patientId, $doctorId);

        DB::transaction(function () use (
            $appointmentIds,
            $all,
            $patientId,
            $doctorId,
            $callsOnly,
            $withClinical,
            $clearSchedule,
            $callManager
        ) {
            $callsQuery = $this->buildCallsQuery($all, $appointmentIds, $patientId, $doctorId);

            if ($callsQuery !== null) {
                $roomsCount = Room::query()
                    ->whereIn('call_id', (clone $callsQuery)->select('id'))
                    ->count();

                $this->destroySfuRooms((clone $callsQuery)->with('room')->get(), $callManager);

                $deletedCalls = $callsQuery->delete();

                $this->line("  - {$deletedCalls} chamada(s) removida(s) ({$roomsCount} sala(s) SFU no banco)");
            } else {
                $this->warn('Nenhum agendamento/chamada encontrado para o filtro informado.');
            }

            if ($callsOnly) {
                if ($clearSchedule) {
                    $this->clearSchedulesForDoctors($this->resolveDoctors($all, $doctorId));
                }

                return;
            }

            $appointmentsQuery = Appointments::withTrashed();

            if (! $all) {
                $appointmentsQuery->whereIn('id', $appointmentIds);
            } elseif ($patientId) {
                $appointmentsQuery->where('patient_id', $patientId);
            } elseif ($doctorId) {
                $appointmentsQuery->where('doctor_id', $doctorId);
            }

            $ids = $appointmentsQuery->pluck('id');

            if ($ids->isEmpty()) {
                $this->warn('Nenhum agendamento de consulta encontrado para o filtro informado.');
            } else {
                $this->clearAppointmentDependents($ids, $withClinical);

                $deletedAppointments = Appointments::withTrashed()
                    ->whereIn('id', $ids)
                    ->forceDelete();

                $this->line("  - {$deletedAppointments} agendamento(s) removido(s) (inclui soft-deleted)");
            }

            if ($clearSchedule) {
                $this->clearSchedulesForDoctors($this->resolveDoctors($all, $doctorId));
            }
        });

        $this->info('Limpeza concluída.');

        return self::SUCCESS;
    }

    private function isAllowedEnvironment(): bool
    {
        return app()->environment(['local', 'testing']);
    }

    private function describeScope(
        bool $all,
        ?string $patientId,
        ?string $doctorId,
        bool $callsOnly,
        bool $withClinical,
        bool $clearSchedule
    ): string {
        $target = match (true) {
            $all => 'TODOS os participantes',
            $patientId && $doctorId => "paciente {$patientId} com médico {$doctorId}",
            $patientId !== null => "paciente {$patientId}",
            default => "médico {$doctorId}",
        };

        $what = $callsOnly ? 'limpar as videochamadas de' : 'limpar agendamentos e videochamadas de';

        $clinical = (! $callsOnly && $withClinical) ? ' (incluindo dados clínicos vinculados)' : '';
        $schedule = (! $callsOnly && $clearSchedule) ? ' e a agenda/disponibilidade do médico' : '';

        return "{$what} {$target}{$clinical}{$schedule}";
    }

    private function shouldClearSchedule(
        bool $all,
        ?string $doctorId,
        bool $callsOnly,
        bool $withSchedule,
        bool $withoutSchedule
    ): bool {
        if ($callsOnly || $withoutSchedule) {
            return false;
        }

        if ($withSchedule) {
            return true;
        }

        return $doctorId !== null;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Doctor>
     */
    private function resolveDoctors(bool $all, ?string $doctorId)
    {
        if ($all) {
            return Doctor::query()->with('user')->get();
        }

        $doctor = Doctor::query()->with('user')->find($doctorId);

        if (! $doctor) {
            $this->error("Médico com ID {$doctorId} não encontrado.");

            return collect();
        }

        return collect([$doctor]);
    }

    private function validateDoctorsExist(bool $all, ?string $doctorId): bool
    {
        if ($all) {
            return true;
        }

        if (! Doctor::query()->whereKey($doctorId)->exists()) {
            $this->error("Médico com ID {$doctorId} não encontrado.");

            return false;
        }

        return true;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Doctor>  $doctors
     */
    private function clearSchedulesForDoctors($doctors): void
    {
        foreach ($doctors as $doctor) {
            $name = $doctor->user?->name ?? $doctor->id;
            $this->info("Limpando agenda de {$name}:");

            $slotsCount = $doctor->availabilitySlots()->withTrashed()->count();
            $doctor->availabilitySlots()->withTrashed()->forceDelete();

            $blockedCount = $doctor->blockedDates()->withTrashed()->count();
            $doctor->blockedDates()->withTrashed()->forceDelete();

            $locationsCount = $doctor->serviceLocations()->withTrashed()->count();
            $doctor->serviceLocations()->withTrashed()->forceDelete();

            $doctor->update(['availability_schedule' => null]);

            $this->line("  - {$slotsCount} slot(s) de disponibilidade");
            $this->line("  - {$blockedCount} data(s) bloqueada(s)");
            $this->line("  - {$locationsCount} local(is) de atendimento");
        }
    }

    private function buildCallsQuery(bool $all, array $appointmentIds, ?string $patientId, ?string $doctorId): ?\Illuminate\Database\Eloquent\Builder
    {
        $callsQuery = Call::query();

        if ($all) {
            return $callsQuery;
        }

        if ($appointmentIds !== []) {
            return $callsQuery->whereIn('appointment_id', $appointmentIds);
        }

        if ($patientId) {
            return $callsQuery->where('patient_id', $patientId);
        }

        if ($doctorId) {
            return $callsQuery->where('doctor_id', $doctorId);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function resolveAppointmentIds(bool $all, ?string $patientId, ?string $doctorId): array
    {
        $query = Appointments::withTrashed();

        if ($patientId) {
            $query->where('patient_id', $patientId);
        }

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        if ($all) {
            return $query->pluck('id')->all();
        }

        return $query->pluck('id')->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Call>  $calls
     */
    private function destroySfuRooms($calls, CallManagerService $callManager): void
    {
        foreach ($calls as $call) {
            if (! $call->room) {
                continue;
            }

            try {
                $callManager->destroyRoom($call->room);
            } catch (\Throwable $exception) {
                $this->warn("  - Falha ao fechar sala SFU da call {$call->id}: {$exception->getMessage()}");
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, string>  $appointmentIds
     */
    private function clearAppointmentDependents($appointmentIds, bool $withClinical): void
    {
        $modelsWithRestrict = [
            Prescription::class,
            Examination::class,
            MedicalDocument::class,
            VitalSign::class,
        ];

        foreach ($modelsWithRestrict as $model) {
            $query = $model::query()->whereIn('appointment_id', $appointmentIds);

            if ($withClinical) {
                $count = $query->delete();
                if ($count > 0) {
                    $this->line('  - '.class_basename($model).": {$count} registro(s) removido(s)");
                }

                continue;
            }

            $count = $query->update(['appointment_id' => null]);
            if ($count > 0) {
                $this->line('  - '.class_basename($model).": {$count} desvinculado(s) do agendamento");
            }
        }

        $diagnoses = Diagnosis::query()->whereIn('appointment_id', $appointmentIds)->delete();
        if ($diagnoses > 0) {
            $this->line("  - Diagnosis: {$diagnoses} removido(s)");
        }

        if ($withClinical) {
            $notes = ClinicalNote::query()->whereIn('appointment_id', $appointmentIds)->delete();
            if ($notes > 0) {
                $this->line("  - ClinicalNote: {$notes} removido(s)");
            }

            $certs = MedicalCertificate::query()->whereIn('appointment_id', $appointmentIds)->delete();
            if ($certs > 0) {
                $this->line("  - MedicalCertificate: {$certs} removido(s)");
            }
        }
    }
}
