<?php
require_once 'functions.php';
require_role('student');

$student = db_prepare_execute(
    "SELECT s.id, s.name, s.reg_no, s.class FROM students s WHERE s.user_id=?",
    'i', [$_SESSION['user_id']]
)->get_result()->fetch_assoc();

$scores = [];
$chartLabels = [];
$chartData   = [];
$subjectColors = [];

if ($student) {
    $rows = db_prepare_execute(
        "SELECT sub.name, m.score FROM marks m
         JOIN subjects sub ON sub.id=m.subject_id
         WHERE m.student_id=?",
        'i', [$student['id']]
    )->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($rows as $r) {
        $scores[]      = (float)$r['score'];
        $chartLabels[] = $r['name'];
        $chartData[]   = (float)$r['score'];
    }
}

$gpa = calculate_gpa($scores);
$avgScore = $scores ? round(array_sum($scores) / count($scores), 1) : 0;
$highestScore = $scores ? max($scores) : 0;
$lowestScore = $scores ? min($scores) : 0;
$passCount = $scores ? count(array_filter($scores, fn($s) => $s >= 50)) : 0;
$failCount = $scores ? count(array_filter($scores, fn($s) => $s < 50)) : 0;

// Announcements visible to student
$anns = db()->query(
    "SELECT a.title, a.body, a.created_at, u.username
     FROM announcements a JOIN users u ON u.id=a.user_id
     WHERE a.role_target IN ('all','student')
     ORDER BY a.created_at DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

$page_title    = 'Student Dashboard';
$page_subtitle = 'My Academic Overview';
include 'includes/header.php';
?>

<!-- ==========================================================
     STUDENT DASHBOARD CUSTOM STYLES
     ========================================================== -->
<style>
/* Student Dashboard Specific Styles */
.student-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.student-stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 22px 24px;
    display: flex;
    gap: 16px;
    align-items: center;
    box-shadow: var(--shadow, 0 8px 25px rgba(0,0,0,0.08));
    transition: 0.3s;
    border: 1px solid rgba(0,0,0,0.03);
}

.student-stat-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 35px rgba(0,0,0,0.12);
}

.student-stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 26px;
    flex-shrink: 0;
}

.student-stat-icon.blue {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
}

