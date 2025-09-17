<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'clock_in',
        'clock_out',
        'corrected_clock_in',
        'corrected_clock_out',
        'corrected_reason',
        'corrected_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function correctedBy()
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }
}
