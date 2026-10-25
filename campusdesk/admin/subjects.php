<?php
/**
 * CampusDesk — Subjects Management
 * File: admin/subjects.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$page_title = 'Subjects & Modules';
$current_page = 'subjects';

// Handle Add Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    verify_csrf_or_die();
    $name      = trim($_POST['subject_name'] ?? '');
    $code      = strtoupper(trim($_POST['subject_code'] ?? ''));
    $course_id = (int)($_POST['course_id'] ?? 0);
    $semester  = (int)($_POST['semester'] ?? 1);
    $credits   = (int)($_POST['credits'] ?? 4);

    if (empty($name) || empty($code) || $course_id <= 0) {
        set_flash('error', 'Subject Name, Subject Code, and Course are required.');
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO subjects (course_id, subject_name, subject_code, semester, credits)
                VALUES (:course_id, :name, :code, :semester, :credits)
            ");
            $stmt->execute([
                ':course_id' => $course_id,
                ':name'      => $name,
                ':code'      => $code,
                ':semester'  => $semester,
                ':credits'   => $credits
            ]);
            set_flash('success', "Subject '{$name}' [{$code}] added successfully.");
        } catch (PDOException $e) {
            set_flash('error', 'Subject code already exists.');
        }
    }
    header("Location: subjects.php");
    exit;
}

// Handle Delete Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf_or_die();
    $sub_id = (int)($_POST['subject_id'] ?? 0);
    try {
        $del = $pdo->prepare("DELETE FROM subjects WHERE subject_id = :id");
        $del->execute([':id' => $sub_id]);
        set_flash('success', 'Subject deleted successfully.');
    } catch (PDOException $e) {
        set_flash('error', 'Cannot delete subject: Examination results exist for this subject.');
    }
    header("Location: subjects.php");
    exit;
}

$courses = $pdo->query("SELECT course_id, course_name, course_code FROM courses ORDER BY course_name ASC")->fetchAll();

$subjects = $pdo->query("
    SELECT s.*, c.course_code, c.course_name
    FROM subjects s
    JOIN courses c ON s.course_id = c.course_id
    ORDER BY c.course_code ASC, s.semester ASC, s.subject_name ASC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4 py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="fw-bold text-dark mb-0">Academic Subjects</h3>
      <div class="text-secondary small">Course syllabus modules, semesters, and credit allocations</div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Add Subject Form -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
        <h6 class="fw-bold text-dark mb-3">Add Subject</h6>
        <form method="POST" action="subjects.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">

          <div class="mb-3">
            <label class="form-label small fw-semibold">Subject Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="subject_name" placeholder="e.g. Software Engineering" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Subject Code <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="subject_code" placeholder="e.g. BCA305" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Parent Course <span class="text-danger">*</span></label>
            <select class="form-select" name="course_id" required>
              <option value="">-- Select Course --</option>
              <?php foreach ($courses as $crs): ?>
                <option value="<?= (int)$crs['course_id'] ?>"><?= e($crs['course_code']) ?> — <?= e($crs['course_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Semester</label>
              <input type="number" min="1" max="8" class="form-control" name="semester" value="3" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Credits</label>
              <input type="number" min="1" max="8" class="form-control" name="credits" value="4" required>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100 fw-semibold">Save Subject</button>
        </form>
      </div>
    </div>

    <!-- Subjects List Table -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
        <div class="table-responsive">
          <table class="table table-hover table-cd mb-0 align-middle">
            <thead>
              <tr>
                <th>Code</th>
                <th>Subject Name</th>
                <th>Course</th>
                <th>Semester</th>
                <th>Credits</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($subjects)): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">No subjects registered yet.</td></tr>
              <?php else: ?>
                <?php foreach ($subjects as $sb): ?>
                  <tr>
                    <td class="tabular-nums fw-bold text-primary"><?= e($sb['subject_code']) ?></td>
                    <td class="fw-semibold text-dark"><?= e($sb['subject_name']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= e($sb['course_code']) ?></span></td>
                    <td class="tabular-nums">Sem <?= (int)$sb['semester'] ?></td>
                    <td class="tabular-nums"><?= (int)$sb['credits'] ?> Credits</td>
                    <td class="text-end">
                      <form method="POST" action="subjects.php" class="d-inline" onsubmit="return confirm('Delete subject <?= addslashes($sb['subject_name']) ?>?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="subject_id" value="<?= (int)$sb['subject_id'] ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" title="Delete Subject">
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
