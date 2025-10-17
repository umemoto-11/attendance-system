@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<div class="attendance-detail__content">
    <div class="attendance-detail__heading">
        <h1>勤怠詳細</h1>
    </div>

    @php
    $isApproved = $attendance->status === 'approved';
    $breakCount = isset($attendance->breakTimes) && $attendance->breakTimes->isNotEmpty() ? $attendance->breakTimes->count() : 0;
    @endphp

    @if (!empty($attendance->id))
    <form action="{{ route('admin.attendances.store', $attendance->id) }}" method="post">
        @csrf
    @else
    <form action="{{ route('admin.attendances.store', ['id' => 0]) }}" method="post">
        @csrf
        <input type="hidden" name="user_id" value="{{ $user->id }}">
        <input type="hidden" name="date" value="{{ $attendance->date }}">
    @endif
        <div class="attendance-detail-table">
            <table class="attendance-detail-table__inner">
                <tr class="attendance-detail-table__row">
                    <th class="attendance-detail-table__header">名前</th>
                    <td class="attendance-detail-table__item">{{ $attendance->user->name }}</td>
                </tr>
                <tr class="attendance-detail-table__row">
                    <th class="attendance-detail-table__header">日付</th>
                    <td class="attendance-detail-table__item">
                        <span class="date-year">{{ \Carbon\Carbon::parse($attendance->date)->format('Y年') }}</span>
                        <span class="date-md">{{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}</span>
                    </td>
                </tr>
                <tr class="attendance-detail-table__row">
                    <th class="attendance-detail-table__header">出勤・退勤</th>
                    <td class="attendance-detail-table__item">
                        <div class="attendance-row">
                            <input type="time" name="corrected_clock_in" value="{{ old('corrected_clock_in', $attendance->clock_in_formatted) }}" {{ $isApproved ? 'disabled' : '' }}>
                            <span>~</span>
                            <input type="time" name="corrected_clock_out" value="{{ old('corrected_clock_out', $attendance->clock_out_formatted) }}" {{ $isApproved ? 'disabled' : '' }}>
                        </div>
                        @error('corrected_clock_in')
                        <p class="error-message">{{ $message }}</p>
                        @enderror
                        @error('corrected_clock_out')
                        <p class="error-message">{{ $message }}</p>
                        @enderror
                    </td>
                </tr>
                @php
                $breakCount = $attendance->breakTimes?->count() ?? 0;
                $nextIndex = $breakCount;
                @endphp
                @foreach ($attendance->breakTimes ?? [] as $index => $break)
                <tr class="attendance-detail-table__row">
                    <th class="attendance-detail-table__header">休憩{{ $index === 0 ? '' : $index + 1 }}</th>
                    <td class="attendance-detail-table__item">
                        <div class="break-row">
                            <input type="hidden" name="breaks[{{ $index }}][id]" value="{{ $break->id }}">
                            <input type="time" name="breaks[{{ $index }}][start]" value="{{ old("breaks.$index.start", $break->break_start_formatted) }}" {{ $isApproved ? 'disabled' : '' }}>
                            <span>~</span>
                            <input type="time" name="breaks[{{ $index }}][end]" value="{{ old("breaks.$index.end", $break->break_end_formatted) }}" {{ $isApproved ? 'disabled' : '' }}>
                        </div>
                        @error("breaks.$index.start")
                        <p class="error-message">{{ $message }}</p>
                        @enderror
                        @error("breaks.$index.end")
                        <p class="error-message">{{ $message }}</p>
                        @enderror
                    </td>
                </tr>
                @endforeach
                @if ($breakCount === 0)
                <tr class="attendance-detail-table__row">
                    <th class="attendance-detail-table__header">休憩{{ $breakCount === 0 ? '' : $breakCount + 1 }}</th>
                    <td class="attendance-detail-table__item">
                        <div class="break-row">
                            <input type="hidden" name="breaks[{{ $breakCount }}][id]" value="">
                            <input type="time" name="breaks[{{ $breakCount }}][start]" value="" {{ $isApproved ? 'disabled' : '' }}>
                            <span>~</span>
                            <input type="time" name="breaks[{{ $breakCount }}][end]" value="" {{ $isApproved ? 'disabled' : '' }}>
                        </div>
                        @error("breaks.$breakCount.start")
                        <p class="error-message">{{ $message }}</p>
                        @enderror
                        @error("breaks.$breakCount.end")
                        <p class="error-message">{{ $message }}</p>
                        @enderror
                    </td>
                </tr>
                @endif
                <tr class="attendance-detail-table__row">
                    <th class="attendance-detail-table__header">備考</th>
                    <td class="attendance-detail-table__item">
                        <textarea type="textarea" name="corrected_reason" {{ $isApproved ? 'disabled' : '' }}>{{ old('corrected_reason', $attendance->corrected_reason) }}</textarea>
                        @error('corrected_reason')
                        <p class="error-message-reason">{{ $message }}</p>
                        @enderror
                    </td>
                </tr>
            </table>
            @unless ($isApproved)
            <div class="attendance-detail__correction">
                <button class="attendance-detail__correction-button" type="submit">修正</button>
            </div>
            @endunless
        </div>
    </form>
</div>
@endsection
