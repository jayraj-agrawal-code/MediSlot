<?php

use App\Enums\DayOfWeek;
use App\Models\Admin;
use App\Models\Doctor;
use App\Models\Patient;

it('rejects unauthenticated access to setting availability', function () {
    $doctor = Doctor::factory()->create();

    $this->putJson("/api/v1/admin/doctors/{$doctor->id}/availabilities", ['availabilities' => []])
        ->assertUnauthorized();
});

it('sets a doctor weekly availability', function () {
    $admin = Admin::factory()->create();
    $doctor = Doctor::factory()->create();

    $response = $this->actingAs($admin, 'admin')
        ->putJson("/api/v1/admin/doctors/{$doctor->id}/availabilities", [
            'availabilities' => [
                ['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00'],
                ['day_of_week' => 3, 'start_time' => '10:00', 'end_time' => '14:00'],
            ],
        ]);

    $response->assertOk();

    expect($doctor->availabilities()->count())->toBe(2);

    $monday = $doctor->availabilities()->forDay(DayOfWeek::Monday)->sole();
    expect($monday->start_time->format('H:i'))->toBe('09:00');
    expect($monday->end_time->format('H:i'))->toBe('17:00');
});

it('replaces existing availability, clearing days no longer submitted', function () {
    $admin = Admin::factory()->create();
    $doctor = Doctor::factory()->create();
    $doctor->availabilities()->create(['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00']);
    $doctor->availabilities()->create(['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '17:00']);

    $this->actingAs($admin, 'admin')
        ->putJson("/api/v1/admin/doctors/{$doctor->id}/availabilities", [
            'availabilities' => [
                ['day_of_week' => 2, 'start_time' => '08:00', 'end_time' => '12:00'],
            ],
        ])
        ->assertOk();

    expect($doctor->availabilities()->count())->toBe(1);
    $this->assertDatabaseMissing('doctor_availabilities', ['doctor_id' => $doctor->id, 'day_of_week' => 1]);

    $tuesday = $doctor->availabilities()->forDay(DayOfWeek::Tuesday)->sole();
    expect($tuesday->start_time->format('H:i'))->toBe('08:00');
    expect($tuesday->end_time->format('H:i'))->toBe('12:00');
});

it('clears all availability when an empty set is submitted', function () {
    $admin = Admin::factory()->create();
    $doctor = Doctor::factory()->create();
    $doctor->availabilities()->create(['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00']);

    $this->actingAs($admin, 'admin')
        ->putJson("/api/v1/admin/doctors/{$doctor->id}/availabilities", ['availabilities' => []])
        ->assertOk();

    expect($doctor->availabilities()->count())->toBe(0);
});

it('allows multiple non-overlapping periods on the same day', function () {
    $admin = Admin::factory()->create();
    $doctor = Doctor::factory()->create();

    $this->actingAs($admin, 'admin')
        ->putJson("/api/v1/admin/doctors/{$doctor->id}/availabilities", [
            'availabilities' => [
                ['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '13:00'],
                ['day_of_week' => 1, 'start_time' => '14:00', 'end_time' => '17:00'],
            ],
        ])
        ->assertOk();

    $monday = $doctor->availabilities()->forDay(DayOfWeek::Monday)->orderBy('start_time')->get();
    expect($monday)->toHaveCount(2);
    expect($monday[0]->end_time->format('H:i'))->toBe('13:00');
    expect($monday[1]->start_time->format('H:i'))->toBe('14:00');
});

it('rejects overlapping periods on the same day', function () {
    $admin = Admin::factory()->create();
    $doctor = Doctor::factory()->create();

    $this->actingAs($admin, 'admin')
        ->putJson("/api/v1/admin/doctors/{$doctor->id}/availabilities", [
            'availabilities' => [
                ['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '13:00'],
                ['day_of_week' => 1, 'start_time' => '12:00', 'end_time' => '17:00'],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('availabilities');
});

it('rejects an end time that is not after the start time', function () {
    $admin = Admin::factory()->create();
    $doctor = Doctor::factory()->create();

    $this->actingAs($admin, 'admin')
        ->putJson("/api/v1/admin/doctors/{$doctor->id}/availabilities", [
            'availabilities' => [
                ['day_of_week' => 1, 'start_time' => '17:00', 'end_time' => '09:00'],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['availabilities.0.end_time']);
});

it('rejects an invalid day of week', function () {
    $admin = Admin::factory()->create();
    $doctor = Doctor::factory()->create();

    $this->actingAs($admin, 'admin')
        ->putJson("/api/v1/admin/doctors/{$doctor->id}/availabilities", [
            'availabilities' => [
                ['day_of_week' => 8, 'start_time' => '09:00', 'end_time' => '17:00'],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['availabilities.0.day_of_week']);
});

it('reschedules a booked appointment to the nearest slot when its period shrinks', function () {
    $admin = Admin::factory()->create();
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->create();
    $tomorrow = now()->addDay();
    $day = DayOfWeek::from($tomorrow->isoWeekday())->value;

    $doctor->availabilities()->create(['day_of_week' => $day, 'start_time' => '09:00', 'end_time' => '17:00']);

    $this->actingAs($patient, 'patient')->postJson('/api/v1/patient/appointments', [
        'doctor_id' => $doctor->id,
        'date' => $tomorrow->toDateString(),
        'start_time' => '11:00',
    ])->assertCreated();

    $response = $this->actingAs($admin, 'admin')
        ->putJson("/api/v1/admin/doctors/{$doctor->id}/availabilities", [
            'availabilities' => [
                ['day_of_week' => $day, 'start_time' => '09:00', 'end_time' => '10:30'],
            ],
        ]);

    $response->assertOk();
    $response->assertJsonCount(1, 'rescheduled_appointments');
    $response->assertJsonCount(0, 'cancelled_appointments');
    $response->assertJsonPath('rescheduled_appointments.0.start_time', '10:00');

    $appointment = $doctor->appointments()->where('patient_id', $patient->id)->sole();
    expect($appointment->status->value)->toBe('booked');
    expect($appointment->start_time->format('H:i'))->toBe('10:00');
});

it('cancels a booked appointment when its day is removed and nothing remains free that day', function () {
    $admin = Admin::factory()->create();
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->create();
    $tomorrow = now()->addDay();
    $day = DayOfWeek::from($tomorrow->isoWeekday())->value;

    $doctor->availabilities()->create(['day_of_week' => $day, 'start_time' => '09:00', 'end_time' => '10:00']);

    $this->actingAs($patient, 'patient')->postJson('/api/v1/patient/appointments', [
        'doctor_id' => $doctor->id,
        'date' => $tomorrow->toDateString(),
        'start_time' => '09:00',
    ])->assertCreated();

    $response = $this->actingAs($admin, 'admin')
        ->putJson("/api/v1/admin/doctors/{$doctor->id}/availabilities", ['availabilities' => []]);

    $response->assertOk();
    $response->assertJsonCount(0, 'rescheduled_appointments');
    $response->assertJsonCount(1, 'cancelled_appointments');

    $appointment = $doctor->appointments()->where('patient_id', $patient->id)->sole();
    expect($appointment->status->value)->toBe('cancelled');
    expect($appointment->slot_lock)->toBeNull();
});

it('leaves a booked appointment untouched when it still fits the updated schedule', function () {
    $admin = Admin::factory()->create();
    $doctor = Doctor::factory()->create();
    $patient = Patient::factory()->create();
    $tomorrow = now()->addDay();
    $day = DayOfWeek::from($tomorrow->isoWeekday())->value;

    $doctor->availabilities()->create(['day_of_week' => $day, 'start_time' => '09:00', 'end_time' => '17:00']);

    $this->actingAs($patient, 'patient')->postJson('/api/v1/patient/appointments', [
        'doctor_id' => $doctor->id,
        'date' => $tomorrow->toDateString(),
        'start_time' => '09:00',
    ])->assertCreated();

    $response = $this->actingAs($admin, 'admin')
        ->putJson("/api/v1/admin/doctors/{$doctor->id}/availabilities", [
            'availabilities' => [
                ['day_of_week' => $day, 'start_time' => '08:00', 'end_time' => '18:00'],
            ],
        ]);

    $response->assertOk();
    $response->assertJsonCount(0, 'rescheduled_appointments');
    $response->assertJsonCount(0, 'cancelled_appointments');

    $appointment = $doctor->appointments()->where('patient_id', $patient->id)->sole();
    expect($appointment->status->value)->toBe('booked');
    expect($appointment->start_time->format('H:i'))->toBe('09:00');
});
