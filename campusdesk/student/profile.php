<?php
/**
 * CampusDesk — Student Profile & Password Change
 * File: student/profile.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('student');

$page_title = 'My Student Profile';
$current_page = 'profile';

$user_id = (int)$_SESSION['user_id'];

// Fetch Student Profile
$stmt = $pdo->prepare("
    SELECT s.*, c.course_name, c.course_code, d.dept_name, u.username
    FROM students s
    JOIN courses c ON s.course_id = c.course_id
    JOIN departments d ON c.dept_id = d.dept_id
    JOIN users u ON s.user_id = u.user_id
    WHERE s.user_id = :uid
");
$stmt->execute([':uid' => $user_id]);
$student = $stmt->fetch();

$pass_error = '';
$pass_success = '';

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    verify_csrf_or_die();
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass     = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
        $pass_error = 'All password fields are required.';
    } elseif (strlen($new_pass) < 8) {
        $pass_error = 'New password must be at least 8 characters long.';
    } elseif ($new_pass !== $confirm_pass) {
        $pass_error = 'New password and confirmation do not match.';
    } else {
        // Verify current password
        $u_stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = :uid");
        $u_stmt->execute([':uid' => $user_id]);
        $curr_hash = $u_stmt->fetchColumn();

        if ($curr_hash && password_verify($current_pass, $curr_hash)) {
            $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
            $up = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE user_id = :uid");
            $up->execute([':hash' => $new_hash, ':uid' => $user_id]);
            $pass_success = 'Password changed successfully.';
        } else {
            $pass_error = 'Current password entered is incorrect.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4 py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="fw-bold text-dark mb-0">My Academic Profile</h3>
      <div class="text-secondary small">Personal institutional information and portal credentials</div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Student Information Summary -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white mb-4">
        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">Academic &amp; Personal Details</h6>

        <div class="row g-3">
          <div class="col-sm-6">
            <div class="text-muted small">Roll / Enrollment Number</div>
            <div class="fw-bold text-primary tabular-nums fs-5"><?= e($student['student_code']) ?></div>
          </div>
          <div class="col-sm-6">
            <div class="text-muted small">Enrollment Status</div>
            <div>
              <span class="badge bg-success-subtle text-success"><?= e($student['enrollment_status']) ?></span>
            </div>
          </div>

          <div class="col-sm-6">
            <div class="text-muted small">Full Name</div>
            <div class="fw-semibold text-dark"><?= e($student['full_name']) ?></div>
          </div>
          <div class="col-sm-6">
            <div class="text-muted small">Login Username</div>
            <div class="fw-semibold text-dark"><?= e($student['username']) ?></div>
          </div>

          <div class="col-sm-6">
            <div class="text-muted small">Course Program</div>
            <div class="fw-semibold"><?= e($student['course_name']) ?> (<?= e($student['course_code']) ?>)</div>
          </div>
          <div class="col-sm-6">
            <div class="text-muted small">Department</div>
            <div class="fw-semibold"><?= e($student['dept_name']) ?></div>
          </div>

          <div class="col-sm-6">
            <div class="text-muted small">Registered Email</div>
            <div><?= e($student['email']) ?></div>
          </div>
          <div class="col-sm-6">
            <div class="text-muted small">Contact Phone</div>
            <div class="tabular-nums"><?= e($student['phone']) ?></div>
          </div>

          <div class="col-sm-6">
            <div class="text-muted small">Date of Birth</div>
            <div class="tabular-nums"><?= format_date($student['dob']) ?></div>
          </div>
          <div class="col-sm-6">
            <div class="text-muted small">Gender</div>
            <div><?= e($student['gender']) ?></div>
          </div>

          <div class="col-12">
            <div class="text-muted small">Residential Address</div>
            <div><?= e($student['address']) ?></div>
          </div>

          <div class="col-sm-6">
            <div class="text-muted small">Admission Date</div>
            <div class="tabular-nums"><?= format_date($student['admission_date']) ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Password Change Form -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">Change Portal Password</h6>

        <?php if (!empty($pass_error)): ?>
          <div class="alert alert-danger py-2 small" role="alert">
            <i class="bi bi-exclamation-circle me-1"></i><?= e($pass_error) ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($pass_success)): ?>
          <div class="alert alert-success py-2 small" role="alert">
            <i class="bi bi-check-circle me-1"></i><?= e($pass_success) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="profile.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="change_password">

          <div class="mb-3">
            <label class="form-label small fw-semibold">Current Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" name="current_password" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">New Password (min 8 characters) <span class="text-danger">*</span></label>
            <input type="password" class="form-control" name="new_password" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" name="confirm_password" required>
          </div>

          <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">
            Update Password
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
