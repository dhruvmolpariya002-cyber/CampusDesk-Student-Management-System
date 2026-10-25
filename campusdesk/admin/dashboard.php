<?php
/**
 * CampusDesk — Administrator Dashboard
 * File: admin/dashboard.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$page_title = 'Administrator Dashboard';
$current_page = 'dashboard';

// Fetch statistics from MySQL using PDO
try {
    // 1. Total Students
    $total_students = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();

    // 2. Total Courses
    $total_courses = (int)$pdo->query("SELECT COUNT(*) FROM courses WHERE status = 'active'")->fetchColumn();

    // 3. Total Attendance Records
    $total_attendance = (int)$pdo->query("SELECT COUNT(*) FROM attendance")->fetchColumn();

    // 4. Pending / Partial Fee Records Count & Outstanding Balance
    $fee_stats = $pdo->query("
        SELECT 
            COUNT(CASE WHEN payment_status != 'Paid' THEN 1 END) AS pending_count,
            COALESCE(SUM(total_amount - paid_amount), 0) AS outstanding_amount
        FROM fees
    ")->fetch();

    $pending_fee_count = (int)($fee_stats['pending_count'] ?? 0);
    $outstanding_fee_amount = (float)($fee_stats['outstanding_amount'] ?? 0.0);

    // 5. Recently Added Students (limit 5)
    $recent_students = $pdo->query("
        SELECT s.student_id, s.student_code, s.full_name, s.email, s.enrollment_status, c.course_code
        FROM students s
        JOIN courses c ON s.course_id = c.course_id
        ORDER BY s.student_id DESC
        LIMIT 5
    ")->fetchAll();

    // 6. Recent Notices (limit 4)
    $recent_notices = $pdo->query("
        SELECT notice_id, title, category, publish_date, status
        FROM notices
        ORDER BY publish_date DESC, notice_id DESC
        LIMIT 4
    ")->fetchAll();

    // 7. Chart Data: Students per Course
    $course_distribution = $pdo->query("
        SELECT c.course_code, COUNT(s.student_id) as student_count
        FROM courses c
        LEFT JOIN students s ON c.course_id = s.course_id
        GROUP BY c.course_id, c.course_code
    ")->fetchAll();

    $chart_courses_labels = array_column($course_distribution, 'course_code');
    $chart_courses_data = array_map('intval', array_column($course_distribution, 'student_count'));

    // 8. Chart Data: Attendance Summary (Present, Late, Absent)
    $attendance_breakdown = $pdo->query("
        SELECT 
            SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count
        FROM attendance
    ")->fetch();

    $chart_att_data = [
        (int)($attendance_breakdown['present_count'] ?? 0),
        (int)($attendance_breakdown['late_count'] ?? 0),
        (int)($attendance_breakdown['absent_count'] ?? 0)
    ];

} catch (PDOException $e) {
    error_log("Dashboard query error: " . $e->getMessage());
    $total_students = $total_courses = $total_attendance = $pending_fee_count = 0;
    $outstanding_fee_amount = 0.0;
    $recent_students = [];
    $recent_notices = [];
    $chart_courses_labels = [];
    $chart_courses_data = [];
    $chart_att_data = [0, 0, 0];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4 py-3">
  <!-- Welcome Header -->
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h3 class="fw-bold text-dark mb-0">Academic Overview</h3>
      <div class="text-secondary small">CampusDesk Live Relational Metrics &amp; Record Summaries</div>
    </div>
    <div class="d-flex gap-2">
      <a href="student_add.php" class="btn btn-primary btn-sm"><i class="bi bi-person-plus me-1"></i>New Student</a>
      <a href="attendance.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-calendar-check me-1"></i>Mark Attendance</a>
    </div>
  </div>

  <!-- Metric Cards Grid -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="card-stat">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="stat-label">Total Students</div>
            <div class="stat-value tabular-nums mt-1"><?= number_format($total_students) ?></div>
          </div>
          <div class="p-2 bg-primary-subtle text-primary rounded-3"><i class="bi bi-people-fill fs-4"></i></div>
        </div>
        <div class="small text-muted mt-2"><a href="students.php" class="text-decoration-none">Manage student directory &rarr;</a></div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card-stat">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="stat-label">Active Courses</div>
            <div class="stat-value tabular-nums mt-1"><?= number_format($total_courses) ?></div>
          </div>
          <div class="p-2 bg-success-subtle text-success rounded-3"><i class="bi bi-journal-code fs-4"></i></div>
        </div>
        <div class="small text-muted mt-2"><a href="courses.php" class="text-decoration-none">View curriculum catalog &rarr;</a></div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card-stat">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="stat-label">Attendance Logs</div>
            <div class="stat-value tabular-nums mt-1"><?= number_format($total_attendance) ?></div>
          </div>
          <div class="p-2 bg-info-subtle text-info rounded-3"><i class="bi bi-calendar2-check-fill fs-4"></i></div>
        </div>
        <div class="small text-muted mt-2"><a href="attendance.php" class="text-decoration-none">Classroom roll-calls &rarr;</a></div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="card-stat">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="stat-label">Pending Fees</div>
            <div class="stat-value tabular-nums mt-1 text-danger"><?= format_currency($outstanding_fee_amount) ?></div>
          </div>
          <div class="p-2 bg-danger-subtle text-danger rounded-3"><i class="bi bi-exclamation-triangle-fill fs-4"></i></div>
        </div>
        <div class="small text-muted mt-2"><a href="fees.php" class="text-decoration-none"><?= $pending_fee_count ?> student fee records due &rarr;</a></div>
      </div>
    </div>
  </div>

  <!-- Charts Row -->
  <div class="row g-4 mb-4">
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white h-100">
        <h6 class="fw-bold text-dark mb-3">Enrolled Students by Course</h6>
        <?php if (empty($chart_courses_labels)): ?>
          <div class="text-center py-5 text-muted small">No course enrollment data found.</div>
        <?php else: ?>
          <div style="height: 260px;">
            <canvas id="courseChart"></canvas>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white h-100">
        <h6 class="fw-bold text-dark mb-3">Attendance Ratio Breakdown</h6>
        <?php if (array_sum($chart_att_data) === 0): ?>
          <div class="text-center py-5 text-muted small">No attendance sessions recorded yet.</div>
        <?php else: ?>
          <div style="height: 260px; position: relative;">
            <canvas id="attendanceChart"></canvas>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Tables Row: Recent Students & Recent Notices -->
  <div class="row g-4 mb-4">
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
          <h6 class="fw-bold text-dark mb-0">Recently Enrolled Students</h6>
          <a href="students.php" class="small text-primary text-decoration-none">View All</a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover table-cd mb-0">
            <thead>
              <tr>
                <th>Roll No</th>
                <th>Name</th>
                <th>Course</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recent_students)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No student records found.</td></tr>
              <?php else: ?>
                <?php foreach ($recent_students as $st): ?>
                  <tr>
                    <td class="tabular-nums fw-semibold text-primary"><?= e($st['student_code']) ?></td>
                    <td><?= e($st['full_name']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= e($st['course_code']) ?></span></td>
                    <td>
                      <span class="badge <?= $st['enrollment_status'] === 'Active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>">
                        <?= e($st['enrollment_status']) ?>
                      </span>
                    </td>
                    <td>
                      <a href="student_edit.php?id=<?= (int)$st['student_id'] ?>" class="btn btn-sm btn-outline-secondary py-0 px-2" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
          <h6 class="fw-bold text-dark mb-0">Recent Circulars &amp; Notices</h6>
          <a href="notices.php" class="small text-primary text-decoration-none">Manage</a>
        </div>
        <div class="list-group list-group-flush">
          <?php if (empty($recent_notices)): ?>
            <div class="text-center py-4 text-muted small">No announcements published.</div>
          <?php else: ?>
            <?php foreach ($recent_notices as $not): ?>
              <div class="list-group-item px-4 py-3">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;">
                    <?= e($not['category']) ?>
                  </span>
                  <span class="text-muted small tabular-nums"><?= format_date($not['publish_date']) ?></span>
                </div>
                <div class="fw-semibold text-dark text-truncate"><?= e($not['title']) ?></div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  <?php if (!empty($chart_courses_labels)): ?>
  // Students by Course Bar Chart
  const ctxCourse = document.getElementById('courseChart');
  if (ctxCourse) {
    new Chart(ctxCourse, {
      type: 'bar',
      data: {
        labels: <?= json_encode($chart_courses_labels) ?>,
        datasets: [{
          label: 'Enrolled Students',
          data: <?= json_encode($chart_courses_data) ?>,
          backgroundColor: '#3b82f6',
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } }
        }
      }
    });
  }
  <?php endif; ?>

  <?php if (array_sum($chart_att_data) > 0): ?>
  // Attendance Doughnut Chart
  const ctxAtt = document.getElementById('attendanceChart');
  if (ctxAtt) {
    new Chart(ctxAtt, {
      type: 'doughnut',
      data: {
        labels: ['Present', 'Late', 'Absent'],
        datasets: [{
          data: <?= json_encode($chart_att_data) ?>,
          backgroundColor: ['#10b981', '#f59e0b', '#ef4444']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' }
        }
      }
    });
  }
  <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
