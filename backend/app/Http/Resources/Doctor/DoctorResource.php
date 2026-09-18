<?php

namespace App\Http\Resources\Doctor;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Doctor
 */
class DoctorResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'specialization' => $this->specialization,
            'is_active' => $this->is_active,
            'availabilities' => DoctorAvailabilityResource::collection($this->whenLoaded('availabilities')),
            'breaks' => DoctorBreakResource::collection($this->whenLoaded('breaks')),
            'created_at' => $this->created_at,
        ];
    }
}
