<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'direction',
        'city',
        'passenger_count',
        'gender',
        'phone',
        'status',
    ];
}
