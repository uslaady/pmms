<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id', 'ip_address', 'user_agent', 'logged_in_at'];
}
