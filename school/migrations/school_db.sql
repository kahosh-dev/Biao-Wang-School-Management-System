-- ============================================================
-- School Management System Database (3NF Normalized)
-- BIT3208 - Advanced Web Design and Development
-- ============================================================

CREATE DATABASE IF NOT EXISTS school_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE school_system;

-- 1. Roles (lookup table)
CREATE TABLE IF NOT EXISTS roles (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

INSERT IGNORE INTO roles (name) VALUES
    ('superadmin'),('manager'),('admin'),('student'),('client');

-- 2. Users
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('superadmin','manager','admin','student','client') NOT NULL DEFAULT 'student',
    email      VARCHAR(150),
    full_name  VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Default superadmin (password: Admin@123)
INSERT IGNORE INTO users (username, password, role, email, full_name) VALUES (
    'superadmin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'superadmin',
    'superadmin@school.edu',
    'Super Administrator'
);

-- 3. Students
CREATE TABLE IF NOT EXISTS students (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL UNIQUE,
    reg_no     VARCHAR(50) NOT NULL UNIQUE,
    name       VARCHAR(150) NOT NULL,
    dob        DATE,
    class      VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Subjects
CREATE TABLE IF NOT EXISTS subjects (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL
);

INSERT IGNORE INTO subjects (code, name) VALUES
    ('MATH101','Mathematics'),
    ('SCI101','Science'),
    ('ENG101','English'),
    ('ICT101','ICT'),
    ('HIS101','History');

-- 5. Terms
CREATE TABLE IF NOT EXISTS terms (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(50) NOT NULL UNIQUE,
    year       YEAR NOT NULL,
    is_current TINYINT(1) DEFAULT 0
);

INSERT IGNORE INTO terms (name, year, is_current) VALUES
    ('Term 1', 2025, 1),
    ('Term 2', 2025, 0),
    ('Term 3', 2025, 0);

-- 6. Marks
CREATE TABLE IF NOT EXISTS marks (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    term_id    INT NOT NULL,
    score      DECIMAL(5,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_mark (student_id, subject_id, term_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id),
    FOREIGN KEY (term_id)    REFERENCES terms(id)
);

-- 7. Announcements
CREATE TABLE IF NOT EXISTS announcements (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    title      VARCHAR(200) NOT NULL,
    body       TEXT NOT NULL,
    role_target ENUM('all','student','client','admin','manager','superadmin') DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 8. Activity Logs
CREATE TABLE IF NOT EXISTS activity_logs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    action     VARCHAR(255) NOT NULL,
    ip         VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
