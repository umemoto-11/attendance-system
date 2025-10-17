@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verify-email.css') }}">
@endsection

@section('content')
<div class="verify__content">
    <p class="verify-message">
        登録していただいたメールアドレスに認証メールを付しました。<br>
        メール認証を完了してください。
    </p>
    @if(app()->environment('local'))
    <div class="verify__button">
        <a class="verify__button-link"
            href="{{ URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => auth()->user()->id, 'hash' => sha1(auth()->user()->email)]
            ) }}">
            認証はこちらから
        </a>
    </div>
    @else
    <p>メールをご確認のうえ、リンクをクリックしてください。</p>
    @endif
    <form id="resend-form" action="{{ route('verification.send') }}" method="post">
        @csrf
        <div class="resend__link">
            <a href="#" onclick="event.preventDefault(); document.getElementById('resend-form').submit();">認証メールを再送する</a>
        </div>
    </form>
    @if (session('message'))
    <div class="verify__success">
        {{ session('message') }}
    </div>
    @endif
</div>
@endsection