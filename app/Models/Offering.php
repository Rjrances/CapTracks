<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offering extends Model
{
    protected $fillable = ['name', 'description', 'teacher_id', 'schedule_time'];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
