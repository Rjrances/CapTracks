<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectSubmission extends Model
{
    protected $fillable = [
        'student_id',
        'file_path',
        'type',
        'status',
        'teacher_comment',
        'submitted_at',
    ];

    public function student()
    {
        return $this->belongsTo(\App\Models\Student::class);
    }
}
