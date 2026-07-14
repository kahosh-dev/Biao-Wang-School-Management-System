<?php
require_once 'functions.php';
require_role(['admin','manager','superadmin','student']);

$student_id = (int)($_GET['student_id'] ?? 0);

// Students can only see own report
if (is_student()) {
    $student = db_prepare_execute(
        "SELECT id, name, reg_no, class FROM students WHERE user_id=?",
        'i', [$_SESSION['user_id']]
    )->get_result()->fetch_assoc();
    if (!$student) { redirect('student_dashboard.php'); }
    $student_id = $student['id'];
} 
else {
    if (!$student_id) {
        $student = db_prepare_execute(
            "SELECT id FROM students LIMIT 1"
        )->get_result()->fetch_assoc();

        if ($student) {
            $student_id = $student['id'];
        } else {
            die('No students found.');
        }
    }

    $student = db_prepare_execute(
        "SELECT * FROM students WHERE id=?",
        'i',
        [$student_id]
    )->get_result()->fetch_assoc();

    if (!$student) {
        die('Student not found.');
    }
}

// All marks
$marks = db_prepare_execute(
    "SELECT m.score, sub.name subject_name, t.name term_name, t.year
     FROM marks m
     JOIN subjects sub ON sub.id=m.subject_id
     JOIN terms t ON t.id=m.term_id
     WHERE m.student_id=?
     ORDER BY t.year, t.name, sub.name",
    'i', [$student_id]
)->get_result()->fetch_all(MYSQLI_ASSOC);

$scores = array_map(fn($r) => (float)$r['score'], $marks);
$gpa    = calculate_gpa($scores);

// Grade distribution
$gradeDist = [];
foreach ($scores as $s) {
    $g = score_to_grade($s);
    $gradeDist[$g] = ($gradeDist[$g] ?? 0) + 1;
}
ksort($gradeDist);

// Calculate additional stats
$totalSubjects = count($marks);
$averageScore = $scores ? array_sum($scores) / count($scores) : 0;
$highestScore = $scores ? max($scores) : 0;
$lowestScore = $scores ? min($scores) : 0;

// PDF Export
if (isset($_GET['pdf']) && class_exists('TCPDF')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

$page_title = 'Report — ' . htmlspecialchars($student['name']);
include 'includes/header.php';
?>

<!-- Additional CSS for report -->
<style>
.report-header-bar {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    color: white;
    padding: 30px 35px;
    border-radius: var(--radius, 16px);
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.25);
}

.report-header-bar h2 {
    margin: 0;
    font-weight: 700;
    font-size: 26px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.report-header-bar .sub-info {
    opacity: 0.9;
    font-size: 14px;
    margin-top: 4px;
}

.report-header-bar .actions {
    display: flex;
    gap: 10px;
}

.report-header-bar .btn {
    padding: 10px 20px;
    border-radius: 10px;
    border: none;
    background: rgba(255,255,255,0.15);
    color: white;
    backdrop-filter: blur(10px);
    transition: 0.3s;
    cursor: pointer;
    font-weight: 500;
    font-size: 14px;
}

.report-header-bar .btn:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-box-custom {
    background: white;
    padding: 24px;
    border-radius: var(--radius, 14px);
    box-shadow: var(--shadow, 0 8px 25px rgba(0,0,0,0.08));
    display: flex;
    align-items: center;
    gap: 16px;
    transition: 0.3s;
    border: 1px solid rgba(0,0,0,0.03);
}

.stat-box-custom:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 35px rgba(0,0,0,0.12);
}

.stat-icon-custom {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    flex-shrink: 0;
}

.stat-info-custom p {
    margin: 0;
    color: #64748b;
    font-size: 13px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.stat-info-custom h3 {
    margin: 2px 0 0;
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
}

.report-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
    align-items: start;
}

@media (max-width: 1024px) {
    .report-grid {
        grid-template-columns: 1fr;
    }
}

.card-custom {
    background: white;
    border-radius: var(--radius, 14px);
    box-shadow: var(--shadow, 0 8px 25px rgba(0,0,0,0.08));
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.03);
    transition: 0.3s;
}

.card-custom:hover {
    box-shadow: 0 12px 35px rgba(0,0,0,0.12);
}

.card-header-custom {
    padding: 18px 24px;
    background: #f8fafc;
    border-bottom: 1px solid #e9edf2;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    color: #0f172a;
    font-size: 16px;
}

.card-header-custom i {
    color: #2563eb;
}

.card-body-custom {
    padding: 24px;
}

.table-wrap {
    overflow-x: auto;
}

.table th {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}

