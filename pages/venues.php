<?php
/**
 * ClubHub - Campus Venues Directory
 * Phase 6: Dynamic venues list loaded from MySQL via PDO prepared statements.
 * Displays venue capacities, locations, and scheduled events count,
 * with role-based action controls for Admin and Club Heads.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$user = current_user();
$is_admin = has_role('admin');
$is_club_head = has_role('club_head');

// Fetch all venues with count of scheduled events
try {
    $stmt = $pdo->query('
        SELECT v.*, 
               (SELECT COUNT(*) FROM events e WHERE e.venue_id = v.id) AS total_events,
               (SELECT COUNT(*) FROM events e WHERE e.venue_id = v.id AND e.event_date >= CURDATE()) AS upcoming_events
        FROM venues v
        ORDER BY v.name ASC
    ');
    $venues = $stmt->fetchAll();
} catch (PDOException $e) {
    log_error('Failed to load venues: ' . $e->getMessage());
    $venues = [];
}

$page_title = 'Campus Venues';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <div class="action-bar">
        <div>
            <h1>Campus Venues & Facilities</h1>
            <p>Explore college auditoriums, seminar halls, computer labs, and open air spaces available for club events.</p>
        </div>
        <?php if ($is_admin || $is_club_head): ?>
            <a href="venue-form.php" class="btn btn-primary">+ Add New Venue</a>
        <?php endif; ?>
    </div>
</section>

<section class="venues-grid card-grid">
    <h2>Available Venues (<?php echo count($venues); ?> total)</h2>

    <?php if (empty($venues)): ?>
        <div class="card no-events-notice">
            <h3>No Venues Registered</h3>
            <p>There are no campus venues configured yet.</p>
            <?php if ($is_admin || $is_club_head): ?>
                <p><a href="venue-form.php" class="btn btn-primary">+ Register First Venue</a></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($venues as $venue): ?>
            <article class="card venue-card">
                <div class="action-bar" style="margin-bottom: var(--space-xs);">
                    <h3><?php echo e($venue['name']); ?></h3>
                    <span class="badge badge-secondary">Capacity: <?php echo (int)$venue['capacity']; ?> seats</span>
                </div>

                <p>
                    <strong>Location:</strong>
                    <?php echo e($venue['location']); ?>
                </p>

                <p>
                    <strong>Scheduled Events:</strong>
                    <?php echo (int)$venue['upcoming_events']; ?> upcoming
                    (<?php echo (int)$venue['total_events']; ?> total)
                </p>

                <p><?php echo nl2br(e($venue['description'] ?? '')); ?></p>

                <?php if ($is_admin || $is_club_head): ?>
                    <div class="card-actions">
                        <a href="venue-form.php?id=<?php echo (int)$venue['id']; ?>" class="btn btn-sm btn-outline">Edit Venue</a>

                        <?php if ($is_admin): ?>
                            <form action="venue-form.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;" onsubmit="return confirm('Are you sure you want to delete this venue? Any linked events will have their venue reset to TBA.');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$venue['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

