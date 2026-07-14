<?php
require_once 'functions.php';
require_role(['superadmin','manager']);

$search = sanitize($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['p'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$where  = $search ? "WHERE u.username LIKE ? OR al.action LIKE ?" : "";
$types  = $search ? 'ss' : '';
$params = $search ? ["%$search%", "%$search%"] : [];

$total = db_prepare_execute(
    "SELECT COUNT(*) c FROM activity_logs al JOIN users u ON u.id=al.user_id $where",
    $types, $params
)->get_result()->fetch_assoc()['c'];

$logs = db_prepare_execute(
    "SELECT al.id, u.username, u.role, al.action, al.ip, al.created_at
     FROM activity_logs al JOIN users u ON u.id=al.user_id
     $where ORDER BY al.created_at DESC LIMIT ? OFFSET ?",
    $types . 'ii', array_merge($params, [$limit, $offset])
)->get_result()->fetch_all(MYSQLI_ASSOC);

$pages = (int)ceil($total / $limit);

$page_title = 'Activity Logs';
$page_subtitle = 'System Activity Monitoring';
include 'includes/header.php';
?>

<!-- ==========================================================
     ACTIVITY LOGS CUSTOM STYLES
     ========================================================== -->
<style>
/* Activity Logs Specific Styles */
.activity-logs-wrapper {
    padding: 10px 0;
}

/* Page Header */
.page-header-custom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}

.page-header-custom h1 {
    font-size: 28px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0;
}

.page-header-custom h1 .header-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.25);
}

.page-header-custom .header-stats {
    display: flex;
    gap: 20px;
    font-size: 14px;
    color: #64748b;
}

.page-header-custom .header-stats span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.page-header-custom .header-stats strong {
    color: #0f172a;
}

/* Search Bar */
.search-bar-custom {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.search-bar-custom .search-input-wrap {
    flex: 1;
    min-width: 200px;
    position: relative;
}

.search-bar-custom .search-input-wrap i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}

.search-bar-custom .search-input-wrap input {
    width: 100%;
    padding: 10px 14px 10px 42px;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    font-size: 14px;
    transition: 0.3s;
    background: #fafbfc;
    font-family: 'Poppins', sans-serif;
}

.search-bar-custom .search-input-wrap input:focus {
    border-color: #2563eb;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
    outline: none;
}

.search-bar-custom .search-input-wrap input::placeholder {
    color: #94a3b8;
}

.search-bar-custom .btn-search {
    padding: 10px 24px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    font-size: 14px;
    transition: 0.3s;
    font-family: 'Poppins', sans-serif;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.search-bar-custom .btn-search.btn-primary {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: #fff;
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.25);
}

.search-bar-custom .btn-search.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.35);
}

.search-bar-custom .btn-search.btn-secondary {
    background: #f1f5f9;
    color: #1e293b;
}

.search-bar-custom .btn-search.btn-secondary:hover {
    background: #e2e8f0;
    transform: translateY(-2px);
}

/* Table Enhancements */
.logs-table {
    font-size: 0.88rem;
}

.logs-table thead {
    background: #f1f5f9;
    color: #1e293b;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.logs-table thead th {
    padding: 14px 16px;
    font-weight: 600;
    border-bottom: 2px solid #e9edf2;
}

.logs-table tbody td {
    padding: 14px 16px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}

.logs-table tbody tr {
    transition: 0.2s;
}

.logs-table tbody tr:hover {
    background: #f8fafc;
}

.logs-table .action-text {
    font-weight: 500;
    color: #1e293b;
}

.logs-table .action-text .action-icon {
    margin-right: 6px;
    opacity: 0.6;
}

