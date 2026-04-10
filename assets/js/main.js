// This code runs when the page is fully loaded
document.addEventListener("DOMContentLoaded", () => {

    // --- Sidebar Toggle Logic ---
    const sidebarToggle = document.getElementById("sidebar-toggle");
    const sidebar = document.getElementById("sidebar");
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener("click", () => {
            // This adds/removes a 'collapsed' class to the sidebar
            sidebar.classList.toggle("collapsed"); 
        });
    }

    // --- 
    // Logout Logic
    // ---
    const logoutButton = document.getElementById("logout-btn");

    if (logoutButton) {
        logoutButton.addEventListener("click", (event) => {
            // Stop the link from doing anything
            event.preventDefault(); 
            
            // Call the logout API in the background
            fetch('api/logout.php', {
                method: 'GET',
            })
            .then(response => response.json())
            .then(data => {
                if (data.message === "Logged out successfully.") {
                    // SUCCESS: Redirect to login page
                    window.location.href = 'login.php';
                } else {
                    // Handle any unexpected errors
                    alert('Logout failed. Please try again.');
                }
            })
            .catch(error => {
                console.error("Logout Error:", error);
                // Still redirect, as session is likely gone
                window.location.href = 'login.php'; 
            });
        });
    }
    // --- END OF LOGOUT LOGIC ---

});