<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\Doctor\StoreDoctorRequest;
use App\Http\Requests\Api\V1\Admin\Doctor\UpdateDoctorRequest;
use App\Http\Resources\Doctor\DoctorResource;
use App\Models\Doctor;
use App\Services\Doctor\DoctorService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class DoctorController extends Controller
{
    public function __construct(private readonly DoctorService $doctorService) {}

    /**
     * List all doctors together with their weekly availability.
     */
    public function index(): AnonymousResourceCollection
    {
        return DoctorResource::collection($this->doctorService->list());
    }

    public function store(StoreDoctorRequest $request): DoctorResource
    {
        $doctor = $this->doctorService->create($request->validated());

        return DoctorResource::make($doctor->load(['availabilities', 'breaks' => fn ($query) => $query->upcoming()]));
    }

    public function show(Doctor $doctor): DoctorResource
    {
        return DoctorResource::make($doctor->load(['availabilities', 'breaks' => fn ($query) => $query->upcoming()]));
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor): DoctorResource
    {
        $doctor = $this->doctorService->update($doctor, $request->validated());

        return DoctorResource::make($doctor->load(['availabilities', 'breaks' => fn ($query) => $query->upcoming()]));
    }

    public function destroy(Doctor $doctor): Response
    {
        $this->doctorService->delete($doctor);

        return response()->noContent();
    }
}
