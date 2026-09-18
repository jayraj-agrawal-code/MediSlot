<?php

namespace App\Services\Appointment;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorBreak;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class SlotService
{
    /**
     * Compute a doctor's bookable slots for the configured booking horizon,
     * derived from their recurring weekly availability periods (a day may
     * have several, e.g. 9-1 and 2-5) minus already-booked slots, doctor
     * breaks, and any slot that has already passed.
     *
     * @return Collection<int, array{date: string, start_time: string, end_time: string}>
     */
    public function availableSlots(Doctor $doctor): Collection
    {
        $slotMinutes = (int) config('appointments.slot_minutes');
        $horizonDays = (int) config('appointments.booking_horizon_days');
        $now = CarbonImmutable::now();
        $from = $now->startOfDay();
        $to = $from->addDays($horizonDays - 1);

        $periodsByDay = $doctor->availabilities->groupBy(fn ($availability) => $availability->day_of_week->value);

        $bookedKeys = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->booked()
            ->whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->map(fn (Appointment $appointment) => $appointment->appointment_date->format('Y-m-d').' '.$appointment->start_time->format('H:i'))
            ->flip();

        $breaksByDate = DoctorBreak::query()
            ->where('doctor_id', $doctor->id)
            ->whereBetween('break_date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy(fn (DoctorBreak $break) => $break->break_date->format('Y-m-d'));

        $slots = collect();

        for ($date = $from; $date->lte($to); $date = $date->addDay()) {
            $periods = $periodsByDay->get($date->isoWeekday());

            if (! $periods) {
                continue;
            }

            $dayBreaks = $breaksByDate->get($date->format('Y-m-d'), collect());

            foreach ($periods as $period) {
                $windowStart = $date->setTimeFromTimeString($period->start_time->format('H:i'));
                $windowEnd = $date->setTimeFromTimeString($period->end_time->format('H:i'));

                $slotStart = $windowStart;

                while ($slotStart->addMinutes($slotMinutes)->lte($windowEnd)) {
                    $slotEnd = $slotStart->addMinutes($slotMinutes);

                    $isPast = $slotStart->lt($now);
                    $isBooked = $bookedKeys->has($date->format('Y-m-d').' '.$slotStart->format('H:i'));
                    $isOnBreak = $dayBreaks->contains(fn (DoctorBreak $break) => $slotStart->format('H:i') < $break->end_time->format('H:i')
                        && $slotEnd->format('H:i') > $break->start_time->format('H:i'));

                    if (! $isPast && ! $isBooked && ! $isOnBreak) {
                        $slots->push([
                            'date' => $date->format('Y-m-d'),
                            'start_time' => $slotStart->format('H:i'),
                            'end_time' => $slotEnd->format('H:i'),
                        ]);
                    }

                    $slotStart = $slotEnd;
                }
            }
        }

        return $slots->sortBy(fn (array $slot) => $slot['date'].' '.$slot['start_time'])->values();
    }

    /**
     * Find the matching currently-bookable slot for a date/start_time, if
     * one exists (part of one of the doctor's availability periods, aligned
     * to the slot grid, not already booked, not on a break, and not in the
     * past).
     *
     * @return array{date: string, start_time: string, end_time: string}|null
     */
    public function findSlot(Doctor $doctor, string $date, string $startTime): ?array
    {
        return $this->availableSlots($doctor)
            ->first(fn (array $slot) => $slot['date'] === $date && $slot['start_time'] === $startTime);
    }
}
