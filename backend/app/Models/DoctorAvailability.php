<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use Database\Factories\DoctorAvailabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['day_of_week', 'start_time', 'end_time'])]
class DoctorAvailability extends Model
{
    /** @use HasFactory<DoctorAvailabilityFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeek::class,
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function scopeForDay(Builder $query, DayOfWeek $day): Builder
    {
        return $query->where('day_of_week', $day);
    }
}
