<?php

namespace App\Http\Resources\Doctor;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Patient-facing doctor listing. Excludes internal contact details that
 * patients don't need to see.
 *
 * @mixin Doctor
 */
class PublicDoctorResource extends JsonResource
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
            'specialization' => $this->specialization,
        ];
    }
}
