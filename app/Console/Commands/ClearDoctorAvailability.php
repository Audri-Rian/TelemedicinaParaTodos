<?php

namespace App\Console\Commands;

use App\Models\Doctor;
use Illuminate\Console\Command;

class ClearDoctorAvailability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'doctor:clear-availability 
                            {--doctor-id= : ID específico do médico para limpar}
                            {--all : Limpar disponibilidade de todos os médicos}
                            {--confirm : Confirmar a operação sem prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpa a disponibilidade (horários) de médicos específicos ou todos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $doctorId = $this->option('doctor-id');
        $all = $this->option('all');
        $confirm = $this->option('confirm');

        if (! $doctorId && ! $all) {
            $this->error('Você deve especificar --doctor-id=ID ou --all');

            return 1;
        }

        if ($all && $doctorId) {
            $this->error('Não é possível usar --doctor-id e --all ao mesmo tempo');

            return 1;
        }

        // Buscar médicos
        $doctorQuery = null;
        if ($all) {
            $doctorQuery = Doctor::query()
                ->select(['id', 'user_id'])
                ->with('user:id,name');
            $message = 'todos os médicos';
        } else {
            $doctor = Doctor::query()
                ->select(['id', 'user_id'])
                ->with('user:id,name')
                ->find($doctorId);
            if (! $doctor) {
                $this->error("Médico com ID {$doctorId} não encontrado");

                return 1;
            }
            $doctors = collect([$doctor]);
            $message = "o médico {$doctor->user->name} (ID: {$doctorId})";
        }

        // Confirmar operação
        if (! $confirm) {
            if (! $this->confirm("Tem certeza que deseja limpar a disponibilidade de {$message}?")) {
                $this->info('Operação cancelada.');

                return 0;
            }
        }

        $totalSlots = 0;
        $totalLocations = 0;
        $processedDoctors = 0;

        $processDoctor = function (Doctor $doctor) use (&$processedDoctors, &$totalLocations, &$totalSlots): void {
            $this->info("Limpando disponibilidade do médico: {$doctor->user->name}");

            // Contar slots antes de deletar
            $slotsCount = $doctor->availabilitySlots()->count();
            $locationsCount = $doctor->serviceLocations()->count();

            $blockedCount = $doctor->blockedDates()->withTrashed()->count();

            $doctor->availabilitySlots()->withTrashed()->forceDelete();
            $doctor->blockedDates()->withTrashed()->forceDelete();
            $doctor->serviceLocations()->withTrashed()->forceDelete();

            $doctor->update(['availability_schedule' => null]);

            $totalSlots += $slotsCount;
            $totalLocations += $locationsCount;
            $processedDoctors++;

            $this->line("  - {$slotsCount} slots de disponibilidade removidos");
            $this->line("  - {$blockedCount} datas bloqueadas removidas");
            $this->line("  - {$locationsCount} locais de atendimento removidos");
        };

        if ($all && $doctorQuery) {
            $doctorQuery->chunkById(100, function ($doctors) use ($processDoctor) {
                foreach ($doctors as $doctor) {
                    $processDoctor($doctor);
                }
            });
        } else {
            foreach ($doctors as $doctor) {
                $processDoctor($doctor);
            }
        }

        $this->info('✅ Operação concluída!');
        $this->info("Total: {$totalSlots} slots e {$totalLocations} locais removidos de {$processedDoctors} médico(s)");

        return 0;
    }
}
