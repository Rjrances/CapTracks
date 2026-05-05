<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefenseRubricCriterion extends Model
{
    protected $fillable = [
        'defense_rubric_template_id',
        'scope',
        'name',
        'max_points',
        'sort_order',
    ];

    protected $casts = [
        'max_points' => 'decimal:2',
    ];

    public function template()
    {
        return $this->belongsTo(DefenseRubricTemplate::class, 'defense_rubric_template_id');
    }
}

