<?php
/**
 * CampusDesk — Examination Results & Marks Entry
 * File: admin/results.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$page_title = 'Marksheet & Examination Results';
$current_page = 'results';

$exams = $pdo->query("SELECT exam_id, exam_name, semester FROM examinations ORDER BY exam_date DESC")->fetchAll();
$selected_exam = (int)($_GET['exam_id'] ?? ($exams[0]['exam_id'] ?? 0));

// Handle Add / Edit Mark Record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_mark') {
    verify_csrf_or_die();
    $exam_id   = (int)($_POST['exam_id'] ?? 0);
    $student_id = (int)($_POST['student_id'] ?? 0);
    $subject_id = (int)($_POST['subject_id'] ?? 0);
    $max_marks  = (float)($_POST['max_marks'] ?? 100.0);
    $obtained   = (float)($_POST['obtained_marks'] ?? 0.0);
    $remarks    = trim($_POST['remarks'] ?? '');

    // Strict Mark Validation
    if ($exam_id <= 0 || $student_id <= 0 || $subject_id <= 0) {
        set_flash('error', 'Exam, Student, and Subject are all required.');
    } elseif ($obtained < 0) {
        set_flash('error', 'Obtained marks cannot be negative.');
    } elseif ($obtained > $max_marks) {
        set_flash('error', "Obtained marks ({$obtained}) cannot exceed maximum marks ({$max_marks}).");
    } elseif ($max_marks <= 0) {
        set_flash('error', 'Maximum marks must be greater than zero.');
    } else {
        // Automatic Grade Calculation
        $percentage = ($obtained / $max_marks) * 100;
        $eval = determine_grade($percentage);

        try {
            $stmt = $pdo->prepare("
                INSERT INTO results (exam_id, student_id, subject_id, max_marks, obtained_marks, grade, status, remarks)
                VALUES (:exam, :student, :subject, :max, :obtained, :grade, :status, :rem)
                ON DUPLICATE KEY UPDATE
                    max_marks = VALUES(max_marks),
                    obtained_marks = VALUES(obtained_marks),
                    grade = VALUES(grade),
                    status = VALUES(status),
                    remarks = VALUES(remarks)
            ");
            $stmt->execute([
                ':exam'     => $exam_id,
                ':student'  => $student_id,
                ':subject'  => $subject_id,
                ':max'      => $max_marks,
                ':obtained' => $obtained,
                ':grade'    => $eval['grade'],
                ':status'   => $eval['status'],
                ':rem'      => $remarks ?: null
            ]);
            set_flash('success', "Marks saved successfully. Evaluated Grade: {$eval['grade']} ({$eval['status']})");
        } catch (PDOException $e) {
            set_flash('error', 'Database error saving result: ' . $e->getMessage());
        }
    }
    header("Location: results.php?exam_id={$exam_id}");
    exit;
}

// Handle Delete Result
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf_or_die();
    $result_id = (int)($_POST['result_id'] ?? 0);
    $exam_id = (int)($_POST['exam_id'] ?? $selected_exam);
    try {
        $del = $pdo->prepare("DELETE FROM results WHERE result_id = :id");
        $del->execute([':id' => $result_id]);
        set_flash('success', 'Mark entry deleted successfully.');
    } catch (PDOException $e) {
        set_flash('error', 'Failed to delete result.');
    }
    header("Location: results.php?exam_id={$exam_id}");
    exit;
}

// Fetch Students & Subjects for dropdowns
$students = $pdo->query("SELECT student_id, student_code, full_name FROM students WHERE enrollment_status = 'Active' ORDER BY student_code ASC")->fetchAll();
$subjects = $pdo->query("SELECT subject_id, subject_name, subject_code FROM subjects ORDER BY subject_name ASC")->fetchAll();

// Fetch Results for the selected exam
$where_exam = $selected_exam > 0 ? "WHERE r.exam_id = :exam_id" : "";
$results_stmt = $pdo->prepare("
    SELECT r.*, s.student_code, s.full_name as student_name,
           sub.subject_name, sub.subject_code,
           ex.exam_name
    FROM results r
    JOIN students s ON r.student_id = s.student_id
    JOIN subjects sub ON r.subject_id = sub.subject_id
    JOIN examinations ex ON r.exam_id = ex.exam_id
    {$where_exam}
    ORDER BY s.student_code ASC, sub.subject_code ASC
");
if ($selected_exam > 0) {
    $results_stmt->execute([':exam_id' => $selected_exam]);
} else {
    $results_stmt->execute();
}
$results = $results_stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4 py-3">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h3 class="fw-bold text-dark mb-0">Examination Marksheets</h3>
      <div class="text-secondary small">Subject marks evaluation, grading calculations, and performance records</div>
    </div>
    <a href="reports.php?type=results&exam_id=<?= $selected_exam ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-download me-1"></i>Export Results CSV
    </a>
  </div>

  <div class="row g-4">
    <!-- Entry Form -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
        <h6 class="fw-bold text-dark mb-3">Record Student Marks</h6>
        <form method="POST" action="results.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save_mark">

          <div class="mb-3">
            <label class="form-label small fw-semibold">Examination <span class="text-danger">*</span></label>
            <select class="form-select" name="exam_id" required>
              <?php foreach ($exams as $ex): ?>
                <option value="<?= (int)$ex['exam_id'] ?>" <?= $selected_exam === (int)$ex['exam_id'] ? 'selected' : '' ?>>
                  <?= e($ex['exam_name']) ?> (Sem <?= (int)$ex['semester'] ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Student <span class="text-danger">*</span></label>
            <select class="form-select" name="student_id" required>
              <option value="">-- Select Student --</option>
              <?php foreach ($students as $st): ?>
                <option value="<?= (int)$st['student_id'] ?>">
                  <?= e($st['student_code']) ?> — <?= e($st['full_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Subject <span class="text-danger">*</span></label>
            <select class="form-select" name="subject_id" required>
              <option value="">-- Select Subject --</option>
              <?php foreach ($subjects as $sub): ?>
                <option value="<?= (int)$sub['subject_id'] ?>">
                  <?= e($sub['subject_code']) ?> — <?= e($sub['subject_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Max Marks</label>
              <input type="number" step="0.5" min="1" max="500" class="form-control tabular-nums" name="max_marks" value="100.0" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Obtained Marks</label>
              <input type="number" step="0.5" min="0" max="500" class="form-control tabular-nums" name="obtained_marks" value="0.0" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Faculty Remarks</label>
            <input type="text" class="form-control" name="remarks" placeholder="e.g. Good theoretical knowledge">
          </div>

          <button type="submit" class="btn btn-primary w-100 fw-semibold">Save &amp; Compute Grade</button>
        </form>
      </div>
    </div>

    <!-- Results Table -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
          <h6 class="fw-bold text-dark mb-0">Gradecard Entries</h6>
          <!-- Exam Selector Filter -->
          <form method="GET" action="results.php" class="d-inline-flex gap-2">
            <select name="exam_id" class="form-select form-select-sm" onchange="this.form.submit()">
              <?php foreach ($exams as $ex): ?>
                <option value="<?= (int)$ex['exam_id'] ?>" <?= $selected_exam === (int)$ex['exam_id'] ? 'selected' : '' ?>>
                  <?= e($ex['exam_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>

        <div class="table-responsive">
          <table class="table table-hover table-cd mb-0 align-middle">
            <thead>
              <tr>
                <th>Roll No</th>
                <th>Student Name</th>
                <th>Subject</th>
                <th>Score</th>
                <th>Grade</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($results)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">No marks recorded for this examination yet.</td></tr>
              <?php else: ?>
                <?php foreach ($results as $res): ?>
                  <?php 
                    $pct = ($res['max_marks'] > 0) ? ($res['obtained_marks'] / $res['max_marks']) * 100 : 0;
                  ?>
                  <tr>
                    <td class="tabular-nums fw-bold text-primary"><?= e($res['student_code']) ?></td>
                    <td><?= e($res['student_name']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= e($res['subject_code']) ?></span></td>
                    <td class="tabular-nums fw-semibold">
                      <?= (float)$res['obtained_marks'] ?> / <?= (float)$res['max_marks'] ?>
                      <span class="text-muted small ms-1">(<?= number_format($pct, 1) ?>%)</span>
                    </td>
                    <td>
                      <span class="badge bg-dark-subtle text-dark border fw-bold"><?= e($res['grade']) ?></span>
                    </td>
                    <td>
                      <span class="badge <?= $res['status'] === 'Pass' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                        <?= e($res['status']) ?>
                      </span>
                    </td>
                    <td class="text-end">
                      <form method="POST" action="results.php" class="d-inline" onsubmit="return confirm('Delete this result entry?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="result_id" value="<?= (int)$res['result_id'] ?>">
                        <input type="hidden" name="exam_id" value="<?= $selected_exam ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" title="Delete Mark">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
