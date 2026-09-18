<?php

use App\Enums\AppointmentStatus;
use App\Enums\DayOfWeek;
use App\Models\Appointment;
use App\Models\Patient;

it('rejects unauthenticated cancellation attempts', function () {
    $appointment = Appointment::factory()->create();

    $this->postJson("/api/v1/patient/appointments/{$appointment->id}/cancel")->assertUnauthorized();
});

it('lets a patient cancel their own upcoming appointment', function () {
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->for($patient)->create();

    $response = $this->actingAs($patient, 'patient')
        ->postJson("/api/v1/patient/appointments/{$appointment->id}/cancel");

    $response->assertOk()->assertJsonPath('data.status', 'cancelled');

    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'status' => 'cancelled',
        'slot_lock' => null,
    ]);
});

it('frees the slot for another patient after cancellation', function () {
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->for($patient)->create();
    $doctor = $appointment->doctor;
    $doctor->availabilities()->create([
        'day_of_week' => DayOfWeek::from($appointment->appointment_date->isoWeekday()),
        'start_time' => '09:00',
        'end_time' => '10:00',
    ]);

    $this->actingAs($patient, 'patient')
        ->postJson("/api/v1/patient/appointments/{$appointment->id}/cancel")
        ->assertOk();

    $otherPatient = Patient::factory()->create();
    $this->actingAs($otherPatient, 'patient')->postJson('/api/v1/patient/appointments', [
        'doctor_id' => $doctor->id,
        'date' => $appointment->appointment_date->format('Y-m-d'),
        'start_time' => '09:00',
    ])->assertCreated();
});

it('does not let a patient cancel another patient appointment', function () {
    $owner = Patient::factory()->create();
    $intruder = Patient::factory()->create();
    $appointment = Appointment::factory()->for($owner)->create();

    $this->actingAs($intruder, 'patient')
        ->postJson("/api/v1/patient/appointments/{$appointment->id}/cancel")
        ->assertForbidden();

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'booked']);
});

it('rejects cancelling an already cancelled appointment', function () {
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->for($patient)->cancelled()->create();

    $this->actingAs($patient, 'patient')
        ->postJson("/api/v1/patient/appointments/{$appointment->id}/cancel")
        ->assertStatus(422)
        ->assertJsonValidationErrors('appointment');
});

it('rejects cancelling an appointment whose time has already passed', function () {
    $patient = Patient::factory()->create();
    $appointment = Appointment::factory()->for($patient)->past()->create();

    expect($appointment->status)->toBe(AppointmentStatus::Booked);

    $this->actingAs($patient, 'patient')
        ->postJson("/api/v1/patient/appointments/{$appointment->id}/cancel")
        ->assertStatus(422)
        ->assertJsonValidationErrors('appointment');

    $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => 'booked']);
});

it('lists the patient own appointments', function () {
    $patient = Patient::factory()->create();
    Appointment::factory()->for($patient)->count(2)->create();
    Appointment::factory()->create(); // another patient's appointment

    $response = $this->actingAs($patient, 'patient')->getJson('/api/v1/patient/appointments');

    $response->assertOk()->assertJsonCount(2, 'data');
});
