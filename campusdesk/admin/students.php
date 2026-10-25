<?php
/**
 * CampusDesk — Student Directory & Management
 * File: admin/students.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$page_title = 'Students Directory';
$current_page = 'students';

// Handle Student Deletion (POST only with CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf_or_die();
    $delete_id = (int)($_POST['student_id'] ?? 0);

    if ($delete_id > 0) {
        try {
            // Find linked user_id first if any
            $stmt = $pdo->prepare("SELECT user_id, full_name FROM students WHERE student_id = :id");
            $stmt->execute([':id' => $delete_id]);
            $student = $stmt->fetch();

            if ($student) {
                // Delete student record
                $del_stmt = $pdo->prepare("DELETE FROM students WHERE student_id = :id");
                $del_stmt->execute([':id' => $delete_id]);

                // If user account exists, remove user as well
                if (!empty($student['user_id'])) {
                    $del_user = $pdo->prepare("DELETE FROM users WHERE user_id = :uid");
                    $del_user->execute([':uid' => $student['user_id']]);
                }

                set_flash('success', "Student '{$student['full_name']}' was successfully deleted.");
            } else {
                set_flash('error', "Student record not found.");
            }
        } catch (PDOException $e) {
            error_log("Delete student error: " . $e->getMessage());
            set_flash('error', "Cannot delete student because related academic records (attendance, marks, or fees) exist. Please reassign or clear records first.");
        }
    }
    header("Location: students.php");
    exit;
}

// Search, Filter & Pagination Parameters
$search = trim($_GET['search'] ?? '');
$filter_course = (int)($_GET['course_id'] ?? 0);
$filter_status = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build Dynamic SQL Query with PDO parameters
$where_clauses = [];
$params = [];

if ($search !== '') {
    $where_clauses[] = "(s.full_name LIKE :search OR s.student_code LIKE :search OR s.email LIKE :search OR s.phone LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($filter_course > 0) {
    $where_clauses[] = "s.course_id = :course_id";
    $params[':course_id'] = $filter_course;
}

if ($filter_status !== '') {
    $where_clauses[] = "s.enrollment_status = :status";
    $params[':status'] = $filter_status;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Count Total Filtered Records
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM students s {$where_sql}");
$count_stmt->execute($params);
$total_records = (int)$count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch Student List with Course Name
$query_sql = "
    SELECT s.*, c.course_name, c.course_code
    FROM students s
    JOIN courses c ON s.course_id = c.course_id
    {$where_sql}
    ORDER BY s.student_id DESC
    LIMIT {$limit} OFFSET {$offset}
";
$data_stmt = $pdo->prepare($query_sql);
$data_stmt->execute($params);
$students = $data_stmt->fetchAll();

// Fetch Courses for Filter Dropdown
$courses = $pdo->query("SELECT course_id, course_name, course_code FROM courses ORDER BY course_name ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4 py-3">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h3 class="fw-bold text-dark mb-0">Student Directory</h3>
      <div class="text-secondary small">Comprehensive registry of all admitted students</div>
    </div>
    <div class="d-flex gap-2">
      <a href="reports.php?type=students" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export CSV</a>
      <a href="student_add.php" class="btn btn-primary btn-sm"><i class="bi bi-person-plus-fill me-1"></i>Add New Student</a>
    </div>
  </div>

  <!-- Search & Filter Card -->
  <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-4">
    <form method="GET" action="students.php" class="row g-2 align-items-center">
      <div class="col-md-4">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
          <input type="text" name="search" class="form-control" placeholder="Search by name, roll no, email..." value="<?= e($search) ?>">
        </div>
      </div>
      <div class="col-md-3">
        <select name="course_id" class="form-select form-select-sm">
          <option value="0">All Courses</option>
          <?php foreach ($courses as $c): ?>
            <option value="<?= (int)$c['course_id'] ?>" <?= $filter_course === (int)$c['course_id'] ? 'selected' : '' ?>>
              <?= e($c['course_code']) ?> — <?= e($c['course_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Statuses</option>
          <option value="Active" <?= $filter_status === 'Active' ? 'selected' : '' ?>>Active</option>
          <option value="Graduated" <?= $filter_status === 'Graduated' ? 'selected' : '' ?>>Graduated</option>
          <option value="Suspended" <?= $filter_status === 'Suspended' ? 'selected' : '' ?>>Suspended</option>
          <option value="Withdrawn" <?= $filter_status === 'Withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
        <a href="students.php" class="btn btn-light btn-sm border" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
      </div>
    </form>
  </div>

  <!-- Students Data Table -->
  <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
    <div class="table-responsive">
      <table class="table table-hover table-cd mb-0 align-middle">
        <thead>
          <tr>
            <th>Roll Number</th>
            <th>Full Name</th>
            <th>Course</th>
            <th>Contact Details</th>
            <th>Gender</th>
            <th>Admission Date</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($students)): ?>
            <tr>
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
                No student records matched your filter criteria.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($students as $st): ?>
              <tr>
                <td class="tabular-nums fw-bold text-primary"><?= e($st['student_code']) ?></td>
                <td>
                  <div class="fw-semibold text-dark"><?= e($st['full_name']) ?></div>
                  <div class="text-muted small"><?= e($st['address']) ?></div>
                </td>
                <td>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= e($st['course_code']) ?></span>
                </td>
                <td>
                  <div class="small"><i class="bi bi-envelope me-1 text-muted"></i><?= e($st['email']) ?></div>
                  <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= e($st['phone']) ?></div>
                </td>
                <td><?= e($st['gender']) ?></td>
                <td class="tabular-nums small"><?= format_date($st['admission_date']) ?></td>
                <td>
                  <span class="badge <?= $st['enrollment_status'] === 'Active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>">
                    <?= e($st['enrollment_status']) ?>
                  </span>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a href="student_edit.php?id=<?= (int)$st['student_id'] ?>" class="btn btn-outline-secondary" title="Edit Student">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <form method="POST" action="students.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete student <?= addslashes($st['full_name']) ?>?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="student_id" value="<?= (int)$st['student_id'] ?>">
                      <button type="submit" class="btn btn-outline-danger" title="Delete Student">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
      <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3 border-top">
        <span class="small text-muted">Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $total_records) ?> of <?= $total_records ?> students</span>
        <nav aria-label="Page navigation">
          <ul class="pagination pagination-sm mb-0">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="students.php?page=<?= $i ?>&search=<?= urlencode($search) ?>&course_id=<?= $filter_course ?>&status=<?= urlencode($filter_status) ?>">
                  <?= $i ?>
                </a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
