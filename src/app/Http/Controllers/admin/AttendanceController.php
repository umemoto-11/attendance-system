<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use App\MOdels\User;
use App\Http\Requests\StoreAttendanceCorrectionRequest;

class AttendanceController extends Controller
{
    public function today(Request $request)
    {
        $selectedDate = $request->input('date', now()->toDateString());
        $selectedDate = Carbon::parse($request->input('date', now()));

        $attendances = Attendance::with('user', 'breakTimes')
            ->whereDate('date', $selectedDate)
            ->get()
            ->map(function ($attendance) {
                $totalBreakMinutes = $attendance->breakTimes->sum(function ($break) {
                    if ($break->break_start && $break->break_end) {
                        return Carbon::parse($break->break_start)->diffInMinutes($break->break_end);
                    }
                    return 0;
                });

                if ($attendance->clock_in && $attendance->clock_out) {
                    $workMinutes = Carbon::parse($attendance->clock_in)->diffInMinutes($attendance->clock_out);
                    $netWorkMinutes = $workMinutes - $totalBreakMinutes;
                } else {
                    $netWorkMinutes = null;
                }

                $attendance->total_break = sprintf('%02d:%02d', floor($totalBreakMinutes / 60), $totalBreakMinutes % 60);
                $attendance->total_work = is_null($netWorkMinutes) ? '' : sprintf('%02d:%02d', floor($netWorkMinutes / 60), $netWorkMinutes % 60);

                return $attendance;
            });

        return view('admin.attendance_list', compact('attendances', 'selectedDate'));
    }

    public function show($id, Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        $userId = $request->query('user_id');

        if ((int)$id === 0) {
            $user = User::find($userId);

            if (!$user) {
                abort(404, 'User not found');
            }

            $attendance = new Attendance([
                'user_id' => $user->id,
                'date' => $date,
            ]);

            $attendance->setRelation('breakTimes', collect());

            return view('admin.attendance_detail', compact('attendance', 'date', 'user'));
        }

        $attendance = Attendance::with('user', 'breakTimes')
            ->findOrFail($id);
        $user = $attendance->user;

        return view('admin.attendance_detail', compact('attendance', 'user'));
    }

    public function store(StoreAttendanceCorrectionRequest $request, $id)
    {
        if ((int) $id === 0) {
            $attendance = Attendance::create([
                'user_id' => $request->input('user_id'),
                'date' => $request->input('date'),
                'clock_in' => $request->input('corrected_clock_in'),
                'clock_out' => $request->input('corrected_clock_out'),
                'corrected_reason' => $request->input('corrected_reason'),
                'corrected_by' => auth()->id(),
                'corrected_date' => now(),
                'status' => 'approved',
            ]);
        } else {
            $attendance = Attendance::findOrFail($id);
            $attendance->update([
                'clock_in' => $request->input('corrected_clock_in'),
                'clock_out' => $request->input('corrected_clock_out'),
                'corrected_reason' => $request->input('corrected_reason'),
                'corrected_by' => auth()->id(),
                'corrected_date' => now(),
                'status' => 'approved',
            ]);
        }

        foreach ($request->input('breaks', []) as $index => $breakData) {
            $break = $attendance->breakTimes[$index] ?? null;

            if ($break) {
                $break->update([
                    'break_start' => $breakData['start'] ?? null,
                    'break_end' => $breakData['end'] ?? null,
                ]);
            } else {
                $attendance->breakTimes()->create([
                    'break_start' => $breakData['start'] ?? null,
                    'break_end' => $breakData['end'] ?? null,
                ]);
            }
        }

        return redirect()->route('admin.attendances.show', $attendance->id);
    }
}
