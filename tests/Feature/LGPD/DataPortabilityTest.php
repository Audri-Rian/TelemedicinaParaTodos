<?php

namespace Tests\Feature\LGPD;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DataPortabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_authenticated_user_can_export_personal_data(): void
    {
        Storage::fake('local');

        $response = $this->actingAs($this->user)
            ->get(route('lgpd.data-portability.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        $this->assertStringContainsString('dados_pessoais_', $response->headers->get('Content-Disposition'));
    }

    public function test_guest_cannot_export_personal_data(): void
    {
        $response = $this->get(route('lgpd.data-portability.export'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_data_portability_page(): void
    {
        $response = $this->get(route('lgpd.data-portability.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_data_access_report(): void
    {
        $response = $this->get(route('lgpd.data-access-report.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_export_generates_json_with_user_data(): void
    {
        Storage::fake('local');

        $response = $this->actingAs($this->user)
            ->get(route('lgpd.data-portability.export'));

        $response->assertOk();
        $content = $response->streamedContent();
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('user', $data);
    }
}
