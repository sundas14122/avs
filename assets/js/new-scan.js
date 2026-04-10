document.addEventListener("DOMContentLoaded", () => {
    
    const scanForm = document.getElementById("new-scan-form");
    const targetInput = document.getElementById("target-url");
    const scanTypeSelect = document.getElementById("scan-type");
    const alertContainer = document.getElementById("scan-alert-container");
    const scanButton = document.getElementById("start-scan-btn");
    
    // 1. GET THE HIDDEN USER ID
    const userIdInput = document.getElementById("user_id"); 

    if (scanForm) {
        scanForm.addEventListener("submit", async (event) => {
            event.preventDefault(); 
            
            let targetURL = targetInput.value.trim();
            const scanType = scanTypeSelect.value;
            let userId = userIdInput ? userIdInput.value : null;

            // --- SMART URL HANDLING ---
            const isIP = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/.test(targetURL);
            
            if (!targetURL.match(/^https?:\/\//) && !isIP) {
                targetURL = 'http://' + targetURL;
            }
            
            if (!userId) {
                showError("Session Error: User ID is missing. Please logout and login again.");
                return;
            }

            // 2. UI LOADING STATE
            scanButton.disabled = true;
            scanButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Starting Engine...';
            if(alertContainer) alertContainer.innerHTML = '';

            // 3. PREPARE PAYLOAD
            const payload = {
                target: targetURL, 
                scan_type: scanType,
                user_id: parseInt(userId) 
            };

            // 4. Start scan through PHP API (server-side forwards to Django)
            try {
                const response = await fetch('api/start_scan.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });

                // Check for HTML error pages (404/500) before parsing JSON
                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    throw new Error("Server returned HTML instead of JSON. Please check your PHP API route.");
                }

                const data = await response.json();

                if (response.ok) {
                    console.log("✅ Scan Started Successfully:", data);
                    showSuccess(`Scan started! (ID: ${data.scan_id}). Redirecting...`);
                    
                    setTimeout(() => {
                        window.location.href = 'scan-history.php';
                    }, 1500);
                } else {
                    console.error("⚠️ Backend Rejected:", data);
                    throw new Error(data.error || "Backend rejected the request.");
                }

            } catch (error) {
                console.error("❌ Connection Error:", error);
                if (error.message.includes("Failed to fetch")) {
                    showError("Error: Could not reach application API. Please check Apache/PHP service.");
                } else {
                    showError(error.message);
                }
                
                // Re-enable button so user can try again
                scanButton.disabled = false;
                scanButton.innerHTML = 'Start Scan';
            }
        });
    }

    function showSuccess(message) {
        if(alertContainer) alertContainer.innerHTML = `<div class="alert alert-success" role="alert"><i class="bi bi-check-circle-fill me-2"></i>${message}</div>`;
    }

    function showError(message) {
        if(alertContainer) alertContainer.innerHTML = `<div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i>${message}</div>`;
    }
});