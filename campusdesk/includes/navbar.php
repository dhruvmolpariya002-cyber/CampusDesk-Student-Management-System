<?php
/**
 * CampusDesk — Top Navbar
 * File: includes/navbar.php
 */

declare(strict_types=1);

$user = current_user();
?>
<div id="page-content-wrapper">
  <!-- Top navigation-->
  <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom px-4 py-2 sticky-top">
    <div class="container-fluid px-0">
      <button class="btn btn-outline-secondary btn-sm me-3" id="sidebarToggle" aria-label="Toggle Navigation">
        <i class="bi bi-list fs-5"></i>
      </button>

      <span class="navbar-text fw-medium text-dark d-none d-md-inline">
        <?= e($page_title ?? 'CampusDesk Management') ?>
      </span>

      <div class="ms-auto d-flex align-items-center gap-3">
        <!-- Role Indicator -->
        <span class="badge <?= ($user['role'] ?? '') === 'admin' ? 'bg-primary' : 'bg-success' ?> px-2 py-1 text-uppercase" style="font-size: 0.725rem; letter-spacing: 0.05em;">
          <?= e($user['role'] ?? 'User') ?>
        </span>

        <!-- User Profile Dropdown -->
        <div class="dropdown">
          <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2 border" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.85rem;">
              <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
            </div>
            <span class="d-none d-sm-inline fw-semibold" style="font-size: 0.875rem;">
              <?= e($user['full_name'] ?? 'Account') ?>
            </span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userMenu">
            <li class="px-3 py-2 border-bottom">
              <div class="fw-bold text-dark"><?= e($user['full_name'] ?? '') ?></div>
              <div class="text-muted small"><?= e($user['email'] ?? '') ?></div>
            </li>
            <?php if (($user['role'] ?? '') === 'student'): ?>
              <li><a class="dropdown-item py-2" href="profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
            <?php endif; ?>
            <li><hr class="dropdown-divider my-1"></li>
            <li><a class="dropdown-item py-2 text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

  <!-- Flash Alerts -->
  <div class="container-fluid px-4 pt-3">
    <?php if ($flash_success = get_flash('success')): ?>
      <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        <div><?= e($flash_success) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <?php if ($flash_error = get_flash('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div><?= e($flash_error) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
  </div>
