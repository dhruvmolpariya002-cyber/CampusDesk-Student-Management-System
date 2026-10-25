<?php
/**
 * CampusDesk — Add New Student Form
 * File: admin/student_add.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$page_title = 'Enroll New Student';
$current_page = 'student_add';

$courses = $pdo->query("SELECT course_id, course_name, course_code FROM courses WHERE status = 'active' ORDER BY course_name ASC")->fetchAll();

$errors = [];
$form_data = [
    'student_code'      => 'BCA' . date('Y') . sprintf('%03d', rand(10, 999)),
    'course_id'         => '',
    'full_name'         => '',
    'email'             => '',
    'phone'             => '',
    'dob'               => '2004-01-01',
    'gender'            => 'Male',
    'address'           => '',
    'admission_date'    => date('Y-m-d'),
    'enrollment_status' => 'Active',
    'create_user'       => '1',
    'student_password'  => 'Password@123'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    // Populate submitted data
    foreach ($form_data as $key => $default) {
        if (isset($_POST[$key])) {
            $form_data[$key] = trim((string)$_POST[$key]);
        }
    }

    // Validation
    if (empty($form_data['student_code'])) {
        $errors[] = "Student Roll Number is required.";
    }
    if (empty($form_data['full_name'])) {
        $errors[] = "Full Name is required.";
    }
    if (empty($form_data['email']) || !filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if (empty($form_data['phone'])) {
        $errors[] = "Phone number is required.";
    }
    if (empty($form_data['course_id'])) {
        $errors[] = "Please select an enrolled course.";
    }
    if (empty($form_data['dob'])) {
        $errors[] = "Date of birth is required.";
    }
    if (empty($form_data['admission_date'])) {
        $errors[] = "Admission date is required.";
    }

    // Check unique student_code and email
    if (empty($errors)) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM students WHERE student_code = :code OR email = :email");
        $chk->execute([':code' => $form_data['student_code'], ':email' => $form_data['email']]);
        if ((int)$chk->fetchColumn() > 0) {
            $errors[] = "A student with this Roll Number or Email already exists in the system.";
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $linked_user_id = null;

            // Optionally create login account for student
            if (!empty($_POST['create_user'])) {
                $username = strtolower($form_data['student_code']);
                $pass_hash = password_hash($form_data['student_password'] ?: 'Password@123', PASSWORD_BCRYPT);

                // Ensure username doesn't conflict
                $u_stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :u OR email = :e");
                $u_stmt->execute([':u' => $username, ':e' => $form_data['email']]);
                if ($u_stmt->fetch()) {
                    // Update username if exists
                    $username = $username . '_' . rand(10, 99);
                }

                $user_insert = $pdo->prepare("
                    INSERT INTO users (username, password_hash, email, role, status)
                    VALUES (:username, :hash, :email, 'student', 'active')
                ");
                $user_insert->execute([
                    ':username' => $username,
                    ':hash'     => $pass_hash,
                    ':email'    => $form_data['email']
                ]);
                $linked_user_id = (int)$pdo->lastInsertId();
            }

            // Insert Student Record
            $st_insert = $pdo->prepare("
                INSERT INTO students (
                    student_code, user_id, course_id, full_name, email, phone, dob, gender, address, admission_date, enrollment_status
                ) VALUES (
                    :student_code, :user_id, :course_id, :full_name, :email, :phone, :dob, :gender, :address, :admission_date, :enrollment_status
                )
            ");
            $st_insert->execute([
                ':student_code'      => $form_data['student_code'],
                ':user_id'           => $linked_user_id,
                ':course_id'         => (int)$form_data['course_id'],
                ':full_name'         => $form_data['full_name'],
                ':email'             => $form_data['email'],
                ':phone'             => $form_data['phone'],
                ':dob'               => $form_data['dob'],
                ':gender'            => $form_data['gender'],
                ':address'           => $form_data['address'],
                ':admission_date'    => $form_data['admission_date'],
                ':enrollment_status' => $form_data['enrollment_status']
            ]);

            $pdo->commit();
            set_flash('success', "Student '{$form_data['full_name']}' [{$form_data['student_code']}] enrolled successfully.");
            header("Location: students.php");
            exit;

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Student registration error: " . $e->getMessage());
            $errors[] = "Database insertion error: " . $e->getMessage();
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
      <h3 class="fw-bold text-dark mb-0">Enroll New Student</h3>
      <div class="text-secondary small">Academic admission record and portal user generation</div>
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
    <form method="POST" action="student_add.php" novalidate>
      <?= csrf_field() ?>

      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label for="student_code" class="form-label small fw-semibold text-secondary">Student Roll / Enrollment No <span class="text-danger">*</span></label>
          <input type="text" class="form-control tabular-nums" id="student_code" name="student_code" value="<?= e($form_data['student_code']) ?>" required>
        </div>
        <div class="col-md-4">
          <label for="course_id" class="form-label small fw-semibold text-secondary">Enrolled Course <span class="text-danger">*</span></label>
          <select class="form-select" id="course_id" name="course_id" required>
            <option value="">-- Choose Course --</option>
            <?php foreach ($courses as $c): ?>
              <option value="<?= (int)$c['course_id'] ?>" <?= $form_data['course_id'] == $c['course_id'] ? 'selected' : '' ?>>
                <?= e($c['course_code']) ?> — <?= e($c['course_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label for="enrollment_status" class="form-label small fw-semibold text-secondary">Enrollment Status</label>
          <select class="form-select" id="enrollment_status" name="enrollment_status">
            <option value="Active" <?= $form_data['enrollment_status'] === 'Active' ? 'selected' : '' ?>>Active</option>
            <option value="Graduated" <?= $form_data['enrollment_status'] === 'Graduated' ? 'selected' : '' ?>>Graduated</option>
            <option value="Suspended" <?= $form_data['enrollment_status'] === 'Suspended' ? 'selected' : '' ?>>Suspended</option>
            <option value="Withdrawn" <?= $form_data['enrollment_status'] === 'Withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
          </select>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label for="full_name" class="form-label small fw-semibold text-secondary">Full Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($form_data['full_name']) ?>" placeholder="e.g. Priya Nair" required>
        </div>
        <div class="col-md-6">
          <label for="email" class="form-label small fw-semibold text-secondary">Email Address <span class="text-danger">*</span></label>
          <input type="email" class="form-control" id="email" name="email" value="<?= e($form_data['email']) ?>" placeholder="e.g. priya.nair@college.edu" required>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label for="phone" class="form-label small fw-semibold text-secondary">Phone Number <span class="text-danger">*</span></label>
          <input type="text" class="form-control tabular-nums" id="phone" name="phone" value="<?= e($form_data['phone']) ?>" placeholder="e.g. 9876543210" required>
        </div>
        <div class="col-md-4">
          <label for="dob" class="form-label small fw-semibold text-secondary">Date of Birth <span class="text-danger">*</span></label>
          <input type="date" class="form-control tabular-nums" id="dob" name="dob" value="<?= e($form_data['dob']) ?>" required>
        </div>
        <div class="col-md-4">
          <label for="gender" class="form-label small fw-semibold text-secondary">Gender</label>
          <select class="form-select" id="gender" name="gender">
            <option value="Male" <?= $form_data['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= $form_data['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
            <option value="Other" <?= $form_data['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
          </select>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-8">
          <label for="address" class="form-label small fw-semibold text-secondary">Residential Address</label>
          <input type="text" class="form-control" id="address" name="address" value="<?= e($form_data['address']) ?>" placeholder="City, State, Postal Code">
        </div>
        <div class="col-md-4">
          <label for="admission_date" class="form-label small fw-semibold text-secondary">Admission Date <span class="text-danger">*</span></label>
          <input type="date" class="form-control tabular-nums" id="admission_date" name="admission_date" value="<?= e($form_data['admission_date']) ?>" required>
        </div>
      </div>

      <!-- User Account Creation Checkbox -->
      <div class="border rounded p-3 bg-light mt-4 mb-4">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="create_user" name="create_user" value="1" <?= $form_data['create_user'] ? 'checked' : '' ?>>
          <label class="form-check-label fw-semibold text-dark" for="create_user">
            Automatically create linked student portal login account
          </label>
        </div>
        <div class="text-muted small mt-1">
          Username will be set to student's roll number (lowercased). Default password will be set below:
        </div>
        <div class="row mt-2">
          <div class="col-md-6">
            <input type="text" class="form-control form-control-sm" name="student_password" value="<?= e($form_data['student_password']) ?>" placeholder="Initial login password">
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2">
        <a href="students.php" class="btn btn-light border px-4">Cancel</a>
        <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="bi bi-check-lg me-1"></i>Save Student Record</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
