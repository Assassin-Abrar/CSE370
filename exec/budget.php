<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['exec']);
$club = myClub($user);
if (!$club) { die('<p style="padding:40px;">Not linked to a club.</p>'); }
$pdo = db();

$stmt = $pdo->prepare("SELECT b.*, v.name AS venue_name FROM budget_requests b LEFT JOIN venues v ON v.id=b.venue_id WHERE b.club_id = ? ORDER BY b.submitted_at DESC");
$stmt->execute([$club['id']]);
$budgets = $stmt->fetchAll();

$itemsStmt = $pdo->prepare('SELECT * FROM budget_items WHERE budget_request_id = ?');
$logStmt = $pdo->prepare('SELECT l.*, u.name AS actor FROM budget_audit_log l LEFT JOIN users u ON u.id=l.actor_user_id WHERE l.budget_request_id = ? ORDER BY l.created_at');

$venues = $pdo->query('SELECT * FROM venues ORDER BY name')->fetchAll();
$stmt = $pdo->prepare("SELECT id, title FROM events WHERE club_id = ? ORDER BY event_date DESC LIMIT 20");
$stmt->execute([$club['id']]);
$clubEvents = $stmt->fetchAll();

$totalAllocated = 0; $totalPending = 0;
foreach ($budgets as $b) {
    if ($b['status'] === 'approved') $totalAllocated += $b['approved_amount'];
    elseif (in_array($b['status'], ['submitted','under_review'], true)) $totalPending += $b['requested_amount'];
}

$pageTitle = 'Budget';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head">
  <div><h1>Budget &amp; Approvals</h1><div class="sub">Submit itemized proposals and track OCA review status.</div></div>
  <button class="btn btn-primary" data-open-modal="newBudgetModal"><?= icon('plus','icon') ?> New Budget Request</button>
</div>

<div class="grid grid-3" style="margin-bottom:22px;">
  <div class="card stat-tile"><div class="value"><?= moneyBDT($totalAllocated) ?></div><div class="label">Allocated Budget</div></div>
  <div class="card stat-tile"><div class="value"><?= moneyBDT($totalPending) ?></div><div class="label">Pending Allocation</div></div>
  <div class="card stat-tile"><div class="value"><?= count($budgets) ?></div><div class="label">Total Requests</div></div>
</div>

<?php if (!$budgets): ?>
  <div class="empty-state"><div class="icon">💰</div><h4>No budget requests yet</h4></div>
<?php else: foreach ($budgets as $b):
  $itemsStmt->execute([$b['id']]); $items = $itemsStmt->fetchAll();
  $logStmt->execute([$b['id']]); $logs = $logStmt->fetchAll();
?>
  <div class="card" style="margin-bottom:16px;">
    <div class="flex-between">
      <div><strong><?= e($b['event_name']) ?></strong><br><span class="text-xs text-muted"><?= $b['event_date'] ? date('M j, Y', strtotime($b['event_date'])) : 'No date set' ?> · <?= e($b['venue_name'] ?? 'TBA') ?></span></div>
      <div style="text-align:right;">
        <span class="badge <?= badgeClass($b['status']) ?>" style="padding:6px 14px;"><?= statusLabel($b['status']) ?></span><br>
        <strong style="font-size:18px;"><?= moneyBDT($b['status']==='approved' ? $b['approved_amount'] : $b['requested_amount']) ?></strong>
        <div class="text-xs text-subtle"><?= $b['status']==='approved' ? 'Allocated Budget' : 'Pending Allocation' ?></div>
      </div>
    </div>
    <details style="margin-top:12px;">
      <summary class="text-sm fw-600" style="cursor:pointer;color:var(--primary);">View breakdown &amp; audit trail</summary>
      <div class="overflow-x" style="margin-top:12px;">
        <table class="table budget-items-table">
          <thead><tr><th>Category</th><th>Amount</th></tr></thead>
          <tbody>
            <?php foreach ($items as $it): ?><tr><td><?= e($it['category']) ?></td><td><?= moneyBDT($it['amount']) ?></td></tr><?php endforeach; ?>
            <tr style="font-weight:700;"><td>Total</td><td><?= moneyBDT($b['requested_amount']) ?></td></tr>
          </tbody>
        </table>
      </div>
      <?php if ($b['justification']): ?><p class="text-sm text-muted" style="margin-top:10px;"><strong>Justification:</strong> <?= e($b['justification']) ?></p><?php endif; ?>
      <?php if ($b['review_comment']): ?><p class="text-sm" style="margin-top:6px;color:<?= $b['status']==='rejected'?'var(--red-dark)':'var(--green-dark)' ?>;"><strong>OCA comment:</strong> <?= e($b['review_comment']) ?></p><?php endif; ?>
      <div class="audit-trail" style="margin-top:16px;">
        <?php foreach ($logs as $l): ?>
          <div class="audit-item"><strong class="text-sm"><?= e(statusLabel($l['action'])) ?></strong> <span class="text-xs text-subtle">by <?= e($l['actor'] ?? 'System') ?> · <?= timeAgo($l['created_at']) ?></span><?php if($l['notes']): ?><p class="text-sm text-muted mt-0"><?= e($l['notes']) ?></p><?php endif; ?></div>
        <?php endforeach; ?>
      </div>
      <?php if ($b['status'] === 'draft'): ?>
        <button class="btn btn-sm btn-primary" style="margin-top:10px;" data-ajax-post="<?= BASE_URL ?>api/submit_draft_budget.php" data-ajax-body='{"budget_id":<?= $b['id'] ?>}'>Submit for Review</button>
      <?php endif; ?>
    </details>
  </div>
