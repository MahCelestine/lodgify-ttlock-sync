<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyMapping extends Model
{

    protected $table = 'property_mappings';

    protected $fillable = [
        'lodgify_property_id',
        'ttlock_lock_id',
        'lodgify_property_name',
        'ttlock_lock_name',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'lodgify_room_id', 'lodgify_property_id');
    }
}
