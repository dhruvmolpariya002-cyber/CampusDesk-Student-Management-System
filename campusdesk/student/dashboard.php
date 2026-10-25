<?php
/**
 * CampusDesk — Student Personal Academic Dashboard
 * File: student/dashboard.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('student');

$page_title = 'Student Academic Portal';
$current_page = 'dashboard';

$user_id = (int)$_SESSION['user_id'];

// Fetch Student Profile linked to this login account
$stmt = $pdo->prepare("
    SELECT s.*, c.course_name, c.course_code, c.duration_years, d.dept_name
    FROM students s
    JOIN courses c ON s.course_id = c.course_id
    JOIN departments d ON c.dept_id = d.dept_id
    WHERE s.user_id = :uid
");
$stmt->execute([':uid' => $user_id]);
$student = $stmt->fetch();

if (!$student) {
    die("<div style='font-family:sans-serif; padding:2rem; max-width:500px; margin:40px auto;'>No student record is associated with this login account. Contact administrator.</div>");
}

$student_id = (int)$student['student_id'];

// 1. Calculate Attendance Percentage
$att_stmt = $pdo->prepare("
    SELECT 
        COUNT(attendance_id) as total_classes,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count
    FROM attendance
    WHERE student_id = :sid
");
$att_stmt->execute([':sid' => $student_id]);
$att_stats = $att_stmt->fetch();

$total_classes = (int)($att_stats['total_classes'] ?? 0);
$present_classes = (int)($att_stats['present_count'] ?? 0);
$late_classes = (int)($att_stats['late_count'] ?? 0);
$att_percentage = calculate_attendance_percentage($present_classes, $late_classes, $total_classes);

// 2. Fetch Latest Examination Marksheet Summary
$res_stmt = $pdo->prepare("
    SELECT r.*, sub.subject_name, sub.subject_code, ex.exam_name
    FROM results r
    JOIN subjects sub ON r.subject_id = sub.subject_id
    JOIN examinations ex ON r.exam_id = ex.exam_id
    WHERE r.student_id = :sid
    ORDER BY ex.exam_date DESC
");
$res_stmt->execute([':sid' => $student_id]);
$results = $res_stmt->fetchAll();

$total_max = 0.0;
$total_obtained = 0.0;
foreach ($results as $r) {
    $total_max += (float)$r['max_marks'];
    $total_obtained += (float)$r['obtained_marks'];
}
$overall_gpa_pct = ($total_max > 0) ? round(($total_obtained / $total_max) * 100, 1) : 0.0;

// 3. Outstanding Fee Dues
$fee_stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total_amount - paid_amount), 0) as pending_dues
    FROM fees
    WHERE student_id = :sid AND payment_status != 'Paid'
");
$fee_stmt->execute([':sid' => $student_id]);
$pending_dues = (float)$fee_stmt->fetchColumn();

// 4. Published Notices
$notices = $pdo->query("
    SELECT * FROM notices
    WHERE status = 'Published' AND (expiry_date IS NULL OR expiry_date >= CURRENT_DATE())
    ORDER BY publish_date DESC, notice_id DESC
    LIMIT 4
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4 py-3">
  <!-- Welcome Banner -->
  <div class="card border-0 shadow-sm rounded-3 p-4 bg-white mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 mb-2">
          Roll No: <?= e($student['student_code']) ?>
        </span>
        <h3 class="fw-bold text-dark mb-1">Welcome back, <?= e($student['full_name']) ?></h3>
        <div class="text-secondary small">
          <?= e($student['course_name']) ?> (<?= e($student['course_code']) ?>) · <?= e($student['dept_name']) ?>
        </div>
      </div>
      <div class="d-flex gap-2">
        <a href="profile.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-person-gear me-1"></i>Edit Profile</a>
        <a href="results.php" class="btn btn-primary btn-sm"><i class="bi bi-award me-1"></i>View Marksheet</a>
      </div>
    </div>
  </div>

  <!-- Key Metrics Grid -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
      <div class="card-stat">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="stat-label">Attendance Standing</div>
            <div class="stat-value tabular-nums mt-1 <?= $att_percentage >= 75 ? 'text-success' : 'text-danger' ?>">
              <?= number_format($att_percentage, 1) ?>%
            </div>
          </div>
          <div class="p-2 bg-success-subtle text-success rounded-3"><i class="bi bi-calendar2-check-fill fs-4"></i></div>
        </div>
        <div class="small text-muted mt-2">
          <?= $present_classes ?> of <?= $total_classes ?> recorded lectures attended
          <?php if ($att_percentage < 75 && $total_classes > 0): ?>
            <span class="text-danger d-block fw-semibold">&bull; Shortage below 75% threshold</span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-4">
      <div class="card-stat">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="stat-label">Overall Exam Score</div>
            <div class="stat-value tabular-nums mt-1 text-primary">
              <?= $overall_gpa_pct > 0 ? number_format($overall_gpa_pct, 1) . '%' : 'N/A' ?>
            </div>
          </div>
          <div class="p-2 bg-primary-subtle text-primary rounded-3"><i class="bi bi-mortarboard-fill fs-4"></i></div>
        </div>
        <div class="small text-muted mt-2">
          <?= count($results) ?> evaluated subject examinations on file
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-4">
      <div class="card-stat">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="stat-label">Pending Fee Balance</div>
            <div class="stat-value tabular-nums mt-1 <?= $pending_dues > 0 ? 'text-danger' : 'text-success' ?>">
              <?= format_currency($pending_dues) ?>
            </div>
          </div>
          <div class="p-2 bg-warning-subtle text-warning rounded-3"><i class="bi bi-wallet2 fs-4"></i></div>
        </div>
        <div class="small text-muted mt-2">
          <?= $pending_dues > 0 ? 'Outstanding semester balance due' : 'All institutional fees cleared' ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Marksheet & Noticeboard Row -->
  <div class="row g-4 mb-4">
    <!-- Academic Result Snapshot -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
          <h6 class="fw-bold text-dark mb-0">Recent Examination Results</h6>
          <a href="results.php" class="small text-primary text-decoration-none">Complete Gradecard &rarr;</a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover table-cd mb-0 align-middle">
            <thead>
              <tr>
                <th>Subject</th>
                <th>Obtained / Max</th>
                <th>Grade</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($results)): ?>
                <tr><td colspan="4" class="text-center py-4 text-muted">No examination marks published yet.</td></tr>
              <?php else: ?>
                <?php foreach (array_slice($results, 0, 4) as $res): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold text-dark"><?= e($res['subject_name']) ?></div>
                      <div class="small text-muted"><?= e($res['subject_code']) ?> · <?= e($res['exam_name']) ?></div>
                    </td>
                    <td class="tabular-nums fw-semibold">
                      <?= (float)$res['obtained_marks'] ?> / <?= (float)$res['max_marks'] ?>
                    </td>
                    <td><span class="badge bg-dark-subtle text-dark border fw-bold"><?= e($res['grade']) ?></span></td>
                    <td>
                      <span class="badge <?= $res['status'] === 'Pass' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                        <?= e($res['status']) ?>
                      </span>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Noticeboard View -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
          <h6 class="fw-bold text-dark mb-0">Campus Circulars &amp; Notices</h6>
          <a href="notices.php" class="small text-primary text-decoration-none">View All</a>
        </div>
        <div class="list-group list-group-flush">
          <?php if (empty($notices)): ?>
            <div class="text-center py-4 text-muted small">No announcements posted.</div>
          <?php else: ?>
            <?php foreach ($notices as $not): ?>
              <div class="list-group-item p-3">
                <div class="d-flex justify-content-between align-items-start mb-1">
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;">
                    <?= e($not['category']) ?>
                  </span>
                  <span class="text-muted small tabular-nums"><?= format_date($not['publish_date']) ?></span>
                </div>
                <div class="fw-semibold text-dark mb-1"><?= e($not['title']) ?></div>
                <div class="text-secondary small text-truncate"><?= e($not['content']) ?></div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
