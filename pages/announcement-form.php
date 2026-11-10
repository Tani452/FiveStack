<?php
/**
 * ClubHub - Announcement Create & Delete Form
 * Phase 7b: Enables Admin to post college-wide bulletins and Club Heads to post club announcements.
 * Full server-side validation and CSRF token protection.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Requires logged-in Admin or Club Head
require_role(['admin', 'club_head']);

$user = current_user();
$user_id = (int)$user['id'];
$is_admin = has_role('admin');

// -------------------------------------------------------------
// POST Handling: Delete Action
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Security token expired. Please try again.');
        redirect('dashboard.php');
    }

    $ann_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($ann_id <= 0) {
        set_flash('danger', 'Invalid announcement specified.');
        redirect('dashboard.php');
    }

    try {
        $chk_stmt = $pdo->prepare('SELECT id, created_by FROM announcements WHERE id = ? LIMIT 1');
        $chk_stmt->execute([$ann_id]);
        $ann = $chk_stmt->fetch();

        if ($ann) {
            // Only Admin or the creator can delete
            if ($is_admin || (int)$ann['created_by'] === $user_id) {
                $del_stmt = $pdo->prepare('DELETE FROM announcements WHERE id = ?');
                $del_stmt->execute([$ann_id]);
                set_flash('success', 'Announcement removed successfully.');
            } else {
                set_flash('danger', 'Access denied. You can only remove your own announcements.');
            }
        }
    } catch (PDOException $e) {
        log_error('Failed to delete announcement: ' . $e->getMessage());
        set_flash('danger', 'Database error while removing announcement.');
    }
    redirect('dashboard.php');
}

// Fetch available target clubs for the posting user
try {
    if ($is_admin) {
        $c_stmt = $pdo->query('SELECT id, name FROM clubs WHERE status = "active" ORDER BY name ASC');
    } else {
        $c_stmt = $pdo->prepare('SELECT id, name FROM clubs WHERE head_id = ? AND status = "active" ORDER BY name ASC');
        $c_stmt->execute([$user_id]);
    }
    $available_clubs = $c_stmt->fetchAll();
} catch (PDOException $e) {
    log_error('Failed to load clubs for announcement: ' . $e->getMessage());
    $available_clubs = [];
}

// If club head has 0 clubs, cannot post
if (!$is_admin && empty($available_clubs)) {
    set_flash('danger', 'You are not assigned as Club Head for any active clubs.');
    redirect('dashboard.php');
}

// -------------------------------------------------------------
// POST Handling: Create Announcement Action
// -------------------------------------------------------------
$errors = [];
$title = '';
$content = '';
$target_club = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] === 'save')) {
    // 1. Verify CSRF
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Security verification failed. Please try again.';
    }

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $target_club = trim($_POST['club_id'] ?? '');

    // 2. Server-side validation
    if ($title === '') {
        $errors['title'] = 'Announcement title is required.';
    } elseif (mb_strlen($title) < 3 || mb_strlen($title) > 150) {
        $errors['title'] = 'Title must be between 3 and 150 characters.';
    }

    if ($content === '') {
        $errors['content'] = 'Announcement content is required.';
    } elseif (mb_strlen($content) < 10) {
        $errors['content'] = 'Content must be at least 10 characters long.';
    }

    $final_club_id = null;
    if ($target_club === '' || $target_club === 'global') {
        if (!$is_admin) {
            $errors['club_id'] = 'Only Faculty Coordinators (Admin) can post college-wide bulletins.';
        } else {
            $final_club_id = null;
        }
    } else {
        $cid = (int)$target_club;
        $found = false;
        foreach ($available_clubs as $ac) {
            if ((int)$ac['id'] === $cid) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $errors['club_id'] = 'You do not have permission to post for this club.';
        } else {
            $final_club_id = $cid;
        }
    }

    // 3. Save to database
    if (empty($errors)) {
        try {
            $ins_stmt = $pdo->prepare('
                INSERT INTO announcements (club_id, title, content, created_by)
                VALUES (?, ?, ?, ?)
            ');
            $ins_stmt->execute([$final_club_id, $title, $content, $user_id]);
            set_flash('success', 'Announcement published successfully!');
            redirect('dashboard.php');
        } catch (PDOException $e) {
            log_error('Error saving announcement: ' . $e->getMessage());
            $errors['db'] = 'Failed to publish announcement due to a database error.';
        }
    }
}

$page_title = 'Post New Announcement';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <div class="action-bar">
        <div>
            <h1>Post New Announcement</h1>
            <p><?php echo $is_admin ? 'Publish a campus-wide bulletin or post to an official student society.' : 'Publish a club notice for your registered members and prospective students.'; ?></p>
        </div>
        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>
</section>

<section class="auth-card-section">
    <?php if (!empty($errors['csrf'])): ?>
        <aside class="flash-alert flash-danger" role="alert"><p><?php echo e($errors['csrf']); ?></p></aside>
    <?php endif; ?>
    <?php if (!empty($errors['db'])): ?>
        <aside class="flash-alert flash-danger" role="alert"><p><?php echo e($errors['db']); ?></p></aside>
    <?php endif; ?>

    <form class="form-card card" action="announcement-form.php" method="POST" novalidate>
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="save">

        <!-- Target Destination -->
        <p class="form-group">
            <label class="form-label" for="club_id">Announcement Audience: <span style="color:var(--error);">*</span></label>
            <select class="form-control <?php echo isset($errors['club_id']) ? 'input-error' : ''; ?>" id="club_id" name="club_id" required>
                <?php if ($is_admin): ?>
                    <option value="global" <?php echo ($target_club === '' || $target_club === 'global') ? 'selected' : ''; ?>>
                        📢 College-Wide Bulletin (All Students & Faculty)
                    </option>
                <?php endif; ?>
                <?php foreach ($available_clubs as $ac): ?>
                    <option value="<?php echo (int)$ac['id']; ?>" <?php echo $target_club === (string)$ac['id'] ? 'selected' : ''; ?>>
                        🏛️ <?php echo e($ac['name']); ?> (Club Specific)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['club_id'])): ?>
                <span class="field-error-message"><?php echo e($errors['club_id']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Title -->
        <p class="form-group">
            <label class="form-label" for="title">Announcement Title: <span style="color:var(--error);">*</span></label>
            <input class="form-control <?php echo isset($errors['title']) ? 'input-error' : ''; ?>" type="text" id="title" name="title" value="<?php echo e($title); ?>" placeholder="e.g. Schedule Change for Upcoming Coding Workshop" required maxlength="150">
            <?php if (isset($errors['title'])): ?>
                <span class="field-error-message"><?php echo e($errors['title']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Content -->
        <p class="form-group">
            <label class="form-label" for="content">Announcement Message: <span style="color:var(--error);">*</span></label>
            <textarea class="form-control <?php echo isset($errors['content']) ? 'input-error' : ''; ?>" id="content" name="content" rows="6" placeholder="Provide full details, guidelines, meeting points, or registration steps..." required><?php echo e($content); ?></textarea>
            <?php if (isset($errors['content'])): ?>
                <span class="field-error-message"><?php echo e($errors['content']); ?></span>
            <?php endif; ?>
        </p>

        <p class="form-actions">
            <button type="submit" class="btn btn-primary">Publish Announcement</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </p>
    </form>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

