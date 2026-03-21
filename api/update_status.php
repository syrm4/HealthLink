<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
startSession();
requireRole(['admin','leader']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'Method not allowed']); exit; }
$reqId  = (int)($_POST['request_id'] ?? 0);
$status = $_POST['status'] ?? '';
$notes  = trim($_POST['admin_notes'] ?? '');
$valid  = ['submitted','in_review','approved','fulfilled'];
if (!$reqId || !in_array($status, $valid)) { echo json_encode(['error'=>'Invalid parameters']); exit; }

$db = getDB();
$db->prepare('UPDATE requests SET status=?,admin_notes=?,updated_at=NOW() WHERE id=?')->execute([$status,$notes,$reqId]);
$by = $_SESSION['user']['full_name'] ?? 'Admin';
$db->prepare('INSERT INTO status_history (request_id,status,changed_by,notes) VALUES (?,?,?,?)')->execute([$reqId,$status,$by,$notes?:null]);
echo json_encode(['success'=>true]);
