<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Specialization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar especializações existentes
        $specializations = Specialization::all()->keyBy('name');

        // Lista de médicos com seus dados
        $doctorsData = [
            [
                'name' => 'Dr. Ana Costa',
                'email' => 'ana.costa@telemedicina.com',
                'crm' => 'CRM-123456',
                'specializations' => ['Cardiologia'],
                'biography' => 'Cardiologista com mais de 15 anos de experiência em doenças cardiovasculares. Especialista em prevenção e tratamento de hipertensão, arritmias e insuficiência cardíaca.',
                'consultation_fee' => 350.00,
                'status' => Doctor::STATUS_ACTIVE,
                'start_time' => '08:00',
                'end_time' => '18:00',
                'include_saturday' => false,
            ],
            [
                'name' => 'Dr. Bruno Alves',
                'email' => 'bruno.alves@telemedicina.com',
                'crm' => 'CRM-234567',
                'specializations' => ['Dermatologia'],
                'biography' => 'Dermatologista especializado em tratamentos de pele, cabelo e unhas. Experiência em dermatologia clínica, estética e cirúrgica.',
                'consultation_fee' => 280.00,
                'status' => Doctor::STATUS_ACTIVE,
                'start_time' => '09:00',
                'end_time' => '18:00',
                'include_saturday' => true,
            ],
            [
                'name' => 'Dr. Carlos Lima',
                'email' => 'carlos.lima@telemedicina.com',
                'crm' => 'CRM-345678',
                'specializations' => ['Clínica Médica', 'Medicina Interna'],
                'biography' => 'Clínico geral e médico internista com vasta experiência em atendimento primário e medicina preventiva. Atendimento para todas as idades.',
                'consultation_fee' => 150.00,
                'status' => Doctor::STATUS_ACTIVE,
                'start_time' => '08:00',
                'end_time' => '17:00',
                'include_saturday' => false,
            ],
            [
                'name' => 'Dra. Maria Silva',
                'email' => 'maria.silva@telemedicina.com',
                'crm' => 'CRM-456789',
                'specializations' => ['Pediatria'],
                'biography' => 'Pediatra especializada em cuidados com crianças e adolescentes há mais de 12 anos. Experiência em puericultura, vacinação e desenvolvimento infantil.',
                'consultation_fee' => 200.00,
                'status' => Doctor::STATUS_ACTIVE,
                'start_time' => '08:00',
                'end_time' => '12:00',
                'include_saturday' => true,
            ],
            [
                'name' => 'Dr. João Santos',
                'email' => 'joao.santos@telemedicina.com',
                'crm' => 'CRM-567890',
                'specializations' => ['Ortopedia', 'Traumatologia'],
                'biography' => 'Ortopedista e traumatologista com especialização em cirurgia ortopédica e tratamento de lesões esportivas. Experiência em próteses e artroscopia.',
                'consultation_fee' => 400.00,
                'status' => Doctor::STATUS_ACTIVE,
                'start_time' => '14:00',
                'end_time' => '19:00',
                'include_saturday' => false,
            ],
            [
                'name' => 'Dra. Fernanda Oliveira',
                'email' => 'fernanda.oliveira@telemedicina.com',
                'crm' => 'CRM-678901',
                'specializations' => ['Psiquiatria', 'Psicologia'],
                'biography' => 'Psiquiatra e psicóloga com formação em terapia cognitivo-comportamental. Especializada em transtornos de ansiedade, depressão e saúde mental.',
                'consultation_fee' => 320.00,
                'status' => Doctor::STATUS_ACTIVE,
                'start_time' => '10:00',
                'end_time' => '18:00',
                'include_saturday' => false,
            ],
            [
                'name' => 'Dr. Rafael Gomes',
                'email' => 'rafael.gomes@telemedicina.com',
                'crm' => 'CRM-789012',
                'specializations' => ['Neurologia'],
                'biography' => 'Neurologista com expertise em doenças do sistema nervoso, epilepsia, cefaleias e distúrbios do movimento. Atuação em neurologia clínica e neurofisiologia.',
                'consultation_fee' => 450.00,
                'status' => Doctor::STATUS_ACTIVE,
                'start_time' => '08:00',
                'end_time' => '17:00',
                'include_saturday' => false,
            ],
            [
                'name' => 'Dra. Patrícia Costa',
                'email' => 'patricia.costa@telemedicina.com',
                'crm' => 'CRM-890123',
                'specializations' => ['Ginecologia', 'Obstetrícia'],
                'biography' => 'Ginecologista e obstetra com mais de 10 anos de experiência. Especializada em saúde da mulher, pré-natal de alto risco e cirurgia ginecológica.',
                'consultation_fee' => 300.00,
                'status' => Doctor::STATUS_ACTIVE,
                'start_time' => '09:00',
                'end_time' => '18:00',
                'include_saturday' => false,
            ],
            [
                'name' => 'Dr. Felipe Santos',
                'email' => 'felipe.santos@telemedicina.com',
                'crm' => 'CRM-901234',
                'specializations' => ['Oftalmologia'],
                'biography' => 'Oftalmologista especializado em cirurgia refrativa, catarata e doenças da retina. Experiência em tratamento de glaucoma e cirurgia ocular.',
                'consultation_fee' => 380.00,
                'status' => Doctor::STATUS_ACTIVE,
                'start_time' => '08:00',
                'end_time' => '12:00',
                'include_saturday' => false,
            ],
            [
                'name' => 'Dra. Juliana Almeida',
                'email' => 'juliana.almeida@telemedicina.com',
                'crm' => 'CRM-012345',
                'specializations' => ['Endocrinologia', 'Nutrição'],
                'biography' => 'Endocrinologista e nutricionista com foco em diabetes, distúrbios da tireoide, obesidade e metabolismo. Abordagem integrada de saúde e nutrição.',
                'consultation_fee' => 280.00,
                'status' => Doctor::STATUS_ACTIVE,
                'start_time' => '14:00',
                'end_time' => '18:00',
                'include_saturday' => false,
            ],
            [
                'name' => 'Dr. Lucas Pereira',
                'email' => 'lucas.pereira@telemedicina.com',
                'crm' => 'CRM-135792',
                'specializations' => ['Urologia'],
                'biography' => 'Urologista com experiência em cirurgia urológica, tratamento de cálculos renais, doenças prostáticas e urologia oncológica.',
                'consultation_fee' => 420.00,
                'status' => Doctor::STATUS_ACTIVE,
                'start_time' => '10:00',
                'end_time' => '18:00',
                'include_saturday' => false,
            ],
            [
                'name' => 'Dra. Camila Rodrigues',
                'email' => 'camila.rodrigues@telemedicina.com',
                'crm' => 'CRM-246813',
                'specializations' => ['Gastroenterologia', 'Cirurgia Digestiva'],
                'biography' => 'Gastroenterologista e cirurgiã digestiva especializada em doenças digestivas, fígado e vias biliares. Experiência em endoscopia digestiva e cirurgias do aparelho digestivo.',
                'consultation_fee' => 360.00,
                'status' => Doctor::STATUS_INACTIVE,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'include_saturday' => false,
            ],
            [
                'name' => 'Dr. André Martins',
                'email' => 'andre.martins@telemedicina.com',
                'crm' => 'CRM-369258',
                'specializations' => ['Pneumologia', 'Alergologia'],
                'biography' => 'Pneumologista e alergologista com expertise em doenças respiratórias, asma, DPOC e alergias. Tratamento de doenças pulmonares e alergias respiratórias.',
                'consultation_fee' => null,
                'status' => Doctor::STATUS_ACTIVE,
                'start_time' => '08:00',
                'end_time' => '18:00',
                'include_saturday' => false,
            ],
            [
                'name' => 'Dra. Beatriz Ferreira',
                'email' => 'beatriz.ferreira@telemedicina.com',
                'crm' => 'CRM-147258',
                'specializations' => ['Reumatologia'],
                'biography' => 'Reumatologista especializada em doenças autoimunes, artrites e doenças do tecido conjuntivo. Experiência em tratamento de doenças reumáticas.',
                'consultation_fee' => 340.00,
                'status' => Doctor::STATUS_INACTIVE,
                'start_time' => '14:00',
                'end_time' => '18:00',
                'include_saturday' => false,
            ],
            [
                'name' => 'Dr. Gabriel Souza',
                'email' => 'gabriel.souza@telemedicina.com',
                'crm' => 'CRM-258147',
                'specializations' => ['Otorrinolaringologia', 'Cirurgia de Cabeça e Pescoço'],
                'biography' => 'Otorrinolaringologista e cirurgião de cabeça e pescoço com experiência em cirurgias otorrinolaringológicas, tratamento de distúrbios auditivos e doenças da voz.',
                'consultation_fee' => null,
                'status' => Doctor::STATUS_SUSPENDED,
                'start_time' => '16:00',
                'end_time' => '19:00',
                'include_saturday' => false,
            ],
        ];

        foreach ($doctorsData as $doctorData) {
            // Criar User
            $user = User::create([
                'name' => $doctorData['name'],
                'email' => $doctorData['email'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);

            // Gerar availability_schedule
            $availabilitySchedule = $this->generateAvailabilitySchedule(
                $doctorData['start_time'],
                $doctorData['end_time'],
                $doctorData['include_saturday']
            );

            // Criar Doctor
            $doctor = Doctor::create([
                'user_id' => $user->id,
                'crm' => $doctorData['crm'],
                'biography' => $doctorData['biography'],
                'status' => $doctorData['status'],
                'availability_schedule' => $availabilitySchedule,
                'consultation_fee' => $doctorData['consultation_fee'],
                'license_number' => 'LIC-' . strtoupper(substr(md5($doctorData['crm']), 0, 8)),
                'license_expiry_date' => Carbon::now()->addYears(3),
            ]);

            // Vincular especializações
            foreach ($doctorData['specializations'] as $specializationName) {
                $specialization = $specializations->get($specializationName);
                if ($specialization) {
                    $doctor->specializations()->attach($specialization->id);
                }
            }
        }
    }

    /**
     * Gera slots de tempo de 45 minutos entre start e end, respeitando intervalo de almoço
     */
    private function generateTimeSlots(string $start, string $end): array
    {
        $slots = [];
        
        // Converter para minutos desde meia-noite
        [$startHour, $startMin] = explode(':', $start);
        [$endHour, $endMin] = explode(':', $end);
        
        $startMinutes = (int)$startHour * 60 + (int)$startMin;
        $endMinutes = (int)$endHour * 60 + (int)$endMin;
        
        // Intervalo de almoço: 12:00-14:00 (720-840 minutos)
        $lunchStart = 12 * 60; // 720 minutos
        $lunchEnd = 14 * 60;   // 840 minutos
        
        $currentMinutes = $startMinutes;
        $slotDuration = 45; // 45 minutos
        
        while ($currentMinutes + $slotDuration <= $endMinutes) {
            // Verificar se o slot não está no intervalo de almoço
            $slotEnd = $currentMinutes + $slotDuration;
            
            if (!($currentMinutes >= $lunchStart && $currentMinutes < $lunchEnd) &&
                !($slotEnd > $lunchStart && $slotEnd <= $lunchEnd) &&
                !($currentMinutes < $lunchStart && $slotEnd > $lunchEnd)) {
                
                // Formatar para HH:MM
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
            // Sábado geralmente tem horário reduzido (manhã)
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
        
        // Domingo sempre null
        $schedule['sunday'] = null;
        
        return $schedule;
    }
}

