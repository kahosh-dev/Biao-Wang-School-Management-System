<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1">

<title><?= $page_title ?? 'UniAdmin' ?></title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<link rel="stylesheet"
href="assets/css/style.css">

</head>

<body>
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <header class="topbar">

<div class="topbar-left">

<button
class="topbar-toggle"
id="sidebarToggle">

<i class="bi bi-list"></i>

</button>

<div>

<h3 class="mb-0">

<?= $page_title ?>

</h3>

<small class="text-muted">

University Student Administration System

</small>

</div>

</div>



<div class="topbar-right">

<div class="search-box">

<input
type="text"
placeholder="Search...">

<i class="bi bi-search"></i>

</div>

<button class="notification-btn">

<i class="bi bi-bell"></i>

<span class="notification-count">

3

</span>

</button>

<div
class="profile-menu">

<button
id="topbarUserBtn">

<div class="avatar">

<?= strtoupper(substr($_SESSION['username'],0,1)) ?>

</div>

<div>

<strong>

<?= $_SESSION['username'] ?>

</strong>

<br>

<small>

<?= ucfirst($_SESSION['role']) ?>

</small>

</div>

<i class="bi bi-chevron-down"></i>

</button>

<div
class="dropdown-menu"
id="topbarUserDropdown">

<a href="profile.php">

<i class="bi bi-person"></i>

Profile

</a>

<a href="settings.php">

<i class="bi bi-gear"></i>

Settings

</a>

<hr>

<a href="logout.php">

<i class="bi bi-box-arrow-right"></i>

Logout

</a>

</div>

</div>

</div>

</header>
<div class="container-fluid py-4">