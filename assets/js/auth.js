document.addEventListener("DOMContentLoaded", () => {
    
    const loginForm = document.getElementById("login-form");
    const alertContainer = document.getElementById("alert-container");

    if (loginForm) {
        loginForm.addEventListener("submit", (event) => {
            event.preventDefault(); // Stop the form from submitting normally

            // Get form data
            const credential = document.getElementById("login-credential").value;
            const password = document.getElementById("password").value;
            const loginButton = loginForm.querySelector('button[type="submit"]');

            // Show loading state
            loginButton.disabled = true;
            loginButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging in...';

            // Send data to the PHP API
            fetch('api/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    credential: credential,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert("Login successful! Redirecting...", "success");
                    
                    setTimeout(() => {
                        window.location.href = data.redirect_to || 'dashboard.php';
                    }, 1000);

                } else {
                    // Show error message from the API
                    showAlert(data.error || "Login failed. Please check your credentials.", "danger");
                    loginButton.disabled = false;
                    loginButton.innerHTML = 'Login';
                }
            })
            .catch(error => {
                console.error("Login Error:", error);
                showAlert("An unknown error occurred. Please try again.", "danger");
                loginButton.disabled = false;
                loginButton.innerHTML = 'Login';
            });
        });
    }

    // Helper function to show alerts
    function showAlert(message, type) {
        if (alertContainer) {
            alertContainer.innerHTML = `<div class="alert alert-${type}" role="alert">${message}</div>`;
        }
    }
});