<?php
/**
 * ClubHub - Expense Logging Form with Receipt Upload
 * Phase 7b: Enables Club Heads to record expenses linked to approved budget requests.
 * Features strict server-side validation, secure receipt upload (images & PDF only, max 2 MB,
 * safe sanitized file naming), and CSRF token verification.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Requires logged-in Admin or Club Head
require_role(['admin', 'club_head']);

$user = current_user();
$user_id = (int)$user['id'];
$is_admin = has_role('admin');

// Fetch clubs available to the user
try {
    if ($is_admin) {
        $c_stmt = $pdo->query('SELECT id, name FROM clubs WHERE status = "active" ORDER BY name ASC');
    } else {
        $c_stmt = $pdo->prepare('SELECT id, name FROM clubs WHERE head_id = ? AND status = "active" ORDER BY name ASC');
        $c_stmt->execute([$user_id]);
    }
    $available_clubs = $c_stmt->fetchAll();
} catch (PDOException $e) {
    log_error('Failed to load clubs for expense form: ' . $e->getMessage());
    $available_clubs = [];
}

if (!$is_admin && empty($available_clubs)) {
    set_flash('danger', 'You are not assigned as Club Head for any active clubs.');
    redirect('dashboard.php');
}

$selected_club_id = isset($_REQUEST['club_id']) ? (int)$_REQUEST['club_id'] : (int)($available_clubs[0]['id'] ?? 0);

// Fetch approved budget requests for the selected club
$approved_requests = [];
try {
    $br_stmt = $pdo->prepare('
        SELECT br.*, 
               COALESCE((SELECT SUM(e.amount) FROM expenses e WHERE e.budget_request_id = br.id), 0) AS spent
        FROM budget_requests br
        WHERE br.club_id = ? AND br.status = "approved"
        ORDER BY br.created_at DESC
    ');
    $br_stmt->execute([$selected_club_id]);
    $approved_requests = $br_stmt->fetchAll();
} catch (PDOException $e) {
    log_error('Failed to load approved budget requests: ' . $e->getMessage());
}

$errors = [];
$title = '';
$budget_request_id = isset($_REQUEST['budget_request_id']) ? (int)$_REQUEST['budget_request_id'] : '';
$amount = '';
$expense_date = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Security verification failed. Please try again.';
    }

    $selected_club_id = (int)($_POST['club_id'] ?? 0);
    $budget_request_id = !empty($_POST['budget_request_id']) ? (int)$_POST['budget_request_id'] : null;
    $title = trim($_POST['title'] ?? '');
    $amount_raw = trim($_POST['amount'] ?? '');
    $expense_date = trim($_POST['expense_date'] ?? '');

    // Validate club authorization
    $valid_club = false;
    foreach ($available_clubs as $ac) {
        if ((int)$ac['id'] === $selected_club_id) {
            $valid_club = true;
            break;
        }
    }
    if (!$valid_club) {
        $errors['club_id'] = 'You do not have permission to log expenses for this club.';
    }

    if ($title === '') {
        $errors['title'] = 'Expense title is required.';
    } elseif (mb_strlen($title) < 3 || mb_strlen($title) > 150) {
        $errors['title'] = 'Title must be between 3 and 150 characters.';
    }

    if ($amount_raw === '' || !is_numeric($amount_raw) || (float)$amount_raw <= 0) {
        $errors['amount'] = 'Please enter a valid positive expense amount.';
    } else {
        $amount = number_format((float)$amount_raw, 2, '.', '');
    }

    if ($expense_date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expense_date) || !strtotime($expense_date)) {
        $errors['expense_date'] = 'Please enter a valid expense date in YYYY-MM-DD format.';
    }

    // Validate budget request if provided
    if (!empty($budget_request_id)) {
        $found_br = false;
        foreach ($approved_requests as $ar) {
            if ((int)$ar['id'] === $budget_request_id) {
                $found_br = true;
                break;
            }
        }
        if (!$found_br) {
            $errors['budget_request_id'] = 'Selected budget request is invalid or not approved for this club.';
        }
    }

    // 2. Receipt Upload Validation (Images and PDF only, max 2 MB, safe file name)
    $saved_receipt_filename = null;
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['receipt'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['receipt'] = 'File upload error (Code: ' . $file['error'] . '). Please try again.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors['receipt'] = 'Receipt file size exceeds the 2 MB maximum limit.';
        } else {
            // Check file extension
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'];

            if (!in_array($ext, $allowed_exts, true)) {
                $errors['receipt'] = 'Invalid file type. Only JPG, PNG, WEBP, GIF images and PDF documents are accepted.';
            } else {
                // Check MIME type using finfo
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                $allowed_mimes = [
                    'image/jpeg', 'image/pjpeg', 'image/png',
                    'image/webp', 'image/gif', 'application/pdf'
                ];

                if (!in_array($mime, $allowed_mimes, true)) {
                    $errors['receipt'] = 'The uploaded file is not a valid image or PDF document.';
                } else {
                    // Generate safe, collision-resistant filename
                    $safe_filename = 'receipt_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                    $target_dir = __DIR__ . '/../uploads/receipts';
                    if (!is_dir($target_dir)) {
                        @mkdir($target_dir, 0777, true);
                    }
                    $target_path = $target_dir . '/' . $safe_filename;

                    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                        $errors['receipt'] = 'Failed to move uploaded receipt to server storage.';
                    } else {
                        $saved_receipt_filename = $safe_filename;
                    }
                }
            }
        }
    } else {
        $errors['receipt'] = 'Please upload a receipt (JPG, PNG, or PDF, max 2 MB) to substantiate this expense.';
    }

    // 3. Save to database
    if (empty($errors)) {
        try {
            $ins_stmt = $pdo->prepare('
                INSERT INTO expenses (club_id, budget_request_id, title, amount, receipt_file, expense_date, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $ins_stmt->execute([
                $selected_club_id,
                $budget_request_id,
                $title,
                $amount,
                $saved_receipt_filename,
                $expense_date,
                $user_id
            ]);

            set_flash('success', 'Expense of $' . $amount . ' for "' . $title . '" has been successfully logged with receipt!');
            redirect('budget.php');
        } catch (PDOException $e) {
            log_error('Error saving expense: ' . $e->getMessage());
            $errors['db'] = 'Failed to log expense due to a database error.';
        }
    }
}

$page_title = 'Log Club Expense';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <div class="action-bar">
        <div>
            <h1>Log Club Expense</h1>
            <p>Record a club expenditure with receipt proof against your approved budget allocations.</p>
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

    <form class="form-card card" action="expense-form.php" method="POST" enctype="multipart/form-data" novalidate>
        <?php echo csrf_field(); ?>

        <!-- Club Selection -->
        <p class="form-group">
            <label class="form-label" for="club_id">Club: <span style="color:var(--error);">*</span></label>
            <select class="form-control <?php echo isset($errors['club_id']) ? 'input-error' : ''; ?>" id="club_id" name="club_id" onchange="window.location.href='expense-form.php?club_id='+this.value;" required>
                <?php foreach ($available_clubs as $ac): ?>
                    <option value="<?php echo (int)$ac['id']; ?>" <?php echo $selected_club_id === (int)$ac['id'] ? 'selected' : ''; ?>>
                        <?php echo e($ac['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['club_id'])): ?>
                <span class="field-error-message"><?php echo e($errors['club_id']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Linked Budget Request -->
        <p class="form-group">
            <label class="form-label" for="budget_request_id">Linked Approved Budget Request:</label>
            <select class="form-control <?php echo isset($errors['budget_request_id']) ? 'input-error' : ''; ?>" id="budget_request_id" name="budget_request_id">
                <option value="">-- General Club Operating Expense (No specific request) --</option>
                <?php foreach ($approved_requests as $ar): ?>
                    <?php
                    $rem = (float)$ar['amount'] - (float)$ar['spent'];
                    ?>
                    <option value="<?php echo (int)$ar['id']; ?>" <?php echo (int)$budget_request_id === (int)$ar['id'] ? 'selected' : ''; ?>>
                        <?php echo e($ar['title']); ?> (Allocated: $<?php echo number_format((float)$ar['amount'], 2); ?> | Remaining: $<?php echo number_format($rem, 2); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['budget_request_id'])): ?>
                <span class="field-error-message"><?php echo e($errors['budget_request_id']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Expense Title -->
        <p class="form-group">
            <label class="form-label" for="title">Expense Title: <span style="color:var(--error);">*</span></label>
            <input class="form-control <?php echo isset($errors['title']) ? 'input-error' : ''; ?>" type="text" id="title" name="title" value="<?php echo e($title); ?>" placeholder="e.g. Cloud Server Hosting Vouchers" required maxlength="150">
            <?php if (isset($errors['title'])): ?>
                <span class="field-error-message"><?php echo e($errors['title']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Expense Amount & Date -->
        <div style="display:flex; gap:var(--space-md); flex-wrap:wrap;">
            <p class="form-group" style="flex:1; min-width: 140px;">
                <label class="form-label" for="amount">Amount ($ USD): <span style="color:var(--error);">*</span></label>
                <input class="form-control <?php echo isset($errors['amount']) ? 'input-error' : ''; ?>" type="number" step="0.01" min="0.01" id="amount" name="amount" value="<?php echo e($amount); ?>" placeholder="e.g. 120.00" required>
                <?php if (isset($errors['amount'])): ?>
                    <span class="field-error-message"><?php echo e($errors['amount']); ?></span>
                <?php endif; ?>
            </p>

            <p class="form-group" style="flex:1; min-width: 140px;">
                <label class="form-label" for="expense_date">Expense Date: <span style="color:var(--error);">*</span></label>
                <input class="form-control <?php echo isset($errors['expense_date']) ? 'input-error' : ''; ?>" type="date" id="expense_date" name="expense_date" value="<?php echo e($expense_date); ?>" required>
                <?php if (isset($errors['expense_date'])): ?>
                    <span class="field-error-message"><?php echo e($errors['expense_date']); ?></span>
                <?php endif; ?>
            </p>
        </div>

        <!-- Receipt File Upload -->
        <p class="form-group">
            <label class="form-label" for="receipt">Receipt File (Image or PDF, max 2 MB): <span style="color:var(--error);">*</span></label>
            <input class="form-control <?php echo isset($errors['receipt']) ? 'input-error' : ''; ?>" type="file" id="receipt" name="receipt" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf" required>
            <small style="color:var(--text-muted); display:block; margin-top:0.25rem;">
                Accepted formats: JPG, PNG, WEBP, GIF, PDF. Max file size: 2 MB. File names are safely sanitized.
            </small>
            <?php if (isset($errors['receipt'])): ?>
                <span class="field-error-message"><?php echo e($errors['receipt']); ?></span>
            <?php endif; ?>
        </p>

        <p class="form-actions">
            <button type="submit" class="btn btn-primary">Log Expense & Upload Receipt</button>
            <a href="budget.php" class="btn btn-secondary">Cancel</a>
        </p>
    </form>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

