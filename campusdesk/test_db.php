<?php
/**
 * CampusDesk — Database Health Check & 1-Click Auto-Setup
 * File: test_db.php
 * Use this file to test MySQL connection, diagnose errors, or auto-import database.sql
 */

declare(strict_types=1);

$db_host = 'localhost';
$db_port = '3306';
$db_name = 'campusdesk_db';
$db_user = 'root';
$db_pass = '';

$action_message = '';
$action_status = '';

// Handle 1-Click Auto Import Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'auto_import') {
    try {
        // Connect to MySQL server without database first
        $pdo_raw = new PDO("mysql:host={$db_host};port={$db_port};charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Create database if not exists
        $pdo_raw->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo_raw->exec("USE `{$db_name}`");

        // Read database.sql file
        $sql_file = __DIR__ . '/database.sql';
        if (!file_exists($sql_file)) {
            throw new Exception("database.sql file not found at " . $sql_file);
        }

        $sql_content = file_get_contents($sql_file);

        // Execute queries
        $pdo_raw->exec($sql_content);

        $action_message = "Success! Database 'campusdesk_db' and all 10 tables were created and populated with demo data!";
        $action_status = 'success';
    } catch (Exception $e) {
        $action_message = "Auto-import failed: " . $e->getMessage();
        $action_status = 'danger';
    }
}

// Perform Diagnostics
$mysql_connected = false;
$db_exists = false;
$tables_found = [];
$users_count = 0;
$admin_found = false;
$diagnostic_error = '';

