<?php
require_once 'api/db_connect.php';

if (!validate_session($conn)) {
    header('Location: login.php');
    exit;
}

if (!current_user_is_admin($conn)) {
    http_response_code(403);
    echo '<h2 style="font-family: Arial; margin: 24px;">Access denied. Admin privileges required.</h2>';
    exit;
}

$username = $_SESSION['username'] ?? 'Admin';
$conn->close();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Vulnerability Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Admin Panel</h1>
            <p class="text-secondary mb-0">Operations console for payments, users, scans, and sessions</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-danger" id="admin-logout-btn"><i class="bi bi-box-arrow-right me-1"></i>Logout</button>
            <button class="btn btn-primary" id="refresh-btn"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
        </div>
    </div>

    <div class="alert alert-info">Logged in as <strong><?php echo htmlspecialchars($username); ?></strong>. Configure admins with env vars: <code>ADMIN_EMAILS</code> or <code>ADMIN_USER_IDS</code>.</div>

    <div id="admin-alert"></div>

    <ul class="nav nav-tabs mb-3" id="adminTab" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button">Overview</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-payments" type="button">Payments</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-users" type="button">Users</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-scans" type="button">Scans</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sessions" type="button">Sessions</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-overview">
            <div class="row g-3" id="overview-cards"></div>
        </div>

        <div class="tab-pane fade" id="tab-payments">
            <div class="card mb-4" style="background-color: var(--primary-surface);">
                <div class="card-header fw-bold">Pending Payments</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th><th>User</th><th>Plan</th><th>Amount</th><th>TRX ID</th><th>Proof</th><th>Date</th><th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="pending-body"><tr><td colspan="8" class="text-center py-4">Loading...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card" style="background-color: var(--primary-surface);">
                <div class="card-header fw-bold">Recent Processed Payments</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark align-middle mb-0">
                            <thead>
                                <tr><th>ID</th><th>User</th><th>Plan</th><th>Amount</th><th>Status</th><th>Date</th></tr>
                            </thead>
                            <tbody id="recent-body"><tr><td colspan="6" class="text-center py-4">Loading...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-users">
            <div class="card" style="background-color: var(--primary-surface);">
                <div class="card-header fw-bold">Users Management</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark align-middle mb-0">
                            <thead>
                                <tr><th>ID</th><th>User</th><th>Subscription</th><th>Scans Rem.</th><th>2FA</th><th>Expiry</th><th>Actions</th></tr>
                            </thead>
                            <tbody id="users-body"><tr><td colspan="7" class="text-center py-4">Loading...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-scans">
            <div class="card" style="background-color: var(--primary-surface);">
                <div class="card-header fw-bold">Recent Scans</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark align-middle mb-0">
                            <thead>
                                <tr><th>ID</th><th>User</th><th>Target</th><th>Type</th><th>Status</th><th>Task ID</th><th>Created</th></tr>
                            </thead>
                            <tbody id="scans-body"><tr><td colspan="7" class="text-center py-4">Loading...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-sessions">
            <div class="card" style="background-color: var(--primary-surface);">
                <div class="card-header fw-bold">Active Sessions</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-dark align-middle mb-0">
                            <thead>
                                <tr><th>ID</th><th>User</th><th>IP</th><th>User Agent</th><th>Last Seen</th><th>Action</th></tr>
                            </thead>
                            <tbody id="sessions-body"><tr><td colspan="6" class="text-center py-4">Loading...</td></tr></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const overviewCards = document.getElementById('overview-cards');
const pendingBody = document.getElementById('pending-body');
const recentBody = document.getElementById('recent-body');
const usersBody = document.getElementById('users-body');
const scansBody = document.getElementById('scans-body');
const sessionsBody = document.getElementById('sessions-body');
const alertBox = document.getElementById('admin-alert');

function showAlert(type, message) {
    alertBox.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
}

function proofLink(path) {
    if (!path) return '<span class="text-secondary">N/A</span>';
    return `<a href="${path}" target="_blank" class="btn btn-sm btn-outline-info">View</a>`;
}

function statusBadge(status) {
    const s = (status || '').toLowerCase();
    if (s === 'approved') return '<span class="badge bg-success">approved</span>';
    if (s === 'rejected') return '<span class="badge bg-danger">rejected</span>';
    if (s === 'pending') return '<span class="badge bg-warning text-dark">pending</span>';
    return `<span class="badge bg-secondary">${status}</span>`;
}

function overviewCard(title, value, sub = '') {
    return `<div class="col-md-4 col-lg-3"><div class="card h-100" style="background-color: var(--primary-surface);"><div class="card-body"><div class="text-secondary small">${title}</div><div class="h4 mb-0">${value}</div>${sub ? `<div class="small text-secondary mt-1">${sub}</div>` : `<div class="small mt-1">&nbsp;</div>`}</div></div></div>`;
}

