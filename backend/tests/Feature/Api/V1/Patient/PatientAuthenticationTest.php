<?php

use App\Models\Patient;

beforeEach(function () {
    $this->withHeaders(['Referer' => 'http://localhost:5173']);
});

it('registers a new patient', function () {
    $response = $this->postJson('/api/v1/patient/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ]);

    $response->assertCreated()->assertJsonPath('data.email', 'jane@example.com');

    $this->assertDatabaseHas('patients', ['email' => 'jane@example.com']);
    $this->assertGuest('patient');
});

it('rejects registration with a mismatched password confirmation', function () {
    $this->postJson('/api/v1/patient/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'not-matching',
    ])->assertStatus(422)->assertJsonValidationErrors('password');
});

it('rejects registration with a duplicate email', function () {
    Patient::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/v1/patient/register', [
        'name' => 'Jane Doe',
        'email' => 'taken@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('logs a patient in with valid credentials', function () {
    $patient = Patient::factory()->create([
        'email' => 'login-ok@example.com',
        'password' => bcrypt('secret-password'),
    ]);

    $this->postJson('/api/v1/patient/login', [
        'email' => 'login-ok@example.com',
        'password' => 'secret-password',
    ])->assertOk()->assertJsonPath('data.id', $patient->id);

    $this->assertAuthenticatedAs($patient, 'patient');
});

it('rejects an invalid password on login', function () {
    Patient::factory()->create([
        'email' => 'login-bad@example.com',
        'password' => bcrypt('secret-password'),
    ]);

    $this->postJson('/api/v1/patient/login', [
        'email' => 'login-bad@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');

    $this->assertGuest('patient');
});

it('rejects unauthenticated access to the patient profile', function () {
    $this->getJson('/api/v1/patient/me')->assertUnauthorized();
});

it('returns the authenticated patient and logs them out', function () {
    $patient = Patient::factory()->create();

    $this->actingAs($patient, 'patient')
        ->getJson('/api/v1/patient/me')
        ->assertOk()
        ->assertJsonPath('data.id', $patient->id);

    $this->actingAs($patient, 'patient')
        ->postJson('/api/v1/patient/logout')
        ->assertNoContent();

    $this->assertGuest('patient');
});
