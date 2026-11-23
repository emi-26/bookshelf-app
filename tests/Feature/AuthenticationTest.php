<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_register_page(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_user_can_register_with_valid_information(): void
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'register@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/');

        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'name' => 'テスト太郎',
            'email' => 'register@example.com',
        ]);

        $user = User::where('email', 'register@example.com')
            ->firstOrFail();

        $this->assertTrue(
            Hash::check('password123', $user->password)
        );
    }

    public function test_registration_requires_name_email_and_password(): void
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors([
            'name',
            'email',
            'password',
        ]);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_invalid_email(): void
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'duplicate@example.com',
        ]);

        $response = $this->post('/register', [
            'name' => '別のユーザー',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_rejects_short_password(): void
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'short@example.com',
            'password' => 'pass123',
            'password_confirmation' => 'pass123',
        ]);

        $response->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_password_confirmation_mismatch(): void
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'mismatch@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }
}