.student-stat-icon.green {
    background: linear-gradient(135deg, #16a34a, #22c55e);
}

.student-stat-icon.orange {
    background: linear-gradient(135deg, #ea580c, #f97316);
}

.student-stat-icon.purple {
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
}

.student-stat-icon.cyan {
    background: linear-gradient(135deg, #0891b2, #06b6d4);
}

.student-stat-icon.pink {
    background: linear-gradient(135deg, #db2777, #ec4899);
}

.student-stat-icon.red {
    background: linear-gradient(135deg, #dc2626, #ef4444);
}

.student-stat-info {
    flex: 1;
    min-width: 0;
}

.student-stat-label {
    font-size: 12px;
    color: var(--muted, #64748b);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.student-stat-value {
    font-size: 28px;
    font-weight: 700;
    color: var(--text, #1e293b);
    line-height: 1.2;
}

.student-stat-value.small {
    font-size: 18px;
}

.student-stat-desc {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 2px;
}

/* Welcome Section */
.student-welcome {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
    color: white;
    padding: 28px 32px;
    border-radius: 16px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.25);
}

.student-welcome h2 {
    margin: 0;
    font-weight: 700;
    font-size: 22px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.student-welcome .greeting {
    opacity: 0.9;
    font-size: 13px;
    margin-top: 4px;
}

.student-welcome .welcome-badge {
    display: inline-block;
    padding: 8px 18px;
    background: rgba(255,255,255,0.15);
    border-radius: 30px;
    font-size: 13px;
    backdrop-filter: blur(10px);
}

/* Chart Container */
.student-chart-box {
    max-width: 100%;
    height: 300px;
    position: relative;
}

.student-chart-stats {
    display: flex;
    gap: 15px;
    justify-content: center;
    padding-top: 14px;
    border-top: 1px solid #e9edf2;
    margin-top: 14px;
    flex-wrap: wrap;
}

.student-chart-stat {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    color: #64748b;
}

.student-chart-stat strong {
    color: #1e293b;
    font-weight: 600;
}

.student-chart-stat i {
    color: #2563eb;
    font-size: 14px;
}

/* Announcements */
.student-announcement {
    padding: 14px 0;
    border-bottom: 1px solid #f1f5f9;
    transition: 0.3s;
}

.student-announcement:last-child {
    border-bottom: none;
}

.student-announcement:hover {
    background: #f8fafc;
    margin: 0 -16px;
    padding: 14px 16px;
    border-radius: 10px;
}

.student-announcement h5 {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 6px 0;
}

.student-announcement p {
    font-size: 13px;
    color: #475569;
    margin: 0 0 8px 0;
    line-height: 1.6;
}

.student-announcement-meta {
    font-size: 11px;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.student-announcement-meta i {
    font-size: 11px;
}

/* Quick Links */
.student-quick-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 10px;
    margin-top: 14px;
}

.student-quick-link {
    background: #f8fafc;
    padding: 14px;
    border-radius: 12px;
    text-align: center;
    transition: 0.3s;
    border: 1px solid #e9edf2;
    text-decoration: none;
    color: #1e293b;
}

.student-quick-link:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    border-color: #2563eb;
    background: white;
}

.student-quick-link i {
    font-size: 24px;
    color: #2563eb;
    display: block;
    margin-bottom: 6px;
}

.student-quick-link span {
    font-size: 12px;
    font-weight: 500;
}

/* Subject Performance Mini */
.student-subject-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    border-bottom: 1px solid #f1f5f9;
}

.student-subject-item:last-child {
    border-bottom: none;
}

.student-subject-item .subject-name {
    font-weight: 500;
    font-size: 13px;
    color: #1e293b;
}

.student-subject-item .subject-score {
    font-weight: 600;
    font-size: 13px;
}

.student-subject-item .subject-score.pass {
    color: #16a34a;
}

.student-subject-item .subject-score.fail {
    color: #dc2626;
}

.student-progress-bar {
    width: 100%;
    height: 5px;
    background: #e9edf2;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 3px;
}

.student-progress-bar .progress-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 1s ease;
}

/* Pass/Fail Indicators */
.pass-fail-stats {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 10px;
}

.pass-fail-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
}

.pass-fail-item .dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}

.pass-fail-item .dot.pass {
    background: #16a34a;
}

.pass-fail-item .dot.fail {
    background: #dc2626;
}

/* Empty State */
.student-empty {
    text-align: center;
    padding: 40px 20px;
    color: #94a3b8;
}

.student-empty i {
    font-size: 48px;
    display: block;
    margin-bottom: 16px;
    opacity: 0.5;
}

.student-empty p {
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

.student-stat-card {
    animation: fadeSlideUp 0.5s ease forwards;
}

.student-stat-card:nth-child(2) { animation-delay: 0.1s; }
.student-stat-card:nth-child(3) { animation-delay: 0.2s; }
.student-stat-card:nth-child(4) { animation-delay: 0.3s; }

.student-welcome {
    animation: fadeSlideUp 0.5s ease forwards;
}

/* Responsive */
@media (max-width: 768px) {
    .student-welcome {
        flex-direction: column;
        text-align: center;
        padding: 20px;
    }
    
    .student-welcome h2 {
        font-size: 20px;
    }
    
    .student-chart-box {
        height: 220px;
    }
    
    .student-quick-links {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .student-stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .student-stat-card {
        padding: 16px;
    }
}

@media (max-width: 480px) {
    .student-stats-grid {
        grid-template-columns: 1fr;
    }
}

/* Print Styles */
@media print {
    .student-welcome { background: #2563eb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .student-stat-card { box-shadow: none; border: 1px solid #ddd; }
    .student-stat-card:hover { transform: none; }
}
</style>

<!-- ==========================================================
     WELCOME SECTION
     ========================================================== -->
<div class="student-welcome">
    <div>
        <h2>
            <i class="bi bi-person-circle"></i>
            Welcome, <?= htmlspecialchars($student['name'] ?? 'Student') ?>!
        </h2>
        <div class="greeting">
            <i class="bi bi-calendar3"></i> <?= date('l, F d, Y') ?>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <i class="bi bi-clock"></i> <?= date('h:i A') ?>
        </div>
    </div>
    <div class="welcome-badge">
        <i class="bi bi-mortarboard-fill"></i>
        Student Portal
    </div>
</div>

<!-- ==========================================================
     STATISTICS CARDS
     ========================================================== -->
<div class="student-stats-grid">
    <div class="student-stat-card">
        <div class="student-stat-icon blue">
            <i class="bi bi-person-badge"></i>
        </div>
        <div class="student-stat-info">
            <div class="student-stat-label">Reg Number</div>
            <div class="student-stat-value small">
                <?= htmlspecialchars($student['reg_no'] ?? '—') ?>
            </div>
            <div class="student-stat-desc">Your registration ID</div>
        </div>
    </div>
    
    <div class="student-stat-card">
        <div class="student-stat-icon green">
            <i class="bi bi-building"></i>
        </div>
        <div class="student-stat-info">
            <div class="student-stat-label">Class</div>
            <div class="student-stat-value small">
                <?= htmlspecialchars($student['class'] ?? '—') ?>
            </div>
            <div class="student-stat-desc">Enrolled class</div>
        </div>
    </div>
    
    <div class="student-stat-card">
        <div class="student-stat-icon orange">
            <i class="bi bi-book-fill"></i>
        </div>
        <div class="student-stat-info">
            <div class="student-stat-label">Subjects</div>
            <div class="student-stat-value" data-counter="<?= count($scores) ?>">
                <?= count($scores) ?>
            </div>
            <div class="student-stat-desc">With recorded marks</div>
        </div>
    </div>
    
    <div class="student-stat-card">
        <div class="student-stat-icon purple">
            <i class="bi bi-star-fill"></i>
        </div>
        <div class="student-stat-info">
            <div class="student-stat-label">GPA</div>
            <div class="student-stat-value"><?= number_format($gpa, 2) ?></div>
            <div class="student-stat-desc">Cumulative grade point average</div>
        </div>
    </div>
</div>

<!-- ==========================================================
     MAIN CONTENT GRID
     ========================================================== -->
<div class="row g-4">

    <!-- Chart Section -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <div class="card-icon"><i class="bi bi-bar-chart-fill"></i></div>
                My Scores by Subject
                <div class="ms-auto d-flex gap-2">
                    <a href="view_marks.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-table"></i> Full Table
                    </a>
                    <a href="reports.php" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-file-earmark-text"></i> Report
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if ($chartLabels): ?>
                    <div class="student-chart-box">
                        <canvas id="marksChart"></canvas>
                    </div>
                    
                    <div class="student-chart-stats">
                        <div class="student-chart-stat">
                            <i class="bi bi-calculator"></i>
                            Average: <strong><?= $avgScore ?>%</strong>
                        </div>
                        <div class="student-chart-stat">
                            <i class="bi bi-star"></i>
                            GPA: <strong><?= number_format($gpa, 2) ?></strong>
                        </div>
                        <div class="student-chart-stat">
                            <i class="bi bi-book"></i>
                            Subjects: <strong><?= count($scores) ?></strong>
                        </div>
                        <div class="student-chart-stat">
                            <i class="bi bi-trophy"></i>
                            Highest: <strong><?= $highestScore ?>%</strong>
                        </div>
                    </div>
                    
                    <!-- Pass/Fail Stats -->
                    <div class="pass-fail-stats">
                        <div class="pass-fail-item">
                            <span class="dot pass"></span>
                            Pass: <strong><?= $passCount ?></strong>
                        </div>
                        <div class="pass-fail-item">
                            <span class="dot fail"></span>
                            Fail: <strong><?= $failCount ?></strong>
                        </div>
                        <div class="pass-fail-item">
                            <i class="bi bi-arrow-up" style="color:#16a34a;"></i>
                            Highest: <strong><?= $highestScore ?>%</strong>
                        </div>
                        <div class="pass-fail-item">
                            <i class="bi bi-arrow-down" style="color:#dc2626;"></i>
                            Lowest: <strong><?= $lowestScore ?>%</strong>
                        </div>
                    </div>
                    
                    <!-- Subject Performance List -->
                    <div class="mt-3 pt-3 border-top">
                        <h6 style="font-weight:600;font-size:13px;margin-bottom:10px;color:#1e293b;">
                            <i class="bi bi-list-check"></i> Subject Performance
                        </h6>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                            <?php 
                            $displaySubjects = array_slice($rows, 0, 6);
                            foreach ($displaySubjects as $subject): 
                                $score = (float)$subject['score'];
                                $pass = $score >= 50;
                            ?>
                            <div>
                                <div class="student-subject-item">
                                    <span class="subject-name"><?= htmlspecialchars($subject['name']) ?></span>
                                    <span class="subject-score <?= $pass ? 'pass' : 'fail' ?>">
                                        <?= number_format($score, 1) ?>%
                                    </span>
                                </div>
                                <div class="student-progress-bar">
                                    <div class="progress-fill" 
                                         style="width: <?= min($score, 100) ?>%; 
                                                background: <?= $pass ? '#16a34a' : '#dc2626' ?>;">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($rows) > 6): ?>
                        <div class="text-center mt-2">
                            <a href="view_marks.php" class="btn btn-sm btn-link text-decoration-none">
                                View all <?= count($rows) ?> subjects <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                <?php else: ?>
                    <div class="student-empty">
                        <i class="bi bi-bar-chart"></i>
                        <p>No marks recorded yet. Check back after your results are entered.</p>
                        <a href="view_marks.php" class="btn btn-primary btn-sm mt-2">
                            <i class="bi bi-table"></i> View Marks
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Announcements Section -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <div class="card-icon"><i class="bi bi-megaphone-fill"></i></div>
                Announcements
                <a href="announcements.php" class="btn btn-sm btn-outline-primary ms-auto">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-body">
                <?php if ($anns): ?>
                    <?php foreach ($anns as $a): ?>
                    <div class="student-announcement">
                        <h5>
                            <?= htmlspecialchars($a['title']) ?>
                            <span style="font-size:10px;font-weight:400;color:#94a3b8;display:inline-block;margin-left:6px;">
                                <i class="bi bi-clock"></i> <?= date('M d', strtotime($a['created_at'])) ?>
                            </span>
                        </h5>
                        <p><?= htmlspecialchars(mb_strimwidth($a['body'], 0, 120, '…')) ?></p>
                        <div class="student-announcement-meta">
                            <span>
                                <i class="bi bi-person"></i> <?= htmlspecialchars($a['username']) ?>
                            </span>
                            <span>
                                <i class="bi bi-calendar3"></i> <?= date('d M Y', strtotime($a['created_at'])) ?>
                            </span>
                            <span>
                                <i class="bi bi-clock"></i> <?= date('h:i A', strtotime($a['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="student-empty">
                        <i class="bi bi-megaphone"></i>
                        <p>No announcements available.</p>
                    </div>
                <?php endif; ?>
                
                <!-- Quick Links -->
                <div class="mt-3 pt-3 border-top">
                    <h6 style="font-weight:600;font-size:13px;color:#64748b;margin-bottom:10px;">
                        <i class="bi bi-link"></i> Quick Links
                    </h6>
                    <div class="student-quick-links">
                        <a href="view_marks.php" class="student-quick-link">
                            <i class="bi bi-table"></i>
                            <span>My Marks</span>
                        </a>
                        <a href="reports.php" class="student-quick-link">
                            <i class="bi bi-file-earmark-text"></i>
                            <span>Reports</span>
                        </a>
                        <a href="profile.php" class="student-quick-link">
                            <i class="bi bi-person-gear"></i>
                            <span>Profile</span>
                        </a>
                        <a href="announcements.php" class="student-quick-link">
                            <i class="bi bi-megaphone"></i>
                            <span>Announcements</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div><!-- /row -->

<!-- ==========================================================
     JAVASCRIPT ENHANCEMENTS
     ========================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Counter animation for stat cards
    const counters = document.querySelectorAll('[data-counter]');
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-counter'));
        if (target > 0) {
            const duration = 1000;
            const step = Math.ceil(target / (duration / 16));
            let current = 0;
            
            const updateCounter = () => {
                current += step;
                if (current >= target) {
                    counter.textContent = target;
                    return;
                }
                counter.textContent = current;
                requestAnimationFrame(updateCounter);
            };
            
            // Start animation when element is visible
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        updateCounter();
                        observer.disconnect();
                    }
                });
            });
            observer.observe(counter);
        }
    });
    
    // Progress bar animation
    const progressBars = document.querySelectorAll('.progress-fill');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 400);
    });
});

