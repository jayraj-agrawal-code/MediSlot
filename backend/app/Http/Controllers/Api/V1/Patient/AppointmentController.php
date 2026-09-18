<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Patient\BookAppointmentRequest;
use App\Http\Resources\Appointment\AppointmentResource;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Services\Appointment\AppointmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AppointmentController extends Controller
{
    public function __construct(private readonly AppointmentService $appointmentService) {}

    /**
     * List the authenticated patient's own appointments.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $appointments = $request->user('patient')
            ->appointments()
            ->with('doctor')
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->get();

        return AppointmentResource::collection($appointments);
    }

    /**
     * Book an available slot for a doctor.
     */
    public function store(BookAppointmentRequest $request): AppointmentResource
    {
        $doctor = Doctor::findOrFail($request->validated('doctor_id'));

        $appointment = $this->appointmentService->book(
            $request->user('patient'),
            $doctor,
            $request->validated('date'),
            $request->validated('start_time'),
        );

        return AppointmentResource::make($appointment->load('doctor'));
    }

    /**
     * Cancel the patient's own upcoming appointment.
     */
    public function cancel(Request $request, Appointment $appointment): AppointmentResource
    {
        $appointment = $this->appointmentService->cancel($request->user('patient'), $appointment);

        return AppointmentResource::make($appointment->load('doctor'));
    }
}
