<?php
require_once 'functions.php';
require_role(['admin','superadmin']);

$student_id = (int)($_GET['student_id'] ?? 0);
if (!$student_id) { redirect('admin_dashboard.php'); }

$student = db_prepare_execute("SELECT * FROM students WHERE id=?", 'i', [$student_id])
             ->get_result()->fetch_assoc();
if (!$student) die('Student not found.');

$subjects = db()->query("SELECT * FROM subjects ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$terms    = db()->query("SELECT * FROM terms ORDER BY year, name")->fetch_all(MYSQLI_ASSOC);

$error = $success = '';

// ── ADD ──────────────────────────────────────────────────────────
if (isset($_POST['add_mark'])) {
    try {
        $subject_id = (int)$_POST['subject_id'];
        $term_id    = (int)$_POST['term_id'];
        $score      = (float)$_POST['score'];
        if (!$subject_id || !$term_id) throw new Exception('Select subject and term.');
        if ($score < 0 || $score > 100) throw new Exception('Score must be 0–100.');

        db_prepare_execute(
            "INSERT INTO marks (student_id,subject_id,term_id,score) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE score=VALUES(score)",
            'iiid', [$student_id, $subject_id, $term_id, $score]
        );
        log_activity("Added mark for student #$student_id");
        $success = 'Mark saved successfully.';
    } catch (Exception $e) { $error = $e->getMessage(); }
}

// ── UPDATE ────────────────────────────────────────────────────────
if (isset($_POST['update_mark'])) {
    try {
        $mark_id    = (int)$_POST['mark_id'];
        $subject_id = (int)$_POST['subject_id'];
        $term_id    = (int)$_POST['term_id'];
        $score      = (float)$_POST['score'];
        if ($score < 0 || $score > 100) throw new Exception('Score must be 0–100.');

        db_prepare_execute(
            "UPDATE marks SET subject_id=?,term_id=?,score=? WHERE id=?",
            'iidi', [$subject_id, $term_id, $score, $mark_id]
        );
        log_activity("Updated mark #$mark_id");
        $success = 'Mark updated successfully.';
    } catch (Exception $e) { $error = $e->getMessage(); }
}

// ── DELETE ────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $del = (int)$_GET['delete'];
    db_prepare_execute("DELETE FROM marks WHERE id=?", 'i', [$del]);
    log_activity("Deleted mark #$del");
    redirect("manage_marks.php?student_id=$student_id");
}

// ── EDIT PREFILL ──────────────────────────────────────────────────
$edit = null;
if (isset($_GET['edit'])) {
    $edit = db_prepare_execute("SELECT * FROM marks WHERE id=?", 'i', [(int)$_GET['edit']])
              ->get_result()->fetch_assoc();
}

// ── FETCH MARKS ───────────────────────────────────────────────────
$marks = db_prepare_execute(
    "SELECT m.id, sub.name subject_name, sub.code subject_code, t.name term_name, t.year, m.score
     FROM marks m
     JOIN subjects sub ON sub.id=m.subject_id
     JOIN terms t ON t.id=m.term_id
     WHERE m.student_id=?
     ORDER BY t.year, t.name, sub.name",
    'i', [$student_id]
)->get_result()->fetch_all(MYSQLI_ASSOC);

$scores = array_column($marks, 'score');
$gpa    = calculate_gpa(array_map('floatval', $scores));
$passCount = count(array_filter($scores, fn($s) => $s >= 50));
$failCount = count(array_filter($scores, fn($s) => $s < 50));

$page_title    = 'Manage Marks';
$page_subtitle = htmlspecialchars($student['name']);
include 'includes/header.php';
?>

<!-- ==========================================================
     MANAGE MARKS CUSTOM STYLES
     ========================================================== -->
<style>
/* Manage Marks Specific Styles */
.manage-marks-wrapper {
    padding: 20px 0;
}

.manage-marks-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}

.manage-marks-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0;
}

.manage-marks-header h1 .header-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.25);
}

