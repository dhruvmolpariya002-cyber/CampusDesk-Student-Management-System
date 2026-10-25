<?php
/**
 * CampusDesk — Safe First-Run Administrator Setup Wizard
 * File: setup_admin.php
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

$success = '';
$error = '';

// Check if an active administrator account already exists
$admin_exists = false;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    if ((int)$stmt->fetchColumn() > 0) {
        $admin_exists = true;
    }
} catch (PDOException $e) {
    $error = "Database error checking administrator table. Please ensure database.sql has been imported.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($token)) {
        $error = "CSRF verification failed. Please try again.";
    } elseif ($admin_exists) {
        $error = "An administrator account is already initialized. For security reasons, please login or manage administrators via database.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            $error = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid administrator email address.";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long for security compliance.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            try {
                // Generate secure bcrypt hash
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

                $insert_stmt = $pdo->prepare("
                    INSERT INTO users (username, password_hash, email, role, status)
                    VALUES (:username, :hash, :email, 'admin', 'active')
                ");
                $insert_stmt->execute([
                    ':username' => $username,
                    ':hash'     => $hash,
                    ':email'    => $email
                ]);

                $success = "Administrator account '{$username}' created successfully! You may now sign in.";
                $admin_exists = true;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $error = "A user with this username or email already exists.";
                } else {
                    $error = "Failed to create administrator account. Error: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Setup Administrator — CampusDesk</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
      <div class="card p-4 shadow-sm border rounded-3 bg-white">
        <div class="d-flex align-items-center gap-2 mb-3">
          <i class="bi bi-shield-lock-fill text-primary fs-3"></i>
          <div>
            <h4 class="fw-bold mb-0">Administrator Setup Wizard</h4>
            <div class="text-muted small">CampusDesk Safe First-Run Provisioning</div>
          </div>
        </div>

        <?php if (!empty($success)): ?>
          <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <div><?= e($success) ?></div>
          </div>
          <a href="login.php" class="btn btn-primary w-100 py-2">Go to Login Page</a>
        <?php else: ?>

          <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 small" role="alert">
              <i class="bi bi-exclamation-triangle-fill"></i>
              <div><?= e($error) ?></div>
            </div>
          <?php endif; ?>

          <?php if ($admin_exists): ?>
            <div class="alert alert-info small" role="alert">
              <i class="bi bi-info-circle-fill me-1"></i>
              An active administrator is already configured in the database.
            </div>
            <p class="text-secondary small">
              If you imported <code>database.sql</code> with sample data, the default administrator is <code>admin</code> with password <code>Password@123</code>.
            </p>
            <div class="d-flex gap-2">
              <a href="login.php" class="btn btn-primary flex-grow-1">Proceed to Login</a>
              <a href="index.php" class="btn btn-outline-secondary">Back to Home</a>
            </div>
          <?php else: ?>
            <form method="POST" action="setup_admin.php" novalidate>
              <?= csrf_field() ?>

              <div class="mb-3">
                <label for="username" class="form-label small fw-semibold">Admin Username</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="e.g. admin" required>
              </div>

              <div class="mb-3">
                <label for="email" class="form-label small fw-semibold">Admin Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="e.g. admin@college.edu" required>
              </div>

              <div class="mb-3">
                <label for="password" class="form-label small fw-semibold">Password (min 8 characters)</label>
                <input type="password" class="form-control" id="password" name="password" required>
              </div>

              <div class="mb-3">
                <label for="confirm_password" class="form-label small fw-semibold">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
              </div>

              <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                Initialize Master Administrator
              </button>
            </form>
          <?php endif; ?>

        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
