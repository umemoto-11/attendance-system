<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BreakTime extends Model
{
    use HasFactory;

    protected $table = 'breaks';

    protected $fillable = [
        'attendance_id',
        'break_start',
        'break_end',
        'corrected_break_start',
        'corrected_break_end',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function getBreakStartFormattedAttribute()
    {
        if (empty($this->break_start)) return '';

        return Carbon::parse($this->attributes['break_start'])->format('H:i');
    }

    public function getBreakEndFormattedAttribute()
    {
        if (empty($this->break_end)) return '';

        return Carbon::parse($this->attributes['break_end'])->format('H:i');
    }
}
