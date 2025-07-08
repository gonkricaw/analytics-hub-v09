@extends('layouts.app')

@section('title', 'Terms and Conditions')

@section('content')
<div class="terms-container">
    <div class="terms-modal">
        <div class="terms-header">
            <h1>Analytics Hub</h1>
            <p>Terms and Conditions</p>
            <div class="terms-version">Version {{ $currentTermsVersion }}</div>
        </div>

        <div class="terms-content-wrapper">
            <div id="terms-content" class="terms-content">
                <!-- Terms content will be loaded here -->
                <div class="loading-spinner">
                    <svg class="animate-spin" width="40" height="40" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" stroke-dasharray="32" stroke-linecap="round"></circle>
                    </svg>
                    <p>Loading terms and conditions...</p>
                </div>
            </div>
        </div>

        <div class="terms-actions">
            <div class="acceptance-notice">
                <p><strong>Important:</strong> You must accept these terms and conditions to continue using Analytics Hub.</p>
            </div>

            <form id="terms-form" method="POST" action="{{ route('terms.accept') }}">
                @csrf
                <input type="hidden" name="terms_version" value="{{ $currentTermsVersion }}">

                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="accept_terms" name="accept_terms" value="1" required>
                        <span class="checkbox-custom"></span>
                        <span class="checkbox-text">
                            I have read and agree to the Terms and Conditions
                        </span>
                    </label>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn-accept" id="accept-btn" disabled>
                        <span class="btn-text">Accept and Continue</span>
                        <span class="btn-loading" style="display: none;">
                            <svg class="animate-spin" width="20" height="20" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" stroke-dasharray="32" stroke-linecap="round"></circle>
                            </svg>
                            Processing...
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Message Container -->
        <div id="message-container" style="display: none;">
            <div id="error-message" class="error-message" style="display: none;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                </svg>
                <span id="error-text"></span>
            </div>
        </div>
    </div>
</div>

