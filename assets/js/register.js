document.addEventListener("DOMContentLoaded", () => {
    
    const registerForm = document.getElementById("register-form");
    const registerBtn = document.getElementById("register-btn");
    const termsCheck = document.getElementById("terms-agree");
    const alertContainer = document.getElementById("alert-container");
    
    // Password specific elements
    const passwordInput = document.getElementById("password");
    const confirmPasswordInput = document.getElementById("confirm-password");

    // Requirements List Items
    const requirements = {
        length: document.getElementById("rule-length"),
        uppercase: document.getElementById("rule-uppercase"),
        lowercase: document.getElementById("rule-lowercase"),
        number: document.getElementById("rule-number"),
        symbol: document.getElementById("rule-symbol")
    };

    // Regex Patterns for validation
    const patterns = {
        uppercase: /[A-Z]/,
        lowercase: /[a-z]/,
        number: /[0-9]/,
        symbol: /[!@#$%^&*(),.?":{}|<>]/ // Defined set of special chars
    };

    // 1. Real-time Password Validation Logic
    if (passwordInput) {
        passwordInput.addEventListener("input", () => {
            const val = passwordInput.value;
            
            // Check Length (>= 8)
            updateRequirement(requirements.length, val.length >= 8);

            // Check Uppercase
            updateRequirement(requirements.uppercase, patterns.uppercase.test(val));

            // Check Lowercase
            updateRequirement(requirements.lowercase, patterns.lowercase.test(val));

            // Check Number
            updateRequirement(requirements.number, patterns.number.test(val));

            // Check Symbol
            updateRequirement(requirements.symbol, patterns.symbol.test(val));
        });
    }

    // Helper to toggle valid/invalid classes and icons
    function updateRequirement(element, isValid) {
        const icon = element.querySelector("i");
        if (isValid) {
            element.classList.remove("invalid");
            element.classList.add("valid");
            icon.classList.remove("bi-x-circle-fill");
            icon.classList.add("bi-check-circle-fill");
        } else {
            element.classList.remove("valid");
            element.classList.add("invalid");
            icon.classList.remove("bi-check-circle-fill");
            icon.classList.add("bi-x-circle-fill");
        }
    }

    // 2. Enable/Disable button based on "Terms" checkbox
    if (termsCheck && registerBtn) {
        registerBtn.disabled = true;
        termsCheck.addEventListener("change", () => {
            registerBtn.disabled = !termsCheck.checked;
        });
    }

    // 3. Handle form submission
    if (registerForm) {
        registerForm.addEventListener("submit", (event) => {
            event.preventDefault(); 

            const username = document.getElementById("username").value;
            const email = document.getElementById("email").value;
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            // --- Final Validation before sending ---

            // Check Match
            if (password !== confirmPassword) {
                showAlert("Passwords do not match.", "danger");
                return;
            }

            // Check Complexity (re-run all tests)
            const isLengthValid = password.length >= 8;
            const isUpperValid = patterns.uppercase.test(password);
            const isLowerValid = patterns.lowercase.test(password);
            const isNumberValid = patterns.number.test(password);
            const isSymbolValid = patterns.symbol.test(password);

            if (!isLengthValid || !isUpperValid || !isLowerValid || !isNumberValid || !isSymbolValid) {
                showAlert("Password does not meet all complexity requirements.", "danger");
                return;
            }

            // Show loading state
            registerBtn.disabled = true;
            registerBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Registering...';

            // Send data to PHP API
            fetch('api/register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username: username,
                    email: email,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert("Registration successful! Redirecting to login...", "success");
                    setTimeout(() => {
                        window.location.href = 'login.php'; 
                    }, 2000);
                } else {
                    showAlert(data.error || "Registration failed.", "danger");
                    registerBtn.disabled = false;
                    registerBtn.innerHTML = 'Register';
                }
            })
            .catch(error => {
                console.error("Registration Error:", error);
                showAlert("An unknown error occurred. Please try again.", "danger");
                registerBtn.disabled = false;
                registerBtn.innerHTML = 'Register';
            });
        });
    }

    function showAlert(message, type) {
        if (alertContainer) {
            alertContainer.innerHTML = `<div class="alert alert-${type}" role="alert">${message}</div>`;
        }
    }
});