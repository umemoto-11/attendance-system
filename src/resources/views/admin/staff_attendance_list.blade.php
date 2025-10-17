@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="attendance-list__content">
    <div class="attendance-list__heading">
        <h1>{{ $user->name }}さんの勤怠</h1>
    </div>
    <div class="month-switcher__container">
        @php
        $selectedMonth = isset($selectedMonth) ? \Carbon\Carbon::parse($selectedMonth) : \Carbon\Carbon::now();
        $prev = $selectedMonth->copy()->subMonth()->format('Y-m');
        $next = $selectedMonth->copy()->addMonth()->format('Y-m');
        @endphp

        <div class="month-switcher">
            <form action="{{ route('admin.staffs.attendances', $user->id) }}" method="get">
                @csrf
                <button class="month-switcher__button" type="submit" name="month" value="{{ $prev }}">← 前月</button>
            </form>
            <form action="{{ route('admin.staffs.attendances', $user->id) }}" method="get">
                @csrf
                <div class="month-switcher__input-wrapper">
                    <img class="month-switcher__icon" src="{{ asset('img/50f4850c610ecd6f85b7ef666143260b91151a78.png') }}" alt="">
                    <span id="monthDisplay" class="month-display">{{ $selectedMonth->format('Y/m') }}</span>
                    <input id ="monthInput" class="month-switcher__input" type="month" name="month" value="{{ $selectedMonth->format('Y-m') }}" onchange="this.form.submit()">
                </div>
            </form>
            <form action="{{ route('admin.staffs.attendances', $user->id) }}" method="get">
                @csrf
                <button class="month-switcher__button" type="submit" name="month" value="{{ $next }}">翌月 →</button>
            </form>
        </div>
    </div>
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
                    {{ $day['attendance']?->clock_in_formatted }}
                </td>
                <td class="attendance-list-table__item">
                    {{ $day['attendance']?->clock_out_formatted }}
                </td>
                <td class="attendance-list-table__item">
                    @if ($day['attendance'] && ($day['attendance']->clock_in || $day['attendance']->clock_out))
                    {{ $day['attendance']->break_time_total }}
                    @endif
                </td>
                <td class="attendance-list-table__item">
                    @if ($day['attendance'] && ($day['attendance']->clock_in || $day['attendance']->clock_out))
                    {{ $day['attendance']->work_time_total }}
                    @endif
                </td>
                <td class="attendance-list-table__item">
                    @if ($day['attendance'])
                    <a href="{{ route('admin.attendances.show', $day['attendance']->id) }}">詳細</a>
                    @else
                    <a href="{{ route('admin.attendances.show', ['id' => 0, 'user_id' => $user->id, 'date' => $day['date']]) }}">詳細</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </table>
    </div>
    <div class="csv-export">
        <a href="{{ route('admin.staffs.attendances.export', ['user' => $user->id, 'month' => $selectedMonth->format('Y-m')]) }}">CSV出力</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('monthInput');
    const display = document.getElementById('monthDisplay');

    if (!display) return;

    if (input && input.value) {
        const [year, month] = input.value.split('-');
        if (year && month) display.textContent = `${year}/${month}`;
    }

    if (input) {
        input.addEventListener('input', function() {
            if (!this.value) {
                return;
            }
            const [y, m] = this.value.split('-');
            display.textContent = `${y}/${m}`;
        });
    }
});
</script>

@endsection