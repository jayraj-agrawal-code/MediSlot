<?php

namespace App\Http\Requests\Api\V1\Admin\Doctor;

use App\Enums\DayOfWeek;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetDoctorAvailabilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * A day may have multiple periods (e.g. 9-1 and 2-5); overlap between
     * periods on the same day is rejected in withValidator() below, since
     * it depends on comparing pairs of entries rather than a single field.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'availabilities' => ['present', 'array'],
            'availabilities.*.day_of_week' => ['required', Rule::enum(DayOfWeek::class)],
            'availabilities.*.start_time' => ['required', 'date_format:H:i'],
            'availabilities.*.end_time' => ['required', 'date_format:H:i', 'after:availabilities.*.start_time'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->ensureNoOverlappingPeriods($validator);
        });
    }

    private function ensureNoOverlappingPeriods(Validator $validator): void
    {
        $byDay = collect($this->input('availabilities', []))
            ->filter(fn ($entry) => isset($entry['day_of_week'], $entry['start_time'], $entry['end_time']))
            ->groupBy('day_of_week');

        foreach ($byDay as $day => $periods) {
            $sorted = $periods->values()->sortBy('start_time')->values();

            for ($i = 0; $i < $sorted->count() - 1; $i++) {
                if ($sorted[$i]['end_time'] > $sorted[$i + 1]['start_time']) {
                    $validator->errors()->add(
                        'availabilities',
                        "Availability periods for day {$day} overlap.",
                    );

                    break;
                }
            }
        }
    }
}