.manage-marks-header .header-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.manage-marks-header .header-actions .btn {
    padding: 8px 18px;
    border-radius: 10px;
    font-weight: 500;
    font-size: 13px;
    transition: 0.3s;
    font-family: 'Poppins', sans-serif;
}

.manage-marks-header .header-actions .btn:hover {
    transform: translateY(-2px);
}

/* Student Info Card */
.student-info-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px 24px;
    margin-bottom: 25px;
    border-left: 5px solid #2563eb;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    transition: 0.3s;
}

.student-info-card:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.student-info-card .avatar-large {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    font-weight: 700;
    flex-shrink: 0;
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.25);
}

.student-info-card .info-details {
    flex: 1;
    min-width: 200px;
}

.student-info-card .info-details h5 {
    margin: 0;
    font-weight: 600;
    font-size: 18px;
    color: #0f172a;
}

.student-info-card .info-details .meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    font-size: 13px;
    color: #64748b;
    margin-top: 4px;
}

.student-info-card .info-details .meta span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.student-info-card .info-details .meta i {
    color: #2563eb;
}

.student-info-card .info-details .meta .gpa-value {
    color: #2563eb;
    font-weight: 700;
    font-size: 18px;
}

.student-info-card .info-details .meta .badge-stat {
    padding: 2px 12px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 600;
}

.student-info-card .info-details .meta .badge-stat.pass {
    background: #dcfce7;
    color: #166534;
}

.student-info-card .info-details .meta .badge-stat.fail {
    background: #fee2e2;
    color: #991b1b;
}

/* Form Styles */
.marks-form .form-group {
    margin-bottom: 20px;
}

.marks-form .form-label {
    font-size: 13px;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.marks-form .form-label .required {
    color: #dc2626;
}

.marks-form .form-control {
    height: 46px;
    border-radius: 10px;
    border: 1.5px solid #e2e8f0;
    font-size: 14px;
    padding: 0 14px;
    transition: 0.3s;
    font-family: 'Poppins', sans-serif;
    background: #fafbfc;
}

.marks-form .form-control:focus {
    border-color: #2563eb;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.08);
}

.marks-form .form-control::placeholder {
    color: #94a3b8;
}

.marks-form .form-control.is-invalid {
    border-color: #dc2626;
}

.marks-form .form-control.is-valid {
    border-color: #16a34a;
}

.marks-form .btn-submit {
    width: 100%;
    padding: 12px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    transition: 0.3s;
    border: none;
    font-family: 'Poppins', sans-serif;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.marks-form .btn-submit.btn-primary {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: #fff;
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.25);
}

.marks-form .btn-submit.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.35);
}

.marks-form .btn-submit.btn-warning {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.25);
}

.marks-form .btn-submit.btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(245, 158, 11, 0.35);
}

.marks-form .btn-submit:active {
    transform: translateY(0);
}

/* Score Progress Bar */
.score-bar {
    display: flex;
    align-items: center;
    gap: 12px;
}

.score-bar .bar-track {
    flex: 1;
    height: 8px;
    background: #f1f5f9;
    border-radius: 10px;
    overflow: hidden;
    min-width: 60px;
}

.score-bar .bar-track .bar-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.8s ease;
}

.score-bar .score-value {
    font-weight: 600;
    font-size: 14px;
    min-width: 45px;
    text-align: right;
}

/* Action Buttons */
.action-btns {
    display: flex;
    gap: 6px;
}

