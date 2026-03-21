<?php
// HealthLink — Update request status endpoint
// W3Schools best practices: prepared statements, role guard, input validation
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Only admins and leaders may update status
require_role('admin', 'leader');

$reqId  = (int) ($_POST['request_id'] ?? 0);
$status = $_POST['status'] ?? '';
$notes  = trim($_POST['admin_notes'] ?? '');

$validStatuses = ['submitted', 'in_review', 'approved', 'sent_to_qualtrics', 'fulfilled'];
if (!$reqId || !in_array($status, $validStatuses, true)) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

$db = getDB();

// Update status using prepared statement
$db->prepare('UPDATE requests SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?')
   ->execute([$status, $notes ?: null, $reqId]);

// Write audit trail
$changedBy = $_SESSION['user_name'] ?? 'Admin';
$db->prepare('INSERT INTO status_history (request_id, status, changed_by, notes) VALUES (?,?,?,?)')
   ->execute([$reqId, $status, $changedBy, $notes ?: null]);

echo json_encode(['success' => true]);