<style>
    body {
        background: #0E0E44;
        font-family: 'Inter', sans-serif;
        margin: 0;
        padding: 0;
        overflow: hidden;
    }

    .terms-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(14, 14, 68, 0.95);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 20px;
        box-sizing: border-box;
    }

    .terms-modal {
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        width: 100%;
        max-width: 800px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .terms-header {
        padding: 2rem;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .terms-header h1 {
        color: #FFFFFF;
        font-size: 2rem;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .terms-header p {
        color: #B0B0B0;
        font-size: 1.1rem;
        margin: 0 0 0.5rem 0;
    }

    .terms-version {
        background: rgba(255, 122, 0, 0.2);
        color: #FF7A00;
        padding: 0.25rem 1rem;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 500;
        display: inline-block;
    }

    .terms-content-wrapper {
        flex: 1;
        overflow: hidden;
        position: relative;
    }

    .terms-content {
        height: 100%;
        overflow-y: auto;
        padding: 2rem;
        color: #FFFFFF;
        line-height: 1.6;
    }

    .terms-content h2 {
        color: #FF7A00;
        font-size: 1.5rem;
        margin-bottom: 1rem;
        text-align: center;
    }

    .terms-content h3 {
        color: #FFFFFF;
        font-size: 1.2rem;
        margin: 1.5rem 0 0.5rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 0.5rem;
    }

    .terms-content p {
        margin-bottom: 1rem;
        color: #B0B0B0;
    }

    .terms-content ul {
        margin: 0.5rem 0 1rem 1.5rem;
        color: #B0B0B0;
    }

    .terms-content li {
        margin-bottom: 0.5rem;
    }

    .loading-spinner {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 200px;
        color: #B0B0B0;
    }

    .loading-spinner svg {
        margin-bottom: 1rem;
        color: #FF7A00;
    }

    .terms-actions {
        padding: 2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.02);
    }

    .acceptance-notice {
        background: rgba(255, 193, 7, 0.1);
        border: 1px solid rgba(255, 193, 7, 0.3);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        text-align: center;
    }

    .acceptance-notice p {
        margin: 0;
        color: #FFC107;
        font-weight: 500;
    }

    .checkbox-group {
        margin-bottom: 1.5rem;
    }

    .checkbox-label {
        display: flex;
        align-items: flex-start;
        cursor: pointer;
        gap: 0.75rem;
    }

    .checkbox-label input[type="checkbox"] {
        display: none;
    }

    .checkbox-custom {
        width: 20px;
        height: 20px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 4px;
        background: rgba(255, 255, 255, 0.1);
        position: relative;
        transition: all 0.3s ease;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .checkbox-label input[type="checkbox"]:checked + .checkbox-custom {
        background: #FF7A00;
        border-color: #FF7A00;
    }

    .checkbox-label input[type="checkbox"]:checked + .checkbox-custom::after {
        content: "✓";
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        font-weight: bold;
        font-size: 14px;
    }

    .checkbox-text {
        color: #FFFFFF;
        font-weight: 500;
        line-height: 1.5;
    }

    .button-group {
        display: flex;
        justify-content: center;
    }

    .btn-accept {
        background: linear-gradient(135deg, #FF7A00 0%, #FF6B00 100%);
        border: none;
        border-radius: 8px;
        color: #FFFFFF;
        font-weight: 600;
        font-size: 1rem;
        padding: 0.875rem 2rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 200px;
        justify-content: center;
    }

    .btn-accept:enabled:hover {
        background: linear-gradient(135deg, #FF6B00 0%, #FF5A00 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(255, 122, 0, 0.3);
    }

    .btn-accept:disabled {
        background: #666;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .error-message {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        margin-top: 1rem;
        background: rgba(244, 67, 54, 0.1);
        border: 1px solid rgba(244, 67, 54, 0.3);
        color: #F44336;
    }

    .animate-spin {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Custom scrollbar */
    .terms-content::-webkit-scrollbar {
        width: 8px;
    }

    .terms-content::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
    }

    .terms-content::-webkit-scrollbar-thumb {
        background: rgba(255, 122, 0, 0.5);
        border-radius: 4px;
    }

    .terms-content::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 122, 0, 0.7);
    }

    @media (max-width: 768px) {
        .terms-modal {
            margin: 10px;
            max-height: calc(100vh - 20px);
        }

        .terms-header,
        .terms-content,
        .terms-actions {
            padding: 1.5rem;
        }

        .terms-header h1 {
            font-size: 1.5rem;
        }

        .btn-accept {
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        .terms-header,
        .terms-content,
        .terms-actions {
            padding: 1rem;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('terms-form');
    const acceptBtn = document.getElementById('accept-btn');
    const acceptCheckbox = document.getElementById('accept_terms');
    const btnText = document.querySelector('.btn-text');
    const btnLoading = document.querySelector('.btn-loading');
    const messageContainer = document.getElementById('message-container');
    const errorMessage = document.getElementById('error-message');
    const errorText = document.getElementById('error-text');
    const termsContent = document.getElementById('terms-content');

    // Load terms content
    loadTermsContent();

    // Enable/disable accept button based on checkbox
    acceptCheckbox.addEventListener('change', function() {
        acceptBtn.disabled = !this.checked;
    });

    // Handle form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!acceptCheckbox.checked) {
            showError('You must accept the terms and conditions to continue.');
            return;
        }

        // Show loading state
        acceptBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'flex';

        // Clear previous messages
        hideError();

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
                // Redirect to dashboard or intended page
                window.location.href = data.redirect || '/dashboard';
            } else {
                showError(data.message || 'An error occurred while processing your request.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('An unexpected error occurred. Please try again.');
        })
        .finally(() => {
            // Reset button state
            acceptBtn.disabled = !acceptCheckbox.checked;
            btnText.style.display = 'block';
            btnLoading.style.display = 'none';
        });
    });

    function loadTermsContent() {
        fetch('/terms/content', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                termsContent.innerHTML = data.content;
            } else {
                termsContent.innerHTML = '<p class="error">Failed to load terms and conditions. Please refresh the page and try again.</p>';
            }
        })
        .catch(error => {
            console.error('Error loading terms:', error);
            termsContent.innerHTML = '<p class="error">Failed to load terms and conditions. Please refresh the page and try again.</p>';
        });
    }

    function showError(message) {
        errorText.textContent = message;
        errorMessage.style.display = 'flex';
        messageContainer.style.display = 'block';
    }

    function hideError() {
        errorMessage.style.display = 'none';
        messageContainer.style.display = 'none';
    }

    // Prevent page navigation/refresh
    window.addEventListener('beforeunload', function(e) {
        if (!acceptCheckbox.checked) {
            e.preventDefault();
            e.returnValue = 'You must accept the terms and conditions to continue.';
        }
    });

    // Disable browser back button
    history.pushState(null, null, location.href);
    window.addEventListener('popstate', function() {
        history.pushState(null, null, location.href);
    });
});
</script>
@endsection
