<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['title', 'description', 'role', 'redirect_url', 'is_read'];
    
    protected $casts = [
        'is_read' => 'boolean',
    ];
}
