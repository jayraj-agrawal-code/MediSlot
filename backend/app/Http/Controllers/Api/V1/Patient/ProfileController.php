<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\Patient\PatientResource;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Return the currently authenticated patient.
     */
    public function show(Request $request): PatientResource
    {
        return PatientResource::make($request->user('patient'));
    }
}
