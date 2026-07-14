<?php
require_once 'functions.php';
require_role(['superadmin']); 

$error   = '';
$success = '';

// ── DELETE ──────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    if ($uid === (int)$_SESSION['user_id']) {
        $error = 'You cannot delete your own account.';
    } else {
        // Check if user has student record before deleting
        $student_check = db_prepare_execute(
            "SELECT id FROM students WHERE user_id = ?",
            'i', [$uid]
        )->get_result()->fetch_assoc();
        
        if ($student_check) {
            // Delete student record first
            db_prepare_execute("DELETE FROM students WHERE user_id = ?", 'i', [$uid]);
        }
        
        // Delete the user
        db_prepare_execute("DELETE FROM users WHERE id=?", 'i', [$uid]);
        log_activity("Deleted user #$uid");
        header("Location: manage_users.php?msg=deleted");
        exit();
    }
}

// ── UPDATE ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    try {
        $uid       = (int)$_POST['user_id'];
        $username  = sanitize($_POST['username']);
        $full_name = sanitize($_POST['full_name']);
        $email     = sanitize($_POST['email']);
        $role      = sanitize($_POST['role']);

        // Validate role based on current user's permissions
        $current_role = current_role();
        $allowed_roles = $current_role === 'superadmin' 
            ? ['superadmin', 'admin', 'manager', 'student'] 
            : ['admin', 'manager', 'student'];
            
        if (!in_array($role, $allowed_roles)) {
            throw new Exception('You do not have permission to assign this role.');
        }

        // Check if username is taken by another user
        $check = db_prepare_execute(
            "SELECT id FROM users WHERE username = ? AND id != ?",
            'si', [$username, $uid]
        )->get_result()->fetch_assoc();
        
        if ($check) {
            throw new Exception('Username already taken by another user.');
        }

        db_prepare_execute(
            "UPDATE users SET username=?, full_name=?, email=?, role=? WHERE id=?",
            'ssssi', [$username, $full_name, $email, $role, $uid]
        );
        
        // If role is student and no student record exists, create one
        if ($role === 'student') {
            $student = db_prepare_execute(
                "SELECT id FROM students WHERE user_id = ?",
                'i', [$uid]
            )->get_result()->fetch_assoc();
            
            if (!$student) {
                $reg_no = 'STU' . str_pad($uid, 4, '0', STR_PAD_LEFT);
                db_prepare_execute(
                    "INSERT INTO students (user_id, name, reg_no, class, created_at) 
                     VALUES (?, ?, ?, ?, NOW())",
                    'isss', [$uid, $full_name ?: $username, $reg_no, 'Not Assigned']
                );
            }
        }
        
        log_activity("Updated user #$uid");
        $success = 'User updated successfully.';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ── SEARCH ──────────────────────────────────────────────────────
$search = sanitize($_GET['q'] ?? '');

if ($search) {
    $users = db_prepare_execute(
        "SELECT u.*, (SELECT COUNT(*) FROM students s WHERE s.user_id=u.id) has_student
         FROM users u 
         WHERE u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?
         ORDER BY u.id DESC",
        'sss', ["%$search%", "%$search%", "%$search%"]
    )->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $users = db()->query(
        "SELECT u.*, (SELECT COUNT(*) FROM students s WHERE s.user_id=u.id) has_student
         FROM users u 
         ORDER BY u.id DESC"
    )->fetch_all(MYSQLI_ASSOC);
}

// Message from redirect
if (isset($_GET['msg']) && $_GET['msg'] === 'deleted' && !$success) {
    $success = 'User deleted successfully.';
}

// Get role options based on current user's role
$current_role = current_role();
$role_options = [];

if ($current_role === 'superadmin') {
    $role_options = [
        'superadmin' => 'Super Admin',
        'admin' => 'Admin',
        'manager' => 'Manager',
        'student' => 'Student'
    ];
} elseif ($current_role === 'manager') {
    $role_options = [
        'admin' => 'Admin',
        'manager' => 'Manager',
        'student' => 'Student'
    ];
} else {
    $role_options = ['student' => 'Student'];
}

