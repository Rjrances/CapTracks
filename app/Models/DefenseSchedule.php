<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class DefenseSchedule extends Model
{
    use HasFactory;
    protected $fillable = [
        'group_id',
        'defense_request_id',
        'stage',
        'academic_term_id',
        'start_at',
        'end_at',
        'room',
        'remarks',
        'status'
    ];
    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean'
    ];
    public function group()
    {
        return $this->belongsTo(Group::class);
    }
    public function academicTerm()
    {
        return $this->belongsTo(AcademicTerm::class, 'academic_term_id');
    }
    public function defenseRequest()
    {
        return $this->belongsTo(DefenseRequest::class);
    }
    public function defensePanels()
    {
        return $this->hasMany(DefensePanel::class);
    }
    public function panelists()
    {
        return $this->hasMany(DefensePanel::class);
    }

    public function ratingSheets()
    {
        return $this->hasMany(RatingSheet::class);
    }

    public function getFormattedDateTimeAttribute()
    {
        return $this->start_at->format('M d, Y') . ' at ' . $this->start_at->format('h:i A');
    }
    public function getStageLabelAttribute()
    {
        return match($this->stage) {
            'proposal' => 'Proposal',
            '60' => '60% Defense',
            '100' => '100% Defense',
            default => 'Unknown Stage'
        };
    }

    /**
     * Label for UI: linked defense request type when present, otherwise manual schedule stage.
     */
    public function getDefenseTypeLabelAttribute(): string
    {
        if ($this->defense_request_id) {
            $request = $this->relationLoaded('defenseRequest')
                ? $this->defenseRequest
                : $this->defenseRequest()->first();
            if ($request) {
                return $request->defense_type_label;
            }
        }

        return $this->stage_label;
    }
    public function getDurationAttribute()
    {
        if ($this->start_at && $this->end_at) {
            return $this->start_at->diffInMinutes($this->end_at);
        }
        return 0;
    }
    public function getFormattedDurationAttribute()
    {
        $duration = $this->duration;
        if ($duration < 60) {
            return $duration . ' minutes';
        }
        $hours = floor($duration / 60);
        $minutes = $duration % 60;
        return $hours . 'h ' . $minutes . 'm';
    }
    public function isScheduled()
    {
        return $this->status === 'scheduled';
    }
    public function isInProgress()
    {
        return $this->status === 'in_progress';
    }
    public function isCompleted()
    {
        return $this->status === 'completed';
    }
    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    /** Bootstrap background variant for status badges (primary, success, …). */
    public function getStatusBadgeVariantAttribute(): string
    {
        return match ($this->status) {
            'scheduled' => 'primary',
            'in_progress' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }

    /** Human-readable workflow status for lists and detail views. */
    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', (string) $this->status));
    }
}
