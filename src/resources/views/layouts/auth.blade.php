@extends('layouts.app')

@section('header')
<nav>
    <ul class="header-nav">
        <li class="header-nav__item">
            <a class="header__link header__link--attendance" href="/attendance">
                {{ $status === '退勤済' ? '' : '勤怠' }}
            </a>
        </li>
        <li>
            <a class="header__link header__link--attendance-list" href="/attendance/list">
                {{ $status === '退勤済' ? '今月の出勤一覧' : '勤怠一覧' }}
            </a>
        </li>
        <li>
            <a class="header__link header__link--request" href="/stamp_correction_request/list">
                {{ $status === '退勤済' ? '申請一覧' : '申請' }}
            </a>
        </li>
        <li>
            <form action="{{ route('logout') }}" method="post">
                @csrf
                <button class="header__logout-button" type="submit">ログアウト</button>
            </form>
        </li>
    </ul>
</nav>
@endsection
