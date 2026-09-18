<?php

use App\Enums\DayOfWeek;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;

it('rejects unauthenticated access to the doctor list', function () {
    $this->getJson('/api/v1/patient/doctors')->assertUnauthorized();
});

it('only lists active doctors', function () {
    $patient = Patient::factory()->create();
    $active = Doctor::factory()->create(['is_active' => true]);
    Doctor::factory()->create(['is_active' => false]);

    $response = $this->actingAs($patient, 'patient')->getJson('/api/v1/patient/doctors');

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $active->id);
    $response->assertJsonMissingPath('data.0.email');
});

it('lists a doctor available slots, excluding booked and past ones', function () {
    $patient = Patient::factory()->create();
    $doctor = Doctor::factory()->create();
    $tomorrow = now()->addDay();

    $doctor->availabilities()->create([
        'day_of_week' => DayOfWeek::from($tomorrow->isoWeekday()),
        'start_time' => '09:00',
        'end_time' => '10:00',
    ]);

    $doctor->appointments()->create([
        'patient_id' => Patient::factory()->create()->id,
        'appointment_date' => $tomorrow->toDateString(),
        'start_time' => '09:00',
        'end_time' => '09:30',
        'status' => 'booked',
        'slot_lock' => Appointment::lockKeyFor($doctor->id, $tomorrow->toDateString(), '09:00'),
    ]);

    $response = $this->actingAs($patient, 'patient')
        ->getJson("/api/v1/patient/doctors/{$doctor->id}/slots");

    $response->assertOk();

    $slots = collect($response->json('data'));

    expect($slots->contains(fn ($slot) => $slot['date'] === $tomorrow->toDateString() && $slot['start_time'] === '09:00'))
        ->toBeFalse();
    expect($slots->contains(fn ($slot) => $slot['date'] === $tomorrow->toDateString() && $slot['start_time'] === '09:30'))
        ->toBeTrue();
});

it('excludes the gap between split availability periods and any doctor break', function () {
    $patient = Patient::factory()->create();
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
    $doctor->breaks()->create([
        'break_date' => $tomorrow->toDateString(),
        'start_time' => '14:00',
        'end_time' => '14:30',
    ]);

    $slots = collect(
        $this->actingAs($patient, 'patient')
            ->getJson("/api/v1/patient/doctors/{$doctor->id}/slots")
            ->assertOk()
            ->json('data'),
    )->filter(fn ($slot) => $slot['date'] === $tomorrow->toDateString());

    expect($slots->contains(fn ($slot) => $slot['start_time'] === '13:00'))->toBeFalse();
    expect($slots->contains(fn ($slot) => $slot['start_time'] === '14:00'))->toBeFalse();
    expect($slots->contains(fn ($slot) => $slot['start_time'] === '14:30'))->toBeTrue();
});
