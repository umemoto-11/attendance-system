@extends('layouts.app')

@section('header')
<nav>
    <ul class="header-nav">
        <li class="header-nav__item">
            <a class="header__link header__link--attendance-list" href="/admin/attendances">
                勤怠一覧
            </a>
        </li>
        <li>
            <a class="header__link header__link--staff-list" href="/admin/users">
                スタッフ一覧
            </a>
        </li>
        <li>
            <a class="header__link header__link--request" href="/admin/requests">
                申請一覧
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