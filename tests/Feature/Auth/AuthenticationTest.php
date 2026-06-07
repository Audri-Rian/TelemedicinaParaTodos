<?php

namespace Tests\Feature\Auth;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_login_screen_stores_safe_redirect_query_as_intended_url(): void
    {
        $this->get('/login?redirect='.rawurlencode('/api/documentation'))
            ->assertStatus(200);

        $this->assertSame('/api/documentation', session('url.intended'));
    }

    public function test_login_screen_ignores_open_redirect_in_redirect_query(): void
    {
        $this->get('/login?redirect='.rawurlencode('//evil.example/phish'))
            ->assertStatus(200);

        $this->assertFalse(session()->has('url.intended'));
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $doctor = Doctor::factory()->create();
        $user = $doctor->user;

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('doctor.dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $doctor = Doctor::factory()->create();
        $user = $doctor->user;

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout()
    {
        $doctor = Doctor::factory()->create();
        $user = $doctor->user;

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_users_are_rate_limited()
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertStatus(302)->assertSessionHasErrors([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');

        $errors = session('errors');

        $this->assertStringContainsString('Too many login attempts', $errors->first('email'));
    }
}
