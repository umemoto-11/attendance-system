@extends('layouts.auth')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<div class="attendance-detail__content">
    <div class="attendance-detail__heading">
        <h1>勤怠詳細</h1>
    </div>

    @if ($attendance->has_correction_request)
    <div class="attendance-detail-table">
        <table class="attendance-detail-table__inner">
            <tr>
                <th class="attendance-detail-table__header">名前</th>
                <td class="attendance-detail-table__item">{{ $user->name }}</td>
            </tr>
            <tr>
                <th class="attendance-detail-table__header">日付</th>
                <td class="attendance-detail-table__item">
                    <span class="date-year">{{ \Carbon\Carbon::parse($attendance->date)->format('Y年') }}</span>
                    <span class="date-md">{{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}</span>
                </td>
            </tr>
            <tr>
                <th class="attendance-detail-table__header">出勤・退勤</th>
                <td class="attendance-detail-table__item">
                    @php
                        $clockIn = $attendance->corrected_clock_in ?? $attendance->clock_in;
                        $clockOut = $attendance->corrected_clock_out ?? $attendance->clock_out;
                        $clockInFormatted = $clockIn ? \Carbon\Carbon::parse($clockIn)->format('H:i') : '';
                        $clockOutFormatted = $clockOut ? \Carbon\Carbon::parse($clockOut)->format('H:i') : '';
                    @endphp
                    <input type="time" value="{{ $clockInFormatted }}" disabled>
                    <span>~</span>
                    <input type="time" value="{{ $clockOutFormatted }}" disabled>
                </td>
            </tr>
            @foreach ($attendance->breakTimes ?? [] as $index => $break)
            <tr>
                <th class="attendance-detail-table__header">休憩{{ $index === 0 ? '' : $index + 1 }}</th>
                <td class="attendance-detail-table__item">
                    <input type="time" value="{{ $break->corrected_break_start ?? $break->break_start }}" disabled>
                    <span>~</span>
                    <input type="time" value="{{ $break->corrected_break_end ?? $break->break_end }}" disabled>
                </td>
            </tr>
            @endforeach
            <tr>
                <th class="attendance-detail-table__header">備考</th>
                <td class="attendance-detail-table__item">
                    <textarea name="corrected_reason" disabled>{{ $attendance->corrected_reason }}</textarea>
                </td>
            </tr>
        </table>
        <div class="attendance-detail__correction">
            <p>*承認待ちのため修正はできません。</p>
        </div>
    </div>

    @elseif ($attendance->status === 'approved')
    <div class="attendance-detail-table">
        <table class="attendance-detail-table__inner">
            <tr>
                <th class="attendance-detail-table__header">名前</th>
                <td class="attendance-detail-table__item">{{ $user->name }}</td>
            </tr>
            <tr>
                <th class="attendance-detail-table__header">日付</th>
                <td class="attendance-detail-table__item">
                    <span class="date-year">{{ \Carbon\Carbon::parse($attendance->date)->format('Y年') }}</span>
                    <span class="date-md">{{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}</span>
                </td>
            </tr>
            <tr>
                <th class="attendance-detail-table__header">出勤・退勤</th>
                <td class="attendance-detail-table__item">
                    <input type="time" value="{{ $attendance->clock_in }}" disabled>
                    <span>~</span>
                    <input type="time" value="{{ $attendance->clock_out }}" disabled>
                </td>
            </tr>
            @foreach ($attendance->breakTimes ?? [] as $index => $break)
            <tr>
                <th class="attendance-detail-table__header">休憩{{ $index === 0 ? '' : $index + 1 }}</th>
                <td class="attendance-detail-table__item">
                    <input type="time" value="{{ $break->break_start }}" disabled>
                    <span>~</span>
                    <input type="time" value="{{ $break->break_end }}" disabled>
                </td>
            </tr>
            @endforeach
            <tr>
                <th class="attendance-detail-table__header">備考</th>
                <td class="attendance-detail-table__item">
                    <textarea disabled>{{ $attendance->corrected_reason }}</textarea>
                </td>
            </tr>
        </table>
    </div>

    @else
    <form action="{{ route('attendance.correction', $attendance->id ?? 0) }}" method="post">
        @csrf
        <div class="attendance-detail-table">
            <table class="attendance-detail-table__inner">
                <tr>
                    <th class="attendance-detail-table__header">名前</th>
                    <td class="attendance-detail-table__item">{{ $user->name }}</td>
                </tr>
                <tr>
                    <th class="attendance-detail-table__header">日付</th>
                    <td class="attendance-detail-table__item">
                        <span class="date-year">{{ \Carbon\Carbon::parse($attendance->date)->format('Y年') }}</span>
                        <span class="date-md">{{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}</span>
                        <input type="hidden" name="date" value="{{ $attendance->date ?? now()->toDateString() }}">
                    </td>
                </tr>
                <tr>
                    <th class="attendance-detail-table__header">出勤・退勤</th>
                    <td class="attendance-detail-table__item">
                        <div class="attendance-row">
                            <input type="time" name="corrected_clock_in" value="{{ old('corrected_clock_in', $attendance->corrected_clock_in ?? $attendance->clock_in_formatted) }}">
                            <span>~</span>
                            <input type="time" name="corrected_clock_out" value="{{ old('corrected_clock_out', $attendance->corrected_clock_out ?? $attendance->clock_out_formatted) }}">
                        </div>
                        @error('corrected_clock_in')<p class="error-message">{{ $message }}</p>@enderror
                        @error('corrected_clock_out')<p class="error-message">{{ $message }}</p>@enderror
                    </td>
                </tr>
                @php
                $breakCount = $attendance->breakTimes?->count() ?? 0;
                $nextIndex = $breakCount;
                @endphp
                @foreach ($attendance->breakTimes ?? [] as $index => $break)
                <tr>
                    <th class="attendance-detail-table__header">休憩{{ $index === 0 ? '' : $index + 1 }}</th>
                    <td class="attendance-detail-table__item">
                        <div class="break-row">
                            <input type="hidden" name="breaks[{{ $index }}][id]" value="{{ $break->id }}">
                            <input type="time" name="breaks[{{ $index }}][start]" value="{{ old("breaks.$index.start", $break->corrected_break_start ?? $break->break_start_formatted) }}">
                            <span>~</span>
                            <input type="time" name="breaks[{{ $index }}][end]" value="{{ old("breaks.$index.end", $break->corrected_break_end ?? $break->break_end_formatted) }}">
                        </div>
                        @error("breaks.$index.start")<p class="error-message">{{ $message }}</p>@enderror
                        @error("breaks.$index.end")<p class="error-message">{{ $message }}</p>@enderror
                    </td>
                </tr>
                @endforeach
                <tr class="attendance-detail-table__row">
                    <th class="attendance-detail-table__header">休憩{{ $breakCount === 0 ? '' : $breakCount + 1 }}</th>
                    <td class="attendance-detail-table__item">
                        <div class="break-row">
                            <input type="time" name="breaks[{{ $nextIndex }}][start]"
                                value="{{ old("breaks.$nextIndex.start", '') }}">
                            <span>~</span>
                            <input type="time" name="breaks[{{ $nextIndex }}][end]"
                                value="{{ old("breaks.$nextIndex.end", '') }}">
                        </div>
                        @error("breaks.$nextIndex.start")<p class="error-message">{{ $message }}</p>@enderror
                        @error("breaks.$nextIndex.end")<p class="error-message">{{ $message }}</p>@enderror
                    </td>
                </tr>
                <tr>
                    <th class="attendance-detail-table__header">備考</th>
                    <td class="attendance-detail-table__item">
                        <textarea name="corrected_reason">{{ old('corrected_reason') }}</textarea>
                        @error('corrected_reason')<p class="error-message-reason">{{ $message }}</p>@enderror
                    </td>
                </tr>
            </table>
            <div class="attendance-detail__correction">
                <button class="attendance-detail__correction-button" type="submit">修正</button>
            </div>
        </div>
    </form>
    @endif
</div>
@endsection