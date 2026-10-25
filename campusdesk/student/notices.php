<?php
/**
 * CampusDesk — Student Campus Notices & Announcements
 * File: student/notices.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('student');

$page_title = 'Campus Notices & Announcements';
$current_page = 'notices';

$category = trim($_GET['category'] ?? '');

$where = ["status = 'Published'", "(expiry_date IS NULL OR expiry_date >= CURRENT_DATE())"];
$params = [];

if (!empty($category)) {
    $where[] = "category = :cat";
    $params[':cat'] = $category;
}

$where_sql = 'WHERE ' . implode(' AND ', $where);

$stmt = $pdo->prepare("
    SELECT n.*, u.username as publisher
    FROM notices n
    JOIN users u ON n.published_by = u.user_id
    {$where_sql}
    ORDER BY n.publish_date DESC, n.notice_id DESC
");
$stmt->execute($params);
$notices = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4 py-3">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h3 class="fw-bold text-dark mb-0">Campus Notice Board</h3>
      <div class="text-secondary small">Official departmental circulars, schedules, and college events</div>
    </div>
    <!-- Category Filter -->
    <div class="btn-group btn-group-sm">
      <a href="notices.php" class="btn <?= empty($category) ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
      <a href="notices.php?category=Academic" class="btn <?= $category === 'Academic' ? 'btn-primary' : 'btn-outline-secondary' ?>">Academic</a>
      <a href="notices.php?category=Examination" class="btn <?= $category === 'Examination' ? 'btn-primary' : 'btn-outline-secondary' ?>">Examination</a>
      <a href="notices.php?category=Event" class="btn <?= $category === 'Event' ? 'btn-primary' : 'btn-outline-secondary' ?>">Event</a>
      <a href="notices.php?category=Urgent" class="btn <?= $category === 'Urgent' ? 'btn-primary' : 'btn-outline-secondary' ?>">Urgent</a>
    </div>
  </div>

  <div class="row g-4">
    <?php if (empty($notices)): ?>
      <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3 p-5 text-center bg-white">
          <i class="bi bi-bell-slash fs-1 text-secondary mb-3"></i>
          <h5 class="fw-bold">No Active Announcements Found</h5>
          <p class="text-muted small">Check back later for new notices from the department.</p>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($notices as $not): ?>
        <div class="col-12">
          <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
            <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                  <?= e($not['category']) ?>
                </span>
                <span class="text-muted small tabular-nums">
                  <i class="bi bi-calendar3 me-1"></i><?= format_date($not['publish_date']) ?>
                </span>
              </div>
              <?php if (!empty($not['expiry_date'])): ?>
                <span class="small text-muted tabular-nums">Valid until: <?= format_date($not['expiry_date']) ?></span>
              <?php endif; ?>
            </div>

            <h5 class="fw-bold text-dark mb-2"><?= e($not['title']) ?></h5>
            <div class="text-secondary mb-3 lh-base" style="font-size: 0.95rem;">
              <?= nl2br(e($not['content'])) ?>
            </div>

            <div class="text-muted small pt-2 border-top">
              <i class="bi bi-person-fill me-1"></i>Issued by Academic Administration Office
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
