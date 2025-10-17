@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admins/staff_list.css') }}">
@endsection

@section('content')
<div class="staff-list__content">
    <div class="staff-list__heading">
        <h1>スタッフ一覧</h1>
    </div>
    <div class="staff-list-table">
        <table class="staff-list__inner">
            <tr class="staff-list__row">
                <th class="staff-list__header">名前</th>
                <th class="staff-list__header">メールアドレス</th>
                <th class="staff-list__header">月次勤怠</th>
            </tr>
            @foreach ($staffs as $staff)
            <tr class="staff-list__row">
                <td class="staff-list__item">
                    {{ $staff->name }}
                </td>
                <td class="staff-list__item">
                    {{ $staff->email }}
                </td>
                <td class="staff-list__item">
                    <a href="{{ route('admin.staffs.attendances', $staff) }}">詳細</a>
                </td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection