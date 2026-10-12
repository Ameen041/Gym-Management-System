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

            <div class="form-group">
                <label>{{ __('Email') }}</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>{{ __('Password') }}</label>
                <input type="password" name="password" required>
            </div>

            <a href="#" class="forgot-password">{{ __('Forgot your password?') }}</a>

            <button type="submit" class="btn btn-primary btn-block">{{ __('Login') }}</button>

            <div class="auth-divider">
                <span>{{ __('or') }}</span>
            </div>

            <button type="button" class="btn btn-outline btn-block">
                <i class="fab fa-google"></i> {{ __('Login with Google') }}
            </button>
        </form>

        <!-- Sign Up Form -->
        <form class="auth-form" id="signup" method="POST" action="{{ route('register') }}">
            @csrf

            <div class="form-row">
                <div class="form-group">
                    <label>{{ __('Full Name') }}</label>
                    <input type="text" name="name" required>
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('Email') }}</label>
                <input type="email" name="email" required>
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

            <div class="form-row">
                <div class="form-group">
                    <label>{{ __('Age') }}</label>
                    <input type="number" name="age" min="10" max="100" required>
                </div>
                <div class="form-group">
                    <label>{{ __('Weight (kg)') }}</label>
                    <input type="number" name="weight" min="20" max="200" step="0.1" required>
                </div>
                <div class="form-group">
                    <label>{{ __('Height (cm)') }}</label>
                    <input type="number" name="height" min="100" max="250" step="1" required>
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('Role') }}</label>
                <select name="role" required>
                    <option value="">{{ __('Select Role') }}</option>
                    <option value="trainee">{{ __('Trainee') }}</option>
                    <option value="trainer">{{ __('Trainer') }}</option>
                </select>
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

