@extends('layouts.app')

@section('title', __('Forgot your password?') . ' | ' . __('Ameen Gym'))

@section('custom_css')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    @include('auth._card')
@endsection

@section('content')
<section class="auth-page">
    <div class="auth-container">
        <h2>{{ __('Forgot your password?') }}</h2>
        <p class="lead">{{ __('Enter your email and we will send you a link to choose a new password.') }}</p>

        @if(session('reset_sent'))
            <div class="auth-alert auth-alert-ok"><i class="fas fa-check-circle"></i> {{ session('reset_sent') }}</div>
        @endif
        @if($errors->any())
            <div class="auth-alert"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
        @endif

        <form class="auth-form" method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="form-group">
                <label>{{ __('Email') }}</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary btn-block">{{ __('Send reset link') }}</button>
        </form>

        <a href="{{ route('home') }}" class="back-link"><i class="fas fa-arrow-left"></i> {{ __('Back to home') }}</a>
    </div>
</section>
@endsection

@section('custom_js')
<script src="{{ asset('js/home.js') }}"></script>
@endsection
