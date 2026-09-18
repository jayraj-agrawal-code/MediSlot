<?php

namespace App\Http\Resources\Appointment;

use App\Enums\AppointmentStatus;
use App\Http\Resources\Doctor\PublicDoctorResource;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Appointment
 */
class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'doctor' => PublicDoctorResource::make($this->whenLoaded('doctor')),
            'appointment_date' => $this->appointment_date->format('Y-m-d'),
            'start_time' => $this->start_time->format('H:i'),
            'end_time' => $this->end_time->format('H:i'),
            'status' => $this->status->value,
            'can_cancel' => $this->status === AppointmentStatus::Booked && ! $this->isPast(),
        ];
    }
}
