<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = ['offering_id', 'day', 'time'];

    public function offering()
    {
        return $this->belongsTo(Offering::class);
    }
}