.grade-badge {
    display: inline-block;
    padding: 5px 14px;
    border-radius: 30px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.grade-badge.grade-a { background: #dcfce7; color: #166534; }
.grade-badge.grade-b { background: #dbeafe; color: #1e40af; }
.grade-badge.grade-c { background: #fef3c7; color: #92400e; }
.grade-badge.grade-d { background: #fef3c7; color: #92400e; }
.grade-badge.grade-f { background: #fee2e2; color: #991b1b; }

.chart-container {
    max-width: 280px;
    margin: 0 auto;
    position: relative;
}

.summary-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.summary-list li {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.9rem;
}

.summary-list li:last-child {
    border-bottom: none;
}

.summary-list li strong {
    color: #64748b;
    font-weight: 500;
}

.summary-list li .value {
    font-weight: 600;
    color: #1e293b;
}

.performance-badge {
    display: inline-block;
    padding: 6px 16px;
    border-radius: 30px;
    font-size: 0.8rem;
    font-weight: 600;
}

.performance-badge.excellent { background: #dcfce7; color: #166534; }
.performance-badge.good { background: #dbeafe; color: #1e40af; }
.performance-badge.average { background: #fef3c7; color: #92400e; }
.performance-badge.poor { background: #fee2e2; color: #991b1b; }

.legend {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
    margin-top: 16px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: #475569;
}

.legend-color {
    width: 14px;
    height: 14px;
    border-radius: 4px;
}

@media print {
    .report-header-bar .actions { display: none; }
    .stat-box-custom { box-shadow: none; border: 1px solid #ddd; }
    .card-custom { box-shadow: none; border: 1px solid #ddd; }
    .stat-box-custom:hover { transform: none; }
    .card-custom:hover { box-shadow: none; }
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.stat-box-custom, .card-custom {
    animation: fadeUp 0.5s ease forwards;
}

.stat-box-custom:nth-child(2) { animation-delay: 0.1s; }
.stat-box-custom:nth-child(3) { animation-delay: 0.2s; }
.stat-box-custom:nth-child(4) { animation-delay: 0.3s; }
</style>

<!-- Report Header -->
<div class="report-header-bar">
    <div>
        <h2>
            <i class="fas fa-graduation-cap"></i>
            Academic Report
        </h2>
        <div class="sub-info">
            <i class="fas fa-calendar-alt"></i> <?= date('F d, Y') ?>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <i class="fas fa-id-card"></i> <?= htmlspecialchars($student['reg_no']) ?>
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <i class="fas fa-user"></i> <?= htmlspecialchars($student['name']) ?>
        </div>
    </div>
    <div class="actions">
        <button onclick="window.print()" class="btn">
            <i class="fas fa-print"></i> Print
        </button>
        <?php if (class_exists('TCPDF')): ?>
        <button onclick="window.location.href='?pdf=1&student_id=<?= $student_id ?>'" class="btn">
            <i class="fas fa-file-pdf"></i> PDF
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-box-custom">
        <div class="stat-icon-custom" style="background: linear-gradient(135deg, #2563eb, #4f46e5);">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="stat-info-custom">
            <p>Student</p>
            <h3 style="font-size:16px;"><?= htmlspecialchars($student['name']) ?></h3>
        </div>
    </div>
    <div class="stat-box-custom">
        <div class="stat-icon-custom" style="background: linear-gradient(135deg, #16a34a, #22c55e);">
            <i class="fas fa-book"></i>
        </div>
        <div class="stat-info-custom">
            <p>Subjects</p>
            <h3><?= $totalSubjects ?></h3>
        </div>
    </div>
    <div class="stat-box-custom">
        <div class="stat-icon-custom" style="background: linear-gradient(135deg, #ea580c, #f97316);">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-info-custom">
            <p>GPA</p>
            <h3><?= number_format($gpa, 2) ?></h3>
        </div>
    </div>
    <div class="stat-box-custom">
        <div class="stat-icon-custom" style="background: linear-gradient(135deg, #7c3aed, #8b5cf6);">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-info-custom">
            <p>Average</p>
            <h3><?= number_format($averageScore, 1) ?>%</h3>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="report-grid">
    <!-- Marks Table -->
    <div class="card-custom">
        <div class="card-header-custom">
            <i class="fas fa-list"></i> Marks Report
            <span style="margin-left:auto;font-size:13px;color:#64748b;font-weight:400;">
                <?= $totalSubjects ?> records
            </span>
        </div>
        <div class="card-body-custom">
            <?php if ($marks): ?>
            <div class="table-wrap">
                <table class="table table-striped">
                    <thead style="background: #f1f5f9; color: #1e293b;">
                        <tr>
                            <th>#</th>
                            <th>Subject</th>
                            <th>Term</th>
                            <th>Score</th>
                            <th>Grade</th>
                            <th>GPA Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($marks as $i => $m): ?>
                        <?php 
                        $g = score_to_grade((float)$m['score']);
                        $gradeClass = match($g[0]) {
                            'A' => 'grade-a',
                            'B' => 'grade-b',
                            'C' => 'grade-c',
                            'D' => 'grade-d',
                            'F' => 'grade-f',
                            default => 'grade-b'
                        };
                        ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= htmlspecialchars($m['subject_name']) ?></strong></td>
                            <td><?= htmlspecialchars($m['term_name']) ?> <?= $m['year'] ?></td>
                            <td><?= number_format($m['score'], 1) ?>%</td>
                            <td>
                                <span class="grade-badge <?= $gradeClass ?>">
                                    <?= $g ?>
                                </span>
                            </td>
                            <td><?= number_format(score_to_gpa((float)$m['score']), 1) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:40px 20px;color:#94a3b8;">
                <i class="fas fa-inbox" style="font-size:48px;display:block;margin-bottom:16px;opacity:0.5;"></i>
                <p style="font-size:16px;">No marks recorded for this student.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div>
        <!-- Grade Distribution Chart -->
        <div class="card-custom" style="margin-bottom:20px;">
            <div class="card-header-custom">
                <i class="fas fa-chart-pie"></i> Grade Distribution
            </div>
            <div class="card-body-custom">
                <?php if ($gradeDist): ?>
                <div class="chart-container">
                    <canvas id="gradeChart"></canvas>
                </div>
                
                <!-- Chart Legend -->
                <div class="legend">
                    <?php 
                    $colors = ['#22c55e', '#3b82f6', '#f59e0b', '#f97316', '#ef4444'];
                    $index = 0;
                    foreach ($gradeDist as $grade => $count): 
                    ?>
                    <div class="legend-item">
                        <span class="legend-color" style="background: <?= $colors[$index % count($colors)] ?>;"></span>
                        <?= $grade ?>: <?= $count ?>
                    </div>
                    <?php $index++; endforeach; ?>
                </div>
                <?php else: ?>
                <p style="text-align:center;color:#94a3b8;margin:0;">No data available</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Summary -->
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-info-circle"></i> Summary
            </div>
            <div class="card-body-custom">
                <ul class="summary-list">
                    <li>
                        <strong>Total Subjects</strong>
                        <span class="value"><?= $totalSubjects ?></span>
                    </li>
                    <li>
                        <strong>Average Score</strong>
                        <span class="value"><?= number_format($averageScore, 1) ?>%</span>
                    </li>
                    <li>
                        <strong>Highest Score</strong>
                        <span class="value" style="color:#16a34a;"><?= number_format($highestScore, 1) ?>%</span>
                    </li>
                    <li>
                        <strong>Lowest Score</strong>
                        <span class="value" style="color:#dc2626;"><?= number_format($lowestScore, 1) ?>%</span>
                    </li>
                    <li>
                        <strong>GPA</strong>
                        <span class="value" style="color:#2563eb;font-size:18px;"><?= number_format($gpa, 2) ?></span>
                    </li>
                    <li>
                        <strong>Class</strong>
                        <span class="value"><?= htmlspecialchars($student['class'] ?? '—') ?></span>
                    </li>
                    <li style="border-bottom:none;padding-top:16px;border-top:2px solid #f1f5f9;margin-top:4px;">
                        <strong>Performance</strong>
                        <span class="value">
                            <?php 
                            $perfClass = '';
                            $perfText = '';
                            if ($gpa >= 3.5) {
                                $perfClass = 'excellent';
                                $perfText = '🌟 Excellent';
                            } elseif ($gpa >= 2.5) {
                                $perfClass = 'good';
                                $perfText = '👍 Good';
                            } elseif ($gpa >= 1.5) {
                                $perfClass = 'average';
                                $perfText = '📖 Average';
                            } else {
                                $perfClass = 'poor';
                                $perfText = '⚠️ Needs Improvement';
                            }
                            ?>
                            <span class="performance-badge <?= $perfClass ?>"><?= $perfText ?></span>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Grade distribution chart
    const ctx = document.getElementById('gradeChart');
    
    <?php if ($gradeDist): ?>
    const grades = <?= json_encode(array_keys($gradeDist)) ?>;
    const counts = <?= json_encode(array_values($gradeDist)) ?>;
    const colors = ['#22c55e', '#3b82f6', '#f59e0b', '#f97316', '#ef4444'];
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: grades,
            datasets: [{
                data: counts,
                backgroundColor: colors.slice(0, grades.length),
                borderWidth: 3,
                borderColor: '#ffffff',
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
    <?php endif; ?>
});
</script>

<?php include 'includes/footer.php'; ?>