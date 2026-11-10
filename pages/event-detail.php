<?php
/**
 * ClubHub - Event Detail Page
 * Phase 6: Full event details view showing club, venue, schedule, and description.
 * Loaded from MySQL via PDO prepared statements with role-based editing controls.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    set_flash('danger', 'Invalid event selected.');
    redirect('events.php');
}

try {
    $stmt = $pdo->prepare('
        SELECT e.*, 
               c.name AS club_name, c.category AS club_category, c.head_id AS club_head_id,
               v.name AS venue_name, v.location AS venue_location, v.capacity AS venue_capacity, v.description AS venue_desc,
               u.name AS creator_name, u.email AS creator_email
        FROM events e
        JOIN clubs c ON e.club_id = c.id
        LEFT JOIN venues v ON e.venue_id = v.id
        LEFT JOIN users u ON e.created_by = u.id
        WHERE e.id = ?
        LIMIT 1
    ');
    $stmt->execute([$id]);
    $event = $stmt->fetch();
} catch (PDOException $e) {
    log_error('Failed to load event details: ' . $e->getMessage());
    $event = null;
}

if (!$event) {
    set_flash('danger', 'The requested event could not be found or has been removed.');
    redirect('events.php');
}

$user = current_user();
$is_admin = has_role('admin');
$can_edit = $is_admin || ($user && ((int)$event['created_by'] === (int)$user['id'] || (int)$event['club_head_id'] === (int)$user['id']));

$formatted_date = date('l, F j, Y', strtotime($event['event_date']));
$formatted_start = date('h:i A', strtotime($event['start_time']));
$formatted_end = date('h:i A', strtotime($event['end_time']));

$page_title = $event['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <div class="action-bar">
        <div>
            <p style="margin: 0; color: var(--text-muted);">
                <a href="events.php" style="color: var(--secondary); text-decoration: none;">&larr; Back to Events Schedule</a>
            </p>
            <h1><?php echo e($event['title']); ?></h1>
        </div>
        <div>
            <span class="badge badge-<?php echo e($event['status']); ?>" style="font-size: 0.95rem; padding: 0.4rem 0.9rem;">
                <?php echo e(ucfirst($event['status'])); ?>
            </span>
        </div>
    </div>
</section>

<article class="event-detail-card">
    <div class="detail-header">
        <p style="font-size: 1.1rem; color: var(--text-muted); margin-bottom: var(--space-xs);">
            Presented by <strong><?php echo e($event['club_name']); ?></strong>
            <span class="badge badge-primary" style="margin-left: var(--space-xs);"><?php echo e($event['club_category']); ?></span>
        </p>
    </div>

    <!-- Metadata Grid -->
    <div class="detail-meta-grid">
        <div class="detail-meta-item">
            <span class="detail-meta-label">Event Date</span>
            <span class="detail-meta-value">
                <time datetime="<?php echo e($event['event_date']); ?>"><?php echo e($formatted_date); ?></time>
            </span>
        </div>

        <div class="detail-meta-item">
            <span class="detail-meta-label">Time & Duration</span>
            <span class="detail-meta-value"><?php echo e($formatted_start); ?> &mdash; <?php echo e($formatted_end); ?></span>
        </div>

        <div class="detail-meta-item">
            <span class="detail-meta-label">Campus Venue</span>
            <span class="detail-meta-value">
                <?php if (!empty($event['venue_name'])): ?>
                    <?php echo e($event['venue_name']); ?>
                <?php else: ?>
                    <em>TBA</em>
                <?php endif; ?>
            </span>
        </div>

        <div class="detail-meta-item">
            <span class="detail-meta-label">Venue Location</span>
            <span class="detail-meta-value">
                <?php if (!empty($event['venue_location'])): ?>
                    <?php echo e($event['venue_location']); ?>
                    <?php if (!empty($event['venue_capacity'])): ?>
                        <small style="color: var(--text-muted);">(Max <?php echo (int)$event['venue_capacity']; ?> seats)</small>
                    <?php endif; ?>
                <?php else: ?>
                    <em>Location details pending</em>
                <?php endif; ?>
            </span>
        </div>

        <div class="detail-meta-item">
            <span class="detail-meta-label">Event Coordinator</span>
            <span class="detail-meta-value">
                <?php echo e($event['creator_name'] ?? 'Faculty Coordinator'); ?>
                <?php if (!empty($event['creator_email'])): ?>
                    <br><small style="color: var(--text-muted);"><?php echo e($event['creator_email']); ?></small>
                <?php endif; ?>
            </span>
        </div>
    </div>

    <!-- Full Event Description -->
    <section class="detail-section">
        <h2>About This Event</h2>
        <p><?php echo nl2br(e($event['description'])); ?></p>
    </section>

    <?php if (!empty($event['admin_remarks'])): ?>
        <section class="detail-section detail-remarks">
            <h2 style="color: #854d0e; font-size: 1.1rem; margin-bottom: 0.25rem;">Faculty Coordinator Remarks:</h2>
            <p style="margin: 0; color: #713f12;"><?php echo nl2br(e($event['admin_remarks'])); ?></p>
        </section>
    <?php endif; ?>

    <!-- Action Toolbar -->
    <div class="detail-actions">
        <a href="events.php" class="btn btn-secondary">&larr; All Events</a>

        <?php if ($can_edit): ?>
            <a href="event-form.php?id=<?php echo (int)$event['id']; ?>" class="btn btn-primary">Edit Event</a>

            <form action="event-form.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;" onsubmit="return confirm('Are you sure you want to delete this event?');">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int)$event['id']; ?>">
                <button type="submit" class="btn btn-danger">Delete Event</button>
            </form>
        <?php endif; ?>

        <?php if (is_logged_in()): ?>
            <span class="badge badge-success" style="padding: 0.6rem 1rem; font-size: 0.85rem;">Registered for Student Attendance</span>
        <?php else: ?>
            <a href="login.php" class="btn btn-outline">Log in to Register for this Event</a>
        <?php endif; ?>
    </div>
</article>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

