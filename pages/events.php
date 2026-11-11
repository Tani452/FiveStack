<?php
/**
 * ClubHub - Events Directory Page
 * Phase 6: Dynamic campus events directory loaded from MySQL via PDO prepared statements.
 * Supports server-side search and category filtering (works without JS),
 * instant client-side filtering via data-attributes, and role-based action controls.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$user = current_user();
$is_admin = has_role('admin');
$is_club_head = has_role('club_head');

$search = trim($_GET['event_search'] ?? '');
$category = trim($_GET['event_category'] ?? '');

// Fetch distinct club categories for the dropdown filter
try {
    $cat_stmt = $pdo->query('SELECT DISTINCT category FROM clubs WHERE category IS NOT NULL AND category != "" ORDER BY category ASC');
    $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    log_error('Failed to load categories for events: ' . $e->getMessage());
    $categories = ['Technical', 'Cultural', 'Literary', 'Sports', 'Media'];
}

$current_user_id = is_logged_in() ? (int)$user['id'] : 0;

// Build query
$sql = 'SELECT e.*, c.name AS club_name, c.category AS club_category, c.head_id AS club_head_id,
               v.name AS venue_name, v.location AS venue_location, v.capacity AS venue_capacity,
               u.name AS creator_name,
               (SELECT COUNT(*) FROM rsvps r WHERE r.event_id = e.id AND r.status = "attending") AS rsvp_count,
               (SELECT COUNT(*) FROM rsvps r WHERE r.event_id = e.id AND r.user_id = ? AND r.status = "attending") AS user_rsvped
        FROM events e
        JOIN clubs c ON e.club_id = c.id
        LEFT JOIN venues v ON e.venue_id = v.id
        LEFT JOIN users u ON e.created_by = u.id
        WHERE 1=1';
$params = [$current_user_id];

// Regular visitors only see approved events; admin and club heads can view all to manage them
if (!$is_admin && !$is_club_head) {
    $sql .= " AND e.status = 'approved'";
}

// Search keyword filter (title, description, club name, venue name)
if ($search !== '') {
    $sql .= ' AND (e.title LIKE ? OR e.description LIKE ? OR c.name LIKE ? OR v.name LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

// Category filter
if ($category !== '') {
    $sql .= ' AND LOWER(c.category) = LOWER(?)';
    $params[] = $category;
}

$sql .= ' ORDER BY e.event_date ASC, e.start_time ASC';

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll();
} catch (PDOException $e) {
    log_error('Failed to fetch events: ' . $e->getMessage());
    $events = [];
}

$page_title = 'Campus Events';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Phase 9: React Event Calendar Widget Styles -->
<link rel="stylesheet" href="<?php echo e($base_url); ?>assets/react/calendar-widget.css">

<section class="page-header">
    <div class="action-bar">
        <div>
            <h1>Upcoming College Events</h1>
            <p>Browse schedule, venue information, and registration details for all sanctioned club events and campus workshops.</p>
        </div>
        <?php if ($is_admin || $is_club_head): ?>
            <a href="event-form.php" class="btn btn-primary">+ Create New Event</a>
        <?php endif; ?>
    </div>
</section>

<!-- Phase 9: React Event Calendar Widget Mount Point -->
<div id="calendar-root"></div>

<section class="filter-section">
    <h2>Search & Filter Events</h2>
    <form class="filter-form" action="events.php" method="GET">
        <p class="form-group">
            <label class="form-label" for="event_search">Search Event:</label>
            <input class="form-control" type="text" id="event_search" name="event_search" value="<?php echo e($search); ?>" placeholder="Type event name or keyword...">
        </p>
        <p class="form-group">
            <label class="form-label" for="event_category">Category:</label>
            <select class="form-control" id="event_category" name="event_category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo e($cat); ?>" <?php echo strcasecmp($category, $cat) === 0 ? 'selected' : ''; ?>>
                        <?php echo e($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p class="form-actions">
            <button type="submit" class="btn btn-primary">Filter Schedule</button>
            <a href="events.php" class="btn btn-secondary">Clear</a>
        </p>
    </form>
</section>

<section class="events-grid card-grid">
    <h2>Scheduled Events (<?php echo count($events); ?> found)</h2>

    <?php if (empty($events)): ?>
        <div class="card no-events-notice">
            <h3>No Matching Events Found</h3>
            <p>Try searching with a different keyword, date, or selecting "All Categories".</p>
            <p><a href="events.php" class="btn btn-outline">View All Events</a></p>
        </div>
    <?php else: ?>
        <?php foreach ($events as $evt): ?>
            <?php
            $evt_date = date('F j, Y', strtotime($evt['event_date']));
            $evt_start = date('h:i A', strtotime($evt['start_time']));
            $evt_end = date('h:i A', strtotime($evt['end_time']));
            $can_edit = $is_admin || ($user && ((int)$evt['created_by'] === (int)$user['id'] || (int)$evt['club_head_id'] === (int)$user['id']));
            ?>
            <article class="card event-card" data-category="<?php echo e(strtolower($evt['club_category'])); ?>">
                <div class="action-bar" style="margin-bottom: var(--space-xs);">
                    <h3>
                        <a href="event-detail.php?id=<?php echo (int)$evt['id']; ?>" style="color: var(--primary); text-decoration: none;">
                            <?php echo e($evt['title']); ?>
                        </a>
                    </h3>
                    <span class="badge badge-primary"><?php echo e($evt['club_category']); ?></span>
                </div>

                <p><strong>Organized By:</strong> <?php echo e($evt['club_name']); ?></p>

                <p>
                    <strong>Date:</strong>
                    <time datetime="<?php echo e($evt['event_date']); ?>"><?php echo e($evt_date); ?></time> |
                    <strong>Time:</strong> <?php echo e($evt_start); ?> - <?php echo e($evt_end); ?>
                </p>

                <p>
                    <strong>Venue:</strong>
                    <?php if (!empty($evt['venue_name'])): ?>
                        <?php echo e($evt['venue_name']); ?>
                        <?php if (!empty($evt['venue_capacity'])): ?>
                            (Capacity: <?php echo (int)$evt['venue_capacity']; ?>)
                        <?php endif; ?>
                    <?php else: ?>
                        <em style="color: var(--text-muted);">To Be Announced</em>
                    <?php endif; ?>
                </p>

                <p>
                    <strong>Status:</strong>
                    <span class="badge badge-<?php echo e($evt['status']); ?>">
                        <?php echo e(ucfirst($evt['status'])); ?>
                    </span>
                    <?php if ($evt['status'] === 'approved'): ?>
                        <span style="font-size: 0.85rem; color: var(--text-muted);">(Sanctioned by Coordinator)</span>
                    <?php endif; ?>
                </p>

                <p class="rsvp-status-line">
                    <strong>Attendance:</strong>
                    <span class="rsvp-counter" id="rsvp-count-<?php echo (int)$evt['id']; ?>"><?php echo (int)$evt['rsvp_count']; ?></span> Students Registered
                </p>

                <p><?php echo nl2br(e(mb_strimwidth($evt['description'] ?? '', 0, 150, '...'))); ?></p>

                <div class="card-actions">
                    <a href="event-detail.php?id=<?php echo (int)$evt['id']; ?>" class="btn btn-sm btn-outline">View Details</a>

                    <?php if (is_logged_in() && $evt['status'] === 'approved'): ?>
                        <button type="button" class="btn btn-sm <?php echo (int)$evt['user_rsvped'] > 0 ? 'btn-success' : 'btn-outline'; ?> btn-rsvp" data-event-id="<?php echo (int)$evt['id']; ?>">
                            <?php echo (int)$evt['user_rsvped'] > 0 ? 'Attending ✓ (Cancel)' : 'RSVP / Attend'; ?>
                        </button>
                    <?php elseif (!is_logged_in()): ?>
                        <a href="login.php" class="btn btn-sm btn-outline">Log in to RSVP</a>
                    <?php endif; ?>

                    <?php if ($can_edit): ?>
                        <a href="event-form.php?id=<?php echo (int)$evt['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                    <?php endif; ?>

                    <?php if ($is_admin || $can_edit): ?>
                        <form action="event-form.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;" onsubmit="return confirm('Are you sure you want to cancel/delete this event?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo (int)$evt['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<!-- Phase 9: React Event Calendar Widget Bundle -->
<script src="<?php echo e($base_url); ?>assets/react/calendar-widget.js" defer></script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
