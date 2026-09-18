<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Patient\RegisterPatientRequest;
use App\Http\Resources\Patient\PatientResource;
use App\Services\Patient\PatientAuthService;

class RegisteredPatientController extends Controller
{
    public function __construct(private readonly PatientAuthService $patientAuthService) {}

    /**
     * Register a new patient account.
     */
    public function store(RegisterPatientRequest $request): PatientResource
    {
        $patient = $this->patientAuthService->register($request->validated());

        return PatientResource::make($patient);
    }
}
