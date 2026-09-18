<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Doctor\SetDoctorAvailabilityRequest;
use App\Http\Resources\Appointment\AppointmentResource;
use App\Http\Resources\Doctor\DoctorAvailabilityResource;
use App\Models\Doctor;
use App\Services\Doctor\DoctorAvailabilityService;
use Illuminate\Http\JsonResponse;

class DoctorAvailabilityController extends Controller
{
    public function __construct(private readonly DoctorAvailabilityService $availabilityService) {}

    /**
     * Replace the doctor's entire weekly availability. Appointments that no
     * longer fit the new schedule are automatically moved to their nearest
     * free slot, or cancelled if none is free.
     */
    public function update(SetDoctorAvailabilityRequest $request, Doctor $doctor): JsonResponse
    {
        $result = $this->availabilityService->sync($doctor, $request->validated('availabilities'));

        $result['rescheduled']->each(fn ($appointment) => $appointment->setRelation('doctor', $doctor));
        $result['cancelled']->each(fn ($appointment) => $appointment->setRelation('doctor', $doctor));

        return response()->json([
            'data' => DoctorAvailabilityResource::collection($result['availabilities']),
            'rescheduled_appointments' => AppointmentResource::collection($result['rescheduled']),
            'cancelled_appointments' => AppointmentResource::collection($result['cancelled']),
        ]);
    }
}
