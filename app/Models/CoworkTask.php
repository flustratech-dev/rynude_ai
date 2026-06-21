<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoworkTask extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'model',
        'status',
        'priority',
        'scheduled_for',
        'result',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
