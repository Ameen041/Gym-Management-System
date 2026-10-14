@extends('layouts.app')

@section('title', __('Choose a new password') . ' | ' . __('Ameen Gym'))

@section('content')
<section class="auth-page">
    <div class="auth-container">
        <h1>{{ __('Choose a new password') }}</h1>
        <p class="lead">{{ __('Enter your email and your new password.') }}</p>

        @if($errors->any())
            <div class="alert alert-error">
                <i class="fas fa-circle-exclamation"></i>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="auth-form" method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="form-group">
                <label>{{ __('Email') }}</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required>
            </div>
            <div class="form-group">
                <label>{{ __('New Password') }}</label>
                <input type="password" name="password" required autofocus>
            </div>
            <div class="form-group">
                <label>{{ __('Confirm Password') }}</label>
                <input type="password" name="password_confirmation" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">{{ __('Reset password') }}</button>
        </form>

        <a href="{{ route('home') }}" class="back-link" style="margin-top:16px"><i class="fas fa-arrow-right-long"></i> {{ __('Back to home') }}</a>
    </div>
</section>
@endsection

