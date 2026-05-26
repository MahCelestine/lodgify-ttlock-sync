<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyMapping extends Model
{
    protected $fillable = [
        'lodgify_property_id',
        'ttlock_lock_id',
        'lodgify_property_name',
        'ttlock_lock_name',
    ];
}
