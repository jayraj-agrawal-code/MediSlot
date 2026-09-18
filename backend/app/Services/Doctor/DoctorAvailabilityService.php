<?php

namespace App\Services\Doctor;

use App\Models\Doctor;
use App\Models\DoctorAvailability;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DoctorAvailabilityService
{
    /**
     * Replace a doctor's entire weekly schedule with the given set of
     * periods. A day may have multiple, non-overlapping periods (e.g.
     * 9-1 and 2-5); days not present in $availabilities are cleared.
     *
     * @param  array<int, array{day_of_week: int, start_time: string, end_time: string}>  $availabilities
     * @return Collection<int, DoctorAvailability>
     */
    public function sync(Doctor $doctor, array $availabilities): Collection
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

            return $doctor->availabilities()->get();
        });
    }
}
