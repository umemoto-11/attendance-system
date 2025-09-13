@extends('layouts.app')

@section('header')
<nav>
    <ul class="header-nav">
        <li class="header-nav__item">
            <a class="header__link header__link--attendance" href="/attendance">勤怠</a>
        </li>
        <li>
            <a class="header__link header__link--attendance-list" href="/attendance/list">勤怠一覧</a>
        </li>
        <li>
            <a class="header__link header__link--request" href="/stamp_correction_request/list">申請</a>
        </li>
        <li>
            <form action="/logout" method="post">
                @csrf
                <button class="header__logout-button" type="submit">ログアウト</button>
            </form>
        </li>
    </ul>
</nav>
@endsection
