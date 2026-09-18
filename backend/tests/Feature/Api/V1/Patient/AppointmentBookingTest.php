<?php

use App\Enums\DayOfWeek;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\QueryException;

function createAvailableDoctor(): Doctor
{
    $doctor = Doctor::factory()->create();
    $tomorrow = now()->addDay();

    $doctor->availabilities()->create([
        'day_of_week' => DayOfWeek::from($tomorrow->isoWeekday()),
        'start_time' => '09:00',
        'end_time' => '10:00',
    ]);

    return $doctor;
}

it('rejects unauthenticated booking attempts', function () {
    $doctor = createAvailableDoctor();

    $this->postJson('/api/v1/patient/appointments', [
        'doctor_id' => $doctor->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
    ])->assertUnauthorized();
});

it('books an available slot', function () {
    $patient = Patient::factory()->create();
    $doctor = createAvailableDoctor();
    $tomorrow = now()->addDay()->toDateString();

    $response = $this->actingAs($patient, 'patient')->postJson('/api/v1/patient/appointments', [
        'doctor_id' => $doctor->id,
        'date' => $tomorrow,
        'start_time' => '09:00',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'booked')
        ->assertJsonPath('data.start_time', '09:00')
        ->assertJsonPath('data.doctor.id', $doctor->id);

    $this->assertDatabaseHas('appointments', [
        'doctor_id' => $doctor->id,
        'patient_id' => $patient->id,
        'appointment_date' => $tomorrow,
        'status' => 'booked',
    ]);
});

it('rejects booking a slot outside the doctor weekly availability', function () {
    $patient = Patient::factory()->create();
    $doctor = createAvailableDoctor();

    $this->actingAs($patient, 'patient')->postJson('/api/v1/patient/appointments', [
        'doctor_id' => $doctor->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '23:00',
    ])->assertStatus(422)->assertJsonValidationErrors('start_time');
});

it('rejects booking a slot that is already booked', function () {
    $doctor = createAvailableDoctor();
    $tomorrow = now()->addDay()->toDateString();

    $firstPatient = Patient::factory()->create();
    $this->actingAs($firstPatient, 'patient')->postJson('/api/v1/patient/appointments', [
        'doctor_id' => $doctor->id,
        'date' => $tomorrow,
        'start_time' => '09:00',
    ])->assertCreated();

    $secondPatient = Patient::factory()->create();
    $this->actingAs($secondPatient, 'patient')->postJson('/api/v1/patient/appointments', [
        'doctor_id' => $doctor->id,
        'date' => $tomorrow,
        'start_time' => '09:00',
    ])->assertStatus(422)->assertJsonValidationErrors('start_time');

    expect(Appointment::where('doctor_id', $doctor->id)->booked()->count())->toBe(1);
});

it('rejects booking for an inactive doctor', function () {
    $patient = Patient::factory()->create();
    $doctor = createAvailableDoctor();
    $doctor->update(['is_active' => false]);

    $this->actingAs($patient, 'patient')->postJson('/api/v1/patient/appointments', [
        'doctor_id' => $doctor->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
    ])->assertStatus(422)->assertJsonValidationErrors('doctor_id');
});

it('rejects a booking date in the past at the validation layer', function () {
    $patient = Patient::factory()->create();
    $doctor = createAvailableDoctor();

    $this->actingAs($patient, 'patient')->postJson('/api/v1/patient/appointments', [
        'doctor_id' => $doctor->id,
        'date' => now()->subDay()->toDateString(),
        'start_time' => '09:00',
    ])->assertStatus(422)->assertJsonValidationErrors('date');
});

it('prevents two booked appointments from sharing the same slot at the database level', function () {
    $doctor = Doctor::factory()->create();
    $lock = Appointment::lockKeyFor($doctor->id, now()->addDay()->toDateString(), '09:00');

    Appointment::factory()->for($doctor)->create([
        'appointment_date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '09:30',
        'slot_lock' => $lock,
    ]);

    expect(fn () => Appointment::factory()->for($doctor)->create([
        'appointment_date' => now()->addDay()->toDateString(),
        'start_time' => '09:00',
        'end_time' => '09:30',
        'slot_lock' => $lock,
    ]))->toThrow(QueryException::class);
});
