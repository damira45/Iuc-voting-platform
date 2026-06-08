// IUC Voting System - Authentication JavaScript
// Client-side validation and user experience enhancements

document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const registerForm = document.querySelector('.auth-form');
    const loginForm = document.querySelector('form[action*="login"]');
    
    if (registerForm) {
        registerForm.addEventListener('submit', validateRegistration);
    }
    
    if (loginForm) {
        loginForm.addEventListener('submit', validateLogin);
    }
    
    // Password strength indicator
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', checkPasswordStrength);
    }
    
    // Confirm password validation
    const confirmPasswordInput = document.getElementById('confirm_password');
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', validatePasswordMatch);
    }
});

function validateRegistration(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const email = document.getElementById('email').value;
    const studentId = document.getElementById('student_id').value;
    
    // Password validation
    if (password.length < 8) {
        showError('Password must be at least 8 characters long');
        e.preventDefault();
        return false;
    }
    
    if (password !== confirmPassword) {
        showError('Passwords do not match');
        e.preventDefault();
        return false;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showError('Please enter a valid email address');
        e.preventDefault();
        return false;
    }
    
    // Student ID validation
    if (studentId.length < 3) {
        showError('Student ID must be at least 3 characters long');
        e.preventDefault();
        return false;
    }
    
    return true;
}

function validateLogin(e) {
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    if (!email || !password) {
        showError('Please enter both email and password');
        e.preventDefault();
        return false;
    }
    
    return true;
}

function checkPasswordStrength() {
    const password = this.value;
    const strengthIndicator = document.getElementById('password-strength');
    
    if (!strengthIndicator) {
        const indicator = document.createElement('div');
        indicator.id = 'password-strength';
        indicator.className = 'password-strength';
        this.parentNode.appendChild(indicator);
    }
    
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    const strengthText = ['Weak', 'Fair', 'Good', 'Strong'][strength];
    const strengthClass = ['weak', 'fair', 'good', 'strong'][strength];
    
    const indicator = document.getElementById('password-strength');
    indicator.textContent = `Password strength: ${strengthText}`;
    indicator.className = `password-strength ${strengthClass}`;
}

function validatePasswordMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    const matchIndicator = document.getElementById('password-match');
    
    if (!matchIndicator) {
        const indicator = document.createElement('div');
        indicator.id = 'password-match';
        indicator.className = 'password-match';
        this.parentNode.appendChild(indicator);
    }
    
    const indicator = document.getElementById('password-match');
    
    if (confirmPassword && password !== confirmPassword) {
        indicator.textContent = 'Passwords do not match';
        indicator.className = 'password-match error';
    } else if (confirmPassword && password === confirmPassword) {
        indicator.textContent = 'Passwords match';
        indicator.className = 'password-match success';
    } else {
        indicator.textContent = '';
    }
}

function showError(message) {
    // Remove existing error messages
    const existingErrors = document.querySelectorAll('.error-message');
    existingErrors.forEach(error => error.remove());
    
    // Create and show new error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    errorDiv.style.cssText = `
        color: #dc3545;
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        padding: 10px;
        margin: 10px 0;
        border-radius: 4px;
    `;
    
    const form = document.querySelector('form');
    form.insertBefore(errorDiv, form.firstChild);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        errorDiv.remove();
    }, 5000);
}

// Show success message
function showSuccess(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.textContent = message;
    successDiv.style.cssText = `
        color: #155724;
        background-color: #d4edda;
        border: 1px solid #c3e6cb;
        padding: 10px;
        margin: 10px 0;
        border-radius: 4px;
    `;
    
    const form = document.querySelector('form');
    form.insertBefore(successDiv, form.firstChild);
    
    setTimeout(() => {
        successDiv.remove();
    }, 5000);
}
