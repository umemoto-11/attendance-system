<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'corrected_clock_in',
        'corrected_clock_out',
        'corrected_reason',
        'corrected_by',
        'corrected_date',
        'status',
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

    public function getClockInFormattedAttribute()
    {
        return $this->clock_in ? Carbon::parse($this->clock_in)->format('H:i') : '';
    }

    public function getClockOutFormattedAttribute()
    {
        return $this->clock_out ? Carbon::parse($this->clock_out)->format('H:i') : '';
    }

    public function getHasCorrectionRequestAttribute()
    {
        return $this->status === 'pending' && (
            $this->corrected_clock_in ||
            $this->corrected_clock_out ||
            $this->corrected_reason ||
            $this->breakTimes()->whereNotNull('corrected_break_start')->exists()
        );
    }
}
