<?php
/**
 * ClubHub - User Login Page
 * Phase 6: Dynamic authentication against MySQL database using PDO prepared statements.
 * Features server-side validation (runs with or without JS), CSRF protection,
 * and demo credentials helper for easy testing.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// If already logged in, redirect to clubs directory
if (is_logged_in()) {
    redirect('clubs.php');
}

$error = '';
$email_val = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $error = 'Security check failed. Please refresh and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $email_val = $email;

        // 2. Server-side validation
        if (empty($email) || empty($password)) {
            $error = 'Please enter both your college email address and password.';
        } elseif (!is_valid_email($email)) {
            $error = 'Please enter a valid email address format.';
        } else {
            // 3. Query user via PDO prepared statement
            $stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // 4. Verify password hash
            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID for session fixation security
                session_regenerate_id(true);

                // Store user details in session
                $_SESSION['user'] = [
                    'id'    => $user['id'],
                    'name'  => $user['name'],
                    'email' => $user['email'],
                    'role'  => $user['role'],
                ];

                set_flash('success', 'Welcome back, ' . $user['name'] . ' (' . ucfirst($user['role']) . ')!');
                
                // Redirect to return page or clubs
                $redirect_to = $_GET['redirect'] ?? 'clubs.php';
                redirect($redirect_to);
            } else {
                $error = 'Invalid email address or password. Please verify your credentials.';
            }
        }
    }
}

$page_title = 'User Login';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <h1>Login to ClubHub</h1>
    <p>Sign in with your registered college credentials to access club management, event scheduling, and venue reservations.</p>
</section>

<section class="auth-card-section">
    <h2>Account Credentials</h2>

    <?php if (!empty($error)): ?>
        <aside class="flash-alert flash-danger" role="alert">
            <p><?php echo e($error); ?></p>
        </aside>
    <?php endif; ?>

    <form class="form-card card" action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="POST">
        <!-- CSRF Protection Token -->
        <?php echo csrf_field(); ?>

        <p class="form-group">
            <label class="form-label" for="email">College Email Address:</label>
            <input class="form-control" type="email" id="email" name="email" value="<?php echo e($email_val); ?>" placeholder="student@college.edu" required autocomplete="email">
        </p>

        <p class="form-group">
            <label class="form-label" for="password">Password:</label>
            <input class="form-control" type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
        </p>

        <p class="form-group form-checkbox">
            <label for="remember" class="checkbox-label">
                <input type="checkbox" id="remember" name="remember" value="1">
                <span>Keep me signed in on this computer</span>
            </label>
        </p>

        <p class="form-actions">
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
        </p>
    </form>
</section>

<section class="card">
    <h2>New to ClubHub?</h2>
    <p>Don't have an account yet? <a href="register.php" class="btn-outline">Register a new student account</a> to join clubs and attend events.</p>
    <p>Forgot your login credentials? Please contact your departmental faculty coordinator.</p>
</section>

<section class="demo-box card">
    <h2>Demo Accounts (For Lab Testing)</h2>
    <p>All test accounts use the password: <code>password123</code></p>
    <ul class="demo-accounts-list">
        <li>
            <strong>Admin (Faculty Coordinator):</strong> <code>admin@college.edu</code>
            <button type="button" class="btn btn-sm btn-outline" onclick="fillLogin('admin@college.edu')">Use Admin</button>
        </li>
        <li>
            <strong>Club Head (Technical):</strong> <code>techhead@college.edu</code>
            <button type="button" class="btn btn-sm btn-outline" onclick="fillLogin('techhead@college.edu')">Use Tech Head</button>
        </li>
        <li>
            <strong>Club Head (Cultural):</strong> <code>culthead@college.edu</code>
            <button type="button" class="btn btn-sm btn-outline" onclick="fillLogin('culthead@college.edu')">Use Cultural Head</button>
        </li>
        <li>
            <strong>Club Member:</strong> <code>rohan@college.edu</code>
            <button type="button" class="btn btn-sm btn-outline" onclick="fillLogin('rohan@college.edu')">Use Member</button>
        </li>
    </ul>
</section>

<script>
function fillLogin(email) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = 'password123';
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
