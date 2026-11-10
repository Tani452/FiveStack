<?php
/**
 * ClubHub - User Login Page
 * Phase 2: Styled with design system, form card, and demo credentials box.
 */

$page_title = 'User Login';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <h1>Login to ClubHub</h1>
    <p>Sign in with your registered college credentials to access your club dashboard, event submissions, or venue reservations.</p>
</section>

<section class="auth-card-section">
    <h2>Account Credentials</h2>
    <form class="form-card card" action="login.php" method="POST">
        <!-- CSRF Protection Token -->
        <?php echo csrf_field(); ?>

        <p class="form-group">
            <label class="form-label" for="email">College Email Address:</label>
            <input class="form-control" type="email" id="email" name="email" placeholder="student@college.edu" required autocomplete="email">
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

<section class="demo-box">
    <h2>Demo Accounts (For Lab Testing)</h2>
    <ul>
        <li><strong>Admin (Faculty Coordinator):</strong> <code>admin@college.edu</code> (Password: <code>password123</code>)</li>
        <li><strong>Club Head (Technical):</strong> <code>techhead@college.edu</code> (Password: <code>password123</code>)</li>
        <li><strong>Club Head (Cultural):</strong> <code>culthead@college.edu</code> (Password: <code>password123</code>)</li>
        <li><strong>Club Member:</strong> <code>member@college.edu</code> (Password: <code>password123</code>)</li>
        <li><strong>Guest:</strong> <code>guest@college.edu</code> (Password: <code>password123</code>)</li>
    </ul>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
