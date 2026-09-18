<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = now()->addDay()->toDateString();
        $startTime = '09:00';

        return [
            'doctor_id' => Doctor::factory(),
            'patient_id' => Patient::factory(),
            'appointment_date' => $date,
            'start_time' => $startTime,
            'end_time' => '09:30',
            'status' => AppointmentStatus::Booked,
            'slot_lock' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Appointment $appointment) {
            if ($appointment->status === AppointmentStatus::Booked && $appointment->slot_lock === null) {
                $appointment->slot_lock = Appointment::lockKeyFor(
                    $appointment->doctor_id,
                    $appointment->appointment_date instanceof \DateTimeInterface
                        ? $appointment->appointment_date->format('Y-m-d')
                        : (string) $appointment->appointment_date,
                    $appointment->start_time instanceof \DateTimeInterface
                        ? $appointment->start_time->format('H:i')
                        : (string) $appointment->start_time,
                );
            }
        });
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Cancelled,
            'slot_lock' => null,
        ]);
    }

    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'appointment_date' => now()->subDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '09:30',
        ]);
    }
}
