<?php
/**
 * CampusDesk — Examination Sessions Management
 * File: admin/examinations.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$page_title = 'Examinations Management';
$current_page = 'examinations';

// Handle Add Examination
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    verify_csrf_or_die();
    $course_id = (int)($_POST['course_id'] ?? 0);
    $name      = trim($_POST['exam_name'] ?? '');
    $semester  = (int)($_POST['semester'] ?? 1);
    $date      = trim($_POST['exam_date'] ?? date('Y-m-d'));

    if (empty($name) || $course_id <= 0 || empty($date)) {
        set_flash('error', 'Exam Name, Course, and Date are required.');
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO examinations (course_id, exam_name, semester, exam_date)
                VALUES (:course, :name, :sem, :dt)
            ");
            $stmt->execute([':course' => $course_id, ':name' => $name, ':sem' => $semester, ':dt' => $date]);
            set_flash('success', "Examination session '{$name}' created successfully.");
        } catch (PDOException $e) {
            set_flash('error', 'Error creating examination: ' . $e->getMessage());
        }
    }
    header("Location: examinations.php");
    exit;
}

// Handle Delete Examination
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf_or_die();
    $exam_id = (int)($_POST['exam_id'] ?? 0);
    try {
        $del = $pdo->prepare("DELETE FROM examinations WHERE exam_id = :id");
        $del->execute([':id' => $exam_id]);
        set_flash('success', 'Examination session deleted successfully.');
    } catch (PDOException $e) {
        set_flash('error', 'Cannot delete examination session with recorded marks.');
    }
    header("Location: examinations.php");
    exit;
}

$courses = $pdo->query("SELECT course_id, course_name, course_code FROM courses ORDER BY course_name ASC")->fetchAll();

$exams = $pdo->query("
    SELECT e.*, c.course_code, c.course_name, COUNT(r.result_id) as marks_count
    FROM examinations e
    JOIN courses c ON e.course_id = c.course_id
    LEFT JOIN results r ON e.exam_id = r.exam_id
    GROUP BY e.exam_id
    ORDER BY e.exam_date DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4 py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="fw-bold text-dark mb-0">Examination Schedules</h3>
      <div class="text-secondary small">Define test sessions, mid-terms, and final semester evaluations</div>
    </div>
    <a href="results.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-clipboard2-data me-1"></i>Enter Marks / Results</a>
  </div>

  <div class="row g-4">
    <!-- Add Examination Form -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
        <h6 class="fw-bold text-dark mb-3">Create Examination Session</h6>
        <form method="POST" action="examinations.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">

          <div class="mb-3">
            <label class="form-label small fw-semibold">Examination Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="exam_name" placeholder="e.g. BCA Sem 3 Final Exams 2024" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Course <span class="text-danger">*</span></label>
            <select class="form-select" name="course_id" required>
              <option value="">-- Choose Course --</option>
              <?php foreach ($courses as $c): ?>
                <option value="<?= (int)$c['course_id'] ?>"><?= e($c['course_code']) ?> — <?= e($c['course_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Semester</label>
              <input type="number" min="1" max="8" class="form-control tabular-nums" name="semester" value="3" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Start Date</label>
              <input type="date" class="form-control tabular-nums" name="exam_date" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100 fw-semibold">Schedule Examination</button>
        </form>
      </div>
    </div>

    <!-- Examination List -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
        <div class="table-responsive">
          <table class="table table-hover table-cd mb-0 align-middle">
            <thead>
              <tr>
                <th>Exam Name</th>
                <th>Course</th>
                <th>Semester</th>
                <th>Exam Date</th>
                <th>Marks Recorded</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($exams)): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">No examinations scheduled yet.</td></tr>
              <?php else: ?>
                <?php foreach ($exams as $ex): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold text-dark"><?= e($ex['exam_name']) ?></div>
                    </td>
                    <td><span class="badge bg-light text-dark border"><?= e($ex['course_code']) ?></span></td>
                    <td class="tabular-nums">Sem <?= (int)$ex['semester'] ?></td>
                    <td class="tabular-nums small"><?= format_date($ex['exam_date']) ?></td>
                    <td>
                      <a href="results.php?exam_id=<?= (int)$ex['exam_id'] ?>" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none">
                        <?= (int)$ex['marks_count'] ?> Entries &rarr;
                      </a>
                    </td>
                    <td class="text-end">
                      <form method="POST" action="examinations.php" class="d-inline" onsubmit="return confirm('Delete examination <?= addslashes($ex['exam_name']) ?>?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="exam_id" value="<?= (int)$ex['exam_id'] ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" title="Delete Exam">
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
