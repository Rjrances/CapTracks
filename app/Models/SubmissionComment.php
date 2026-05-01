<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionComment extends Model
{
    protected $fillable = [
        'project_submission_id',
        'user_id',
        'student_id',
        'body',
        'parent_id',
    ];

    public function projectSubmission()
    {
        return $this->belongsTo(ProjectSubmission::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(SubmissionComment::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(SubmissionComment::class, 'parent_id')->latest();
    }
}