.logs-table .role-badge {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 30px;
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.logs-table .role-badge.superadmin {
    background: #fef3c7;
    color: #92400e;
}

.logs-table .role-badge.admin {
    background: #dbeafe;
    color: #1e40af;
}

.logs-table .role-badge.manager {
    background: #d1fae5;
    color: #065f46;
}

.logs-table .role-badge.student {
    background: #fce4ec;
    color: #9a1f3d;
}

.logs-table .ip-address {
    font-family: 'Courier New', monospace;
    font-size: 13px;
    background: #f1f5f9;
    padding: 2px 10px;
    border-radius: 6px;
    display: inline-block;
}

.logs-table .timestamp {
    font-size: 13px;
    color: #64748b;
    white-space: nowrap;
}

.logs-table .timestamp i {
    margin-right: 4px;
    font-size: 12px;
}

/* Action Buttons for Logs */
.logs-table .action-btns {
    display: flex;
    gap: 6px;
}

.logs-table .action-btns .btn-action {
    width: 34px;
    height: 34px;
    padding: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    transition: 0.3s;
    font-size: 14px;
    cursor: pointer;
    text-decoration: none;
}

.logs-table .action-btns .btn-action.btn-info {
    background: #dbeafe;
    color: #1e40af;
}

.logs-table .action-btns .btn-action.btn-info:hover {
    background: #2563eb;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.logs-table .action-btns .btn-action.btn-warning {
    background: #fef3c7;
    color: #92400e;
}

.logs-table .action-btns .btn-action.btn-warning:hover {
    background: #f59e0b;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.logs-table .action-btns .btn-action.btn-danger {
    background: #fee2e2;
    color: #991b1b;
}

.logs-table .action-btns .btn-action.btn-danger:hover {
    background: #dc2626;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

/* Pagination */
.pagination-custom {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid #e9edf2;
    justify-content: space-between;
    align-items: center;
}

.pagination-custom .page-info {
    font-size: 14px;
    color: #64748b;
}

.pagination-custom .page-info strong {
    color: #0f172a;
}

.pagination-custom .page-buttons {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.pagination-custom .page-btn {
    padding: 8px 16px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #1e293b;
    font-weight: 500;
    font-size: 14px;
    transition: 0.3s;
    font-family: 'Poppins', sans-serif;
    text-decoration: none;
    min-width: 36px;
    text-align: center;
}

.pagination-custom .page-btn:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
    transform: translateY(-2px);
}

.pagination-custom .page-btn.active {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: #fff;
    border-color: #2563eb;
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.25);
}

.pagination-custom .page-btn.active:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.35);
}

.pagination-custom .page-btn.disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* Empty State */
.empty-state-custom {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
}

.empty-state-custom i {
    font-size: 56px;
    display: block;
    margin-bottom: 16px;
    opacity: 0.4;
}

.empty-state-custom p {
    font-size: 15px;
    margin: 0;
}

/* Animations */
@keyframes fadeSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeSlideUp 0.5s ease forwards;
}

