<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_five_failed_logins_lock_only_that_email(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'a@test.cl', 'password' => 'mala']);
        }

        $this->post('/login', ['email' => 'a@test.cl', 'password' => 'mala'])
            ->assertSessionHasErrors(['email' => 'Demasiados intentos, espera un minuto.']);

        // Mismo laboratorio (misma IP), otro alumno: no queda bloqueado.
        $this->post('/login', ['email' => 'b@test.cl', 'password' => 'mala'])
            ->assertSessionHasErrors(['email' => 'Las credenciales son incorrectas.']);
    }
}
