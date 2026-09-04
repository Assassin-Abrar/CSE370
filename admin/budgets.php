<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['admin']);
$pdo = db();

$stmt = $pdo->query("SELECT b.*, c.name AS club_name FROM budget_requests b JOIN clubs c ON c.id=b.club_id ORDER BY b.submitted_at DESC");
$budgets = $stmt->fetchAll();
$pending = array_filter($budgets, fn($b) => in_array($b['status'], ['submitted','under_review'], true));

$itemsStmt = $pdo->prepare('SELECT * FROM budget_items WHERE budget_request_id = ?');

$totalApproved = 0; $totalPending = 0;
foreach ($budgets as $b) {
    if ($b['status'] === 'approved') $totalApproved += $b['approved_amount'];
    if (in_array($b['status'], ['submitted','under_review'], true)) $totalPending += $b['requested_amount'];
}

$pageTitle = 'Budgets';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head"><div><h1>Budget Approvals</h1><div class="sub">Review and allocate club budget requests.</div></div></div>

<div class="grid grid-3" style="margin-bottom:22px;">
  <div class="card stat-tile"><div class="value"><?= moneyBDT($totalApproved) ?></div><div class="label">Total Allocated</div></div>
  <div class="card stat-tile"><div class="value"><?= moneyBDT($totalPending) ?></div><div class="label">Pending Allocation</div></div>
  <div class="card stat-tile"><div class="value"><?= count($pending) ?></div><div class="label">Awaiting Review</div></div>
</div>

<div class="tabs" data-tabs="#budAdminTabs">
  <button class="active" data-tab="pending">Pending (<?= count($pending) ?>)</button>
  <button data-tab="all">All Requests</button>
</div>
<div id="budAdminTabs">
  <div data-tab-panel="pending">
    <?php if (!$pending): ?><div class="empty-state"><div class="icon">✅</div><h4>Nothing awaiting review</h4></div>
    <?php else: foreach ($pending as $b): $itemsStmt->execute([$b['id']]); $items = $itemsStmt->fetchAll(); ?>
      <div class="card" style="margin-bottom:14px;">
        <div class="flex-between">
          <div><strong><?= e($b['event_name']) ?></strong> <span class="badge badge-gray"><?= e($b['club_name']) ?></span>
            <p class="text-sm text-muted" style="margin:6px 0;"><?= e($b['justification']) ?></p>
          </div>
          <strong style="font-size:18px;"><?= moneyBDT($b['requested_amount']) ?></strong>
        </div>
        <div class="overflow-x" style="margin:10px 0;">
          <table class="table budget-items-table"><thead><tr><th>Category</th><th>Amount</th></tr></thead><tbody>
            <?php foreach ($items as $it): ?><tr><td><?= e($it['category']) ?></td><td><?= moneyBDT($it['amount']) ?></td></tr><?php endforeach; ?>
          </tbody></table>
        </div>
        <div style="display:flex;gap:8px;">
          <button class="btn btn-sm btn-outline" data-open-modal="approveModal-<?= $b['id'] ?>">Approve</button>
          <button class="btn btn-sm btn-danger" data-open-modal="rejectBudModal-<?= $b['id'] ?>">Reject</button>
        </div>
      </div>
      <div class="modal-backdrop" id="approveModal-<?= $b['id'] ?>">
        <div class="modal">
          <div class="modal-head"><h3>Approve Budget</h3><button class="modal-close" data-close-modal>&times;</button></div>
          <form class="ajax-form" action="<?= BASE_URL ?>api/review_budget.php" method="post">
            <div class="modal-body">
              <input type="hidden" name="budget_id" value="<?= $b['id'] ?>"><input type="hidden" name="action" value="approve">
              <div class="field"><label>Approved amount (৳)</label><input class="input" type="number" name="approved_amount" value="<?= (int)$b['requested_amount'] ?>" min="0"></div>
              <div class="field"><label>Comment (optional)</label><textarea class="input" name="comment" rows="2"></textarea></div>
            </div>
            <div class="modal-foot"><button type="button" class="btn" data-close-modal>Cancel</button><button type="submit" class="btn btn-success">Confirm Approval</button></div>
          </form>
        </div>
      </div>
      <div class="modal-backdrop" id="rejectBudModal-<?= $b['id'] ?>">
        <div class="modal">
          <div class="modal-head"><h3>Reject Budget</h3><button class="modal-close" data-close-modal>&times;</button></div>
          <form class="ajax-form" action="<?= BASE_URL ?>api/review_budget.php" method="post">
            <div class="modal-body">
              <input type="hidden" name="budget_id" value="<?= $b['id'] ?>"><input type="hidden" name="action" value="reject">
              <div class="field"><label>Reason for rejection *</label><textarea class="input" name="comment" rows="3" required></textarea></div>
            </div>
            <div class="modal-foot"><button type="button" class="btn" data-close-modal>Cancel</button><button type="submit" class="btn btn-danger">Confirm Rejection</button></div>
          </form>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>
  <div data-tab-panel="all" style="display:none;">
    <div class="card" style="padding:0;">
      <div class="overflow-x">
      <table class="table responsive-cards">
        <thead><tr><th>Event</th><th>Club</th><th>Requested</th><th>Approved</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($budgets as $b): ?>
          <tr>
            <td data-label="Event"><?= e($b['event_name']) ?></td>
            <td data-label="Club"><?= e($b['club_name']) ?></td>
            <td data-label="Requested"><?= moneyBDT($b['requested_amount']) ?></td>
            <td data-label="Approved"><?= $b['approved_amount'] !== null ? moneyBDT($b['approved_amount']) : '—' ?></td>
            <td data-label="Status"><span class="badge <?= badgeClass($b['status']) ?>"><?= statusLabel($b['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
