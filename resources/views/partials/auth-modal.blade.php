@php
  // Which tab should open by itself after a failed submit
  $openAuthTab = $errors->register->any() ? 'signup'
      : (($errors->any() || session('error') || session('status')) ? 'login' : null);
@endphp
<div class="auth-modal">
    <div class="auth-container">
        <button class="close-modal">
            <i class="fas fa-times"></i>
        </button>

        <div class="auth-tabs">
            <button class="auth-tab active" data-tab="login">{{ __('Login') }}</button>
            <button class="auth-tab" data-tab="signup">{{ __('Sign Up') }}</button>
        </div>

        <!-- Login Form -->
        <form class="auth-form active" id="login" method="POST" action="{{ route('login') }}">
            @csrf
            @if(session('error') || $errors->any())
                <div class="auth-alert">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ session('error') ?: $errors->first() }}
                </div>
            @endif
            @if(session('status'))
                <div class="auth-alert auth-alert-ok">
                    <i class="fas fa-check-circle"></i> {{ session('status') }}
                </div>
            @endif

            <div class="form-group">
                <label>{{ __('Email') }}</label>
                <input type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div class="form-group">
                <label>{{ __('Password') }}</label>
                <input type="password" name="password" required>
            </div>
            <a href="{{ route('password.request') }}" class="forgot-password">{{ __('Forgot your password?') }}</a>
            <button type="submit" class="btn btn-primary btn-block">{{ __('Login') }}</button>
        </form>

        <!-- Sign Up Form -->
        <form class="auth-form" id="signup" method="POST" action="{{ route('register') }}">
            @csrf
            @if($errors->register->any())
                <div class="auth-alert">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul>
                        @foreach($errors->register->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="form-group">
                <label>{{ __('Role') }}</label>
                <select name="role" id="signupRole" required>
                    <option value="">{{ __('Select Role') }}</option>
                    <option value="trainee" @selected(old('role') === 'trainee')>{{ __('Trainee') }}</option>
                    <option value="trainer" @selected(old('role') === 'trainer')>{{ __('Trainer') }}</option>
                </select>
            </div>

            <div class="form-group">
                <label>{{ __('Full Name') }}</label>
                <input type="text" name="name" value="{{ old('name') }}" required>
            </div>

            <div class="form-group">
                <label>{{ __('Email') }}</label>
                <input type="email" name="email" value="{{ old('email') }}" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>{{ __('Password') }}</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>{{ __('Confirm Password') }}</label>
                    <input type="password" name="password_confirmation" required>
                </div>
            </div>

            {{-- Body measurements are only asked from trainees --}}
            <div class="form-row trainee-only">
                <div class="form-group">
                    <label>{{ __('Age') }}</label>
                    <input type="number" name="age" min="10" max="100" value="{{ old('age') }}">
                </div>
                <div class="form-group">
                    <label>{{ __('Weight (kg)') }}</label>
                    <input type="number" name="weight" min="20" max="200" step="0.1" value="{{ old('weight') }}">
                </div>
                <div class="form-group">
                    <label>{{ __('Height (cm)') }}</label>
                    <input type="number" name="height" min="100" max="250" step="1" value="{{ old('height') }}">
                </div>
            </div>

            <div class="form-checkbox">
                <input type="checkbox" id="terms" required>
                <label for="terms">
                    {{ __('I agree to the') }} <a href="#">{{ __('Terms & Conditions') }}</a> {{ __('and') }} <a href="#">{{ __('Privacy Policy') }}</a>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">{{ __('Create Account') }}</button>
        </form>
    </div>
</div>

<style>
  .auth-alert{background:rgba(231,29,54,.12);border:1px solid rgba(231,29,54,.4);color:#ffb3bd;padding:12px 14px;border-radius:8px;margin-bottom:18px;font-size:.92rem;line-height:1.6}
  .auth-alert ul{margin:6px 0 0;padding-inline-start:18px}
  .auth-alert-ok{background:rgba(46,196,182,.12);border-color:rgba(46,196,182,.4);color:#9ff0e6}
</style>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Show age / weight / height only for trainees
    var role = document.getElementById('signupRole');
    var traineeFields = document.querySelector('#signup .trainee-only');
    function syncRoleFields() {
      var isTrainee = role.value === 'trainee';
      traineeFields.style.display = isTrainee ? '' : 'none';
      traineeFields.querySelectorAll('input').forEach(function (input) { input.required = isTrainee; });
    }
    if (role && traineeFields) {
      role.addEventListener('change', syncRoleFields);
      syncRoleFields();
    }

    // Re-open the modal on the right tab after a failed login / sign up
    var openTab = @json($openAuthTab);
    if (openTab === 'login' && typeof showLoginModal === 'function') showLoginModal();
    if (openTab === 'signup' && typeof showSignupModal === 'function') showSignupModal();
  });
</script>
