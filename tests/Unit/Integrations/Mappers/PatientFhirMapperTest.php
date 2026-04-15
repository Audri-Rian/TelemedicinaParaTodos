<?php

namespace Tests\Unit\Integrations\Mappers;

use App\Integrations\Mappers\PatientFhirMapper;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Testes do PatientFhirMapper.
 *
 * Nota importante: os testes usam instâncias não persistidas (`new Patient(...)`)
 * com `setRawAttributes()` para injetar valores de CPF/CNS diretamente, evitando:
 *   (a) o conflito entre o mutator setCpfAttribute() e o cast 'encrypted' do model,
 *       que faz o valor ser salvo como plaintext e falhar na leitura;
 *   (b) os limites VARCHAR(11/15) das colunas cpf/cns que não comportam valores
 *       cifrados pelo cast encrypted.
 *
 * Isto reflete que o foco destes testes é a lógica de mapeamento FHIR — não o
 * comportamento de encriptação do model (esse fica em testes dedicados do Patient
 * model, fora do escopo desta suite).
 */
class PatientFhirMapperTest extends TestCase
{
    use RefreshDatabase;

    private PatientFhirMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new PatientFhirMapper();
    }

    /**
     * Cria um Patient não persistido com os atributos e relacionamento dados.
     * Campos com cast 'encrypted' (cpf, cns) são pré-cifrados para simular
     * o comportamento esperado na leitura do model.
     */
    private function makePatient(array $attrs = [], ?User $user = null): Patient
    {
        $user = $user ?? User::factory()->make();

        $defaults = [
            'id' => (string) Str::uuid(),
            'user_id' => $user->id ?? (string) Str::uuid(),
            'gender' => 'male',
            'date_of_birth' => '1985-03-20',
            'phone_number' => '11987654321',
            'status' => Patient::STATUS_ACTIVE,
        ];

        $merged = array_merge($defaults, $attrs);

        // Pré-cifrar campos que têm cast 'encrypted' no model
        foreach (['cpf', 'cns'] as $encrypted) {
            if (array_key_exists($encrypted, $merged) && is_string($merged[$encrypted]) && $merged[$encrypted] !== '') {
                $merged[$encrypted] = Crypt::encryptString($merged[$encrypted]);
            }
        }

        $patient = new Patient();
        $patient->setRawAttributes($merged);
        $patient->setRelation('user', $user);

        return $patient;
    }

    public function test_maps_complete_patient_to_fhir_resource(): void
    {
        $user = User::factory()->make([
            'name' => 'João da Silva',
            'email' => 'joao@example.com',
        ]);

        $patient = $this->makePatient([
            'gender' => 'male',
            'date_of_birth' => '1985-03-20',
            'phone_number' => '11987654321',
            'cpf' => '12345678900',
            'cns' => '706000000000001',
            'mother_name' => 'Maria da Silva',
        ], $user);

        $fhir = $this->mapper->toFhir($patient);

        $this->assertEquals('Patient', $fhir['resourceType']);
        $this->assertEquals('1985-03-20', $fhir['birthDate']);
        $this->assertEquals('male', $fhir['gender']);
        $this->assertEquals('João da Silva', $fhir['name'][0]['text']);

        // Identifiers: interno + CNS + CPF
        $this->assertCount(3, $fhir['identifier']);
        $this->assertEquals($patient->id, $fhir['identifier'][0]['value']);
        $this->assertEquals('706000000000001', $fhir['identifier'][1]['value']);
        $this->assertEquals('http://rnds.saude.gov.br/fhir/r4/NamingSystem/cns', $fhir['identifier'][1]['system']);
        $this->assertEquals('12345678900', $fhir['identifier'][2]['value']);
        $this->assertEquals('http://rnds.saude.gov.br/fhir/r4/NamingSystem/cpf', $fhir['identifier'][2]['system']);

        // Telecom: phone + email
        $this->assertCount(2, $fhir['telecom']);
        $this->assertEquals('phone', $fhir['telecom'][0]['system']);
        $this->assertEquals('11987654321', $fhir['telecom'][0]['value']);
        $this->assertEquals('email', $fhir['telecom'][1]['system']);
        $this->assertEquals('joao@example.com', $fhir['telecom'][1]['value']);

        // Extension de mother_name
        $this->assertCount(1, $fhir['extension']);
        $this->assertEquals(
            'http://rnds.saude.gov.br/fhir/r4/StructureDefinition/nome-mae',
            $fhir['extension'][0]['url']
        );
        $this->assertEquals('Maria da Silva', $fhir['extension'][0]['valueString']);
    }

    public function test_minimal_patient_omits_optional_fields(): void
    {
        $user = User::factory()->make(['name' => 'Paciente Mínimo', 'email' => 'min@example.com']);

        // Patient mínimo: sem cns/cpf/mother_name, mas com gender (NOT NULL no schema)
        $patient = $this->makePatient([
            'gender' => 'other',
            'phone_number' => null,
            'cpf' => null,
            'cns' => null,
            'mother_name' => null,
        ], $user);
        // Unset date_of_birth para simular paciente sem data de nascimento
        $attrs = $patient->getAttributes();
        unset($attrs['date_of_birth']);
        $patient->setRawAttributes($attrs);

        $fhir = $this->mapper->toFhir($patient);

        $this->assertEquals('Patient', $fhir['resourceType']);
        $this->assertArrayNotHasKey('birthDate', $fhir);
        $this->assertArrayNotHasKey('extension', $fhir);

        // Apenas 1 identifier (interno) quando CPF e CNS não existem
        $this->assertCount(1, $fhir['identifier']);

        // Telecom com apenas email (sem phone)
        $this->assertCount(1, $fhir['telecom']);
        $this->assertEquals('email', $fhir['telecom'][0]['system']);
    }

    public function test_gender_mapping(): void
    {
        $mappings = [
            'male' => 'male',
            'female' => 'female',
            'other' => 'other',
        ];

        foreach ($mappings as $input => $expected) {
            $patient = $this->makePatient(['gender' => $input]);
            $fhir = $this->mapper->toFhir($patient);
            $this->assertEquals($expected, $fhir['gender'], "Gender '{$input}' should map to '{$expected}'");
        }
    }

    public function test_birthdate_is_formatted_as_iso_date(): void
    {
        $patient = $this->makePatient(['date_of_birth' => '1990-12-25']);

        $fhir = $this->mapper->toFhir($patient);

        $this->assertEquals('1990-12-25', $fhir['birthDate']);
    }

    public function test_patient_without_cns_omits_cns_identifier(): void
    {
        $patient = $this->makePatient([
            'cns' => null,
            'cpf' => '98765432100',
        ]);

        $fhir = $this->mapper->toFhir($patient);

        $cnsIdentifiers = array_filter(
            $fhir['identifier'],
            fn ($id) => ($id['system'] ?? '') === 'http://rnds.saude.gov.br/fhir/r4/NamingSystem/cns'
        );

        $this->assertEmpty($cnsIdentifiers);
    }

    public function test_patient_without_mother_name_omits_extension(): void
    {
        $patient = $this->makePatient(['mother_name' => null]);

        $fhir = $this->mapper->toFhir($patient);

        $this->assertArrayNotHasKey('extension', $fhir);
    }

    public function test_user_relationship_is_loaded_if_not_already(): void
    {
        // Para este teste, precisamos de um Patient persistido sem CPF/CNS.
        $user = User::factory()->create(['email' => 'lazy@example.com']);
        $patient = Patient::factory()->create([
            'user_id' => $user->id,
            'cpf' => null,
            'cns' => null,
        ]);

        // Forçar fresh model sem relationship carregado
        $freshPatient = Patient::find($patient->id);
        $this->assertFalse($freshPatient->relationLoaded('user'));

        $fhir = $this->mapper->toFhir($freshPatient);

        $this->assertTrue($freshPatient->relationLoaded('user'));
        $emailTelecom = collect($fhir['telecom'])->firstWhere('system', 'email');
        $this->assertEquals('lazy@example.com', $emailTelecom['value']);
    }

    public function test_identifier_system_uses_config_fhir_url(): void
    {
        config(['integrations.fhir.system_url' => 'https://custom.example.com/fhir']);

        $patient = $this->makePatient();

        $fhir = $this->mapper->toFhir($patient);

        $this->assertStringContainsString(
            'https://custom.example.com/fhir/patient-id',
            $fhir['identifier'][0]['system']
        );
    }
}
