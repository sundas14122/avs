document.addEventListener("DOMContentLoaded", () => {

    // --- Common Elements ---
    const alertContainer = document.getElementById("alert-container");

    // ==========================================
    // 1. THEME SELECTOR LOGIC
    // ==========================================
    const themeSelector = document.getElementById("theme-selector");

    if (themeSelector) {
        themeSelector.addEventListener("change", (event) => {
            const newTheme = event.target.value;
            // a) Instantly update the page theme
            document.documentElement.setAttribute('data-bs-theme', newTheme);
            // b) Save the theme to the database
            saveThemePreference(newTheme);
        });
    }

    async function saveThemePreference(theme) {
        try {
            const response = await fetch('api/update_theme.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ theme: theme })
            });
            const result = await response.json();
            if (result.success) {
                showAlert('Theme updated!', 'success');
            } else {
                showAlert(result.error || 'Failed to save theme.', 'danger');
            }
        } catch (error) {
            console.error('Error saving theme:', error);
            showAlert('A network error occurred while saving theme.', 'danger');
        }
    }

    // ==========================================
    // 2. ACCOUNT SETTINGS LOGIC
    // ==========================================
    const emailForm = document.getElementById("email-form");
    const passwordForm = document.getElementById("password-form");

    // --- Email Form Handler ---
    if (emailForm) {
        emailForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const email = document.getElementById("email").value;
            const password = document.getElementById("email-current-password").value;
            const button = emailForm.querySelector('button[type="submit"]');

            if (!email || !password) {
                showAlert('Please enter your new email and your current password.', 'danger');
                return;
            }
            setLoading(button, 'Updating...');

            try {
                const response = await fetch('api/update_account_settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'update_email', email: email, password: password })
                });
                const result = await response.json();

                if (result.success) {
                    showAlert('Email updated successfully!', 'success');
                    document.getElementById("email-current-password").value = ''; // Clear password field
                } else {
                    showAlert(result.error || 'Failed to update email.', 'danger');
                }
            } catch (error) {
                showAlert('A network error occurred.', 'danger');
            }
            resetLoading(button, 'Update Email');
        });
    }

    // --- Password Form Handler ---
    if (passwordForm) {
        passwordForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const currentPassword = document.getElementById("current-password").value;
            const newPassword = document.getElementById("new-password").value;
            const confirmPassword = document.getElementById("confirm-password").value;
            const button = passwordForm.querySelector('button[type="submit"]');

            if (newPassword !== confirmPassword) {
                showAlert('New passwords do not match.', 'danger');
                return;
            }
            if (newPassword.length < 4) {
                showAlert('New password must be at least 4 characters.', 'danger');
                return;
            }
            setLoading(button, 'Changing...');

            try {
                const response = await fetch('api/update_account_settings.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'update_password', currentPassword: currentPassword, newPassword: newPassword })
                });
                const result = await response.json();

                if (result.success) {
                    showAlert('Password changed successfully! Please log in again.', 'success');
                    setTimeout(() => {
                        window.location.href = 'api/logout.php'; // Force re-login
                    }, 2000);
                } else {
                    showAlert(result.error || 'Failed to change password.', 'danger');
                }
            } catch (error) {
                showAlert('A network error occurred.', 'danger');
            }
            resetLoading(button, 'Change Password');
        });
    }

    // ==========================================
    // 3. NOTIFICATION SETTINGS LOGIC
    // ==========================================
    const notifyScanToggle = document.getElementById("notify-scan-complete");
    const notifyPremiumToggle = document.getElementById("notify-premium-active");

    if (notifyScanToggle) {
        notifyScanToggle.addEventListener("change", (e) => {
            saveNotificationPreference('notify_scan_complete', e.target.checked);
        });
    }

    if (notifyPremiumToggle) {
        notifyPremiumToggle.addEventListener("change", (e) => {
            saveNotificationPreference('notify_premium_approval', e.target.checked);
        });
    }

    async function saveNotificationPreference(settingName, isEnabled) {
        try {
            const response = await fetch('api/update_notifications.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ setting: settingName, enabled: isEnabled })
            });
            const result = await response.json();
            if (result.success) {
                showAlert('Notification preference updated!', 'success');
            } else {
                showAlert(result.error || 'Failed to save preference.', 'danger');
            }
        } catch (error) {
            console.error('Error saving preference:', error);
            showAlert('A network error occurred.', 'danger');
        }
    }

    // ==========================================
    // 4. SECURITY SETTINGS (SESSIONS) LOGIC
    // ==========================================
    const securityTab = document.getElementById('v-pills-security-tab');
    const sessionListContainer = document.getElementById('session-list-container');
    const logoutAllBtn = document.getElementById('logout-all-btn');
    let sessionsLoaded = false;

    if (securityTab) {
        securityTab.addEventListener('shown.bs.tab', () => {
            // Load sessions only when the tab is clicked, and only once per page load
            if (!sessionsLoaded) {
                loadActiveSessions();
                sessionsLoaded = true;
            }
        });
    }

    async function loadActiveSessions() {
        if (!sessionListContainer) return;

        sessionListContainer.innerHTML = `
            <div class="text-center p-3">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Loading sessions...
            </div>`;

        try {
            const response = await fetch('api/get_sessions.php');
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error || 'Could not load sessions.');
            }

            if (result.sessions.length === 0) {
                sessionListContainer.innerHTML = '<p class="text-secondary">No active sessions found.</p>';
                return;
            }

            sessionListContainer.innerHTML = ''; // Clear spinner
            result.sessions.forEach(session => {
                sessionListContainer.appendChild(createSessionElement(session));
            });
        } catch (error) {
            sessionListContainer.innerHTML = `<p class="text-danger">${error.message}</p>`;
        }
    }

    function createSessionElement(session) {
        const item = document.createElement('div');
        item.className = 'session-item';

        const isCurrent = session.is_current_session;
        const icon = isCurrent ? 'bi-laptop' : 'bi-display';
        const lastSeen = new Date(session.last_seen).toLocaleString();

        let sessionHTML = `
            <div class="session-icon"><i class="bi ${icon}"></i></div>
            <div class="session-details flex-grow-1">
                <strong>${session.ip_address}</strong>
                ${isCurrent ? '<span class="badge bg-success ms-2">This device</span>' : ''}
                <p>${session.user_agent.substring(0, 70)}...<br>Last seen: ${lastSeen}</p>
            </div>
            <div class="session-action">
        `;

        if (isCurrent) {
            sessionHTML += `<span class="text-success small">Active Now</span>`;
        } else {
            sessionHTML += `<button class="btn btn-sm btn-outline-danger revoke-btn" data-session-id="${session.id}">Revoke</button>`;
        }

        sessionHTML += '</div>';
        item.innerHTML = sessionHTML;

        // Add event listener to the new "Revoke" button
        const revokeBtn = item.querySelector('.revoke-btn');
        if (revokeBtn) {
            revokeBtn.addEventListener('click', () => {
                if (confirm('Are you sure you want to revoke this session?')) {
                    revokeSession(session.id, revokeBtn);
                }
            });
        }
        return item;
    }

    async function revokeSession(sessionId, button) {
        // Change button text to loading state
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '...';

        try {
            const response = await fetch('api/logout_session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'revoke_session', session_id: sessionId })
            });
            const result = await response.json();
            if (result.success) {
                showAlert('Session revoked!', 'success');
                button.closest('.session-item').remove(); // Remove it from the list
            } else {
                showAlert(result.error, 'danger');
                button.disabled = false;
                button.innerHTML = originalText;
            }
        } catch (error) {
            showAlert('A network error occurred.', 'danger');
            button.disabled = false;
            button.innerHTML = originalText;
        }
    }

    if (logoutAllBtn) {
        logoutAllBtn.addEventListener('click', async () => {
            if (!confirm('Are you sure you want to log out of all other devices?')) {
                return;
            }
            setLoading(logoutAllBtn, 'Logging out...');
            try {
                const response = await fetch('api/logout_session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'logout_all_other' })
                });
                const result = await response.json();
                if (result.success) {
                    showAlert('All other sessions logged out.', 'success');
                    sessionsLoaded = false; // Force a reload next time
                    // Re-load the list to show only the current session
                    loadActiveSessions();
                } else {
                    showAlert(result.error, 'danger');
                }
            } catch (error) {
                showAlert('A network error occurred.', 'danger');
            }
            resetLoading(logoutAllBtn, 'Log Out All');
        });
    }

    // ==========================================
    // 5. 2FA (TWO-FACTOR AUTH) LOGIC
    // ==========================================
    const enable2faBtn = document.getElementById('enable-2fa-btn');
    const disable2faBtn = document.getElementById('disable-2fa-btn');
    const tfaModalEl = document.getElementById('tfaModal');
    let tfaModal;

    // Initialize Bootstrap Modal if the element exists
    if (tfaModalEl) {
        tfaModal = new bootstrap.Modal(tfaModalEl);
    }

    // --- ENABLE 2FA ---
    if (enable2faBtn) {
        enable2faBtn.addEventListener('click', async () => {
            // 1. Request a new QR code from the server
            setLoading(enable2faBtn, 'Loading...');

            try {
                const res = await fetch('api/tfa_generate.php');
                const data = await res.json();

                if (data.success) {
                    // 2. Show the QR code and secret in the modal
                    // If we have an image (SVG), show it. If not, show the secret only.
                    if (data.qr_code_image) {
                        document.getElementById('tfa-qr-container').innerHTML = `<img src="${data.qr_code_image}" alt="QR Code" style="width: 200px; height: 200px;">`;
                    } else {
                        document.getElementById('tfa-qr-container').innerHTML = '<div class="alert alert-warning">QR Code not available. Use manual secret.</div>';
                    }

                    document.getElementById('tfa-secret-text').textContent = data.secret;

                    // 3. Open the modal
                    tfaModal.show();
                } else {
                    showAlert(data.error || 'Failed to generate QR.', 'danger');
                }
            } catch (error) {
                console.error(error);
                showAlert('Failed to generate 2FA code.', 'danger');
            }

            resetLoading(enable2faBtn, 'Enable 2FA');
        });
    }

    // --- VERIFY & ENABLE ---
    const verify2faBtn = document.getElementById('verify-2fa-btn');
    if (verify2faBtn) {
        verify2faBtn.addEventListener('click', async () => {
            const code = document.getElementById('tfa-verify-code').value;
            if (code.length !== 6) {
                alert("Please enter a valid 6-digit code.");
                return;
            }

            setLoading(verify2faBtn, 'Verifying...');

            try {
                const res = await fetch('api/tfa_enable.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code: code })
                });
                const data = await res.json();

                if (data.success) {
                    tfaModal.hide();
                    // Show recovery codes to the user
                    alert('2FA Enabled Successfully!\n\nPlease save these recovery codes in a safe place:\n\n' + data.recovery_codes.join('\n'));
                    // Reload the page to update the UI (Enable button -> Disable button)
                    location.reload();
                } else {
                    alert(data.error || 'Invalid code. Please try again.');
                }
            } catch (error) {
                alert('Verification failed. Please check your connection.');
            }

            resetLoading(verify2faBtn, 'Verify & Enable');
        });
    }

    // --- DISABLE 2FA ---
    if (disable2faBtn) {
        disable2faBtn.addEventListener('click', async () => {
            if (!confirm("Are you sure you want to disable Two-Factor Authentication? This will lower your account security.")) {
                return;
            }

            setLoading(disable2faBtn, 'Disabling...');

            try {
                const res = await fetch('api/tfa_disable.php', {
                    method: 'POST'
                });
                const data = await res.json();

                if (data.success) {
                    showAlert("2FA has been disabled.", "warning");
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.error, 'danger');
                }

            } catch (error) {
                showAlert('Error disabling 2FA.', 'danger');
            }

            resetLoading(disable2faBtn, 'Disable 2FA');
        });
    }

    // ==========================================
    // 6. SUBSCRIPTION & BILLING LOGIC
    // ==========================================
    // ==========================================
    // 6. SUBSCRIPTION & BILLING LOGIC
    // ==========================================
    
    // --- A. THE TRIGGER (THIS WAS MISSING) ---
    const billingTab = document.getElementById('v-pills-billing-tab');
    let billingLoaded = false;

    if (billingTab) {
        billingTab.addEventListener('shown.bs.tab', () => {
            // Only load if we haven't loaded it yet
            if (!billingLoaded) {
                loadPaymentHistory();
                billingLoaded = true;
            }
        });
    }

    // --- B. THE FUNCTION ---
    async function loadPaymentHistory() {
        const historyBody = document.getElementById('payment-history-body');
        const expiryEl = document.getElementById('status-expiry-date');
        const scansEl = document.getElementById('status-scans-left');
        const badgeEl = document.getElementById('status-badge');

        // Safety check: If elements don't exist, stop.
        if (!historyBody || !expiryEl || !scansEl) return;

        try {
            // Fetch data
            const res = await fetch('api/get_payment_history.php');
            // Check if the network request itself failed (e.g. 404 or 500)
            if (!res.ok) throw new Error(`HTTP Error: ${res.status}`);

            const data = await res.json();

            // Check if the API returned a logic error (success: false)
            if (!data.success) {
                console.error("API Error:", data.error);
                expiryEl.innerHTML = '<span class="text-danger">Error</span>';
                scansEl.innerHTML = '<span class="text-danger">Error</span>';
                historyBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">${data.error}</td></tr>`;
                return;
            }

            // --- 1. UPDATE STATUS CARDS ---
            const status = data.current_status;

            if (status) {
                // Update Scans
                scansEl.textContent = status.scans !== null ? status.scans : "0";

                // Update Expiry Date
                if (status.expiry) {
                    const dateObj = new Date(status.expiry);
                    // Check if date is valid
                    if (!isNaN(dateObj.getTime())) {
                        expiryEl.textContent = dateObj.toLocaleDateString();

                        if (dateObj < new Date()) {
                            badgeEl.textContent = "Expired";
                            badgeEl.className = "text-danger fw-bold";
                        } else {
                            badgeEl.textContent = "Active";
                            badgeEl.className = "text-success fw-bold";
                        }
                    } else {
                        expiryEl.textContent = "Invalid Date";
                    }
                } else {
                    expiryEl.textContent = "Lifetime / Free";
                    badgeEl.textContent = "Free Plan";
                    badgeEl.className = "text-secondary";
                }
            }

            // --- 2. UPDATE HISTORY TABLE ---
            if (Array.isArray(data.history) && data.history.length > 0) {
                historyBody.innerHTML = data.history.map(item => {
                    let badgeClass = 'bg-secondary';
                    if (item.status === 'approved') badgeClass = 'bg-success';
                    if (item.status === 'pending') badgeClass = 'bg-warning text-dark';
                    if (item.status === 'rejected') badgeClass = 'bg-danger';

                    // Safety: Ensure amount is a number
                    const amount = parseFloat(item.amount).toFixed(2);
                    const planDisplay = item.plan_name ? item.plan_name.replace('_', ' ').toUpperCase() : 'UNKNOWN';

                    return `
                    <tr>
                        <td>${new Date(item.created_at).toLocaleDateString()}</td>
                        <td><strong>${planDisplay}</strong></td>
                        <td>$${amount}</td>
                        <td><small class="text-muted font-monospace">${item.trx_id}</small></td>
                        <td><span class="badge ${badgeClass}">${item.status.toUpperCase()}</span></td>
                    </tr>
                `;
                }).join('');
            } else {
                historyBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No payment history found.</td></tr>';
            }

        } catch (error) {
            console.error("JS Error:", error);
            expiryEl.textContent = "-";
            scansEl.textContent = "-";
            historyBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">System Error. Check Console (F12).</td></tr>';
        }
    }
    // ==========================================
    // HELPER FUNCTIONS
    // ==========================================

    function setLoading(button, text) {
        if (button) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML; // Save original text
            button.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${text}`;
        }
    }

    function resetLoading(button, text) {
        if (button) {
            button.disabled = false;
            // Restore original text if 'text' param isn't provided, otherwise use 'text'
            button.innerHTML = text || button.dataset.originalText;
        }
    }

    function showAlert(message, type) {
        if (alertContainer) {
            alertContainer.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            `;
            // Auto-dismiss success alerts
            if (type === 'success') {
                setTimeout(() => {
                    const alert = alertContainer.querySelector('.alert');
                    if (alert) {
                        bootstrap.Alert.getOrCreateInstance(alert).close();
                    }
                }, 3000);
            }
        }
    }
});