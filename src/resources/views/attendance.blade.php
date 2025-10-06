@extends('layouts.auth')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance__content">
    <div class="status">
        {{ $status }}
    </div>
    <div class="date">
        {{ $today }}
    </div>
    <div class="clock">
        {{ $time }}

        <script>
            function displayTime() {
                const now = new Date();
                const hour = String(now.getHours()).padStart(2, '0');
                const minute = String(now.getMinutes()).padStart(2, '0');

                const currentTime = `${hour}:${minute}`;
                document.querySelector('.clock').textContent = currentTime;
            }

            displayTime();
            setInterval(displayTime, 1000);
        </script>
    </div>
    <div class="form__button">
        @if ($status === '勤務外')
        <form action="{{ route('attendance.store') }}" method="post">
            @csrf
            <input type="hidden" name="action" value="clock_in">
            <button class="attendance__button-submit" type="submit">出勤</button>
        </form>
        @elseif ($status === '出勤中')
        <form action="{{ route('attendance.store') }}" method="post">
            @csrf
            <input type="hidden" name="action" value="clock_out">
            <button class="attendance__button-submit" type="submit">退勤</button>
        </form>
        <form action="{{ route('attendance.store') }}" method="post">
            @csrf
            <input type="hidden" name="action" value="break_start">
            <button class="break__button-submit" type="submit">休憩入</button>
        </form>
        @elseif ($status === '休憩中')
        <form action="{{ route('attendance.store') }}" method="post">
            @csrf
            <input type="hidden" name="action" value="break_end">
            <button class="break__button-submit" type="submit">休憩戻</button>
        </form>
        @else
            <p>お疲れ様でした。</p>
        @endif
    </div>
</div>
@endsection