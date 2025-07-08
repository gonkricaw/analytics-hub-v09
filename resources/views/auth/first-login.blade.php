@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-auto flex items-center justify-center">
                <div class="text-2xl font-bold text-indigo-600">Analytics Hub</div>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                First Login Setup
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Please complete your account setup
            </p>
        </div>

        <div class="bg-white py-8 px-6 shadow rounded-lg">
            <div id="setup-alerts" class="mb-4"></div>

            <form id="first-login-form" class="space-y-6">
                @csrf

                <!-- Password Change Section -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Change Your Password</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        For security, you must change your password on first login.
                    </p>

                    <div class="space-y-4">
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700">
                                New Password
                            </label>
                            <input id="new_password" name="new_password" type="password" required
                                   class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm">
                            <div class="mt-2 text-sm text-gray-600">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>At least 12 characters long</li>
                                    <li>Contains uppercase and lowercase letters</li>
                                    <li>Contains numbers and special characters</li>
                                    <li>Not similar to your username or email</li>
                                </ul>
                            </div>
                        </div>

                        <div>
                            <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700">
                                Confirm New Password
                            </label>
                            <input id="new_password_confirmation" name="new_password_confirmation" type="password" required
                                   class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm">
                        </div>
                    </div>
                </div>

                <!-- Profile Information Section -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Profile Information</h3>

                    <div class="space-y-4">
                        <div>
                            <label for="full_name" class="block text-sm font-medium text-gray-700">
                                Full Name
                            </label>
                            <input id="full_name" name="full_name" type="text"
                                   value="{{ old('full_name', $user->full_name) }}"
                                   class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm">
                        </div>

                        <div>
                            <label for="timezone" class="block text-sm font-medium text-gray-700">
                                Timezone
                            </label>
                            <select id="timezone" name="timezone"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Select timezone</option>
                                <option value="UTC" {{ old('timezone', $user->timezone) == 'UTC' ? 'selected' : '' }}>UTC</option>
                                <option value="America/New_York" {{ old('timezone', $user->timezone) == 'America/New_York' ? 'selected' : '' }}>Eastern Time</option>
                                <option value="America/Chicago" {{ old('timezone', $user->timezone) == 'America/Chicago' ? 'selected' : '' }}>Central Time</option>
                                <option value="America/Denver" {{ old('timezone', $user->timezone) == 'America/Denver' ? 'selected' : '' }}>Mountain Time</option>
                                <option value="America/Los_Angeles" {{ old('timezone', $user->timezone) == 'America/Los_Angeles' ? 'selected' : '' }}>Pacific Time</option>
                                <option value="Europe/London" {{ old('timezone', $user->timezone) == 'Europe/London' ? 'selected' : '' }}>London</option>
                                <option value="Europe/Berlin" {{ old('timezone', $user->timezone) == 'Europe/Berlin' ? 'selected' : '' }}>Berlin</option>
                                <option value="Asia/Tokyo" {{ old('timezone', $user->timezone) == 'Asia/Tokyo' ? 'selected' : '' }}>Tokyo</option>
                                <option value="Asia/Shanghai" {{ old('timezone', $user->timezone) == 'Asia/Shanghai' ? 'selected' : '' }}>Shanghai</option>
                                <option value="Australia/Sydney" {{ old('timezone', $user->timezone) == 'Australia/Sydney' ? 'selected' : '' }}>Sydney</option>
                            </select>
                        </div>

                        <div>
                            <label for="language" class="block text-sm font-medium text-gray-700">
                                Language
                            </label>
                            <select id="language" name="language"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Select language</option>
                                <option value="en" {{ old('language', $user->language) == 'en' ? 'selected' : '' }}>English</option>
                                <option value="es" {{ old('language', $user->language) == 'es' ? 'selected' : '' }}>Spanish</option>
                                <option value="fr" {{ old('language', $user->language) == 'fr' ? 'selected' : '' }}>French</option>
                                <option value="de" {{ old('language', $user->language) == 'de' ? 'selected' : '' }}>German</option>
                                <option value="it" {{ old('language', $user->language) == 'it' ? 'selected' : '' }}>Italian</option>
                                <option value="pt" {{ old('language', $user->language) == 'pt' ? 'selected' : '' }}>Portuguese</option>
                                <option value="ru" {{ old('language', $user->language) == 'ru' ? 'selected' : '' }}>Russian</option>
                                <option value="ja" {{ old('language', $user->language) == 'ja' ? 'selected' : '' }}>Japanese</option>
                                <option value="ko" {{ old('language', $user->language) == 'ko' ? 'selected' : '' }}>Korean</option>
                                <option value="zh" {{ old('language', $user->language) == 'zh' ? 'selected' : '' }}>Chinese</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Terms and Conditions</h3>

                    <div class="bg-gray-50 rounded-lg p-4 mb-4 max-h-32 overflow-y-auto">
                        <p class="text-sm text-gray-700">
                            By using Analytics Hub, you agree to our Terms of Service and Privacy Policy.
                            You acknowledge that you will use this system responsibly and in accordance with
                            your organization's policies and applicable laws.
                        </p>
                        <p class="text-sm text-gray-700 mt-2">
                            This system may collect and process data for analytics and security purposes.
                            All data is handled in accordance with applicable privacy regulations.
                        </p>
                    </div>

                    <div class="flex items-center">
                        <input id="terms_accepted" name="terms_accepted" type="checkbox" required
                               class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="terms_accepted" class="ml-2 block text-sm text-gray-900">
                            I accept the <a href="#" class="text-indigo-600 hover:text-indigo-500">Terms and Conditions</a>
                            <span class="text-red-500">*</span>
                        </label>
                    </div>
                </div>

                <div>
                    <button id="submit-btn" type="submit"
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="submit-text">Complete Setup</span>
                        <span id="submit-spinner" class="hidden ml-2">
                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('first-login-form');
    const submitBtn = document.getElementById('submit-btn');
    const submitText = document.getElementById('submit-text');
    const submitSpinner = document.getElementById('submit-spinner');
    const alertsContainer = document.getElementById('setup-alerts');

    // Password strength indicator
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('new_password_confirmation');

    // Show alert
    function showAlert(message, type = 'error') {
        const alertClass = type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700';
        const iconClass = type === 'error' ? 'text-red-400' : 'text-green-400';

        alertsContainer.innerHTML = `
            <div class="rounded-md ${alertClass} p-4 border">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 ${iconClass}" viewBox="0 0 20 20" fill="currentColor">
                            ${type === 'error' ?
                                '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />' :
                                '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />'
                            }
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                </div>
            </div>
        `;
    }

    // Password confirmation validation
    function validatePasswordMatch() {
        const password = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        if (confirmPassword && password !== confirmPassword) {
            confirmPasswordInput.setCustomValidity('Passwords do not match');
        } else {
            confirmPasswordInput.setCustomValidity('');
        }
    }

    newPasswordInput.addEventListener('input', validatePasswordMatch);
    confirmPasswordInput.addEventListener('input', validatePasswordMatch);

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Show loading state
        submitBtn.disabled = true;
        submitText.textContent = 'Setting up...';
        submitSpinner.classList.remove('hidden');
        alertsContainer.innerHTML = '';

        // Prepare form data
        const formData = new FormData(form);

        // Submit via AJAX
        fetch('{{ route("auth.first-login.complete") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');

                // Redirect after short delay
                setTimeout(function() {
                    window.location.href = data.redirect;
                }, 1500);
            } else {
                showAlert(data.message || 'Setup failed. Please try again.');

                // Show validation errors
                if (data.errors) {
                    const errorMessages = Object.values(data.errors).flat();
                    showAlert(errorMessages.join('<br>'));
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred. Please try again.');
        })
        .finally(() => {
            // Reset button state
            submitBtn.disabled = false;
            submitText.textContent = 'Complete Setup';
            submitSpinner.classList.add('hidden');
        });
    });
});
</script>
@endsection
