<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    protected $fillable = ['user_id', 'role'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function roleModel()
    {
        return $this->belongsTo(Role::class, 'role', 'name');
    }
}
