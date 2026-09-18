<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PatientSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * Seeds a single default patient for local development/testing login.
     */
    public function run(): void
    {
        Patient::factory()->create([
            'name' => 'Patient',
            'email' => 'patient@example.com',
            'password' => Hash::make('password'),
        ]);

        Patient::factory()->create([
            'name' => 'Patient Two',
            'email' => 'patient2@example.com',
            'password' => Hash::make('password'),
        ]);
    }
}
