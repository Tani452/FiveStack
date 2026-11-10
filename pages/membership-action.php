<?php
/**
 * ClubHub - Club Membership Actions Handler
 * Phase 7b: Handles student membership applications, withdrawals,
 * and Club Head / Admin approval, rejection, and roster management.
 * Strictly enforces CSRF protection, role authorization, and prepared statements.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Must be logged in to perform any membership action
require_login();

// Strictly allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('clubs.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Security verification failed. Please try again.');
    redirect('clubs.php');
}

$user = current_user();
$user_id = (int)$user['id'];
$is_admin = has_role('admin');

$action = trim($_POST['action'] ?? '');
$club_id = isset($_POST['club_id']) ? (int)$_POST['club_id'] : 0;
$member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;

// Determine safe redirect destination
$redirect_url = 'dashboard.php';
if (!empty($_POST['redirect'])) {
    $redirect_url = $_POST['redirect'];
} elseif (!empty($_SERVER['HTTP_REFERER'])) {
    $ref_parts = parse_url($_SERVER['HTTP_REFERER']);
    if (isset($ref_parts['path'])) {
        $redirect_url = basename($ref_parts['path']);
        if (!empty($ref_parts['query'])) {
            $redirect_url .= '?' . $ref_parts['query'];
        }
    }
}

// -------------------------------------------------------------
// 1. ACTION: APPLY TO JOIN A CLUB (Students / Members)
// -------------------------------------------------------------
if ($action === 'apply') {
    if ($club_id <= 0) {
        set_flash('danger', 'Invalid club selected for application.');
        redirect($redirect_url);
    }

    try {
        // Verify club exists and is active
        $c_stmt = $pdo->prepare('SELECT id, name, status FROM clubs WHERE id = ? LIMIT 1');
        $c_stmt->execute([$club_id]);
        $club = $c_stmt->fetch();

        if (!$club || $club['status'] !== 'active') {
            set_flash('danger', 'This club is currently inactive or does not exist.');
            redirect($redirect_url);
        }

        // Check if existing membership record exists
        $chk_stmt = $pdo->prepare('SELECT id, status FROM club_members WHERE club_id = ? AND user_id = ? LIMIT 1');
        $chk_stmt->execute([$club_id, $user_id]);
        $existing = $chk_stmt->fetch();

        if ($existing) {
            if ($existing['status'] === 'approved') {
                set_flash('info', 'You are already an active member of "' . $club['name'] . '".');
            } elseif ($existing['status'] === 'pending') {
                set_flash('warning', 'Your application to "' . $club['name'] . '" is already submitted and under review.');
            } elseif ($existing['status'] === 'rejected') {
                // Re-apply by resetting status to pending
                $reapply_stmt = $pdo->prepare('UPDATE club_members SET status = "pending", joined_at = CURRENT_TIMESTAMP WHERE id = ?');
                $reapply_stmt->execute([(int)$existing['id']]);
                set_flash('success', 'Your application to "' . $club['name'] . '" has been resubmitted for review!');
            }
        } else {
            // New application with status 'pending'
            $ins_stmt = $pdo->prepare('INSERT INTO club_members (club_id, user_id, status) VALUES (?, ?, "pending")');
            $ins_stmt->execute([$club_id, $user_id]);
            set_flash('success', 'Application to join "' . $club['name'] . '" submitted successfully! The Club Head will review your request.');
        }
    } catch (PDOException $e) {
        log_error('Failed to submit club application: ' . $e->getMessage());
        set_flash('danger', 'An error occurred while submitting your application.');
    }
    redirect($redirect_url);
}

// -------------------------------------------------------------
// 2. ACTION: WITHDRAW APPLICATION OR LEAVE CLUB
// -------------------------------------------------------------
if ($action === 'leave' || $action === 'withdraw') {
    if ($club_id <= 0) {
        set_flash('danger', 'Invalid club specified.');
        redirect($redirect_url);
    }

    try {
        $del_stmt = $pdo->prepare('DELETE FROM club_members WHERE club_id = ? AND user_id = ?');
        $del_stmt->execute([$club_id, $user_id]);
        set_flash('info', 'Your membership / application has been withdrawn.');
    } catch (PDOException $e) {
        log_error('Failed to withdraw membership: ' . $e->getMessage());
        set_flash('danger', 'Database error while updating membership status.');
    }
    redirect($redirect_url);
}

// -------------------------------------------------------------
// 3. ACTIONS: APPROVE / REJECT / REMOVE (Club Head or Admin)
// -------------------------------------------------------------
if ($action === 'approve' || $action === 'reject' || $action === 'remove') {
    if ($member_id <= 0) {
        set_flash('danger', 'Invalid member record specified.');
        redirect($redirect_url);
    }

    try {
        // Fetch membership record joined with club to verify leadership authorization
        $m_stmt = $pdo->prepare('
            SELECT cm.*, c.name AS club_name, c.head_id, u.name AS applicant_name
            FROM club_members cm
            JOIN clubs c ON cm.club_id = c.id
            JOIN users u ON cm.user_id = u.id
            WHERE cm.id = ?
            LIMIT 1
        ');
        $m_stmt->execute([$member_id]);
        $record = $m_stmt->fetch();

        if (!$record) {
            set_flash('danger', 'Membership application not found.');
            redirect($redirect_url);
        }

        // Authorize: Only Admin or the assigned Club Head of that club can approve/reject/remove
        $can_manage = $is_admin || ((int)$record['head_id'] === $user_id);
        if (!$can_manage) {
            set_flash('danger', 'Access denied. You can only manage memberships for clubs you lead.');
            redirect($redirect_url);
        }

        if ($action === 'approve') {
            $up_stmt = $pdo->prepare('UPDATE club_members SET status = "approved" WHERE id = ?');
            $up_stmt->execute([$member_id]);
            set_flash('success', 'Applicant "' . $record['applicant_name'] . '" has been approved as an active member of ' . $record['club_name'] . '!');
        } elseif ($action === 'reject') {
            $up_stmt = $pdo->prepare('UPDATE club_members SET status = "rejected" WHERE id = ?');
            $up_stmt->execute([$member_id]);
            set_flash('info', 'Application for "' . $record['applicant_name'] . '" has been rejected.');
        } elseif ($action === 'remove') {
            $del_stmt = $pdo->prepare('DELETE FROM club_members WHERE id = ?');
            $del_stmt->execute([$member_id]);
            set_flash('info', 'Member "' . $record['applicant_name'] . '" has been removed from ' . $record['club_name'] . ' roster.');
        }
    } catch (PDOException $e) {
        log_error('Error managing club membership action: ' . $e->getMessage());
        set_flash('danger', 'Database error while processing membership action.');
    }
    redirect($redirect_url);
}

// Fallback redirect
redirect('clubs.php');
