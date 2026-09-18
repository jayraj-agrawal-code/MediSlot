<?php

namespace App\Services\Doctor;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorBreak;
use App\Services\Appointment\SlotService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DoctorBreakService
{
    public function __construct(private readonly SlotService $slotService) {}

    /**
     * Add a break for a doctor. Any booked, upcoming appointment that falls
     * inside the break window is moved to the nearest free slot on the same
     * day; if none is free, the appointment is cancelled instead.
     *
     * @return array{break: DoctorBreak, rescheduled: Collection<int, Appointment>, cancelled: Collection<int, Appointment>}
     */
    public function create(Doctor $doctor, string $date, string $startTime, string $endTime): array
    {
        return DB::transaction(function () use ($doctor, $date, $startTime, $endTime) {
            $break = $doctor->breaks()->create([
                'break_date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]);

            $affected = $doctor->appointments()
                ->booked()
                ->where('appointment_date', $date)
                ->where('start_time', '<', $endTime)
                ->where('end_time', '>', $startTime)
                ->orderBy('start_time')
                ->get();

            $rescheduled = collect();
            $cancelled = collect();

            foreach ($affected as $appointment) {
                if ($this->rescheduleToNearestSlot($doctor, $appointment)) {
                    $rescheduled->push($appointment);
                } else {
                    $cancelled->push($appointment);
                }
            }

            return ['break' => $break, 'rescheduled' => $rescheduled, 'cancelled' => $cancelled];
        });
    }

    public function delete(DoctorBreak $break): void
    {
        $break->delete();
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
