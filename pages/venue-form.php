<?php
/**
 * ClubHub - Venue Create / Edit / Delete Form
 * Phase 6: Admin and Club Head form to create, edit, and delete campus venues.
 * Features strict server-side validation (runs with or without JS),
 * capacity boundary checks, unique name verification, and CSRF protection.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Requires logged-in Admin or Club Head
require_role(['admin', 'club_head']);

$user = current_user();
$is_admin = has_role('admin');

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$is_edit = $id > 0;

$venue = null;
if ($is_edit) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM venues WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $venue = $stmt->fetch();
    } catch (PDOException $e) {
        log_error('Failed to query venue: ' . $e->getMessage());
    }

    if (!$venue) {
        set_flash('danger', 'The requested venue was not found.');
        redirect('venues.php');
    }
}

// ---------------------------------------------------------
// POST Handling: Delete Action
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Security token expired. Please try again.');
        redirect('venues.php');
    }

    if (!$is_admin) {
        set_flash('danger', 'Only faculty coordinators (Admins) can delete campus venues.');
        redirect('venues.php');
    }

    try {
        $del_stmt = $pdo->prepare('DELETE FROM venues WHERE id = ?');
        $del_stmt->execute([$id]);
        set_flash('success', 'Venue "' . ($venue['name'] ?? 'Venue') . '" has been deleted.');
    } catch (PDOException $e) {
        log_error('Failed to delete venue: ' . $e->getMessage());
        set_flash('danger', 'Database error while deleting venue.');
    }
    redirect('venues.php');
}

// ---------------------------------------------------------
// POST Handling: Save (Create / Update) Action
// ---------------------------------------------------------
$errors = [];
$name = $venue['name'] ?? '';
$capacity = $venue['capacity'] ?? '';
$location = $venue['location'] ?? '';
$description = $venue['description'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] === 'save')) {
    // 1. Verify CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Security verification failed. Please try submitting again.';
    }

    // 2. Extract inputs
    $name = trim($_POST['name'] ?? '');
    $capacity_raw = trim($_POST['capacity'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // 3. Server-side validation (runs even if JS is disabled)
    if ($name === '') {
        $errors['name'] = 'Venue name is required.';
    } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors['name'] = 'Venue name must be between 2 and 100 characters.';
    } else {
        // Unique check
        try {
            $u_sql = 'SELECT id FROM venues WHERE LOWER(name) = LOWER(?)';
            $u_params = [$name];
            if ($is_edit) {
                $u_sql .= ' AND id != ?';
                $u_params[] = $id;
            }
            $u_stmt = $pdo->prepare($u_sql);
            $u_stmt->execute($u_params);
            if ($u_stmt->fetch()) {
                $errors['name'] = 'A venue with this name is already registered.';
            }
        } catch (PDOException $e) {
            log_error('Error checking venue uniqueness: ' . $e->getMessage());
        }
    }

    if ($capacity_raw === '' || !ctype_digit($capacity_raw) || (int)$capacity_raw <= 0) {
        $errors['capacity'] = 'Capacity must be a positive integer (at least 1 seat).';
    } elseif ((int)$capacity_raw > 50000) {
        $errors['capacity'] = 'Capacity cannot exceed 50,000 seats.';
    } else {
        $capacity = (int)$capacity_raw;
    }

    if ($location === '') {
        $errors['location'] = 'Campus location is required.';
    } elseif (mb_strlen($location) < 2 || mb_strlen($location) > 150) {
        $errors['location'] = 'Location description must be between 2 and 150 characters.';
    }

    // 4. Save to Database
    if (empty($errors)) {
        try {
            if ($is_edit) {
                $up_stmt = $pdo->prepare('UPDATE venues SET name = ?, capacity = ?, location = ?, description = ? WHERE id = ?');
                $up_stmt->execute([$name, $capacity, $location, $description, $id]);
                set_flash('success', 'Venue "' . $name . '" updated successfully!');
            } else {
                $in_stmt = $pdo->prepare('INSERT INTO venues (name, capacity, location, description) VALUES (?, ?, ?, ?)');
                $in_stmt->execute([$name, $capacity, $location, $description]);
                set_flash('success', 'Venue "' . $name . '" registered successfully!');
            }
            redirect('venues.php');
        } catch (PDOException $e) {
            log_error('Database error saving venue: ' . $e->getMessage());
            $errors['db'] = 'Failed to save venue due to a database error. Please review logs.';
        }
    }
}

$page_title = $is_edit ? 'Edit Venue: ' . ($venue['name'] ?? '') : 'Add New Venue';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <div class="action-bar">
        <div>
            <h1><?php echo $is_edit ? 'Edit Venue: ' . e($venue['name']) : 'Add New Campus Venue'; ?></h1>
            <p><?php echo $is_edit ? 'Update seating capacity, equipment specifications, or location.' : 'Register a campus auditorium, conference room, lab, or outdoor amphitheatre.'; ?></p>
        </div>
        <a href="venues.php" class="btn btn-secondary">Back to Venues</a>
    </div>
</section>

<section class="auth-card-section">
    <?php if (!empty($errors['csrf'])): ?>
        <aside class="flash-alert flash-danger" role="alert"><p><?php echo e($errors['csrf']); ?></p></aside>
    <?php endif; ?>
    <?php if (!empty($errors['db'])): ?>
        <aside class="flash-alert flash-danger" role="alert"><p><?php echo e($errors['db']); ?></p></aside>
    <?php endif; ?>

    <form class="form-card card" action="venue-form.php<?php echo $is_edit ? '?id=' . (int)$id : ''; ?>" method="POST" novalidate>
        <!-- CSRF Protection Token -->
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="save">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
        <?php endif; ?>

        <!-- Venue Name -->
        <p class="form-group">
            <label class="form-label" for="name">Venue Name: <span style="color:var(--error);">*</span></label>
            <input class="form-control <?php echo isset($errors['name']) ? 'input-error' : ''; ?>" type="text" id="name" name="name" value="<?php echo e($name); ?>" placeholder="e.g. Science Wing Auditorium" required maxlength="100">
            <?php if (isset($errors['name'])): ?>
                <span class="field-error-message"><?php echo e($errors['name']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Seating Capacity -->
        <p class="form-group">
            <label class="form-label" for="capacity">Seating Capacity (Number of Seats): <span style="color:var(--error);">*</span></label>
            <input class="form-control <?php echo isset($errors['capacity']) ? 'input-error' : ''; ?>" type="number" id="capacity" name="capacity" value="<?php echo e($capacity); ?>" placeholder="e.g. 150" min="1" max="50000" required>
            <?php if (isset($errors['capacity'])): ?>
                <span class="field-error-message"><?php echo e($errors['capacity']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Campus Location -->
        <p class="form-group">
            <label class="form-label" for="location">Campus Location: <span style="color:var(--error);">*</span></label>
            <input class="form-control <?php echo isset($errors['location']) ? 'input-error' : ''; ?>" type="text" id="location" name="location" value="<?php echo e($location); ?>" placeholder="e.g. Tech Block B, 2nd Floor" required maxlength="150">
            <?php if (isset($errors['location'])): ?>
                <span class="field-error-message"><?php echo e($errors['location']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Description -->
        <p class="form-group">
            <label class="form-label" for="description">Facilities & Equipment Description:</label>
            <textarea class="form-control <?php echo isset($errors['description']) ? 'input-error' : ''; ?>" id="description" name="description" rows="4" placeholder="Detail audiovisual gear, projectors, microphone setups, AC, and access instructions..."><?php echo e($description); ?></textarea>
            <?php if (isset($errors['description'])): ?>
                <span class="field-error-message"><?php echo e($errors['description']); ?></span>
            <?php endif; ?>
        </p>

        <p class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Update Venue' : 'Register Venue'; ?></button>
            <a href="venues.php" class="btn btn-secondary">Cancel</a>
        </p>
    </form>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

