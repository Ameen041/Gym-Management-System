@extends('layouts.app')

@section('title', __('Forgot your password?') . ' | ' . __('Ameen Gym'))

@section('content')
<section class="auth-page">
    <div class="auth-container">
        <h1>{{ __('Forgot your password?') }}</h1>
        <p class="lead">{{ __('Enter your email and we will send you a link to choose a new password.') }}</p>

        @if(session('reset_sent'))
            <div class="alert alert-success"><i class="fas fa-circle-check"></i><div>{{ session('reset_sent') }}</div></div>
        @endif
        @if($errors->any())
            <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><div>{{ $errors->first() }}</div></div>
        @endif

        <form class="auth-form" method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="form-group">
                <label>{{ __('Email') }}</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <button type="submit" class="btn btn-primary btn-block">{{ __('Send reset link') }}</button>
        </form>

        <a href="{{ route('home') }}" class="back-link" style="margin-top:16px"><i class="fas fa-arrow-right-long"></i> {{ __('Back to home') }}</a>
    </div>
</section>
@endsection

