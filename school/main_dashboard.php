<?php
require_once 'functions.php';

// TEMPORARILY DISABLE REDIRECT
/*
if (is_logged_in()) {
    $dest = [
        'superadmin' => 'superadmin_dashboard.php',
        'admin'      => 'admin_dashboard.php',
        'student'    => 'student_dashboard.php',
    ];

    redirect($dest[current_role()] ?? 'login.php');
}
*/
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="UniAdmin — University Student Administration System">
    <title>UniAdmin — School Management System</title>
    
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        /* ==========================================================
           RESET & BASE
           ========================================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f7fb;
            color: #1e293b;
            overflow-x: hidden;
        }
        
        /* ==========================================================
           NAVIGATION BAR
           ========================================================== */
        .navbar-custom {
            background: rgba(15, 23, 42, 0.95);
            padding: 10px 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .navbar-custom.scrolled {
            background: rgba(15, 23, 42, 0.98);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
        }
        
        .navbar-custom .navbar-brand {
            font-size: 1.4rem;
            font-weight: 700;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar-custom .navbar-brand i {
            color: #38bdf8;
            font-size: 28px;
        }
        
        .navbar-custom .navbar-brand span {
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* ==========================================================
           HAMBURGER MENU (3 LINES)
           ========================================================== */
        .navbar-toggler-custom {
            border: none;
            background: transparent;
            padding: 8px 6px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            gap: 5px;
            transition: 0.3s;
        }
        
        .navbar-toggler-custom:focus {
            outline: none;
        }
        
        .navbar-toggler-custom .bar {
            display: block;
            width: 28px;
            height: 3px;
            background: #fff;
            border-radius: 4px;
            transition: 0.3s ease;
        }
        
        .navbar-toggler-custom .bar:nth-child(2) {
            width: 22px;
        }
        
        .navbar-toggler-custom .bar:nth-child(3) {
            width: 18px;
        }
        
        .navbar-toggler-custom:hover .bar {
            background: #38bdf8;
        }
        
        /* When menu is open (X animation) */
        .navbar-toggler-custom.active .bar:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
            width: 28px;
        }
        
        .navbar-toggler-custom.active .bar:nth-child(2) {
            opacity: 0;
            width: 0;
        }
        
        .navbar-toggler-custom.active .bar:nth-child(3) {
            transform: rotate(-45deg) translate(6px, -6px);
            width: 28px;
        }
        
        /* Mobile menu items */
        .navbar-custom .navbar-nav {
            gap: 4px;
        }
        
        .navbar-custom .nav-link {
            color: #cbd5e1 !important;
            font-weight: 500;
            font-size: 0.9rem;
            padding: 8px 16px !important;
            transition: 0.3s;
            border-radius: 8px;
        }
        
        .navbar-custom .nav-link:hover {
            color: #fff !important;
            background: rgba(255, 255, 255, 0.08);
        }
        
        .navbar-custom .nav-link.active {
            color: #fff !important;
            background: rgba(37, 99, 235, 0.3);
        }
        
        .navbar-custom .btn-login {
            padding: 8px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            background: linear-gradient(135deg, #2563eb, #4f46e5);
            color: #fff;
            border: none;
            transition: 0.3s;
        }
        
        .navbar-custom .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
            color: #fff;
        }
        
        .navbar-custom .btn-register {
            padding: 8px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            background: transparent;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: 0.3s;
        }
        
        .navbar-custom .btn-register:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            color: #fff;
        }
        
        /* ==========================================================
           HERO SECTION
           ========================================================== */
        .hero-section {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            padding: 140px 0 80px;
            color: #fff;
            position: relative;
            overflow: hidden;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 70%;
            height: 200%;
            background: rgba(255, 255, 255, 0.05);
            transform: rotate(30deg);
            border-radius: 50%;
            pointer-events: none;
        }
        
        .hero-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 40%;
            height: 150%;
            background: rgba(255, 255, 255, 0.03);
            transform: rotate(-20deg);
            border-radius: 50%;
            pointer-events: none;
        }
        
        .hero-badge {
            display: inline-block;
            padding: 6px 18px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .hero-section h1 {
            font-size: 3.2rem;
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 16px;
            letter-spacing: -1px;
        }
        
        .hero-section h1 .highlight {
            background: linear-gradient(135deg, #fcd34d, #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-section p {
            font-size: 1.05rem;
            line-height: 1.8;
            opacity: 0.9;
            margin-bottom: 30px;
            max-width: 500px;
        }
        
        .hero-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .hero-buttons .btn {
            padding: 10px 28px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: 0.3s;
            font-family: 'Poppins', sans-serif;
        }
        
        .hero-buttons .btn-primary {
            background: #fff;
            color: #2563eb;
            border: none;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .hero-buttons .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.25);
        }
        
        .hero-buttons .btn-outline-light {
            border: 2px solid rgba(255, 255, 255, 0.4);
            color: #fff;
        }
        
        .hero-buttons .btn-outline-light:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: #fff;
            transform: translateY(-3px);
        }
        
        .hero-icon {
            font-size: 14rem;
            opacity: 0.9;
            color: rgba(255, 255, 255, 0.12);
            animation: float 5s ease-in-out infinite;
            position: relative;
            z-index: 1;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .hero-stats {
            display: flex;
            gap: 30px;
            margin-top: 35px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .hero-stats .stat-item h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }
        
        .hero-stats .stat-item p {
            font-size: 0.8rem;
            opacity: 0.7;
            margin: 0;
        }
        
        /* ==========================================================
           FEATURES SECTION
           ========================================================== */
        .features-section {
            padding: 70px 0;
            background: #f8fafc;
        }
        
        .section-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .section-subtitle {
            color: #64748b;
            font-size: 1rem;
        }
        
        .feature-card {
            background: #fff;
            padding: 30px 24px;
            border-radius: 16px;
            text-align: center;
            transition: 0.4s;
            height: 100%;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            width: 65px;
            height: 65px;
            margin: 0 auto 16px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 28px;
            color: #fff;
        }
        
        .feature-icon.bg-primary { background: linear-gradient(135deg, #2563eb, #4f46e5); }
        .feature-icon.bg-success { background: linear-gradient(135deg, #16a34a, #22c55e); }
        .feature-icon.bg-warning { background: linear-gradient(135deg, #ea580c, #f97316); }
        .feature-icon.bg-danger { background: linear-gradient(135deg, #dc2626, #ef4444); }
        
        .feature-card h4 {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .feature-card p {
            font-size: 0.88rem;
            color: #64748b;
            line-height: 1.7;
            margin: 0;
        }
        
        /* ==========================================================
           PORTAL SECTION
           ========================================================== */
        .portal-section {
            padding: 70px 0;
            background: #fff;
        }
        
        .portal-card {
            background: #fff;
            border-radius: 16px;
            padding: 35px 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
            transition: 0.4s;
            height: 100%;
            text-align: center;
            border: 1px solid rgba(0, 0, 0, 0.04);
        }
        
        .portal-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }
        
        .portal-icon {
            width: 75px;
            height: 75px;
            margin: 0 auto 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #fff;
        }
        
        .portal-icon.bg-primary { background: linear-gradient(135deg, #2563eb, #4f46e5); }
        .portal-icon.bg-success { background: linear-gradient(135deg, #16a34a, #22c55e); }
        .portal-icon.bg-dark { background: linear-gradient(135deg, #0f172a, #1e293b); }
        
        .portal-card h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 6px;
        }
        
        .portal-card .portal-desc {
            color: #64748b;
            font-size: 0.85rem;
            margin-bottom: 16px;
        }
        
        .portal-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .portal-buttons .btn {
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: 0.3s;
            font-family: 'Poppins', sans-serif;
        }
        
        .portal-buttons .btn-primary {
            background: #2563eb;
            border: none;
        }
        
        .portal-buttons .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
        }
        
        .portal-buttons .btn-outline-primary {
            border-color: #2563eb;
            color: #2563eb;
        }
        
        .portal-buttons .btn-outline-primary:hover {
            background: #2563eb;
            color: #fff;
            transform: translateY(-2px);
        }
        
        .portal-buttons .btn-success {
            background: #16a34a;
            border: none;
        }
        
        .portal-buttons .btn-success:hover {
            background: #15803d;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(22, 163, 74, 0.3);
        }
        
        .portal-buttons .btn-outline-success {
            border-color: #16a34a;
            color: #16a34a;
        }
        
        .portal-buttons .btn-outline-success:hover {
            background: #16a34a;
            color: #fff;
            transform: translateY(-2px);
        }
        
        .portal-buttons .btn-dark {
            background: #0f172a;
            border: none;
        }
        
        .portal-buttons .btn-dark:hover {
            background: #1e293b;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.3);
        }
        
        .portal-buttons .btn-outline-dark {
            border-color: #0f172a;
            color: #0f172a;
        }
        
        .portal-buttons .btn-outline-dark:hover {
            background: #0f172a;
            color: #fff;
            transform: translateY(-2px);
        }
        
        /* ==========================================================
           PROCESS SECTION
           ========================================================== */
        .process-section {
            padding: 70px 0;
            background: #f8fafc;
        }
        
        .process-card {
            background: #fff;
            padding: 35px 25px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            height: 100%;
            transition: 0.4s;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .process-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        }
        
        .process-number {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #2563eb, #4f46e5);
            color: #fff;
            margin: 0 auto 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.2);
        }
        
        .process-card h4 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .process-card p {
            font-size: 0.88rem;
            color: #64748b;
            line-height: 1.7;
            margin: 0;
        }
        
        /* ==========================================================
           FOOTER
           ========================================================== */
        .footer {
            background: #0f172a;
            color: #cbd5e1;
            padding: 50px 0 25px;
        }
        
        .footer h3 {
            color: #fff;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 12px;
        }
        
        .footer h5 {
            color: #fff;
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 12px;
        }
        
        .footer p {
            font-size: 0.85rem;
            line-height: 1.8;
        }
        
        .footer ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer ul li {
            margin-bottom: 6px;
        }
        
        .footer ul li a {
            color: #94a3b8;
            text-decoration: none;
            transition: 0.3s;
            font-size: 0.85rem;
        }
        
        .footer ul li a:hover {
            color: #fff;
            padding-left: 5px;
        }
        
        .footer hr {
            margin: 30px 0 20px;
            border-color: #334155;
        }
        
        .footer .text-center {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .social-links a {
            color: #94a3b8;
            font-size: 18px;
            transition: 0.3s;
            margin-right: 12px;
            text-decoration: none;
        }
        
        .social-links a:hover {
            color: #fff;
            transform: translateY(-2px);
        }
        
        /* ==========================================================
           RESPONSIVE
           ========================================================== */
        @media (max-width: 992px) {
            .hero-section h1 {
                font-size: 2.5rem;
            }
            
            .hero-icon {
                font-size: 10rem;
                margin-top: 20px;
            }
            
            .hero-stats {
                gap: 15px;
                flex-wrap: wrap;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            /* Mobile menu styles */
            .navbar-custom .navbar-collapse {
                background: rgba(15, 23, 42, 0.98);
                padding: 15px 20px;
                border-radius: 12px;
                margin-top: 10px;
                border: 1px solid rgba(255, 255, 255, 0.06);
            }
            
            .navbar-custom .navbar-nav {
                gap: 2px;
            }
            
            .navbar-custom .nav-link {
                padding: 10px 14px !important;
                border-radius: 8px;
            }
            
            .navbar-custom .btn-login,
            .navbar-custom .btn-register {
                width: 100%;
                text-align: center;
                justify-content: center;
                margin: 4px 0;
            }
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 120px 0 50px;
                text-align: center;
                min-height: auto;
            }
            
            .hero-section h1 {
                font-size: 2rem;
            }
            
            .hero-section p {
                margin-left: auto;
                margin-right: auto;
            }
            
            .hero-buttons {
                justify-content: center;
            }
            
            .hero-icon {
                font-size: 7rem;
            }
            
            .hero-stats {
                justify-content: center;
            }
            
            .features-section, .portal-section, .process-section {
                padding: 50px 0;
            }
            
            .section-title {
                font-size: 1.6rem;
            }
            
            .feature-card, .portal-card, .process-card {
                padding: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .hero-section h1 {
                font-size: 1.6rem;
            }
            
            .hero-buttons .btn {
                width: 100%;
                justify-content: center;
            }
            
            .portal-buttons .btn {
                width: 100%;
            }
        }
        
        /* ==========================================================
           ANIMATIONS
           ========================================================== */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .feature-card, .portal-card, .process-card {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        .feature-card:nth-child(2) { animation-delay: 0.1s; }
        .feature-card:nth-child(3) { animation-delay: 0.2s; }
        .feature-card:nth-child(4) { animation-delay: 0.3s; }
        
        .portal-card:nth-child(2) { animation-delay: 0.1s; }
        .portal-card:nth-child(3) { animation-delay: 0.2s; }
        
        .process-card:nth-child(2) { animation-delay: 0.1s; }
        .process-card:nth-child(3) { animation-delay: 0.2s; }
    </style>
</head>
<body>
  
    <!-- ==========================================================
         NAVIGATION BAR WITH HAMBURGER MENU
         ========================================================== -->
    <nav class="navbar navbar-expand-lg navbar-custom" id="mainNav">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-mortarboard-fill"></i>
                <span>UniAdmin</span>
            </a>
            
            <!-- Custom Hamburger Button (3 lines) -->
            <button class="navbar-toggler-custom" type="button" id="navbarToggler" aria-label="Toggle navigation">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#portals">Portals</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#process">How It Works</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a href="login.php" class="btn btn-login">Login</a>
                    </li>
                    <li class="nav-item">
                        <a href="register.php" class="btn btn-register">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ==========================================================
         HERO SECTION
         ========================================================== -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <span class="hero-badge">
                        <i class="bi bi-mortarboard-fill me-1"></i> School Management Platform
                    </span>
                    <h1>
                        Manage Your School<br>
                        <span class="highlight">Digitally</span>
                    </h1>
                    <p>
                        A modern and secure student management system for schools and universities.
                        Manage students, marks, reports and announcements from one dashboard.
                    </p>
                    <div class="hero-buttons">
                        <a href="#portals" class="btn btn-primary">
                            <i class="bi bi-rocket-takeoff me-2"></i> Get Started
                        </a>
                        <a href="#features" class="btn btn-outline-light">
                            <i class="bi bi-info-circle me-2"></i> Learn More
                        </a>
                    </div>
                    
                    <div class="hero-stats">
                        <div class="stat-item">
                            <h3>4+</h3>
                            <p>User Roles</p>
                        </div>
                        <div class="stat-item">
                            <h3>100%</h3>
                            <p>Secure Platform</p>
                        </div>
                        <div class="stat-item">
                            <h3>24/7</h3>
                            <p>Accessibility</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="bi bi-mortarboard-fill hero-icon"></i>
                </div>
            </div>
        </div>
    </section>

    <!-- ==========================================================
         FEATURES SECTION
         ========================================================== -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="section-title">Why Choose UniAdmin?</h2>
                <p class="section-subtitle">Everything your institution needs in one modern platform.</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon bg-primary">
                            <i class="bi bi-person-lines-fill"></i>
                        </div>
                        <h4>Student Management</h4>
                        <p>Register students, update profiles and manage academic records easily.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon bg-success">
                            <i class="bi bi-journal-check"></i>
                        </div>
                        <h4>Marks Management</h4>
                        <p>Record examination results and automatically calculate GPA.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon bg-warning">
                            <i class="bi bi-bar-chart-line-fill"></i>
                        </div>
                        <h4>Reports</h4>
                        <p>Generate academic reports and performance summaries instantly.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon bg-danger">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <h4>Secure Access</h4>
                        <p>Role-based authentication for Students, Admins and Super Admins.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==========================================================
         PORTAL SECTION
         ========================================================== -->
    <section class="portal-section" id="portals">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="section-title">Choose Your Portal</h2>
                <p class="section-subtitle">Select your role to continue.</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="portal-card">
                        <div class="portal-icon bg-primary">
                            <i class="bi bi-person-graduation"></i>
                        </div>
                        <h3>Student Portal</h3>
                        <p class="portal-desc">Access your academic records, view marks and track your performance.</p>
                        <div class="portal-buttons">
                            <a href="student_register.php" class="btn btn-primary">Register</a>
                            <a href="login.php" class="btn btn-outline-primary">Login</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="portal-card">
                        <div class="portal-icon bg-success">
                            <i class="bi bi-person-workspace"></i>
                        </div>
                        <h3>Administrator</h3>
                        <p class="portal-desc">Manage students, staff, marks and generate comprehensive reports.</p>
                        <div class="portal-buttons">
                            <a href="admin_register.php" class="btn btn-success">Register</a>
                            <a href="login.php" class="btn btn-outline-success">Login</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="portal-card">
                        <div class="portal-icon bg-dark">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <h3>Super Admin</h3>
                        <p class="portal-desc">Full system control with advanced management capabilities and oversight.</p>
                        <div class="portal-buttons">
                            <a href="superadmin_register.php" class="btn btn-dark">Register</a>
                            <a href="login.php" class="btn btn-outline-dark">Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==========================================================
         PROCESS SECTION
         ========================================================== -->
    <section class="process-section" id="process">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="section-title">How UniAdmin Works</h2>
                <p class="section-subtitle">Three simple steps to get started.</p>
            </div>
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="process-card">
                        <div class="process-number">1</div>
                        <h4>Create Account</h4>
                        <p>Register as a Student, Administrator or Super Administrator.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="process-card">
                        <div class="process-number">2</div>
                        <h4>Manage Information</h4>
                        <p>Record student information, marks and generate reports.</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="process-card">
                        <div class="process-number">3</div>
                        <h4>Generate Reports</h4>
                        <p>View results, GPA and performance reports instantly.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==========================================================
         FOOTER
         ========================================================== -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h3>
                        <i class="bi bi-mortarboard-fill" style="color: #2563eb;"></i> 
                        UniAdmin SMS
                    </h3>
                    <p>A modern school management platform for universities and secondary schools. Streamline administration, enhance learning outcomes.</p>
                    <div class="social-links">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-twitter-x"></i></a>
                        <a href="#"><i class="bi bi-linkedin"></i></a>
                        <a href="#"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
                <div class="col-lg-2">
                    <h5>Quick Links</h5>
                    <ul>
                        <li><a href="#">Home</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#portals">Portals</a></li>
                        <li><a href="#">About</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h5>Portals</h5>
                    <ul