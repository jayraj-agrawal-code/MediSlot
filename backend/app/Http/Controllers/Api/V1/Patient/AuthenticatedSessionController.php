<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Patient\LoginRequest;
use App\Http\Resources\Patient\PatientResource;
use App\Services\Patient\PatientAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly PatientAuthService $patientAuthService) {}

    /**
     * Log a patient in and start a stateful session.
     */
    public function store(LoginRequest $request): PatientResource
    {
        $patient = $this->patientAuthService->login(
            $request->validated(),
            $request->throttleKey(),
            $request,
        );

        return PatientResource::make($patient);
    }

    /**
     * Log the currently authenticated patient out.
     */
    public function destroy(Request $request): JsonResponse
    {
        $this->patientAuthService->logout($request);

        return response()->json(status: 204);
    }
}