<?php endforeach; endif; ?>

<div class="modal-backdrop" id="newBudgetModal">
  <div class="modal modal-lg">
    <div class="modal-head"><h3>New Budget Request</h3><button class="modal-close" data-close-modal>&times;</button></div>
    <form class="ajax-form" action="<?= BASE_URL ?>api/submit_budget.php" method="post" id="budgetForm">
      <div class="modal-body">
        <div class="form-row">
          <div class="field"><label>Event name *</label><input class="input" name="event_name" required></div>
          <div class="field"><label>Linked event</label>
            <select class="input" name="event_id"><option value="">— None —</option><?php foreach ($clubEvents as $ce): ?><option value="<?= $ce['id'] ?>"><?= e($ce['title']) ?></option><?php endforeach; ?></select>
          </div>
        </div>
        <div class="form-row">
          <div class="field"><label>Event date</label><input class="input" type="date" name="event_date"></div>
          <div class="field"><label>Venue</label><select class="input" name="venue_id"><option value="">Select venue</option><?php foreach ($venues as $v): ?><option value="<?= $v['id'] ?>"><?= e($v['name']) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="field"><label>Expected attendance</label><input class="input" type="number" name="expected_attendance" min="0"></div>
        <div class="field"><label>Justification</label><textarea class="input" name="justification" rows="2"></textarea></div>
        <div class="divider"></div>
        <label>Budget categories *</label>
        <div id="budgetRows">
          <div class="form-row budget-row"><input class="input" name="category[]" placeholder="Category e.g. Decorations"><input class="input" type="number" name="amount[]" placeholder="Amount ৳" min="0"></div>
          <div class="form-row budget-row"><input class="input" name="category[]" placeholder="Category e.g. Food"><input class="input" type="number" name="amount[]" placeholder="Amount ৳" min="0"></div>
        </div>
        <button type="button" class="btn btn-sm btn-outline" id="addRowBtn" style="margin-top:6px;">+ Add category</button>
        <p class="text-sm fw-700" style="margin-top:12px;">Total: ৳<span id="budgetTotal">0</span></p>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn" data-close-modal>Cancel</button>
        <button type="submit" class="btn btn-outline" onclick="document.getElementById('intentField').value='draft'">Save Draft</button>
        <button type="submit" class="btn btn-primary" onclick="document.getElementById('intentField').value='submitted'">Submit for Review</button>
      </div>
      <input type="hidden" name="intent" id="intentField" value="submitted">
    </form>
  </div>
</div>

<script>
document.getElementById('addRowBtn').addEventListener('click', () => {
  const row = document.createElement('div');
  row.className = 'form-row budget-row';
  row.innerHTML = '<input class="input" name="category[]" placeholder="Category"><input class="input" type="number" name="amount[]" placeholder="Amount ৳" min="0">';
  document.getElementById('budgetRows').appendChild(row);
});
document.getElementById('budgetRows').addEventListener('input', () => {
  let total = 0;
  document.querySelectorAll('input[name="amount[]"]').forEach(i => total += parseFloat(i.value || 0));
  document.getElementById('budgetTotal').textContent = total.toLocaleString();
});
</script>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
