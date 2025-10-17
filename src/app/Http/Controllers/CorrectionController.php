<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Http\Requests\StoreAttendanceCorrectionRequest;
use Carbon\Carbon;

class CorrectionController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'pending');

        $requests = Attendance::with('user')
            ->whereNotNull('corrected_reason')
            ->when($tab === 'pending', fn($q) => $q->where('status', 'pending'))
            ->when($tab === 'approved', fn($q) => $q->where('status', 'approved'))
            ->orderBy('corrected_date', 'asc')
            ->get();

        return view('request_list', compact('requests', 'tab'));
    }

    public function store(StoreAttendanceCorrectionRequest $request, $attendanceId)
    {
        $attendance = Attendance::with('breakTimes')->firstOrCreate(
            ['id' => $attendanceId],
            [
                'user_id' => auth()->id(),
                'date' => $request->date,
                'status' => 'pending',
            ]
        );

        $correctedClockIn  = $request->input('corrected_clock_in');
        $correctedClockOut = $request->input('corrected_clock_out');

        $updateData = [
            'corrected_clock_in' => $request->input('corrected_clock_in') ? Carbon::parse($request->input('corrected_clock_in'))->format('H:i') : null,
            'corrected_clock_out' => $request->input('corrected_clock_out') ? Carbon::parse($request->input('corrected_clock_out'))->format('H:i') : null,
            'corrected_reason' => $request->input('corrected_reason') ?: null,
            'corrected_by' => auth()->id(),
            'corrected_date' => now(),
            'status' => 'pending',
        ];

        if (
            $request->filled('corrected_clock_in') ||
            $request->filled('corrected_clock_out') ||
            $request->filled('corrected_reason') ||
            collect($request->input('breaks', []))->filter(fn($b) => !empty($b['start']) || !empty($b['end']))->isNotEmpty()
        ) {
            $updateData['status'] = 'pending';
        }

        $attendance->update($updateData);

        foreach ($request->input('breaks', []) as $index => $breakData) {
            $break = $attendance->breakTimes[$index] ?? null;

            $start = !empty($breakData['start']) ? Carbon::parse($breakData['start'])->format('H:i') : null;
            $end   = !empty($breakData['end']) ? Carbon::parse($breakData['end'])->format('H:i') : null;

            if ($break) {
                $break->update([
                    'corrected_break_start' => $breakData['start'] ?? null,
                    'corrected_break_end' => $breakData['end'] ?? null,
                ]);
            } else {
                $attendance->breakTimes()->create([
                    'corrected_break_start' => $breakData['start'] ?? null,
                    'corrected_break_end' => $breakData['end'] ?? null,
                ]);
            }
        }

        if (!$attendance->id) {
            $attendance->save();
        }

        return redirect()->route('attendance.show', ['id' => $attendance->id]);
    }
}