// ==========================================================
// CHART RENDERING FUNCTION (Ensures chart is never empty)
// ==========================================================
function renderBarChart(canvasId, labels, data) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    // If no data, show placeholder message
    if (!labels || labels.length === 0 || !data || data.length === 0) {
        canvas.parentElement.innerHTML = `
            <div style="text-align:center;padding:40px 20px;color:#94a3b8;">
                <i class="bi bi-bar-chart" style="font-size:48px;display:block;margin-bottom:16px;opacity:0.5;"></i>
                <p style="font-size:15px;margin:0;">No marks available to display.</p>
                <p style="font-size:13px;margin-top:4px;">Check back after your results are entered.</p>
            </div>
        `;
        return;
    }
    
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        // Load Chart.js dynamically
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        script.onload = function() {
            createChart(canvasId, labels, data);
        };
        document.head.appendChild(script);
    } else {
        createChart(canvasId, labels, data);
    }
}

function createChart(canvasId, labels, data) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    // Generate colors based on data
    const colors = data.map(score => {
        if (score >= 70) return 'rgba(22, 163, 74, 0.8)';      // Green - Excellent
        if (score >= 50) return 'rgba(37, 99, 235, 0.8)';      // Blue - Good
        if (score >= 40) return 'rgba(245, 158, 11, 0.8)';     // Yellow - Average
        return 'rgba(220, 38, 38, 0.8)';                       // Red - Poor
    });
    
    const borderColors = colors.map(c => c.replace('0.8', '1'));
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Score (%)',
                data: data,
                backgroundColor: colors,
                borderColor: borderColors,
                borderWidth: 2,
                borderRadius: 6,
                maxBarThickness: 40,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const score = context.parsed.y;
                            const grade = score >= 70 ? 'A' : (score >= 60 ? 'B' : (score >= 50 ? 'C' : (score >= 40 ? 'D' : 'F')));
                            return `Score: ${score}% (Grade: ${grade})`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        },
                        stepSize: 20
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

// Initialize chart when page loads
document.addEventListener('DOMContentLoaded', function() {
    const chartLabels = <?= json_encode($chartLabels) ?>;
    const chartData = <?= json_encode($chartData) ?>;
    
    // Small delay to ensure DOM is ready
    setTimeout(function() {
        renderBarChart('marksChart', chartLabels, chartData);
    }, 100);
});
</script>

<?php include 'includes/footer.php'; ?>