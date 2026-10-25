<?php
/**
 * CampusDesk — Course Management
 * File: admin/courses.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$page_title = 'Course Management';
$current_page = 'courses';

// Handle Add Course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    verify_csrf_or_die();
    $name     = trim($_POST['course_name'] ?? '');
    $code     = strtoupper(trim($_POST['course_code'] ?? ''));
    $dept_id  = (int)($_POST['dept_id'] ?? 0);
    $duration = (float)($_POST['duration_years'] ?? 3.0);
    $desc     = trim($_POST['description'] ?? '');

    if (empty($name) || empty($code) || $dept_id <= 0) {
        set_flash('error', 'Course Name, Course Code, and Department are required.');
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO courses (dept_id, course_name, course_code, duration_years, description, status)
                VALUES (:dept, :name, :code, :duration, :desc, 'active')
            ");
            $stmt->execute([
                ':dept'     => $dept_id,
                ':name'     => $name,
                ':code'     => $code,
                ':duration' => $duration,
                ':desc'     => $desc
            ]);
            set_flash('success', "Course '{$name}' [{$code}] added successfully.");
        } catch (PDOException $e) {
            set_flash('error', 'A course with this Course Code already exists.');
        }
    }
    header("Location: courses.php");
    exit;
}

// Handle Delete Course
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf_or_die();
    $course_id = (int)($_POST['course_id'] ?? 0);

    try {
        // Enforce referential safety: check if students are enrolled
        $chk = $pdo->prepare("SELECT COUNT(*) FROM students WHERE course_id = :id");
        $chk->execute([':id' => $course_id]);
        $enrolled = (int)$chk->fetchColumn();

        if ($enrolled > 0) {
            set_flash('error', "Cannot delete course: {$enrolled} student(s) are currently enrolled in it. Reassign students first.");
        } else {
            $del = $pdo->prepare("DELETE FROM courses WHERE course_id = :id");
            $del->execute([':id' => $course_id]);
            set_flash('success', 'Course deleted successfully.');
        }
    } catch (PDOException $e) {
        set_flash('error', 'Database constraint prevented course deletion.');
    }
    header("Location: courses.php");
    exit;
}

$departments = $pdo->query("SELECT dept_id, dept_name, dept_code FROM departments WHERE status = 'active' ORDER BY dept_name ASC")->fetchAll();

$courses = $pdo->query("
    SELECT c.*, d.dept_name, d.dept_code, COUNT(s.student_id) as enrolled_students
    FROM courses c
    JOIN departments d ON c.dept_id = d.dept_id
    LEFT JOIN students s ON c.course_id = s.course_id
    GROUP BY c.course_id
    ORDER BY c.course_name ASC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4 py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="fw-bold text-dark mb-0">Courses &amp; Curriculum</h3>
      <div class="text-secondary small">Academic programs offered across departments</div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Add Course Form -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
        <h6 class="fw-bold text-dark mb-3">Add New Course</h6>
        <form method="POST" action="courses.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">

          <div class="mb-3">
            <label class="form-label small fw-semibold">Course Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="course_name" placeholder="e.g. Master of Science in IT" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Course Code <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="course_code" placeholder="e.g. MSCIT" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Department <span class="text-danger">*</span></label>
            <select class="form-select" name="dept_id" required>
              <option value="">-- Choose Department --</option>
              <?php foreach ($departments as $dept): ?>
                <option value="<?= (int)$dept['dept_id'] ?>"><?= e($dept['dept_code']) ?> — <?= e($dept['dept_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Duration (Years)</label>
            <input type="number" step="0.5" min="0.5" max="6" class="form-control" name="duration_years" value="3.0" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Description</label>
            <textarea class="form-control" name="description" rows="2" placeholder="Course goals and syllabus brief"></textarea>
          </div>

          <button type="submit" class="btn btn-primary w-100 fw-semibold">Add Course</button>
        </form>
      </div>
    </div>

    <!-- Courses List Table -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
        <div class="table-responsive">
          <table class="table table-hover table-cd mb-0 align-middle">
            <thead>
              <tr>
                <th>Code</th>
                <th>Course Name</th>
                <th>Department</th>
                <th>Duration</th>
                <th>Enrolled</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($courses)): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">No courses registered yet.</td></tr>
              <?php else: ?>
                <?php foreach ($courses as $crs): ?>
                  <tr>
                    <td class="tabular-nums fw-bold text-primary"><?= e($crs['course_code']) ?></td>
                    <td>
                      <div class="fw-semibold text-dark"><?= e($crs['course_name']) ?></div>
                      <div class="small text-muted text-truncate" style="max-width: 280px;"><?= e($crs['description']) ?></div>
                    </td>
                    <td><span class="badge bg-light text-secondary border"><?= e($crs['dept_code']) ?></span></td>
                    <td class="tabular-nums"><?= (float)$crs['duration_years'] ?> Yrs</td>
                    <td>
                      <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                        <?= (int)$crs['enrolled_students'] ?> Students
                      </span>
                    </td>
                    <td class="text-end">
                      <form method="POST" action="courses.php" class="d-inline" onsubmit="return confirm('Delete course <?= addslashes($crs['course_name']) ?>?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="course_id" value="<?= (int)$crs['course_id'] ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" <?= (int)$crs['enrolled_students'] > 0 ? 'disabled title="Cannot delete: active enrolled students"' : '' ?>>
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
