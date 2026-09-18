<?php

use App\Enums\DayOfWeek;
use App\Models\Admin;
use App\Models\Doctor;
use App\Models\Patient;

function doctorWithSplitAvailability(): Doctor
{
    $doctor = Doctor::factory()->create();
    $tomorrow = now()->addDay();

    $doctor->availabilities()->create([
        'day_of_week' => DayOfWeek::from($tomorrow->isoWeekday()),
        'start_time' => '09:00',
        'end_time' => '13:00',
    ]);
    $doctor->availabilities()->create([
        'day_of_week' => DayOfWeek::from($tomorrow->isoWeekday()),
        'start_time' => '14:00',
        'end_time' => '17:00',
    ]);

    return $doctor;
}

it('rejects unauthenticated access to creating a break', function () {
    $doctor = Doctor::factory()->create();

    $this->postJson("/api/v1/admin/doctors/{$doctor->id}/breaks", [
        'break_date' => now()->addDay()->toDateString(),
        'start_time' => '10:00',
        'end_time' => '11:00',
    ])->assertUnauthorized();
});

it('creates a break that blocks new bookings for that window', function () {
    $admin = Admin::factory()->create();
    $doctor = doctorWithSplitAvailability();
    $tomorrow = now()->addDay()->toDateString();

    $this->actingAs($admin, 'admin')
        ->postJson("/api/v1/admin/doctors/{$doctor->id}/breaks", [
            'break_date' => $tomorrow,
            'start_time' => '10:30',
            'end_time' => '11:30',
        ])
        ->assertCreated()
        ->assertJsonPath('data.start_time', '10:30')
        ->assertJsonPath('rescheduled_appointments', [])
        ->assertJsonPath('cancelled_appointments', []);

    $this->assertDatabaseHas('doctor_breaks', [
        'doctor_id' => $doctor->id,
        'break_date' => $tomorrow,
    ]);

    $patient = Patient::factory()->create();
    $this->actingAs($patient, 'patient')->postJson('/api/v1/patient/appointments', [
        'doctor_id' => $doctor->id,
        'date' => $tomorrow,
        'start_time' => '10:30',
    ])->assertStatus(422)->assertJsonValidationErrors('start_time');
});

it('rejects a break date in the past', function () {
    $admin = Admin::factory()->create();
    $doctor = Doctor::factory()->create();

    $this->actingAs($admin, 'admin')
        ->postJson("/api/v1/admin/doctors/{$doctor->id}/breaks", [
            'break_date' => now()->subDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('break_date');
});

it('automatically moves an existing appointment to the nearest free slot', function () {
    $admin = Admin::factory()->create();
    $doctor = doctorWithSplitAvailability();
    $patient = Patient::factory()->create();
    $tomorrow = now()->addDay()->toDateString();

    $this->actingAs($patient, 'patient')->postJson('/api/v1/patient/appointments', [
        'doctor_id' => $doctor->id,
        'date' => $tomorrow,
        'start_time' => '11:00',
    ])->assertCreated();

    $response = $this->actingAs($admin, 'admin')
        ->postJson("/api/v1/admin/doctors/{$doctor->id}/breaks", [
            'break_date' => $tomorrow,
            'start_time' => '10:30',
            'end_time' => '11:30',
        ]);

    // The 11:00 appointment collides with the 10:30-11:30 break. Candidates
    // once 10:30 and 11:00 are excluded: 09:00/09:30/10:00 before, 11:30/
    // 12:00/12:30 after. 11:30 is the single nearest (30 min vs. 60+ for
    // every other candidate), so it must win.
    $response->assertCreated();
    $response->assertJsonCount(1, 'rescheduled_appointments');
    $response->assertJsonCount(0, 'cancelled_appointments');
    $response->assertJsonPath('rescheduled_appointments.0.start_time', '11:30');

    $appointment = $doctor->appointments()->where('patient_id', $patient->id)->sole();
    expect($appointment->status->value)->toBe('booked');
    expect($appointment->start_time->format('H:i'))->toBe('11:30');

    $this->actingAs($patient, 'patient')
        ->getJson('/api/v1/patient/appointments')
        ->assertOk()
        ->assertJsonPath('data.0.start_time', '11:30');
});

it('cancels an existing appointment when no free slot remains for it', function () {
    $admin = Admin::factory()->create();
    $doctor = Doctor::factory()->create();
    $tomorrow = now()->addDay();

    // A single, exactly-one-slot-long availability period.
    $doctor->availabilities()->create([
        'day_of_week' => DayOfWeek::from($tomorrow->isoWeekday()),
        'start_time' => '10:00',
        'end_time' => '10:30',
    ]);

    $patient = Patient::factory()->create();
    $this->actingAs($patient, 'patient')->postJson('/api/v1/patient/appointments', [
        'doctor_id' => $doctor->id,
        'date' => $tomorrow->toDateString(),
        'start_time' => '10:00',
    ])->assertCreated();

    $response = $this->actingAs($admin, 'admin')
        ->postJson("/api/v1/admin/doctors/{$doctor->id}/breaks", [
            'break_date' => $tomorrow->toDateString(),
            'start_time' => '10:00',
            'end_time' => '10:30',
        ]);

    $response->assertCreated();
    $response->assertJsonCount(0, 'rescheduled_appointments');
    $response->assertJsonCount(1, 'cancelled_appointments');

    $this->assertDatabaseHas('appointments', [
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'status' => 'cancelled',
        'slot_lock' => null,
    ]);
});

it('lists and deletes a doctor break', function () {
    $admin = Admin::factory()->create();
    $doctor = Doctor::factory()->create();
    $tomorrow = now()->addDay()->toDateString();

    $breakId = $this->actingAs($admin, 'admin')
        ->postJson("/api/v1/admin/doctors/{$doctor->id}/breaks", [
            'break_date' => $tomorrow,
            'start_time' => '10:00',
            'end_time' => '11:00',
        ])->json('data.id');

    $this->actingAs($admin, 'admin')
        ->getJson("/api/v1/admin/doctors/{$doctor->id}/breaks")
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->actingAs($admin, 'admin')
        ->deleteJson("/api/v1/admin/doctors/{$doctor->id}/breaks/{$breakId}")
        ->assertNoContent();

    $this->assertDatabaseMissing('doctor_breaks', ['id' => $breakId]);
});
