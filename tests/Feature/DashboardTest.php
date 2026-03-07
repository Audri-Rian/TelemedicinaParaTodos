<?php

namespace Tests\Feature;

use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $doctor = Doctor::factory()->create();
        $this->actingAs($doctor->user);

        // /dashboard redirects to role dashboard; doctor goes to /doctor/dashboard
        $response = $this->get(route('doctor.dashboard'));
        $response->assertStatus(200);
    }
}
