<?php
/**
 * ClubHub - Budget Request Approval Action Handler
 * Phase 7b: Enables Faculty Coordinator (Admin) to approve or reject
 * club budget requests with coordinator remarks.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Only Admin can sanction or reject budget requests
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('budget.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Security verification failed. Please try again.');
    redirect('budget.php');
}

$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
$decision = trim($_POST['decision'] ?? '');
$admin_remarks = trim($_POST['admin_remarks'] ?? '');

if ($request_id <= 0 || !in_array($decision, ['approve', 'reject'], true)) {
    set_flash('danger', 'Invalid budget decision request.');
    redirect('budget.php');
}

try {
    $b_stmt = $pdo->prepare('SELECT * FROM budget_requests WHERE id = ? LIMIT 1');
    $b_stmt->execute([$request_id]);
    $request = $b_stmt->fetch();

    if (!$request) {
        set_flash('danger', 'Budget request record not found.');
        redirect('budget.php');
    }

    if ($decision === 'approve') {
        $up = $pdo->prepare('UPDATE budget_requests SET status = "approved", admin_remarks = ? WHERE id = ?');
        $up->execute([$admin_remarks !== '' ? $admin_remarks : 'Approved by Faculty Coordinator.', $request_id]);
        set_flash('success', 'Budget request for "' . $request['title'] . '" ($' . number_format((float)$request['amount'], 2) . ') has been approved!');
    } elseif ($decision === 'reject') {
        $up = $pdo->prepare('UPDATE budget_requests SET status = "rejected", admin_remarks = ? WHERE id = ?');
        $up->execute([$admin_remarks !== '' ? $admin_remarks : 'Budget request rejected by Faculty Coordinator.', $request_id]);
        set_flash('info', 'Budget request for "' . $request['title'] . '" has been rejected.');
    }
} catch (PDOException $e) {
    log_error('Error processing budget decision: ' . $e->getMessage());
    set_flash('danger', 'Database error while updating budget request.');
}

redirect('budget.php');

