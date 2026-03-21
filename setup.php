<?php
require_once __DIR__ . '/config/db.php';
$db = getDB();
$db->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach (['status_history','requests','material_categories','service_area_zips','users'] as $t) {
    $db->exec("DROP TABLE IF EXISTS $t");
}
$db->exec('SET FOREIGN_KEY_CHECKS = 1');

$db->exec("CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(200) NOT NULL,
    email VARCHAR(200) NOT NULL,
    phone VARCHAR(50),
    organization VARCHAR(200),
    role ENUM('community','staff','admin','leader') NOT NULL DEFAULT 'community',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE service_area_zips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zip_code VARCHAR(10) NOT NULL UNIQUE,
    city VARCHAR(100),
    region VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE material_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    requestor_name VARCHAR(200) NOT NULL,
    requestor_email VARCHAR(200) NOT NULL,
    requestor_phone VARCHAR(50),
    organization VARCHAR(200) NOT NULL,
    is_internal TINYINT(1) DEFAULT 0,
    event_name VARCHAR(300) NOT NULL,
    event_date DATE NOT NULL,
    city VARCHAR(100) NOT NULL,
    zip_code VARCHAR(10) NOT NULL,
    estimated_attendees INT,
    audience_type VARCHAR(100),
    request_type ENUM('mailing','presentation','inperson_support') NOT NULL,
    material_category VARCHAR(200) NOT NULL,
    notes TEXT,
    status ENUM('submitted','in_review','approved','fulfilled') DEFAULT 'submitted',
    admin_notes TEXT,
    assigned_staff VARCHAR(200),
    ai_classification TEXT,
    ai_priority_score INT DEFAULT 5,
    ai_routing_recommendation VARCHAR(200),
    ai_flags TEXT,
    in_service_area TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    changed_by VARCHAR(200) NOT NULL,
    notes TEXT,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$hash = password_hash('HealthLink2025!', PASSWORD_DEFAULT);
$ins = $db->prepare('INSERT INTO users (username,password_hash,full_name,email,phone,organization,role) VALUES (?,?,?,?,?,?,?)');

$users = [
    ['maria.gonzalez','Maria Gonzalez','maria.gonzalez@westsideclinic.org','801-555-0101','Westside Community Clinic','community'],
    ['ana.rodriguez','Ana Rodriguez','ana.rodriguez@hispaniccc.org','801-555-0102','Hispanic Community Center','community'],
    ['sofia.martinez','Sofia Martinez','sofia.martinez@ogdenfamily.org','801-555-0103','Ogden Family Services','community'],
    ['isabella.torres','Isabella Torres','isabella.torres@wvhealthclinic.org','801-555-0104','West Valley Health Clinic','community'],
    ['lucia.hernandez','Lucia Hernandez','lucia.hernandez@sllatinorc.org','801-555-0105','SL Latinx Resource Center','community'],
    ['carmen.vega','Carmen Vega','carmen.vega@provohealth.org','801-555-0106','Provo Community Health','community'],
    ['rosa.mendez','Rosa Mendez','rosa.mendez@ogdencenter.org','801-555-0107','Ogden Community Center','community'],
    ['elena.ramirez','Elena Ramirez','elena.ramirez@midvalefamily.org','801-555-0108','Midvale Family Services','community'],
    ['patricia.lopez','Patricia Lopez','patricia.lopez@sandycenter.org','801-555-0109','Sandy Community Center','community'],
    ['gloria.sanchez','Gloria Sanchez','gloria.sanchez@southjordanhealth.org','801-555-0110','South Jordan Health Alliance','community'],
    ['james.thompson','James Thompson','james.thompson@imail.org','801-555-0201','Intermountain Healthcare','staff'],
    ['michael.chen','Michael Chen','michael.chen@imail.org','801-555-0202','Intermountain Healthcare','staff'],
    ['robert.williams','Robert Williams','robert.williams@imail.org','801-555-0203','Intermountain Healthcare','staff'],
    ['daniel.johnson','Daniel Johnson','daniel.johnson@imail.org','801-555-0204','Intermountain Healthcare','staff'],
    ['kevin.park','Kevin Park','kevin.park@imail.org','801-555-0205','Intermountain Healthcare','staff'],
    ['sarah.mitchell','Sarah Mitchell','sarah.mitchell@imail.org','801-555-0301','Community Health Operations','admin'],
    ['jennifer.adams','Jennifer Adams','jennifer.adams@imail.org','801-555-0302','Community Health Operations','admin'],
    ['lisa.bennett','Lisa Bennett','lisa.bennett@imail.org','801-555-0303','Community Health Operations','admin'],
    ['dr.chen','Dr. Emily Chen','emily.chen@imail.org','801-555-0401','Community Health Leadership','leader'],
    ['dr.johnson','Dr. Marcus Johnson','marcus.johnson@imail.org','801-555-0402','Community Health Leadership','leader'],
    ['dr.patel','Dr. Priya Patel','priya.patel@imail.org','801-555-0403','Community Health Leadership','leader'],
];
foreach ($users as $u) $ins->execute([$u[0],$hash,$u[1],$u[2],$u[3],$u[4],$u[5]]);

$zips = [['84101','Salt Lake City','Central SL'],['84102','Salt Lake City','Central SL'],['84103','Salt Lake City','Central SL'],['84104','Salt Lake City','West SL'],['84105','Salt Lake City','East SL'],['84106','Salt Lake City','East SL'],['84107','Murray','South Valley'],['84108','Salt Lake City','East SL'],['84109','Salt Lake City','East SL'],['84115','Salt Lake City','South SL'],['84116','Salt Lake City','Northwest SL'],['84117','Murray','South Valley'],['84118','Kearns','West Valley'],['84119','West Valley City','West Valley'],['84120','West Valley City','West Valley'],['84121','Cottonwood','South Valley'],['84123','Murray','South Valley'],['84124','Holladay','East Valley'],['84128','West Valley City','West Valley'],['84047','Midvale','South Valley'],['84070','Sandy','South Valley'],['84094','Sandy','South Valley'],['84095','South Jordan','Southwest Valley'],['84096','Herriman','Southwest Valley']];
$insZ = $db->prepare('INSERT INTO service_area_zips (zip_code,city,region) VALUES (?,?,?)');
foreach ($zips as $z) $insZ->execute($z);

$cats = [['Educational materials','Printed handouts, brochures, guides'],['Safety devices','Gun locks and other safety equipment'],['Behavioral reinforcement tools','Stickers, charts, incentive tools'],['Program-specific toolkit','Bundled kits for specific programs'],['Promotional items','Branded giveaways and awareness items']];
$insC = $db->prepare('INSERT INTO material_categories (name,description) VALUES (?,?)');
foreach ($cats as $c) $insC->execute($c);

$uid = function(string $u) use ($db): int { $s=$db->prepare('SELECT id FROM users WHERE username=?'); $s->execute([$u]); return (int)$s->fetchColumn(); };
$insR = $db->prepare('INSERT INTO requests (user_id,requestor_name,requestor_email,requestor_phone,organization,is_internal,event_name,event_date,city,zip_code,estimated_attendees,audience_type,request_type,material_category,notes,status,ai_classification,ai_priority_score,ai_routing_recommendation,ai_flags,in_service_area) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');

$samples = [
    [$uid('james.thompson'),'James Thompson','james.thompson@imail.org','801-555-0201','Intermountain Healthcare',1,'Spring Health Fair','2025-04-12','Salt Lake City','84101',120,'General community','inperson_support','Educational materials','Please bring blood pressure kits and handouts.','approved','In-person support at high-attendance community event, service area confirmed',8,'In-person support','High attendance — prioritize',1],
    [$uid('maria.gonzalez'),'Maria Gonzalez','maria.gonzalez@westsideclinic.org','801-555-0101','Westside Community Clinic',0,'Westside Community Day','2025-03-28','West Valley City','84119',60,'Pediatric / families','mailing','Program-specific toolkit','Necesitamos materiales en español también.','fulfilled','Mailing request for pediatric community event, bilingual materials needed',5,'Mailing','Spanish materials requested',1],
    [$uid('james.thompson'),'James Thompson','james.thompson@imail.org','801-555-0201','Intermountain Healthcare',1,'School Wellness Week','2025-04-22','Salt Lake City','84105',200,'Schools / youth','presentation','Behavioral reinforcement tools','For 4 classrooms, need sticker charts.','in_review','Large youth presentation with high volume material order',7,'In-person presentation','Large order — confirm quantities',1],
    [$uid('rosa.mendez'),'Rosa Mendez','rosa.mendez@ogdencenter.org','801-555-0107','Ogden Community Center',0,'Ogden Family Wellness Day','2025-05-10','Ogden','84401',150,'General community','mailing','Educational materials','Large event, need materials 2 weeks early.','submitted','Mailing request outside service area — high attendance, early delivery needed',9,'Mailing','Outside service area — flag for review',0],
    [$uid('michael.chen'),'Michael Chen','michael.chen@imail.org','801-555-0202','Intermountain Healthcare',1,'Nutrition Awareness Month','2025-05-15','Salt Lake City','84106',300,'Schools / youth','inperson_support','Educational materials','Multi-school program across 3 campuses.','in_review','Very high attendance multi-site in-person support event requiring coordination',10,'In-person support','Multi-site event — needs coordination',1],
    [$uid('gloria.sanchez'),'Gloria Sanchez','gloria.sanchez@southjordanhealth.org','801-555-0110','South Jordan Health Alliance',0,'Summer Safety Fair','2025-06-07','South Jordan','84095',90,'General community','presentation','Promotional items',null,'approved','Community presentation with moderate attendance, service area confirmed',5,'In-person presentation',null,1],
    [$uid('elena.ramirez'),'Elena Ramirez','elena.ramirez@midvalefamily.org','801-555-0108','Midvale Family Services',0,'Youth Sports Health Day','2025-06-14','Midvale','84047',180,'Schools / youth','mailing','Behavioral reinforcement tools','Ship to rec center front desk.','submitted','Mailing for youth sports event, moderate priority',6,'Mailing',null,1],
    [$uid('ana.rodriguez'),'Ana Rodriguez','ana.rodriguez@hispaniccc.org','801-555-0102','Hispanic Community Center',0,'Bilingual Health Workshop','2025-04-28','West Valley City','84120',75,'General community','presentation','Educational materials','Please provide bilingual presenter if available.','submitted','Bilingual presentation request, moderate priority',6,'In-person presentation','Bilingual presenter requested',1],
];
foreach ($samples as $s) $insR->execute($s);

$insH = $db->prepare('INSERT INTO status_history (request_id,status,changed_by,notes) VALUES (?,?,?,?)');
$hist = [[1,'submitted','System','Request received via web form'],[1,'in_review','Sarah Mitchell','Reviewed and verified service area'],[1,'approved','Sarah Mitchell','Approved for in-person support'],[2,'submitted','System','Request received via web form'],[2,'in_review','Sarah Mitchell','Confirmed bilingual materials available'],[2,'approved','Sarah Mitchell','Approved for mailing'],[2,'fulfilled','Sarah Mitchell','Materials shipped March 20'],[3,'submitted','System','Request received via web form'],[3,'in_review','Sarah Mitchell','Verifying quantities with warehouse'],[4,'submitted','System','Request received via web form'],[5,'submitted','System','Request received via web form'],[5,'in_review','Jennifer Adams','Multi-site coordination required'],[6,'submitted','System','Request received via web form'],[6,'approved','Sarah Mitchell','Approved for presentation'],[7,'submitted','System','Request received via web form'],[8,'submitted','System','Request received via web form']];
foreach ($hist as $h) $insH->execute($h);

$allUsers = $db->query('SELECT username,role,full_name FROM users ORDER BY role,username')->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>HealthLink Setup</title><link rel="stylesheet" href="/assets/style.css"></head><body>
<div class="container">
<div class="card" style="max-width:700px;margin:40px auto;">
<h2 style="margin-bottom:16px;font-size:18px;">HealthLink Setup Complete</h2>
<div class="alert alert-success">Database initialized. 21 users created, 8 sample requests inserted.</div>
<p style="font-size:13px;color:var(--text-muted);margin:12px 0;">Default password for all accounts: <strong>HealthLink2025!</strong></p>
<div class="table-wrap"><table>
<thead><tr><th>Username</th><th>Role</th><th>Full name</th></tr></thead>
<tbody>
<?php foreach ($allUsers as $u): ?>
<tr><td><?= htmlspecialchars($u['username']) ?></td><td><span class="badge role-<?= $u['role'] ?>"><?= $u['role'] ?></span></td><td><?= htmlspecialchars($u['full_name']) ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<div style="margin-top:16px;"><a href="/index.php" class="btn btn-primary">Go to login</a></div>
</div></div></body></html>
