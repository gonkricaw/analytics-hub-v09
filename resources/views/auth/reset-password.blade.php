@extends('layouts.app')

@section('title', 'Reset Password')

@section('content')
<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <h1>Analytics Hub</h1>
            <p>Set New Password</p>
        </div>

        <form id="reset-password-form" method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="form-group">
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required
                       class="form-input"
                       placeholder="Enter new password">
                <div class="error-message" id="password-error"></div>
                <div class="password-strength" id="password-strength" style="display: none;">
                    <div class="strength-bar">
                        <div class="strength-fill" id="strength-fill"></div>
                    </div>
                    <div class="strength-text" id="strength-text"></div>
                </div>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Confirm New Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       class="form-input"
                       placeholder="Confirm new password">
                <div class="error-message" id="password_confirmation-error"></div>
            </div>

            <button type="submit" class="btn-primary" id="submit-btn">
                <span class="btn-text">Update Password</span>
                <span class="btn-loading" style="display: none;">
                    <svg class="animate-spin" width="20" height="20" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" stroke-dasharray="32" stroke-linecap="round"></circle>
                    </svg>
                    Updating...
                </span>
            </button>
        </form>

        <div class="login-footer">
            <p>Remember your password? <a href="{{ route('login') }}">Back to Login</a></p>
        </div>

        <!-- Success/Error Messages -->
        <div id="message-container" style="display: none;">
            <div id="success-message" class="success-message" style="display: none;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22,4 12,14.01 9,11.01"></polyline>
                </svg>
                <span id="success-text"></span>
            </div>

            <div id="error-message" class="error-message" style="display: none;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
                <span id="error-text"></span>
            </div>
        </div>

        <!-- Password Requirements -->
        <div class="security-info">
            <h3>🔒 Password Requirements</h3>
            <ul>
                <li>At least 8 characters long</li>
                <li>Contains uppercase and lowercase letters</li>
                <li>Contains at least one number</li>
                <li>Contains at least one special character</li>
                <li>Not one of your last 5 passwords</li>
            </ul>
        </div>
    </div>
</div>

