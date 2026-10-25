<?php
/**
 * CampusDesk — Fee Management & Ledgers
 * File: admin/fees.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$page_title = 'Fee Management & Ledgers';
$current_page = 'fees';

// Handle Add Fee Record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_fee') {
    verify_csrf_or_die();
    $student_id   = (int)($_POST['student_id'] ?? 0);
    $fee_type     = trim($_POST['fee_type'] ?? 'Semester Tuition Fee');
    $total_amount = (float)($_POST['total_amount'] ?? 0.0);
    $paid_amount  = (float)($_POST['paid_amount'] ?? 0.0);
    $due_date     = trim($_POST['due_date'] ?? date('Y-m-d'));
    $ref_no       = trim($_POST['reference_no'] ?? '');

    // Validation
    if ($student_id <= 0 || empty($fee_type) || empty($due_date)) {
        set_flash('error', 'Student, Fee Type, and Due Date are required.');
    } elseif ($total_amount <= 0) {
        set_flash('error', 'Total fee amount must be greater than zero.');
    } elseif ($paid_amount < 0) {
        set_flash('error', 'Paid amount cannot be negative.');
    } elseif ($paid_amount > $total_amount) {
        set_flash('error', 'Paid amount cannot exceed total fee amount.');
    } else {
        // Determine payment status
        if ($paid_amount >= $total_amount) {
            $payment_status = 'Paid';
            $payment_date = date('Y-m-d');
        } elseif ($paid_amount > 0) {
            $payment_status = 'Partial';
            $payment_date = date('Y-m-d');
        } else {
            $payment_status = 'Pending';
            $payment_date = null;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO fees (student_id, fee_type, total_amount, paid_amount, due_date, payment_status, payment_date, reference_no)
                VALUES (:sid, :type, :total, :paid, :due, :status, :pdate, :ref)
            ");
            $stmt->execute([
                ':sid'    => $student_id,
                ':type'   => $fee_type,
                ':total'  => $total_amount,
                ':paid'   => $paid_amount,
                ':due'    => $due_date,
                ':status' => $payment_status,
                ':pdate'  => $payment_date,
                ':ref'    => $ref_no ?: null
            ]);
            set_flash('success', "Fee invoice created successfully.");
        } catch (PDOException $e) {
            set_flash('error', 'Database error creating fee: ' . $e->getMessage());
        }
    }
    header("Location: fees.php");
    exit;
}

// Handle Update Fee Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_payment') {
    verify_csrf_or_die();
    $fee_id        = (int)($_POST['fee_id'] ?? 0);
    $add_payment   = (float)($_POST['additional_payment'] ?? 0.0);
    $ref_no        = trim($_POST['reference_no'] ?? '');

    if ($fee_id <= 0 || $add_payment <= 0) {
        set_flash('error', 'Valid payment amount is required.');
    } else {
        try {
            $f_stmt = $pdo->prepare("SELECT total_amount, paid_amount FROM fees WHERE fee_id = :id");
            $f_stmt->execute([':id' => $fee_id]);
            $current_fee = $f_stmt->fetch();

            if ($current_fee) {
                $new_paid = (float)$current_fee['paid_amount'] + $add_payment;
                $tot = (float)$current_fee['total_amount'];

                if ($new_paid > $tot) {
                    set_flash('error', "Payment of " . format_currency($add_payment) . " would exceed the remaining balance of " . format_currency($tot - (float)$current_fee['paid_amount']));
                } else {
                    $new_status = ($new_paid >= $tot) ? 'Paid' : 'Partial';
                    $up_stmt = $pdo->prepare("
                        UPDATE fees
                        SET paid_amount = :paid, payment_status = :status, payment_date = :pdate, reference_no = :ref
                        WHERE fee_id = :id
                    ");
                    $up_stmt->execute([
                        ':paid'   => $new_paid,
                        ':status' => $new_status,
                        ':pdate'  => date('Y-m-d'),
                        ':ref'    => $ref_no ?: ('TXN-' . date('Ymd') . '-' . rand(1000, 9999)),
                        ':id'     => $fee_id
                    ]);
                    set_flash('success', "Payment of " . format_currency($add_payment) . " recorded successfully.");
                }
            }
        } catch (PDOException $e) {
            set_flash('error', 'Database error updating fee payment.');
        }
    }
    header("Location: fees.php");
    exit;
}

// Filter by Status & Search
$filter_status = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$where = [];
$params = [];
if ($filter_status !== '') {
    $where[] = "f.payment_status = :status";
    $params[':status'] = $filter_status;
}
if ($search !== '') {
    $where[] = "(s.full_name LIKE :search OR s.student_code LIKE :search OR f.reference_no LIKE :search)";
    $params[':search'] = "%{$search}%";
}
$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$fees_stmt = $pdo->prepare("
    SELECT f.*, s.student_code, s.full_name, c.course_code
    FROM fees f
    JOIN students s ON f.student_id = s.student_id
    JOIN courses c ON s.course_id = c.course_id
    {$where_sql}
    ORDER BY f.due_date ASC
");
$fees_stmt->execute($params);
$fee_records = $fees_stmt->fetchAll();

// Summary aggregates
$fee_totals = $pdo->query("
    SELECT 
        COALESCE(SUM(total_amount), 0) as total_billed,
        COALESCE(SUM(paid_amount), 0) as total_collected,
        COALESCE(SUM(total_amount - paid_amount), 0) as total_due
    FROM fees
")->fetch();

$students = $pdo->query("SELECT student_id, student_code, full_name FROM students WHERE enrollment_status = 'Active' ORDER BY student_code ASC")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid px-4 py-3">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <h3 class="fw-bold text-dark mb-0">Fee Management &amp; Invoicing</h3>
      <div class="text-secondary small">Academic tuition, examination dues, and installment ledger tracking</div>
    </div>
    <a href="reports.php?type=fees" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i>Export Fee Ledger CSV</a>
  </div>

  <!-- Metric Badges -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card-stat">
        <div class="stat-label">Total Billed Fees</div>
        <div class="stat-value tabular-nums mt-1"><?= format_currency($fee_totals['total_billed']) ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card-stat">
        <div class="stat-label text-success">Total Collected</div>
        <div class="stat-value tabular-nums mt-1 text-success"><?= format_currency($fee_totals['total_collected']) ?></div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card-stat">
        <div class="stat-label text-danger">Total Outstanding Balance</div>
        <div class="stat-value tabular-nums mt-1 text-danger"><?= format_currency($fee_totals['total_due']) ?></div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <!-- Create Fee Invoice -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm rounded-3 p-4 bg-white">
        <h6 class="fw-bold text-dark mb-3">Create Fee Invoice</h6>
        <form method="POST" action="fees.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add_fee">

          <div class="mb-3">
            <label class="form-label small fw-semibold">Student <span class="text-danger">*</span></label>
            <select class="form-select" name="student_id" required>
              <option value="">-- Choose Student --</option>
              <?php foreach ($students as $st): ?>
                <option value="<?= (int)$st['student_id'] ?>">
                  <?= e($st['student_code']) ?> — <?= e($st['full_name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Fee Type <span class="text-danger">*</span></label>
            <select class="form-select" name="fee_type" required>
              <option value="Semester Tuition Fee">Semester Tuition Fee</option>
              <option value="Examination Fee">Examination Fee</option>
              <option value="Library & Lab Deposit">Library &amp; Lab Deposit</option>
              <option value="Hostel & Amenities Fee">Hostel &amp; Amenities Fee</option>
            </select>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Total Amount <span class="text-danger">*</span></label>
              <input type="number" step="100" min="100" class="form-control tabular-nums" name="total_amount" value="35000" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Initial Payment</label>
              <input type="number" step="100" min="0" class="form-control tabular-nums" name="paid_amount" value="0">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Due Date <span class="text-danger">*</span></label>
            <input type="date" class="form-control tabular-nums" name="due_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Payment Reference / Txn No</label>
            <input type="text" class="form-control" name="reference_no" placeholder="Optional transaction ID">
          </div>

          <button type="submit" class="btn btn-primary w-100 fw-semibold">Issue Fee Invoice</button>
        </form>
      </div>
    </div>

    <!-- Fee Ledger List -->
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h6 class="fw-bold text-dark mb-0">Fee Ledger Entries</h6>
          <!-- Filter Form -->
          <form method="GET" action="fees.php" class="d-inline-flex gap-2">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
              <option value="">All Statuses</option>
              <option value="Paid" <?= $filter_status === 'Paid' ? 'selected' : '' ?>>Paid</option>
              <option value="Partial" <?= $filter_status === 'Partial' ? 'selected' : '' ?>>Partial</option>
              <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
            </select>
          </form>
        </div>

        <div class="table-responsive">
          <table class="table table-hover table-cd mb-0 align-middle">
            <thead>
              <tr>
                <th>Student</th>
                <th>Fee Type</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Balance Due</th>
                <th>Status</th>
                <th class="text-end">Record Payment</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($fee_records)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">No fee records found.</td></tr>
              <?php else: ?>
                <?php foreach ($fee_records as $fee): ?>
                  <?php $balance = (float)$fee['total_amount'] - (float)$fee['paid_amount']; ?>
                  <tr>
                    <td>
                      <div class="fw-semibold text-dark"><?= e($fee['full_name']) ?></div>
                      <div class="small text-muted tabular-nums"><?= e($fee['student_code']) ?> · <?= e($fee['course_code']) ?></div>
                    </td>
                    <td>
                      <div class="small fw-semibold"><?= e($fee['fee_type']) ?></div>
                      <div class="small text-muted">Due: <?= format_date($fee['due_date']) ?></div>
                    </td>
                    <td class="tabular-nums"><?= format_currency($fee['total_amount']) ?></td>
                    <td class="tabular-nums text-success"><?= format_currency($fee['paid_amount']) ?></td>
                    <td class="tabular-nums fw-bold <?= $balance > 0 ? 'text-danger' : 'text-muted' ?>">
                      <?= format_currency($balance) ?>
                    </td>
                    <td>
                      <?php if ($fee['payment_status'] === 'Paid'): ?>
                        <span class="badge bg-success-subtle text-success">Paid</span>
                      <?php elseif ($fee['payment_status'] === 'Partial'): ?>
                        <span class="badge bg-warning-subtle text-warning">Partial</span>
                      <?php else: ?>
                        <span class="badge bg-danger-subtle text-danger">Pending</span>
                      <?php endif; ?>
                    </td>
                    <td class="text-end">
                      <?php if ($balance > 0): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" data-bs-toggle="modal" data-bs-target="#payModal<?= (int)$fee['fee_id'] ?>">
                          + Pay
                        </button>

                        <!-- Payment Modal -->
                        <div class="modal fade" id="payModal<?= (int)$fee['fee_id'] ?>" tabindex="-1" aria-hidden="true">
                          <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content text-start">
                              <form method="POST" action="fees.php">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="record_payment">
                                <input type="hidden" name="fee_id" value="<?= (int)$fee['fee_id'] ?>">

                                <div class="modal-header">
                                  <h6 class="modal-title fw-bold">Record Payment for <?= e($fee['full_name']) ?></h6>
                                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                  <div class="p-2 mb-3 bg-light rounded small">
                                    <div>Outstanding Balance: <strong class="text-danger tabular-nums"><?= format_currency($balance) ?></strong></div>
                                    <div class="text-muted">Fee Type: <?= e($fee['fee_type']) ?></div>
                                  </div>
                                  <div class="mb-3">
                                    <label class="form-label small fw-semibold">Payment Amount (₹)</label>
                                    <input type="number" step="100" min="1" max="<?= $balance ?>" class="form-control tabular-nums" name="additional_payment" value="<?= $balance ?>" required>
                                  </div>
                                  <div class="mb-3">
                                    <label class="form-label small fw-semibold">Transaction / Receipt Reference</label>
                                    <input type="text" class="form-control" name="reference_no" placeholder="e.g. TXN-UPI-<?= rand(10000, 99999) ?>">
                                  </div>
                                </div>
                                <div class="modal-footer">
                                  <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                                  <button type="submit" class="btn btn-sm btn-primary">Confirm Payment</button>
                                </div>
                              </form>
                            </div>
                          </div>
                        </div>
                      <?php else: ?>
                        <span class="text-success small"><i class="bi bi-check-circle-fill me-1"></i>Cleared</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
