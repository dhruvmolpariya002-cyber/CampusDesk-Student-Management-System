<?php
/**
 * CampusDesk — Landing & Redirect Controller
 * File: index.php
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

// If user is already logged in, route directly to their dashboard
if (is_logged_in()) {
    $role = $_SESSION['role'] ?? 'student';
    if ($role === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    } else {
        header("Location: student/dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CampusDesk — Student Management System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">

<!-- Public Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark py-3 px-4 shadow-sm" style="background-color: #0f172a !important;">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2 fw-bold fs-4" href="index.php">
      <i class="bi bi-mortarboard-fill text-primary"></i> CampusDesk
    </a>
    <div class="ms-auto d-flex align-items-center gap-2">
      <a href="login.php" class="btn btn-outline-light px-3 py-1 btn-sm">Sign In</a>
      <a href="setup_admin.php" class="btn btn-primary px-3 py-1 btn-sm">First-Run Setup</a>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<header class="py-5 bg-white border-bottom">
  <div class="container py-4">
    <div class="row align-items-center g-5">
      <div class="col-lg-7">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 mb-3 fw-semibold">
          BCA Semester 3 Academic Project · PHP &amp; MySQL
        </span>
        <h1 class="display-5 fw-bold text-dark lh-tight mb-3">
          CampusDesk Student Management System
        </h1>
        <p class="lead text-secondary mb-4">
          A full-stack institutional records and student lifecycle portal. Engineered with PHP 8.2, PDO prepared statements, and normalized MySQL relational architecture.
        </p>
        <div class="d-flex flex-wrap gap-3">
          <a href="login.php" class="btn btn-primary btn-lg px-4 py-2 fw-semibold">
            <i class="bi bi-box-arrow-in-right me-2"></i>Access Portal Login
          </a>
          <a href="setup_admin.php" class="btn btn-outline-secondary btn-lg px-4 py-2">
            <i class="bi bi-shield-lock me-2"></i>Admin Setup Wizard
          </a>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="p-4 bg-light border rounded-3 shadow-sm">
          <h5 class="fw-bold mb-3 text-dark">Pre-Configured Demo Credentials</h5>
          <div class="mb-3 p-3 bg-white border rounded">
            <div class="fw-bold text-primary mb-1"><i class="bi bi-person-fill-gear me-1"></i> Administrator</div>
            <div class="small text-muted">Username: <code>admin</code></div>
            <div class="small text-muted">Password: <code>Password@123</code></div>
          </div>
          <div class="mb-3 p-3 bg-white border rounded">
            <div class="fw-bold text-success mb-1"><i class="bi bi-person-badge me-1"></i> Student</div>
            <div class="small text-muted">Username / Roll No: <code>bca2024001</code></div>
            <div class="small text-muted">Password: <code>Password@123</code></div>
          </div>
          <div class="small text-secondary">
            <i class="bi bi-info-circle me-1"></i> Passwords are encrypted with bcrypt hashes using PHP's native <code>password_hash()</code>.
          </div>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- Feature Matrix -->
<section class="py-5">
  <div class="container">
    <div class="row g-4 text-center mb-5">
      <div class="col-md-4">
        <div class="card h-100 p-4 border shadow-sm">
          <div class="fs-1 text-primary mb-2"><i class="bi bi-people"></i></div>
          <h5 class="fw-bold">Student Lifecycle CRUD</h5>
          <p class="text-secondary small mb-0">Complete admissions, student profiles, unique enrollment roll numbers, course mapping, and safe relational constraints.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 p-4 border shadow-sm">
          <div class="fs-1 text-primary mb-2"><i class="bi bi-calendar2-check"></i></div>
          <h5 class="fw-bold">Roll-Call Attendance</h5>
          <p class="text-secondary small mb-0">Daily classroom roll-call with Present, Absent, and Late weights, automatic percentage calculation, and division-by-zero safeguards.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 p-4 border shadow-sm">
          <div class="fs-1 text-primary mb-2"><i class="bi bi-cash-coin"></i></div>
          <h5 class="fw-bold">Ledgers &amp; Marksheets</h5>
          <p class="text-secondary small mb-0">Subject-wise marks entry, automatic letter grading, fee installment tracking with balance calculations, and CSV reporting.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="py-4 bg-dark text-white-50 text-center small" style="background-color: #0f172a !important;">
  <div class="container">
    CampusDesk &copy; <?= date('Y') ?> — BCA Semester 3 PHP &amp; MySQL Project.
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
