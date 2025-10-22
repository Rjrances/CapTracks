<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Student extends Model
{
    protected $fillable = [
        'student_id',
        'name',
        'email',
        'course',
        'year',
        'semester',
        'offer_code',
    ];
    public function account()
    {
        return $this->hasOne(StudentAccount::class, 'student_id', 'student_id');
    }

    public function offering()
    {
        return $this->belongsTo(Offering::class, 'offer_code', 'offer_code');
    }
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members', 'student_id', 'group_id')
                    ->withPivot('role')
                    ->withTimestamps();
    }
    public function groupInvitations()
    {
        return $this->hasMany(GroupInvitation::class, 'student_id', 'student_id');
    }
    public function sentGroupInvitations()
    {
        return $this->hasMany(GroupInvitation::class, 'invited_by_student_id', 'student_id');
    }
    public function submissions()
    {
        return $this->hasMany(ProjectSubmission::class, 'student_id', 'student_id');
    }
    public function offerings()
    {
        return $this->belongsToMany(Offering::class, 'offering_student', 'student_id', 'offering_id', 'student_id', 'id')
                    ->withPivot('enrolled_at')
                    ->withTimestamps();
    }
    public function enrollInOffering(Offering $offering)
    {
        if (!$this->offerings()->where('offering_id', $offering->id)->exists()) {
            $this->offerings()->attach($offering->id, ['enrolled_at' => now()]);
        }
        return $this;
    }
    public function isEnrolled()
    {
        return $this->offerings()->exists();
    }
    public function getCurrentOffering()
    {
        return $this->offerings()->first();
    }

    public function enrollInOfferingByCode()
    {
        if ($this->offer_code) {
            $offering = Offering::where('offer_code', $this->offer_code)->first();
            if ($offering) {
                if (!$this->offerings()->where('offering_id', $offering->id)->exists()) {
                    $this->offerings()->attach($offering->id, ['enrolled_at' => now()]);
                }
                return $offering;
            }
        }
        return null;
    }
    protected $primaryKey = 'student_id';
    public $incrementing = false;
    protected $keyType = 'string';
}
