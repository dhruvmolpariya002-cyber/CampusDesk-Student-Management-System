<?php
/**
 * CampusDesk — Roll-Call Attendance Management
 * File: admin/attendance.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$page_title = 'Attendance Management';
$current_page = 'attendance';

$courses = $pdo->query("SELECT course_id, course_name, course_code FROM courses WHERE status = 'active' ORDER BY course_name ASC")->fetchAll();
$selected_course = (int)($_GET['course_id'] ?? ($courses[0]['course_id'] ?? 1));
$selected_date   = trim($_GET['date'] ?? date('Y-m-d'));

// Handle Batch Roll-Call Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_batch') {
    verify_csrf_or_die();
    $post_course = (int)($_POST['course_id'] ?? 0);
    $post_date   = trim($_POST['date'] ?? date('Y-m-d'));
    $attendance_entries = $_POST['status'] ?? []; // student_id => status

    if ($post_course > 0 && !empty($post_date) && !empty($attendance_entries)) {
        try {
            $pdo->beginTransaction();

            $upsert = $pdo->prepare("
                INSERT INTO attendance (student_id, course_id, attendance_date, status, remarks)
                VALUES (:student_id, :course_id, :att_date, :status, :remarks)
                ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks)
            ");

            foreach ($attendance_entries as $sid => $status) {
                $student_id = (int)$sid;
                $rem = trim($_POST['remarks'][$student_id] ?? '');
                $valid_status = in_array($status, ['Present', 'Absent', 'Late']) ? $status : 'Present';

                $upsert->execute([
                    ':student_id' => $student_id,
                    ':course_id'  => $post_course,
                    ':att_date'   => $post_date,
                    ':status'     => $valid_status,
                    ':remarks'    => $rem ?: null
                ]);
            }

            $pdo->commit();
            set_flash('success', "Attendance records for " . count($attendance_entries) . " students recorded for date " . format_date($post_date));
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Attendance batch save error: " . $e->getMessage());
            set_flash('error', "Database error saving attendance records: " . $e->getMessage());
        }
    }
    header("Location: attendance.php?course_id={$post_course}&date={$post_date}");
    exit;
}

// Fetch Students enrolled in the selected course
$students_stmt = $pdo->prepare("
    SELECT s.student_id, s.student_code, s.full_name,
           a.status as recorded_status, a.remarks as recorded_remarks
    FROM students s
    LEFT JOIN attendance a ON s.student_id = a.student_id AND a.attendance_date = :att_date
    WHERE s.course_id = :course_id AND s.enrollment_status = 'Active'
    ORDER BY s.student_code ASC
");
$students_stmt->execute([':course_id' => $selected_course, ':att_date' => $selected_date]);
$students = $students_stmt->fetchAll();

// Calculate Student-Wise Cumulative Attendance Percentage for this course
$cumulative_stmt = $pdo->prepare("
    SELECT 
        s.student_id, s.student_code, s.full_name,
        COUNT(a.attendance_id) as total_classes,
        SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) as late_count,
        SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) as absent_count
    FROM students s
    LEFT JOIN attendance a ON s.student_id = a.student_id
    WHERE s.course_id = :course_id AND s.enrollment_status = 'Active'
    GROUP BY s.student_id
    ORDER BY s.student_code ASC
");
$cumulative_stmt->execute([':course_id' => $selected_course]);
$cumulative_records = $cumulative_stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4 py-3">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h3 class="fw-bold text-dark mb-0">Roll-Call Attendance</h3>
      <div class="text-secondary small">Daily classroom session marking and automated percentage computation</div>
    </div>
    <a href="reports.php?type=attendance&course_id=<?= $selected_course ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-filetype-csv me-1"></i>Export Attendance CSV
    </a>
  </div>

  <!-- Selection Form -->
  <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-4">
    <form method="GET" action="attendance.php" class="row g-3 align-items-end">
      <div class="col-md-5">
        <label class="form-label small fw-semibold text-secondary">Course Selection</label>
        <select name="course_id" class="form-select">
          <?php foreach ($courses as $crs): ?>
            <option value="<?= (int)$crs['course_id'] ?>" <?= $selected_course === (int)$crs['course_id'] ? 'selected' : '' ?>>
              <?= e($crs['course_code']) ?> — <?= e($crs['course_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label small fw-semibold text-secondary">Attendance Date</label>
        <input type="date" name="date" class="form-control tabular-nums" value="<?= e($selected_date) ?>">
      </div>

      <div class="col-md-3">
        <button type="submit" class="btn btn-primary w-100 fw-semibold">
          <i class="bi bi-filter me-1"></i>Load Classroom Sheet
        </button>
      </div>
    </form>
  </div>

  <!-- Roll Call Sheet -->
  <div class="card border-0 shadow-sm rounded-3 bg-white mb-4 overflow-hidden">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
      <h6 class="fw-bold text-dark mb-0">
        Class Roll Call: <?= format_date($selected_date) ?> (<?= count($students) ?> Active Students)
      </h6>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-success py-1" onclick="document.querySelectorAll('.radio-present').forEach(r => r.checked = true);">
          Mark All Present
        </button>
      </div>
    </div>

    <form method="POST" action="attendance.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_batch">
      <input type="hidden" name="course_id" value="<?= $selected_course ?>">
      <input type="hidden" name="date" value="<?= e($selected_date) ?>">

      <div class="table-responsive">
        <table class="table table-hover table-cd mb-0 align-middle">
          <thead>
            <tr>
              <th>Roll Number</th>
              <th>Student Name</th>
              <th>Attendance Status</th>
              <th>Remarks / Note</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($students)): ?>
              <tr><td colspan="4" class="text-center py-4 text-muted">No active students enrolled in this course.</td></tr>
            <?php else: ?>
              <?php foreach ($students as $st): ?>
                <?php $current_stat = $st['recorded_status'] ?? 'Present'; ?>
                <tr>
                  <td class="tabular-nums fw-bold text-primary"><?= e($st['student_code']) ?></td>
                  <td class="fw-semibold text-dark"><?= e($st['full_name']) ?></td>
                  <td>
                    <div class="btn-group btn-group-sm" role="group">
                      <input type="radio" class="btn-check radio-present" name="status[<?= (int)$st['student_id'] ?>]" id="pres_<?= (int)$st['student_id'] ?>" value="Present" <?= $current_stat === 'Present' ? 'checked' : '' ?>>
                      <label class="btn btn-outline-success" for="pres_<?= (int)$st['student_id'] ?>">Present</label>

                      <input type="radio" class="btn-check" name="status[<?= (int)$st['student_id'] ?>]" id="late_<?= (int)$st['student_id'] ?>" value="Late" <?= $current_stat === 'Late' ? 'checked' : '' ?>>
                      <label class="btn btn-outline-warning" for="late_<?= (int)$st['student_id'] ?>">Late</label>

                      <input type="radio" class="btn-check" name="status[<?= (int)$st['student_id'] ?>]" id="abs_<?= (int)$st['student_id'] ?>" value="Absent" <?= $current_stat === 'Absent' ? 'checked' : '' ?>>
                      <label class="btn btn-outline-danger" for="abs_<?= (int)$st['student_id'] ?>">Absent</label>
                    </div>
                  </td>
                  <td>
                    <input type="text" class="form-control form-control-sm" name="remarks[<?= (int)$st['student_id'] ?>]" value="<?= e($st['recorded_remarks'] ?? '') ?>" placeholder="Optional remark">
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if (!empty($students)): ?>
        <div class="card-footer bg-white p-3 d-flex justify-content-end">
          <button type="submit" class="btn btn-primary px-4 fw-semibold">
            <i class="bi bi-save me-1"></i>Save Attendance Session
          </button>
        </div>
      <?php endif; ?>
    </form>
  </div>

  <!-- Cumulative Attendance Summary Table -->
  <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden mb-4">
    <div class="card-header bg-white py-3">
      <h6 class="fw-bold text-dark mb-0">Cumulative Attendance Summary (Current Semester)</h6>
      <div class="text-muted small">Rule: Present = 1.0, Late = 0.5, Absent = 0.0. Division-by-zero safeguarded.</div>
    </div>
    <div class="table-responsive">
      <table class="table table-hover table-cd mb-0 align-middle">
        <thead>
          <tr>
            <th>Roll No</th>
            <th>Student Name</th>
            <th>Total Classes</th>
            <th>Present</th>
            <th>Late</th>
            <th>Absent</th>
            <th>Attendance %</th>
            <th>Academic Standing</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($cumulative_records as $cum): ?>
            <?php 
              $tot = (int)$cum['total_classes'];
              $pres = (int)$cum['present_count'];
              $late = (int)$cum['late_count'];
              $abs = (int)$cum['absent_count'];
              $pct = calculate_attendance_percentage($pres, $late, $tot);
            ?>
            <tr>
              <td class="tabular-nums fw-bold text-primary"><?= e($cum['student_code']) ?></td>
              <td><?= e($cum['full_name']) ?></td>
              <td class="tabular-nums"><?= $tot ?></td>
              <td class="tabular-nums text-success"><?= $pres ?></td>
              <td class="tabular-nums text-warning"><?= $late ?></td>
              <td class="tabular-nums text-danger"><?= $abs ?></td>
              <td class="tabular-nums fw-bold <?= $pct >= 75 ? 'text-success' : 'text-danger' ?>">
                <?= number_format($pct, 1) ?>%
              </td>
              <td>
                <?php if ($pct >= 75): ?>
                  <span class="badge bg-success-subtle text-success">Eligible for Exam</span>
                <?php elseif ($tot === 0): ?>
                  <span class="badge bg-secondary-subtle text-secondary">No Records</span>
                <?php else: ?>
                  <span class="badge bg-danger-subtle text-danger">Shortage Warning (&lt;75%)</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