<style>
    .login-container {
        background: linear-gradient(135deg, rgba(255, 122, 0, 0.1) 0%, rgba(14, 14, 68, 0.9) 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .login-card {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 2.5rem;
        width: 100%;
        max-width: 400px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
    }

    .login-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .login-header h1 {
        color: #FFFFFF;
        font-size: 2rem;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .login-header p {
        color: #B0B0B0;
        font-size: 1rem;
        margin: 0;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        color: #FFFFFF;
        font-weight: 500;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .form-input {
        width: 100%;
        padding: 0.875rem 1rem;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        color: #FFFFFF;
        font-size: 1rem;
        transition: all 0.3s ease;
        box-sizing: border-box;
    }

    .form-input:focus {
        outline: none;
        border-color: #FF7A00;
        box-shadow: 0 0 0 3px rgba(255, 122, 0, 0.1);
        background: rgba(255, 255, 255, 0.15);
    }

    .form-input::placeholder {
        color: #B0B0B0;
    }

    .password-strength {
        margin-top: 0.5rem;
    }

    .strength-bar {
        width: 100%;
        height: 4px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 2px;
        overflow: hidden;
        margin-bottom: 0.5rem;
    }

    .strength-fill {
        height: 100%;
        transition: width 0.3s ease, background-color 0.3s ease;
        border-radius: 2px;
    }

    .strength-text {
        font-size: 0.8rem;
        color: #B0B0B0;
    }

    .btn-primary {
        width: 100%;
        padding: 0.875rem 1.5rem;
        background: linear-gradient(135deg, #FF7A00 0%, #FF6B00 100%);
        border: none;
        border-radius: 8px;
        color: #FFFFFF;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #FF6B00 0%, #FF5A00 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255, 122, 0, 0.3);
    }

    .btn-primary:active {
        transform: translateY(0);
    }

    .btn-primary:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none;
    }

    .login-footer {
        text-align: center;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .login-footer p {
        color: #B0B0B0;
        font-size: 0.9rem;
        margin: 0;
    }

    .login-footer a {
        color: #FF7A00;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .login-footer a:hover {
        color: #FF6B00;
        text-decoration: underline;
    }

    .success-message, .error-message {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }

    .success-message {
        background: rgba(76, 175, 80, 0.1);
        border: 1px solid rgba(76, 175, 80, 0.3);
        color: #4CAF50;
    }

    .error-message {
        background: rgba(244, 67, 54, 0.1);
        border: 1px solid rgba(244, 67, 54, 0.3);
        color: #F44336;
    }

    .security-info {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1.5rem;
    }

    .security-info h3 {
        color: #FF7A00;
        font-size: 0.9rem;
        margin: 0 0 0.5rem 0;
    }

    .security-info ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .security-info li {
        color: #B0B0B0;
        font-size: 0.8rem;
        margin-bottom: 0.25rem;
        padding-left: 1rem;
        position: relative;
    }

    .security-info li:before {
        content: "•";
        color: #FF7A00;
        position: absolute;
        left: 0;
    }

    .animate-spin {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    @media (max-width: 480px) {
        .login-card {
            padding: 2rem;
            margin: 10px;
        }

        .login-header h1 {
            font-size: 1.75rem;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reset-password-form');
    const submitBtn = document.getElementById('submit-btn');
    const btnText = document.querySelector('.btn-text');
    const btnLoading = document.querySelector('.btn-loading');
    const messageContainer = document.getElementById('message-container');
    const successMessage = document.getElementById('success-message');
    const errorMessage = document.getElementById('error-message');
    const successText = document.getElementById('success-text');
    const errorText = document.getElementById('error-text');
    const passwordInput = document.getElementById('password');
    const passwordStrength = document.getElementById('password-strength');
    const strengthFill = document.getElementById('strength-fill');
    const strengthText = document.getElementById('strength-text');

    // Password strength checker
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        if (password.length === 0) {
            passwordStrength.style.display = 'none';
            return;
        }

        passwordStrength.style.display = 'block';

        let score = 0;
        let feedback = [];

        // Length check
        if (password.length >= 8) score += 20;
        else feedback.push('At least 8 characters');

        // Uppercase check
        if (/[A-Z]/.test(password)) score += 20;
        else feedback.push('Uppercase letter');

        // Lowercase check
        if (/[a-z]/.test(password)) score += 20;
        else feedback.push('Lowercase letter');

        // Number check
        if (/[0-9]/.test(password)) score += 20;
        else feedback.push('Number');

        // Special character check
        if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) score += 20;
        else feedback.push('Special character');

        // Update strength bar
        strengthFill.style.width = score + '%';

        let strengthLevel = 'Weak';
        let strengthColor = '#F44336';

        if (score >= 80) {
            strengthLevel = 'Strong';
            strengthColor = '#4CAF50';
        } else if (score >= 60) {
            strengthLevel = 'Good';
            strengthColor = '#FFC107';
        } else if (score >= 40) {
            strengthLevel = 'Fair';
            strengthColor = '#FF9800';
        }

        strengthFill.style.backgroundColor = strengthColor;
        strengthText.textContent = strengthLevel + (feedback.length > 0 ? ' - Missing: ' + feedback.join(', ') : '');
        strengthText.style.color = strengthColor;
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Clear previous messages
        messageContainer.style.display = 'none';
        successMessage.style.display = 'none';
        errorMessage.style.display = 'none';
        document.querySelectorAll('.error-message').forEach(el => el.textContent = '');

        // Show loading state
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'flex';

        // Get form data
        const formData = new FormData(form);

        // Send request
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                successText.textContent = data.message;
                successMessage.style.display = 'flex';
                messageContainer.style.display = 'block';

                // Redirect after 2 seconds
                setTimeout(() => {
                    window.location.href = data.redirect || '/login';
                }, 2000);
            } else {
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const errorElement = document.getElementById(field + '-error');
                        if (errorElement) {
                            const errors = Array.isArray(data.errors[field]) ? data.errors[field] : [data.errors[field]];
                            errorElement.textContent = errors.join(', ');
                        }
                    });
                } else {
                    errorText.textContent = data.message || 'An error occurred. Please try again.';
                    errorMessage.style.display = 'flex';
                    messageContainer.style.display = 'block';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorText.textContent = 'An unexpected error occurred. Please try again.';
            errorMessage.style.display = 'flex';
            messageContainer.style.display = 'block';
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            btnText.style.display = 'block';
            btnLoading.style.display = 'none';
        });
    });
});
</script>
@endsection
