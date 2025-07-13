<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MilestoneTask extends Model
{
    use HasFactory;

    protected $fillable = ['milestone_template_id', 'name', 'description', 'order'];

    // Each MilestoneTask belongs to a MilestoneTemplate
    public function milestoneTemplate()
    {
        return $this->belongsTo(MilestoneTemplate::class);
    }
}
