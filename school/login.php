<?php
require_once __DIR__ . '/functions.php';
if (is_logged_in()) {

    switch(current_role()){

        case 'superadmin':
         redirect('superadmin_dashboard.php');
            break;

        case 'admin':
            redirect('admin_dashboard.php');
            break;

        case 'student':
            redirect('student_dashboard.php');
            break;

        default:
            session_destroy();
            break;
    }

}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$username || !$password) throw new Exception('All fields are required.');

        $stmt = db_prepare_execute('SELECT * FROM users WHERE username=?', 's', [$username]);
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || !password_verify($password, $user['password']))
            throw new Exception('Invalid username or password.');

        session_regenerate_id(true);
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role']     = $user['role'];
        $_SESSION['full_name']= $user['full_name'] ?? $user['username'];
        log_activity('Logged in');

        $dest = [
            'superadmin' => 'superadmin_dashboard.php',
            'manager'    => 'superadmin_dashboard.php',
            'admin'      => 'admin_dashboard.php',
            'student'    => 'student_dashboard.php',
        ];
        redirect($dest[$user['role']] ?? 'main_dashboard.php');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Sign in to UniAdmin School Management System">
  <title>Sign In — UniAdmin SMS</title>
  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-page">

  <!-- Left Panel -->
  <div class="auth-left">
    <div class="auth-brand">
      <div class="brand-logo"><i class="bi bi-mortarboard-fill"></i></div>
      <span>UniAdmin SMS</span>
    </div>

    <h1 class="auth-headline">Empowering<br>Education<br>Management</h1>
    <p class="auth-sub">
      A complete student administration platform for schools and universities.
      Manage students, marks, reports, and more — all in one place.
    </p>

    <div class="auth-features">
      <div class="auth-feature">
        <div class="feat-icon"><i class="bi bi-people-fill"></i></div>
        <span>Role-based access for students, admins &amp; superadmins</span>
      </div>
      <div class="auth-feature">
        <div class="feat-icon"><i class="bi bi-journal-check"></i></div>
        <span>Complete marks management with GPA calculation</span>
      </div>
      <div class="auth-feature">
        <div class="feat-icon"><i class="bi bi-bar-chart-fill"></i></div>
        <span>Detailed reports and grade distribution charts</span>
      </div>
      <div class="auth-feature">
        <div class="feat-icon"><i class="bi bi-shield-check"></i></div>
        <span>Secure sessions with activity logging</span>
      </div>
    </div>
  </div>

  <!-- Right Panel -->
  <div class="auth-right">
    <div class="auth-card">

      <div class="auth-card-header">
        <div class="auth-logo-icon">
          <i class="bi bi-mortarboard-fill"></i>
        </div>
        <h1 class="auth-card-title">Welcome Back</h1>
        <p class="auth-card-sub">Sign in to your account to continue</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible" role="alert" data-auto-dismiss="5000">
          <span class="alert-icon"><i class="bi bi-exclamation-circle-fill"></i></span>
          <?= htmlspecialchars($error) ?>
          <button class="btn-close" type="button" aria-label="Close">&times;</button>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate id="loginForm">

        <div class="form-group">
          <label class="form-label" for="login_username">
            <i class="bi bi-person"></i> Username
          </label>
          <div class="form-control-icon">
            <i class="bi bi-person"></i>
            <input type="text" id="login_username" name="username" class="form-control"
                   placeholder="Enter your username" autocomplete="username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="login_password">
            <i class="bi bi-lock"></i> Password
          </label>
          <div class="form-control-icon">
            <i class="bi bi-lock"></i>
            <input type="password" id="login_password" name="password" class="form-control"
                   placeholder="Enter your password" autocomplete="current-password" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-lg mt-2" id="loginBtn">
          <i class="bi bi-box-arrow-in-right"></i>
          Sign In
        </button>

      </form>

      <div class="text-center mt-4" style="font-size:.82rem;color:var(--text-muted)">
        <a href="main_dashboard.php" style="color:var(--primary)">
          <i class="bi bi-arrow-left"></i> Back to Home
        </a>
      </div>

    </div>
  </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>
