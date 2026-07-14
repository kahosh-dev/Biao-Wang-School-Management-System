<?php
require_once 'functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (is_logged_in()) {
    redirect('main_dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $email    = sanitize($_POST['email'] ?? '');
        $name     = sanitize($_POST['name'] ?? '');

        if (
            empty($username) ||
            empty($password) ||
            empty($email) ||
            empty($name)
        ) {
            throw new Exception('Please fill all fields.');
        }

        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address.');
        }

        $exists = db_prepare_execute(
            "SELECT id FROM users WHERE username=?",
            "s",
            [$username]
        )->get_result()->fetch_assoc();

        if ($exists) {
            throw new Exception("Username already exists.");
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        db_prepare_execute(
            "INSERT INTO users
            (username,password,role,email,full_name)
            VALUES (?,?,?,?,?)",
            "sssss",
            [
                $username,
                $hash,
                "superadmin",
                $email,
                $name
            ]
        );

        header("Location: login.php");
        exit();

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

<title>Super Admin Registration</title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<link rel="stylesheet"
href="assets/css/style.css">

</head>

<body>

<div class="auth-page">

<div class="auth-right" style="width:100%;justify-content:center;">

<div class="auth-card register-card">

<div class="auth-card-header">

<div class="auth-logo-icon superadmin-icon">

<i class="bi bi-shield-lock-fill"></i>

</div>

<h2 class="auth-card-title">

Super Admin Registration

</h2>

<p class="auth-card-sub">

Create a Super Administrator account

</p>

</div>

<?php if($error): ?>

<div class="alert alert-danger">

<i class="bi bi-exclamation-circle-fill"></i>

<?= htmlspecialchars($error) ?>

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
placeholder="admin@email.com"
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
placeholder="Choose username"
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
type="submit"
class="btn btn-primary w-100 btn-lg mt-3">

<i class="bi bi-shield-check"></i>

Register Super Admin

</button>

</form>

<div class="text-center mt-4">

<p style="font-size:.9rem">

Already have an account?

</p>

<a href="login.php"
class="fw-bold">

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

</body>

</html>