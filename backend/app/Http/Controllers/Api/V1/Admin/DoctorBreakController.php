<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Doctor\StoreDoctorBreakRequest;
use App\Http\Resources\Appointment\AppointmentResource;
use App\Http\Resources\Doctor\DoctorBreakResource;
use App\Models\Doctor;
use App\Models\DoctorBreak;
use App\Services\Doctor\DoctorBreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DoctorBreakController extends Controller
{
    public function __construct(private readonly DoctorBreakService $breakService) {}

    /**
     * List a doctor's upcoming breaks.
     */
    public function index(Doctor $doctor): AnonymousResourceCollection
    {
        return DoctorBreakResource::collection($doctor->breaks()->upcoming()->get());
    }

    /**
     * Add a break for a doctor. Any booked appointment it collides with is
     * automatically moved to the nearest free same-day slot, or cancelled
     * if none is free.
     */
    public function store(StoreDoctorBreakRequest $request, Doctor $doctor): JsonResponse
    {
        $result = $this->breakService->create(
            $doctor,
            $request->validated('break_date'),
            $request->validated('start_time'),
            $request->validated('end_time'),
        );

        $result['rescheduled']->each(fn ($appointment) => $appointment->setRelation('doctor', $doctor));
        $result['cancelled']->each(fn ($appointment) => $appointment->setRelation('doctor', $doctor));

        return response()->json([
            'data' => DoctorBreakResource::make($result['break']),
            'rescheduled_appointments' => AppointmentResource::collection($result['rescheduled']),
            'cancelled_appointments' => AppointmentResource::collection($result['cancelled']),
        ], 201);
    }

    /**
     * Remove a doctor's break.
     */
    public function destroy(Doctor $doctor, DoctorBreak $break): Response
    {
        abort_unless($break->doctor_id === $doctor->id, 404);

        $this->breakService->delete($break);

        return response()->noContent();
    }
}
