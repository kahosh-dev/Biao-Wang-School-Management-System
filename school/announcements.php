<?php
require_once 'functions.php';
if (!is_logged_in()) redirect('login.php');

$error = $success = '';
$canPost = in_array(current_role(), ['superadmin','manager','admin']);

// ── POST ──────────────────────────────────────────────────
if ($canPost && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_ann'])) {
    try {
        $title  = sanitize($_POST['title'] ?? '');
        $body   = sanitize($_POST['body']  ?? '');
        $target = sanitize($_POST['role_target'] ?? 'all');
        if (!$title || !$body) throw new Exception('Title and message are required.');
        $allowed = ['all','student','client','admin','manager','superadmin'];
        if (!in_array($target, $allowed)) $target = 'all';

        db_prepare_execute(
            "INSERT INTO announcements (user_id, title, body, role_target) VALUES (?,?,?,?)",
            'isss', [$_SESSION['user_id'], $title, $body, $target]
        );
        log_activity("Posted announcement: $title");
        $success = 'Announcement posted.';
    } catch (Exception $e) { $error = $e->getMessage(); }
}

// ── DELETE ────────────────────────────────────────────────
if ($canPost && isset($_GET['delete'])) {
    $aid = (int)$_GET['delete'];
    db_prepare_execute("DELETE FROM announcements WHERE id=?", 'i', [$aid]);
    log_activity("Deleted announcement #$aid");
    redirect('announcements.php');
}

// ── FETCH ─────────────────────────────────────────────────
$role = current_role();
$visibleTargets = ['all', $role];
$placeholders = implode(',', array_fill(0, count($visibleTargets), '?'));
$types  = str_repeat('s', count($visibleTargets));
$anns   = db_prepare_execute(
    "SELECT a.*, u.username FROM announcements a
     JOIN users u ON u.id=a.user_id
     WHERE a.role_target IN ($placeholders)
     ORDER BY a.created_at DESC",
    $types, $visibleTargets
)->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = 'Announcements';
include 'includes/header.php';
?>

<?php if ($error): ?>
  <div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
  <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($canPost): ?>
<div class="card" style="margin-bottom:24px">
  <div class="card-header"><i class="fas fa-plus"></i> New Announcement</div>
  <div class="card-body">
    <form method="post">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Title</label>
          <input type="text" name="title" class="form-control" placeholder="Announcement title" required>
        </div>
        <div class="form-group">
          <label class="form-label">Target Audience</label>
          <select name="role_target" class="form-control">
            <option value="all">Everyone</option>
            <option value="student">Students</option>
            <option value="client">Clients</option>
            <option value="admin">Admins</option>
            <option value="manager">Managers</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Message</label>
        <textarea name="body" class="form-control" rows="3" placeholder="Write your announcement…" required></textarea>
      </div>
      <button type="submit" name="post_ann" class="btn btn-primary">
        <i class="fas fa-bullhorn"></i> Post Announcement
      </button>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><i class="fas fa-bullhorn"></i> Announcements (<?= count($anns) ?>)</div>
  <div class="card-body">
    <?php if ($anns): ?>
      <?php foreach ($anns as $a): ?>
      <div class="ann-card" style="display:flex;justify-content:space-between;align-items:flex-start">
        <div>
          <h5><?= htmlspecialchars($a['title']) ?>
            <span class="badge badge-info" style="margin-left:6px"><?= ucfirst($a['role_target']) ?></span>
          </h5>
          <p><?= nl2br(htmlspecialchars($a['body'])) ?></p>
          <div class="ann-meta">
            <i class="fas fa-user"></i> <?= htmlspecialchars($a['username']) ?>
            &nbsp;&middot;&nbsp;
            <?= date('d M Y, H:i', strtotime($a['created_at'])) ?>
          </div>
        </div>
        <?php if ($canPost): ?>
        <a href="announcements.php?delete=<?= $a['id'] ?>" class="btn btn-sm btn-danger"
           data-confirm="Delete this announcement?" style="flex-shrink:0;margin-left:12px">
          <i class="fas fa-trash"></i>
        </a>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="color:#94a3b8">No announcements available.</p>
    <?php endif; ?>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
