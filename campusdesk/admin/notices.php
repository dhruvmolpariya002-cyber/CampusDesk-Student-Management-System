<?php
/**
 * CampusDesk — Notice Board Management
 * File: admin/notices.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$page_title = 'Notice Board Management';
$current_page = 'notices';

// Handle Add Notice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_notice') {
    verify_csrf_or_die();
    $title    = trim($_POST['title'] ?? '');
    $content  = trim($_POST['content'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    $pub_date = trim($_POST['publish_date'] ?? date('Y-m-d'));
    $exp_date = trim($_POST['expiry_date'] ?? '');
    $status   = trim($_POST['status'] ?? 'Published');
    $author   = (int)($_SESSION['user_id'] ?? 1);

    if (empty($title) || empty($content)) {
        set_flash('error', 'Notice Title and Content cannot be empty.');
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO notices (title, content, category, published_by, publish_date, expiry_date, status)
                VALUES (:title, :content, :category, :author, :pub_date, :exp_date, :status)
            ");
            $stmt->execute([
                ':title'    => $title,
                ':content'  => $content,
                ':category' => in_array($category, ['Academic', 'Examination', 'Event', 'Urgent', 'General']) ? $category : 'General',
                ':author'   => $author,
                ':pub_date' => $pub_date,
                ':exp_date' => $exp_date ?: null,
                ':status'   => $status === 'Draft' ? 'Draft' : 'Published'
            ]);
            set_flash('success', "Notice '{$title}' posted successfully.");
        } catch (PDOException $e) {
            set_flash('error', 'Database error creating notice: ' . $e->getMessage());
        }
    }
    header("Location: notices.php");
    exit;
}

// Handle Delete Notice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    verify_csrf_or_die();
    $notice_id = (int)($_POST['notice_id'] ?? 0);
    try {
        $del = $pdo->prepare("DELETE FROM notices WHERE notice_id = :id");
        $del->execute([':id' => $notice_id]);
        set_flash('success', 'Notice deleted successfully.');
    } catch (PDOException $e) {
        set_flash('error', 'Error deleting notice.');
    }
    header("Location: notices.php");
    exit;
}

$notices = $pdo->query("
    SELECT n.*, u.username as author_name
    FROM notices n
    JOIN users u ON n.published_by = u.user_id
    ORDER BY n.publish_date DESC, n.notice_id DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4 py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="fw-bold text-dark mb-0">Notice Board</h3>
      <div class="text-secondary small">Broadcast academic announcements, circulars, and event bulletins</div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Add Notice Form -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
        <h6 class="fw-bold text-dark mb-3">Publish New Announcement</h6>
        <form method="POST" action="notices.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add_notice">

          <div class="mb-3">
            <label class="form-label small fw-semibold">Notice Title <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="title" placeholder="e.g. Schedule for Mid-Term Examination 2024" required>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Category</label>
              <select class="form-select" name="category">
                <option value="Academic">Academic</option>
                <option value="Examination">Examination</option>
                <option value="Event">Event</option>
                <option value="Urgent">Urgent</option>
                <option value="General" selected>General</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Status</label>
              <select class="form-select" name="status">
                <option value="Published" selected>Published</option>
                <option value="Draft">Draft</option>
              </select>
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Publish Date</label>
              <input type="date" class="form-control tabular-nums" name="publish_date" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Expiry Date (Optional)</label>
              <input type="date" class="form-control tabular-nums" name="expiry_date">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Announcement Content <span class="text-danger">*</span></label>
            <textarea class="form-control" name="content" rows="4" placeholder="Enter complete announcement text and instructions..." required></textarea>
          </div>

          <button type="submit" class="btn btn-primary w-100 fw-semibold">
            <i class="bi bi-broadcast me-1"></i>Publish Notice
          </button>
        </form>
      </div>
    </div>

    <!-- Notice List -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
        <div class="card-header bg-white py-3">
          <h6 class="fw-bold text-dark mb-0">Published Circulars (<?= count($notices) ?>)</h6>
        </div>
        <div class="list-group list-group-flush">
          <?php if (empty($notices)): ?>
            <div class="text-center py-5 text-muted">No circulars published yet.</div>
          <?php else: ?>
            <?php foreach ($notices as $not): ?>
              <div class="list-group-item p-4">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.75rem;">
                      <?= e($not['category']) ?>
                    </span>
                    <span class="badge <?= $not['status'] === 'Published' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>" style="font-size: 0.75rem;">
                      <?= e($not['status']) ?>
                    </span>
                  </div>
                  <div class="text-muted small tabular-nums">
                    <?= format_date($not['publish_date']) ?>
                  </div>
                </div>

                <h6 class="fw-bold text-dark mb-2"><?= e($not['title']) ?></h6>
                <p class="text-secondary small mb-3 lh-base"><?= nl2br(e($not['content'])) ?></p>

                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                  <div class="small text-muted">
                    <i class="bi bi-person me-1"></i><?= e($not['author_name']) ?>
                    <?php if (!empty($not['expiry_date'])): ?>
                      <span class="ms-2">· Valid until: <?= format_date($not['expiry_date']) ?></span>
                    <?php endif; ?>
                  </div>
                  <form method="POST" action="notices.php" class="d-inline" onsubmit="return confirm('Delete notice <?= addslashes($not['title']) ?>?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="notice_id" value="<?= (int)$not['notice_id'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" title="Delete Announcement">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
