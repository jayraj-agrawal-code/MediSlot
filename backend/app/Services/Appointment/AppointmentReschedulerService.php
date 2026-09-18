<?php

namespace App\Services\Appointment;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

/**
 * Moves booked appointments that a schedule change (a new break, or an
 * edited availability period) has knocked out of a valid slot to the
 * nearest free slot on the same day, falling back to cancellation when
 * nothing is free. Shared by DoctorBreakService and DoctorAvailabilityService
 * so both changes behave the same way for existing patients.
 */
class AppointmentReschedulerService
{
    public function __construct(private readonly SlotService $slotService) {}

    /**
     * @param  Collection<int, Appointment>  $affected
     * @return array{rescheduled: Collection<int, Appointment>, cancelled: Collection<int, Appointment>}
     */
    public function rescheduleOrCancel(Doctor $doctor, Collection $affected): array
    {
        $rescheduled = collect();
        $cancelled = collect();

        foreach ($affected as $appointment) {
            if ($this->rescheduleToNearestSlot($doctor, $appointment)) {
                $rescheduled->push($appointment);
            } else {
                $cancelled->push($appointment);
            }
        }

        return ['rescheduled' => $rescheduled, 'cancelled' => $cancelled];
    }

    /**
     * Try each same-day candidate slot, nearest to the original start time
     * first, falling back to cancellation if every candidate is taken
     * (including by a concurrent booking made while we were choosing one).
     */
    private function rescheduleToNearestSlot(Doctor $doctor, Appointment $appointment): bool
    {
        $originalDate = $appointment->appointment_date->format('Y-m-d');
        $originalMinutes = $this->toMinutes($appointment->start_time->format('H:i'));

        $candidates = $this->slotService->availableSlots($doctor)
            ->filter(fn (array $slot) => $slot['date'] === $originalDate)
            ->sortBy(fn (array $slot) => abs($this->toMinutes($slot['start_time']) - $originalMinutes));

        foreach ($candidates as $slot) {
            try {
                $appointment->forceFill([
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'slot_lock' => Appointment::lockKeyFor($doctor->id, $slot['date'], $slot['start_time']),
                ])->save();

                return true;
            } catch (QueryException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }

                // Another request took this slot first; try the next nearest one.
            }
        }

        $appointment->update(['status' => AppointmentStatus::Cancelled, 'slot_lock' => null]);

        return false;
    }

    private function toMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);

        return ((int) $hours * 60) + (int) $minutes;
    }
}
