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
    public function submissions()
    {
        return $this->hasMany(ProjectSubmission::class);
    }
    public function offerings()
    {
        return $this->belongsToMany(Offering::class, 'offering_student', 'student_id', 'offering_id', 'student_id', 'id')
                    ->withTimestamps();
    }
    public function enrollInOffering(Offering $offering)
    {
        // Check if already enrolled to avoid duplicates
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
                // Check if already enrolled to avoid duplicates
                if (!$this->offerings()->where('offering_id', $offering->id)->exists()) {
                    $this->offerings()->attach($offering->id, ['enrolled_at' => now()]);
                }
                return $offering;
            }
        }
        return null;
    }
    // Set primary key
    protected $primaryKey = 'student_id';
    public $incrementing = false;
    protected $keyType = 'string';
}
