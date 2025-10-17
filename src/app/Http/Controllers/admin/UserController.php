<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class UserController extends Controller
{
    public function index()
    {
        $staffs = User::where('role', 'user')->get();

        return view('admin.staff_list', compact('staffs'));
    }

    public function show(Request $request, User $user)
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
            ->where('user_id', $user->id)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy(function ($attendance) {
                return Carbon::parse($attendance->date)->format('Y-m-d');
            });

        $attendanceList = collect($period)->map(function ($date) use ($attendances) {
            $dateStr = $date->format('Y-m-d');
            $attendance = $attendances[$dateStr] ?? null;

            if ($attendance) {
                $totalBreakMinutes = $attendance->breakTimes->sum(function ($break) {
                    return $break->break_start && $break->break_end ? Carbon::parse($break->break_start)->diffInMinutes(Carbon::parse($break->break_end)) : 0;
                });

                $attendance->break_time_total = sprintf('%02d:%02d', floor($totalBreakMinutes / 60), $totalBreakMinutes % 60);

                $workMinutes = ($attendance->clock_in && $attendance->clock_out) ? Carbon::parse($attendance->clock_in)->diffInMinutes(Carbon::parse($attendance->clock_out)): 0;

                $actualMinutes = max(0, $workMinutes - $totalBreakMinutes);

                $attendance->work_time_total = sprintf('%02d:%02d', floor($actualMinutes / 60), $actualMinutes % 60);
            }

            return [
                'date' => $dateStr,
                'attendance' => $attendance,
            ];
        });

        return view('admin.staff_attendance_list', compact('user', 'attendanceList', 'selectedMonth'));
    }

    public function export(Request $request, User $user)
    {
        $selectedMonth = $request->input('month', now()->format('Y-m'));
        [$year, $month] = explode('-', $selectedMonth);

        $start = Carbon::create($year, $month, 1);
        $end = $start->copy()->endOfMonth();

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy(fn($attendance) => Carbon::parse($attendance->date)->format('Y-m-d'));

        $period = CarbonPeriod::create($start, '1 day', $end);

        $csvData = [];

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $attendance = $attendances[$dateStr] ?? null;

            $break_time_total = '';
            $work_time_total = '';

            if ($attendance) {
                $totalBreakMinutes = $attendance->breakTimes->sum(function ($break) {
                    if ($break->break_start && $break->break_end) {
                        return Carbon::parse($break->break_start)->diffInMinutes($break->break_end);
                    }
                    return 0;
                });
                $break_time_total = sprintf('%02d:%02d', floor($totalBreakMinutes / 60), $totalBreakMinutes % 60);

                if ($attendance->clock_in && $attendance->clock_out) {
                    $workMinutes = Carbon::parse($attendance->clock_in)->diffInMinutes($attendance->clock_out);
                    $netWorkMinutes = $workMinutes - $totalBreakMinutes;
                    $work_time_total = sprintf('%02d:%02d', floor($netWorkMinutes / 60), $netWorkMinutes % 60);
                }
            }

            $row = [
                '日付' => $date->isoFormat('MM/DD(ddd)'),
                '出勤' => $attendance?->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '',
                '退勤' => $attendance?->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '',
                '休憩' => $break_time_total,
                '合計' => $work_time_total,
            ];

            $csvData[] = $row;
        }

        $filename = "{$user->name}_{$selectedMonth}_勤怠一覧.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($csvData) {
            $output = fopen('php://output', 'w');
            fputcsv($output, array_keys($csvData[0]));

            foreach ($csvData as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }
}
