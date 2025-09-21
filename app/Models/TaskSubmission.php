<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class TaskSubmission extends Model
{
    use HasFactory;
    protected $fillable = [
        'group_milestone_task_id',
        'student_id',
        'submission_type',
        'file_path',
        'description',
        'notes',
        'status',
        'adviser_feedback',
        'reviewed_by',
        'reviewed_at',
        'progress_percentage',
    ];
    protected $casts = [
        'reviewed_at' => 'datetime',
        'progress_percentage' => 'integer',
    ];
    public function groupMilestoneTask()
    {
        return $this->belongsTo(GroupMilestoneTask::class);
    }
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_id');
    }
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
    public function isPending()
    {
        return $this->status === 'pending';
    }
    public function isApproved()
    {
        return $this->status === 'approved';
    }
    public function isRejected()
    {
        return $this->status === 'rejected';
    }
    public function getStatusBadgeClassAttribute()
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'secondary'
        };
    }
    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'pending' => 'Under Review',
            'approved' => 'Approved',
            'rejected' => 'Needs Revision',
            default => 'Unknown'
        };
    }
    public function getSubmissionTypeTextAttribute()
    {
        return match($this->submission_type) {
            'document' => 'Document',
            'screenshots' => 'Screenshots',
            'progress_notes' => 'Progress Notes',
            default => 'Unknown'
        };
    }
    public function isForPhase($phase)
    {
        $taskName = strtolower($this->groupMilestoneTask->milestoneTask->name ?? '');
        return match($phase) {
            'must_haves' => str_contains($taskName, 'must') || str_contains($taskName, 'have'),
            'chapter_1_2' => str_contains($taskName, 'chapter') && (str_contains($taskName, '1') || str_contains($taskName, '2')),
            'screenshots_60' => str_contains($taskName, '60') || str_contains($taskName, 'screenshot'),
            'screenshots_100' => str_contains($taskName, '100') || str_contains($taskName, 'screenshot'),
            'chapter_3_4' => str_contains($taskName, 'chapter') && (str_contains($taskName, '3') || str_contains($taskName, '4')),
            default => false
        };
    }
}
