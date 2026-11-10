<?php
/**
 * ClubHub - Club Budget & Expenses Hub
 * Phase 7b: Overview of approved budget allocations, remaining funds,
 * and logged expenses with receipt verification.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Requires logged-in Admin or Club Head
require_role(['admin', 'club_head']);

$user = current_user();
$user_id = (int)$user['id'];
$is_admin = has_role('admin');

// Clubs filter logic
$my_club_ids = [];
if ($is_admin) {
    $c_stmt = $pdo->query('SELECT id, name FROM clubs ORDER BY name ASC');
    $clubs_list = $c_stmt->fetchAll();
    $my_club_ids = array_column($clubs_list, 'id');
} else {
    $c_stmt = $pdo->prepare('SELECT id, name FROM clubs WHERE head_id = ? ORDER BY name ASC');
    $c_stmt->execute([$user_id]);
    $clubs_list = $c_stmt->fetchAll();
    $my_club_ids = array_column($clubs_list, 'id');
}

$selected_club = isset($_GET['club_id']) ? (int)$_GET['club_id'] : 0;

// Query Totals
$approved_budget_total = 0.0;
$expenses_total = 0.0;
$pending_requests_count = 0;

if (!empty($my_club_ids)) {
    $in_clause = implode(',', array_fill(0, count($my_club_ids), '?'));
    
    // Approved Budget
    $b_sql = "SELECT COALESCE(SUM(amount), 0) FROM budget_requests WHERE status = 'approved' AND club_id IN ($in_clause)";
    if ($selected_club > 0) {
        $b_sql = "SELECT COALESCE(SUM(amount), 0) FROM budget_requests WHERE status = 'approved' AND club_id = ?";
        $b_stmt = $pdo->prepare($b_sql);
        $b_stmt->execute([$selected_club]);
    } else {
        $b_stmt = $pdo->prepare($b_sql);
        $b_stmt->execute($my_club_ids);
    }
    $approved_budget_total = (float)$b_stmt->fetchColumn();

    // Total Expenses
    $e_sql = "SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE club_id IN ($in_clause)";
    if ($selected_club > 0) {
        $e_sql = "SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE club_id = ?";
        $e_stmt = $pdo->prepare($e_sql);
        $e_stmt->execute([$selected_club]);
    } else {
        $e_stmt = $pdo->prepare($e_sql);
        $e_stmt->execute($my_club_ids);
    }
    $expenses_total = (float)$e_stmt->fetchColumn();

    // Pending Requests
    $p_sql = "SELECT COUNT(*) FROM budget_requests WHERE status = 'pending' AND club_id IN ($in_clause)";
    if ($selected_club > 0) {
        $p_sql = "SELECT COUNT(*) FROM budget_requests WHERE status = 'pending' AND club_id = ?";
        $p_stmt = $pdo->prepare($p_sql);
        $p_stmt->execute([$selected_club]);
    } else {
        $p_stmt = $pdo->prepare($p_sql);
        $p_stmt->execute($my_club_ids);
    }
    $pending_requests_count = (int)$p_stmt->fetchColumn();
}

$remaining_budget = $approved_budget_total - $expenses_total;

// Query Budget Requests
$req_sql = "
    SELECT br.*, c.name AS club_name, u.name AS creator_name
    FROM budget_requests br
    JOIN clubs c ON br.club_id = c.id
    LEFT JOIN users u ON br.created_by = u.id
    WHERE 1=1
";
$req_params = [];
if (!empty($my_club_ids)) {
    if ($selected_club > 0) {
        $req_sql .= " AND br.club_id = ?";
        $req_params[] = $selected_club;
    } else {
        $in_clause = implode(',', array_fill(0, count($my_club_ids), '?'));
        $req_sql .= " AND br.club_id IN ($in_clause)";
        $req_params = array_merge($req_params, $my_club_ids);
    }
} else {
    $req_sql .= " AND 1=0";
}
$req_sql .= " ORDER BY br.created_at DESC";
$req_stmt = $pdo->prepare($req_sql);
$req_stmt->execute($req_params);
$budget_requests = $req_stmt->fetchAll();

// Query Expenses
$exp_sql = "
    SELECT exp.*, c.name AS club_name, br.title AS request_title, u.name AS creator_name
    FROM expenses exp
    JOIN clubs c ON exp.club_id = c.id
    LEFT JOIN budget_requests br ON exp.budget_request_id = br.id
    LEFT JOIN users u ON exp.created_by = u.id
    WHERE 1=1
";
$exp_params = [];
if (!empty($my_club_ids)) {
    if ($selected_club > 0) {
        $exp_sql .= " AND exp.club_id = ?";
        $exp_params[] = $selected_club;
    } else {
        $in_clause = implode(',', array_fill(0, count($my_club_ids), '?'));
        $exp_sql .= " AND exp.club_id IN ($in_clause)";
        $exp_params = array_merge($exp_params, $my_club_ids);
    }
} else {
    $exp_sql .= " AND 1=0";
}
$exp_sql .= " ORDER BY exp.expense_date DESC, exp.created_at DESC";
$exp_stmt = $pdo->prepare($exp_sql);
$exp_stmt->execute($exp_params);
$expenses = $exp_stmt->fetchAll();

$page_title = 'Club Budget & Expenses';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <div class="action-bar">
        <div>
            <h1>Club Budget & Expenses</h1>
            <p><?php echo $is_admin ? 'Manage campus allocations, review club funding requests, and audit expense receipts.' : 'Track your approved club budget, request funding, and log receipts.'; ?></p>
        </div>
        <div style="display:flex; gap:var(--space-sm); flex-wrap:wrap;">
            <a href="budget-form.php" class="btn btn-primary">+ Raise Budget Request</a>
            <a href="expense-form.php" class="btn btn-secondary">+ Log New Expense</a>
        </div>
    </div>
</section>

<!-- Filter by club if Admin or multiple clubs -->
<?php if (count($clubs_list) > 1): ?>
    <section class="filter-section" style="margin-bottom: var(--space-xl);">
        <form class="filter-form" action="budget.php" method="GET">
            <p class="form-group">
                <label class="form-label" for="club_id">Filter by Club:</label>
                <select class="form-control" id="club_id" name="club_id" onchange="this.form.submit()">
                    <option value="">All Managed Clubs</option>
                    <?php foreach ($clubs_list as $cl): ?>
                        <option value="<?php echo (int)$cl['id']; ?>" <?php echo $selected_club === (int)$cl['id'] ? 'selected' : ''; ?>>
                            <?php echo e($cl['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p class="form-actions">
                <noscript><button type="submit" class="btn btn-secondary">Filter</button></noscript>
            </p>
        </form>
    </section>
<?php endif; ?>

<!-- Financial Metrics Grid -->
<div class="stat-grid">
    <div class="stat-card" style="border-left: 4px solid var(--primary);">
        <div class="stat-value">$<?php echo number_format($approved_budget_total, 2); ?></div>
        <div class="stat-label">Total Approved Budget</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid var(--secondary);">
        <div class="stat-value" style="color: var(--secondary);">$<?php echo number_format($expenses_total, 2); ?></div>
        <div class="stat-label">Total Logged Expenses</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid <?php echo $remaining_budget >= 0 ? 'var(--success)' : 'var(--error)'; ?>;">
        <div class="stat-value" style="color: <?php echo $remaining_budget >= 0 ? 'var(--success)' : 'var(--error)'; ?>;">
            $<?php echo number_format($remaining_budget, 2); ?>
        </div>
        <div class="stat-label">Budget Remaining</div>
    </div>
    <div class="stat-card" style="border-left: 4px solid var(--warning);">
        <div class="stat-value" style="color: var(--warning);"><?php echo $pending_requests_count; ?></div>
        <div class="stat-label">Pending Requests</div>
    </div>
</div>

<!-- 1. Budget Requests Table -->
<section class="dashboard-section card">
    <div class="action-bar" style="margin-bottom: var(--space-md);">
        <h2>Budget Requests (<?php echo count($budget_requests); ?>)</h2>
        <a href="budget-form.php" class="btn btn-sm btn-primary">+ New Request</a>
    </div>

    <?php if (empty($budget_requests)): ?>
        <p style="color: var(--text-muted);">No budget requests recorded yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Club</th>
                        <th>Request Title</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Coordinator Remarks</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($budget_requests as $br): ?>
                        <tr>
                            <td><strong><?php echo e($br['club_name']); ?></strong></td>
                            <td>
                                <strong><?php echo e($br['title']); ?></strong>
                                <small style="display:block; color:var(--text-muted);"><?php echo e(mb_strimwidth($br['description'] ?? '', 0, 80, '...')); ?></small>
                            </td>
                            <td><strong>$<?php echo number_format((float)$br['amount'], 2); ?></strong></td>
                            <td><span class="badge badge-<?php echo e($br['status']); ?>"><?php echo e(ucfirst($br['status'])); ?></span></td>
                            <td>
                                <?php if (!empty($br['admin_remarks'])): ?>
                                    <em style="color:#713f12;"><?php echo e($br['admin_remarks']); ?></em>
                                <?php else: ?>
                                    <span style="color:var(--text-muted);">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($br['created_at'])); ?></td>
                            <td>
                                <?php if ($is_admin && $br['status'] === 'pending'): ?>
                                    <form action="budget-action.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="request_id" value="<?php echo (int)$br['id']; ?>">
                                        <input type="hidden" name="decision" value="approve">
                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                    </form>
                                    <form action="budget-action.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="request_id" value="<?php echo (int)$br['id']; ?>">
                                        <input type="hidden" name="decision" value="reject">
                                        <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                <?php endif; ?>
                                <a href="budget-form.php?id=<?php echo (int)$br['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- 2. Logged Expenses Table -->
<section class="dashboard-section card">
    <div class="action-bar" style="margin-bottom: var(--space-md);">
        <h2>Logged Expenses & Receipts (<?php echo count($expenses); ?>)</h2>
        <a href="expense-form.php" class="btn btn-sm btn-primary">+ Log Expense</a>
    </div>

    <?php if (empty($expenses)): ?>
        <p style="color: var(--text-muted);">No expenses logged yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Club</th>
                        <th>Expense Title</th>
                        <th>Linked Request</th>
                        <th>Amount</th>
                        <th>Expense Date</th>
                        <th>Receipt Document</th>
                        <th>Logged By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $exp): ?>
                        <tr>
                            <td><strong><?php echo e($exp['club_name']); ?></strong></td>
                            <td><strong><?php echo e($exp['title']); ?></strong></td>
                            <td><?php echo !empty($exp['request_title']) ? e($exp['request_title']) : '<em style="color:var(--text-muted);">General Club Expense</em>'; ?></td>
                            <td><strong style="color:var(--secondary);">$<?php echo number_format((float)$exp['amount'], 2); ?></strong></td>
                            <td><?php echo date('M j, Y', strtotime($exp['expense_date'])); ?></td>
                            <td>
                                <?php if (!empty($exp['receipt_file'])): ?>
                                    <a href="../uploads/receipts/<?php echo e($exp['receipt_file']); ?>" target="_blank" class="btn btn-sm btn-outline" style="font-size:0.8rem;">
                                        📄 View Receipt (<?php echo strtoupper(pathinfo($exp['receipt_file'], PATHINFO_EXTENSION)); ?>)
                                    </a>
                                <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.85rem;">No file</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($exp['creator_name'] ?? 'Club Organizer'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

