<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/db.php';

// ── Session ──────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/',
        'secure'   => false, 'httponly' => true, 'samesite' => 'Lax'
    ]);
    session_start();
}

// 30-minute inactivity timeout
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset(); session_destroy();
    header('Location: login.php'); exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// ── DB helpers ───────────────────────────────────────────
function db(): mysqli {
    global $mysqli; return $mysqli;
}

function db_prepare_execute(string $query, string $types = '', array $params = []): mysqli_stmt {
    global $mysqli;
    $stmt = $mysqli->prepare($query);
    if (!$stmt) throw new Exception('Prepare failed: ' . $mysqli->error);
    if ($types && $params) $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) throw new Exception('Execute failed: ' . $stmt->error);
    return $stmt;
}

// ── Sanitize ─────────────────────────────────────────────
function sanitize(string $v): string {
    return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}
// alias kept for back-compat
function sanitize_string(string $v): string { return sanitize($v); }

// ── Auth ─────────────────────────────────────────────────
function is_logged_in(): bool { return isset($_SESSION['user_id']); }
function current_role(): string { return $_SESSION['role'] ?? ''; }
function is_superadmin(): bool { return current_role() === 'superadmin'; }
function is_manager(): bool    { return current_role() === 'manager'; }
function is_admin(): bool      { return in_array(current_role(), ['admin','manager','superadmin']); }
function is_student(): bool    { return current_role() === 'student'; }
function is_client(): bool     { return current_role() === 'client'; }

function require_role($roles): void {
    if (!is_logged_in()) { header('Location: login.php'); exit; }
    if (!in_array(current_role(), (array)$roles)) {
        http_response_code(403);
        die('<div style="font-family:Arial;text-align:center;margin-top:100px"><h2>403 — Access Denied</h2><a href="main_dashboard.php">Go Home</a></div>');
    }
}

// ── Activity log ─────────────────────────────────────────
function log_activity(string $action): void {
    if (!isset($_SESSION['user_id'])) return;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    db_prepare_execute(
        'INSERT INTO activity_logs (user_id, action, ip) VALUES (?,?,?)',
        'iss', [$_SESSION['user_id'], $action, $ip]
    );
}

// ── GPA ──────────────────────────────────────────────────
function score_to_grade(float $score): string {
    if ($score >= 90) return 'A+';
    if ($score >= 80) return 'A';
    if ($score >= 70) return 'B';
    if ($score >= 60) return 'C';
    if ($score >= 50) return 'D';
    return 'F';
}

function score_to_gpa(float $score): float {
    if ($score >= 90) return 4.0;
    if ($score >= 80) return 3.7;
    if ($score >= 70) return 3.0;
    if ($score >= 60) return 2.0;
    if ($score >= 50) return 1.0;
    return 0.0;
}

function calculate_gpa(array $scores): float {
    if (!$scores) return 0.0;
    $total = array_sum(array_map('score_to_gpa', $scores));
    return round($total / count($scores), 2);
}

// ── Redirect helper ──────────────────────────────────────
function redirect(string $url): void {
    header("Location: $url"); exit;
}

function back_button() {
    echo '
    <div style="margin-bottom:20px;">
        <a href="javascript:history.back();" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Back
        </a>
    </div>';
}


