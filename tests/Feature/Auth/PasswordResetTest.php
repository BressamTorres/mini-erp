<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    public function test_password_recovery_screen_is_not_available(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertNotFound();
    }

    public function test_password_recovery_request_is_not_available(): void
    {
        $response = $this->post('/forgot-password', ['email' => 'admin@example.com']);

        $response->assertNotFound();
    }

    public function test_password_reset_endpoint_is_not_available(): void
    {
        $response = $this->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => 'admin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertNotFound();
    }
}
