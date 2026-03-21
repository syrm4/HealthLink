<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/ai_classify.php';
header('Content-Type: application/json');
startSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error'=>'Method not allowed']); exit; }

$required = ['requestor_name','organization','requestor_email','event_name','event_date','city','zip_code','audience_type','request_type','material_category'];
foreach ($required as $f) {
    if (empty(trim($_POST[$f] ?? ''))) { echo json_encode(['error'=>"Missing required field: $f"]); exit; }
}

$db = getDB();
$stmt = $db->prepare('SELECT id FROM service_area_zips WHERE zip_code=? LIMIT 1');
$stmt->execute([trim($_POST['zip_code'])]);
$inArea = (bool)$stmt->fetch();

$stmt = $db->prepare('INSERT INTO requests (user_id,requestor_name,requestor_email,requestor_phone,organization,is_internal,event_name,event_date,city,zip_code,estimated_attendees,audience_type,request_type,material_category,notes,status,in_service_area) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,\'submitted\',?)');
$userId = isLoggedIn() ? $_SESSION['user_id'] : null;
$isInternal = ($_SESSION['role'] ?? '') === 'staff' ? 1 : 0;
$stmt->execute([$userId,trim($_POST['requestor_name']),trim($_POST['requestor_email']),$_POST['requestor_phone']??null,trim($_POST['organization']),$isInternal,trim($_POST['event_name']),trim($_POST['event_date']),trim($_POST['city']),trim($_POST['zip_code']),$_POST['estimated_attendees']?:null,trim($_POST['audience_type']),trim($_POST['request_type']),trim($_POST['material_category']),$_POST['notes']??null,$inArea?1:0]);
$reqId = (int)$db->lastInsertId();

$db->prepare('INSERT INTO status_history (request_id,status,changed_by,notes) VALUES (?,\'submitted\',\'System\',\'Request received via web form\')')->execute([$reqId]);

classifyAndUpdateRequest(['id'=>$reqId,'event_name'=>$_POST['event_name'],'event_date'=>$_POST['event_date'],'city'=>$_POST['city'],'zip_code'=>$_POST['zip_code'],'estimated_attendees'=>$_POST['estimated_attendees']??0,'audience_type'=>$_POST['audience_type'],'request_type'=>$_POST['request_type'],'material_category'=>$_POST['material_category'],'notes'=>$_POST['notes']??'','in_service_area'=>$inArea]);

echo json_encode(['success'=>true,'request_id'=>$reqId]);
