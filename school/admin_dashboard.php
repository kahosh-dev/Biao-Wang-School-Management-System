<?php
require_once 'functions.php';
require_role(['admin','manager','superadmin']);

// ── Load students list with username ──────────────────────────
$search = sanitize($_GET['q'] ?? '');

if ($search) {
    $res = db_prepare_execute(
        "SELECT s.*, u.username FROM students s JOIN users u ON u.id=s.user_id
         WHERE s.name LIKE ? OR s.reg_no LIKE ? OR u.username LIKE ?
         ORDER BY s.name",
        'sss', ["%$search%", "%$search%", "%$search%"]
    )->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $res = db()->query(
        "SELECT s.*, u.username FROM students s JOIN users u ON u.id=s.user_id ORDER BY s.name"
    )->fetch_all(MYSQLI_ASSOC);
}

$totalStudents = db()->query("SELECT COUNT(*) c FROM students")->fetch_assoc()['c'];
$totalMarks    = db()->query("SELECT COUNT(*) c FROM marks")->fetch_assoc()['c'];

$page_title    = 'Admin Dashboard';
$page_subtitle = 'Student Records';
include 'includes/header.php';
?>

<!-- ==========================================================
     ADMIN DASHBOARD CUSTOM STYLES
     ========================================================== -->
<style>
/* Admin Dashboard Specific Styles */
.admin-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.admin-stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    display: flex;
    gap: 18px;
    align-items: center;
    box-shadow: var(--shadow, 0 8px 25px rgba(0,0,0,0.08));
    transition: 0.3s;
    border: 1px solid rgba(0,0,0,0.03);
}

.admin-stat-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 35px rgba(0,0,0,0.12);
}

.admin-stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 28px;
    flex-shrink: 0;
}

.admin-stat-icon.blue {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
}

.admin-stat-icon.orange {
    background: linear-gradient(135deg, #ea580c, #f97316);
}

.admin-stat-icon.green {
    background: linear-gradient(135deg, #16a34a, #22c55e);
}

.admin-stat-icon.purple {
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
}

.admin-stat-icon.cyan {
    background: linear-gradient(135deg, #0891b2, #06b6d4);
}

.admin-stat-icon.pink {
    background: linear-gradient(135deg, #db2777, #ec4899);
}

.admin-stat-info {
    flex: 1;
}

.admin-stat-label {
    font-size: 13px;
    color: var(--muted, #64748b);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.admin-stat-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--text, #1e293b);
    line-height: 1.2;
}

.admin-stat-desc {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 2px;
}

/* Toolbar */
.admin-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
    padding: 16px 20px;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e9edf2;
}

.admin-search {
    position: relative;
    flex: 1;
    max-width: 420px;
}

.admin-search i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 16px;
}

.admin-search input {
    width: 100%;
    padding: 10px 16px 10px 42px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    background: white;
    font-size: 14px;
    transition: 0.3s;
    font-family: "Poppins", sans-serif;
}

.admin-search input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.admin-search input::placeholder {
    color: #94a3b8;
}

.admin-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-admin {
    padding: 8px 18px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 13px;
    transition: 0.3s;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-family: "Poppins", sans-serif;
}

.btn-admin-primary {
    background: #2563eb;
    color: white;
}

.btn-admin-primary:hover {
    background: #1d4ed8;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
}

.btn-admin-light {
    background: white;
    color: #1e293b;
    border: 1px solid #d1d5db;
}

.btn-admin-light:hover {
    background: #f1f5f9;
    transform: translateY(-2px);
}

.btn-admin-success {
    background: #16a34a;
    color: white;
}

.btn-admin-success:hover {
    background: #15803d;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(22, 163, 74, 0.3);
}

.btn-admin-info {
    background: #0891b2;
    color: white;
}

.btn-admin-info:hover {
    background: #0e7490;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(8, 145, 178, 0.3);
}

.btn-admin-danger {
    background: #dc2626;
    color: white;
}

.btn-admin-danger:hover {
    background: #b91c1c;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
}

/* Table Enhancements */
.admin-table {
    font-size: 0.88rem;
}

.admin-table thead {
    background: #f1f5f9;
    color: #1e293b;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.admin-table thead th {
    padding: 14px 16px;
    font-weight: 600;
    border-bottom: 2px solid #e9edf2;
}

.admin-table tbody td {
    padding: 14px 16px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}

.admin-table tbody tr {
    transition: 0.2s;
}

.admin-table tbody tr:hover {
    background: #f8fafc;
}

.admin-table .student-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    flex-shrink: 0;
}

.admin-table .student-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: #1e293b;
}

.admin-table .reg-badge {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 30px;
    font-size: 0.75rem;
    font-weight: 600;
    background: #dbeafe;
    color: #1e40af;
    letter-spacing: 0.3px;
}

.admin-table .class-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 30px;
    font-size: 0.75rem;
    font-weight: 600;
    background: #f1f5f9;
    color: #475569;
}

