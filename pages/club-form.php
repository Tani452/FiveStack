<?php
/**
 * ClubHub - Club Create / Edit / Delete Form
 * Phase 6: Admin and Club Head form to create, edit, and delete clubs.
 * Implements strict server-side validation (runs with or without JS),
 * CSRF protection, and role-based permissions.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Protect page: strictly requires logged-in Admin or Club Head
require_role(['admin', 'club_head']);

$user = current_user();
$is_admin = has_role('admin');

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$is_edit = $id > 0;

$club = null;
if ($is_edit) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM clubs WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $club = $stmt->fetch();
    } catch (PDOException $e) {
        log_error('Failed to query club: ' . $e->getMessage());
    }

    if (!$club) {
        set_flash('danger', 'The requested club was not found.');
        redirect('clubs.php');
    }

    // If logged in as club_head, they can only edit their own club
    if (!$is_admin && (int)$club['head_id'] !== (int)$user['id']) {
        set_flash('danger', 'Access denied. You can only edit clubs where you are assigned as Club Head.');
        redirect('clubs.php');
    }
} else {
    // Only admins can create new clubs
    if (!$is_admin) {
        set_flash('danger', 'Only faculty coordinators (Admins) can create new student clubs.');
        redirect('clubs.php');
    }
}

// ---------------------------------------------------------
// POST Handling: Delete Action
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Invalid security token. Please try again.');
        redirect('clubs.php');
    }

    if (!$is_admin) {
        set_flash('danger', 'Only faculty coordinators (Admins) can delete student clubs.');
        redirect('clubs.php');
    }

    try {
        $del_stmt = $pdo->prepare('DELETE FROM clubs WHERE id = ?');
        $del_stmt->execute([$id]);
        set_flash('success', 'Club "' . ($club['name'] ?? 'Club') . '" has been deleted successfully.');
    } catch (PDOException $e) {
        log_error('Failed to delete club: ' . $e->getMessage());
        set_flash('danger', 'Database error while deleting club. Please check error logs.');
    }
    redirect('clubs.php');
}

// ---------------------------------------------------------
// POST Handling: Save (Create / Update) Action
// ---------------------------------------------------------
$errors = [];
$name = $club['name'] ?? '';
$category = $club['category'] ?? 'Technical';
$description = $club['description'] ?? '';
$head_id = $club['head_id'] ?? '';
$status = $club['status'] ?? 'active';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] === 'save')) {
    // 1. Verify CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Security verification failed. Please try submitting again.';
    }

    // 2. Extract and sanitize inputs
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if ($is_admin) {
        $head_id = !empty($_POST['head_id']) ? (int)$_POST['head_id'] : null;
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive'], true) ? $_POST['status'] : 'active';
    }

    // 3. Server-side validation (runs even if JS is disabled)
    if ($name === '') {
        $errors['name'] = 'Club name is required.';
    } elseif (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
        $errors['name'] = 'Club name must be between 3 and 100 characters.';
    } else {
        // Check uniqueness of club name
        try {
            $unique_sql = 'SELECT id FROM clubs WHERE LOWER(name) = LOWER(?)';
            $unique_params = [$name];
            if ($is_edit) {
                $unique_sql .= ' AND id != ?';
                $unique_params[] = $id;
            }
            $unique_stmt = $pdo->prepare($unique_sql);
            $unique_stmt->execute($unique_params);
            if ($unique_stmt->fetch()) {
                $errors['name'] = 'A club with this name already exists. Please choose a unique name.';
            }
        } catch (PDOException $e) {
            log_error('Error checking club name uniqueness: ' . $e->getMessage());
        }
    }

    if ($category === '') {
        $errors['category'] = 'Please select a valid club category.';
    }

    if ($description === '') {
        $errors['description'] = 'Club description is required.';
    } elseif (mb_strlen($description) < 10) {
        $errors['description'] = 'Description must be at least 10 characters long.';
    }

    // Validate head_id if specified
    if ($is_admin && !empty($head_id)) {
        try {
            $user_stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
            $user_stmt->execute([$head_id]);
            if (!$user_stmt->fetch()) {
                $errors['head_id'] = 'The selected student/faculty member was not found in the database.';
            }
        } catch (PDOException $e) {
            log_error('Error validating head user: ' . $e->getMessage());
        }
    }

    // 4. Save to Database if no errors
    if (empty($errors)) {
        try {
            if ($is_edit) {
                if ($is_admin) {
                    $up_stmt = $pdo->prepare('UPDATE clubs SET name = ?, category = ?, description = ?, head_id = ?, status = ? WHERE id = ?');
                    $up_stmt->execute([$name, $category, $description, $head_id, $status, $id]);
                } else {
                    // Club Head can update name, category, and description
                    $up_stmt = $pdo->prepare('UPDATE clubs SET name = ?, category = ?, description = ? WHERE id = ?');
                    $up_stmt->execute([$name, $category, $description, $id]);
                }
                set_flash('success', 'Club "' . $name . '" has been updated successfully!');
            } else {
                // Admin create
                $in_stmt = $pdo->prepare('INSERT INTO clubs (name, category, description, head_id, status) VALUES (?, ?, ?, ?, ?)');
                $in_stmt->execute([$name, $category, $description, $head_id, $status]);
                set_flash('success', 'New club "' . $name . '" has been created successfully!');
            }
            redirect('clubs.php');
        } catch (PDOException $e) {
            log_error('Database error saving club: ' . $e->getMessage());
            $errors['db'] = 'Failed to save club record due to a database error. Please review logs.';
        }
    }
}

// Fetch potential club heads for Admin selector
$potential_heads = [];
if ($is_admin) {
    try {
        $h_stmt = $pdo->query("SELECT id, name, email, role FROM users WHERE role IN ('club_head', 'member', 'admin') ORDER BY name ASC");
        $potential_heads = $h_stmt->fetchAll();
    } catch (PDOException $e) {
        log_error('Failed to load potential heads: ' . $e->getMessage());
    }
}

$page_title = $is_edit ? 'Edit Club: ' . ($club['name'] ?? '') : 'Create New Club';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <div class="action-bar">
        <div>
            <h1><?php echo $is_edit ? 'Edit Club: ' . e($club['name']) : 'Create New Club'; ?></h1>
            <p><?php echo $is_edit ? 'Update club details, category, or assigned leadership.' : 'Register a new student club or society with official campus status.'; ?></p>
        </div>
        <a href="clubs.php" class="btn btn-secondary">Back to Clubs</a>
    </div>
</section>

<section class="auth-card-section">
    <?php if (!empty($errors['csrf'])): ?>
        <aside class="flash-alert flash-danger" role="alert"><p><?php echo e($errors['csrf']); ?></p></aside>
    <?php endif; ?>
    <?php if (!empty($errors['db'])): ?>
        <aside class="flash-alert flash-danger" role="alert"><p><?php echo e($errors['db']); ?></p></aside>
    <?php endif; ?>

    <form class="form-card card" action="club-form.php<?php echo $is_edit ? '?id=' . (int)$id : ''; ?>" method="POST" novalidate>
        <!-- CSRF Protection Token -->
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="save">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
        <?php endif; ?>

        <!-- Club Name -->
        <p class="form-group">
            <label class="form-label" for="name">Club Name: <span style="color:var(--error);">*</span></label>
            <input class="form-control <?php echo isset($errors['name']) ? 'input-error' : ''; ?>" type="text" id="name" name="name" value="<?php echo e($name); ?>" placeholder="e.g. Artificial Intelligence Society" required maxlength="100">
            <?php if (isset($errors['name'])): ?>
                <span class="field-error-message"><?php echo e($errors['name']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Category -->
        <p class="form-group">
            <label class="form-label" for="category">Category: <span style="color:var(--error);">*</span></label>
            <select class="form-control <?php echo isset($errors['category']) ? 'input-error' : ''; ?>" id="category" name="category" required>
                <?php
                $available_cats = ['Technical', 'Cultural', 'Literary', 'Sports', 'Media', 'Academic', 'Social Service'];
                foreach ($available_cats as $cat):
                ?>
                    <option value="<?php echo e($cat); ?>" <?php echo strcasecmp($category, $cat) === 0 ? 'selected' : ''; ?>>
                        <?php echo e($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['category'])): ?>
                <span class="field-error-message"><?php echo e($errors['category']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Description -->
        <p class="form-group">
            <label class="form-label" for="description">Club Description: <span style="color:var(--error);">*</span></label>
            <textarea class="form-control <?php echo isset($errors['description']) ? 'input-error' : ''; ?>" id="description" name="description" rows="5" placeholder="Provide a summary of the club's objectives, regular activities, and student engagement..." required><?php echo e($description); ?></textarea>
            <?php if (isset($errors['description'])): ?>
                <span class="field-error-message"><?php echo e($errors['description']); ?></span>
            <?php endif; ?>
        </p>

        <?php if ($is_admin): ?>
            <!-- Assigned Club Head (Admin Only) -->
            <p class="form-group">
                <label class="form-label" for="head_id">Assigned Club Head:</label>
                <select class="form-control <?php echo isset($errors['head_id']) ? 'input-error' : ''; ?>" id="head_id" name="head_id">
                    <option value="">-- No Club Head Assigned --</option>
                    <?php foreach ($potential_heads as $p_user): ?>
                        <option value="<?php echo (int)$p_user['id']; ?>" <?php echo (string)$head_id === (string)$p_user['id'] ? 'selected' : ''; ?>>
                            <?php echo e($p_user['name']); ?> (<?php echo e($p_user['email']); ?> - <?php echo e($p_user['role']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['head_id'])): ?>
                    <span class="field-error-message"><?php echo e($errors['head_id']); ?></span>
                <?php endif; ?>
            </p>

            <!-- Status (Admin Only) -->
            <p class="form-group">
                <label class="form-label" for="status">Club Status:</label>
                <select class="form-control" id="status" name="status">
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </p>
        <?php endif; ?>

        <p class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Update Club Details' : 'Create Club'; ?></button>
            <a href="clubs.php" class="btn btn-secondary">Cancel</a>
        </p>
    </form>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

