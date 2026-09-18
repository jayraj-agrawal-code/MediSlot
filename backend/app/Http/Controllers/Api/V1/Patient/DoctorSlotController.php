<?php

namespace App\Http\Controllers\Api\V1\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\Appointment\SlotResource;
use App\Models\Doctor;
use App\Services\Appointment\SlotService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DoctorSlotController extends Controller
{
    public function __construct(private readonly SlotService $slotService) {}

    /**
     * List a doctor's available appointment slots within the booking horizon.
     */
    public function index(Doctor $doctor): AnonymousResourceCollection
    {
        return SlotResource::collection($this->slotService->availableSlots($doctor));
    }
}
