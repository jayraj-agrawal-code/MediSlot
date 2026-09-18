<?php

use App\Models\Admin;

beforeEach(function () {
    $this->withHeaders(['Referer' => 'http://localhost:5173']);
});

it('logs an admin in with valid credentials and starts a session', function () {
    $admin = Admin::factory()->create([
        'email' => 'login-ok@example.com',
        'password' => bcrypt('secret-password'),
    ]);

    $response = $this->postJson('/api/v1/admin/login', [
        'email' => 'login-ok@example.com',
        'password' => 'secret-password',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.id', $admin->id)
        ->assertJsonPath('data.email', 'login-ok@example.com')
        ->assertJsonMissing(['password']);

    $this->assertAuthenticatedAs($admin, 'admin');
});

it('rejects an invalid password', function () {
    Admin::factory()->create([
        'email' => 'login-bad@example.com',
        'password' => bcrypt('secret-password'),
    ]);

    $response = $this->postJson('/api/v1/admin/login', [
        'email' => 'login-bad@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors('email');

    $this->assertGuest('admin');
});

it('validates that email and password are required', function () {
    $response = $this->postJson('/api/v1/admin/login', []);

    $response->assertStatus(422)->assertJsonValidationErrors(['email', 'password']);
});

it('locks the admin out after too many failed attempts', function () {
    Admin::factory()->create([
        'email' => 'login-lockout@example.com',
        'password' => bcrypt('secret-password'),
    ]);

    $attempt = fn () => $this->postJson('/api/v1/admin/login', [
        'email' => 'login-lockout@example.com',
        'password' => 'wrong-password',
    ]);

    for ($i = 0; $i < 5; $i++) {
        $attempt()->assertStatus(422);
    }

    $attempt()
        ->assertStatus(422)
        ->assertJsonPath('errors.email.0', fn (string $message) => str_contains($message, 'Too many login attempts'));
});

it('rejects unauthenticated access to the admin profile', function () {
    $this->getJson('/api/v1/admin/me')->assertUnauthorized();
});

it('returns the authenticated admin profile', function () {
    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/me')
        ->assertOk()
        ->assertJsonPath('data.id', $admin->id)
        ->assertJsonPath('data.email', $admin->email);
});

it('logs the admin out and invalidates the session', function () {
    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/logout')
        ->assertNoContent();

    $this->assertGuest('admin');

    $this->getJson('/api/v1/admin/me')->assertUnauthorized();
});
