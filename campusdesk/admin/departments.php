<?php
/**
 * CampusDesk — Academic Departments Management
 * File: admin/departments.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$page_title = 'Department Management';
$current_page = 'departments';

// Handle Add Department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    verify_csrf_or_die();
    $name = trim($_POST['dept_name'] ?? '');
    $code = strtoupper(trim($_POST['dept_code'] ?? ''));

    if (empty($name) || empty($code)) {
        set_flash('error', 'Department Name and Department Code are required.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO departments (dept_name, dept_code, status) VALUES (:name, :code, 'active')");
            $stmt->execute([':name' => $name, ':code' => $code]);
            set_flash('success', "Department '{$name}' created successfully.");
        } catch (PDOException $e) {
            set_flash('error', 'A department with this name or code already exists.');
        }
    }
    header("Location: departments.php");
    exit;
}

// Handle Delete Department
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf_or_die();
    $dept_id = (int)($_POST['dept_id'] ?? 0);

    try {
        // Check if any courses are attached
        $chk = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE dept_id = :id");
        $chk->execute([':id' => $dept_id]);
        if ((int)$chk->fetchColumn() > 0) {
            set_flash('error', 'Cannot delete department: Courses are currently assigned to it.');
        } else {
            $del = $pdo->prepare("DELETE FROM departments WHERE dept_id = :id");
            $del->execute([':id' => $dept_id]);
            set_flash('success', 'Department deleted successfully.');
        }
    } catch (PDOException $e) {
        set_flash('error', 'Database error deleting department.');
    }
    header("Location: departments.php");
    exit;
}

// Fetch all departments with course counts
$departments = $pdo->query("
    SELECT d.*, COUNT(c.course_id) AS course_count
    FROM departments d
    LEFT JOIN courses c ON d.dept_id = c.dept_id
    GROUP BY d.dept_id
    ORDER BY d.dept_name ASC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4 py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="fw-bold text-dark mb-0">Academic Departments</h3>
      <div class="text-secondary small">Divisional faculties and schools</div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Add Department Card -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
        <h6 class="fw-bold text-dark mb-3">Add Department</h6>
        <form method="POST" action="departments.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">

          <div class="mb-3">
            <label class="form-label small fw-semibold">Department Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="dept_name" placeholder="e.g. Department of Cyber Science" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Department Code <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="dept_code" placeholder="e.g. DCS" required>
          </div>

          <button type="submit" class="btn btn-primary w-100 fw-semibold">Save Department</button>
        </form>
      </div>
    </div>

    <!-- Department List Table -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
        <div class="table-responsive">
          <table class="table table-hover table-cd mb-0 align-middle">
            <thead>
              <tr>
                <th>Code</th>
                <th>Department Name</th>
                <th>Courses</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($departments)): ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">No departments configured yet.</td></tr>
              <?php else: ?>
                <?php foreach ($departments as $d): ?>
                  <tr>
                    <td class="tabular-nums fw-bold text-primary"><?= e($d['dept_code']) ?></td>
                    <td class="fw-semibold text-dark"><?= e($d['dept_name']) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= (int)$d['course_count'] ?> Courses</span></td>
                    <td><span class="badge bg-success-subtle text-success"><?= e($d['status']) ?></span></td>
                    <td class="text-end">
                      <form method="POST" action="departments.php" class="d-inline" onsubmit="return confirm('Delete department <?= addslashes($d['dept_name']) ?>?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="dept_id" value="<?= (int)$d['dept_id'] ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" <?= (int)$d['course_count'] > 0 ? 'disabled title="Cannot delete: has active courses"' : '' ?>>
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
