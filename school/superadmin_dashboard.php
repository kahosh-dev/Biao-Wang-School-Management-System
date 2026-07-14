<?php
require_once 'functions.php';
require_role('superadmin');

// ── Stats ──────────────────────────────────────────────────────────
$totalUsers    = db()->query("SELECT COUNT(*) total FROM users")->fetch_assoc()['total'];
$totalStudents = db()->query("SELECT COUNT(*) total FROM students")->fetch_assoc()['total'];
$totalMarks    = db()->query("SELECT COUNT(*) total FROM marks")->fetch_assoc()['total'];
$totalSubjects = db()->query("SELECT COUNT(*) total FROM subjects")->fetch_assoc()['total'];
$totalAdmins   = db()->query("SELECT COUNT(*) total FROM users WHERE role IN ('admin','manager')")->fetch_assoc()['total'];

// ── Recent Users ───────────────────────────────────────────────────
$recentUsers = db()->query(
    "SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 6"
)->fetch_all(MYSQLI_ASSOC);

// ── Recent Activity ────────────────────────────────────────────────
$recentLogs = db()->query(
    "SELECT u.username, u.role, al.action, al.created_at
     FROM activity_logs al JOIN users u ON u.id=al.user_id
     ORDER BY al.created_at DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

$page_title    = 'Super Admin Dashboard';
$page_subtitle = 'System Overview & Management';
include 'includes/header.php';
?>

<!-- ==========================================================
     SUPER ADMIN DASHBOARD CUSTOM STYLES
     ========================================================== -->
<style>
/* Super Admin Dashboard Specific Styles */
.superadmin-wrapper {
    padding: 10px 0;
}

/* Page Header */
.superadmin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}

.superadmin-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0;
}

.superadmin-header h1 .header-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    box-shadow: 0 4px 15px rgba(124, 58, 237, 0.25);
}

.superadmin-header .header-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.superadmin-header .header-actions .btn {
    padding: 8px 18px;
    border-radius: 10px;
    font-weight: 500;
    font-size: 13px;
    transition: 0.3s;
    font-family: 'Poppins', sans-serif;
}

.superadmin-header .header-actions .btn:hover {
    transform: translateY(-2px);
}

/* Enhanced Stat Cards */
.stat-cards-super {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card-super {
    background: #fff;
    border-radius: 16px;
    padding: 22px 24px;
    display: flex;
    gap: 16px;
    align-items: center;
    box-shadow: var(--shadow, 0 8px 25px rgba(0,0,0,0.08));
    transition: 0.3s;
    border: 1px solid rgba(0,0,0,0.03);
    position: relative;
    overflow: hidden;
}

.stat-card-super::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--stat-color), var(--stat-color-light));
}

.stat-card-super:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 35px rgba(0,0,0,0.12);
}

.stat-card-super .stat-icon-super {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 24px;
    flex-shrink: 0;
}

.stat-card-super .stat-icon-super.blue {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    --stat-color: #2563eb;
    --stat-color-light: #4f46e5;
}

