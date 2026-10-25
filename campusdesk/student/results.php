<?php
/**
 * CampusDesk — Student Examination Gradecard & Results
 * File: student/results.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('student');

$page_title = 'Examination Results';
$current_page = 'results';

$user_id = (int)$_SESSION['user_id'];

// Get student_id
$st = $pdo->prepare("SELECT student_id, student_code, full_name FROM students WHERE user_id = :uid");
$st->execute([':uid' => $user_id]);
$student = $st->fetch();
$student_id = (int)$student['student_id'];

// Fetch all examination results grouped by examination session
$results_stmt = $pdo->prepare("
    SELECT r.*, sub.subject_name, sub.subject_code, sub.credits,
           ex.exam_name, ex.semester, ex.exam_date
    FROM results r
    JOIN subjects sub ON r.subject_id = sub.subject_id
    JOIN examinations ex ON r.exam_id = ex.exam_id
    WHERE r.student_id = :sid
    ORDER BY ex.exam_date DESC, sub.subject_code ASC
");
$results_stmt->execute([':sid' => $student_id]);
$all_results = $results_stmt->fetchAll();

// Group results by exam_id
$exams = [];
foreach ($all_results as $r) {
    $eid = (int)$r['exam_id'];
    if (!isset($exams[$eid])) {
        $exams[$eid] = [
            'exam_name' => $r['exam_name'],
            'semester'  => $r['semester'],
            'exam_date' => $r['exam_date'],
            'marks'     => [],
            'total_max' => 0.0,
            'total_obt' => 0.0
        ];
    }
    $exams[$eid]['marks'][] = $r;
    $exams[$eid]['total_max'] += (float)$r['max_marks'];
    $exams[$eid]['total_obt'] += (float)$r['obtained_marks'];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4 py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="fw-bold text-dark mb-0">Examination Gradecards</h3>
      <div class="text-secondary small">Official subject evaluations, marks, and GPA credentials</div>
    </div>
  </div>

  <?php if (empty($exams)): ?>
    <div class="card border-0 shadow-sm rounded-3 p-5 text-center bg-white">
      <i class="bi bi-file-earmark-x fs-1 text-secondary mb-3"></i>
      <h5 class="fw-bold">No Examination Results Published Yet</h5>
      <p class="text-muted small">Your faculty evaluations and gradecards will be posted here once published.</p>
    </div>
  <?php else: ?>
    <?php foreach ($exams as $eid => $ex): ?>
      <?php 
        $pct = ($ex['total_max'] > 0) ? round(($ex['total_obt'] / $ex['total_max']) * 100, 1) : 0;
        $eval = determine_grade($pct);
      ?>
      <div class="card border-0 shadow-sm rounded-3 bg-white mb-4 overflow-hidden">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
          <div>
            <h5 class="fw-bold text-dark mb-0"><?= e($ex['exam_name']) ?></h5>
            <div class="text-muted small">Semester <?= (int)$ex['semester'] ?> · Date: <?= format_date($ex['exam_date']) ?></div>
          </div>
          <div class="d-flex align-items-center gap-3">
            <div class="text-end">
              <div class="small text-muted">Overall Percentage</div>
              <div class="fw-bold tabular-nums text-primary fs-5"><?= $pct ?>%</div>
            </div>
            <span class="badge bg-dark-subtle text-dark border px-3 py-2 fw-bold fs-6">
              Grade: <?= e($eval['grade']) ?> (<?= e($eval['status']) ?>)
            </span>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover table-cd mb-0 align-middle">
            <thead>
              <tr>
                <th>Subject Code</th>
                <th>Subject Name</th>
                <th>Credits</th>
                <th>Max Marks</th>
                <th>Marks Obtained</th>
                <th>Grade</th>
                <th>Result</th>
                <th>Remarks</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($ex['marks'] as $m): ?>
                <tr>
                  <td class="tabular-nums fw-bold text-primary"><?= e($m['subject_code']) ?></td>
                  <td class="fw-semibold text-dark"><?= e($m['subject_name']) ?></td>
                  <td class="tabular-nums"><?= (int)$m['credits'] ?></td>
                  <td class="tabular-nums"><?= (float)$m['max_marks'] ?></td>
                  <td class="tabular-nums fw-bold"><?= (float)$m['obtained_marks'] ?></td>
                  <td><span class="badge bg-light text-dark border fw-bold"><?= e($m['grade']) ?></span></td>
                  <td>
                    <span class="badge <?= $m['status'] === 'Pass' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                      <?= e($m['status']) ?>
                    </span>
                  </td>
                  <td class="small text-muted"><?= e($m['remarks'] ?? '—') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light">
              <tr class="fw-bold">
                <td colspan="3" class="text-end">Total Academic Score:</td>
                <td class="tabular-nums"><?= $ex['total_max'] ?></td>
                <td class="tabular-nums text-primary"><?= $ex['total_obt'] ?></td>
                <td colspan="3" class="text-muted small">Percentage: <?= $pct ?>% (<?= e($eval['label']) ?>)</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
