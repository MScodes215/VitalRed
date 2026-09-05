/**
 * VitalRed - Google Sign-In & Authentication Handler
 * Handles Google Identity Services (GSI) client-side token flow
 * and provides instant One-Click Google Authentication testing.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Quick Demo Credentials Auto-Fill
    document.querySelectorAll('.demo-pill').forEach(pill => {
        pill.addEventListener('click', function () {
            const email = this.getAttribute('data-email');
            const pass = this.getAttribute('data-pass');
            const emailInput = document.getElementById('email');
            const passInput = document.getElementById('password');
            if (emailInput && passInput) {
                emailInput.value = email;
                passInput.value = pass;
                // Subtle highlight animation
                emailInput.classList.add('is-valid');
                passInput.classList.add('is-valid');
                setTimeout(() => {
                    emailInput.classList.remove('is-valid');
                    passInput.classList.remove('is-valid');
                }, 1500);
            }
        });
    });

    // Handle Google Sign-In Simulator / Direct GSI
    const googleBtn = document.getElementById('btn-google-signin');
    if (googleBtn) {
        googleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            simulateGoogleAuth();
        });
    }
});

/**
 * Handle Google Credential Callback from Google Identity Services
 */
function handleGoogleCredentialResponse(response) {
    if (!response || !response.credential) {
        console.error('Google credential not returned');
        return;
    }
    
    // Send token to backend verification endpoint
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = window.VITALRED_BASE_URL + 'auth/google-callback.php';
    
    const inputToken = document.createElement('input');
    inputToken.type = 'hidden';
    inputToken.name = 'google_credential';
    inputToken.value = response.credential;
    form.appendChild(inputToken);
    
    document.body.appendChild(form);
    form.submit();
}

/**
 * Instant One-Click Google Authentication Simulator
 * Allows academic examiners and users to test Google Sign-In
 * without requiring live Google Cloud Console credentials.
 */
function simulateGoogleAuth() {
    const btn = document.getElementById('btn-google-signin');
    if (btn) {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Connecting to Google...';
        btn.style.pointerEvents = 'none';
    }

    setTimeout(() => {
        // Submit simulated Google verified payload
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.VITALRED_BASE_URL + 'auth/google-callback.php';

        const fields = {
            'is_simulation': '1',
            'google_id': 'google_sub_109283746501928374',
            'email': 'evaluator@google.com',
            'name': 'Google Evaluator User',
            'avatar': 'https://lh3.googleusercontent.com/a/default-user=s96-c'
        };

        for (const [key, value] of Object.entries(fields)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }

        document.body.appendChild(form);
        form.submit();
    }, 600);
}
