<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['doctor_id', 'patient_id', 'appointment_date', 'start_time', 'end_time', 'status', 'slot_lock'])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'appointment_date' => 'date:Y-m-d',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'status' => AppointmentStatus::class,
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function scopeBooked(Builder $query): Builder
    {
        return $query->where('status', AppointmentStatus::Booked);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        $now = CarbonImmutable::now();

        return $query->where(function (Builder $query) use ($now) {
            $query->where('appointment_date', '>', $now->toDateString())
                ->orWhere(function (Builder $query) use ($now) {
                    $query->where('appointment_date', $now->toDateString())
                        ->where('start_time', '>=', $now->format('H:i:s'));
                });
        });
    }

    /**
     * The instant the appointment slot starts.
     */
    public function startsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->appointment_date->format('Y-m-d').' '.$this->start_time->format('H:i'));
    }

    public function isPast(): bool
    {
        return $this->startsAt()->isPast();
    }

    public static function lockKeyFor(int $doctorId, string $date, string $startTime): string
    {
        return "{$doctorId}_{$date}_{$startTime}";
    }
}
