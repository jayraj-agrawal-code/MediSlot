<?php

use App\Models\Admin;
use App\Models\Doctor;

it('rejects unauthenticated access to the doctors list', function () {
    $this->getJson('/api/v1/admin/doctors')->assertUnauthorized();
});

it('lists doctors with their weekly availability', function () {
    $admin = Admin::factory()->create();
    $doctor = Doctor::factory()->create();
    $doctor->availabilities()->create([
        'day_of_week' => 1,
        'start_time' => '09:00',
        'end_time' => '17:00',
    ]);

    $this->actingAs($admin, 'admin')
        ->getJson('/api/v1/admin/doctors')
        ->assertOk()
        ->assertJsonPath('data.0.id', $doctor->id)
        ->assertJsonPath('data.0.availabilities.0.day_label', 'Monday')
        ->assertJsonPath('data.0.availabilities.0.start_time', '09:00')
        ->assertJsonPath('data.0.availabilities.0.end_time', '17:00');
});

it('creates a doctor', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')->postJson('/api/v1/admin/doctors', [
        'name' => 'Dr. House',
        'email' => 'house@example.com',
        'phone' => '5551234567',
        'specialization' => 'Diagnostics',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Dr. House')
        ->assertJsonPath('data.is_active', true);

    $this->assertDatabaseHas('doctors', ['email' => 'house@example.com']);
});

it('validates required fields when creating a doctor', function () {
    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/doctors', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('name');
});

it('validates unique email when creating a doctor', function () {
    $admin = Admin::factory()->create();
    Doctor::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($admin, 'admin')
        ->postJson('/api/v1/admin/doctors', [
            'name' => 'Dr. Duplicate',
            'email' => 'taken@example.com',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('email');
});

it('shows a single doctor', function () {
    $admin = Admin::factory()->create();
    $doctor = Doctor::factory()->create();

    $this->actingAs($admin, 'admin')
        ->getJson("/api/v1/admin/doctors/{$doctor->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $doctor->id);
});

it('updates a doctor', function () {
    $admin = Admin::factory()->create();
    $doctor = Doctor::factory()->create(['name' => 'Dr. Old Name']);

    $this->actingAs($admin, 'admin')
        ->putJson("/api/v1/admin/doctors/{$doctor->id}", [
            'name' => 'Dr. New Name',
            'is_active' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Dr. New Name')
        ->assertJsonPath('data.is_active', false);

    $this->assertDatabaseHas('doctors', ['id' => $doctor->id, 'name' => 'Dr. New Name', 'is_active' => false]);
});

it('deletes a doctor and cascades their availability', function () {
    $admin = Admin::factory()->create();
    $doctor = Doctor::factory()->create();
    $availability = $doctor->availabilities()->create([
        'day_of_week' => 1,
        'start_time' => '09:00',
        'end_time' => '17:00',
    ]);

    $this->actingAs($admin, 'admin')
        ->deleteJson("/api/v1/admin/doctors/{$doctor->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('doctors', ['id' => $doctor->id]);
    $this->assertDatabaseMissing('doctor_availabilities', ['id' => $availability->id]);
});
