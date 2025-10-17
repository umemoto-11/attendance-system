@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="attendance-list__content">
    <div class="attendance-list__heading">
        <h1>{{ $selectedDate->format('Y年n月j日') }}の勤怠</h1>
    </div>
    <div class="date-switcher__container">
        @php
        $selectedDate = isset($selectedDate) ? \Carbon\Carbon::parse($selectedDate) : \Carbon\Carbon::today();

        $prev = $selectedDate->copy()->subDay()->format('Y-m-d');
        $next = $selectedDate->copy()->addDay()->format('Y-m-d');
        @endphp

        <div class="date-switcher">
            <form action="{{ route('admin.attendances.today') }}" method="get">
                @csrf
                <button class="date-switcher__button" type="submit" name="date" value="{{ $prev }}">← 前日</button>
            </form>
            <form action="{{ route('admin.attendances.today') }}" method="get">
                @csrf
                <div class="date-switcher__input-wrapper">
                    <img class="date-switcher__icon" src="{{ asset('img/50f4850c610ecd6f85b7ef666143260b91151a78.png') }}" alt="">
                    <span id="dateDisplay" class="date-display">{{ $selectedDate->format('Y/m/d') }}</span>
                    <input id="dateInput" class="date-switcher__input" type="date" name="date" value="{{ $selectedDate->format('Y-m-d') }}" onchange="this.form.submit()">
                </div>
            </form>
            <form action="{{ route('admin.attendances.today') }}" method="get">
                @csrf
                <button class="date-switcher__button" type="submit" name="date" value="{{ $next }}">翌日 →</button>
            </form>
        </div>
    </div>
    <div class="attendance-list-table">
        <table class="attendance-list-table__inner">
            <tr class="attendance-list-table__row">
                <th class="attendance-list-table__header">名前</th>
                <th class="attendance-list-table__header">出勤</th>
                <th class="attendance-list-table__header">退勤</th>
                <th class="attendance-list-table__header">休憩</th>
                <th class="attendance-list-table__header">合計</th>
                <th class="attendance-list-table__header">詳細</th>
            </tr>
            @forelse ($attendances as $attendance)
            <tr class="attendance-list-table__row">
                <td class="attendance-list-table__item">
                    {{ $attendance->user->name }}
                </td>
                <td class="attendance-list-table__item">
                    {{ $attendance->clock_in_formatted }}
                </td>
                <td class="attendance-list-table__item">
                    {{ $attendance->clock_out_formatted }}
                </td>
                <td class="attendance-list-table__item">
                    {{ $attendance->total_break ?? '' }}
                </td>
                <td class="attendance-list-table__item">
                    {{ $attendance->total_work ?? '' }}
                </td>
                <td class="attendance-list-table__item">
                    <a href="{{ route('admin.attendances.show', $attendance->id) }}">詳細</a>
                </td>
            </tr>
            @empty
            @endforelse
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('dateInput');
    const display = document.getElementById('dateDisplay');

    if (!display) return;

    if (input && input.value) {
        const [year, month, day] = input.value.split('-');
        if (year && month && day) display.textContent = `${year}/${month}/${day}`;
    }

    if (input) {
        input.addEventListener('input', function() {
            if (!this.value) {
                return;
            }
            const [y, m, d] = this.value.split('-');
            display.textContent = `${y}/${m}/${d}`;
        });
    }
});
</script>

@endsection