try {
    // 1. Check MySQL connection
    $pdo_test = new PDO("mysql:host={$db_host};port={$db_port};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $mysql_connected = true;

    // 2. Check if database exists
    $stmt = $pdo_test->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$db_name}'");
    if ($stmt->fetch()) {
        $db_exists = true;

        // Switch to database
        $pdo_test->exec("USE `{$db_name}`");

        // 3. Check tables
        $tables_stmt = $pdo_test->query("SHOW TABLES");
        $tables_found = $tables_stmt->fetchAll(PDO::FETCH_COLUMN);

        // 4. Check users table
        if (in_array('users', $tables_found)) {
            $user_stmt = $pdo_test->query("SELECT COUNT(*) FROM `users`");
            $users_count = (int)$user_stmt->fetchColumn();

            $admin_stmt = $pdo_test->query("SELECT username, email, role FROM `users` WHERE role = 'admin' LIMIT 1");
            $admin_row = $admin_stmt->fetch(PDO::FETCH_ASSOC);
            if ($admin_row) {
                $admin_found = true;
            }
        }
    }
} catch (PDOException $e) {
    $diagnostic_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Database Health & Diagnostic — CampusDesk</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; color: #1e293b; }
    .status-badge { font-size: 0.85rem; padding: 0.35rem 0.65rem; border-radius: 9999px; }
  </style>
</head>
<body class="py-5">

<div class="container" style="max-width: 760px;">
  <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white mb-4">
    <div class="p-4 bg-primary text-white d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-3">
        <i class="bi bi-database-gear fs-1"></i>
        <div>
          <h4 class="fw-bold mb-0">CampusDesk Database Diagnostics</h4>
          <p class="mb-0 text-white-50 small">Automatic XAMPP MySQL health check and 1-click installer</p>
        </div>
      </div>
      <a href="login.php" class="btn btn-light btn-sm fw-semibold">
        <i class="bi bi-box-arrow-in-right me-1"></i>Go to Login
      </a>
    </div>

    <div class="card-body p-4">
      <?php if (!empty($action_message)): ?>
        <div class="alert alert-<?= $action_status ?> d-flex align-items-center gap-2 mb-4" role="alert">
          <i class="bi <?= $action_status === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> fs-5"></i>
          <div><?= htmlspecialchars($action_message) ?></div>
        </div>
      <?php endif; ?>

      <?php if (!empty($diagnostic_error)): ?>
        <div class="alert alert-danger mb-4">
          <h6 class="fw-bold"><i class="bi bi-exclamation-octagon-fill me-2"></i>MySQL Connection Error:</h6>
          <code><?= htmlspecialchars($diagnostic_error) ?></code>
          <hr>
          <p class="small mb-0">
            <strong>Solution:</strong> Open XAMPP Control Panel and make sure <strong>MySQL</strong> has been Started (showing green).
          </p>
        </div>
      <?php endif; ?>

      <h6 class="fw-bold text-uppercase text-secondary small mb-3">Diagnostic Checklist</h6>

      <ul class="list-group list-group-flush border rounded-3 mb-4">
        <!-- 1. MySQL Server -->
        <li class="list-group-item d-flex align-items-center justify-content-between py-3">
          <div class="d-flex align-items-center gap-2">
            <i class="bi <?= $mysql_connected ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?> fs-5"></i>
            <div>
              <div class="fw-semibold">MySQL Service Status</div>
              <div class="text-muted small">Connecting to <?= htmlspecialchars($db_host) ?>:<?= htmlspecialchars($db_port) ?> as '<?= htmlspecialchars($db_user) ?>'</div>
            </div>
          </div>
          <span class="badge <?= $mysql_connected ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> status-badge">
            <?= $mysql_connected ? 'Running & Connected' : 'Disconnected / Stopped' ?>
          </span>
        </li>

        <!-- 2. Database Existence -->
        <li class="list-group-item d-flex align-items-center justify-content-between py-3">
          <div class="d-flex align-items-center gap-2">
            <i class="bi <?= $db_exists ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?> fs-5"></i>
            <div>
              <div class="fw-semibold">Database '<?= htmlspecialchars($db_name) ?>'</div>
              <div class="text-muted small">Catalog schema inside MySQL server</div>
            </div>
          </div>
          <span class="badge <?= $db_exists ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' ?> status-badge">
            <?= $db_exists ? 'Database Found' : 'Not Found' ?>
          </span>
        </li>

        <!-- 3. Tables Count -->
        <li class="list-group-item d-flex align-items-center justify-content-between py-3">
          <div class="d-flex align-items-center gap-2">
            <i class="bi <?= count($tables_found) >= 10 ? 'bi-check-circle-fill text-success' : 'bi-exclamation-circle-fill text-warning' ?> fs-5"></i>
            <div>
              <div class="fw-semibold">Database Tables (<?= count($tables_found) ?>/10 Created)</div>
              <div class="text-muted small">
                <?php if (empty($tables_found)): ?>
                  No tables imported yet (Database is empty)
                <?php else: ?>
                  Found: <?= htmlspecialchars(implode(', ', $tables_found)) ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <span class="badge <?= count($tables_found) >= 10 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> status-badge">
            <?= count($tables_found) ?> Tables
          </span>
        </li>

        <!-- 4. Admin Account -->
        <li class="list-group-item d-flex align-items-center justify-content-between py-3">
          <div class="d-flex align-items-center gap-2">
            <i class="bi <?= $admin_found ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?> fs-5"></i>
            <div>
              <div class="fw-semibold">Administrator User Record</div>
              <div class="text-muted small">Users table row with role = 'admin' (Total users: <?= $users_count ?>)</div>
            </div>
          </div>
          <span class="badge <?= $admin_found ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> status-badge">
            <?= $admin_found ? 'Admin Ready' : 'Admin Missing' ?>
          </span>
        </li>
      </ul>

      <!-- Action Panel -->
      <div class="card bg-light border-0 p-3 rounded-3 mb-3">
        <h6 class="fw-bold mb-2"><i class="bi bi-magic me-2 text-primary"></i>1-Click Database Auto-Setup</h6>
        <p class="small text-muted mb-3">
          If phpMyAdmin is giving you trouble, click the button below. This script will automatically create the database <code>campusdesk_db</code>, import all 10 tables, and insert all student/admin demo records in 2 seconds.
        </p>

        <form method="POST" action="test_db.php">
          <input type="hidden" name="action" value="auto_import">
          <button type="submit" class="btn btn-primary fw-semibold w-100 py-2">
            <i class="bi bi-cloud-arrow-down-fill me-2"></i>Auto-Import / Repair Database Now (1-Click)
          </button>
        </form>
      </div>

      <?php if ($admin_found && count($tables_found) >= 10): ?>
        <div class="alert alert-success d-flex align-items-center justify-content-between">
          <div>
            <div class="fw-bold"><i class="bi bi-patch-check-fill me-2"></i>Database is 100% Ready!</div>
            <div class="small">Username: <code>admin</code> | Password: <code>password</code> (or <code>Password@123</code>)</div>
          </div>
          <a href="login.php" class="btn btn-success fw-semibold">
            Login Now <i class="bi bi-arrow-right ms-1"></i>
          </a>
        </div>
      <?php endif; ?>

    </div>
    <div class="card-footer bg-light border-top px-4 py-3 d-flex justify-content-between align-items-center">
      <span class="small text-muted">CampusDesk BCA Project — PHP 8.2 & MySQL</span>
      <a href="login.php" class="small text-decoration-none">Return to Login &rarr;</a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
