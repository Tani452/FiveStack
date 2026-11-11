<?php
/**
 * ClubHub - Role-Based User Dashboard
 * Phase 7b: Complete operations workflows across roles.
 * - Admin: Pending event sanctions, budget request approvals, announcements, club metrics.
 * - Club Head: Pending member applications approval/rejection, event schedule, budget & remaining funds, announcements.
 * - Member: Enrolled clubs status, announcements bulletin, upcoming sanctioned events.
 * Protected by require_role(['admin', 'club_head', 'member']).
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Protect page: require authenticated user with valid role
require_role(['admin', 'club_head', 'member']);

$user = current_user();
$user_id = (int)$user['id'];
$role = $user['role'];

$page_title = ucwords(str_replace('_', ' ', $role)) . ' Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <div class="action-bar">
        <div>
            <h1><?php echo e(ucwords(str_replace('_', ' ', $role))); ?> Dashboard</h1>
            <p>Welcome, <strong><?php echo e($user['name']); ?></strong> (<?php echo e($user['email']); ?>) &mdash; Campus Club Operations Center.</p>
        </div>
        <div>
            <span class="badge badge-role badge-<?php echo e($role); ?>" style="font-size: 0.95rem; padding: 0.45rem 1rem;">
                Role: <?php echo e(ucwords(str_replace('_', ' ', $role))); ?>
            </span>
        </div>
    </div>
</section>

<?php
// =========================================================================
// 1. ADMIN (FACULTY COORDINATOR) DASHBOARD
// =========================================================================
if ($role === 'admin'):
    // Query metrics
    $total_clubs = (int)$pdo->query("SELECT COUNT(*) FROM clubs WHERE status = 'active'")->fetchColumn();
    $pending_events = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status = 'pending'")->fetchColumn();
    $approved_events = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status = 'approved'")->fetchColumn();
    $pending_budgets = (int)$pdo->query("SELECT COUNT(*) FROM budget_requests WHERE status = 'pending'")->fetchColumn();
    $total_approved_budget = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM budget_requests WHERE status = 'approved'")->fetchColumn();
    $total_expenses = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM expenses")->fetchColumn();
    $total_students = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'member'")->fetchColumn();

    // Query pending events requiring approval
    $pending_stmt = $pdo->query("
        SELECT e.*, c.name AS club_name, v.name AS venue_name
        FROM events e
        JOIN clubs c ON e.club_id = c.id
        LEFT JOIN venues v ON e.venue_id = v.id
        WHERE e.status = 'pending'
        ORDER BY e.event_date ASC
    ");
    $pending_events_list = $pending_stmt->fetchAll();

    // Query pending budget requests requiring sanction
    $pending_b_stmt = $pdo->query("
        SELECT br.*, c.name AS club_name, u.name AS creator_name
        FROM budget_requests br
        JOIN clubs c ON br.club_id = c.id
        LEFT JOIN users u ON br.created_by = u.id
        WHERE br.status = 'pending'
        ORDER BY br.created_at ASC
    ");
    $pending_budgets_list = $pending_b_stmt->fetchAll();

    // Query announcements
    $ann_stmt = $pdo->query("
        SELECT a.*, c.name AS club_name, u.name AS author_name
        FROM announcements a
        LEFT JOIN clubs c ON a.club_id = c.id
        JOIN users u ON a.created_by = u.id
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $announcements_list = $ann_stmt->fetchAll();

    // Query all clubs
    $clubs_stmt = $pdo->query("
        SELECT c.*, u.name AS head_name,
               (SELECT COUNT(*) FROM club_members cm WHERE cm.club_id = c.id AND cm.status = 'approved') AS member_count
        FROM clubs c
        LEFT JOIN users u ON c.head_id = u.id
        ORDER BY c.name ASC
    ");
    $admin_clubs = $clubs_stmt->fetchAll();
?>
    <!-- Admin Stat Grid -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo $total_clubs; ?></div>
            <div class="stat-label">Active Clubs</div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--warning);">
            <div class="stat-value" style="color: var(--warning);"><?php echo $pending_events; ?></div>
            <div class="stat-label">Pending Event Proposals</div>
        </div>
        <div class="stat-card" style="border-left: 4px solid #d97706;">
            <div class="stat-value" style="color: #d97706;"><?php echo $pending_budgets; ?></div>
            <div class="stat-label">Pending Budget Requests</div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--success);">
            <div class="stat-value" style="color: var(--success);">$<?php echo number_format($total_approved_budget - $total_expenses, 2); ?></div>
            <div class="stat-label">Total Budget Remaining</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $total_students; ?></div>
            <div class="stat-label">Registered Students</div>
        </div>
    </div>

    <!-- Admin Quick Action Bar -->
    <div class="action-bar card" style="padding: var(--space-md) var(--space-lg); margin-bottom: var(--space-2xl);">
        <div>
            <h3 style="margin: 0;">Faculty Operations Shortcuts</h3>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Sanction proposals, manage campus venues, and publish college announcements.</p>
        </div>
        <div style="display: flex; gap: var(--space-sm); flex-wrap: wrap;">
            <a href="club-form.php" class="btn btn-primary">+ Create Club</a>
            <a href="event-form.php" class="btn btn-primary">+ Schedule Event</a>
            <a href="venue-form.php" class="btn btn-secondary">+ Add Venue</a>
            <a href="announcement-form.php" class="btn btn-secondary">📢 Post Announcement</a>
            <a href="budget.php" class="btn btn-outline">💰 Budget Hub</a>
            <a href="http://localhost:8000/" target="_blank" class="btn btn-outline" style="border-color: #7c3aed; color: #6d28d9;" title="Launch Django Analytical Reports Dashboard on port 8000">📊 Django Reports</a>
        </div>
    </div>

    <!-- 1. Pending Event Proposals Section -->
    <section class="dashboard-section card">
        <div class="action-bar" style="margin-bottom: var(--space-md);">
            <h2>Pending Event Proposals (<?php echo count($pending_events_list); ?>)</h2>
            <span class="badge badge-warning">Requires Faculty Sanction</span>
        </div>

        <?php if (empty($pending_events_list)): ?>
            <p style="color: var(--text-muted);">No club events are currently awaiting review. All proposals have been processed.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Event Title</th>
                            <th>Club</th>
                            <th>Proposed Date & Time</th>
                            <th>Venue</th>
                            <th>Sanction Decision & Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_events_list as $pe): ?>
                            <tr>
                                <td>
                                    <strong><a href="event-detail.php?id=<?php echo (int)$pe['id']; ?>"><?php echo e($pe['title']); ?></a></strong>
                                </td>
                                <td><?php echo e($pe['club_name']); ?></td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($pe['event_date'])); ?><br>
                                    <small style="color: var(--text-muted);"><?php echo date('h:i A', strtotime($pe['start_time'])); ?> - <?php echo date('h:i A', strtotime($pe['end_time'])); ?></small>
                                </td>
                                <td><?php echo e($pe['venue_name'] ?? 'TBA'); ?></td>
                                <td>
                                    <form action="event-action.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="event_id" value="<?php echo (int)$pe['id']; ?>">
                                        <input type="text" name="admin_remarks" placeholder="Optional approval remark..." style="padding:0.25rem 0.5rem; font-size:0.85rem; width:180px; margin-right:4px;">
                                        <button type="submit" name="decision" value="approve" class="btn btn-sm btn-success">Approve</button>
                                        <button type="submit" name="decision" value="reject" class="btn btn-sm btn-danger">Reject</button>
                                        <a href="event-form.php?id=<?php echo (int)$pe['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- 2. Pending Budget Requests Section -->
    <section class="dashboard-section card">
        <div class="action-bar" style="margin-bottom: var(--space-md);">
            <h2>Pending Budget Requests (<?php echo count($pending_budgets_list); ?>)</h2>
            <a href="budget.php" class="btn btn-sm btn-outline">View Budget Hub</a>
        </div>

        <?php if (empty($pending_budgets_list)): ?>
            <p style="color: var(--text-muted);">No club budget requests are currently awaiting review.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Club</th>
                            <th>Request Title</th>
                            <th>Amount</th>
                            <th>Justification</th>
                            <th>Decision & Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_budgets_list as $pbr): ?>
                            <tr>
                                <td><strong><?php echo e($pbr['club_name']); ?></strong></td>
                                <td><?php echo e($pbr['title']); ?></td>
                                <td><strong style="color:var(--primary);">$<?php echo number_format((float)$pbr['amount'], 2); ?></strong></td>
                                <td><small style="color:var(--text-muted);"><?php echo e(mb_strimwidth($pbr['description'] ?? '', 0, 80, '...')); ?></small></td>
                                <td>
                                    <form action="budget-action.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="request_id" value="<?php echo (int)$pbr['id']; ?>">
                                        <input type="text" name="admin_remarks" placeholder="Remarks..." style="padding:0.25rem 0.5rem; font-size:0.85rem; width:140px; margin-right:4px;">
                                        <button type="submit" name="decision" value="approve" class="btn btn-sm btn-success">Approve</button>
                                        <button type="submit" name="decision" value="reject" class="btn btn-sm btn-danger">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- 3. Campus Announcements Bulletin -->
    <section class="dashboard-section card">
        <div class="action-bar" style="margin-bottom: var(--space-md);">
            <h2>Campus Announcements (<?php echo count($announcements_list); ?>)</h2>
            <a href="announcement-form.php" class="btn btn-sm btn-primary">+ Post Announcement</a>
        </div>

        <?php if (empty($announcements_list)): ?>
            <p style="color: var(--text-muted);">No announcements published yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Scope / Club</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Posted Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($announcements_list as $ann): ?>
                            <tr>
                                <td>
                                    <?php if (empty($ann['club_name'])): ?>
                                        <span class="badge badge-primary">📢 College-Wide</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><?php echo e($ann['club_name']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo e($ann['title']); ?></strong>
                                    <small style="display:block; color:var(--text-muted);"><?php echo e(mb_strimwidth($ann['content'], 0, 80, '...')); ?></small>
                                </td>
                                <td><?php echo e($ann['author_name']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($ann['created_at'])); ?></td>
                                <td>
                                    <form action="announcement-form.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;" onsubmit="return confirm('Remove this announcement?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$ann['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- 4. Managed Clubs Table -->
    <section class="dashboard-section card">
        <div class="action-bar" style="margin-bottom: var(--space-md);">
            <h2>Managed Student Clubs (<?php echo count($admin_clubs); ?>)</h2>
            <a href="club-form.php" class="btn btn-sm btn-primary">+ Add Club</a>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Club Name</th>
                        <th>Category</th>
                        <th>Club Head</th>
                        <th>Members</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admin_clubs as $ac): ?>
                        <tr>
                            <td><strong><?php echo e($ac['name']); ?></strong></td>
                            <td><span class="badge badge-primary"><?php echo e($ac['category']); ?></span></td>
                            <td><?php echo e($ac['head_name'] ?? 'Unassigned'); ?></td>
                            <td><?php echo (int)$ac['member_count']; ?> students</td>
                            <td><span class="badge badge-<?php echo $ac['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo e(ucfirst($ac['status'])); ?></span></td>
                            <td>
                                <a href="club-form.php?id=<?php echo (int)$ac['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

<?php
// =========================================================================
// 2. CLUB HEAD DASHBOARD
// =========================================================================
elseif ($role === 'club_head'):
    // Query clubs headed by this user
    $head_clubs_stmt = $pdo->prepare("SELECT * FROM clubs WHERE head_id = ?");
    $head_clubs_stmt->execute([$user_id]);
    $my_clubs = $head_clubs_stmt->fetchAll();
    $my_club_ids = array_column($my_clubs, 'id');

    $my_events = [];
    $pending_applications = [];
    $roster = [];
    $my_announcements = [];
    $club_approved_budget = 0.0;
    $club_expenses = 0.0;

    if (!empty($my_club_ids)) {
        $in_clause = implode(',', array_fill(0, count($my_club_ids), '?'));

        // Events
        $my_events_stmt = $pdo->prepare("
            SELECT e.*, c.name AS club_name, v.name AS venue_name
            FROM events e
            JOIN clubs c ON e.club_id = c.id
            LEFT JOIN venues v ON e.venue_id = v.id
            WHERE e.club_id IN ($in_clause)
            ORDER BY e.event_date ASC
        ");
        $my_events_stmt->execute($my_club_ids);
        $my_events = $my_events_stmt->fetchAll();

        // Pending Membership Applications
        $p_apps_stmt = $pdo->prepare("
            SELECT cm.*, u.name AS applicant_name, u.email AS applicant_email, u.phone AS applicant_phone, c.name AS club_name
            FROM club_members cm
            JOIN users u ON cm.user_id = u.id
            JOIN clubs c ON cm.club_id = c.id
            WHERE cm.club_id IN ($in_clause) AND cm.status = 'pending'
            ORDER BY cm.joined_at ASC
        ");
        $p_apps_stmt->execute($my_club_ids);
        $pending_applications = $p_apps_stmt->fetchAll();

        // Approved Members Roster
        $roster_stmt = $pdo->prepare("
            SELECT cm.*, u.name, u.email, u.phone, c.name AS club_name
            FROM club_members cm
            JOIN users u ON cm.user_id = u.id
            JOIN clubs c ON cm.club_id = c.id
            WHERE cm.club_id IN ($in_clause) AND cm.status = 'approved'
            ORDER BY cm.joined_at DESC
        ");
        $roster_stmt->execute($my_club_ids);
        $roster = $roster_stmt->fetchAll();

        // Budget & Expenses
        $b_calc = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM budget_requests WHERE status = 'approved' AND club_id IN ($in_clause)");
        $b_calc->execute($my_club_ids);
        $club_approved_budget = (float)$b_calc->fetchColumn();

        $e_calc = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE club_id IN ($in_clause)");
        $e_calc->execute($my_club_ids);
        $club_expenses = (float)$e_calc->fetchColumn();

        // Announcements
        $ann_calc = $pdo->prepare("
            SELECT a.*, c.name AS club_name, u.name AS author_name
            FROM announcements a
            LEFT JOIN clubs c ON a.club_id = c.id
            JOIN users u ON a.created_by = u.id
            WHERE a.club_id IN ($in_clause) OR a.club_id IS NULL
            ORDER BY a.created_at DESC
            LIMIT 5
        ");
        $ann_calc->execute($my_club_ids);
        $my_announcements = $ann_calc->fetchAll();
    }

    $club_remaining_budget = $club_approved_budget - $club_expenses;
?>
    <?php if (empty($my_clubs)): ?>
        <div class="card flash-alert flash-warning">
            <p>You have the <strong>Club Head</strong> role, but you are not currently assigned to head any student clubs. Please contact the Faculty Coordinator.</p>
        </div>
    <?php else: ?>
        <!-- Club Head Stat Grid -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo count($roster); ?></div>
                <div class="stat-label">Active Members</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--warning);">
                <div class="stat-value" style="color: var(--warning);"><?php echo count($pending_applications); ?></div>
                <div class="stat-label">Pending Join Requests</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid <?php echo $club_remaining_budget >= 0 ? 'var(--success)' : 'var(--error)'; ?>;">
                <div class="stat-value" style="color: <?php echo $club_remaining_budget >= 0 ? 'var(--success)' : 'var(--error)'; ?>;">
                    $<?php echo number_format($club_remaining_budget, 2); ?>
                </div>
                <div class="stat-label">Budget Remaining</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count($my_events); ?></div>
                <div class="stat-label">Club Events</div>
            </div>
        </div>

        <!-- Quick Actions Bar -->
        <div class="action-bar card" style="padding: var(--space-md) var(--space-lg); margin-bottom: var(--space-2xl);">
            <div>
                <h3 style="margin:0;">Club Operations Shortcuts</h3>
                <p style="margin:0; font-size: 0.9rem; color: var(--text-muted);">
                    Managing: <strong><?php echo e(implode(', ', array_column($my_clubs, 'name'))); ?></strong>
                </p>
            </div>
            <div style="display: flex; gap: var(--space-sm); flex-wrap: wrap;">
                <a href="event-form.php" class="btn btn-primary">+ Propose New Event</a>
                <a href="budget-form.php" class="btn btn-secondary">💰 Request Budget</a>
                <a href="expense-form.php" class="btn btn-secondary">🧾 Log Expense</a>
                <a href="announcement-form.php" class="btn btn-outline">📢 Post Notice</a>
            </div>
        </div>

        <!-- 1. Join Requests Approval Section -->
        <section class="dashboard-section card">
            <div class="action-bar" style="margin-bottom: var(--space-md);">
                <h2>Pending Student Join Requests (<?php echo count($pending_applications); ?>)</h2>
                <span class="badge badge-warning">Action Required</span>
            </div>

            <?php if (empty($pending_applications)): ?>
                <p style="color: var(--text-muted);">No pending membership requests right now. All applicant submissions have been reviewed.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Student Applicant</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Target Club</th>
                                <th>Applied On</th>
                                <th>Review Decisions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_applications as $app): ?>
                                <tr>
                                    <td><strong><?php echo e($app['applicant_name']); ?></strong></td>
                                    <td><?php echo e($app['applicant_email']); ?></td>
                                    <td><?php echo e($app['applicant_phone'] ?? 'N/A'); ?></td>
                                    <td><span class="badge badge-primary"><?php echo e($app['club_name']); ?></span></td>
                                    <td><?php echo date('M j, Y', strtotime($app['joined_at'])); ?></td>
                                    <td>
                                        <form action="membership-action.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="member_id" value="<?php echo (int)$app['id']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve Member</button>
                                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger" onclick="return confirm('Reject this student application?');">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- 2. My Club's Events Table -->
        <section class="dashboard-section card">
            <div class="action-bar" style="margin-bottom: var(--space-md);">
                <h2>My Club's Events (<?php echo count($my_events); ?>)</h2>
                <a href="event-form.php" class="btn btn-sm btn-primary">+ Propose Event</a>
            </div>

            <?php if (empty($my_events)): ?>
                <p style="color: var(--text-muted);">No events scheduled yet for your club. Click "+ Propose New Event" to create one.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Event Title</th>
                                <th>Schedule Date</th>
                                <th>Time</th>
                                <th>Venue</th>
                                <th>Faculty Status</th>
                                <th>Coordinator Feedback</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_events as $me): ?>
                                <tr>
                                    <td>
                                        <a href="event-detail.php?id=<?php echo (int)$me['id']; ?>"><strong><?php echo e($me['title']); ?></strong></a>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($me['event_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($me['start_time'])); ?> - <?php echo date('h:i A', strtotime($me['end_time'])); ?></td>
                                    <td><?php echo e($me['venue_name'] ?? 'TBA'); ?></td>
                                    <td><span class="badge badge-<?php echo e($me['status']); ?>"><?php echo e(ucfirst($me['status'])); ?></span></td>
                                    <td>
                                        <?php if (!empty($me['admin_remarks'])): ?>
                                            <small style="color: #854d0e;"><em><?php echo e($me['admin_remarks']); ?></em></small>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted);">&mdash;</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="event-detail.php?id=<?php echo (int)$me['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                        <a href="event-form.php?id=<?php echo (int)$me['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- 3. Club Budget & Remaining Funds Section -->
        <section class="dashboard-section card">
            <div class="action-bar" style="margin-bottom: var(--space-md);">
                <div>
                    <h2>Budget & Expense Management</h2>
                    <p style="margin:0; color:var(--text-muted); font-size:0.9rem;">
                        Approved: <strong>$<?php echo number_format($club_approved_budget, 2); ?></strong> | 
                        Spent: <strong>$<?php echo number_format($club_expenses, 2); ?></strong> | 
                        <strong>Remaining: $<?php echo number_format($club_remaining_budget, 2); ?></strong>
                    </p>
                </div>
                <div style="display:flex; gap:var(--space-sm);">
                    <a href="budget.php" class="btn btn-sm btn-outline">View Budget Details</a>
                    <a href="budget-form.php" class="btn btn-sm btn-primary">+ Request Budget</a>
                    <a href="expense-form.php" class="btn btn-sm btn-secondary">+ Log Expense</a>
                </div>
            </div>
        </section>

        <!-- 4. Club Roster -->
        <section class="dashboard-section card">
            <h2>Active Club Membership Roster (<?php echo count($roster); ?>)</h2>
            <?php if (empty($roster)): ?>
                <p style="color: var(--text-muted);">No approved student members yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Club</th>
                                <th>Joined Date</th>
                                <th>Manage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roster as $mb): ?>
                                <tr>
                                    <td><strong><?php echo e($mb['name']); ?></strong></td>
                                    <td><?php echo e($mb['email']); ?></td>
                                    <td><?php echo e($mb['phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo e($mb['club_name']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($mb['joined_at'])); ?></td>
                                    <td>
                                        <form action="membership-action.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;" onsubmit="return confirm('Remove student from membership roster?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="member_id" value="<?php echo (int)$mb['id']; ?>">
                                            <button type="submit" name="action" value="remove" class="btn btn-sm btn-outline">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

<?php
// =========================================================================
// 3. MEMBER DASHBOARD
// =========================================================================
elseif ($role === 'member'):
    // Query student's enrolled clubs
    $member_clubs_stmt = $pdo->prepare("
        SELECT c.*, cm.status AS membership_status, cm.joined_at
        FROM club_members cm
        JOIN clubs c ON cm.club_id = c.id
        WHERE cm.user_id = ?
        ORDER BY cm.joined_at DESC
    ");
    $member_clubs_stmt->execute([$user_id]);
    $my_enrolled_clubs = $member_clubs_stmt->fetchAll();

    // Collect approved club IDs for announcements feed
    $approved_club_ids = [];
    $approved_count = 0;
    $pending_count = 0;
    foreach ($my_enrolled_clubs as $mc) {
        if ($mc['membership_status'] === 'approved') {
            $approved_club_ids[] = (int)$mc['id'];
            $approved_count++;
        } elseif ($mc['membership_status'] === 'pending') {
            $pending_count++;
        }
    }

    // Query relevant announcements: College-wide (club_id IS NULL) + member's approved clubs
    $ann_sql = "
        SELECT a.*, c.name AS club_name, u.name AS author_name
        FROM announcements a
        LEFT JOIN clubs c ON a.club_id = c.id
        JOIN users u ON a.created_by = u.id
        WHERE a.club_id IS NULL
    ";
    $ann_params = [];
    if (!empty($approved_club_ids)) {
        $in_clause = implode(',', array_fill(0, count($approved_club_ids), '?'));
        $ann_sql .= " OR a.club_id IN ($in_clause)";
        $ann_params = $approved_club_ids;
    }
    $ann_sql .= " ORDER BY a.created_at DESC LIMIT 6";
    $m_ann_stmt = $pdo->prepare($ann_sql);
    $m_ann_stmt->execute($ann_params);
    $member_announcements = $m_ann_stmt->fetchAll();

    // Query upcoming sanctioned events
    $upcoming_events_stmt = $pdo->query("
        SELECT e.*, c.name AS club_name, c.category AS club_category, v.name AS venue_name
        FROM events e
        JOIN clubs c ON e.club_id = c.id
        LEFT JOIN venues v ON e.venue_id = v.id
        WHERE e.status = 'approved' AND e.event_date >= CURDATE()
        ORDER BY e.event_date ASC
        LIMIT 4
    ");
    $upcoming_events = $upcoming_events_stmt->fetchAll();
?>
    <!-- Member Stat Grid -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo $approved_count; ?></div>
            <div class="stat-label">Active Club Memberships</div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--warning);">
            <div class="stat-value" style="color: var(--warning);"><?php echo $pending_count; ?></div>
            <div class="stat-label">Pending Join Requests</div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--secondary);">
            <div class="stat-value" style="color: var(--secondary);"><?php echo count($upcoming_events); ?></div>
            <div class="stat-label">Upcoming Sanctioned Events</div>
        </div>
    </div>

    <!-- 1. Announcements Bulletin -->
    <section class="dashboard-section card">
        <div class="action-bar" style="margin-bottom: var(--space-md);">
            <h2>Campus Announcements & Club Bulletins</h2>
            <span class="badge badge-primary">Notice Board</span>
        </div>

        <?php if (empty($member_announcements)): ?>
            <p style="color: var(--text-muted);">No active announcements at this time.</p>
        <?php else: ?>
            <div style="display: grid; gap: var(--space-md); grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                <?php foreach ($member_announcements as $ma): ?>
                    <article class="card" style="margin:0; background: var(--background);">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.25rem;">
                            <?php if (empty($ma['club_name'])): ?>
                                <span class="badge badge-primary">📢 College-Wide</span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><?php echo e($ma['club_name']); ?></span>
                            <?php endif; ?>
                            <small style="color:var(--text-muted);"><?php echo date('M j, Y', strtotime($ma['created_at'])); ?></small>
                        </div>
                        <h4 style="margin: 0.35rem 0; color: var(--primary); font-size: 1.1rem;"><?php echo e($ma['title']); ?></h4>
                        <p style="font-size: 0.92rem; line-height: 1.5; margin-bottom: 0.5rem;"><?php echo nl2br(e($ma['content'])); ?></p>
                        <small style="color: var(--text-muted);">Posted by: <?php echo e($ma['author_name']); ?></small>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- 2. Enrolled Clubs Section -->
    <section class="dashboard-section card">
        <div class="action-bar" style="margin-bottom: var(--space-md);">
            <h2>My Club Memberships</h2>
            <a href="clubs.php" class="btn btn-sm btn-primary">+ Join Another Club</a>
        </div>

        <?php if (empty($my_enrolled_clubs)): ?>
            <p style="color: var(--text-muted);">
                You haven't applied to any clubs yet! Visit the <a href="clubs.php">Clubs Directory</a> to explore societies and apply for membership.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Club Name</th>
                            <th>Category</th>
                            <th>Membership Status</th>
                            <th>Applied / Enrolled Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_enrolled_clubs as $ec): ?>
                            <tr>
                                <td><strong><?php echo e($ec['name']); ?></strong></td>
                                <td><span class="badge badge-primary"><?php echo e($ec['category']); ?></span></td>
                                <td>
                                    <span class="badge badge-<?php echo $ec['membership_status'] === 'approved' ? 'success' : ($ec['membership_status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                        <?php echo e(ucfirst($ec['membership_status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($ec['joined_at'])); ?></td>
                                <td>
                                    <?php if ($ec['membership_status'] === 'approved'): ?>
                                        <form action="membership-action.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;" onsubmit="return confirm('Leave this club?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="leave">
                                            <input type="hidden" name="club_id" value="<?php echo (int)$ec['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline">Leave Club</button>
                                        </form>
                                    <?php elseif ($ec['membership_status'] === 'pending'): ?>
                                        <form action="membership-action.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="withdraw">
                                            <input type="hidden" name="club_id" value="<?php echo (int)$ec['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-secondary">Withdraw Application</button>
                                        </form>
                                    <?php elseif ($ec['membership_status'] === 'rejected'): ?>
                                        <form action="membership-action.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="apply">
                                            <input type="hidden" name="club_id" value="<?php echo (int)$ec['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-primary">Re-apply</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- 3. Upcoming Sanctioned Events -->
    <section class="dashboard-section card">
        <div class="action-bar" style="margin-bottom: var(--space-md);">
            <h2>Upcoming Sanctioned Campus Events</h2>
            <a href="events.php" class="btn btn-sm btn-outline">View Full Calendar</a>
        </div>

        <?php if (empty($upcoming_events)): ?>
            <p style="color: var(--text-muted);">No upcoming events scheduled right now. Check back soon!</p>
        <?php else: ?>
            <div class="card-grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
                <?php foreach ($upcoming_events as $ue): ?>
                    <article class="card event-card" style="margin: 0;">
                        <h3>
                            <a href="event-detail.php?id=<?php echo (int)$ue['id']; ?>" style="text-decoration: none; color: var(--primary);">
                                <?php echo e($ue['title']); ?>
                            </a>
                        </h3>
                        <p style="margin-bottom: 0.25rem;"><strong>Club:</strong> <?php echo e($ue['club_name']); ?></p>
                        <p style="margin-bottom: 0.25rem;"><strong>Date:</strong> <?php echo date('M j, Y', strtotime($ue['event_date'])); ?></p>
                        <p style="margin-bottom: 0.5rem;"><strong>Venue:</strong> <?php echo e($ue['venue_name'] ?? 'TBA'); ?></p>
                        <a href="event-detail.php?id=<?php echo (int)$ue['id']; ?>" class="btn btn-sm btn-outline">View Details</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
