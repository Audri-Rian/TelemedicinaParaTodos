<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Specialization;
use App\Models\User;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{
    public const DEMO_DOCTOR_EMAIL = 'demo.doctor@telemedicina.test';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $availabilityService = new AvailabilityService;

        $specializations = Specialization::all()->keyBy('name');

        $user = User::updateOrCreate(
            ['email' => self::DEMO_DOCTOR_EMAIL],
            [
                'name' => 'Dr. Ana Costa (Demo)',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $availabilitySchedule = $this->generateAvailabilitySchedule('08:00', '18:00', false);

        $doctor = Doctor::updateOrCreate(
            ['user_id' => $user->id],
            [
                'crm' => 'CRM-123456',
                'biography' => 'Cardiologista demo com agenda, teleconsulta e integrações para testes locais.',
                'status' => Doctor::STATUS_ACTIVE,
                'availability_schedule' => $availabilitySchedule,
                'consultation_fee' => 350.00,
                'license_number' => 'LIC-DEMO-DOCTOR',
                'license_expiry_date' => Carbon::now()->addYears(3),
            ]
        );

        if ($doctor->serviceLocations()->doesntExist()) {
            $teleconsultationLocation = $availabilityService->createServiceLocation(
                $doctor,
                'Teleconsulta',
                \App\Models\ServiceLocation::TYPE_TELECONSULTATION,
                null,
                null,
                'Consulta realizada por videoconferência'
            );

            $officeLocation = $availabilityService->createServiceLocation(
                $doctor,
                'Consultório Principal',
                \App\Models\ServiceLocation::TYPE_OFFICE,
                'Endereço do consultório',
                null,
                null
            );

            foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day) {
                $availabilityService->createRecurringSlot(
                    $doctor,
                    $day,
                    '08:00',
                    '18:00',
                    $teleconsultationLocation->id
                );

                $availabilityService->createRecurringSlot(
                    $doctor,
                    $day,
                    '08:00',
                    '18:00',
                    $officeLocation->id
                );
            }
        }

        $cardiology = $specializations->get('Cardiologia');
        if ($cardiology !== null) {
            $doctor->specializations()->syncWithoutDetaching([$cardiology->id]);
        }
    }

    /**
     * Gera slots de tempo de 45 minutos entre start e end, respeitando intervalo de almoço
     */
    private function generateTimeSlots(string $start, string $end): array
    {
        $slots = [];

        [$startHour, $startMin] = explode(':', $start);
        [$endHour, $endMin] = explode(':', $end);

        $startMinutes = (int) $startHour * 60 + (int) $startMin;
        $endMinutes = (int) $endHour * 60 + (int) $endMin;

        $lunchStart = 12 * 60;
        $lunchEnd = 14 * 60;

        $currentMinutes = $startMinutes;
        $slotDuration = 45;

        while ($currentMinutes + $slotDuration <= $endMinutes) {
            $slotEnd = $currentMinutes + $slotDuration;

            if (! ($currentMinutes >= $lunchStart && $currentMinutes < $lunchEnd) &&
                ! ($slotEnd > $lunchStart && $slotEnd <= $lunchEnd) &&
                ! ($currentMinutes < $lunchStart && $slotEnd > $lunchEnd)) {
                $hours = floor($currentMinutes / 60);
                $minutes = $currentMinutes % 60;
                $slots[] = sprintf('%02d:%02d', $hours, $minutes);
            }

            $currentMinutes += $slotDuration;
        }

        return $slots;
    }

    /**
     * Gera availability_schedule para segunda a sexta (e opcionalmente sábado)
     */
    private function generateAvailabilitySchedule(
        string $startTime,
        string $endTime,
        bool $includeSaturday = false
    ): array {
        $schedule = [];

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        if ($includeSaturday) {
            $days[] = 'saturday';
        }

        foreach ($days as $day) {
            if ($day === 'saturday') {
                $dayStart = '08:00';
                $dayEnd = '12:00';
            } else {
                $dayStart = $startTime;
                $dayEnd = $endTime;
            }

            $slots = $this->generateTimeSlots($dayStart, $dayEnd);

            $schedule[$day] = [
                'start' => $dayStart,
                'end' => $dayEnd,
                'slots' => $slots,
            ];
        }

        $schedule['sunday'] = null;

        return $schedule;
    }
}
