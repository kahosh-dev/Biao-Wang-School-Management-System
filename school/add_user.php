<?php
require_once 'functions.php';
require_role(['superadmin']);

$error = '';
$success = '';
$form_data = [
    'username' => '',
    'full_name' => '',
    'email' => '',
    'role' => 'student',
    'password' => '',
    'confirm_password' => ''
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    try {
        // Get and sanitize form data
        $username = sanitize($_POST['username'] ?? '');
        $full_name = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $role = sanitize($_POST['role'] ?? 'student');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validate inputs
        if (empty($username)) {
            throw new Exception('Username is required.');
        }
        if (strlen($username) < 3) {
            throw new Exception('Username must be at least 3 characters.');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            throw new Exception('Username can only contain letters, numbers, and underscores.');
        }
        if (empty($password)) {
            throw new Exception('Password is required.');
        }
        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters.');
        }
        if ($password !== $confirm_password) {
            throw new Exception('Passwords do not match.');
        }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }

        $allowed_roles = ['superadmin', 'admin', 'manager', 'student'];
        if (!in_array($role, $allowed_roles)) {
            throw new Exception('Invalid role selected.');
        }

        // Check if username already exists
        $check = db_prepare_execute(
            "SELECT id FROM users WHERE username = ?",
            's', [$username]
        )->get_result()->fetch_assoc();
        
        if ($check) {
            throw new Exception('Username already exists. Please choose a different username.');
        }

        // Check if email already exists (if provided)
        if (!empty($email)) {
            $check_email = db_prepare_execute(
                "SELECT id FROM users WHERE email = ?",
                's', [$email]
            )->get_result()->fetch_assoc();
            
            if ($check_email) {
                throw new Exception('Email already registered. Please use a different email.');
            }
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $result = db_prepare_execute(
            "INSERT INTO users (username, full_name, email, role, password, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())",
            'sssss', [$username, $full_name, $email, $role, $hashed_password]
        );

        if ($result) {
            $user_id = db()->insert_id;
            log_activity("Added new user: $username (Role: $role)");
            
            // If role is student, also create student record
            if ($role === 'student') {
                // Get student registration number
                $reg_no = 'STU' . str_pad($user_id, 4, '0', STR_PAD_LEFT);
                
                db_prepare_execute(
                    "INSERT INTO students (user_id, name, reg_no, class, created_at) 
                     VALUES (?, ?, ?, ?, NOW())",
                    'isss', [$user_id, $full_name ?: $username, $reg_no, 'Not Assigned']
                );
                log_activity("Created student record for $username");
            }
            
            $success = "User <strong>$username</strong> has been added successfully!";
            
            // Clear form data
            $form_data = [
                'username' => '',
                'full_name' => '',
                'email' => '',
                'role' => 'student',
                'password' => '',
                'confirm_password' => ''
            ];
        } else {
            throw new Exception('Failed to add user. Please try again.');
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
        // Keep form data for re-fill
        $form_data = [
            'username' => $username ?? '',
            'full_name' => $full_name ?? '',
            'email' => $email ?? '',
            'role' => $role ?? 'student',
            'password' => '',
            'confirm_password' => ''
        ];
    }
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

// Get total users count for stats
$total_users = db()->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_students = db()->query("SELECT COUNT(*) as count FROM users WHERE role='student'")->fetch_assoc()['count'];
$total_admins = db()->query("SELECT COUNT(*) as count FROM users WHERE role IN ('admin','manager')")->fetch_assoc()['count'];

$page_title = 'Add New User';
$page_subtitle = 'Create a new user account';
include 'includes/header.php';
?>

<!-- ==========================================================
     ADD USER CUSTOM STYLES
     ========================================================== -->
<style>
/* Add User Specific Styles */
.add-user-wrapper {
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

.page-header-custom .header-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.page-header-custom .header-actions .btn {
    padding: 8px 18px;
    border-radius: 10px;
    font-weight: 500;
    font-size: 13px;
    transition: 0.3s;
    font-family: 'Poppins', sans-serif;
}

.page-header-custom .header-actions .btn:hover {
    transform: translateY(-2px);
}

/* Stats Cards */
.stats-grid-mini {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.stat-mini {
    background: #fff;
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: var(--shadow, 0 4px 15px rgba(0,0,0,0.06));
    border: 1px solid rgba(0,0,0,0.03);
    transition: 0.3s;
}

.stat-mini:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.stat-mini .stat-icon-mini {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
    flex-shrink: 0;
}

.stat-mini .stat-icon-mini.blue {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
}

.stat-mini .stat-icon-mini.green {
    background: linear-gradient(135deg, #16a34a, #22c55e);
}

.stat-mini .stat-icon-mini.purple {
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
}

.stat-mini .stat-icon-mini.orange {
    background: linear-gradient(135deg, #ea580c, #f97316);
}

.stat-mini .stat-info-mini .stat-label {
    font-size: 11px;
    color: #64748b;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.stat-mini .stat-info-mini .stat-value {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
}

/* Form Card */
.form-card {
    max-width: 800px;
    margin: 0 auto;
}

.form-card .card-body {
    padding: 30px;
}

.form-card .form-group {
    margin-bottom: 20px;
}

.form-card .form-label {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.form-card .form-label .required {
    color: #dc2626;
}

.form-card .form-label .optional {
    color: #94a3b8;
    font-weight: 400;
    font-size: 11px;
}

.form-card .form-control {
    height: 44px;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    font-size: 14px;
    padding: 0 14px;
    transition: 0.3s;
    font-family: 'Poppins', sans-serif;
    background: #fafbfc;
}

.form-card .form-control:focus {
    border-color: #7c3aed;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.08);
    outline: none;
}

.form-card .form-control.is-invalid {
    border-color: #dc2626;
}

.form-card .form-control.is-valid {
    border-color: #16a34a;
}

.form-card .form-control::placeholder {
    color: #94a3b8;
}

.form-card .form-control-icon {
    position: relative;
}

.form-card .form-control-icon i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 16px;
}

.form-card .form-control-icon .form-control {
    padding-left: 42px;
}

.form-card .form-control-icon .toggle-password {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 0;
}

.form-card .form-control-icon .toggle-password:hover {
    color: #0f172a;
}

.form-card .form-select {
    height: 44px;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    font-size: 14px;
    padding: 0 14px;
    font-family: 'Poppins', sans-serif;
    background: #fafbfc;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
}

.form-card .form-select:focus {
    border-color: #7c3aed;
    box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.08);
    outline: none;
}

.form-card .form-text {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 4px;
}

.form-card .form-text i {
    margin-right: 4px;
}

.form-card .password-strength {
    height: 4px;
    margin-top: 8px;
    border-radius: 10px;
    background: #e2e8f0;
    overflow: hidden;
    transition: 0.3s;
}

.form-card .password-strength .strength-bar {
    height: 100%;
    border-radius: 10px;
    transition: width 0.3s ease;
    width: 0%;
}

.form-card .password-strength .strength-bar.weak {
    width: 25%;
    background: #dc2626;
}

.form-card .password-strength .strength-bar.fair {
    width: 50%;
    background: #f59e0b;
}

.form-card .password-strength .strength-bar.good {
    width: 75%;
    background: #2563eb;
}

.form-card .password-strength .strength-bar.strong {
    width: 100%;
    background: #16a34a;
}

.form-card .strength-text {
    font-size: 11px;
    font-weight: 500;
    margin-top: 4px;
}

.form-card .strength-text.weak { color: #dc2626; }
.form-card .strength-text.fair { color: #f59e0b; }
.form-card .strength-text.good { color: #2563eb; }
.form-card .strength-text.strong { color: #16a34a; }

.form-card .btn-submit {
    width: 100%;
    padding: 12px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 15px;
    transition: 0.3s;
    border: none;
    font-family: 'Poppins', sans-serif;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.form-card .btn-submit.btn-primary {
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
    color: #fff;
    box-shadow: 0 4px 15px rgba(124, 58, 237, 0.25);
}

.form-card .btn-submit.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(124, 58, 237, 0.35);
}

.form-card .btn-submit:active {
    transform: translateY(0);
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

.stat-mini {
    animation: fadeSlideUp 0.5s ease forwards;
}

.stat-mini:nth-child(2) { animation-delay: 0.1s; }
.stat-mini:nth-child(3) { animation-delay: 0.2s; }

/* Responsive */
@media (max-width: 768px) {
    .page-header-custom {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    
    .page-header-custom .header-actions {
        justify-content: stretch;
    }
    
    .page-header-custom .header-actions .btn {
        flex: 1;
        text-align: center;
    }
    
    .stats-grid-mini {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .form-card .card-body {
        padding: 20px;
    }
}

@media (max-width: 480px) {
    .stats-grid-mini {
        grid-template-columns: 1fr;
    }
    
    .page-header-custom h1 {
        font-size: 20px;
    }
    
    .page-header-custom h1 .header-icon {
        width: 40px;
        height: 40px;
        font-size: 18px;
    }
}
</style>

<!-- ==========================================================
     PAGE HEADER
     ========================================================== -->
<div class="page-header-custom">
    <h1>
        <span class="header-icon"><i class="bi bi-person-plus"></i></span>
        Add New User
    </h1>
    <div class="header-actions">
        <a href="manage_users.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Users
        </a>
        <a href="manage_users.php" class="btn btn-primary">
            <i class="bi bi-people"></i> Manage Users
        </a>
    </div>
</div>

<!-- ==========================================================
     STATISTICS
     ========================================================== -->
<div class="stats-grid-mini">
    <div class="stat-mini">
        <div class="stat-icon-mini blue">
            <i class="bi bi-people"></i>
        </div>
        <div class="stat-info-mini">
            <div class="stat-label">Total Users</div>
            <div class="stat-value"><?= $total_users ?></div>
        </div>
    </div>
    <div class="stat-mini">
        <div class="stat-icon-mini green">
            <i class="bi bi-person-graduation"></i>
        </div>
        <div class="stat-info-mini">
            <div class="stat-label">Students</div>
            <div class="stat-value"><?= $total_students ?></div>
        </div>
    </div>
    <div class="stat-mini">
        <div class="stat-icon-mini purple">
            <i class="bi bi-shield-check"></i>
        </div>
        <div class="stat-info-mini">
            <div class="stat-label">Admins</div>
            <div class="stat-value"><?= $total_admins ?></div>
        </div>
    </div>
</div>

<!-- ==========================================================
     ALERTS
     ========================================================== -->
<?php if ($error): ?>
<div class="alert-custom alert-danger alert-dismissible fade show" role="alert">
    <span class="alert-icon"><i class="bi bi-exclamation-circle-fill"></i></span>
    <?= $error ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert-custom alert-success alert-dismissible fade show" role="alert">
    <span class="alert-icon"><i class="bi bi-check-circle-fill"></i></span>
    <?= $success ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ==========================================================
     ADD USER FORM
     ========================================================== -->
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card form-card">
            <div class="card-header">
                <div class="card-icon"><i class="bi bi-person-plus"></i></div>
                User Information
                <span style="margin-left:auto;font-size:12px;color:#94a3b8;">
                    <i class="bi bi-asterisk text-danger"></i> Required fields
                </span>
            </div>
            <div class="card-body">
                <form method="POST" class="needs-validation" novalidate>
                    
                    <!-- Username -->
                    <div class="form-group">
                        <label class="form-label" for="username">
                            <i class="bi bi-person"></i> Username <span class="required">*</span>
                        </label>
                        <div class="form-control-icon">
                            <i class="bi bi-person"></i>
                            <input type="text" name="username" id="username" class="form-control"
                                   placeholder="Enter username (e.g., johndoe)" 
                                   value="<?= htmlspecialchars($form_data['username']) ?>"
                                   required minlength="3" maxlength="50"
                                   pattern="[a-zA-Z0-9_]+">
                            <div class="invalid-feedback">
                                Username must be at least 3 characters and contain only letters, numbers, and underscores.
                            </div>
                        </div>
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> 3-50 characters, letters, numbers, and underscores only.
                        </div>
                    </div>

                    <!-- Full Name -->
                    <div class="form-group">
                        <label class="form-label" for="full_name">
                            <i class="bi bi-person-badge"></i> Full Name <span class="optional">(Optional)</span>
                        </label>
                        <div class="form-control-icon">
                            <i class="bi bi-person-badge"></i>
                            <input type="text" name="full_name" id="full_name" class="form-control"
                                   placeholder="Enter full name" 
                                   value="<?= htmlspecialchars($form_data['full_name']) ?>">
                        </div>
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> Optional but recommended for better identification.
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label class="form-label" for="email">
                            <i class="bi bi-envelope"></i> Email <span class="optional">(Optional)</span>
                        </label>
                        <div class="form-control-icon">
                            <i class="bi bi-envelope"></i>
                            <input type="email" name="email" id="email" class="form-control"
                                   placeholder="Enter email address" 
                                   value="<?= htmlspecialchars($form_data['email']) ?>">
                            <div class="invalid-feedback">
                                Please enter a valid email address.
                            </div>
                        </div>
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> Optional but recommended for communication and password recovery.
                        </div>
                    </div>

                    <!-- Role -->
                    <div class="form-group">
                        <label class="form-label" for="role">
                            <i class="bi bi-shield-check"></i> Role <span class="required">*</span>
                        </label>
                        <select name="role" id="role" class="form-select" required>
                            <?php foreach ($role_options as $value => $label): ?>
                                <option value="<?= $value ?>" <?= $form_data['role'] === $value ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> 
                            <?php if (current_role() === 'superadmin'): ?>
                                Super Admin: Full system access. Admin: Manage students and marks. Manager: User management. Student: View own records.
                            <?php elseif (current_role() === 'manager'): ?>
                                Admin: Manage students and marks. Manager: User management. Student: View own records.
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label class="form-label" for="password">
                            <i class="bi bi-lock"></i> Password <span class="required">*</span>
                        </label>
                        <div class="form-control-icon">
                            <i class="bi bi-lock"></i>
                            <input type="password" name="password" id="password" class="form-control"
                                   placeholder="Enter password (min 6 characters)" 
                                   required minlength="6">
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <i class="bi bi-eye"></i>
                            </button>
                            <div class="invalid-feedback">
                                Password must be at least 6 characters.
                            </div>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                        <div id="strengthText" class="strength-text"></div>
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> Minimum 6 characters. Use a mix of letters, numbers, and symbols for better security.
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">
                            <i class="bi bi-lock-fill"></i> Confirm Password <span class="required">*</span>
                        </label>
                        <div class="form-control-icon">
                            <i class="bi bi-lock-fill"></i>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                                   placeholder="Confirm password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                <i class="bi bi-eye"></i>
                            </button>
                            <div class="invalid-feedback">
                                Passwords do not match.
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="d-flex gap-3 mt-4">
                        <button type="submit" name="add_user" class="btn-submit btn-primary">
                            <i class="bi bi-person-plus"></i> Add User
                        </button>
                        <button type="reset" class="btn btn-outline-secondary" style="flex:1;border-radius:10px;font-weight:500;">
                            <i class="bi bi-arrow-counterclockwise"></i> Reset Form
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================
     JAVASCRIPT ENHANCEMENTS
     ========================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password strength checker
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = checkPasswordStrength(password);
            
            // Update strength bar
            strengthBar.className = 'strength-bar';
            if (password.length === 0) {
                strengthBar.style.width = '0%';
                strengthText.textContent = '';
                strengthText.className = 'strength-text';
                return;
            }
            
            strengthBar.classList.add(strength.label);
            strengthBar.style.width = strength.percentage + '%';
            strengthText.textContent = strength.label.toUpperCase();
            strengthText.className = 'strength-text ' + strength.label;
        });
    }
    
    // Password match validation
    const confirmPassword = document.getElementById('confirm_password');
    if (confirmPassword && passwordInput) {
        confirmPassword.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        passwordInput.addEventListener('input', function() {
            if (confirmPassword.value && this.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else if (confirmPassword.value) {
                confirmPassword.setCustomValidity('');
            }
        });
    }
    
    // Auto-dismiss alerts
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // Form validation
    const form = document.querySelector('.needs-validation');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            this.classList.add('was-validated');
        });
    }
});

// ==========================================================
// PASSWORD STRENGTH CHECKER
// ==========================================================
function checkPasswordStrength(password) {
    let score = 0;
    
    if (password.length === 0) {
        return { label: '', percentage: 0 };
    }
    
    // Length check
    if (password.length >= 8) score += 25;
    else if (password.length >= 6) score += 15;
    
    // Contains lowercase
    if (/[a-z]/.test(password)) score += 25;
    
    // Contains uppercase
    if (/[A-Z]/.test(password)) score += 20;
    
    // Contains number
    if (/\d/.test(password)) score += 15;
    
    // Contains special character
    if (/[^a-zA-Z0-9]/.test(password)) score += 15;
    
    let label = 'weak';
    if (score >= 80) label = 'strong';
    else if (score >= 60) label = 'good';
    else if (score >= 40) label = 'fair';
    
    return { label, percentage: Math.min(score, 100) };
}

// ==========================================================
// TOGGLE PASSWORD VISIBILITY
// ==========================================================
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.parentElement.querySelector('.toggle-password i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>

<?php include 'includes/footer.php'; ?>