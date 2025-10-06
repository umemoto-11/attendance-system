<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function record()
    {
        $attendance = Attendance::with('breakTimes')
            ->where('user_id', auth()->id())
            ->whereDate('date', today())
            ->first();

        if (!$attendance || is_null($attendance->clock_in)) {
            $status = '勤務外';
        } elseif (is_null($attendance->clock_out) && $attendance->breakTimes?->whereNull('break_end')->isNotEmpty()) {
            $status = '休憩中';
        } elseif (is_null($attendance->clock_out)) {
            $status = '出勤中';
        } else {
            $status = '退勤済み';
        }

        $now = Carbon::now();
        $today = $now->isoFormat('YYYY年MM月DD日(ddd)');
        $time = $now->format('H:i');

        return view('attendance', compact('attendance', 'status', 'today', 'time'));
    }

    public function store(Request $request)
    {
        $action = $request->input('action');
        $userId = auth()->id();

        switch ($action) {
            case 'clock_in':
                Attendance::firstOrCreate(
                    ['user_id' => $userId, 'date' => today()],
                    ['clock_in' => now()],
                );
                break;
            case 'clock_out':
                $attendance = Attendance::where('user_id', $userId)
                    ->whereDate('date', today())
                    ->firstOrFail();
                $attendance
                    ->update([
                        'clock_out' => now(),
                    ]);
                break;
            case 'break_start':
                $attendance = Attendance::where('user_id', $userId)
                    ->whereNull('clock_out')
                    ->latest()
                    ->first();
                $attendance->breakTimes()
                    ->create([
                    'break_start' => now(),
                ]);
                break;
            case 'break_end':
                $attendance = Attendance::where('user_id', $userId)
                    ->latest()
                    ->first();
                $attendance->breakTimes()
                    ->whereNull('break_end')
                    ->latest()
                    ->firstOrFail()
                    ->update([
                        'break_end' => now()
                    ]);
                break;
        }

        return redirect()->route('attendance');
    }
}
