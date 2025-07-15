<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = ['offering_id', 'student_id', 'type', 'date', 'time', 'room', 'remarks'];

    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
