<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status')->default('booked');
            /**
             * Set to "{doctor_id}_{appointment_date}_{start_time}" while the
             * appointment is booked, and cleared to null on cancellation.
             * The unique index makes double-booking (including concurrent
             * requests for the same slot) impossible at the database level,
             * while allowing the freed slot to be rebooked afterwards.
             */
            $table->string('slot_lock')->nullable()->unique();
            $table->timestamps();

            $table->index(['doctor_id', 'appointment_date']);
            $table->index(['patient_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
