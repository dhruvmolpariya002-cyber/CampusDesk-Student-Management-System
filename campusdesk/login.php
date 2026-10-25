<?php
/**
 * CampusDesk — Secure User Authentication
 * File: login.php
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already authenticated
if (is_logged_in()) {
    $role = $_SESSION['role'] ?? 'student';
    header("Location: " . ($role === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
    exit;
}

$error_message = '';
$username_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    $submitted_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($submitted_token)) {
        $error_message = "Security verification failed (Invalid CSRF token). Please try again.";
    } else {
        $username_input = trim($_POST['username'] ?? '');
        $password_input = $_POST['password'] ?? '';

        if (empty($username_input) || empty($password_input)) {
            $error_message = "Please enter both username and password.";
        } else {
            try {
                // Query user by username or email using PDO prepared statement with distinct parameter bindings
                $stmt = $pdo->prepare("
                    SELECT u.user_id, u.username, u.password_hash, u.email, u.role, u.status,
                           s.student_id, s.full_name
                    FROM users u
                    LEFT JOIN students s ON s.user_id = u.user_id
                    WHERE u.username = :login_user OR u.email = :login_email
                    LIMIT 1
                ");
                $stmt->execute([
                    ':login_user'  => $username_input,
                    ':login_email' => $username_input
                ]);
                $user = $stmt->fetch();

                if ($user) {
                    $valid_password = password_verify($password_input, $user['password_hash']);
                    // Academic Demo fallback: allow both 'password' and 'Password@123' for seeded demonstration accounts
                    if (!$valid_password && ($password_input === 'password' || $password_input === 'Password@123')) {
                        if (str_starts_with($user['password_hash'], '$2y$10$92IXUNpkjO0rOQ5byMi')) {
                            $valid_password = true;
                        }
                    }

                    if ($valid_password) {
                        if ($user['status'] !== 'active') {
                            $error_message = "Your account has been deactivated. Please contact the administrator.";
                        } else {
                            // Successful authentication: regenerate session ID and store user context
                            login_user([
                                'user_id'    => (int)$user['user_id'],
                                'username'   => $user['username'],
                                'email'      => $user['email'],
                                'role'       => $user['role'],
                                'student_id' => $user['student_id'] ? (int)$user['student_id'] : null,
                                'full_name'  => $user['full_name'] ?: $user['username']
                            ]);

                            // Route to appropriate dashboard
                            if ($user['role'] === 'admin') {
                                header("Location: admin/dashboard.php");
                            } else {
                                header("Location: student/dashboard.php");
                            }
                            exit;
                        }
                    } else {
                        $error_message = "Invalid username or password. (Hint: default password is 'password' or 'Password@123')";
                    }
                } else {
                    $error_message = "User not found. Please verify your username or import database.sql.";
                }
            } catch (PDOException $e) {
                error_log("Login Query Error: " . $e->getMessage());
                if (str_contains($e->getMessage(), "doesn't exist") || $e->getCode() === '42S02') {
                    $error_message = "Database tables are not imported yet! Please open <strong>phpMyAdmin</strong> (<a href='http://localhost/phpmyadmin/' target='_blank' class='alert-link'>http://localhost/phpmyadmin</a>) and import <code>database.sql</code> into database <code>campusdesk_db</code>.";
                } else {
                    $error_message = "Database Error: " . htmlspecialchars($e->getMessage()) . ". Check phpMyAdmin connection.";
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
  <title>Login — CampusDesk</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    body {
      background-color: #f1f5f9;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .login-card {
      max-width: 440px;
      width: 100%;
      border-radius: 12px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    }
  </style>
</head>
<body>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">
      <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-3 p-3 mb-2 shadow-sm">
          <i class="bi bi-mortarboard-fill fs-3"></i>
        </div>
        <h3 class="fw-bold text-dark mb-1">CampusDesk</h3>
        <p class="text-muted small">Sign in to your academic portal</p>
      </div>

      <div class="card login-card p-4 bg-white">
        <?php if (!empty($error_message)): ?>
          <div class="alert alert-danger d-flex align-items-center gap-2 small mb-3 py-2" role="alert">
            <i class="bi bi-exclamation-circle-fill fs-5"></i>
            <div><?= $error_message ?></div>
          </div>
        <?php endif; ?>

        <?php if ($flash_error = get_flash('error')): ?>
          <div class="alert alert-warning d-flex align-items-center gap-2 small mb-3 py-2" role="alert">
            <i class="bi bi-info-circle-fill"></i>
            <div><?= e($flash_error) ?></div>
          </div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
          <?= csrf_field() ?>

          <div class="mb-3">
            <label for="username" class="form-label small fw-semibold text-secondary">Username or Email</label>
            <div class="input-group">
              <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
              <input type="text" class="form-control" id="username" name="username" value="<?= e($username_input) ?>" placeholder="e.g. admin or bca2024001" required autofocus>
            </div>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label small fw-semibold text-secondary">Password</label>
            <div class="input-group">
              <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
              <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <div class="form-text text-muted" style="font-size: 0.75rem;">Default Demo Password: <code>password</code> or <code>Password@123</code></div>
          </div>

          <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary fw-semibold py-2">
              Sign In to Portal
            </button>
          </div>
        </form>

        <div class="mt-4 pt-3 border-top text-center">
          <span class="text-muted small fw-medium">Academic Demo Quick Fill:</span>
          <div class="d-flex justify-content-center gap-2 mt-2">
            <button type="button" class="btn btn-sm btn-outline-primary py-1 px-3" onclick="document.getElementById('username').value='admin'; document.getElementById('password').value='password';">
              <i class="bi bi-person-badge me-1"></i>Fill Admin
            </button>
            <button type="button" class="btn btn-sm btn-outline-success py-1 px-3" onclick="document.getElementById('username').value='bca2024001'; document.getElementById('password').value='password';">
              <i class="bi bi-mortarboard me-1"></i>Fill Student
            </button>
          </div>
        </div>
      </div>

      <div class="text-center mt-4">
        <a href="index.php" class="text-decoration-none text-muted small me-2"><i class="bi bi-arrow-left me-1"></i>Home</a>
        <a href="setup_admin.php" class="text-decoration-none text-muted small me-2"><i class="bi bi-shield-check me-1"></i>Setup Admin</a>
        <a href="test_db.php" class="text-decoration-none text-primary small"><i class="bi bi-database-check me-1"></i>Database Auto-Setup</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
