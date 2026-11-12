<?php
/**
 * ClubHub - Admin Manage Users Page
 * Allows administrators (Faculty Coordinators) to search users, update user roles
 * (member, club_head, admin), and assign club heads to clubs.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce admin-only access for both page view and update actions
require_role('admin');

$current_user = current_user();
$current_user_id = (int)$current_user['id'];

$search = trim($_REQUEST['q'] ?? '');
$redirect_target = 'manage-users.php' . ($search !== '' ? '?q=' . urlencode($search) : '');

// ---------------------------------------------------------
// Handle POST Role & Club Assignment Updates
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Security verification failed. Please refresh and try again.');
        redirect($redirect_target);
    }

    $target_user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $new_role = trim($_POST['role'] ?? '');
    $club_id = isset($_POST['club_id']) ? (int)$_POST['club_id'] : 0;
    $allowed_roles = ['member', 'club_head', 'admin'];

    if ($target_user_id <= 0 || !in_array($new_role, $allowed_roles, true)) {
        set_flash('danger', 'Invalid user or role selection.');
        redirect($redirect_target);
    }

    // Rule: A user cannot change their own role
    if ($target_user_id === $current_user_id) {
        set_flash('danger', 'You cannot change your own role.');
        redirect($redirect_target);
    }

    try {
        $pdo->beginTransaction();

        // Fetch target user
        $u_stmt = $pdo->prepare('SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1');
        $u_stmt->execute([$target_user_id]);
        $target_user = $u_stmt->fetch();

        if (!$target_user) {
            $pdo->rollBack();
            set_flash('danger', 'The selected user could not be found.');
            redirect($redirect_target);
        }

        // Rule: The last remaining admin cannot be demoted
        if ($target_user['role'] === 'admin' && $new_role !== 'admin') {
            $admin_count_stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $admin_count = (int)$admin_count_stmt->fetchColumn();
            if ($admin_count <= 1) {
                $pdo->rollBack();
                set_flash('danger', 'Cannot demote the last remaining administrator.');
                redirect($redirect_target);
            }
        }

        if ($new_role === 'club_head') {
            // Club Head must be assigned to a valid club
            if ($club_id <= 0) {
                $pdo->rollBack();
                set_flash('danger', 'Please select a club for ' . $target_user['name'] . ' to lead.');
                redirect($redirect_target);
            }

            $c_stmt = $pdo->prepare('SELECT id, name, head_id FROM clubs WHERE id = ? LIMIT 1');
            $c_stmt->execute([$club_id]);
            $club = $c_stmt->fetch();

            if (!$club) {
                $pdo->rollBack();
                set_flash('danger', 'The selected club does not exist.');
                redirect($redirect_target);
            }

            // If target user was previously leading a different club, clear that club's head_id
            $clear_prev_stmt = $pdo->prepare('UPDATE clubs SET head_id = NULL WHERE head_id = ? AND id != ?');
            $clear_prev_stmt->execute([$target_user_id, $club_id]);

            // Check if the selected club already has a different head
            $old_head_demoted_name = null;
            $old_head_id = !empty($club['head_id']) ? (int)$club['head_id'] : 0;

            if ($old_head_id > 0 && $old_head_id !== $target_user_id) {
                $old_stmt = $pdo->prepare('SELECT id, name, role FROM users WHERE id = ? LIMIT 1');
                $old_stmt->execute([$old_head_id]);
                $old_head = $old_stmt->fetch();

                // Rule: When a club gets a new head, the old head becomes a member again
                if ($old_head && $old_head['role'] === 'club_head') {
                    // Only demote if they do not lead another club
                    $other_club_stmt = $pdo->prepare('SELECT COUNT(*) FROM clubs WHERE head_id = ? AND id != ?');
                    $other_club_stmt->execute([$old_head_id, $club_id]);
                    $leads_other = (int)$other_club_stmt->fetchColumn();

                    if ($leads_other === 0) {
                        $demote_old_stmt = $pdo->prepare("UPDATE users SET role = 'member' WHERE id = ?");
                        $demote_old_stmt->execute([$old_head_id]);
                        $old_head_demoted_name = $old_head['name'];
                    }
                }
            }

            // Assign target user as head of the chosen club
            $set_head_stmt = $pdo->prepare('UPDATE clubs SET head_id = ? WHERE id = ?');
            $set_head_stmt->execute([$target_user_id, $club_id]);

            // Update target user's role to club_head
            $up_user_stmt = $pdo->prepare("UPDATE users SET role = 'club_head' WHERE id = ?");
            $up_user_stmt->execute([$target_user_id]);

            $pdo->commit();

            $msg = 'Updated "' . $target_user['name'] . '" to Club Head of "' . $club['name'] . '".';
            if ($old_head_demoted_name !== null) {
                $msg .= ' Former head (' . $old_head_demoted_name . ') is now a Member.';
            }
            set_flash('success', $msg);
        } else {
            // Role is 'member' or 'admin': clear any club leadership held by this user
            $clear_club_stmt = $pdo->prepare('UPDATE clubs SET head_id = NULL WHERE head_id = ?');
            $clear_club_stmt->execute([$target_user_id]);

            // Update user role
            $up_user_stmt = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
            $up_user_stmt->execute([$new_role, $target_user_id]);

            $pdo->commit();

            $role_label = ucwords(str_replace('_', ' ', $new_role));
            set_flash('success', 'Updated "' . $target_user['name'] . '" role to ' . $role_label . '.');
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        log_error('Failed to update user role in manage-users.php: ' . $e->getMessage());
        set_flash('danger', 'A database error occurred while updating the user role.');
    }

    redirect($redirect_target);
}

// ---------------------------------------------------------
// Fetch Clubs and Users for Display (GET)
// ---------------------------------------------------------
try {
    $clubs_stmt = $pdo->query('SELECT id, name, head_id FROM clubs ORDER BY name ASC');
    $all_clubs = $clubs_stmt->fetchAll();
} catch (PDOException $e) {
    log_error('Failed to fetch clubs for manage-users.php: ' . $e->getMessage());
    $all_clubs = [];
}

$sql = "
    SELECT u.id, u.name, u.email, u.role, u.created_at,
           (SELECT id FROM clubs c1 WHERE c1.head_id = u.id ORDER BY c1.id ASC LIMIT 1) AS led_club_id,
           (SELECT GROUP_CONCAT(c2.name ORDER BY c2.name SEPARATOR ', ') FROM clubs c2 WHERE c2.head_id = u.id) AS led_clubs,
           (SELECT GROUP_CONCAT(c3.name ORDER BY c3.name SEPARATOR ', ')
            FROM club_members cm
            JOIN clubs c3 ON cm.club_id = c3.id
            WHERE cm.user_id = u.id AND cm.status = 'approved') AS member_clubs
    FROM users u
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            u.name LIKE ?
            OR u.email LIKE ?
            OR u.role LIKE ?
            OR EXISTS (SELECT 1 FROM clubs c4 WHERE c4.head_id = u.id AND c4.name LIKE ?)
            OR EXISTS (
                SELECT 1 FROM club_members cm2
                JOIN clubs c5 ON cm2.club_id = c5.id
                WHERE cm2.user_id = u.id AND cm2.status = 'approved' AND c5.name LIKE ?
            )
        )
    ";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like, $like];
}

$sql .= " ORDER BY FIELD(u.role, 'admin', 'club_head', 'member', 'guest'), u.name ASC";

try {
    $users_stmt = $pdo->prepare($sql);
    $users_stmt->execute($params);
    $users_list = $users_stmt->fetchAll();
} catch (PDOException $e) {
    log_error('Failed to query users in manage-users.php: ' . $e->getMessage());
    $users_list = [];
}

// Helper for role badge CSS class
function role_badge_class($role) {
    switch ($role) {
        case 'admin':
            return 'badge-danger';
        case 'club_head':
            return 'badge-warning';
        case 'member':
            return 'badge-success';
        default:
            return 'badge-info';
    }
}

$page_title = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <div class="action-bar">
        <div>
            <h1>Manage Users</h1>
            <p>View all registered accounts, search by user or club, and assign user roles and club leadership.</p>
        </div>
        <a href="dashboard.php" class="btn btn-secondary">&larr; Back to Dashboard</a>
    </div>
</section>

<!-- Search Box -->
<section class="filter-section">
    <h2>Search Users</h2>
    <form class="filter-form" action="manage-users.php" method="GET">
        <p class="form-group">
            <label class="form-label" for="user_search">Search by Name, Email, Role, or Club:</label>
            <input class="form-control" type="text" id="user_search" name="q" value="<?php echo e($search); ?>" placeholder="Enter name, email, role, or club name...">
        </p>
        <p class="form-actions">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="manage-users.php" class="btn btn-secondary">Clear</a>
        </p>
    </form>
</section>

<!-- Users List & Role Assignment Table -->
<section class="dashboard-section card">
    <div class="action-bar" style="margin-bottom: var(--space-md);">
        <h2>Registered Users (<?php echo count($users_list); ?>)</h2>
        <span class="badge badge-primary">Admin Only</span>
    </div>

    <?php if (empty($users_list)): ?>
        <p style="color: var(--text-muted);">No users matched your search criteria.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Club</th>
                        <th>Update Role &amp; Club Leadership</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users_list as $u): ?>
                        <?php
                        $is_self = ((int)$u['id'] === $current_user_id);
                        $led_club_id = !empty($u['led_club_id']) ? (int)$u['led_club_id'] : 0;
                        $role_label = ucwords(str_replace('_', ' ', $u['role']));
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo e($u['name']); ?></strong>
                                <?php if ($is_self): ?>
                                    <span class="badge badge-info" style="margin-left: 4px;">You</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($u['email']); ?></td>
                            <td>
                                <span class="badge badge-role <?php echo e(role_badge_class($u['role'])); ?>">
                                    <?php echo e($role_label); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($u['led_clubs'])): ?>
                                    <span class="badge badge-primary">Leads: <?php echo e($u['led_clubs']); ?></span>
                                <?php elseif (!empty($u['member_clubs'])): ?>
                                    <span style="color: var(--text-muted); font-size: 0.9rem;"><?php echo e($u['member_clubs']); ?></span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">&mdash;</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($is_self): ?>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">Cannot change your own role</span>
                                <?php else: ?>
                                    <form action="manage-users.php" method="POST" style="display: inline-flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; margin: 0; padding: 0; border: none; box-shadow: none; background: transparent;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                        <?php if ($search !== ''): ?>
                                            <input type="hidden" name="q" value="<?php echo e($search); ?>">
                                        <?php endif; ?>

                                        <select id="role-<?php echo (int)$u['id']; ?>" name="role" aria-label="Role for <?php echo e($u['name']); ?>" class="form-control role-select" data-target="club-<?php echo (int)$u['id']; ?>" style="width: auto; min-width: 130px; padding: 0.35rem 0.5rem; font-size: 0.88rem;">
                                            <option value="member" <?php echo $u['role'] === 'member' ? 'selected' : ''; ?>>Member</option>
                                            <option value="club_head" <?php echo $u['role'] === 'club_head' ? 'selected' : ''; ?>>Club Head</option>
                                            <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>

                                        <select id="club-<?php echo (int)$u['id']; ?>" name="club_id" aria-label="Club to lead for <?php echo e($u['name']); ?>" class="form-control club-select" style="width: auto; min-width: 190px; padding: 0.35rem 0.5rem; font-size: 0.88rem;">
                                            <option value="0">-- Select Club to Lead --</option>
                                            <?php foreach ($all_clubs as $c): ?>
                                                <option value="<?php echo (int)$c['id']; ?>" <?php echo $led_club_id === (int)$c['id'] ? 'selected' : ''; ?>>
                                                    <?php echo e($c['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function syncClubSelect(roleSelect) {
        var clubSelect = document.getElementById(roleSelect.getAttribute('data-target'));
        if (!clubSelect) return;
        var isClubHead = roleSelect.value === 'club_head';
        clubSelect.disabled = !isClubHead;
        clubSelect.required = isClubHead;
        clubSelect.style.opacity = isClubHead ? '1' : '0.5';
    }

    document.querySelectorAll('.role-select').forEach(function (select) {
        syncClubSelect(select);
        select.addEventListener('change', function () {
            syncClubSelect(select);
        });
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
