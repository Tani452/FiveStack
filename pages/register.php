<?php
/**
 * ClubHub - User Registration Page
 * Phase 2: Styled with design system, form card, and responsive controls.
 */

$page_title = 'Register Account';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <h1>Register New Student Account</h1>
    <p>Create your student profile to join college clubs, apply for event passes, and participate in campus activities.</p>
</section>

<section class="auth-card-section">
    <h2>Registration Form</h2>
    <form class="form-card card" action="register.php" method="POST">
        <!-- CSRF Protection Token -->
        <?php echo csrf_field(); ?>

        <p class="form-group">
            <label class="form-label" for="fullname">Full Name:</label>
            <input class="form-control" type="text" id="fullname" name="fullname" placeholder="e.g. John Doe" required autocomplete="name">
        </p>

        <p class="form-group">
            <label class="form-label" for="email">College Email Address:</label>
            <input class="form-control" type="email" id="email" name="email" placeholder="e.g. j.doe@college.edu" required autocomplete="email">
        </p>

        <p class="form-group">
            <label class="form-label" for="password">Password:</label>
            <input class="form-control" type="password" id="password" name="password" placeholder="Minimum 8 characters" required minlength="8" autocomplete="new-password">
        </p>

        <p class="form-group">
            <label class="form-label" for="confirm_password">Confirm Password:</label>
            <input class="form-control" type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required minlength="8" autocomplete="new-password">
        </p>

        <p class="form-group">
            <label class="form-label" for="phone">Contact Number:</label>
            <input class="form-control" type="tel" id="phone" name="phone" placeholder="e.g. 9876543210" pattern="[0-9]{10}" autocomplete="tel">
        </p>

        <p class="form-group">
            <label class="form-label" for="dob">Date of Birth:</label>
            <input class="form-control" type="date" id="dob" name="dob">
        </p>

        <p class="form-group">
            <label class="form-label" for="role">Register As:</label>
            <select class="form-control" id="role" name="role" required>
                <option value="">-- Select Your Role --</option>
                <option value="member">Club Member (Current College Student)</option>
                <option value="guest">Guest (Prospective Student / Campus Visitor)</option>
            </select>
        </p>

        <p class="form-group">
            <label class="form-label" for="department">Academic Department:</label>
            <select class="form-control" id="department" name="department" required>
                <option value="">-- Select Department --</option>
                <option value="cs">Computer Science & Engineering</option>
                <option value="ece">Electronics & Communication Engineering</option>
                <option value="me">Mechanical Engineering</option>
                <option value="ce">Civil Engineering</option>
                <option value="mgmt">Business & Management</option>
                <option value="arts">Humanities & Creative Arts</option>
            </select>
        </p>

        <p class="form-group">
            <label for="terms">
                <input type="checkbox" id="terms" name="terms" value="1" required>
                I agree to abide by college club constitution guidelines and code of conduct.
            </label>
        </p>

        <p class="form-actions">
            <button type="submit" class="btn btn-primary">Create Account</button>
            <button type="reset" class="btn btn-secondary">Clear Form</button>
        </p>
    </form>
</section>

<section class="card">
    <h2>Already Have an Account?</h2>
    <p>Already registered on ClubHub? <a href="login.php" class="btn-outline">Log in to your account</a>.</p>
    <p>Note: Faculty Coordinators and Club Heads are assigned directly by the Student Affairs administration.</p>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
