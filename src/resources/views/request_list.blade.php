@extends('layouts.auth')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request_list.css') }}">
@endsection

@section('content')
<div class="request-list__content">
    <div class="request-list__heading">
        <h1>申請一覧</h1>
    </div>
    <div class="tabs">
        <a class="{{ $tab === 'pending' ? 'active' : '' }}" href="{{ route('requests.index', ['tab' => 'pending']) }}">承認待ち</a>
        <a class="{{ $tab === 'approved' ? 'active' : '' }}" href="{{ route('requests.index', ['tab' => 'approved']) }}">承認済み</a>
    </div>
    <div class="request-list-table">
        <table class="request-list-table__inner">
            <tr class="request-list-table__row">
                <th class="request-list-table__header">状態</th>
                <th class="request-list-table__header">名前</th>
                <th class="request-list-table__header">対象日時</th>
                <th class="request-list-table__header">申請理由</th>
                <th class="request-list-table__header">申請日時</th>
                <th class="request-list-table__header">詳細</th>
            </tr>
            @foreach ($requests as $request)
            <tr class="request-list-table__row">
                <td class="request-list-table__item">{{ $request->status === 'pending' ? '承認待ち' : '承認済み' }}</td>
                <td class="request-list-table__item">{{ $request->user->name }}</td>
                <td class="request-list-table__item">{{ \Carbon\Carbon::parse($request->date)->format('Y/m/d') }}</td>
                <td class="request-list-table__item">{{ $request->corrected_reason }}</td>
                <td class="request-list-table__item">{{ \Carbon\Carbon::parse($request->corrected_date)->format('Y/m/d') }}</td>
                <td class="request-list-table__item">
                    <a href="{{ route('attendance.show', $request->id) }}">詳細</a>
                </td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection