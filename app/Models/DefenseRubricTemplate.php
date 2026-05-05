<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefenseRubricTemplate extends Model
{
    protected $fillable = [
        'name',
        'stage',
        'is_active',
        'description',
        'grade_guidelines',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'grade_guidelines' => 'array',
    ];

    public function criteria()
    {
        return $this->hasMany(DefenseRubricCriterion::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}

