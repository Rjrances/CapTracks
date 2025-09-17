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
        'password',
        'must_change_password',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
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
        return $this->belongsToMany(Offering::class, 'offering_student')
                    ->withTimestamps();
    }
    public function enrollInOffering(Offering $offering)
    {
        $this->offerings()->detach();
        $this->offerings()->attach($offering->id);
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
    protected $casts = [
        'must_change_password' => 'boolean',
    ];
}
