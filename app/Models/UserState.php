<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserState extends Model
{
    protected $table = 'user_states';

    protected $fillable = ['chat_id', 'state'];
}
