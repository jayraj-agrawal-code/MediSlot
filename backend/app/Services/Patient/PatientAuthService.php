<?php

namespace App\Services\Patient;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PatientAuthService
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function register(array $data): Patient
    {
        return Patient::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
    }

    /**
     * Attempt to authenticate a patient and start a stateful session.
     *
     * @param  array{email: string, password: string, remember?: bool}  $credentials
     *
     * @throws ValidationException
     */
    public function login(array $credentials, string $throttleKey, Request $request): Patient
    {
        $this->ensureIsNotRateLimited($throttleKey);

        $remember = (bool) ($credentials['remember'] ?? false);

        if (! Auth::guard('patient')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], $remember)) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        /** @var Patient $patient */
        $patient = Auth::guard('patient')->user();

        return $patient;
    }

    /**
     * Log the currently authenticated patient out and invalidate their session.
     */
    public function logout(Request $request): void
    {
        Auth::guard('patient')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(string $throttleKey): void
    {
        if (! RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($throttleKey);

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }
}
