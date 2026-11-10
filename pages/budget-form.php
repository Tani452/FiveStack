<?php
/**
 * ClubHub - Budget Request Form
 * Phase 7b: Enables Club Heads to submit budget requests and Admins to review, approve, or reject them.
 * Includes server-side validation and CSRF token protection.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Requires logged-in Admin or Club Head
require_role(['admin', 'club_head']);

$user = current_user();
$user_id = (int)$user['id'];
$is_admin = has_role('admin');

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$is_edit = $id > 0;

$budget_req = null;
if ($is_edit) {
    try {
        $stmt = $pdo->prepare('
            SELECT br.*, c.name AS club_name, c.head_id 
            FROM budget_requests br 
            JOIN clubs c ON br.club_id = c.id 
            WHERE br.id = ? 
            LIMIT 1
        ');
        $stmt->execute([$id]);
        $budget_req = $stmt->fetch();
    } catch (PDOException $e) {
        log_error('Failed to load budget request: ' . $e->getMessage());
    }

    if (!$budget_req) {
        set_flash('danger', 'Budget request not found.');
        redirect('budget.php');
    }

    // Authorization
    if (!$is_admin && (int)$budget_req['head_id'] !== $user_id && (int)$budget_req['created_by'] !== $user_id) {
        set_flash('danger', 'Access denied. You do not have permission for this budget request.');
        redirect('budget.php');
    }
}

// Fetch available clubs for the user
try {
    if ($is_admin) {
        $c_stmt = $pdo->query('SELECT id, name FROM clubs WHERE status = "active" ORDER BY name ASC');
    } else {
        $c_stmt = $pdo->prepare('SELECT id, name FROM clubs WHERE head_id = ? AND status = "active" ORDER BY name ASC');
        $c_stmt->execute([$user_id]);
    }
    $available_clubs = $c_stmt->fetchAll();
} catch (PDOException $e) {
    log_error('Failed to load clubs for budget request: ' . $e->getMessage());
    $available_clubs = [];
}

if (!$is_admin && empty($available_clubs)) {
    set_flash('danger', 'You are not assigned as Club Head for any active clubs.');
    redirect('dashboard.php');
}

// -------------------------------------------------------------
// POST Handling: Save (Create / Update) Action
// -------------------------------------------------------------
$errors = [];
$title = $budget_req['title'] ?? '';
$club_id = $budget_req['club_id'] ?? ($available_clubs[0]['id'] ?? '');
$amount = $budget_req['amount'] ?? '';
$description = $budget_req['description'] ?? '';
$status = $budget_req['status'] ?? ($is_admin ? 'approved' : 'pending');
$admin_remarks = $budget_req['admin_remarks'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Security verification failed. Please try submitting again.';
    }

    $title = trim($_POST['title'] ?? '');
    $club_id = (int)($_POST['club_id'] ?? 0);
    $amount_raw = trim($_POST['amount'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($is_admin) {
        $status = in_array($_POST['status'] ?? '', ['pending', 'approved', 'rejected'], true) ? $_POST['status'] : 'approved';
        $admin_remarks = trim($_POST['admin_remarks'] ?? '');
    }

    // Validation
    if ($title === '') {
        $errors['title'] = 'Request title is required.';
    } elseif (mb_strlen($title) < 3 || mb_strlen($title) > 150) {
        $errors['title'] = 'Title must be between 3 and 150 characters.';
    }

    if ($club_id <= 0) {
        $errors['club_id'] = 'Please select a club.';
    } else {
        $valid_club = false;
        foreach ($available_clubs as $ac) {
            if ((int)$ac['id'] === $club_id) {
                $valid_club = true;
                break;
            }
        }
        if (!$valid_club) {
            $errors['club_id'] = 'You do not have permission for this club.';
        }
    }

    if ($amount_raw === '' || !is_numeric($amount_raw) || (float)$amount_raw <= 0) {
        $errors['amount'] = 'Please enter a valid positive dollar amount (e.g. 250.00).';
    } else {
        $amount = number_format((float)$amount_raw, 2, '.', '');
    }

    if ($description === '') {
        $errors['description'] = 'Please provide an itemized justification or description.';
    } elseif (mb_strlen($description) < 10) {
        $errors['description'] = 'Description must be at least 10 characters long.';
    }

    if (empty($errors)) {
        try {
            if ($is_edit) {
                if ($is_admin) {
                    $up_sql = 'UPDATE budget_requests SET club_id = ?, title = ?, amount = ?, description = ?, status = ?, admin_remarks = ? WHERE id = ?';
                    $stmt = $pdo->prepare($up_sql);
                    $stmt->execute([$club_id, $title, $amount, $description, $status, $admin_remarks, $id]);
                } else {
                    $up_sql = 'UPDATE budget_requests SET club_id = ?, title = ?, amount = ?, description = ? WHERE id = ?';
                    $stmt = $pdo->prepare($up_sql);
                    $stmt->execute([$club_id, $title, $amount, $description, $id]);
                }
                set_flash('success', 'Budget request updated successfully!');
            } else {
                $in_sql = 'INSERT INTO budget_requests (club_id, title, amount, description, status, admin_remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)';
                $stmt = $pdo->prepare($in_sql);
                $stmt->execute([$club_id, $title, $amount, $description, $status, $admin_remarks, $user_id]);
                set_flash('success', 'Budget request for $' . $amount . ' submitted successfully!');
            }
            redirect('budget.php');
        } catch (PDOException $e) {
            log_error('Error saving budget request: ' . $e->getMessage());
            $errors['db'] = 'Failed to save budget request due to a database error.';
        }
    }
}

$page_title = $is_edit ? 'Review Budget Request' : 'Raise Budget Request';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <div class="action-bar">
        <div>
            <h1><?php echo $is_edit ? 'Review Budget Request' : 'Raise Club Budget Request'; ?></h1>
            <p><?php echo $is_edit ? 'Review funding justification or update request status.' : 'Submit a funding request for club equipment, hackathons, or event supplies.'; ?></p>
        </div>
        <a href="budget.php" class="btn btn-secondary">Back to Budget</a>
    </div>
</section>

<section class="auth-card-section">
    <?php if (!empty($errors['csrf'])): ?>
        <aside class="flash-alert flash-danger" role="alert"><p><?php echo e($errors['csrf']); ?></p></aside>
    <?php endif; ?>
    <?php if (!empty($errors['db'])): ?>
        <aside class="flash-alert flash-danger" role="alert"><p><?php echo e($errors['db']); ?></p></aside>
    <?php endif; ?>

    <form class="form-card card" action="budget-form.php<?php echo $is_edit ? '?id=' . (int)$id : ''; ?>" method="POST" novalidate>
        <?php echo csrf_field(); ?>
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
        <?php endif; ?>

        <!-- Club -->
        <p class="form-group">
            <label class="form-label" for="club_id">Club: <span style="color:var(--error);">*</span></label>
            <select class="form-control <?php echo isset($errors['club_id']) ? 'input-error' : ''; ?>" id="club_id" name="club_id" required>
                <?php foreach ($available_clubs as $ac): ?>
                    <option value="<?php echo (int)$ac['id']; ?>" <?php echo (int)$club_id === (int)$ac['id'] ? 'selected' : ''; ?>>
                        <?php echo e($ac['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['club_id'])): ?>
                <span class="field-error-message"><?php echo e($errors['club_id']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Title -->
        <p class="form-group">
            <label class="form-label" for="title">Request Title: <span style="color:var(--error);">*</span></label>
            <input class="form-control <?php echo isset($errors['title']) ? 'input-error' : ''; ?>" type="text" id="title" name="title" value="<?php echo e($title); ?>" placeholder="e.g. Refreshments & Server Credits for Annual Hackathon" required maxlength="150">
            <?php if (isset($errors['title'])): ?>
                <span class="field-error-message"><?php echo e($errors['title']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Amount -->
        <p class="form-group">
            <label class="form-label" for="amount">Requested Amount ($ USD): <span style="color:var(--error);">*</span></label>
            <input class="form-control <?php echo isset($errors['amount']) ? 'input-error' : ''; ?>" type="number" step="0.01" min="1" id="amount" name="amount" value="<?php echo e($amount); ?>" placeholder="e.g. 450.00" required>
            <?php if (isset($errors['amount'])): ?>
                <span class="field-error-message"><?php echo e($errors['amount']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Description -->
        <p class="form-group">
            <label class="form-label" for="description">Itemized Justification & Purpose: <span style="color:var(--error);">*</span></label>
            <textarea class="form-control <?php echo isset($errors['description']) ? 'input-error' : ''; ?>" id="description" name="description" rows="5" placeholder="Detail budget breakdown, vendor estimates, and event requirements..." required><?php echo e($description); ?></textarea>
            <?php if (isset($errors['description'])): ?>
                <span class="field-error-message"><?php echo e($errors['description']); ?></span>
            <?php endif; ?>
        </p>

        <?php if ($is_admin): ?>
            <!-- Admin Sanction Controls -->
            <p class="form-group">
                <label class="form-label" for="status">Decision Status:</label>
                <select class="form-control" id="status" name="status">
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </p>

            <p class="form-group">
                <label class="form-label" for="admin_remarks">Faculty Coordinator Remarks / Terms:</label>
                <textarea class="form-control" id="admin_remarks" name="admin_remarks" rows="2" placeholder="Approval conditions, allocation accounts, or rejection justification..."><?php echo e($admin_remarks); ?></textarea>
            </p>
        <?php else: ?>
            <p>
                <strong>Current Status:</strong>
                <span class="badge badge-<?php echo e($status); ?>"><?php echo e(ucfirst($status)); ?></span>
            </p>
        <?php endif; ?>

        <p class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Update Request' : 'Submit Budget Request'; ?></button>
            <a href="budget.php" class="btn btn-secondary">Cancel</a>
        </p>
    </form>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

