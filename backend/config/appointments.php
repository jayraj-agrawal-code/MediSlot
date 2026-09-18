<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Slot Duration
    |--------------------------------------------------------------------------
    |
    | Length, in minutes, of a single bookable appointment slot. A doctor's
    | daily availability window is divided into slots of this length.
    |
    */

    'slot_minutes' => (int) env('APPOINTMENT_SLOT_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Booking Horizon
    |--------------------------------------------------------------------------
    |
    | How many days ahead (from today) patients can view and book slots for,
    | derived from a doctor's recurring weekly availability.
    |
    */

    'booking_horizon_days' => (int) env('APPOINTMENT_BOOKING_HORIZON_DAYS', 14),

];
