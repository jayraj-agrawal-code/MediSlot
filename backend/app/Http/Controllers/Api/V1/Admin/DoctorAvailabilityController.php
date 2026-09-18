<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Doctor\SetDoctorAvailabilityRequest;
use App\Http\Resources\Doctor\DoctorAvailabilityResource;
use App\Models\Doctor;
use App\Services\Doctor\DoctorAvailabilityService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DoctorAvailabilityController extends Controller
{
    public function __construct(private readonly DoctorAvailabilityService $availabilityService) {}

    /**
     * Replace the doctor's entire weekly availability.
     */
    public function update(SetDoctorAvailabilityRequest $request, Doctor $doctor): AnonymousResourceCollection
    {
        $availabilities = $this->availabilityService->sync($doctor, $request->validated('availabilities'));

        return DoctorAvailabilityResource::collection($availabilities);
    }
}
