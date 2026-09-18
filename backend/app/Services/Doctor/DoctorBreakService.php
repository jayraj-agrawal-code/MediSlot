<?php

namespace App\Services\Doctor;

use App\Models\Doctor;
use App\Models\DoctorBreak;
use App\Services\Appointment\AppointmentReschedulerService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DoctorBreakService
{
    public function __construct(private readonly AppointmentReschedulerService $rescheduler) {}

    /**
     * Add a break for a doctor. Any booked, upcoming appointment that falls
     * inside the break window is moved to the nearest free slot on the same
     * day; if none is free, the appointment is cancelled instead.
     *
     * @return array{break: DoctorBreak, rescheduled: Collection, cancelled: Collection}
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

            $result = $this->rescheduler->rescheduleOrCancel($doctor, $affected);

            return ['break' => $break, ...$result];
        });
    }

    public function delete(DoctorBreak $break): void
    {
        $break->delete();
    }
}
