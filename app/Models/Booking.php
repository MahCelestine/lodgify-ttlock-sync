<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'ttlock_cour_pwd_id',
        'generated_passcode',
        'status',
    ];

    public function propertyMapping(): BelongsTo
    {
        return $this->belongsTo(PropertyMapping::class, 'lodgify_room_id', 'lodgify_property_id');
    }
}
