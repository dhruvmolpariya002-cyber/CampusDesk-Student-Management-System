<?php
/**
 * CampusDesk — Edit Student Details
 * File: admin/student_edit.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$page_title = 'Edit Student Record';
$current_page = 'student_edit';

$student_id = (int)($_GET['id'] ?? 0);
if ($student_id <= 0) {
    set_flash('error', 'Invalid student identifier provided.');
    header("Location: students.php");
    exit;
}

// Fetch Existing Student Record
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = :id");
$stmt->execute([':id' => $student_id]);
$student = $stmt->fetch();

if (!$student) {
    set_flash('error', 'Student record not found.');
    header("Location: students.php");
    exit;
}

$courses = $pdo->query("SELECT course_id, course_name, course_code FROM courses WHERE status = 'active' ORDER BY course_name ASC")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    $student_code      = trim($_POST['student_code'] ?? '');
    $course_id         = (int)($_POST['course_id'] ?? 0);
    $full_name         = trim($_POST['full_name'] ?? '');
    $email             = trim($_POST['email'] ?? '');
    $phone             = trim($_POST['phone'] ?? '');
    $dob               = trim($_POST['dob'] ?? '');
    $gender            = trim($_POST['gender'] ?? 'Male');
    $address           = trim($_POST['address'] ?? '');
    $admission_date    = trim($_POST['admission_date'] ?? '');
    $enrollment_status = trim($_POST['enrollment_status'] ?? 'Active');

    if (empty($student_code)) $errors[] = "Student Roll Number is required.";
    if (empty($full_name)) $errors[] = "Full Name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($phone)) $errors[] = "Phone number is required.";
    if ($course_id <= 0) $errors[] = "Please select a valid course.";
    if (empty($dob)) $errors[] = "Date of birth is required.";
    if (empty($admission_date)) $errors[] = "Admission date is required.";

    // Check unique code & email excluding this student
    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM students WHERE (student_code = :code OR email = :email) AND student_id != :id");
        $chk->execute([':code' => $student_code, ':email' => $email, ':id' => $student_id]);
        if ((int)$chk->fetchColumn() > 0) {
            $errors[] = "Another student is already registered with this Roll Number or Email.";
        }
    }

    if (empty($errors)) {
        try {
            $update_stmt = $pdo->prepare("
                UPDATE students SET
                    student_code = :student_code,
                    course_id = :course_id,
                    full_name = :full_name,
                    email = :email,
                    phone = :phone,
                    dob = :dob,
                    gender = :gender,
                    address = :address,
                    admission_date = :admission_date,
                    enrollment_status = :enrollment_status
                WHERE student_id = :id
            ");
            $update_stmt->execute([
                ':student_code'      => $student_code,
                ':course_id'         => $course_id,
                ':full_name'         => $full_name,
                ':email'             => $email,
                ':phone'             => $phone,
                ':dob'               => $dob,
                ':gender'            => $gender,
                ':address'           => $address,
                ':admission_date'    => $admission_date,
                ':enrollment_status' => $enrollment_status,
                ':id'                => $student_id
            ]);

            // Sync user account email if linked
            if (!empty($student['user_id'])) {
                $user_up = $pdo->prepare("UPDATE users SET email = :email WHERE user_id = :uid");
                $user_up->execute([':email' => $email, ':uid' => $student['user_id']]);
            }

            set_flash('success', "Student '{$full_name}' was successfully updated.");
            header("Location: students.php");
            exit;
        } catch (PDOException $e) {
            error_log("Update student error: " . $e->getMessage());
            $errors[] = "Database update error: " . $e->getMessage();
        }
    }

    // Refresh state with submitted values on error
    $student['student_code'] = $student_code;
    $student['course_id'] = $course_id;
    $student['full_name'] = $full_name;
    $student['email'] = $email;
    $student['phone'] = $phone;
    $student['dob'] = $dob;
    $student['gender'] = $gender;
    $student['address'] = $address;
    $student['admission_date'] = $admission_date;
    $student['enrollment_status'] = $enrollment_status;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4 py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="fw-bold text-dark mb-0">Edit Student Record</h3>
      <div class="text-secondary small">Update enrollment profile for <?= e($student['full_name']) ?></div>
    </div>
    <a href="students.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Directory</a>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger mb-4">
      <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please correct the highlighted errors:</div>
      <ul class="mb-0 ps-3">
        <?php foreach ($errors as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
    <form method="POST" action="student_edit.php?id=<?= $student_id ?>" novalidate>
      <?= csrf_field() ?>

      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label for="student_code" class="form-label small fw-semibold text-secondary">Student Roll / Enrollment No <span class="text-danger">*</span></label>
          <input type="text" class="form-control tabular-nums" id="student_code" name="student_code" value="<?= e($student['student_code']) ?>" required>
        </div>
        <div class="col-md-4">
          <label for="course_id" class="form-label small fw-semibold text-secondary">Course <span class="text-danger">*</span></label>
          <select class="form-select" id="course_id" name="course_id" required>
            <?php foreach ($courses as $c): ?>
              <option value="<?= (int)$c['course_id'] ?>" <?= (int)$student['course_id'] === (int)$c['course_id'] ? 'selected' : '' ?>>
                <?= e($c['course_code']) ?> — <?= e($c['course_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label for="enrollment_status" class="form-label small fw-semibold text-secondary">Enrollment Status</label>
          <select class="form-select" id="enrollment_status" name="enrollment_status">
            <option value="Active" <?= $student['enrollment_status'] === 'Active' ? 'selected' : '' ?>>Active</option>
            <option value="Graduated" <?= $student['enrollment_status'] === 'Graduated' ? 'selected' : '' ?>>Graduated</option>
            <option value="Suspended" <?= $student['enrollment_status'] === 'Suspended' ? 'selected' : '' ?>>Suspended</option>
            <option value="Withdrawn" <?= $student['enrollment_status'] === 'Withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
          </select>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label for="full_name" class="form-label small fw-semibold text-secondary">Full Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($student['full_name']) ?>" required>
        </div>
        <div class="col-md-6">
          <label for="email" class="form-label small fw-semibold text-secondary">Email Address <span class="text-danger">*</span></label>
          <input type="email" class="form-control" id="email" name="email" value="<?= e($student['email']) ?>" required>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label for="phone" class="form-label small fw-semibold text-secondary">Phone Number <span class="text-danger">*</span></label>
          <input type="text" class="form-control tabular-nums" id="phone" name="phone" value="<?= e($student['phone']) ?>" required>
        </div>
        <div class="col-md-4">
          <label for="dob" class="form-label small fw-semibold text-secondary">Date of Birth <span class="text-danger">*</span></label>
          <input type="date" class="form-control tabular-nums" id="dob" name="dob" value="<?= e($student['dob']) ?>" required>
        </div>
        <div class="col-md-4">
          <label for="gender" class="form-label small fw-semibold text-secondary">Gender</label>
          <select class="form-select" id="gender" name="gender">
            <option value="Male" <?= $student['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= $student['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
            <option value="Other" <?= $student['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
          </select>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-8">
          <label for="address" class="form-label small fw-semibold text-secondary">Residential Address</label>
          <input type="text" class="form-control" id="address" name="address" value="<?= e($student['address']) ?>">
        </div>
        <div class="col-md-4">
          <label for="admission_date" class="form-label small fw-semibold text-secondary">Admission Date <span class="text-danger">*</span></label>
          <input type="date" class="form-control tabular-nums" id="admission_date" name="admission_date" value="<?= e($student['admission_date']) ?>" required>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2">
        <a href="students.php" class="btn btn-light border px-4">Cancel</a>
        <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="bi bi-save me-1"></i>Update Changes</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