.stat-card-super .stat-icon-super.green {
    background: linear-gradient(135deg, #16a34a, #22c55e);
    --stat-color: #16a34a;
    --stat-color-light: #22c55e;
}

.stat-card-super .stat-icon-super.orange {
    background: linear-gradient(135deg, #ea580c, #f97316);
    --stat-color: #ea580c;
    --stat-color-light: #f97316;
}

.stat-card-super .stat-icon-super.purple {
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
    --stat-color: #7c3aed;
    --stat-color-light: #8b5cf6;
}

.stat-card-super .stat-icon-super.pink {
    background: linear-gradient(135deg, #db2777, #ec4899);
    --stat-color: #db2777;
    --stat-color-light: #ec4899;
}

.stat-card-super .stat-info-super {
    flex: 1;
    min-width: 0;
}

.stat-card-super .stat-info-super .stat-label {
    font-size: 12px;
    color: #64748b;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.stat-card-super .stat-info-super .stat-value {
    font-size: 30px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}

.stat-card-super .stat-info-super .stat-desc {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 2px;
}

.stat-card-super .stat-trend {
    position: absolute;
    top: 12px;
    right: 12px;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 10px;
    border-radius: 20px;
}

.stat-card-super .stat-trend.up {
    background: #dcfce7;
    color: #166534;
}

.stat-card-super .stat-trend.down {
    background: #fee2e2;
    color: #991b1b;
}

/* Quick Stats */
.quick-stat {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.quick-stat:last-child {
    border-bottom: none;
}

.quick-stat .qs-label {
    color: #64748b;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.quick-stat .qs-label i {
    color: #7c3aed;
    font-size: 16px;
}

.quick-stat .qs-val {
    font-weight: 600;
    font-size: 16px;
    color: #0f172a;
}

/* Activity List */
.activity-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.activity-list li {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    transition: 0.2s;
}

.activity-list li:hover {
    background: #f8fafc;
}

.activity-list li:last-child {
    border-bottom: none;
}

.activity-list .activity-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
    color: #fff;
    flex-shrink: 0;
}

.activity-list .activity-content {
    flex: 1;
}

.activity-list .activity-content .activity-user {
    font-weight: 600;
    font-size: 14px;
    color: #0f172a;
}

.activity-list .activity-content .activity-action {
    font-size: 13px;
    color: #475569;
}

.activity-list .activity-content .activity-time {
    font-size: 11px;
    color: #94a3b8;
}

.activity-list .activity-role-badge {
    font-size: 10px;
    padding: 2px 10px;
    border-radius: 20px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.activity-list .activity-role-badge.superadmin {
    background: #fef3c7;
    color: #92400e;
}

.activity-list .activity-role-badge.admin {
    background: #dbeafe;
    color: #1e40af;
}

.activity-list .activity-role-badge.manager {
    background: #d1fae5;
    color: #065f46;
}

.activity-list .activity-role-badge.student {
    background: #fce4ec;
    color: #9a1f3d;
}

/* Role Badges */
.role-badge-super {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 30px;
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.role-badge-super.superadmin {
    background: #fef3c7;
    color: #92400e;
}

.role-badge-super.admin {
    background: #dbeafe;
    color: #1e40af;
}

.role-badge-super.manager {
    background: #d1fae5;
    color: #065f46;
}

.role-badge-super.student {
    background: #fce4ec;
    color: #9a1f3d;
}

/* Empty State */
.empty-state-super {
    text-align: center;
    padding: 40px 20px;
    color: #94a3b8;
}

.empty-state-super i {
    font-size: 48px;
    display: block;
    margin-bottom: 16px;
    opacity: 0.4;
}

.empty-state-super p {
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

.stat-card-super {
    animation: fadeSlideUp 0.5s ease forwards;
}

.stat-card-super:nth-child(2) { animation-delay: 0.1s; }
.stat-card-super:nth-child(3) { animation-delay: 0.2s; }
.stat-card-super:nth-child(4) { animation-delay: 0.3s; }

.card {
    animation: fadeSlideUp 0.5s ease forwards;
}

.card:nth-child(2) { animation-delay: 0.1s; }

/* Responsive */
@media (max-width: 992px) {
    .superadmin-header h1 {
        font-size: 24px;
    }
}

@media (max-width: 768px) {
    .superadmin-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    
    .superadmin-header .header-actions {
        justify-content: stretch;
    }
    
    .superadmin-header .header-actions .btn {
        flex: 1;
        text-align: center;
    }
    
    .stat-cards-super {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .stat-card-super {
        padding: 16px;
    }
    
    .stat-card-super .stat-info-super .stat-value {
        font-size: 24px;
    }
}

@media (max-width: 480px) {
    .stat-cards-super {
        grid-template-columns: 1fr;
    }
    
    .superadmin-header h1 {
        font-size: 20px;
    }
    
    .superadmin-header h1 .header-icon {
        width: 40px;
        height: 40px;
        font-size: 18px;
    }
}

/* Print Styles */
@media print {
    .superadmin-header .header-actions {
        display: none;
    }
    
    .stat-card-super {
        border: 1px solid #ddd;
        box-shadow: none;
    }
    
    .stat-card-super:hover {
        transform: none;
        box-shadow: none;
    }
    
    .stat-card-super::before {
        display: none;
    }
}
</style>




<!-- ==========================================================
     STATISTICS CARDS
     ========================================================== -->
<div class="stat-cards-super">
    <div class="stat-card-super">
        <div class="stat-icon-super blue">
            <i class="bi bi-people-fill"></i>
        </div>
        <div class="stat-info-super">
            <div class="stat-label">Total Users</div>
            <div class="stat-value" data-counter="<?= (int)$totalUsers ?>"><?= (int)$totalUsers ?></div>
            <div class="stat-desc">Registered accounts</div>
        </div>
        <?php if ($totalUsers > 0): ?>
        <span class="stat-trend up"><i class="bi bi-arrow-up"></i> Active</span>
        <?php endif; ?>
    </div>

    <div class="stat-card-super">
        <div class="stat-icon-super green">
            <i class="bi bi-person-graduation"></i>
        </div>
        <div class="stat-info-super">
            <div class="stat-label">Students</div>
            <div class="stat-value" data-counter="<?= (int)$totalStudents ?>"><?= (int)$totalStudents ?></div>
            <div class="stat-desc">Enrolled students</div>
        </div>
        <?php if ($totalStudents > 0): ?>
        <span class="stat-trend up"><i class="bi bi-arrow-up"></i> <?= round(($totalStudents / max($totalUsers, 1)) * 100) ?>%</span>
        <?php endif; ?>
    </div>

    <div class="stat-card-super">
        <div class="stat-icon-super orange">
            <i class="bi bi-journal-check"></i>
        </div>
        <div class="stat-info-super">
            <div class="stat-label">Marks Recorded</div>
            <div class="stat-value" data-counter="<?= (int)$totalMarks ?>"><?= (int)$totalMarks ?></div>
            <div class="stat-desc">Across all terms</div>
        </div>
        <?php if ($totalMarks > 0): ?>
        <span class="stat-trend up"><i class="bi bi-arrow-up"></i> Recorded</span>
        <?php endif; ?>
    </div>

    <div class="stat-card-super">
        <div class="stat-icon-super purple">
            <i class="bi bi-book-fill"></i>
        </div>
        <div class="stat-info-super">
            <div class="stat-label">Subjects</div>
            <div class="stat-value" data-counter="<?= (int)$totalSubjects ?>"><?= (int)$totalSubjects ?></div>
            <div class="stat-desc">Active subjects</div>
        </div>
        <?php if ($totalSubjects > 0): ?>
        <span class="stat-trend up"><i class="bi bi-arrow-up"></i> Active</span>
        <?php endif; ?>
    </div>
</div>

<!-- ==========================================================
     MAIN CONTENT GRID
     ========================================================== -->
<div class="row g-4">

    <!-- Recent Users Table -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <div class="card-icon"><i class="bi bi-people"></i></div>
                Recent Users
                <div class="ms-auto d-flex gap-2">
                    <span style="font-size:12px;color:#94a3b8;">
                        <i class="bi bi-clock"></i> Last 6 registered
                    </span>
                    <a href="manage_users.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-arrow-right"></i> View All
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th style="width:100px;">Role</th>
                                <th style="width:120px;">Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $i => $u): ?>
                            <?php 
                            $roleClass = strtolower($u['role']);
                            ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar" style="width:32px;height:32px;font-size:.75rem;flex-shrink:0;">
                                            <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                        </div>
                                        <strong><?= htmlspecialchars($u['username']) ?></strong>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($u['email'] ?? '—') ?></td>
                                <td>
                                    <span class="role-badge-super <?= $roleClass ?>">
                                        <?= ucfirst($u['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <i class="bi bi-calendar3" style="color:#94a3b8;font-size:12px;"></i>
                                    <?= date('d M Y', strtotime($u['created_at'])) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (!$recentUsers): ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state-super">
                                        <i class="bi bi-people"></i>
                                        <p>No users found.</p>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div><!-- /col -->

    <!-- Sidebar -->
    <div class="col-lg-4">

        <!-- Quick Stats -->
        <div class="card mb-4">
            <div class="card-header">
                <div class="card-icon"><i class="bi bi-lightning-fill"></i></div>
                Quick Stats
            </div>
            <div class="card-body">
                <div class="quick-stat">
                    <span class="qs-label"><i class="bi bi-people"></i>Total Users</span>
                    <span class="qs-val"><?= $totalUsers ?></span>
                </div>
                <div class="quick-stat">
                    <span class="qs-label"><i class="bi bi-person-graduation"></i>Students</span>
                    <span class="qs-val"><?= $totalStudents ?></span>
                </div>
                <div class="quick-stat">
                    <span class="qs-label"><i class="bi bi-shield-check"></i>Admins</span>
                    <span class="qs-val"><?= $totalAdmins ?></span>
                </div>
                <div class="quick-stat">
                    <span class="qs-label"><i class="bi bi-journal-check"></i>Marks</span>
                    <span class="qs-val"><?= $totalMarks ?></span>
                </div>
                <div class="quick-stat mb-0">
                    <span class="qs-label"><i class="bi bi-book"></i>Subjects</span>
                    <span class="qs-val"><?= $totalSubjects ?></span>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <div class="card-icon"><i class="bi bi-activity"></i></div>
                Recent Activity
                <a href="activity_logs.php" class="btn btn-sm btn-outline-primary ms-auto">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <?php if ($recentLogs): ?>
                <ul class="activity-list">
                    <?php foreach ($recentLogs as $log): ?>
                    <li>
                        <div class="d-flex align-items-start gap-3">
                            <div class="activity-avatar" style="background: linear-gradient(135deg, #7c3aed, #8b5cf6);">
                                <?= strtoupper(substr($log['username'], 0, 1)) ?>
                            </div>
                            <div class="activity-content">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="activity-user"><?= htmlspecialchars($log['username']) ?></span>
                                    <span class="activity-role-badge <?= strtolower($log['role']) ?>">
                                        <?= ucfirst($log['role']) ?>
                                    </span>
                                </div>
                                <div class="activity-action">
                                    <i class="bi bi-dot" style="color:#7c3aed;"></i>
                                    <?= htmlspecialchars($log['action']) ?>
                                </div>
                                <div class="activity-time">
                                    <i class="bi bi-clock"></i>
                                    <?= date('d M Y, H:i', strtotime($log['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="empty-state-super py-4">
                    <i class="bi bi-activity"></i>
                    <p>No activity recorded yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- System Info -->
        <div class="card mt-4">
            <div class="card-header">
                <div class="card-icon"><i class="bi bi-info-circle"></i></div>
                System Info
            </div>
            <div class="card-body">
                <div class="quick-stat">
                    <span class="qs-label"><i class="bi bi-hdd"></i>PHP Version</span>
                    <span class="qs-val" style="font-size:13px;"><?= phpversion() ?></span>
                </div>
                <div class="quick-stat">
                    <span class="qs-label"><i class="bi bi-database"></i>DB Driver</span>
                    <span class="qs-val" style="font-size:13px;">MySQL</span>
                </div>
                <div class="quick-stat mb-0">
                    <span class="qs-label"><i class="bi bi-clock"></i>Server Time</span>
                    <span class="qs-val" style="font-size:13px;"><?= date('H:i:s') ?></span>
                </div>
            </div>
        </div>

    </div><!-- /col -->

</div><!-- /row -->

<!-- ==========================================================
     JAVASCRIPT ENHANCEMENTS
     ========================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Counter animation for stat cards
    const counters = document.querySelectorAll('[data-counter]');
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-counter'));
        if (target > 0) {
            const duration = 1000;
            const step = Math.ceil(target / (duration / 16));
            let current = 0;
            
            const updateCounter = () => {
                current += step;
                if (current >= target) {
                    counter.textContent = target;
                    return;
                }
                counter.textContent = current;
                requestAnimationFrame(updateCounter);
            };
            
            // Start animation when element is visible
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        updateCounter();
                        observer.disconnect();
                    }
                });
            });
            observer.observe(counter);
        }
    });
    
    // Auto-refresh activity every 30 seconds (optional)
    // Uncomment to enable auto-refresh
    /*
    setTimeout(function() {
        location.reload();
    }, 30000);
    */
    
    // Tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            new bootstrap.Tooltip(el);
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>