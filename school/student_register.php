<?php
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

if (is_logged_in()) {
    redirect('main_dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        // CSRF Validation
        $token = $_POST['csrf_token'] ?? '';

        if (
            empty($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $token)
        ) {
            throw new Exception('Invalid CSRF token.');
        }

        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $name     = sanitize($_POST['name'] ?? '');
        $reg_no   = sanitize($_POST['reg_no'] ?? '');
        $email    = sanitize($_POST['email'] ?? '');
        $class    = sanitize($_POST['class'] ?? '');

        // Validation
        if (
            empty($username) ||
            empty($password) ||
            empty($name) ||
            empty($reg_no) ||
            empty($email)
        ) {
            throw new Exception('Please fill all required fields.');
        }

        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address.');
        }

        // Check username
        $checkUser = db_prepare_execute(
            "SELECT id FROM users WHERE username = ?",
            "s",
            [$username]
        )->get_result()->fetch_assoc();

        if ($checkUser) {
            throw new Exception('Username already exists.');
        }

        // Check registration number
        $checkReg = db_prepare_execute(
            "SELECT id FROM students WHERE reg_no = ?",
            "s",
            [$reg_no]
        )->get_result()->fetch_assoc();

        if ($checkReg) {
            throw new Exception('Registration number already exists.');
        }

        // Insert User
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        db_prepare_execute(
            "INSERT INTO users
            (username,password,role,email,full_name)
            VALUES (?,?,?,?,?)",
            "sssss",
            [
                $username,
                $passwordHash,
                'student',
                $email,
                $name
            ]
        );

        $user_id = db()->insert_id;

        // Insert Student
        db_prepare_execute(
            "INSERT INTO students
            (user_id,reg_no,name,class)
            VALUES (?,?,?,?)",
            "isss",
            [
                $user_id,
                $reg_no,
                $name,
                $class
            ]
        );

        unset($_SESSION['csrf_token']);

        header("Location: login.php");
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Registration</title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<link rel="stylesheet"
href="assets/css/style.css">
</head>

<body>

<div class="auth-page">

    <div class="auth-card">

      <div class="auth-card-header">

    <div class="register-icon">
        <i class="bi bi-person-graduation"></i>
    </div>

    <h2 class="auth-card-title">
        Student Registration
    </h2>

    <p class="auth-card-sub">
        Create your student account
    </p>

</div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>

<input type="hidden"
       name="csrf_token"
       value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

<div class="row">

    <div class="col-md-6">

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

    </div>

    <div class="col-md-6">

        <div class="form-group">

            <label class="form-label">
                <i class="bi bi-credit-card-2-front"></i>
                Admission Number
            </label>

            <div class="form-control-icon">

                <i class="bi bi-credit-card-2-front-fill"></i>

                <input
                    type="text"
                    name="reg_no"
                    class="form-control"
                    placeholder="ADM/2026/001"
                    value="<?= htmlspecialchars($_POST['reg_no'] ?? '') ?>"
                    required>

            </div>

        </div>

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
            placeholder="student@email.com"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required>

    </div>

</div>


<div class="row">

    <div class="col-md-6">

        <div class="form-group">

            <label class="form-label">

                <i class="bi bi-building"></i>

                Form / Class

            </label>

            <div class="form-control-icon">

                <i class="bi bi-building-fill"></i>

                <input
                    type="text"
                    name="class"
                    class="form-control"
                    placeholder="Form 4 East"
                    value="<?= htmlspecialchars($_POST['class'] ?? '') ?>"
                    required>

            </div>

        </div>

    </div>

    <div class="col-md-6">

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
                    placeholder="Choose username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    required>

            </div>

        </div>

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
    type="submit"
    class="btn btn-primary w-100 btn-lg mt-3">

    <i class="bi bi-person-plus-fill"></i>

    Register Student

</button>

</form>
<div class="text-center mt-4">

<p class="mb-2">
Already have an account?
</p>

<a href="login.php" class="fw-semibold">
Login
</a>

<div class="mt-3">

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
```
