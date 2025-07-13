<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MilestoneTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    // One MilestoneTemplate has many MilestoneTasks
    public function tasks()
    {
        return $this->hasMany(MilestoneTask::class)->orderBy('order');
    }
}
