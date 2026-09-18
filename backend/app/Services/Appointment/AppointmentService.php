<?php

namespace App\Services\Appointment;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function __construct(private readonly SlotService $slotService) {}

    /**
     * Book an available slot for a doctor on behalf of a patient.
     *
     * A doctor may only have one booked appointment per slot; the unique
     * "slot_lock" column guarantees this even under concurrent requests for
     * the same slot, since only one of them can win the insert.
     *
     * @throws ValidationException
     */
    public function book(Patient $patient, Doctor $doctor, string $date, string $startTime): Appointment
    {
        if (! $doctor->is_active) {
            throw ValidationException::withMessages([
                'doctor_id' => 'This doctor is not currently accepting appointments.',
            ]);
        }

        $slot = $this->slotService->findSlot($doctor, $date, $startTime);

        if (! $slot) {
            throw ValidationException::withMessages([
                'start_time' => 'This slot is not available.',
            ]);
        }

        return DB::transaction(function () use ($patient, $doctor, $slot) {
            try {
                return Appointment::create([
                    'doctor_id' => $doctor->id,
                    'patient_id' => $patient->id,
                    'appointment_date' => $slot['date'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'status' => AppointmentStatus::Booked,
                    'slot_lock' => Appointment::lockKeyFor($doctor->id, $slot['date'], $slot['start_time']),
                ]);
            } catch (QueryException $exception) {
                if ($this->isUniqueConstraintViolation($exception)) {
                    throw ValidationException::withMessages([
                        'start_time' => 'This slot was just booked by someone else. Please choose another.',
                    ]);
                }

                throw $exception;
            }
        });
    }

    /**
     * Cancel a patient's own, still-upcoming, booked appointment.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function cancel(Patient $patient, Appointment $appointment): Appointment
    {
        if ($appointment->patient_id !== $patient->id) {
            throw new AuthorizationException('You may only cancel your own appointments.');
        }

        if ($appointment->status === AppointmentStatus::Cancelled) {
            throw ValidationException::withMessages([
                'appointment' => 'This appointment is already cancelled.',
            ]);
        }

        if ($appointment->isPast()) {
            throw ValidationException::withMessages([
                'appointment' => 'Past appointments cannot be cancelled.',
            ]);
        }

        $appointment->update([
            'status' => AppointmentStatus::Cancelled,
            'slot_lock' => null,
        ]);

        return $appointment;
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return $exception->getCode() === '23000';
    }
}
