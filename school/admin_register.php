<?php
require_once __DIR__ . '/functions.php';

if (is_logged_in()) {

    switch (current_role()) {

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
        $email    = sanitize($_POST['email'] ?? '');
        $name     = sanitize($_POST['name'] ?? '');

        if (!$username || !$password || !$email || !$name) {
            throw new Exception('All fields are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }

        if (strlen($password) < 6) {
            throw new Exception('Password must contain at least 6 characters.');
        }

        $exists = db_prepare_execute(
            "SELECT id FROM users WHERE username=?",
            "s",
            [$username]
        )->get_result()->fetch_assoc();

        if ($exists) {
            throw new Exception('Username already exists.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        db_prepare_execute(
            "INSERT INTO users(username,password,role,email,full_name)
             VALUES(?,?,?,?,?)",
            "sssss",
            [
                $username,
                $hash,
                'admin',
                $email,
                $name
            ]
        );

        log_activity("New admin account created");

        redirect('login.php');

    } catch (Exception $e) {

        $error = $e->getMessage();

    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1">

<meta name="description"
      content="Admin Registration">

<title>Admin Registration | UniAdmin SMS</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
      rel="stylesheet">

<link rel="stylesheet"
      href="assets/css/style.css">

</head>

<body>
  <div class="auth-page">
    <!-- =========================================
         RIGHT SIDE
    ========================================== -->

    <div class="auth-right">

        <div class="auth-card">

            <div class="auth-card-header">

                <div class="auth-logo-icon">
                    <i class="bi bi-person-plus-fill"></i>
                </div>

                <h2 class="auth-card-title">
                    Admin Registration
                </h2>

                <p class="auth-card-sub">
                    Create your administrator account
                </p>

            </div>

            <?php if($error): ?>

                <div class="alert alert-danger alert-dismissible">

                    <span class="alert-icon">
                        <i class="bi bi-exclamation-circle-fill"></i>
                    </span>

                    <?= htmlspecialchars($error) ?>

                    <button class="btn-close" type="button"></button>

                </div>

            <?php endif; ?>

            <form method="POST">

                <div class="form-group">

                    <label class="form-label">
                        <i class="bi bi-person"></i>
                        Full Name
                    </label>

                    <div class="form-control-icon">

                        <i class="bi bi-person-fill"></i>

                        <input
                            type="text"
                            name="name"
                            class="form-control"
                            placeholder="Enter full name"
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                            required>

                    </div>

                </div>

                <div class="form-group">

                    <label class="form-label">
                        <i class="bi bi-envelope"></i>
                        Email Address
                    </label>

                    <div class="form-control-icon">

                        <i class="bi bi-envelope-fill"></i>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            placeholder="admin@school.edu"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            required>

                    </div>

                </div>

                <div class="form-group">

                    <label class="form-label">
                        <i class="bi bi-person-circle"></i>
                        Username
                    </label>

                    <div class="form-control-icon">

                        <i class="bi bi-person-circle"></i>

                        <input
                            type="text"
                            name="username"
                            class="form-control"
                            placeholder="Choose a username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            required>

                    </div>

                </div>

                <div class="form-group">

                    <label class="form-label">
                        <i class="bi bi-lock"></i>
                        Password
                    </label>

                    <div class="form-control-icon">

                        <i class="bi bi-lock-fill"></i>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            placeholder="Minimum 6 characters"
                            required>

                    </div>

                </div>

                <button
                    class="btn btn-primary w-100 btn-lg mt-3"
                    type="submit">

                    <i class="bi bi-person-plus-fill"></i>

                    Register Admin

                </button>

            </form>

            <div class="text-center mt-4"
                 style="font-size:.82rem">

                Already have an account?

                <a href="login.php">

                    Login

                </a>

                <br><br>

                <a href="main_dashboard.php">

                    <i class="bi bi-arrow-left"></i>

                    Back to Home

                </a>

            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="assets/js/script.js"></script>

</body>
</html>