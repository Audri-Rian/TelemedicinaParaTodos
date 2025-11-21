<?php

namespace App\Console\Commands;

use App\Models\Doctor;
use App\Models\AvailabilitySlot;
use App\Models\ServiceLocation;
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

        if (!$doctorId && !$all) {
            $this->error('Você deve especificar --doctor-id=ID ou --all');
            return 1;
        }

        if ($all && $doctorId) {
            $this->error('Não é possível usar --doctor-id e --all ao mesmo tempo');
            return 1;
        }

        // Buscar médicos
        if ($all) {
            $doctors = Doctor::all();
            $message = 'todos os médicos';
        } else {
            $doctor = Doctor::find($doctorId);
            if (!$doctor) {
                $this->error("Médico com ID {$doctorId} não encontrado");
                return 1;
            }
            $doctors = collect([$doctor]);
            $message = "o médico {$doctor->user->name} (ID: {$doctorId})";
        }

        // Confirmar operação
        if (!$confirm) {
            if (!$this->confirm("Tem certeza que deseja limpar a disponibilidade de {$message}?")) {
                $this->info('Operação cancelada.');
                return 0;
            }
        }

        $totalSlots = 0;
        $totalLocations = 0;

        foreach ($doctors as $doctor) {
            $this->info("Limpando disponibilidade do médico: {$doctor->user->name}");

            // Contar slots antes de deletar
            $slotsCount = $doctor->availabilitySlots()->count();
            $locationsCount = $doctor->serviceLocations()->count();

            // Deletar slots de disponibilidade
            $doctor->availabilitySlots()->delete();
            
            // Deletar locais de atendimento
            $doctor->serviceLocations()->delete();

            // Limpar availability_schedule (campo legacy)
            $doctor->update(['availability_schedule' => null]);

            $totalSlots += $slotsCount;
            $totalLocations += $locationsCount;

            $this->line("  - {$slotsCount} slots de disponibilidade removidos");
            $this->line("  - {$locationsCount} locais de atendimento removidos");
        }

        $this->info("✅ Operação concluída!");
        $this->info("Total: {$totalSlots} slots e {$totalLocations} locais removidos de {$doctors->count()} médico(s)");

        return 0;
    }
}