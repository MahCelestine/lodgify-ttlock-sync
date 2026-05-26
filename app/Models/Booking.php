<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'lodgify_booking_id',
        'lodgify_room_id',
        'guest_name',
        'arrival_date',
        'departure_date',
        'ttlock_lock_id',
        'ttlock_pwd_id',
        'generated_passcode',
        'status',
    ];
}
