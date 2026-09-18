<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        if ($this->command->getLaravel()->environment('local', 'testing')) {
            $this->call(AdminSeeder::class);
            $this->call(DoctorSeeder::class);
            $this->call(PatientSeeder::class);
        }
    }
}
