<?php
$current = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar" id="sidebar">

    <div class="sidebar-header">
        <div class="logo">
            <i class="bi bi-mortarboard-fill"></i>
            <span>UniAdmin</span>
        </div>
    </div>

    <ul class="sidebar-menu">

        <li>
            <a href="superadmin_dashboard.php" class="<?= $current=='superadmin_dashboard.php'?'active':'' ?>">
                <i class="bi bi-speedometer2"></i>
                Dashboard
            </a>
        </li>

        <li>
            <a href="manage_users.php" class="<?= $current=='manage_users.php'?'active':'' ?>">
                <i class="bi bi-people-fill"></i>
                Users
            </a>
        </li>

        <li>
            <a href="manage_marks.php" class="<?= $current=='manage_marks.php'?'active':'' ?>">
                <i class="bi bi-journal-check"></i>
                Marks
            </a>
        </li>

        <li>
            <a href="reports.php" class="<?= $current=='reports.php'?'active':'' ?>">
                <i class="bi bi-bar-chart-fill"></i>
                Reports
            </a>
        </li>

        <li>
            <a href="announcements.php" class="<?= $current=='announcements.php'?'active':'' ?>">
                <i class="bi bi-megaphone-fill"></i>
                Announcements
            </a>
        </li>

        <li>
            <a href="activity_logs.php" class="<?= $current=='activity_logs.php'?'active':'' ?>">
                <i class="bi bi-clock-history"></i>
                Activity Logs
            </a>
        </li>

        <li>
            <a href="profile.php" class="<?= $current=='profile.php'?'active':'' ?>">
                <i class="bi bi-person-circle"></i>
                Profile
            </a>
        </li>

        <li class="logout">
            <a href="logout.php">
                <i class="bi bi-box-arrow-right"></i>
                Logout
            </a>
        </li>

    </ul>

</aside>