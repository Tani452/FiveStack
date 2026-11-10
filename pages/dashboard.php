<?php
/**
 * ClubHub - Role-Based User Dashboard
 * Phase 7a: Dynamic, role-differentiated dashboard views.
 * Admin (Faculty Coordinator), Club Head, and Member each see customized metrics,
 * tables, and authorized operations.
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
            <p>Welcome, <strong><?php echo e($user['name']); ?></strong> (<?php echo e($user['email']); ?>) &mdash; Manage your operations and campus club activities.</p>
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
    $total_venues = (int)$pdo->query("SELECT COUNT(*) FROM venues")->fetchColumn();
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

    // Query all clubs with head and membership counts
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
            <div class="stat-label">Pending Approvals</div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--success);">
            <div class="stat-value" style="color: var(--success);"><?php echo $approved_events; ?></div>
            <div class="stat-label">Sanctioned Events</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $total_venues; ?></div>
            <div class="stat-label">Campus Venues</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $total_students; ?></div>
            <div class="stat-label">Registered Students</div>
        </div>
    </div>

    <!-- Admin Quick Action Bar -->
    <div class="action-bar card" style="padding: var(--space-md) var(--space-lg); margin-bottom: var(--space-2xl);">
        <div>
            <h3 style="margin: 0;">Quick Operations</h3>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">Create and manage campus resources directly.</p>
        </div>
        <div style="display: flex; gap: var(--space-sm); flex-wrap: wrap;">
            <a href="club-form.php" class="btn btn-primary">+ Create New Club</a>
            <a href="event-form.php" class="btn btn-primary">+ Schedule Event</a>
            <a href="venue-form.php" class="btn btn-secondary">+ Add Venue</a>
        </div>
    </div>

    <!-- Pending Approvals Table -->
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
                            <th>Requested Venue</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_events_list as $pe): ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($pe['title']); ?></strong>
                                </td>
                                <td><?php echo e($pe['club_name']); ?></td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($pe['event_date'])); ?><br>
                                    <small style="color: var(--text-muted);"><?php echo date('h:i A', strtotime($pe['start_time'])); ?> - <?php echo date('h:i A', strtotime($pe['end_time'])); ?></small>
                                </td>
                                <td><?php echo e($pe['venue_name'] ?? 'TBA'); ?></td>
                                <td>
                                    <a href="event-form.php?id=<?php echo (int)$pe['id']; ?>" class="btn btn-sm btn-primary">Review / Sanction</a>
                                    <a href="event-detail.php?id=<?php echo (int)$pe['id']; ?>" class="btn btn-sm btn-outline">Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- Managed Clubs Table -->
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
                        <th>Assigned Club Head</th>
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
                                <form action="club-form.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;" onsubmit="return confirm('Are you sure you want to delete this club?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$ac['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
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

    // Events organized by this club head's clubs
    $my_events = [];
    if (!empty($my_club_ids)) {
        $in_clause = implode(',', array_fill(0, count($my_club_ids), '?'));
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

        // Query roster / members
        $roster_stmt = $pdo->prepare("
            SELECT cm.*, u.name, u.email, u.phone, c.name AS club_name
            FROM club_members cm
            JOIN users u ON cm.user_id = u.id
            JOIN clubs c ON cm.club_id = c.id
            WHERE cm.club_id IN ($in_clause)
            ORDER BY cm.joined_at DESC
        ");
        $roster_stmt->execute($my_club_ids);
        $roster = $roster_stmt->fetchAll();
    } else {
        $roster = [];
    }

    $approved_member_count = 0;
    $pending_member_count = 0;
    foreach ($roster as $m) {
        if ($m['status'] === 'approved') $approved_member_count++;
        if ($m['status'] === 'pending') $pending_member_count++;
    }
?>
    <?php if (empty($my_clubs)): ?>
        <div class="card flash-alert flash-warning">
            <p>You have the <strong>Club Head</strong> role, but you are not currently assigned to head any student clubs. Please contact the Faculty Coordinator.</p>
        </div>
    <?php else: ?>
        <!-- Club Head Stat Grid -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo count($my_clubs); ?></div>
                <div class="stat-label">Assigned Clubs</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--success);">
                <div class="stat-value" style="color: var(--success);"><?php echo $approved_member_count; ?></div>
                <div class="stat-label">Active Members</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid var(--warning);">
                <div class="stat-value" style="color: var(--warning);"><?php echo $pending_member_count; ?></div>
                <div class="stat-label">Pending Applications</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo count($my_events); ?></div>
                <div class="stat-label">Club Events</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="action-bar card" style="padding: var(--space-md) var(--space-lg); margin-bottom: var(--space-2xl);">
            <div>
                <h3 style="margin:0;">Club Operations</h3>
                <p style="margin:0; font-size: 0.9rem; color: var(--text-muted);">
                    Managing: <strong><?php echo e(implode(', ', array_column($my_clubs, 'name'))); ?></strong>
                </p>
            </div>
            <div style="display: flex; gap: var(--space-sm); flex-wrap: wrap;">
                <a href="event-form.php" class="btn btn-primary">+ Propose New Event</a>
                <?php if (!empty($my_clubs[0])): ?>
                    <a href="club-form.php?id=<?php echo (int)$my_clubs[0]['id']; ?>" class="btn btn-secondary">Edit Club Profile</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- My Club's Events Table -->
        <section class="dashboard-section card">
            <div class="action-bar" style="margin-bottom: var(--space-md);">
                <h2>My Club's Events (<?php echo count($my_events); ?>)</h2>
                <a href="event-form.php" class="btn btn-sm btn-primary">+ New Event</a>
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
                                <th>Sanction Status</th>
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
                                        <a href="event-detail.php?id=<?php echo (int)$me['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                        <a href="event-form.php?id=<?php echo (int)$me['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                                        <form action="event-form.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;" onsubmit="return confirm('Cancel this event?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int)$me['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <!-- Member Roster -->
        <section class="dashboard-section card">
            <h2>Club Membership Roster (<?php echo count($roster); ?>)</h2>
            <?php if (empty($roster)): ?>
                <p style="color: var(--text-muted);">No student memberships registered yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Club</th>
                                <th>Membership Status</th>
                                <th>Joined Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roster as $mb): ?>
                                <tr>
                                    <td><strong><?php echo e($mb['name']); ?></strong></td>
                                    <td><?php echo e($mb['email']); ?></td>
                                    <td><?php echo e($mb['phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo e($mb['club_name']); ?></td>
                                    <td><span class="badge badge-<?php echo $mb['status'] === 'approved' ? 'success' : 'warning'; ?>"><?php echo e(ucfirst($mb['status'])); ?></span></td>
                                    <td><?php echo date('M j, Y', strtotime($mb['joined_at'])); ?></td>
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
            <div class="stat-value"><?php echo count($my_enrolled_clubs); ?></div>
            <div class="stat-label">Enrolled Clubs</div>
        </div>
        <div class="stat-card" style="border-left: 4px solid var(--secondary);">
            <div class="stat-value" style="color: var(--secondary);"><?php echo count($upcoming_events); ?></div>
            <div class="stat-label">Upcoming Events</div>
        </div>
    </div>

    <!-- Member Quick Links -->
    <div class="action-bar card" style="padding: var(--space-md) var(--space-lg); margin-bottom: var(--space-2xl);">
        <div>
            <h3 style="margin:0;">Student Portal</h3>
            <p style="margin:0; font-size: 0.9rem; color: var(--text-muted);">Explore extracurricular societies, participate in workshops, and attend campus competitions.</p>
        </div>
        <div style="display: flex; gap: var(--space-sm); flex-wrap: wrap;">
            <a href="clubs.php" class="btn btn-primary">Browse All Clubs</a>
            <a href="events.php" class="btn btn-secondary">Explore Events Calendar</a>
        </div>
    </div>

    <!-- Enrolled Clubs Section -->
    <section class="dashboard-section card">
        <div class="action-bar" style="margin-bottom: var(--space-md);">
            <h2>My Club Memberships</h2>
            <a href="clubs.php" class="btn btn-sm btn-outline">+ Join Another Club</a>
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
                            <th>Enrolled Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_enrolled_clubs as $ec): ?>
                            <tr>
                                <td><strong><?php echo e($ec['name']); ?></strong></td>
                                <td><span class="badge badge-primary"><?php echo e($ec['category']); ?></span></td>
                                <td>
                                    <span class="badge badge-<?php echo $ec['membership_status'] === 'approved' ? 'success' : 'warning'; ?>">
                                        <?php echo e(ucfirst($ec['membership_status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($ec['joined_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- Upcoming Events Calendar -->
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
