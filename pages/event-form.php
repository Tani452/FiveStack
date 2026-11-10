<?php
/**
 * ClubHub - Event Create / Edit / Delete Form
 * Phase 6: Admin and Club Head form to create, edit, and delete events.
 * Implements strict server-side validation (runs with or without JS),
 * time sequencing verification (end time > start time), CSRF protection,
 * and role-based permissions.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Requires logged-in Admin or Club Head
require_role(['admin', 'club_head']);

$user = current_user();
$is_admin = has_role('admin');

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$is_edit = $id > 0;

$event = null;
if ($is_edit) {
    try {
        $stmt = $pdo->prepare('
            SELECT e.*, c.head_id AS club_head_id 
            FROM events e 
            JOIN clubs c ON e.club_id = c.id 
            WHERE e.id = ? 
            LIMIT 1
        ');
        $stmt->execute([$id]);
        $event = $stmt->fetch();
    } catch (PDOException $e) {
        log_error('Failed to query event: ' . $e->getMessage());
    }

    if (!$event) {
        set_flash('danger', 'The requested event was not found.');
        redirect('events.php');
    }

    // Authorization check for edit
    $can_edit = $is_admin || ((int)$event['created_by'] === (int)$user['id']) || ((int)$event['club_head_id'] === (int)$user['id']);
    if (!$can_edit) {
        set_flash('danger', 'Access denied. You do not have permission to edit this event.');
        redirect('events.php');
    }
}

// ---------------------------------------------------------
// POST Handling: Delete Action
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Security token expired. Please try again.');
        redirect('events.php');
    }

    if (!$is_edit || !$event) {
        set_flash('danger', 'No event specified for deletion.');
        redirect('events.php');
    }

    try {
        $del_stmt = $pdo->prepare('DELETE FROM events WHERE id = ?');
        $del_stmt->execute([$id]);
        set_flash('success', 'Event "' . ($event['title'] ?? 'Event') . '" has been cancelled and deleted.');
    } catch (PDOException $e) {
        log_error('Failed to delete event: ' . $e->getMessage());
        set_flash('danger', 'Database error while deleting event.');
    }
    redirect('events.php');
}

// Fetch clubs available to the current user
try {
    if ($is_admin) {
        $clubs_stmt = $pdo->query('SELECT id, name, category FROM clubs WHERE status = "active" ORDER BY name ASC');
    } else {
        $clubs_stmt = $pdo->prepare('SELECT id, name, category FROM clubs WHERE head_id = ? AND status = "active" ORDER BY name ASC');
        $clubs_stmt->execute([(int)$user['id']]);
    }
    $available_clubs = $clubs_stmt->fetchAll();
} catch (PDOException $e) {
    log_error('Failed to load clubs for event form: ' . $e->getMessage());
    $available_clubs = [];
}

// If a club head has no assigned clubs, they cannot schedule events
if (!$is_admin && empty($available_clubs)) {
    set_flash('danger', 'You are not assigned as Club Head for any active club. Please contact the Faculty Coordinator.');
    redirect('events.php');
}

// Fetch all venues for dropdown
try {
    $v_stmt = $pdo->query('SELECT id, name, capacity, location FROM venues ORDER BY name ASC');
    $venues = $v_stmt->fetchAll();
} catch (PDOException $e) {
    log_error('Failed to load venues for event form: ' . $e->getMessage());
    $venues = [];
}

// ---------------------------------------------------------
// POST Handling: Save (Create / Update) Action
// ---------------------------------------------------------
$errors = [];
$title = $event['title'] ?? '';
$club_id = $event['club_id'] ?? ($available_clubs[0]['id'] ?? '');
$venue_id = $event['venue_id'] ?? '';
$event_date = $event['event_date'] ?? '';
$start_time = isset($event['start_time']) ? substr($event['start_time'], 0, 5) : '09:00';
$end_time = isset($event['end_time']) ? substr($event['end_time'], 0, 5) : '17:00';
$description = $event['description'] ?? '';
$status = $event['status'] ?? ($is_admin ? 'approved' : 'pending');
$admin_remarks = $event['admin_remarks'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] === 'save')) {
    // 1. Verify CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors['csrf'] = 'Security verification failed. Please try submitting again.';
    }

    // 2. Extract inputs
    $title = trim($_POST['title'] ?? '');
    $club_id = (int)($_POST['club_id'] ?? 0);
    $venue_id = !empty($_POST['venue_id']) ? (int)$_POST['venue_id'] : null;
    $event_date = trim($_POST['event_date'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($is_admin) {
        $valid_statuses = ['draft', 'pending', 'approved', 'rejected', 'completed', 'cancelled'];
        $status = in_array($_POST['status'] ?? '', $valid_statuses, true) ? $_POST['status'] : 'approved';
        $admin_remarks = trim($_POST['admin_remarks'] ?? '');
    } else {
        // Club heads cannot unilaterally approve their own events
        if (!$is_edit) {
            $status = 'pending';
        }
    }

    // 3. Server-side validation
    if ($title === '') {
        $errors['title'] = 'Event title is required.';
    } elseif (mb_strlen($title) < 3 || mb_strlen($title) > 150) {
        $errors['title'] = 'Event title must be between 3 and 150 characters.';
    }

    if ($club_id <= 0) {
        $errors['club_id'] = 'Please select an organizing club.';
    } else {
        // Verify club is in available_clubs
        $found_club = false;
        foreach ($available_clubs as $ac) {
            if ((int)$ac['id'] === $club_id) {
                $found_club = true;
                break;
            }
        }
        if (!$found_club) {
            $errors['club_id'] = 'You do not have permission to schedule events for this club.';
        }
    }

    if (!empty($venue_id)) {
        $found_venue = false;
        foreach ($venues as $v) {
            if ((int)$v['id'] === $venue_id) {
                $found_venue = true;
                break;
            }
        }
        if (!$found_venue) {
            $errors['venue_id'] = 'Selected venue is invalid.';
        }
    }

    // Date validation
    if ($event_date === '') {
        $errors['event_date'] = 'Event date is required.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $event_date) || !strtotime($event_date)) {
        $errors['event_date'] = 'Please enter a valid date in YYYY-MM-DD format.';
    }

    // Time validation (End Time must be after Start Time)
    if ($start_time === '') {
        $errors['start_time'] = 'Start time is required.';
    }
    if ($end_time === '') {
        $errors['end_time'] = 'End time is required.';
    }
    if ($start_time !== '' && $end_time !== '') {
        if (strtotime($end_time) <= strtotime($start_time)) {
            $errors['end_time'] = 'End time must be later than the start time.';
        }
    }

    if ($description === '') {
        $errors['description'] = 'Event description is required.';
    } elseif (mb_strlen($description) < 10) {
        $errors['description'] = 'Please provide a descriptive overview (at least 10 characters).';
    }

    // 4. Save to Database
    if (empty($errors)) {
        try {
            if ($is_edit) {
                if ($is_admin) {
                    $up_sql = 'UPDATE events SET club_id = ?, venue_id = ?, title = ?, description = ?, event_date = ?, start_time = ?, end_time = ?, status = ?, admin_remarks = ? WHERE id = ?';
                    $stmt = $pdo->prepare($up_sql);
                    $stmt->execute([$club_id, $venue_id, $title, $description, $event_date, $start_time, $end_time, $status, $admin_remarks, $id]);
                } else {
                    $up_sql = 'UPDATE events SET club_id = ?, venue_id = ?, title = ?, description = ?, event_date = ?, start_time = ?, end_time = ? WHERE id = ?';
                    $stmt = $pdo->prepare($up_sql);
                    $stmt->execute([$club_id, $venue_id, $title, $description, $event_date, $start_time, $end_time, $id]);
                }
                set_flash('success', 'Event "' . $title . '" updated successfully!');
            } else {
                $in_sql = 'INSERT INTO events (club_id, venue_id, title, description, event_date, start_time, end_time, status, admin_remarks, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
                $stmt = $pdo->prepare($in_sql);
                $stmt->execute([$club_id, $venue_id, $title, $description, $event_date, $start_time, $end_time, $status, $admin_remarks, (int)$user['id']]);
                $id = (int)$pdo->lastInsertId();
                set_flash('success', 'Event "' . $title . '" successfully created and scheduled!');
            }
            redirect('event-detail.php?id=' . $id);
        } catch (PDOException $e) {
            log_error('Database error saving event: ' . $e->getMessage());
            $errors['db'] = 'Failed to save event due to a database error. Please review logs.';
        }
    }
}

$page_title = $is_edit ? 'Edit Event: ' . ($event['title'] ?? '') : 'Schedule New Event';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="page-header">
    <div class="action-bar">
        <div>
            <h1><?php echo $is_edit ? 'Edit Event: ' . e($event['title']) : 'Schedule New Event'; ?></h1>
            <p><?php echo $is_edit ? 'Update event details, timing, or venue booking.' : 'Submit a new college event, workshop, or competition for campus calendar scheduling.'; ?></p>
        </div>
        <a href="events.php" class="btn btn-secondary">Back to Events</a>
    </div>
</section>

<section class="auth-card-section">
    <?php if (!empty($errors['csrf'])): ?>
        <aside class="flash-alert flash-danger" role="alert"><p><?php echo e($errors['csrf']); ?></p></aside>
    <?php endif; ?>
    <?php if (!empty($errors['db'])): ?>
        <aside class="flash-alert flash-danger" role="alert"><p><?php echo e($errors['db']); ?></p></aside>
    <?php endif; ?>

    <form class="form-card card" action="event-form.php<?php echo $is_edit ? '?id=' . (int)$id : ''; ?>" method="POST" novalidate>
        <!-- CSRF Protection Token -->
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="save">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
        <?php endif; ?>

        <!-- Event Title -->
        <p class="form-group">
            <label class="form-label" for="title">Event Title: <span style="color:var(--error);">*</span></label>
            <input class="form-control <?php echo isset($errors['title']) ? 'input-error' : ''; ?>" type="text" id="title" name="title" value="<?php echo e($title); ?>" placeholder="e.g. Annual Autonomous Robotics Challenge" required maxlength="150">
            <?php if (isset($errors['title'])): ?>
                <span class="field-error-message"><?php echo e($errors['title']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Organizing Club -->
        <p class="form-group">
            <label class="form-label" for="club_id">Organizing Club: <span style="color:var(--error);">*</span></label>
            <select class="form-control <?php echo isset($errors['club_id']) ? 'input-error' : ''; ?>" id="club_id" name="club_id" required>
                <?php foreach ($available_clubs as $ac): ?>
                    <option value="<?php echo (int)$ac['id']; ?>" <?php echo (int)$club_id === (int)$ac['id'] ? 'selected' : ''; ?>>
                        <?php echo e($ac['name']); ?> (<?php echo e($ac['category']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['club_id'])): ?>
                <span class="field-error-message"><?php echo e($errors['club_id']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Venue Selection -->
        <p class="form-group">
            <label class="form-label" for="venue_id">Campus Venue:</label>
            <select class="form-control <?php echo isset($errors['venue_id']) ? 'input-error' : ''; ?>" id="venue_id" name="venue_id">
                <option value="">-- No Venue / To Be Announced --</option>
                <?php foreach ($venues as $v): ?>
                    <option value="<?php echo (int)$v['id']; ?>" <?php echo (int)$venue_id === (int)$v['id'] ? 'selected' : ''; ?>>
                        <?php echo e($v['name']); ?> &mdash; <?php echo e($v['location']); ?> (Cap: <?php echo (int)$v['capacity']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['venue_id'])): ?>
                <span class="field-error-message"><?php echo e($errors['venue_id']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Event Date -->
        <p class="form-group">
            <label class="form-label" for="event_date">Event Date: <span style="color:var(--error);">*</span></label>
            <input class="form-control <?php echo isset($errors['event_date']) ? 'input-error' : ''; ?>" type="date" id="event_date" name="event_date" value="<?php echo e($event_date); ?>" required>
            <?php if (isset($errors['event_date'])): ?>
                <span class="field-error-message"><?php echo e($errors['event_date']); ?></span>
            <?php endif; ?>
        </p>

        <!-- Start & End Time -->
        <div style="display: flex; gap: var(--space-md); flex-wrap: wrap;">
            <p class="form-group" style="flex: 1; min-width: 140px;">
                <label class="form-label" for="start_time">Start Time: <span style="color:var(--error);">*</span></label>
                <input class="form-control <?php echo isset($errors['start_time']) ? 'input-error' : ''; ?>" type="time" id="start_time" name="start_time" value="<?php echo e($start_time); ?>" required>
                <?php if (isset($errors['start_time'])): ?>
                    <span class="field-error-message"><?php echo e($errors['start_time']); ?></span>
                <?php endif; ?>
            </p>

            <p class="form-group" style="flex: 1; min-width: 140px;">
                <label class="form-label" for="end_time">End Time: <span style="color:var(--error);">*</span></label>
                <input class="form-control <?php echo isset($errors['end_time']) ? 'input-error' : ''; ?>" type="time" id="end_time" name="end_time" value="<?php echo e($end_time); ?>" required>
                <?php if (isset($errors['end_time'])): ?>
                    <span class="field-error-message"><?php echo e($errors['end_time']); ?></span>
                <?php endif; ?>
            </p>
        </div>

        <!-- Description -->
        <p class="form-group">
            <label class="form-label" for="description">Event Description: <span style="color:var(--error);">*</span></label>
            <textarea class="form-control <?php echo isset($errors['description']) ? 'input-error' : ''; ?>" id="description" name="description" rows="5" placeholder="Detail the event objectives, speaker information, agenda, and student registration guidelines..." required><?php echo e($description); ?></textarea>
            <?php if (isset($errors['description'])): ?>
                <span class="field-error-message"><?php echo e($errors['description']); ?></span>
            <?php endif; ?>
        </p>

        <?php if ($is_admin): ?>
            <!-- Event Status (Admin Only) -->
            <p class="form-group">
                <label class="form-label" for="status">Approval Status (Admin Sanction):</label>
                <select class="form-control" id="status" name="status">
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved (Sanctioned & Public)</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending Faculty Review</option>
                    <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft / In Planning</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </p>

            <!-- Admin Remarks (Admin Only) -->
            <p class="form-group">
                <label class="form-label" for="admin_remarks">Faculty Coordinator Remarks:</label>
                <textarea class="form-control" id="admin_remarks" name="admin_remarks" rows="2" placeholder="Feedback or sanction terms for the club organizers..."><?php echo e($admin_remarks); ?></textarea>
            </p>
        <?php else: ?>
            <p>
                <strong>Status:</strong>
                <span class="badge badge-<?php echo e($status); ?>"><?php echo e(ucfirst($status)); ?></span>
                <small style="color: var(--text-muted); display: block; margin-top: 4px;">
                    New and updated events will be sent to the Faculty Coordinator for sanction.
                </small>
            </p>
        <?php endif; ?>

        <p class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Update Event' : 'Schedule Event'; ?></button>
            <a href="events.php" class="btn btn-secondary">Cancel</a>
        </p>
    </form>
</section>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