.admin-table .action-buttons {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.admin-table .action-btn {
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    border: none;
    transition: 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-family: "Poppins", sans-serif;
    text-decoration: none;
}

.admin-table .action-btn-primary {
    background: #2563eb;
    color: white;
}

.admin-table .action-btn-primary:hover {
    background: #1d4ed8;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.admin-table .action-btn-info {
    background: #0891b2;
    color: white;
}

.admin-table .action-btn-info:hover {
    background: #0e7490;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(8, 145, 178, 0.3);
}

.admin-table .action-btn-danger {
    background: #dc2626;
    color: white;
}

.admin-table .action-btn-danger:hover {
    background: #b91c1c;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

/* Empty State */
.admin-empty {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
}

.admin-empty i {
    font-size: 56px;
    display: block;
    margin-bottom: 16px;
    opacity: 0.5;
}

.admin-empty p {
    font-size: 16px;
    margin: 0;
}

/* Quick Actions Card */
.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.quick-action-item {
    background: #f8fafc;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
    transition: 0.3s;
    border: 1px solid #e9edf2;
    text-decoration: none;
    color: #1e293b;
}

.quick-action-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    border-color: #2563eb;
    background: white;
}

.quick-action-item i {
    font-size: 32px;
    color: #2563eb;
    display: block;
    margin-bottom: 8px;
}

.quick-action-item span {
    font-size: 14px;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
    .admin-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .admin-search {
        max-width: 100%;
    }
    
    .admin-actions {
        justify-content: stretch;
    }
    
    .admin-actions .btn-admin {
        flex: 1;
        justify-content: center;
    }
    
    .admin-table .action-buttons {
        flex-direction: column;
    }
    
    .admin-table .action-btn {
        justify-content: center;
    }
}

/* Animation */
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

.admin-stat-card {
    animation: fadeSlideUp 0.5s ease forwards;
}

.admin-stat-card:nth-child(2) { animation-delay: 0.1s; }
.admin-stat-card:nth-child(3) { animation-delay: 0.2s; }
.admin-stat-card:nth-child(4) { animation-delay: 0.3s; }

/* Print Styles */
@media print {
    .admin-toolbar { display: none; }
    .admin-stat-card { box-shadow: none; border: 1px solid #ddd; }
    .admin-stat-card:hover { transform: none; }
    .admin-table tbody tr:hover { background: transparent; }
    .admin-table .action-btn { display: none; }
}
</style>

<!-- ==========================================================
     STATISTICS CARDS
     ========================================================== -->
<div class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-icon blue">
            <i class="bi bi-person-graduation"></i>
        </div>
        <div class="admin-stat-info">
            <div class="admin-stat-label">Total Students</div>
            <div class="admin-stat-value" data-counter="<?= (int)$totalStudents ?>">
                <?= (int)$totalStudents ?>
            </div>
            <div class="admin-stat-desc">Enrolled Students</div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon green">
            <i class="bi bi-journal-check"></i>
        </div>
        <div class="admin-stat-info">
            <div class="admin-stat-label">Marks Recorded</div>
            <div class="admin-stat-value" data-counter="<?= (int)$totalMarks ?>">
                <?= (int)$totalMarks ?>
            </div>
            <div class="admin-stat-desc">Across all subjects</div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon cyan">
            <i class="bi bi-search"></i>
        </div>
        <div class="admin-stat-info">
            <div class="admin-stat-label">Search Results</div>
            <div class="admin-stat-value"><?= count($res) ?></div>
            <div class="admin-stat-desc">
                <?= $search ? 'Matching "' . htmlspecialchars($search) . '"' : 'All students' ?>
            </div>
        </div>
    </div>
    
    <div class="admin-stat-card">
        <div class="admin-stat-icon purple">
            <i class="bi bi-shield-check"></i>
        </div>
        <div class="admin-stat-info">
            <div class="admin-stat-label">Your Role</div>
            <div class="admin-stat-value" style="font-size:1.2rem">
                <?= ucfirst(current_role()) ?>
            </div>
            <div class="admin-stat-desc">Access Level</div>
        </div>
    </div>
</div>

<!-- ==========================================================
     STUDENTS TABLE
     ========================================================== -->
<div class="card">
    <div class="card-header">
        <div class="card-icon"><i class="bi bi-person-graduation"></i></div>
        Student Records
        <span class="badge badge-primary ms-2"><?= count($res) ?></span>
        
        <?php if ($search): ?>
        <span class="badge badge-info ms-2">
            <i class="bi bi-search"></i> <?= htmlspecialchars($search) ?>
        </span>
        <?php endif; ?>
    </div>
    <div class="card-body">
        
        <!-- Toolbar -->
        <div class="admin-toolbar">
            <form method="GET" class="admin-search" style="flex:1;max-width:420px;margin:0;">
                <i class="bi bi-search"></i>
                <input type="text" name="q" id="tableSearch" 
                       placeholder="Search students by name, reg no, username…"
                       value="<?= htmlspecialchars($search) ?>"
                       autofocus="<?= $search ? 'autofocus' : '' ?>">
            </form>
            
            <div class="admin-actions">
                <button type="submit" form="searchForm" class="btn-admin btn-admin-primary">
                    <i class="bi bi-search"></i> Search
                </button>
                
                <?php if ($search): ?>
                <a href="admin_dashboard.php" class="btn-admin btn-admin-light">
                    <i class="bi bi-x"></i> Clear
                </a>
                <?php endif; ?>
                
               
            </div>
        </div>
        
        <!-- Hidden form for search submission -->
        <form id="searchForm" method="GET" style="display:none;"></form>
        
        <!-- Table -->
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Reg Number</th>
                        <th>Class</th>
                        <th>Username</th>
                        <th style="min-width:200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($res as $i => $row): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="student-avatar">
                                    <?= strtoupper(substr($row['name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="student-name">
                                        <?= htmlspecialchars($row['name']) ?>
                                    </div>
                                    <?php if ($row['email'] ?? false): ?>
                                    <div style="font-size:0.75rem;color:#94a3b8;">
                                        <i class="bi bi-envelope"></i> <?= htmlspecialchars($row['email']) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="reg-badge">
                                <i class="bi bi-hash"></i> <?= htmlspecialchars($row['reg_no']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="class-badge">
                                <?= htmlspecialchars($row['class'] ?? '—') ?>
                            </span>
                        </td>
                        <td>
                            <i class="bi bi-person-circle" style="color:#64748b;"></i>
                            <?= htmlspecialchars($row['username']) ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="manage_marks.php?student_id=<?= $row['id'] ?>"
                                   class="action-btn action-btn-primary"
                                   data-bs-toggle="tooltip" 
                                   title="Manage Marks for <?= htmlspecialchars($row['name']) ?>">
                                    <i class="bi bi-journal-pen"></i> Marks
                                </a>
                                <a href="reports.php?student_id=<?= $row['id'] ?>"
                                   class="action-btn action-btn-info"
                                   data-bs-toggle="tooltip"
                                   title="View Report for <?= htmlspecialchars($row['name']) ?>">
                                    <i class="bi bi-bar-chart"></i>
                                </a>
                                <a href="edit_student.php?id=<?= $row['id'] ?>"
                                   class="action-btn action-btn-primary"
                                   data-bs-toggle="tooltip"
                                   title="Edit <?= htmlspecialchars($row['name']) ?>">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (!$res): ?>
                    <tr>
                        <td colspan="6">
                            <div class="admin-empty">
                                <i class="bi bi-people"></i>
                                <p>
                                    <?= $search ? 'No students matched your search.' : 'No students registered yet.' ?>
                                </p>
                                <?php if (!$search): ?>
                                <a href="add_student.php" class="btn-admin btn-admin-success" style="display:inline-flex;margin-top:10px;">
                                    <i class="bi bi-person-plus"></i> Add Your First Student
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Table Footer -->
        <?php if ($res): ?>
        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top" style="font-size:0.85rem;color:#64748b;">
            <span>
                <i class="bi bi-info-circle"></i> 
                Showing <?= count($res) ?> <?= $search ? 'result(s)' : 'students' ?>
            </span>
            <span>
                <i class="bi bi-database"></i> 
                Total: <?= $totalStudents ?> students
            </span>
        </div>
        <?php endif; ?>
        
    </div>
</div>


<!-- ==========================================================
     JAVASCRIPT ENHANCEMENTS
     ========================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit search on input change (with debounce)
    let searchTimeout;
    const searchInput = document.getElementById('tableSearch');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 2 || this.value.length === 0) {
                    this.closest('form').submit();
                }
            }, 500);
        });
    }
    
    // Tooltips
    const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if (tooltipElements.length && typeof bootstrap !== 'undefined') {
        tooltipElements.forEach(el => {
            new bootstrap.Tooltip(el);
        });
    }
    
    // Counter animation (for stat cards)
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
});
</script>

<?php include 'includes/footer.php'; ?>