.action-btns .btn-action {
    width: 36px;
    height: 36px;
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

.action-btns .btn-action.btn-info {
    background: #dbeafe;
    color: #1e40af;
}

.action-btns .btn-action.btn-info:hover {
    background: #2563eb;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
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

.empty-state-custom .btn {
    margin-top: 16px;
}

/* Alert Styling */
.alert-custom {
    border-radius: 12px;
    padding: 14px 20px;
    border: none;
    display: flex;
    align-items: center;
    gap: 12px;
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

/* Responsive */
@media (max-width: 992px) {
    .manage-marks-header h1 {
        font-size: 22px;
    }
}

@media (max-width: 768px) {
    .manage-marks-header {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    
    .manage-marks-header .header-actions {
        justify-content: stretch;
    }
    
    .manage-marks-header .header-actions .btn {
        flex: 1;
        text-align: center;
    }
    
    .student-info-card {
        flex-direction: column;
        text-align: center;
        padding: 20px;
    }
    
    .student-info-card .info-details .meta {
        justify-content: center;
    }
    
    .student-info-card .info-details .meta span {
        justify-content: center;
    }
}

@media (max-width: 576px) {
    .manage-marks-header h1 {
        font-size: 18px;
    }
    
    .manage-marks-header h1 .header-icon {
        width: 40px;
        height: 40px;
        font-size: 18px;
    }
    
    .student-info-card .avatar-large {
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
    
    .student-info-card .info-details h5 {
        font-size: 16px;
    }
}

/* Animation */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.student-info-card {
    animation: slideIn 0.4s ease;
}

.card {
    animation: slideIn 0.5s ease;
}

.card:nth-child(2) {
    animation-delay: 0.1s;
}

/* Print Styles */
@media print {
    .manage-marks-header .header-actions {
        display: none;
    }
    
    .student-info-card {
        border: 1px solid #ddd;
        box-shadow: none;
    }
    
    .action-btns {
        display: none;
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


    <!-- Student Info -->
    <div class="student-info-card">
        <div class="avatar-large">
            <?= strtoupper(substr($student['name'], 0, 1)) ?>
        </div>
        <div class="info-details">
            <h5><?= htmlspecialchars($student['name']) ?></h5>
            <div class="meta">
                <span><i class="bi bi-hash"></i> <?= htmlspecialchars($student['reg_no']) ?></span>
                <span><i class="bi bi-building"></i> <?= htmlspecialchars($student['class'] ?? '—') ?></span>
                <span><i class="bi bi-envelope"></i> <?= htmlspecialchars($student['email'] ?? 'N/A') ?></span>
                <span>
                    <i class="bi bi-star"></i> 
                    GPA: <span class="gpa-value"><?= number_format($gpa, 2) ?></span>
                </span>
                <span>
                    <i class="bi bi-book"></i> 
                    Subjects: <strong><?= count($marks) ?></strong>
                </span>
                <?php if ($marks): ?>
                <span>
                    <span class="badge-stat pass"><i class="bi bi-check-circle"></i> <?= $passCount ?> Pass</span>
                    <span class="badge-stat fail"><i class="bi bi-x-circle"></i> <?= $failCount ?> Fail</span>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="row g-4">

        <!-- Form Column -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="bi bi-<?= $edit ? 'pencil-fill' : 'plus-lg' ?>"></i>
                    </div>
                    <?= $edit ? 'Edit Mark' : 'Add New Mark' ?>
                    <?php if ($edit): ?>
                    <span class="badge bg-warning ms-2">Editing</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="POST" class="marks-form">
                        <?php if ($edit): ?>
                            <input type="hidden" name="mark_id" value="<?= $edit['id'] ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label class="form-label" for="subject_id">
                                <i class="bi bi-book"></i> Subject <span class="required">*</span>
                            </label>
                            <select name="subject_id" id="subject_id" class="form-control" required>
                                <option value="">— Select Subject —</option>
                                <?php foreach ($subjects as $sub): ?>
                                    <option value="<?= $sub['id'] ?>"
                                        <?= ($edit && $edit['subject_id'] == $sub['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sub['code']) ?> — <?= htmlspecialchars($sub['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="term_id">
                                <i class="bi bi-calendar3"></i> Term <span class="required">*</span>
                            </label>
                            <select name="term_id" id="term_id" class="form-control" required>
                                <option value="">— Select Term —</option>
                                <?php foreach ($terms as $t): ?>
                                    <option value="<?= $t['id'] ?>"
                                        <?= ($edit && $edit['term_id'] == $t['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($t['name']) ?> <?= $t['year'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="score">
                                <i class="bi bi-123"></i> Score (0 – 100) <span class="required">*</span>
                            </label>
                            <input type="number" name="score" id="score" class="form-control"
                                   step="0.01" min="0" max="100" placeholder="e.g. 85.5"
                                   value="<?= $edit ? $edit['score'] : '' ?>" required>
                            <div style="font-size:12px;color:#94a3b8;margin-top:6px;">
                                <i class="bi bi-info-circle"></i> Enter a value between 0 and 100
                            </div>
                            <div id="scorePreview" style="margin-top:8px;display:none;">
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <span style="font-size:13px;font-weight:500;">Preview:</span>
                                    <div style="flex:1;height:6px;background:#f1f5f9;border-radius:10px;overflow:hidden;">
                                        <div id="scorePreviewBar" style="height:100%;width:0%;border-radius:10px;transition:width 0.3s;"></div>
                                    </div>
                                    <span id="scorePreviewText" style="font-weight:600;font-size:13px;">0%</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <?php if ($edit): ?>
                                <button type="submit" name="update_mark" class="btn-submit btn-warning">
                                    <i class="bi bi-save"></i> Update Mark
                                </button>
                                <a href="manage_marks.php?student_id=<?= $student_id ?>" 
                                   class="btn btn-outline-secondary" style="flex:1;text-align:center;">
                                    Cancel
                                </a>
                            <?php else: ?>
                                <button type="submit" name="add_mark" class="btn-submit btn-primary">
                                    <i class="bi bi-plus-circle"></i> Add Mark
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Marks Table Column -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <div class="card-icon"><i class="bi bi-list-check"></i></div>
                    Marks Record
                    <div class="ms-auto d-flex align-items-center gap-3">
                        <span style="font-size:13px;color:#64748b;">
                            <i class="bi bi-star"></i> 
                            GPA: <strong style="color:#2563eb;font-size:16px;"><?= number_format($gpa, 2) ?></strong>
                        </span>
                        <span style="font-size:13px;color:#64748b;">
                            <i class="bi bi-book"></i> 
                            Total: <strong><?= count($marks) ?></strong>
                        </span>
                    </div>
                </div>
                <div class="card-body">

                    <!-- Search -->
                    <div class="table-toolbar">
                        <div class="table-search">
                            <i class="bi bi-search"></i>
                            <input type="text" id="tableSearch" placeholder="Filter marks by subject or term…">
                        </div>
                        <span style="font-size:12px;color:#94a3b8;">
                            <i class="bi bi-info-circle"></i> Type to filter
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle" id="marksTable">
                            <thead>
                                <tr>
                                    <th style="width:50px;">#</th>
                                    <th>Subject</th>
                                    <th>Term</th>
                                    <th style="min-width:140px;">Score</th>
                                    <th style="width:80px;">Grade</th>
                                    <th style="width:100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($marks as $i => $m): ?>
                                <?php 
                                $g = score_to_grade((float)$m['score']);
                                $score = (float)$m['score'];
                                $color = $score >= 70 ? '#16a34a' : ($score >= 50 ? '#f59e0b' : '#dc2626');
                                ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($m['subject_name']) ?></strong>
                                            <?php if (isset($m['subject_code'])): ?>
                                            <div style="font-size:11px;color:#94a3b8;">
                                                <?= htmlspecialchars($m['subject_code']) ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?= htmlspecialchars($m['term_name']) ?> <?= $m['year'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="score-bar">
                                            <div class="bar-track">
                                                <div class="bar-fill" 
                                                     style="width: <?= min(100, $score) ?>%; background: <?= $color ?>;">
                                                </div>
                                            </div>
                                            <span class="score-value" style="color: <?= $color ?>;">
                                                <?= number_format($score, 1) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $gbadge = $g === 'F' ? 'badge-danger' : 
                                                 ($g[0] === 'A' ? 'badge-success' : 
                                                 ($g[0] === 'B' ? 'badge-info' : 'badge-warning'));
                                        ?>
                                        <span class="badge <?= $gbadge ?>"><?= $g ?></span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="manage_marks.php?student_id=<?= $student_id ?>&edit=<?= $m['id'] ?>"
                                               class="btn-action btn-warning" 
                                               data-bs-toggle="tooltip" 
                                               title="Edit <?= htmlspecialchars($m['subject_name']) ?> mark">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <a href="manage_marks.php?student_id=<?= $student_id ?>&delete=<?= $m['id'] ?>"
                                               class="btn-action btn-danger"
                                               data-bs-toggle="tooltip" 
                                               title="Delete <?= htmlspecialchars($m['subject_name']) ?> mark"
                                               onclick="return confirm('Delete this mark? This action cannot be undone.');">
                                                <i class="bi bi-trash-fill"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (!$marks): ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state-custom">
                                            <i class="bi bi-journal"></i>
                                            <p>No marks recorded yet. Add the first mark using the form.</p>
                                            <button class="btn btn-primary btn-sm" onclick="document.querySelector('form').scrollIntoView({behavior:'smooth'});">
                                                <i class="bi bi-plus-circle"></i> Add First Mark
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Table Footer Stats -->
                    <?php if ($marks): ?>
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top" 
                         style="font-size:13px;color:#64748b;flex-wrap:wrap;gap:8px;">
                        <span>
                            <i class="bi bi-info-circle"></i> 
                            Showing <strong><?= count($marks) ?></strong> mark(s)
                        </span>
                        <span>
                            <i class="bi bi-arrow-up" style="color:#16a34a;"></i> 
                            Pass: <strong><?= $passCount ?></strong>
                            &nbsp;|&nbsp;
                            <i class="bi bi-arrow-down" style="color:#dc2626;"></i> 
                            Fail: <strong><?= $failCount ?></strong>
                            &nbsp;|&nbsp;
                            <i class="bi bi-check-circle" style="color:#2563eb;"></i> 
                            GPA: <strong><?= number_format($gpa, 2) ?></strong>
                        </span>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div><!-- /row -->

</div><!-- /wrapper -->

<!-- ==========================================================
     JAVASCRIPT ENHANCEMENTS
     ========================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Table search filter
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('marksTable');
            const rows = table.querySelectorAll('tbody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const shouldShow = text.includes(searchTerm);
                row.style.display = shouldShow ? '' : 'none';
                if (shouldShow) visibleCount++;
            });
            
            // Update row count
            const footerInfo = document.querySelector('.mt-3 span:first-child');
            if (footerInfo) {
                footerInfo.innerHTML = `<i class="bi bi-info-circle"></i> Showing <strong>${visibleCount}</strong> mark(s)`;
            }
        });
    }
    
    // Tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            new bootstrap.Tooltip(el);
        }
    });
    
    // Score input preview
    const scoreInput = document.getElementById('score');
    const scorePreview = document.getElementById('scorePreview');
    const scorePreviewBar = document.getElementById('scorePreviewBar');
    const scorePreviewText = document.getElementById('scorePreviewText');
    
    if (scoreInput) {
        scoreInput.addEventListener('input', function() {
            const value = parseFloat(this.value);
            
            // Validate range
            if (value > 100) {
                this.value = 100;
            }
            if (value < 0) {
                this.value = 0;
            }
            
            const validValue = parseFloat(this.value) || 0;
            
            // Show preview
            if (this.value !== '') {
                scorePreview.style.display = 'block';
                const percentage = Math.min(validValue, 100);
                const color = percentage >= 70 ? '#16a34a' : (percentage >= 50 ? '#f59e0b' : '#dc2626');
                scorePreviewBar.style.width = percentage + '%';
                scorePreviewBar.style.background = color;
                scorePreviewText.textContent = percentage + '%';
                scorePreviewText.style.color = color;
            } else {
                scorePreview.style.display = 'none';
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
    
    // Progress bar animation on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.querySelectorAll('.bar-fill').forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0%';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 300);
                });
            }
        });
    });
    
    document.querySelectorAll('.score-bar').forEach(bar => {
        observer.observe(bar.closest('tr') || bar);
    });
});
</script>

<?php include 'includes/footer.php'; ?>