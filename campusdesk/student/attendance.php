<?php
/**
 * CampusDesk — Student Personal Attendance Record
 * File: student/attendance.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('student');

$page_title = 'My Attendance Record';
$current_page = 'attendance';

$user_id = (int)$_SESSION['user_id'];

// Get student_id
$st = $pdo->prepare("SELECT student_id, student_code, full_name FROM students WHERE user_id = :uid");
$st->execute([':uid' => $user_id]);
$student = $st->fetch();
$student_id = (int)$student['student_id'];

// Calculate overall attendance
$stat_stmt = $pdo->prepare("
    SELECT 
        COUNT(attendance_id) as total_classes,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count
    FROM attendance
    WHERE student_id = :sid
");
$stat_stmt->execute([':sid' => $student_id]);
$stats = $stat_stmt->fetch();

$total = (int)($stats['total_classes'] ?? 0);
$present = (int)($stats['present_count'] ?? 0);
$late = (int)($stats['late_count'] ?? 0);
$absent = (int)($stats['absent_count'] ?? 0);
$pct = calculate_attendance_percentage($present, $late, $total);

// Detailed daily attendance log
$logs = $pdo->prepare("
    SELECT a.*, c.course_code
    FROM attendance a
    JOIN courses c ON a.course_id = c.course_id
    WHERE a.student_id = :sid
    ORDER BY a.attendance_date DESC
");
$logs->execute([':sid' => $student_id]);
$records = $logs->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4 py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="fw-bold text-dark mb-0">My Attendance Records</h3>
      <div class="text-secondary small">Lecture roll-call log and cumulative attendance standing</div>
    </div>
  </div>

  <!-- Metric Badges -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
      <div class="card-stat">
        <div class="stat-label">Total Lectures</div>
        <div class="stat-value tabular-nums mt-1"><?= $total ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-md-3">
      <div class="card-stat">
        <div class="stat-label text-success">Attended (Present)</div>
        <div class="stat-value tabular-nums mt-1 text-success"><?= $present ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-md-3">
      <div class="card-stat">
        <div class="stat-label text-warning">Late Markings</div>
        <div class="stat-value tabular-nums mt-1 text-warning"><?= $late ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-md-3">
      <div class="card-stat">
        <div class="stat-label <?= $pct >= 75 ? 'text-success' : 'text-danger' ?>">Cumulative %</div>
        <div class="stat-value tabular-nums mt-1 <?= $pct >= 75 ? 'text-success' : 'text-danger' ?>">
          <?= number_format($pct, 1) ?>%
        </div>
      </div>
    </div>
  </div>

  <?php if ($pct < 75 && $total > 0): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
      <i class="bi bi-exclamation-triangle-fill fs-5"></i>
      <div>
        <strong>Attendance Shortage Alert:</strong> Your attendance is currently at <strong><?= number_format($pct, 1) ?>%</strong>, which is below the university mandate of 75%. Please contact your faculty advisor.
      </div>
    </div>
  <?php endif; ?>

  <!-- Detailed Table -->
  <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
    <div class="card-header bg-white py-3">
      <h6 class="fw-bold text-dark mb-0">Lecture Session History</h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover table-cd mb-0 align-middle">
        <thead>
          <tr>
            <th>Date</th>
            <th>Course</th>
            <th>Attendance Status</th>
            <th>Faculty Remarks</th>
            <th>Recorded At</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($records)): ?>
            <tr><td colspan="5" class="text-center py-5 text-muted">No attendance sessions recorded yet.</td></tr>
          <?php else: ?>
            <?php foreach ($records as $r): ?>
              <tr>
                <td class="tabular-nums fw-bold"><?= format_date($r['attendance_date']) ?></td>
                <td><span class="badge bg-light text-dark border"><?= e($r['course_code']) ?></span></td>
                <td>
                  <span class="badge <?= $r['status'] === 'Present' ? 'bg-success-subtle text-success' : ($r['status'] === 'Late' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger') ?>">
                    <?= e($r['status']) ?>
                  </span>
                </td>
                <td class="small text-muted"><?= e($r['remarks'] ?? 'Regular Session') ?></td>
                <td class="tabular-nums small text-muted"><?= e($r['recorded_at']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
