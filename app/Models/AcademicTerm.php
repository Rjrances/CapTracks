<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class AcademicTerm extends Model
{
    use HasFactory;
    protected $fillable = [
        'school_year',
        'semester',
        'is_active',
        'is_archived'
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'is_archived' => 'boolean'
    ];
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    public function scopeNotArchived($query)
    {
        return $query->where('is_archived', false);
    }
    public function getFullNameAttribute()
    {
        return "{$this->school_year} - {$this->semester}";
    }
    public function groups()
    {
        return $this->hasMany(Group::class);
    }
    public function offerings()
    {
        return $this->hasMany(Offering::class);
    }
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
