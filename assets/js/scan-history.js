document.addEventListener('DOMContentLoaded', () => {
    const scanTableBody = document.getElementById('scan-history-body');
    const filterButtons = document.querySelectorAll('.status-filter-btn');
    const searchInput = document.getElementById('scan-search-input');
    
    let allScans = [];
    
    // Function to fetch scan data from the PHP API
    async function fetchScans() {
        if (!scanTableBody) return;

        scanTableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span> Loading scans...</td></tr>';
        
        try {
            const response = await fetch('api/get_scans.php');
            const data = await response.json();

            if (!response.ok || !data.success) {
                scanTableBody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">${data.message || 'Failed to load scan history.'}</td></tr>`;
                return;
            }

            allScans = data.scans;
            displayScans(allScans);

        } catch (error) {
            console.error("Error fetching scan data:", error);
            scanTableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Network error. Cannot connect to the server.</td></tr>';
        }
    }
    
    // Cancel scan via PHP proxy endpoint (which validates session and maps task IDs)
    window.cancelScan = async function(scanId) {
        if (!confirm("Are you sure you want to stop this scan?")) return;

        try {
            const response = await fetch(`api/cancel_scan.php?scan_id=${scanId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await response.json();

            if (response.ok && data.success) {
                alert(data.message || "Scan cancelled successfully.");
                fetchScans(); // Refresh table data
            } else {
                alert("Error: " + (data.error || "Could not cancel scan."));
            }
        } catch (error) {
            console.error("Cancel error:", error);
            alert("Connection to backend failed.");
        }
    };

    // Function to render the scans in the table
    function displayScans(scansToDisplay) {
        if (scansToDisplay.length === 0) {
            scanTableBody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-info">No scans found for this user.</td></tr>';
            return;
        }

        scanTableBody.innerHTML = scansToDisplay.map(scan => {
            const statusClass = getStatusClass(scan.status);
            const vulnerabilities = scan.vulnerability_count > 0 
                ? `<span class="badge bg-danger">${scan.vulnerability_count} Found</span>`
                : `<span class="badge bg-success">Clean</span>`;
            
            // Generate Action Buttons
            let actionButtons = '';
            
            // 1. PDF Report Link (If Completed)
            if (scan.status === 'Completed') {
                actionButtons += `
                    <a href="api/generate_pdf.php?id=${scan.id}" target="_blank" class="btn btn-sm btn-outline-info me-1" title="Download Report">
                        <i class="bi bi-file-pdf"></i> PDF
                    </a>`;
            }

            // 2. CANCEL BUTTON (If Running or Pending)
            if (scan.status === 'Running' || scan.status === 'Pending') {
                actionButtons += `
                    <button onclick="cancelScan(${scan.id})" class="btn btn-sm btn-outline-danger" title="Cancel Scan">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>`;
            }

            // Fallback for View Details
            actionButtons += `
                <a href="scan-details.php?id=${scan.id}" class="btn btn-sm btn-outline-primary ms-1">
                    <i class="bi bi-eye"></i> View
                </a>`;

            return `
                <tr>
                    <td>${scan.id}</td>
                    <td><small class="text-muted">${scan.target}</small></td>
                    <td>${scan.started_at}</td>
                    <td>${scan.finished_at || '---'}</td>
                    <td><span class="badge ${statusClass}">${scan.status}</span></td>
                    <td>${vulnerabilities}</td>
                    <td><div class="d-flex">${actionButtons}</div></td>
                </tr>
            `;
        }).join('');
    }

    function getStatusClass(status) {
        switch (status) {
            case 'Completed': return 'bg-success';
            case 'Running': return 'bg-info text-dark';
            case 'Pending': return 'bg-warning text-dark';
            case 'Failed': return 'bg-danger';
            case 'Cancelled': return 'bg-secondary';
            default: return 'bg-secondary';
        }
    }
    
    function filterAndSearch() {
        const searchTerm = searchInput.value.toLowerCase();
        let activeStatus = '';

        filterButtons.forEach(btn => {
            if (btn.classList.contains('active')) {
                activeStatus = btn.textContent.trim();
            }
        });

        const filteredScans = allScans.filter(scan => {
            const matchesStatus = (activeStatus === 'All' || activeStatus === '') || scan.status === activeStatus;
            const matchesSearch = scan.target.toLowerCase().includes(searchTerm);
            return matchesStatus && matchesSearch;
        });

        displayScans(filteredScans);
    }

    // Event Listeners
    filterButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            filterButtons.forEach(b => b.classList.remove('active'));
            e.currentTarget.classList.add('active');
            filterAndSearch();
        });
    });

    if(searchInput) searchInput.addEventListener('keyup', filterAndSearch);
    
    fetchScans();
});