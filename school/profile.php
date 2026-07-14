<?php
require_once 'functions.php';
if (!is_logged_in()) redirect('login.php');

$error = $success = '';

$user = db_prepare_execute("SELECT * FROM users WHERE id=?", 'i', [$_SESSION['user_id']])
          ->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name  = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $pw    = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$name || !$email) throw new Exception('Name and email are required.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Invalid email.');

        if ($pw) {
            if (strlen($pw) < 6) throw new Exception('Password must be at least 6 characters.');
            if ($pw !== $confirm) throw new Exception('Passwords do not match.');
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            db_prepare_execute(
                "UPDATE users SET full_name=?,email=?,password=? WHERE id=?",
                'sssi', [$name, $email, $hash, $_SESSION['user_id']]
            );
        } else {
            db_prepare_execute(
                "UPDATE users SET full_name=?,email=? WHERE id=?",
                'ssi', [$name, $email, $_SESSION['user_id']]
            );
        }

        $_SESSION['username'] = $user['username'];
        log_activity('Updated profile');
        $success = 'Profile updated successfully.';
        $user['full_name'] = $name;
        $user['email']     = $email;
    } catch (Exception $e) { $error = $e->getMessage(); }
}

$page_title = 'My Profile';
include 'includes/header.php';
?>

<?php if ($error): ?>
  <div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
  <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div style="max-width:560px">
  <div class="card">
    <div class="card-header"><i class="fas fa-user"></i> Profile Information</div>
    <div class="card-body">
      <div style="text-align:center;margin-bottom:20px">
        <div class="avatar" style="width:64px;height:64px;font-size:1.6rem;margin:0 auto 10px">
          <?= strtoupper(substr($user['username'], 0, 1)) ?>
        </div>
        <span class="badge badge-primary"><?= ucfirst($user['role']) ?></span>
      </div>
      <form method="post" novalidate>
        <div class="form-group">
          <label class="form-label">Username</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
        </div>
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control" required
                 value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required
                 value="<?= htmlspecialchars($user['email'] ?? '') ?>">
        </div>
        <hr style="margin:20px 0;border-color:#e2e8f0">
        <p style="font-size:.85rem;color:#64748b;margin-bottom:12px">Leave password fields blank to keep current password.</p>
        <div class="form-group">
          <label class="form-label">New Password</label>
          <input type="password" name="new_password" class="form-control" placeholder="New password (optional)">
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password">
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </form>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
