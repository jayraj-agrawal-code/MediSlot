<?php

namespace Database\Seeders;

use App\Enums\DayOfWeek;
use App\Models\Doctor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * Seeds a handful of demo doctors with a weekday 9-5 schedule for local
     * development/testing.
     */
    public function run(): void
    {
        Doctor::factory()
            ->count(5)
            ->create()
            ->each(function (Doctor $doctor): void {
                foreach ([DayOfWeek::Monday, DayOfWeek::Tuesday, DayOfWeek::Wednesday, DayOfWeek::Thursday, DayOfWeek::Friday] as $day) {
                    $doctor->availabilities()->create([
                        'day_of_week' => $day,
                        'start_time' => '09:00',
                        'end_time' => '17:00',
                    ]);
                }
            });
    }
}
