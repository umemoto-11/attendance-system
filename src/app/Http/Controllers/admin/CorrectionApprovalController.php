<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;

class CorrectionApprovalController extends Controller
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

        return view('admin.request_list', compact('requests', 'tab'));
    }

    public function show($id)
    {
        $attendance = Attendance::with(['user', 'breakTimes'])
            ->findOrFail($id);

        return view('admin.request_approval', compact('attendance'));
    }

    public function approve($id)
    {
        $attendance = Attendance::with('breakTimes')
            ->findOrFail($id);

        $attendance->update([
            'clock_in' => $attendance->corrected_clock_in,
            'clock_out' => $attendance->corrected_clock_out,
            'status' => 'approved',
        ]);

        foreach ($attendance->breakTimes as $break) {
            $break->update([
                'break_start' => $break->corrected_break_start ?? $break->break_start,
                'break_end' => $break->corrected_break_end ?? $break->break_end,
            ]);
        }

        return redirect()->route('admin.corrections.show', $attendance->id);
    }
}