async function loadOverview() {
    try {
        const res = await fetch('api/admin_overview.php');
        const data = await res.json();
        if (!data.success) throw new Error(data.error || 'Failed to load overview.');

        const o = data.overview;
        overviewCards.innerHTML = [
            overviewCard('Users Total', o.users_total, `Active ${o.users_active} | Pending ${o.users_pending} | Free ${o.users_free}`),
            overviewCard('Payments Pending', o.payments_pending, `Approved ${o.payments_approved} | Rejected ${o.payments_rejected}`),
            overviewCard('Scans Total', o.scans_total, `P ${o.scans_pending} | R ${o.scans_running} | C ${o.scans_completed} | F ${o.scans_failed}`),
            overviewCard('Sessions Total', o.sessions_total)
        ].join('');
    } catch (err) {
        showAlert('danger', err.message || 'Overview loading failed.');
    }
}

async function loadPayments() {
    pendingBody.innerHTML = '<tr><td colspan="8" class="text-center py-4">Loading...</td></tr>';
    recentBody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Loading...</td></tr>';

    try {
        const res = await fetch('api/admin_get_payments.php');
        const data = await res.json();

        if (!data.success) throw new Error(data.error || 'Failed to load payments.');

        const payments = data.payments || [];
        const pending = payments.filter(p => (p.status || '').toLowerCase() === 'pending');
        const recent = payments.filter(p => (p.status || '').toLowerCase() !== 'pending').slice(0, 40);

        if (!pending.length) {
            pendingBody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-secondary">No pending payments.</td></tr>';
        } else {
            pendingBody.innerHTML = pending.map(p => `
                <tr>
                    <td>${p.id}</td>
                    <td><div>${p.username}</div><small class="text-secondary">${p.email}</small></td>
                    <td>${p.plan_name}</td>
                    <td>${Number(p.amount).toFixed(2)}</td>
                    <td>${p.trx_id || ''}</td>
                    <td>${proofLink(p.proof_image)}</td>
                    <td>${p.created_at}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-success" onclick="processPayment(${p.id}, 'approve')">Approve</button>
                            <button class="btn btn-danger" onclick="processPayment(${p.id}, 'reject')">Reject</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        if (!recent.length) {
            recentBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-secondary">No processed payments yet.</td></tr>';
        } else {
            recentBody.innerHTML = recent.map(p => `
                <tr>
                    <td>${p.id}</td>
                    <td><div>${p.username}</div><small class="text-secondary">${p.email}</small></td>
                    <td>${p.plan_name}</td>
                    <td>${Number(p.amount).toFixed(2)}</td>
                    <td>${statusBadge(p.status)}</td>
                    <td>${p.created_at}</td>
                </tr>
            `).join('');
        }
    } catch (err) {
        showAlert('danger', err.message || 'Failed to load admin data.');
    }
}

async function loadUsers() {
    usersBody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Loading...</td></tr>';
    try {
        const res = await fetch('api/admin_get_users.php');
        const data = await res.json();
        if (!data.success) throw new Error(data.error || 'Failed to load users.');

        const users = data.users || [];
        if (!users.length) {
            usersBody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-secondary">No users found.</td></tr>';
            return;
        }

        usersBody.innerHTML = users.map(u => `
            <tr>
                <td>${u.id}</td>
                <td><div>${u.username}</div><small class="text-secondary">${u.email}</small></td>
                <td>${statusBadge(u.subscription_status)}</td>
                <td>${u.scans_remaining}</td>
                <td>${u.tfa_enabled ? '<span class="badge bg-success">enabled</span>' : '<span class="badge bg-secondary">disabled</span>'}</td>
                <td>${u.expiry_date || 'N/A'}</td>
                <td>
                    <div class="btn-group btn-group-sm mb-1">
                        <button class="btn btn-outline-success" onclick="setUserStatus(${u.id}, 'active')">Active</button>
                        <button class="btn btn-outline-warning" onclick="setUserStatus(${u.id}, 'pending')">Pending</button>
                        <button class="btn btn-outline-secondary" onclick="setUserStatus(${u.id}, 'free')">Free</button>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-info" onclick="setScans(${u.id})">Set Scans</button>
                        <button class="btn btn-outline-danger" onclick="disableTfa(${u.id})">Disable 2FA</button>
                        <button class="btn btn-danger" onclick="forceLogout(${u.id})">Logout All</button>
                    </div>
                </td>
            </tr>
        `).join('');
    } catch (err) {
        showAlert('danger', err.message || 'Users loading failed.');
    }
}

async function loadScans() {
    scansBody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Loading...</td></tr>';
    try {
        const res = await fetch('api/admin_get_scans.php');
        const data = await res.json();
        if (!data.success) throw new Error(data.error || 'Failed to load scans.');

        const scans = data.scans || [];
        if (!scans.length) {
            scansBody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-secondary">No scans found.</td></tr>';
            return;
        }

        scansBody.innerHTML = scans.map(s => `
            <tr>
                <td>${s.id}</td>
                <td><div>${s.username}</div><small class="text-secondary">${s.email}</small></td>
                <td><small>${s.target_url}</small></td>
                <td>${s.scan_type}</td>
                <td>${statusBadge(s.status)}</td>
                <td><small>${s.task_id || 'N/A'}</small></td>
                <td>${s.created_at}</td>
            </tr>
        `).join('');
    } catch (err) {
        showAlert('danger', err.message || 'Scans loading failed.');
    }
}

async function loadSessions() {
    sessionsBody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Loading...</td></tr>';
    try {
        const res = await fetch('api/admin_get_sessions.php');
        const data = await res.json();
        if (!data.success) throw new Error(data.error || 'Failed to load sessions.');

        const sessions = data.sessions || [];
        if (!sessions.length) {
            sessionsBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-secondary">No sessions found.</td></tr>';
            return;
        }

        sessionsBody.innerHTML = sessions.map(s => `
            <tr>
                <td>${s.id}</td>
                <td><div>${s.username}</div><small class="text-secondary">${s.email}</small></td>
                <td>${s.ip_address || 'N/A'}</td>
                <td><small>${(s.user_agent || '').substring(0, 90)}</small></td>
                <td>${s.last_seen}</td>
                <td><button class="btn btn-sm btn-danger" onclick="revokeSession(${s.id})">Revoke</button></td>
            </tr>
        `).join('');
    } catch (err) {
        showAlert('danger', err.message || 'Sessions loading failed.');
    }
}

async function processPayment(paymentId, action) {
    if (!confirm(`Are you sure you want to ${action} payment #${paymentId}?`)) return;

    try {
        const res = await fetch('api/admin_update_payment.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({payment_id: paymentId, action})
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error || 'Action failed.');

        showAlert('success', data.message || 'Updated successfully.');
        await loadPayments();
    } catch (err) {
        showAlert('danger', err.message || 'Request failed.');
    }
}

async function updateUserAction(payload) {
    const res = await fetch('api/admin_update_user.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!data.success) throw new Error(data.error || 'User update failed.');
    return data;
}

async function setUserStatus(userId, status) {
    try {
        const data = await updateUserAction({user_id: userId, action: 'set_subscription', status});
        showAlert('success', data.message || 'Updated.');
        await loadOverview();
        await loadUsers();
    } catch (err) {
        showAlert('danger', err.message || 'Update failed.');
    }
}

async function setScans(userId) {
    const val = prompt('Enter scans remaining value:', '100');
    if (val === null) return;
    const num = Number(val);
    if (!Number.isInteger(num) || num < 0) {
        showAlert('danger', 'Please enter a valid non-negative integer.');
        return;
    }

    try {
        const data = await updateUserAction({user_id: userId, action: 'set_scans_remaining', value: num});
        showAlert('success', data.message || 'Updated.');
        await loadUsers();
    } catch (err) {
        showAlert('danger', err.message || 'Update failed.');
    }
}

async function disableTfa(userId) {
    if (!confirm('Disable 2FA for this user?')) return;
    try {
        const data = await updateUserAction({user_id: userId, action: 'disable_tfa'});
        showAlert('success', data.message || 'Updated.');
        await loadUsers();
    } catch (err) {
        showAlert('danger', err.message || 'Update failed.');
    }
}

async function forceLogout(userId) {
    if (!confirm('Revoke all sessions for this user?')) return;
    try {
        const data = await updateUserAction({user_id: userId, action: 'force_logout'});
        showAlert('success', data.message || 'Updated.');
        await loadSessions();
    } catch (err) {
        showAlert('danger', err.message || 'Update failed.');
    }
}

async function revokeSession(sessionId) {
    if (!confirm('Revoke this session?')) return;
    try {
        const res = await fetch('api/admin_revoke_session.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({session_id: sessionId})
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error || 'Failed.');
        showAlert('success', data.message || 'Session revoked.');
        await loadSessions();
    } catch (err) {
        showAlert('danger', err.message || 'Action failed.');
    }
}

async function loadAll() {
    await Promise.all([
        loadOverview(),
        loadPayments(),
        loadUsers(),
        loadScans(),
        loadSessions()
    ]);
}

document.getElementById('refresh-btn').addEventListener('click', loadAll);

document.getElementById('admin-logout-btn').addEventListener('click', async () => {
    try {
        await fetch('api/logout.php', { method: 'POST' });
    } catch (e) {
        // Ignore logout API errors and continue to login page.
    }
    window.location.href = 'login.php';
});

// Fallback tab switcher in case Bootstrap tab plugin is not active due to cache/script issues.
document.querySelectorAll('#adminTab .nav-link').forEach(btn => {
    btn.addEventListener('click', () => {
        const target = btn.getAttribute('data-bs-target');
        if (!target) return;

        document.querySelectorAll('#adminTab .nav-link').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        document.querySelectorAll('.tab-content .tab-pane').forEach(p => {
            p.classList.remove('show', 'active');
        });

        const pane = document.querySelector(target);
        if (pane) pane.classList.add('show', 'active');
    });
});

loadAll();
</script>
</body>
</html>