/* Responsive */
@media (max-width: 768px) {
    .page-header-custom {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    
    .page-header-custom .header-stats {
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .search-bar-custom {
        flex-direction: column;
    }
    
    .search-bar-custom .search-input-wrap {
        min-width: 100%;
    }
    
    .search-bar-custom .btn-search {
        justify-content: center;
    }
    
    .pagination-custom {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
    }
    
    .pagination-custom .page-buttons {
        justify-content: center;
    }
    
    .logs-table .action-btns {
        flex-wrap: wrap;
    }
}

@media (max-width: 576px) {
    .logs-table thead {
        display: none;
    }
    
    .logs-table tbody td {
        display: block;
        padding: 8px 12px;
        border-bottom: none;
    }
    
    .logs-table tbody td:before {
        content: attr(data-label);
        font-weight: 600;
        display: inline-block;
        width: 80px;
        color: #64748b;
        font-size: 12px;
    }
    
    .logs-table tbody td:last-child {
        border-bottom: 1px solid #f1f5f9;
    }
    
    .logs-table tbody tr {
        border-bottom: 2px solid #e9edf2;
        margin-bottom: 12px;
        display: block;
    }
}

/* Print Styles */
@media print {
    .search-bar-custom,
    .action-btns,
    .pagination-custom {
        display: none !important;
    }
    
    .page-header-custom {
        border-bottom: 1px solid #ddd;
        padding-bottom: 15px;
    }
}
</style>

<!-- ==========================================================
     PAGE HEADER
     ========================================================== -->
<div class="page-header-custom">
    <h1>
        <span class="header-icon"><i class="bi bi-clock-history"></i></span>
        Activity Logs
    </h1>
    <div class="header-stats">
        <span>
            <i class="bi bi-database"></i> 
            Total: <strong><?= (int)$total ?></strong> records
        </span>
        <span>
            <i class="bi bi-calendar3"></i> 
            Page: <strong><?= $page ?></strong> of <strong><?= $pages ?: 1 ?></strong>
        </span>
        <?php if ($search): ?>
        <span>
            <i class="bi bi-search"></i> 
            Filter: "<strong><?= htmlspecialchars($search) ?></strong>"
        </span>
        <?php endif; ?>
    </div>
</div>

<!-- ==========================================================
     ACTIVITY LOGS TABLE
     ========================================================== -->
<div class="card">
    <div class="card-header">
        <div class="card-icon"><i class="bi bi-list-check"></i></div>
        System Activity Logs
        <span style="margin-left:auto;font-size:13px;color:#64748b;">
            <i class="bi bi-info-circle"></i> Real-time activity monitoring
        </span>
    </div>
    <div class="card-body">

        <!-- Search Bar -->
        <form method="get" class="search-bar-custom">
            <div class="search-input-wrap">
                <i class="bi bi-search"></i>
                <input type="text" name="q" placeholder="Search by username or action…"
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn-search btn-primary">
                <i class="bi bi-search"></i> Search
            </button>
            <?php if ($search): ?>
                <a href="activity_logs.php" class="btn-search btn-secondary">
                    <i class="bi bi-x"></i> Clear
                </a>
            <?php endif; ?>
        </form>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table logs-table">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>User</th>
                        <th style="width:100px;">Role</th>
                        <th>Action</th>
                        <th style="width:120px;">IP Address</th>
                        <th style="width:160px;">Time</th>
                        <th style="width:100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $i => $l): ?>
                    <?php 
                    // Determine action icon
                    $actionIcon = '';
                    if (strpos(strtolower($l['action']), 'login') !== false) {
                        $actionIcon = 'bi-box-arrow-in-right';
                    } elseif (strpos(strtolower($l['action']), 'logout') !== false) {
                        $actionIcon = 'bi-box-arrow-left';
                    } elseif (strpos(strtolower($l['action']), 'delete') !== false) {
                        $actionIcon = 'bi-trash';
                    } elseif (strpos(strtolower($l['action']), 'update') !== false || strpos(strtolower($l['action']), 'edit') !== false) {
                        $actionIcon = 'bi-pencil';
                    } elseif (strpos(strtolower($l['action']), 'add') !== false || strpos(strtolower($l['action']), 'create') !== false) {
                        $actionIcon = 'bi-plus-circle';
                    } else {
                        $actionIcon = 'bi-circle';
                    }
                    
                    $roleClass = strtolower($l['role']);
                    ?>
                    <tr>
                        <td data-label="#"><?= $offset + $i + 1 ?></td>
                        <td data-label="User">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg, #2563eb, #4f46e5);color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:600;flex-shrink:0;">
                                    <?= strtoupper(substr($l['username'], 0, 1)) ?>
                                </div>
                                <strong><?= htmlspecialchars($l['username']) ?></strong>
                            </div>
                        </td>
                        <td data-label="Role">
                            <span class="role-badge <?= $roleClass ?>">
                                <i class="bi bi-shield-check"></i> <?= ucfirst($l['role']) ?>
                            </span>
                        </td>
                        <td data-label="Action">
                            <span class="action-text">
                                <i class="bi <?= $actionIcon ?> action-icon"></i>
                                <?= htmlspecialchars($l['action']) ?>
                            </span>
                        </td>
                        <td data-label="IP Address">
                            <span class="ip-address">
                                <i class="bi bi-wifi"></i> <?= htmlspecialchars($l['ip']) ?>
                            </span>
                        </td>
                        <td data-label="Time">
                            <span class="timestamp">
                                <i class="bi bi-clock"></i>
                                <?= date('M d, Y H:i:s', strtotime($l['created_at'])) ?>
                            </span>
                        </td>
                        <td data-label="Actions">
                            <div class="action-btns">
                                <a href="#" 
                                   class="btn-action btn-info" 
                                   data-bs-toggle="tooltip" 
                                   title="View details of this log"
                                   onclick="viewLogDetails(<?= htmlspecialchars(json_encode($l)) ?>); return false;">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                <?php if (strpos(strtolower($l['action']), 'delete') === false): ?>
                                <a href="#" 
                                   class="btn-action btn-warning" 
                                   data-bs-toggle="tooltip" 
                                   title="Edit this log entry"
                                   onclick="editLogEntry(<?= $l['id'] ?? 0 ?>); return false;">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <?php endif; ?>
                                <a href="#" 
                                   class="btn-action btn-danger" 
                                   data-bs-toggle="tooltip" 
                                   title="Delete this log entry"
                                   onclick="confirmDeleteLog(<?= $l['id'] ?? 0 ?>); return false;">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (!$logs): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state-custom">
                                <i class="bi bi-clock-history"></i>
                                <p><?= $search ? 'No logs found matching your search.' : 'No activity logs recorded yet.' ?></p>
                                <?php if ($search): ?>
                                <a href="activity_logs.php" class="btn btn-primary btn-sm mt-2">
                                    <i class="bi bi-x"></i> Clear Search
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div class="pagination-custom">
            <div class="page-info">
                <i class="bi bi-info-circle"></i> 
                Showing <?= $offset + 1 ?> – <?= min($offset + $limit, $total) ?> of <strong><?= $total ?></strong> records
            </div>
            <div class="page-buttons">
                <?php if ($page > 1): ?>
                <a href="?p=<?= $page - 1 ?>&q=<?= urlencode($search) ?>" class="page-btn">
                    <i class="bi bi-chevron-left"></i> Prev
                </a>
                <?php endif; ?>
                
                <?php 
                $start = max(1, $page - 2);
                $end = min($pages, $page + 2);
                
                if ($start > 1): ?>
                <a href="?p=1&q=<?= urlencode($search) ?>" class="page-btn">1</a>
                <?php if ($start > 2): ?>
                <span class="page-btn disabled">…</span>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start; $i <= $end; $i++): ?>
                <a href="?p=<?= $i ?>&q=<?= urlencode($search) ?>" 
                   class="page-btn <?= $i === $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
                
                <?php if ($end < $pages): ?>
                <?php if ($end < $pages - 1): ?>
                <span class="page-btn disabled">…</span>
                <?php endif; ?>
                <a href="?p=<?= $pages ?>&q=<?= urlencode($search) ?>" class="page-btn"><?= $pages ?></a>
                <?php endif; ?>
                
                <?php if ($page < $pages): ?>
                <a href="?p=<?= $page + 1 ?>&q=<?= urlencode($search) ?>" class="page-btn">
                    Next <i class="bi bi-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- ==========================================================
     MODAL FOR LOG DETAILS
     ========================================================== -->
<div class="modal fade" id="logDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-info-circle me-2" style="color:#2563eb;"></i>
                    Log Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="logDetailBody">
                <!-- Dynamic content -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================
     JAVASCRIPT ENHANCEMENTS
     ========================================================== -->
<script>
// ==========================================================
// LOG DETAIL VIEW
// ==========================================================
function viewLogDetails(logData) {
    const modal = document.getElementById('logDetailModal');
    const body = document.getElementById('logDetailBody');
    
    if (!modal || !body) return;
    
    const html = `
        <div style="display:grid;gap:12px;">
            <div style="display:flex;justify-content:space-between;padding:10px 14px;background:#f8fafc;border-radius:8px;">
                <span style="color:#64748b;font-weight:500;">ID</span>
                <span style="font-weight:600;">#${logData.id || 'N/A'}</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:10px 14px;background:#f8fafc;border-radius:8px;">
                <span style="color:#64748b;font-weight:500;">User</span>
                <span style="font-weight:600;">${logData.username || 'N/A'}</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:10px 14px;background:#f8fafc;border-radius:8px;">
                <span style="color:#64748b;font-weight:500;">Role</span>
                <span style="font-weight:600;">${logData.role || 'N/A'}</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:10px 14px;background:#f8fafc;border-radius:8px;">
                <span style="color:#64748b;font-weight:500;">Action</span>
                <span style="font-weight:600;">${logData.action || 'N/A'}</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:10px 14px;background:#f8fafc;border-radius:8px;">
                <span style="color:#64748b;font-weight:500;">IP Address</span>
                <span style="font-weight:600;font-family:monospace;">${logData.ip || 'N/A'}</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:10px 14px;background:#f8fafc;border-radius:8px;">
                <span style="color:#64748b;font-weight:500;">Timestamp</span>
                <span style="font-weight:600;">${logData.created_at || 'N/A'}</span>
            </div>
        </div>
    `;
    
    body.innerHTML = html;
    
    // Show modal using Bootstrap
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
    }
}

