<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class DefensePanel extends Model
{
    use HasFactory;
    protected $fillable = [
        'defense_schedule_id',
        'faculty_id',
        'role'
    ];
    public function defenseSchedule()
    {
        return $this->belongsTo(DefenseSchedule::class, 'defense_schedule_id');
    }
    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }
    public function getRoleLabelAttribute()
    {
        return match($this->role) {
            'adviser' => 'Adviser',
            'coordinator' => 'Coordinator',
            'chair' => 'Chair',
            'member' => 'Member',
            default => 'Unknown Role'
        };
    }
}
