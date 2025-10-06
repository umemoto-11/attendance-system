@extends('layouts.auth')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="attendance-list__content">
    <div class="attendance-list__heading">
        <h1>勤怠一覧</h1>
    </div>
    <form action="{{ route('attendance.index') }}" method="method">
        @csrf
        <input type="month" name="month" value="{{ $selectedMonth }}" onchange="this.form.submit()">

        @php
        $current = Carbon\Carbon::createFromFormat('Y-m', $selectedMonth);
        $prev = $current->copy()->subMonth()->format('Y-m');
        $next = $current->copy()->addMonth()->format('Y-m');
        @endphp

        <button type="submit" name="month" value="{{ $prev }}">前月</button>
        <button type="submit" name="month" value="{{ $next }}">翌月</button>
    </form>
    <div class="attendance-list-table">
        <table class="attendance-list-table__inner">
            <tr class="attendance-list-table__row">
                <th class="attendance-list-table__header">日付</th>
                <th class="attendance-list-table__header">出勤</th>
                <th class="attendance-list-table__header">退勤</th>
                <th class="attendance-list-table__header">休憩</th>
                <th class="attendance-list-table__header">合計</th>
                <th class="attendance-list-table__header">詳細</th>
            </tr>
            @foreach ($attendanceList as $day)
            <tr class="attendance-list-table__row">
                <td class="attendance-list-table__item">
                    {{ \Carbon\Carbon::parse($day['date'])->isoFormat('MM/DD(ddd)') }}
                </td>
                <td class="attendance-list-table__item">
                    {{ $day['attendance'] && $day['attendance']->clock_in ? \Carbon\Carbon::parse($day['attendance']->clock_in)->format('H:i') : '' }}
                </td>
                <td class="attendance-list-table__item">
                    {{ $day['attendance'] && $day['attendance']->clock_out ? \Carbon\Carbon::parse($day['attendance']->clock_out)->format('H:i') : '' }}
                </td>
                <td class="attendance-list-table__item">
                    {{ $day['attendance']?->break_time_total ?? '' }}
                </td>
                <td class="attendance-list-table__item">
                    {{ $day['attendance']?->work_time_total ?? '' }}
                </td>
                <td class="attendance-list-table__item">
                    <a href="">詳細</a>
                </td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection