@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-auto flex items-center justify-center">
                <div class="text-2xl font-bold text-indigo-600">Analytics Hub</div>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Password Expired
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Your password has expired. Please create a new one.
            </p>
        </div>

        <div class="bg-white py-8 px-6 shadow rounded-lg">
            <div id="password-alerts" class="mb-4"></div>

            <form id="password-expired-form" class="space-y-6">
                @csrf

                <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Your password has expired for security reasons. Please create a new secure password to continue.
                            </p>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700">
                        Current Password
                    </label>
                    <input id="current_password" name="current_password" type="password" required
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm">
                </div>

                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700">
                        New Password
                    </label>
                    <input id="new_password" name="new_password" type="password" required
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm">
                    <div class="mt-2 text-sm text-gray-600">
                        <p class="font-medium">Password requirements:</p>
                        <ul class="list-disc list-inside space-y-1 mt-1">
                            <li>At least 12 characters long</li>
                            <li>Contains uppercase and lowercase letters</li>
                            <li>Contains numbers and special characters</li>
                            <li>Not similar to your username or email</li>
                            <li>Not reused from recent passwords</li>
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

                <div>
                    <button id="submit-btn" type="submit"
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="submit-text">Change Password</span>
                        <span id="submit-spinner" class="hidden ml-2">
                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                </div>

                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        Need help? Contact your system administrator.
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('password-expired-form');
    const submitBtn = document.getElementById('submit-btn');
    const submitText = document.getElementById('submit-text');
    const submitSpinner = document.getElementById('submit-spinner');
    const alertsContainer = document.getElementById('password-alerts');

    // Password inputs
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
        submitText.textContent = 'Changing Password...';
        submitSpinner.classList.remove('hidden');
        alertsContainer.innerHTML = '';

        // Prepare form data
        const formData = new FormData(form);

        // Submit via AJAX
        fetch('{{ route("auth.password-expired.change") }}', {
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
                showAlert(data.message || 'Password change failed. Please try again.');

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
            submitText.textContent = 'Change Password';
            submitSpinner.classList.add('hidden');
        });
    });
});
</script>
@endsection
