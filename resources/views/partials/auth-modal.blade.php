<div class="auth-modal">
    <div class="auth-container">
        <button class="close-modal">
            <i class="fas fa-times"></i>
        </button>

        <div class="auth-tabs">
            <button class="auth-tab active" data-tab="login">Login</button>
            <button class="auth-tab" data-tab="signup">Sign Up</button>
        </div>

        <!-- Login Form -->
        <form class="auth-form active" id="login" method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <a href="#" class="forgot-password">Forgot your password?</a>

            <button type="submit" class="btn btn-primary btn-block">Login</button>

            <div class="auth-divider">
                <span>or</span>
            </div>

            <button type="button" class="btn btn-outline btn-block">
                <i class="fab fa-google"></i> Login with Google
            </button>
        </form>

        <!-- Sign Up Form -->
        <form class="auth-form" id="signup" method="POST" action="{{ route('register') }}">
            @csrf

            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="password_confirmation" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="age" min="10" max="100" required>
                </div>
                <div class="form-group">
                    <label>Weight (kg)</label>
                    <input type="number" name="weight" min="20" max="200" step="0.1" required>
                </div>
                <div class="form-group">
                    <label>Height (cm)</label>
                    <input type="number" name="height" min="100" max="250" step="1" required>
                </div>
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="">Select Role</option>
                    <option value="trainee">Trainee</option>
                    <option value="trainer">Trainer</option>
                </select>
            </div>

            <div class="form-checkbox">
                <input type="checkbox" id="terms" required>
                <label for="terms">
                    I agree to the <a href="#">Terms & Conditions</a> and <a href="#">Privacy Policy</a>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>
    </div>
</div>

