<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Design extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'type',
        'prompt',
        'content',
        'status',
        'is_starred',
    ];

    protected $casts = [
        'is_starred' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
