<?php
/**
 * ClubHub - User Registration Page
 * Phase 7a: Dynamic user registration with default role 'member'.
 * Features server-side validation (runs even if JS is disabled),
 * unique email check via PDO prepared statements, secure password hashing,
 * and CSRF token protection.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$fullname = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Security verification failed. Please try submitting again.';
    }

    // 2. Extract and sanitize inputs
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    // 3. Server-side validation (works without JS)
    if ($fullname === '') {
        $errors['fullname'] = 'Full name is required.';
    } elseif (mb_strlen($fullname) < 2 || mb_strlen($fullname) > 100) {
        $errors['fullname'] = 'Full name must be between 2 and 100 characters.';
    }

    if ($email === '') {
        $errors['email'] = 'College email address is required.';
    } elseif (!is_valid_email($email)) {
        $errors['email'] = 'Please enter a valid email address (e.g. student@college.edu).';
    } else {
        // Check uniqueness in database
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors['email'] = 'An account with this email address is already registered.';
            }
        } catch (PDOException $e) {
            log_error('Error checking email uniqueness: ' . $e->getMessage());
            $errors['db'] = 'Database error while checking credentials.';
        }
    }

    if ($password === '') {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    }

    if ($confirm_password === '') {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }

    // 4. Create user if validation passed
    if (empty($errors)) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'member'; // Default role is member as specified

            $insert_stmt = $pdo->prepare('
                INSERT INTO users (name, email, password, role, phone)
                VALUES (?, ?, ?, ?, ?)
            ');
            $insert_stmt->execute([
                $fullname,
                $email,
                $hash,
                $role,
                $phone !== '' ? $phone : null
            ]);

            set_flash('success', 'Registration successful! You can now log in with your credentials.');
            redirect('login.php');
        } catch (PDOException $e) {
            log_error('Failed to register user: ' . $e->getMessage());
            $errors['db'] = 'Failed to create account due to a database error. Please try again.';
        }
    }
}

$page_title = 'Register Account';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <h1>Register New Student Account</h1>
    <p>Create your student profile to join college clubs, apply for event passes, and participate in campus activities.</p>
</section>

<section class="auth-card-section">
    <h2>Student Registration</h2>

    <?php if (!empty($errors['csrf'])): ?>
        <aside class="flash-alert flash-danger" role="alert"><p><?php echo e($errors['csrf']); ?></p></aside>
    <?php endif; ?>
    <?php if (!empty($errors['db'])): ?>
        <aside class="flash-alert flash-danger" role="alert"><p><?php echo e($errors['db']); ?></p></aside>
    <?php endif; ?>

    <form class="form-card card" action="register.php" method="POST" novalidate>
        <!-- CSRF Protection Token -->
        <?php echo csrf_field(); ?>

        <!-- Full Name -->
        <p class="form-group">
            <label class="form-label" for="fullname">Full Name: <span style="color:var(--error);">*</span></label>
            <input class="form-control <?php echo isset($errors['fullname']) ? 'input-error' : ''; ?>" type="text" id="fullname" name="fullname" value="<?php echo e($fullname); ?>" placeholder="e.g. John Doe" required autocomplete="name">
            <?php if (isset($errors['fullname'])): ?>
                <span class="field-error-message"><?php echo e($errors['fullname']); ?></span>
            <?php endif; ?>
        </p>

        <!-- College Email -->
        <p class="form-group">
            <label class="form-label" for="email">College Email Address: <span style="color:var(--error);">*</span></label>
            <input class="form-control <?php echo isset($errors['email']) ? 'input-error' : ''; ?>" type="email" id="email" name="email" value="<?php echo e($email); ?>" placeholder="e.g. j.doe@college.edu" required autocomplete="email">
            <?php if (isset($errors['email'])): ?>
                <span class="field-error-message"><?php echo e($errors['email']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Password -->
        <p class="form-group">
            <label class="form-label" for="password">Password: <span style="color:var(--error);">*</span></label>
            <input class="form-control <?php echo isset($errors['password']) ? 'input-error' : ''; ?>" type="password" id="password" name="password" placeholder="Minimum 8 characters" required minlength="8" autocomplete="new-password">
            <?php if (isset($errors['password'])): ?>
                <span class="field-error-message"><?php echo e($errors['password']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Confirm Password -->
        <p class="form-group">
            <label class="form-label" for="confirm_password">Confirm Password: <span style="color:var(--error);">*</span></label>
            <input class="form-control <?php echo isset($errors['confirm_password']) ? 'input-error' : ''; ?>" type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required minlength="8" autocomplete="new-password">
            <?php if (isset($errors['confirm_password'])): ?>
                <span class="field-error-message"><?php echo e($errors['confirm_password']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Contact Number -->
        <p class="form-group">
            <label class="form-label" for="phone">Contact Number (Optional):</label>
            <input class="form-control <?php echo isset($errors['phone']) ? 'input-error' : ''; ?>" type="tel" id="phone" name="phone" value="<?php echo e($phone); ?>" placeholder="e.g. 9876543210" autocomplete="tel">
            <?php if (isset($errors['phone'])): ?>
                <span class="field-error-message"><?php echo e($errors['phone']); ?></span>
            <?php endif; ?>
        </p>

        <p class="form-group">
            <span style="font-size: 0.9rem; color: var(--text-muted);">
                Account role: <strong>Club Member</strong> (access to join clubs, RSVP for events, and view student dashboard).
            </span>
        </p>

        <p class="form-actions">
            <button type="submit" class="btn btn-primary btn-block">Create Student Account</button>
        </p>
    </form>
</section>

<section class="card">
    <h2>Already Have an Account?</h2>
    <p>Already registered on ClubHub? <a href="login.php" class="btn-outline">Log in to your account</a>.</p>
    <p>Note: Faculty Coordinators (Admin) and Club Heads are appointed directly by Student Affairs administration.</p>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
