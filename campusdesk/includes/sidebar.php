<?php
/**
 * CampusDesk — Role-Based Sidebar Navigation
 * File: includes/sidebar.php
 */

declare(strict_types=1);

$user_role = $_SESSION['role'] ?? 'student';
$current_page = $current_page ?? 'dashboard';
?>
<!-- Sidebar -->
<div id="sidebar-wrapper" class="border-end">
  <div class="sidebar-heading d-flex align-items-center gap-2">
    <i class="bi bi-mortarboard-fill text-primary"></i>
    <span>CampusDesk</span>
  </div>

  <div class="list-group list-group-flush pt-2">
    <?php if ($user_role === 'admin'): ?>
      <div class="nav-section-title">Overview</div>
      <a href="dashboard.php" class="nav-link <?= $current_page === 'dashboard' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
      </a>

      <div class="nav-section-title">Academics</div>
      <a href="students.php" class="nav-link <?= in_array($current_page, ['students', 'student_add', 'student_edit']) ? 'active' : '' ?>">
        <i class="bi bi-people-fill"></i> Students
      </a>
      <a href="departments.php" class="nav-link <?= $current_page === 'departments' ? 'active' : '' ?>">
        <i class="bi bi-building"></i> Departments
      </a>
      <a href="courses.php" class="nav-link <?= $current_page === 'courses' ? 'active' : '' ?>">
        <i class="bi bi-journal-bookmark-fill"></i> Courses
      </a>
      <a href="subjects.php" class="nav-link <?= $current_page === 'subjects' ? 'active' : '' ?>">
        <i class="bi bi-book"></i> Subjects
      </a>

      <div class="nav-section-title">Records & Assessment</div>
      <a href="attendance.php" class="nav-link <?= $current_page === 'attendance' ? 'active' : '' ?>">
        <i class="bi bi-calendar-check-fill"></i> Attendance
      </a>
      <a href="examinations.php" class="nav-link <?= $current_page === 'examinations' ? 'active' : '' ?>">
        <i class="bi bi-award"></i> Examinations
      </a>
      <a href="results.php" class="nav-link <?= $current_page === 'results' ? 'active' : '' ?>">
        <i class="bi bi-clipboard2-data-fill"></i> Results / Marks
      </a>
      <a href="fees.php" class="nav-link <?= $current_page === 'fees' ? 'active' : '' ?>">
        <i class="bi bi-cash-stack"></i> Fee Management
      </a>

      <div class="nav-section-title">Communication & Export</div>
      <a href="notices.php" class="nav-link <?= $current_page === 'notices' ? 'active' : '' ?>">
        <i class="bi bi-megaphone-fill"></i> Notice Board
      </a>
      <a href="reports.php" class="nav-link <?= $current_page === 'reports' ? 'active' : '' ?>">
        <i class="bi bi-file-earmark-spreadsheet-fill"></i> Reports & CSV
      </a>

    <?php else: ?>
      <!-- Student Portal Links -->
      <div class="nav-section-title">Student Portal</div>
      <a href="dashboard.php" class="nav-link <?= $current_page === 'dashboard' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i> My Dashboard
      </a>
      <a href="profile.php" class="nav-link <?= $current_page === 'profile' ? 'active' : '' ?>">
        <i class="bi bi-person-badge-fill"></i> My Profile
      </a>
      <a href="attendance.php" class="nav-link <?= $current_page === 'attendance' ? 'active' : '' ?>">
        <i class="bi bi-calendar2-week-fill"></i> My Attendance
      </a>
      <a href="results.php" class="nav-link <?= $current_page === 'results' ? 'active' : '' ?>">
        <i class="bi bi-file-earmark-text-fill"></i> Examination Results
      </a>
      <a href="fees.php" class="nav-link <?= $current_page === 'fees' ? 'active' : '' ?>">
        <i class="bi bi-wallet2"></i> Fee Statements
      </a>
      <a href="notices.php" class="nav-link <?= $current_page === 'notices' ? 'active' : '' ?>">
        <i class="bi bi-bell-fill"></i> Campus Notices
      </a>
    <?php endif; ?>

    <div class="nav-section-title">Account</div>
    <a href="../logout.php" class="nav-link text-danger">
      <i class="bi bi-box-arrow-right"></i> Sign Out
    </a>
  </div>
</div>
