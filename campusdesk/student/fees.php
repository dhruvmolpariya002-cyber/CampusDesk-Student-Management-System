<?php
/**
 * CampusDesk — Student Fee Invoices & Payment Ledger
 * File: student/fees.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('student');

$page_title = 'My Fee Statements';
$current_page = 'fees';

$user_id = (int)$_SESSION['user_id'];

// Get student_id
$st = $pdo->prepare("SELECT student_id, student_code, full_name FROM students WHERE user_id = :uid");
$st->execute([':uid' => $user_id]);
$student = $st->fetch();
$student_id = (int)$student['student_id'];

// Fetch all fee invoices for this student
$fee_stmt = $pdo->prepare("SELECT * FROM fees WHERE student_id = :sid ORDER BY due_date ASC");
$fee_stmt->execute([':sid' => $student_id]);
$fee_records = $fee_stmt->fetchAll();

// Aggregates
$total_billed = 0.0;
$total_paid = 0.0;
$total_due = 0.0;
foreach ($fee_records as $f) {
    $total_billed += (float)$f['total_amount'];
    $total_paid   += (float)$f['paid_amount'];
    $total_due    += ((float)$f['total_amount'] - (float)$f['paid_amount']);
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4 py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="fw-bold text-dark mb-0">My Fee Statements</h3>
      <div class="text-secondary small">Academic tuition installments, dues, and payment transaction receipts</div>
    </div>
  </div>

  <!-- Fee Metric Cards -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-4">
      <div class="card-stat">
        <div class="stat-label">Total Billed</div>
        <div class="stat-value tabular-nums mt-1"><?= format_currency($total_billed) ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-md-4">
      <div class="card-stat">
        <div class="stat-label text-success">Total Amount Paid</div>
        <div class="stat-value tabular-nums mt-1 text-success"><?= format_currency($total_paid) ?></div>
      </div>
    </div>
    <div class="col-sm-6 col-md-4">
      <div class="card-stat">
        <div class="stat-label text-danger">Outstanding Balance</div>
        <div class="stat-value tabular-nums mt-1 <?= $total_due > 0 ? 'text-danger' : 'text-success' ?>">
          <?= format_currency($total_due) ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Fee Invoices Table -->
  <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden mb-4">
    <div class="card-header bg-white py-3">
      <h6 class="fw-bold text-dark mb-0">Institutional Fee Invoices</h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover table-cd mb-0 align-middle">
        <thead>
          <tr>
            <th>Invoice ID</th>
            <th>Description / Fee Type</th>
            <th>Billed Amount</th>
            <th>Amount Paid</th>
            <th>Balance Due</th>
            <th>Due Date</th>
            <th>Status</th>
            <th>Receipt / Txn Ref</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($fee_records)): ?>
            <tr><td colspan="8" class="text-center py-5 text-muted">No fee invoices issued for your account.</td></tr>
          <?php else: ?>
            <?php foreach ($fee_records as $fee): ?>
              <?php $bal = (float)$fee['total_amount'] - (float)$fee['paid_amount']; ?>
              <tr>
                <td class="tabular-nums fw-bold">#<?= (int)$fee['fee_id'] ?></td>
                <td>
                  <div class="fw-semibold text-dark"><?= e($fee['fee_type']) ?></div>
                </td>
                <td class="tabular-nums"><?= format_currency($fee['total_amount']) ?></td>
                <td class="tabular-nums text-success"><?= format_currency($fee['paid_amount']) ?></td>
                <td class="tabular-nums fw-bold <?= $bal > 0 ? 'text-danger' : 'text-muted' ?>">
                  <?= format_currency($bal) ?>
                </td>
                <td class="tabular-nums small"><?= format_date($fee['due_date']) ?></td>
                <td>
                  <?php if ($fee['payment_status'] === 'Paid'): ?>
                    <span class="badge bg-success-subtle text-success">Cleared (Paid)</span>
                  <?php elseif ($fee['payment_status'] === 'Partial'): ?>
                    <span class="badge bg-warning-subtle text-warning">Partially Paid</span>
                  <?php else: ?>
                    <span class="badge bg-danger-subtle text-danger">Pending Payment</span>
                  <?php endif; ?>
                </td>
                <td class="tabular-nums small text-muted">
                  <?= e($fee['reference_no'] ?? '—') ?>
                  <?php if (!empty($fee['payment_date'])): ?>
                    <div class="text-secondary" style="font-size:0.75rem;">Paid on <?= format_date($fee['payment_date']) ?></div>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="alert alert-light border small text-secondary">
    <i class="bi bi-info-circle me-1"></i> Fee payments are officially verified and entered by the Accounts Department. For payment confirmation or receipts, present your transaction reference to the college accounts desk.
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
