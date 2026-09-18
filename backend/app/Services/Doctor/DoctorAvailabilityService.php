<?php

namespace App\Services\Doctor;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\Appointment\AppointmentReschedulerService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DoctorAvailabilityService
{
    public function __construct(private readonly AppointmentReschedulerService $rescheduler) {}

    /**
     * Replace a doctor's entire weekly schedule with the given set of
     * periods. A day may have multiple, non-overlapping periods (e.g.
     * 9-1 and 2-5); days not present in $availabilities are cleared.
     *
     * Any booked, upcoming appointment that no longer fits inside one of
     * the new periods for its day (because a period shrank, moved, or was
     * removed) is moved to the nearest free slot on the same day under the
     * new schedule, or cancelled if none is free.
     *
     * @param  array<int, array{day_of_week: int, start_time: string, end_time: string}>  $availabilities
     * @return array{availabilities: \Illuminate\Database\Eloquent\Collection, rescheduled: Collection, cancelled: Collection}
     */
    public function sync(Doctor $doctor, array $availabilities): array
    {
        return DB::transaction(function () use ($doctor, $availabilities) {
            $doctor->availabilities()->delete();

            foreach ($availabilities as $entry) {
                $doctor->availabilities()->create([
                    'day_of_week' => $entry['day_of_week'],
                    'start_time' => $entry['start_time'],
                    'end_time' => $entry['end_time'],
                ]);
            }

            $periodsByDay = collect($availabilities)->groupBy('day_of_week');

            $affected = $doctor->appointments()
                ->booked()
                ->upcoming()
                ->get()
                ->reject(fn (Appointment $appointment) => $this->fitsSchedule($appointment, $periodsByDay));

            $result = $this->rescheduler->rescheduleOrCancel($doctor, $affected);

            return [
                'availabilities' => $doctor->availabilities()->get(),
                ...$result,
            ];
        });
    }

    /**
     * @param  Collection<int|string, Collection>  $periodsByDay
     */
    private function fitsSchedule(Appointment $appointment, $periodsByDay): bool
    {
        $periods = $periodsByDay->get($appointment->appointment_date->isoWeekday(), collect());
        $start = $appointment->start_time->format('H:i');
        $end = $appointment->end_time->format('H:i');

        return $periods->contains(fn (array $period) => $start >= $period['start_time'] && $end <= $period['end_time']);
    }
}
