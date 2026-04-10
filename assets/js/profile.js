document.addEventListener('DOMContentLoaded', async () => {
    // main.js handles the primary security check

    // Form Elements
    const profileForm = document.getElementById('profile-form');
    const fullNameInput = document.getElementById('fullName');
    const bioInput = document.getElementById('bio');
    const alertContainer = document.getElementById('alert-container');

    // Function to show alerts
    function showAlert(message, type = 'success') {
        alertContainer.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        setTimeout(() => { alertContainer.innerHTML = ''; }, 3000);
    }

    // Fetch and Populate Initial Data (with Cache Bust)
    try {
        const cacheBust = new Date().getTime();
        const response = await fetch(`api/get_user_data.php?cachebust=${cacheBust}`);
        const currentUser = await response.json();
        if (response.ok) {
            fullNameInput.value = currentUser.fullName || '';
            bioInput.value = currentUser.bio || '';
        } else {
            showAlert('Could not load profile data.', 'danger');
        }
    } catch (error) {
        showAlert('An error occurred while fetching your profile.', 'danger');
    }

    // Event Listener for Profile Form Submission
    profileForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const newFullName = fullNameInput.value.trim();
        const newBio = bioInput.value.trim();

        const response = await fetch('api/update_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fullName: newFullName, bio: newBio })
        });
        const result = await response.json();
        showAlert(result.message, response.ok ? 'success' : 'danger');
    });
});