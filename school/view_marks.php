<?php
require_once 'functions.php';
require_role('student');

$student = db_prepare_execute(
    "SELECT id, name, reg_no, class FROM students WHERE user_id=?",
    'i', [$_SESSION['user_id']]
)->get_result()->fetch_assoc();

if (!$student) {
    $page_title = 'My Marks';
    include 'includes/header.php';
    echo '<div class="alert alert-info"><i class="bi bi-info-circle-fill me-2"></i>Your student profile is not set up yet. Please contact your administrator.</div>';
    include 'includes/footer.php';
    exit;
}

// Filter by term
$termFilter = (int)($_GET['term_id'] ?? 0);
$terms      = db()->query("SELECT * FROM terms ORDER BY year, name")->fetch_all(MYSQLI_ASSOC);

$sql    = "SELECT m.score, sub.name subject_name, t.id term_id, t.name term_name, t.year
           FROM marks m
           JOIN subjects sub ON sub.id=m.subject_id
           JOIN terms t ON t.id=m.term_id
           WHERE m.student_id=?";
$params = [$student['id']];
$types  = 'i';

if ($termFilter) { $sql .= " AND t.id=?"; $params[] = $termFilter; $types .= 'i'; }
$sql .= " ORDER BY t.year, t.name, sub.name";

$marks  = db_prepare_execute($sql, $types, $params)->get_result()->fetch_all(MYSQLI_ASSOC);
$scores = array_map(fn($r) => (float)$r['score'], $marks);
$gpa    = calculate_gpa($scores);
$avg    = $scores ? round(array_sum($scores) / count($scores), 1) : 0;

// Calculate additional stats
$passCount = count(array_filter($scores, fn($s) => $s >= 50));
$failCount = count(array_filter($scores, fn($s) => $s < 50));
$highestScore = $scores ? max($scores) : 0;
$lowestScore = $scores ? min($scores) : 0;

$page_title    = 'My Marks';
$page_subtitle = htmlspecialchars($student['name']);
include 'includes/header.php';
?>

<!-- ==========================================================
     VIEW MARKS CUSTOM STYLES
     ========================================================== -->
<style>
/* View Marks Specific Styles */
.view-marks-wrapper {
    padding: 10px 0;
}

