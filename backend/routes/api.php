<?php

use App\Http\Controllers\Api\V1\Admin\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Api\V1\Admin\DoctorAvailabilityController;
use App\Http\Controllers\Api\V1\Admin\DoctorBreakController;
use App\Http\Controllers\Api\V1\Admin\DoctorController as AdminDoctorController;
use App\Http\Controllers\Api\V1\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Api\V1\Patient\AppointmentController;
use App\Http\Controllers\Api\V1\Patient\AuthenticatedSessionController as PatientAuthenticatedSessionController;
use App\Http\Controllers\Api\V1\Patient\DoctorController as PatientDoctorController;
use App\Http\Controllers\Api\V1\Patient\DoctorSlotController;
use App\Http\Controllers\Api\V1\Patient\ProfileController as PatientProfileController;
use App\Http\Controllers\Api\V1\Patient\RegisteredPatientController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::post('login', [AdminAuthenticatedSessionController::class, 'store'])->name('login');

        Route::middleware('auth:admin')->group(function () {
            Route::post('logout', [AdminAuthenticatedSessionController::class, 'destroy'])->name('logout');
            Route::get('me', [AdminProfileController::class, 'show'])->name('me');

            Route::apiResource('doctors', AdminDoctorController::class);
            Route::put('doctors/{doctor}/availabilities', [DoctorAvailabilityController::class, 'update'])
                ->name('doctors.availabilities.update');

            Route::get('doctors/{doctor}/breaks', [DoctorBreakController::class, 'index'])
                ->name('doctors.breaks.index');
            Route::post('doctors/{doctor}/breaks', [DoctorBreakController::class, 'store'])
                ->name('doctors.breaks.store');
            Route::delete('doctors/{doctor}/breaks/{break}', [DoctorBreakController::class, 'destroy'])
                ->name('doctors.breaks.destroy');
        });
    });

    Route::prefix('patient')->name('patient.')->group(function () {
        Route::post('register', [RegisteredPatientController::class, 'store'])->name('register');
        Route::post('login', [PatientAuthenticatedSessionController::class, 'store'])->name('login');

        Route::middleware('auth:patient')->group(function () {
            Route::post('logout', [PatientAuthenticatedSessionController::class, 'destroy'])->name('logout');
            Route::get('me', [PatientProfileController::class, 'show'])->name('me');

            Route::get('doctors', [PatientDoctorController::class, 'index'])->name('doctors.index');
            Route::get('doctors/{doctor}/slots', [DoctorSlotController::class, 'index'])->name('doctors.slots');

            Route::get('appointments', [AppointmentController::class, 'index'])->name('appointments.index');
            Route::post('appointments', [AppointmentController::class, 'store'])->name('appointments.store');
            Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])
                ->name('appointments.cancel');
        });
    });
});
