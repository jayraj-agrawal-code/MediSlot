<?php

namespace App\Services\Doctor;

use App\Models\Doctor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DoctorService
{
    public function list(int $perPage = 15): LengthAwarePaginator
    {
        return Doctor::query()
            ->with(['availabilities', 'breaks' => fn ($query) => $query->upcoming()])
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * @param  array{name: string, email?: ?string, phone?: ?string, specialization?: ?string, is_active?: bool}  $data
     */
    public function create(array $data): Doctor
    {
        return Doctor::create([
            ...$data,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * @param  array{name?: string, email?: ?string, phone?: ?string, specialization?: ?string, is_active?: bool}  $data
     */
    public function update(Doctor $doctor, array $data): Doctor
    {
        $doctor->update($data);

        return $doctor;
    }

    public function delete(Doctor $doctor): void
    {
        $doctor->delete();
    }
}
