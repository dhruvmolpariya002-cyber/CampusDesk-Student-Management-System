<?php
/**
 * CampusDesk — Reports & CSV Export Engine
 * File: admin/reports.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$type = trim($_GET['type'] ?? 'students');
$export = isset($_GET['export']) && $_GET['export'] === 'csv';

// ----------------------------------------------------------------------------
// CSV Export Dispatcher
// ----------------------------------------------------------------------------
if ($export) {
    $filename = "campusdesk_{$type}_" . date('Ymd_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    $output = fopen('php://output', 'w');

    // UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    if ($type === 'students') {
        fputcsv($output, ['Roll No', 'Full Name', 'Course', 'Email', 'Phone', 'Gender', 'Admission Date', 'Status']);
        $rows = $pdo->query("
            SELECT s.student_code, s.full_name, c.course_code, s.email, s.phone, s.gender, s.admission_date, s.enrollment_status
            FROM students s JOIN courses c ON s.course_id = c.course_id
            ORDER BY s.student_code ASC
        ")->fetchAll();
        foreach ($rows as $r) {
            fputcsv($output, [$r['student_code'], $r['full_name'], $r['course_code'], $r['email'], $r['phone'], $r['gender'], $r['admission_date'], $r['enrollment_status']]);
        }

    } elseif ($type === 'attendance') {
        fputcsv($output, ['Roll No', 'Full Name', 'Course', 'Date', 'Status', 'Remarks']);
        $rows = $pdo->query("
            SELECT s.student_code, s.full_name, c.course_code, a.attendance_date, a.status, a.remarks
            FROM attendance a
            JOIN students s ON a.student_id = s.student_id
            JOIN courses c ON a.course_id = c.course_id
            ORDER BY a.attendance_date DESC, s.student_code ASC
        ")->fetchAll();
        foreach ($rows as $r) {
            fputcsv($output, [$r['student_code'], $r['full_name'], $r['course_code'], $r['attendance_date'], $r['status'], $r['remarks'] ?? '']);
        }

    } elseif ($type === 'results') {
        fputcsv($output, ['Exam Name', 'Roll No', 'Student Name', 'Subject Code', 'Subject Name', 'Max Marks', 'Obtained Marks', 'Percentage', 'Grade', 'Status']);
        $rows = $pdo->query("
            SELECT ex.exam_name, s.student_code, s.full_name, sub.subject_code, sub.subject_name,
                   r.max_marks, r.obtained_marks, r.grade, r.status
            FROM results r
            JOIN examinations ex ON r.exam_id = ex.exam_id
            JOIN students s ON r.student_id = s.student_id
            JOIN subjects sub ON r.subject_id = sub.subject_id
            ORDER BY ex.exam_date DESC, s.student_code ASC
        ")->fetchAll();
        foreach ($rows as $r) {
            $pct = ($r['max_marks'] > 0) ? round(($r['obtained_marks'] / $r['max_marks']) * 100, 1) : 0;
            fputcsv($output, [$r['exam_name'], $r['student_code'], $r['full_name'], $r['subject_code'], $r['subject_name'], $r['max_marks'], $r['obtained_marks'], $pct . '%', $r['grade'], $r['status']]);
        }

    } elseif ($type === 'fees') {
        fputcsv($output, ['Invoice ID', 'Roll No', 'Student Name', 'Fee Type', 'Total Billed (INR)', 'Paid (INR)', 'Balance Due (INR)', 'Due Date', 'Status', 'Payment Date', 'Reference No']);
        $rows = $pdo->query("
            SELECT f.*, s.student_code, s.full_name
            FROM fees f
            JOIN students s ON f.student_id = s.student_id
            ORDER BY f.fee_id DESC
        ")->fetchAll();
        foreach ($rows as $r) {
            $balance = (float)$r['total_amount'] - (float)$r['paid_amount'];
            fputcsv($output, [$r['fee_id'], $r['student_code'], $r['full_name'], $r['fee_type'], $r['total_amount'], $r['paid_amount'], $balance, $r['due_date'], $r['payment_status'], $r['payment_date'] ?? '', $r['reference_no'] ?? '']);
        }
    }

    fclose($output);
    exit;
}

$page_title = 'Reports & Data Exports';
$current_page = 'reports';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4 py-3">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h3 class="fw-bold text-dark mb-0">Academic Reports &amp; Exports</h3>
      <div class="text-secondary small">Generate institutional transcripts, attendance summaries, and ledger records in CSV</div>
    </div>
  </div>

  <!-- Report Nav Tabs -->
  <ul class="nav nav-pills mb-4 gap-2">
    <li class="nav-item">
      <a class="nav-link <?= $type === 'students' ? 'active' : 'bg-white border' ?>" href="reports.php?type=students">
        <i class="bi bi-people me-1"></i>Students Directory
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $type === 'attendance' ? 'active' : 'bg-white border' ?>" href="reports.php?type=attendance">
        <i class="bi bi-calendar2-check me-1"></i>Attendance Log
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $type === 'results' ? 'active' : 'bg-white border' ?>" href="reports.php?type=results">
        <i class="bi bi-file-earmark-text me-1"></i>Exam Results
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?= $type === 'fees' ? 'active' : 'bg-white border' ?>" href="reports.php?type=fees">
        <i class="bi bi-cash-stack me-1"></i>Fee Collections
      </a>
    </li>
  </ul>

  <!-- Report Preview Card -->
  <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h6 class="fw-bold text-dark mb-0 text-capitalize"><?= e($type) ?> Report Preview</h6>
      <a href="reports.php?type=<?= urlencode($type) ?>&export=csv" class="btn btn-success btn-sm fw-semibold">
        <i class="bi bi-filetype-csv me-1"></i>Download CSV Export
      </a>
    </div>

    <div class="table-responsive">
      <?php if ($type === 'students'): ?>
        <?php $data = $pdo->query("SELECT s.*, c.course_code FROM students s JOIN courses c ON s.course_id = c.course_id ORDER BY s.student_code ASC")->fetchAll(); ?>
        <table class="table table-hover table-cd mb-0 align-middle">
          <thead>
            <tr>
              <th>Roll No</th>
              <th>Full Name</th>
              <th>Course</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($data as $r): ?>
              <tr>
                <td class="tabular-nums fw-bold text-primary"><?= e($r['student_code']) ?></td>
                <td><?= e($r['full_name']) ?></td>
                <td><span class="badge bg-light text-dark border"><?= e($r['course_code']) ?></span></td>
                <td><?= e($r['email']) ?></td>
                <td class="tabular-nums"><?= e($r['phone']) ?></td>
                <td><span class="badge bg-success-subtle text-success"><?= e($r['enrollment_status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

      <?php elseif ($type === 'attendance'): ?>
        <?php $data = $pdo->query("SELECT a.*, s.student_code, s.full_name, c.course_code FROM attendance a JOIN students s ON a.student_id = s.student_id JOIN courses c ON a.course_id = c.course_id ORDER BY a.attendance_date DESC LIMIT 50")->fetchAll(); ?>
        <table class="table table-hover table-cd mb-0 align-middle">
          <thead>
            <tr>
              <th>Date</th>
              <th>Roll No</th>
              <th>Student Name</th>
              <th>Course</th>
              <th>Status</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($data as $r): ?>
              <tr>
                <td class="tabular-nums"><?= format_date($r['attendance_date']) ?></td>
                <td class="tabular-nums fw-bold text-primary"><?= e($r['student_code']) ?></td>
                <td><?= e($r['full_name']) ?></td>
                <td><span class="badge bg-light text-dark border"><?= e($r['course_code']) ?></span></td>
                <td>
                  <span class="badge <?= $r['status'] === 'Present' ? 'bg-success-subtle text-success' : ($r['status'] === 'Late' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger') ?>">
                    <?= e($r['status']) ?>
                  </span>
                </td>
                <td class="small text-muted"><?= e($r['remarks'] ?? '—') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

      <?php elseif ($type === 'results'): ?>
        <?php $data = $pdo->query("SELECT r.*, s.student_code, s.full_name, sub.subject_code, sub.subject_name, ex.exam_name FROM results r JOIN students s ON r.student_id = s.student_id JOIN subjects sub ON r.subject_id = sub.subject_id JOIN examinations ex ON r.exam_id = ex.exam_id ORDER BY r.result_id DESC")->fetchAll(); ?>
        <table class="table table-hover table-cd mb-0 align-middle">
          <thead>
            <tr>
              <th>Exam Name</th>
              <th>Roll No</th>
              <th>Student Name</th>
              <th>Subject</th>
              <th>Score</th>
              <th>Grade</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($data as $r): ?>
              <tr>
                <td class="fw-semibold"><?= e($r['exam_name']) ?></td>
                <td class="tabular-nums fw-bold text-primary"><?= e($r['student_code']) ?></td>
                <td><?= e($r['full_name']) ?></td>
                <td><?= e($r['subject_code']) ?> — <?= e($r['subject_name']) ?></td>
                <td class="tabular-nums"><?= (float)$r['obtained_marks'] ?> / <?= (float)$r['max_marks'] ?></td>
                <td><span class="badge bg-dark-subtle text-dark border"><?= e($r['grade']) ?></span></td>
                <td><span class="badge <?= $r['status'] === 'Pass' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>"><?= e($r['status']) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

      <?php elseif ($type === 'fees'): ?>
        <?php $data = $pdo->query("SELECT f.*, s.student_code, s.full_name FROM fees f JOIN students s ON f.student_id = s.student_id ORDER BY f.fee_id DESC")->fetchAll(); ?>
        <table class="table table-hover table-cd mb-0 align-middle">
          <thead>
            <tr>
              <th>Invoice #</th>
              <th>Roll No</th>
              <th>Student Name</th>
              <th>Fee Description</th>
              <th>Total Billed</th>
              <th>Paid</th>
              <th>Outstanding</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($data as $r): ?>
              <?php $bal = (float)$r['total_amount'] - (float)$r['paid_amount']; ?>
              <tr>
                <td class="tabular-nums">#<?= (int)$r['fee_id'] ?></td>
                <td class="tabular-nums fw-bold text-primary"><?= e($r['student_code']) ?></td>
                <td><?= e($r['full_name']) ?></td>
                <td><?= e($r['fee_type']) ?></td>
                <td class="tabular-nums"><?= format_currency($r['total_amount']) ?></td>
                <td class="tabular-nums text-success"><?= format_currency($r['paid_amount']) ?></td>
                <td class="tabular-nums fw-bold <?= $bal > 0 ? 'text-danger' : 'text-muted' ?>"><?= format_currency($bal) ?></td>
                <td>
                  <span class="badge <?= $r['payment_status'] === 'Paid' ? 'bg-success-subtle text-success' : ($r['payment_status'] === 'Partial' ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger') ?>">
                    <?= e($r['payment_status']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
