<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\Doctor\PublicDoctorResource;
use App\Models\Doctor;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DoctorController extends Controller
{
    /**
     * List active doctors available for booking.
     */
    public function index(): AnonymousResourceCollection
    {
        $doctors = Doctor::query()->active()->orderBy('name')->get();

        return PublicDoctorResource::collection($doctors);
    }
}