// ==========================================================
// EDIT LOG ENTRY
// ==========================================================
function editLogEntry(logId) {
    if (!logId) {
        alert('Invalid log entry ID.');
        return;
    }
    
    // Redirect to edit page or show edit modal
    // For demo purposes, show alert
    alert(`Edit functionality for log #${logId}\n\nThis would typically open an edit form or redirect to an edit page.\n\nYou can implement custom edit logic here.`);
}

// ==========================================================
// DELETE LOG ENTRY
// ==========================================================
function confirmDeleteLog(logId) {
    if (!logId) {
        alert('Invalid log entry ID.');
        return;
    }
    
    if (confirm('Are you sure you want to delete this log entry? This action cannot be undone.')) {
        // Redirect to delete endpoint
        window.location.href = `activity_logs.php?delete=${logId}`;
    }
}

// ==========================================================
// INITIALIZATION
// ==========================================================
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            new bootstrap.Tooltip(el);
        }
    });
    
    // Table search highlight
    const searchInput = document.querySelector('.search-bar-custom input[name="q"]');
    if (searchInput && searchInput.value) {
        const searchTerm = searchInput.value.toLowerCase();
        const table = document.querySelector('.logs-table tbody');
        if (table) {
            const rows = table.querySelectorAll('tr');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.background = '#fef3c7';
                    setTimeout(() => {
                        row.style.transition = 'background 1s ease';
                        row.style.background = 'transparent';
                    }, 2000);
                }
            });
        }
    }
    
    // Auto-dismiss any alerts
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});
</script>

<?php include 'includes/footer.php'; ?>