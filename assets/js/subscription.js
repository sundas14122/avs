document.addEventListener('DOMContentLoaded', async () => {
    //
    // THE OLD, CONFLICTING SECURITY CHECK HAS BEEN REMOVED FROM HERE.
    // main.js now handles all security.
    //
    
    // Get references to all HTML elements
    const paymentSection = document.getElementById('payment-section');
    const paymentForm = document.getElementById('payment-form');
    const alertContainer = document.getElementById('alert-container');
    const statusBox = document.getElementById('status-box');
    const pricingPlans = document.getElementById('pricing-plans');
    const planButtons = document.querySelectorAll('.select-plan-btn');
    
    if (!paymentSection || !paymentForm || !statusBox || !pricingPlans) {
        console.error("Subscription page HTML is missing required elements (e.g., #payment-section, #status-box)");
        return; // Stop script if page is broken
    }
    
    let selectedPlan = '';

    function normalizePlanSlug(planType) {
        const raw = (planType || '').toString().trim().toLowerCase();
        if (raw === 'starter') return 'starter';
        if (raw === 'pro monthly' || raw === 'pro_monthly' || raw === 'monthly') return 'pro_monthly';
        if (raw === 'pro yearly' || raw === 'pro_yearly' || raw === 'yearly') return 'pro_yearly';
        return '';
    }

    function applyCurrentPlanButtonState(currentPlanSlug, userStatus) {
        planButtons.forEach((btn) => {
            if (!btn.dataset.defaultLabel) {
                btn.dataset.defaultLabel = btn.textContent.trim();
            }

            const btnPlan = (btn.getAttribute('data-plan') || '').trim();
            const isCurrent = userStatus === 'active' && currentPlanSlug !== '' && btnPlan === currentPlanSlug;

            btn.disabled = isCurrent;
            btn.textContent = isCurrent ? 'Current Plan' : btn.dataset.defaultLabel;
        });
    }

    // 1. Check current user status (Free/Pending/Active)
    try {
        const cacheBust = new Date().getTime();
        const response = await fetch(`api/get_user_data.php?cachebust=${cacheBust}`);
        
        if (!response.ok) {
            throw new Error(`API Error: ${response.statusText}`);
        }
        
        const user = await response.json();
        const statusBody = statusBox.querySelector('.card-body');
        
        const userPlan = user.plan_type || 'Free';
        const userStatus = user.subscription_status || 'free'; // Default to 'free' if null/empty
        const currentPlanSlug = normalizePlanSlug(userPlan);

        applyCurrentPlanButtonState(currentPlanSlug, userStatus);
        
        if (userStatus === 'pending') {
            statusBody.innerHTML = `<h5 class="card-title text-warning"><i class="bi bi-clock-history me-2"></i>Payment Pending Verification</h5><p class="mb-0">Your plan: <strong>${userPlan}</strong>. Please wait for admin approval (within 24 hours). If this takes too long, contact support.</p>`;
            statusBox.classList.remove('border-info', 'border-success');
            statusBox.classList.add('border-warning');
            pricingPlans.classList.add('d-none'); // Hide plans if pending
        } else if (userStatus === 'active') {
            // Plan is active and displays the name from the database (e.g., 'Basic', 'Pro Monthly')
            statusBody.innerHTML = `<h5 class="card-title text-success"><i class="bi bi-check-circle-fill me-2"></i>Active Subscription</h5><p class="mb-0">Current Plan: <strong>${userPlan}</strong>. You have full access. You can still switch or upgrade using the plans below.</p>`;
            statusBox.classList.remove('border-info', 'border-warning');
            statusBox.classList.add('border-success');
            pricingPlans.classList.remove('d-none'); // Keep plans visible for upgrades
        } else {
             // User is on the default/free plan
             statusBody.innerHTML = `<h5 class="card-title text-info"><i class="bi bi-info-circle-fill me-2"></i>Free Plan</h5><p class="mb-0">You are currently on the Free plan (1 Scan Limit). Upgrade now to unlock full features!</p>`;
             statusBox.classList.remove('border-success', 'border-warning');
             statusBox.classList.add('border-info');
             pricingPlans.classList.remove('d-none');
        }
        
    } catch (error) {
        console.error("Failed to get user data:", error);
        statusBox.innerHTML = `<div class="card-body text-danger">Could not load your subscription status. Please check your connection.</div>`;
    }

    // 2. Logic to Make Buttons Clickable and Show Payment Form
    if (planButtons.length === 0) {
        console.error("No plan buttons found with class '.select-plan-btn'.");
    }
    
    planButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            console.log("Plan button clicked!"); // For debugging
            
            selectedPlan = e.target.getAttribute('data-plan');
            const price = e.target.getAttribute('data-price');
            
            // Update the payment form text
            document.getElementById('selected-plan-name').innerText = selectedPlan;
            document.getElementById('selected-plan-price').innerText = price;
            document.getElementById('plan-input').value = selectedPlan;
            
            // UN-HIDE the payment section
            paymentSection.classList.remove('d-none');
            
            // Smooth scroll to the payment section
            paymentSection.scrollIntoView({ behavior: 'smooth' });
        });
    });

    // 3. Handle Payment Form Submission
    paymentForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const trxIdInput = document.getElementById('trx-id');
        const proofInput = document.getElementById('payment-proof');
        const submitBtn = paymentForm.querySelector('button[type="submit"]');
        
        if (!proofInput.files[0]) {
             showAlert('Please upload a payment proof file.', 'danger');
             return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';

        const formData = new FormData(paymentForm);
        formData.set('plan', selectedPlan); 

        try {
            const response = await fetch('api/submit_payment.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (response.ok) {
                showAlert('Payment submitted successfully! The page will reload shortly.', 'success');
                setTimeout(() => location.reload(), 2000);
            } else {
                showAlert(`Error: ${result.message}`, 'danger');
                submitBtn.disabled = false;
                submitBtn.innerHTML = "Submit for Verification";
            }
        } catch (error) {
            console.error("Form submission error:", error);
            showAlert('An error occurred. Please check your network connection.', 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = "Submit for Verification";
        }
    });

    // Alert display function
    function showAlert(message, type = 'success') {
        alertContainer.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>`;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
});