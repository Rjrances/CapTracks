<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offering extends Model
{
    protected $fillable = ['subject_title', 'subject_code', 'teacher_name'];
}
