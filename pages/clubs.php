<?php
/**
 * ClubHub - Clubs Directory Page
 * Phase 6: Dynamic clubs catalog loaded from MySQL database via PDO prepared statements.
 * Supports server-side search and category filtering (works without JS),
 * active member counters, and role-based action controls for Admin and Club Heads.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

// Fetch distinct categories from database for dropdown filter
try {
    $cat_stmt = $pdo->query('SELECT DISTINCT category FROM clubs WHERE category IS NOT NULL AND category != "" ORDER BY category ASC');
    $categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    log_error('Failed to load club categories: ' . $e->getMessage());
    $categories = ['Technical', 'Cultural', 'Literary', 'Sports', 'Media'];
}

// Build dynamic PDO query with prepared statements
$sql = 'SELECT c.*, u.name AS head_name, u.email AS head_email,
               (SELECT COUNT(*) FROM club_members cm WHERE cm.club_id = c.id AND cm.status = "approved") AS member_count
        FROM clubs c
        LEFT JOIN users u ON c.head_id = u.id
        WHERE 1=1';
$params = [];

// Filter by search keyword (name or description)
if ($search !== '') {
    $sql .= ' AND (c.name LIKE ? OR c.description LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

// Filter by category
if ($category !== '') {
    $sql .= ' AND LOWER(c.category) = LOWER(?)';
    $params[] = $category;
}

$sql .= ' ORDER BY c.name ASC';

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clubs = $stmt->fetchAll();
} catch (PDOException $e) {
    log_error('Failed to fetch clubs from database: ' . $e->getMessage());
    $clubs = [];
}

$user = current_user();
$is_admin = has_role('admin');

// Fetch memberships for currently logged-in user
$user_memberships = [];
if (is_logged_in()) {
    try {
        $m_stmt = $pdo->prepare('SELECT club_id, status FROM club_members WHERE user_id = ?');
        $m_stmt->execute([(int)$user['id']]);
        $rows = $m_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $user_memberships[(int)$r['club_id']] = $r['status'];
        }
    } catch (PDOException $e) {
        log_error('Failed to load user memberships: ' . $e->getMessage());
    }
}

$page_title = 'Clubs Directory';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <div class="action-bar">
        <div>
            <h1>College Clubs Directory</h1>
            <p>Discover student organizations, technical societies, cultural clubs, and sports teams across the campus.</p>
        </div>
        <?php if ($is_admin): ?>
            <a href="club-form.php" class="btn btn-primary">+ Create New Club</a>
        <?php endif; ?>
    </div>
</section>

<section class="filter-section">
    <h2>Filter Clubs</h2>
    <form class="filter-form" action="clubs.php" method="GET">
        <p class="form-group">
            <label class="form-label" for="search">Search Club Name or Description:</label>
            <input class="form-control" type="text" id="search" name="search" value="<?php echo e($search); ?>" placeholder="e.g. Coding, Robotics, Debate">
        </p>
        <p class="form-group">
            <label class="form-label" for="category">Category:</label>
            <select class="form-control" id="category" name="category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo e($cat); ?>" <?php echo strcasecmp($category, $cat) === 0 ? 'selected' : ''; ?>>
                        <?php echo e($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p class="form-actions">
            <button type="submit" class="btn btn-primary">Filter Directory</button>
            <a href="clubs.php" class="btn btn-secondary">Reset</a>
        </p>
    </form>
</section>

<section class="clubs-grid card-grid">
    <h2>Active Student Clubs (<?php echo count($clubs); ?> found)</h2>

    <?php if (empty($clubs)): ?>
        <div class="card no-events-notice">
            <h3>No Clubs Found</h3>
            <p>No student clubs matched your search criteria. Try selecting "All Categories" or clearing the search text.</p>
            <p><a href="clubs.php" class="btn btn-outline">View All Clubs</a></p>
        </div>
    <?php else: ?>
        <?php foreach ($clubs as $club): ?>
            <?php
            $is_my_club = ($user && $club['head_id'] && (int)$user['id'] === (int)$club['head_id']);
            $can_manage_club = $is_admin || $is_my_club;
            ?>
            <article class="card club-card" data-category="<?php echo e(strtolower($club['category'])); ?>">
                <div class="action-bar" style="margin-bottom: var(--space-xs);">
                    <h3><?php echo e($club['name']); ?></h3>
                    <span class="badge badge-primary"><?php echo e($club['category']); ?></span>
                </div>

                <p>
                    <strong>Status:</strong>
                    <span class="badge badge-<?php echo $club['status'] === 'active' ? 'success' : 'danger'; ?>">
                        <?php echo e(ucfirst($club['status'])); ?>
                    </span>
                </p>

                <p>
                    <strong>Club Head:</strong>
                    <?php if (!empty($club['head_name'])): ?>
                        <?php echo e($club['head_name']); ?>
                        <?php if ($is_my_club): ?>
                            <span class="badge badge-role badge-club_head">(You)</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <em style="color: var(--text-muted);">Not Assigned</em>
                    <?php endif; ?>
                </p>

                <p>
                    <strong>Registered Members:</strong>
                    <?php echo (int)$club['member_count']; ?> Student<?php echo (int)$club['member_count'] === 1 ? '' : 's'; ?>
                </p>

                <p><?php echo nl2br(e($club['description'])); ?></p>

                <div class="card-actions">
                    <?php if ($can_manage_club): ?>
                        <a href="club-form.php?id=<?php echo (int)$club['id']; ?>" class="btn btn-sm btn-outline">Edit Club</a>
                    <?php endif; ?>

                    <?php if ($is_admin): ?>
                        <form action="club-form.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;" onsubmit="return confirm('Are you sure you want to delete this club? All associated events and memberships will also be affected.');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo (int)$club['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    <?php endif; ?>

                    <?php if (!$can_manage_club): ?>
                        <?php if (is_logged_in()): ?>
                            <?php
                            $club_id_int = (int)$club['id'];
                            $m_status = $user_memberships[$club_id_int] ?? null;
                            ?>
                            <?php if ($m_status === 'approved'): ?>
                                <span class="badge badge-success" style="align-self: center;">Active Member</span>
                                <form action="membership-action.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;" onsubmit="return confirm('Are you sure you want to leave this club?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="leave">
                                    <input type="hidden" name="club_id" value="<?php echo $club_id_int; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline">Leave Club</button>
                                </form>
                            <?php elseif ($m_status === 'pending'): ?>
                                <span class="badge badge-warning" style="align-self: center;">Application Under Review</span>
                                <form action="membership-action.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="withdraw">
                                    <input type="hidden" name="club_id" value="<?php echo $club_id_int; ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary">Withdraw</button>
                                </form>
                            <?php elseif ($m_status === 'rejected'): ?>
                                <span class="badge badge-danger" style="align-self: center;">Application Rejected</span>
                                <form action="membership-action.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="apply">
                                    <input type="hidden" name="club_id" value="<?php echo $club_id_int; ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">Re-apply</button>
                                </form>
                            <?php else: ?>
                                <form action="membership-action.php" method="POST" style="display:inline; margin:0; padding:0; border:none; box-shadow:none; background:transparent;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="apply">
                                    <input type="hidden" name="club_id" value="<?php echo $club_id_int; ?>">
                                    <button type="submit" class="btn btn-sm btn-primary">+ Apply to Join</button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="login.php" class="btn-outline">Log in to Apply for Membership</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
