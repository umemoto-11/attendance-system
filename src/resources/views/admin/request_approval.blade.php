@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<div class="request-approval__content">
    <div class="request-approval__heading">
        <h1>勤怠詳細</h1>
    </div>
    <form action="{{ route('admin.corrections.approved', $attendance->id) }}" method="post">
        @csrf
        <div class="request-approval-table">
            <table class="request-approval-table__inner">
                <tr>
                    <th class="request-approval-table__header">名前</th>
                    <td class="request-approval-table__item">{{ $attendance->user->name }}</td>
                </tr>
                <tr>
                    <th class="request-approval-table__header">日付</th>
                    <td class="request-approval-table__item">
                        <span class="date-year">{{ \Carbon\Carbon::parse($attendance->date)->format('Y年') }}</span>
                        <span class="date-md">{{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}</span>
                    </td>
                </tr>
                <tr>
                    <th class="request-approval-table__header">出勤・退勤</th>
                    <td class="request-approval-table__item">
                        <input type="time" value="{{ $attendance->corrected_clock_in ?? $attendance->clock_in }}" disabled>
                        <span>~</span>
                        <input type="time" value="{{ $attendance->corrected_clock_out ?? $attendance->clock_out }}" disabled>
                    </td>
                </tr>
                @php
                $breakCount = isset($attendance->breakTimes) && $attendance->breakTimes->isNotEmpty() ? $attendance->breakTimes->count() : 0;
                @endphp
                @foreach ($attendance->breakTimes ?? [] as $index => $break)
                <tr>
                    <th class="request-approval-table__header">休憩{{ $index === 0 ? '' : $index + 1 }}</th>
                    <td class="request-approval-table__item">
                        <input type="time" value="{{ $break->corrected_break_start ?? $break->break_start }}" disabled>
                        <span>~</span>
                        <input type="time" value="{{ $break->corrected_break_end ?? $break->break_end }}" disabled>
                    </td>
                </tr>
                @endforeach
                @if ($breakCount === 0)
                <tr class="attendance-detail-table__row">
                    <th class="attendance-detail-table__header">休憩</th>
                    <td class="attendance-detail-table__item">
                        <input type="time" value="{{ old('breaks.0.start', '') }}" disabled>
                        <span>~</span>
                        <input type="time" value="{{ old('breaks.0.end', '') }}" disabled>
                    </td>
                </tr>
                @endif
                <tr>
                    <th class="request-approval-table__header">備考</th>
                    <td class="request-approval-table__item">
                        <textarea name="corrected_reason" disabled>{{ $attendance->corrected_reason }}</textarea>
                    </td>
                </tr>
            </table>
            <div class="request-approval__button">
                @if ($attendance->status === 'approved')
                <p class="approval-label">承認済み</p>
                @else
                <button class="request-approval__button-submit" type="submit">承認</button>
                @endif
            </div>
        </div>
    </form>
</div>
@endsection