<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Specialization;

class SpecializationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $specializations = [
            'Cardiologia',
            'Dermatologia',
            'Endocrinologia',
            'Gastroenterologia',
            'Neurologia',
            'Pediatria',
            'Psiquiatria',
            'Ortopedia',
            'Oftalmologia',
            'Urologia',
            'Ginecologia',
            'Obstetrícia',
            'Anestesiologia',
            'Radiologia',
            'Patologia',
            'Medicina Interna',
            'Cirurgia Geral',
            'Cirurgia Plástica',
            'Otorrinolaringologia',
            'Pneumologia',
            'Oncologia',
            'Hematologia',
            'Nefrologia',
            'Reumatologia',
            'Alergologia',
            'Infectologia',
            'Geriatria',
            'Medicina do Trabalho',
            'Medicina Esportiva',
            'Medicina Preventiva',
            'Medicina de Família',
            'Clínica Médica',
            'Cirurgia Cardiovascular',
            'Cirurgia Torácica',
            'Cirurgia Neurológica',
            'Cirurgia Pediátrica',
            'Cirurgia Oncológica',
            'Neurocirurgia',
            'Cirurgia de Cabeça e Pescoço',
            'Cirurgia Vascular',
            'Cirurgia Digestiva',
            'Cirurgia Bariátrica',
            'Cirurgia Estética',
            'Cirurgia Reconstrutiva',
            'Traumatologia',
            'Medicina Legal',
            'Medicina Nuclear',
            'Medicina Intensiva',
            'Medicina de Emergência',
            'Medicina Hospitalar',
            'Medicina Ambulatorial',
            'Medicina de Urgência',
            'Medicina de Tráfego',
            'Medicina Aeroespacial',
            'Medicina Subaquática',
            'Medicina Hiperbárica',
            'Medicina do Sono',
            'Medicina Paliativa',
            'Medicina Integrativa',
            'Medicina Funcional',
            'Medicina Estética',
            'Medicina Antienvelhecimento',
            'Medicina Regenerativa',
            'Medicina Genômica',
            'Medicina Personalizada',
            'Medicina Digital',
            'Telemedicina',
            'Medicina Preventiva e Social',
            'Saúde Pública',
            'Epidemiologia',
            'Medicina Veterinária',
            'Odontologia',
            'Fisioterapia',
            'Terapia Ocupacional',
            'Fonoaudiologia',
            'Psicologia',
            'Nutrição',
            'Farmácia',
            'Enfermagem',
            'Biomedicina',
            'Fisioterapia Cardiorrespiratória',
            'Fisioterapia Neurológica',
            'Fisioterapia Ortopédica',
            'Fisioterapia Pediátrica',
            'Fisioterapia Geriátrica',
            'Fisioterapia Esportiva',
            'Fisioterapia Aquática',
            'Fisioterapia Respiratória',
            'Fisioterapia Uroginecológica',
            'Fisioterapia Dermatofuncional',
            'Fisioterapia Traumato-Ortopédica',
            'Fisioterapia Reumatológica',
            'Fisioterapia Oncológica',
            'Fisioterapia Intensiva',
            'Fisioterapia Ambulatorial',
            'Fisioterapia Hospitalar',
            'Fisioterapia Domiciliar',
            'Fisioterapia Laboral',
            'Fisioterapia Preventiva',
            'Fisioterapia Estética',
            'Fisioterapia Funcional',
            'Fisioterapia Manual',
            'Fisioterapia Cardiovascular',
        ];

        // Normaliza e remove duplicatas (mesmo nome com capitalização diferente)
        $normalized = array_unique(array_map(
            fn (string $name) => ucwords(strtolower(trim($name))),
            $specializations
        ));

        foreach ($normalized as $name) {
            Specialization::firstOrCreate(
                ['name' => $name],
                ['name' => $name]
            );
        }
    }
}