<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

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

    public function index(Request $request)
    {
        $year = now()->year;
        $month = now()->month;

        $selectedMonth = $request->input('month', now()->format('Y-m'));
        $selectedMonth = Carbon::parse($request->input('month', now()));
        [$year, $month] = explode('-', $selectedMonth);

        $start = Carbon::create($year, $month, 1);
        $end = $start->copy()->endOfMonth();
        $period = CarbonPeriod::create($start, '1 day', $end);

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', auth()->id())
            ->whereBetween('date', [$start, $end])
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy(function ($attendances) {
                return Carbon::parse($attendances->date)->format('Y-m-d');
            });

        $attendanceList = collect($period)->map(function ($date) use ($attendances) {
            $dateStr = $date->format('Y-m-d');
            $attendance = $attendances[$dateStr] ?? null;

            if ($attendance) {
                $totalBreakMinutes = $attendance->breakTimes->sum(function ($break) {
                    return $break->break_start && $break->break_end ? Carbon::parse($break->break_start)->diffInMinutes(Carbon::parse($break->break_end)) : 0;
                });

                $attendance->break_time_total = sprintf('%02d:%02d', floor($totalBreakMinutes / 60), $totalBreakMinutes % 60);

                $workMinutes = ($attendance->clock_in && $attendance->clock_out) ? Carbon::parse($attendance->clock_in)->diffInMinutes(Carbon::parse($attendance->clock_out)) : 0;

                $actualMinutes = max(0, $workMinutes - $totalBreakMinutes);

                $attendance->work_time_total = sprintf('%02d:%02d', floor($actualMinutes / 60), $actualMinutes % 60);
            }

            return [
                'date' => $dateStr,
                'attendance' => $attendance,
            ];
        });

        return view('attendance_list', compact('attendanceList', 'selectedMonth'));
    }

    public function show($id, Request $request)
    {
        $user = auth()->user();

        if ($id == 0) {
            $date = $request->query('date', now()->toDateString());

            $attendance = new Attendance([
                'user_id' => $user->id,
                'date' => $date,
            ]);

            $attendance->setRelation('breakTimes', collect());

            return view('attendance_detail',compact('attendance', 'user'));
        }

        $attendance = Attendance::with('breakTimes')
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('attendance_detail', compact('attendance', 'user'));
    }
}