/* Stat Cards Enhancement */
.stat-cards-custom {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card-custom {
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

.stat-card-custom:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 35px rgba(0,0,0,0.12);
}

.stat-card-custom .stat-icon-custom {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 24px;
    flex-shrink: 0;
}

.stat-card-custom .stat-icon-custom.blue {
    background: linear-gradient(135deg, #2563eb, #4f46e5);
}

.stat-card-custom .stat-icon-custom.green {
    background: linear-gradient(135deg, #16a34a, #22c55e);
}

.stat-card-custom .stat-icon-custom.orange {
    background: linear-gradient(135deg, #ea580c, #f97316);
}

.stat-card-custom .stat-icon-custom.purple {
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
}

.stat-card-custom .stat-info-custom {
    flex: 1;
    min-width: 0;
}

.stat-card-custom .stat-info-custom .stat-label {
    font-size: 12px;
    color: #64748b;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.stat-card-custom .stat-info-custom .stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.2;
}

.stat-card-custom .stat-info-custom .stat-value.small {
    font-size: 18px;
}

.stat-card-custom .stat-info-custom .stat-desc {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 2px;
}

/* Score Bar Enhancement */
.score-bar-custom {
    display: flex;
    align-items: center;
    gap: 12px;
}

.score-bar-custom .bar-track {
    width: 80px;
    height: 6px;
    background: #f1f5f9;
    border-radius: 10px;
    overflow: hidden;
}

.score-bar-custom .bar-track .bar-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.8s ease;
}

.score-bar-custom .score-value {
    font-weight: 600;
    font-size: 14px;
    min-width: 40px;
}

/* Quick Stats */
.quick-stat {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
}

.quick-stat:last-child {
    border-bottom: none;
}

.quick-stat .qs-label {
    color: #64748b;
    font-size: 14px;
}

.quick-stat .qs-val {
    font-weight: 600;
    font-size: 16px;
    color: #1e293b;
}

/* Chart Container */
.chart-container-custom {
    width: 100%;
    height: 280px;
    position: relative;
}

/* Term Filter */
.term-filter {
    display: flex;
    align-items: center;
    gap: 10px;
}

.term-filter select {
    height: 36px;
    padding: 4px 30px 4px 12px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    font-size: 13px;
    background: #fafbfc;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transition: 0.3s;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
}

.term-filter select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    outline: none;
}

.term-filter .btn-clear {
    padding: 4px 10px;
    border-radius: 6px;
    border: 1px solid #d1d5db;
    background: #fff;
    color: #64748b;
    transition: 0.3s;
}

.term-filter .btn-clear:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}

/* Empty State */
.empty-state-custom {
    text-align: center;
    padding: 40px 20px;
    color: #94a3b8;
}

.empty-state-custom i {
    font-size: 48px;
    display: block;
    margin-bottom: 16px;
    opacity: 0.4;
}

.empty-state-custom p {
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

.stat-card-custom {
    animation: fadeSlideUp 0.5s ease forwards;
}

.stat-card-custom:nth-child(2) { animation-delay: 0.1s; }
.stat-card-custom:nth-child(3) { animation-delay: 0.2s; }
.stat-card-custom:nth-child(4) { animation-delay: 0.3s; }

/* Responsive */
@media (max-width: 768px) {
    .stat-cards-custom {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .stat-card-custom {
        padding: 16px;
    }
    
    .stat-card-custom .stat-info-custom .stat-value {
        font-size: 22px;
    }
    
    .chart-container-custom {
        height: 220px;
    }
    
    .term-filter select {
        width: 140px;
    }
}

@media (max-width: 480px) {
    .stat-cards-custom {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- ==========================================================
     STATISTICS CARDS
     ========================================================== -->
<div class="stat-cards-custom">
    <div class="stat-card-custom">
        <div class="stat-icon-custom blue">
            <i class="bi bi-person-graduation"></i>
        </div>
        <div class="stat-info-custom">
            <div class="stat-label">Student</div>
            <div class="stat-value small"><?= htmlspecialchars($student['name']) ?></div>
            <div class="stat-desc"><?= htmlspecialchars($student['class'] ?? '—') ?></div>
        </div>
    </div>
    
    <div class="stat-card-custom">
        <div class="stat-icon-custom green">
            <i class="bi bi-person-badge"></i>
        </div>
        <div class="stat-info-custom">
            <div class="stat-label">Reg Number</div>
            <div class="stat-value small"><?= htmlspecialchars($student['reg_no']) ?></div>
            <div class="stat-desc">Registration ID</div>
        </div>
    </div>
    
    <div class="stat-card-custom">
        <div class="stat-icon-custom orange">
            <i class="bi bi-book-fill"></i>
        </div>
        <div class="stat-info-custom">
            <div class="stat-label">Subjects</div>
            <div class="stat-value" data-counter="<?= count($marks) ?>"><?= count($marks) ?></div>
            <div class="stat-desc">Avg: <?= $avg ?>%</div>
        </div>
    </div>
    
    <div class="stat-card-custom">
        <div class="stat-icon-custom purple">
            <i class="bi bi-star-fill"></i>
        </div>
        <div class="stat-info-custom">
            <div class="stat-label">GPA</div>
            <div class="stat-value"><?= number_format($gpa, 2) ?></div>
            <div class="stat-desc">
                <?php if ($gpa >= 3.5): ?>
                    🌟 Excellent
                <?php elseif ($gpa >= 2.5): ?>
                    👍 Good
                <?php elseif ($gpa >= 1.5): ?>
                    📖 Average
                <?php else: ?>
                    ⚠️ Needs Improvement
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================
     MAIN CONTENT
     ========================================================== -->
<div class="row g-4">

    <!-- Marks Table -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="card-icon"><i class="bi bi-table"></i></div>
                My Marks
                
                <!-- Term Filter -->
                <form method="GET" class="term-filter ms-auto">
                    <select name="term_id" onchange="this.form.submit()">
                        <option value="">All Terms</option>
                        <?php foreach ($terms as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $termFilter == $t['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['name']) ?> <?= $t['year'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($termFilter): ?>
                        <a href="view_marks.php" class="btn-clear" title="Clear filter">
                            <i class="bi bi-x"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="card-body">

                <!-- Search -->
                <div class="table-toolbar">
                    <div class="table-search">
                        <i class="bi bi-search"></i>
                        <input type="text" id="tableSearch" placeholder="Filter marks by subject or term…">
                    </div>
                    <span style="font-size:12px;color:#94a3b8;">
                        <i class="bi bi-info-circle"></i> <?= count($marks) ?> records
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
                                <th style="width:80px;">GPA pts</th>
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
                                    <strong><?= htmlspecialchars($m['subject_name']) ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info">
                                        <?= htmlspecialchars($m['term_name']) ?> <?= $m['year'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="score-bar-custom">
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
                                <td><?= number_format(score_to_gpa((float)$m['score']), 1) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (!$marks): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state-custom">
                                        <i class="bi bi-journal"></i>
                                        <p>No marks recorded yet.</p>
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
                        Showing <strong id="visibleCount"><?= count($marks) ?></strong> mark(s)
                    </span>
                    <span>
                        <i class="bi bi-arrow-up" style="color:#16a34a;"></i> 
                        Pass: <strong><?= $passCount ?></strong>
                        &nbsp;|&nbsp;
                        <i class="bi bi-arrow-down" style="color:#dc2626;"></i> 
                        Fail: <strong><?= $failCount ?></strong>
                        &nbsp;|&nbsp;
                        <i class="bi bi-arrow-up" style="color:#2563eb;"></i> 
                        Highest: <strong><?= $highestScore ?>%</strong>
                        &nbsp;|&nbsp;
                        <i class="bi bi-arrow-down" style="color:#2563eb;"></i> 
                        Lowest: <strong><?= $lowestScore ?>%</strong>
                    </span>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Chart Panel -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <div class="card-icon"><i class="bi bi-bar-chart-fill"></i></div>
                Score Chart
                <span style="font-size:11px;color:#94a3b8;margin-left:auto;">
                    <?= $marks ? count($marks) . ' subjects' : '' ?>
                </span>
            </div>
            <div class="card-body">
                <?php if ($marks): ?>
                    <div class="chart-container-custom">
                        <canvas id="marksChart"></canvas>
                    </div>
                <?php else: ?>
                    <div class="empty-state-custom">
                        <i class="bi bi-bar-chart"></i>
                        <p>No data to display.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($marks): ?>
        <!-- Summary mini-card -->
        <div class="card mt-4">
            <div class="card-header">
                <div class="card-icon"><i class="bi bi-info-circle"></i></div>
                Performance Summary
            </div>
            <div class="card-body">
                <div class="quick-stat">
                    <span class="qs-label"><i class="bi bi-book"></i> Subjects</span>
                    <span class="qs-val"><?= count($marks) ?></span>
                </div>
                <div class="quick-stat">
                    <span class="qs-label"><i class="bi bi-calculator"></i> Average Score</span>
                    <span class="qs-val" style="color:#2563eb;"><?= $avg ?>%</span>
                </div>
                <div class="quick-stat">
                    <span class="qs-label"><i class="bi bi-star"></i> GPA</span>
                    <span class="qs-val" style="color:#7c3aed;"><?= number_format($gpa, 2) ?></span>
                </div>
                <div class="quick-stat">
                    <span class="qs-label"><i class="bi bi-check-circle" style="color:#16a34a;"></i> Pass</span>
                    <span class="qs-val" style="color:#16a34a;"><?= $passCount ?></span>
                </div>
                <div class="quick-stat mb-0">
                    <span class="qs-label"><i class="bi bi-x-circle" style="color:#dc2626;"></i> Fail</span>
                    <span class="qs-val" style="color:#dc2626;"><?= $failCount ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
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
    
    // Table search filter
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const table = document.getElementById('marksTable');
            const rows = table.querySelectorAll('tbody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                // Skip empty state row
                if (row.querySelector('.empty-state-custom')) {
                    row.style.display = '';
                    return;
                }
                
                const text = row.textContent.toLowerCase();
                const shouldShow = text.includes(searchTerm);
                row.style.display = shouldShow ? '' : 'none';
                if (shouldShow) visibleCount++;
            });
            
            // Update visible count
            const countSpan = document.getElementById('visibleCount');
            if (countSpan) {
                countSpan.textContent = visibleCount;
            }
        });
    }
    
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
    
    document.querySelectorAll('.score-bar-custom').forEach(bar => {
        const row = bar.closest('tr');
        if (row) {
            observer.observe(row);
        }
    });
});

// ==========================================================
// CHART RENDERING - FIXED VERSION
// ==========================================================
function renderBarChart(canvasId, labels, data) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        console.error('Canvas element not found:', canvasId);
        return;
    }
    
    // Check if we have data
    if (!labels || labels.length === 0 || !data || data.length === 0) {
        const container = canvas.parentElement;
        if (container) {
            container.innerHTML = `
                <div style="text-align:center;padding:40px 20px;color:#94a3b8;">
                    <i class="bi bi-bar-chart" style="font-size:48px;display:block;margin-bottom:16px;opacity:0.4;"></i>
                    <p style="font-size:15px;margin:0;">No marks available to display.</p>
                </div>
            `;
        }
        return;
    }
    
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') {
        // Load Chart.js dynamically
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
        script.onload = function() {
            createChart(canvasId, labels, data);
        };
        script.onerror = function() {
            console.error('Failed to load Chart.js');
            const container = canvas.parentElement;
            if (container) {
                container.innerHTML = `
                    <div style="text-align:center;padding:40px 20px;color:#94a3b8;">
                        <i class="bi bi-exclamation-triangle" style="font-size:48px;display:block;margin-bottom:16px;opacity:0.4;"></i>
                        <p style="font-size:15px;margin:0;">Failed to load chart library. Please refresh the page.</p>
                    </div>
                `;
            }
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
    
    // Generate colors based on scores
    const colors = data.map(score => {
        const s = parseFloat(score);
        if (s >= 70) return 'rgba(22, 163, 74, 0.8)';      // Green - Excellent
        if (s >= 60) return 'rgba(37, 99, 235, 0.8)';      // Blue - Good
        if (s >= 50) return 'rgba(59, 130, 246, 0.8)';     // Light Blue - Average
        if (s >= 40) return 'rgba(245, 158, 11, 0.8)';     // Yellow - Below Average
        return 'rgba(220, 38, 38, 0.8)';                   // Red - Poor
    });
    
    const borderColors = colors.map(c => c.replace('0.8', '1'));
    
    // Destroy existing chart if any
    if (canvas.chart) {
        canvas.chart.destroy();
    }
    
    // Create new chart
    canvas.chart = new Chart(ctx, {
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
                maxBarThickness: 45,
                hoverBackgroundColor: borderColors,
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
                    backgroundColor: 'rgba(15, 23, 42, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    cornerRadius: 10,
                    padding: 12,
                    callbacks: {
                        label: function(context) {
                            const score = context.parsed.y;
                            const grade = score >= 70 ? 'A' : 
                                         (score >= 60 ? 'B' : 
                                         (score >= 50 ? 'C' : 
                                         (score >= 40 ? 'D' : 'F')));
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
                        stepSize: 20,
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.06)',
                        drawBorder: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 10
                        },
                        maxRotation: 45,
                        minRotation: 0
                    }
                }
            },
            animation: {
                duration: 800,
                easing: 'easeOutQuart'
            }
        }
    });
}

// Initialize chart when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Wait a moment for DOM to be fully ready
    setTimeout(function() {
        const chartLabels = <?= json_encode(array_column($marks, 'subject_name')) ?>;
        const chartData = <?= json_encode(array_map(fn($r) => (float)$r['score'], $marks)) ?>;
        renderBarChart('marksChart', chartLabels, chartData);
    }, 200);
});
</script>

<?php include 'includes/footer.php'; ?>