$page_title    = 'Manage Users';
$page_subtitle = 'View, edit and remove users';
include 'includes/header.php';
?>

<!-- ==========================================================
     MANAGE USERS CUSTOM STYLES
     ========================================================== -->
<style>
/* Manage Users Specific Styles */
.manage-users-wrapper {
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
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    box-shadow: 0 4px 15px rgba(124, 58, 237, 0.25);
}

.page-header-custom .header-stats {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.page-header-custom .header-stats .stat-badge {
    display: inline-block;
    padding: 6px 18px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 600;
    background: #f1f5f9;
    color: #1e293b;
}

.page-header-custom .header-stats .stat-badge i {
    margin-right: 6px;
    color: #7c3aed;
}

/* Alert Styling */
.alert-custom {
    border-radius: 12px;
    padding: 14px 20px;
    border: none;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.alert-custom .alert-icon {
    font-size: 20px;
}

.alert-custom.alert-success {
    background: #f0fdf4;
    color: #166534;
    border-left: 4px solid #16a34a;
}

.alert-custom.alert-danger {
    background: #fef2f2;
    color: #991b1b;
    border-left: 4px solid #dc2626;
}

/* Search Bar */
.search-bar-custom {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.search-bar-custom .search-input-wrap {
    position: relative;
    min-width: 250px;
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
    padding: 8px 14px 8px 42px;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    font-size: 14px;
    transition: 0.3s;
    background: #fafbfc;
    font-family: 'Poppins', sans-serif;
    height: 40px;
}

.search-bar-custom .search-input-wrap input:focus {
    border-color: #7c3aed;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.08);
    outline: none;
}

.search-bar-custom .search-input-wrap input::placeholder {
    color: #94a3b8;
}

.search-bar-custom .btn-search {
    padding: 8px 20px;
    border-radius: 10px;
    border: none;
    font-weight: 600;
    font-size: 14px;
    transition: 0.3s;
    font-family: 'Poppins', sans-serif;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 40px;
}

.search-bar-custom .btn-search.btn-primary {
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
    color: #fff;
    box-shadow: 0 4px 15px rgba(124, 58, 237, 0.25);
}

.search-bar-custom .btn-search.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(124, 58, 237, 0.35);
}

.search-bar-custom .btn-search.btn-secondary {
    background: #f1f5f9;
    color: #1e293b;
}

.search-bar-custom .btn-search.btn-secondary:hover {
    background: #e2e8f0;
    transform: translateY(-2px);
}

/* User Table */
.users-table {
    font-size: 0.88rem;
}

.users-table thead {
    background: #f1f5f9;
    color: #1e293b;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.users-table thead th {
    padding: 14px 16px;
    font-weight: 600;
    border-bottom: 2px solid #e9edf2;
}

.users-table tbody td {
    padding: 14px 16px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}

.users-table tbody tr {
    transition: 0.2s;
}

.users-table tbody tr:hover {
    background: #f8fafc;
}

.users-table .user-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    color: #fff;
    flex-shrink: 0;
}

.users-table .user-avatar.superadmin {
    background: linear-gradient(135deg, #f59e0b, #d97706);
}

.users-table .user-avatar.admin {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
}

.users-table .user-avatar.manager {
    background: linear-gradient(135deg, #16a34a, #22c55e);
}

.users-table .user-avatar.student {
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
}

.users-table .user-name {
    font-weight: 600;
    font-size: 14px;
    color: #0f172a;
}

.users-table .user-fullname {
    font-size: 12px;
    color: #64748b;
}

/* Role Badges */
.role-badge-custom {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 30px;
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.role-badge-custom.superadmin {
    background: #fef3c7;
    color: #92400e;
}

.role-badge-custom.admin {
    background: #dbeafe;
    color: #1e40af;
}

.role-badge-custom.manager {
    background: #d1fae5;
    color: #065f46;
}

.role-badge-custom.student {
    background: #ede9fe;
    color: #5b21b6;
}

/* Action Buttons */
.action-btns {
    display: flex;
    gap: 6px;
}

.action-btns .btn-action {
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

.action-btns .btn-action.btn-warning {
    background: #fef3c7;
    color: #92400e;
}

.action-btns .btn-action.btn-warning:hover {
    background: #f59e0b;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.action-btns .btn-action.btn-danger {
    background: #fee2e2;
    color: #991b1b;
}

.action-btns .btn-action.btn-danger:hover {
    background: #dc2626;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
}

.action-btns .btn-action.btn-success {
    background: #dcfce7;
    color: #166534;
}

.action-btns .btn-action.btn-success:hover {
    background: #16a34a;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
}

.action-btns .btn-action.btn-light {
    background: #f1f5f9;
    color: #64748b;
}

.action-btns .btn-action.btn-light:hover {
    background: #e2e8f0;
    color: #0f172a;
    transform: translateY(-2px);
}

/* Edit Row */
.edit-row {
    background: #f8fafc;
    border-top: 2px solid #7c3aed;
}

.edit-row td {
    padding: 16px 20px !important;
}

.edit-row .form-label {
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 4px;
}

.edit-row .form-control {
    height: 38px;
    border-radius: 8px;
    border: 1.5px solid #e2e8f0;
    font-size: 13px;
    padding: 0 12px;
    transition: 0.3s;
    font-family: 'Poppins', sans-serif;
    background: #fff;
}

.edit-row .form-control:focus {
    border-color: #7c3aed;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    outline: none;
}

.edit-row .form-select {
    height: 38px;
    border-radius: 8px;
    border: 1.5px solid #e2e8f0;
    font-size: 13px;
    padding: 0 12px;
    font-family: 'Poppins', sans-serif;
    background: #fff;
}

.edit-row .form-select:focus {
    border-color: #7c3aed;
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    outline: none;
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

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.edit-row {
    animation: slideDown 0.3s ease forwards;
}

/* Responsive */
@media (max-width: 992px) {
    .page-header-custom h1 {
        font-size: 24px;
    }
}

@media (max-width: 768px) {
    .page-header-custom {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    
    .page-header-custom .header-stats {
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .page-header-custom .header-stats .stat-badge {
        font-size: 12px;
        padding: 4px 12px;
    }
    
    .search-bar-custom {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-bar-custom .search-input-wrap {
        min-width: 100%;
    }
    
    .search-bar-custom .btn-search {
        justify-content: center;
    }
    
    .users-table thead {
        display: none;
    }
    
    .users-table tbody td {
        display: block;
        padding: 8px 12px;
        border-bottom: none;
    }
    
    .users-table tbody td:before {
        content: attr(data-label);
        font-weight: 600;
        display: inline-block;
        width: 80px;
        color: #64748b;
        font-size: 12px;
    }
    
    .users-table tbody td:last-child {
        border-bottom: 1px solid #f1f5f9;
    }
    
    .users-table tbody tr {
        border-bottom: 2px solid #e9edf2;
        margin-bottom: 12px;
        display: block;
    }
    
    .edit-row .row {
        flex-direction: column;
        gap: 10px;
    }
    
    .edit-row .row > div {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .page-header-custom h1 {
        font-size: 20px;
    }
    
    .page-header-custom h1 .header-icon {
        width: 40px;
        height: 40px;
        font-size: 18px;
    }
}

/* Print Styles */
@media print {
    .search-bar-custom,
    .action-btns,
    .edit-row {
        display: none !important;
    }
    
    .page-header-custom {
        border-bottom: 1px solid #ddd;
        padding-bottom: 15px;
    }
}
</style>

<!-- ==========================================================
     ALERTS
     ========================================================== -->
<?php if ($error): ?>
<div class="alert-custom alert-danger alert-dismissible fade show" role="alert">
    <span class="alert-icon"><i class="bi bi-exclamation-circle-fill"></i></span>
    <?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert-custom alert-success alert-dismissible fade show" role="alert">
    <span class="alert-icon"><i class="bi bi-check-circle-fill"></i></span>
    <?= htmlspecialchars($success) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ==========================================================
     PAGE HEADER
     ========================================================== -->
<div class="page-header-custom">
    <h1>
        <span class="header-icon"><i class="bi bi-people-fill"></i></span>
        Manage Users
    </h1>
    <div class="header-stats">
        <span class="stat-badge">
            <i class="bi bi-people"></i>
            <?= count($users) ?> user<?= count($users) !== 1 ? 's' : '' ?>
        </span>
        <?php if ($search): ?>
        <span class="stat-badge" style="background:#ede9fe;color:#5b21b6;">
            <i class="bi bi-search"></i>
            "<?= htmlspecialchars($search) ?>"
        </span>
        <?php endif; ?>
        
        <!-- ADD USER BUTTON -->
        <a href="add_user.php" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus"></i> Add User
        </a>
    </div>
</div>

<!-- ==========================================================
     USERS TABLE
     ========================================================== -->
<div class="card">
    <div class="card-header">
        <div class="card-icon"><i class="bi bi-people"></i></div>
        User Accounts
        <div class="ms-auto">
            <form method="GET" class="search-bar-custom">
                <div class="search-input-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" name="q" placeholder="Search by username, email or name…"
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <button type="submit" class="btn-search btn-primary">
                    <i class="bi bi-search"></i> Search
                </button>
                <?php if ($search): ?>
                    <a href="manage_users.php" class="btn-search btn-secondary">
                        <i class="bi bi-x"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table users-table mb-0">
                <thead>
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>User</th>
                        <th>Email</th>
                        <th style="width:100px;">Role</th>
                        <th style="width:120px;">Joined</th>
                        <th style="width:100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $i => $u): ?>
                    <?php 
                    $roleClass = strtolower($u['role']);
                    $avatarClass = $roleClass;
                    $isSelf = (int)$u['id'] === (int)$_SESSION['user_id'];
                    ?>
                    <tr id="row-<?= $u['id'] ?>">
                        <td data-label="#"><?= $i + 1 ?></td>
                        <td data-label="User">
                            <div class="d-flex align-items-center gap-2">
                                <div class="user-avatar <?= $avatarClass ?>">
                                    <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="user-name"><?= htmlspecialchars($u['username']) ?></div>
                                    <?php if ($u['full_name']): ?>
                                    <div class="user-fullname">
                                        <i class="bi bi-person"></i> <?= htmlspecialchars($u['full_name']) ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($isSelf): ?>
                                    <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:9px;">
                                        <i class="bi bi-shield-check"></i> You
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td data-label="Email">
                            <?php if ($u['email']): ?>
                            <i class="bi bi-envelope" style="color:#94a3b8;font-size:12px;"></i>
                            <?= htmlspecialchars($u['email']) ?>
                            <?php else: ?>
                            <span style="color:#94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Role">
                            <span class="role-badge-custom <?= $roleClass ?>">
                                <i class="bi bi-shield-check"></i> <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td data-label="Joined">
                            <i class="bi bi-calendar3" style="color:#94a3b8;font-size:12px;"></i>
                            <?= date('d M Y', strtotime($u['created_at'])) ?>
                        </td>
                        <td data-label="Actions">
                            <div class="action-btns">
                                <button type="button"
                                        class="btn-action btn-warning"
                                        data-show-row="edit-<?= $u['id'] ?>"
                                        data-bs-toggle="tooltip" 
                                        title="Edit <?= htmlspecialchars($u['username']) ?>">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <?php if (!$isSelf): ?>
                                <a href="manage_users.php?delete=<?= $u['id'] ?>"
                                   class="btn-action btn-danger"
                                   data-confirm="Delete user '<?= htmlspecialchars($u['username']) ?>'? This cannot be undone."
                                   data-bs-toggle="tooltip" 
                                   title="Delete <?= htmlspecialchars($u['username']) ?>"
                                   onclick="return confirm('Delete user \'<?= htmlspecialchars($u['username']) ?>\'? This cannot be undone.');">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>

                    <!-- Inline edit row -->
                    <tr id="edit-<?= $u['id'] ?>" class="edit-row" style="display:none;">
                        <td colspan="6">
                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label">
                                            <i class="bi bi-person"></i> Username <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" name="username" class="form-control" required
                                               value="<?= htmlspecialchars($u['username']) ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">
                                            <i class="bi bi-person-badge"></i> Full Name
                                        </label>
                                        <input type="text" name="full_name" class="form-control"
                                               value="<?= htmlspecialchars($u['full_name'] ?? '') ?>"
                                               placeholder="Optional">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">
                                            <i class="bi bi-envelope"></i> Email
                                        </label>
                                        <input type="email" name="email" class="form-control"
                                               value="<?= htmlspecialchars($u['email'] ?? '') ?>"
                                               placeholder="Optional">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">
                                            <i class="bi bi-shield-check"></i> Role
                                        </label>
                                        <select name="role" class="form-select">
                                            <?php foreach ($role_options as $value => $label): ?>
                                                <option value="<?= $value ?>" <?= $u['role'] === $value ? 'selected' : '' ?>>
                                                    <?= $label ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="d-flex gap-1">
                                            <button type="submit" name="update_user" class="btn-action btn-success"
                                                    data-bs-toggle="tooltip" title="Save Changes">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button type="button" class="btn-action btn-light" 
                                                    data-show-row="edit-<?= $u['id'] ?>"
                                                    data-bs-toggle="tooltip" title="Cancel">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </td>
                    </tr>

                    <?php endforeach; ?>

                    <?php if (!$users): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state-custom">
                                <i class="bi bi-people"></i>
                                <p>No users found<?= $search ? ' for "' . htmlspecialchars($search) . '"' : '' ?>.</p>
                                <?php if ($search): ?>
                                <a href="manage_users.php" class="btn btn-primary btn-sm">
                                    <i class="bi bi-x"></i> Clear Search
                                </a>
                                <?php else: ?>
                                <a href="add_user.php" class="btn btn-primary btn-sm">
                                    <i class="bi bi-person-plus"></i> Add First User
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>

                </tbody>
            </table>
        </div>
    </div>

    <!-- Table Footer -->
    <?php if ($users): ?>
    <div class="card-footer bg-transparent">
        <div class="d-flex justify-content-between align-items-center" style="font-size:13px;color:#64748b;flex-wrap:wrap;gap:8px;">
            <span>
                <i class="bi bi-info-circle"></i> 
                Showing <strong><?= count($users) ?></strong> user<?= count($users) !== 1 ? 's' : '' ?>
            </span>
            <span>
                <i class="bi bi-shield-check" style="color:#f59e0b;"></i>
                <strong><?= count(array_filter($users, fn($u) => $u['role'] === 'superadmin')) ?></strong> Super Admins
                &nbsp;|&nbsp;
                <i class="bi bi-shield" style="color:#2563eb;"></i>
                <strong><?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?></strong> Admins
                &nbsp;|&nbsp;
                <i class="bi bi-person" style="color:#16a34a;"></i>
                <strong><?= count(array_filter($users, fn($u) => $u['role'] === 'manager')) ?></strong> Managers
                &nbsp;|&nbsp;
                <i class="bi bi-person-graduation" style="color:#7c3aed;"></i>
                <strong><?= count(array_filter($users, fn($u) => $u['role'] === 'student')) ?></strong> Students
            </span>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /card -->

<!-- ==========================================================
     JAVASCRIPT ENHANCEMENTS
     ========================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle edit row visibility
    document.querySelectorAll('[data-show-row]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const rowId = this.getAttribute('data-show-row');
            const row = document.getElementById(rowId);
            if (row) {
                if (row.style.display === 'none') {
                    row.style.display = 'table-row';
                    // Hide any other edit rows
                    document.querySelectorAll('.edit-row').forEach(r => {
                        if (r.id !== rowId) {
                            r.style.display = 'none';
                        }
                    });
                    // Scroll to the edit row
                    setTimeout(() => {
                        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 100);
                } else {
                    row.style.display = 'none';
                }
            }
        });
    });
    
    // Tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            new bootstrap.Tooltip(el);
        }
    });
    
    // Auto-dismiss alerts
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            this.classList.add('was-validated');
        });
    });
    
    // Cancel button - hide edit row
    document.querySelectorAll('.edit-row .btn-light[data-show-row]').forEach(btn => {
        btn.addEventListener('click', function() {
            const rowId = this.getAttribute('data-show-row');
            const row = document.getElementById(rowId);
            if (row) {
                row.style.display = 'none';
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>