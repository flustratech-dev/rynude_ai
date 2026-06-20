<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    protected $fillable = ['code', 'name', 'is_active', 'provider', 'user_id'];
}
