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
    preferred_lang ENUM('en','es') NOT NULL DEFAULT 'en',
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
    status ENUM('submitted','in_review','approved','sent_to_qualtrics','fulfilled') DEFAULT 'submitted',
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

// All demo accounts use password: pass123
$hash = password_hash('pass123', PASSWORD_DEFAULT);
$ins  = $db->prepare('INSERT INTO users (username,password_hash,full_name,email,phone,organization,role,preferred_lang) VALUES (?,?,?,?,?,?,?,?)');

$users = [
    // 10 Community partners (Maria persona)
    ['maria',     'Maria Gonzalez',  'maria@westsideclinic.org',         '801-555-0201', 'Westside Community Clinic',        'community', 'es'],
    ['rosa',      'Rosa Mendez',     'rosa@ogdencenter.org',              '801-555-0202', 'Ogden Community Center',           'community', 'es'],
    ['linda',     'Linda Ortiz',     'linda@southjordanhealth.org',       '801-555-0203', 'South Jordan Health Alliance',     'community', 'es'],
    ['carmen',    'Carmen Reyes',    'carmen@saltlakeparent.org',         '801-555-0204', 'Salt Lake Parent Resource Center', 'community', 'es'],
    ['sofia',     'Sofia Ramirez',   'sofia@kearnsrec.org',               '801-555-0205', 'Kearns Recreation Center',         'community', 'es'],
    ['david',     'David Park',      'david@slcschools.org',              '801-555-0206', 'SLC School District',              'community', 'en'],
    ['tom',       'Tom Baker',       'tom@herrimanrec.org',               '801-555-0207', 'Herriman Recreation Center',       'community', 'en'],
    ['patricia',  'Patricia White',  'patricia@murrayfamilyclinic.org',   '801-555-0208', 'Murray Family Clinic',             'community', 'en'],
    ['james.lee', 'James Lee',       'james.lee@westvalleycommunity.org', '801-555-0209', 'West Valley Community Center',     'community', 'en'],
    ['angela',    'Angela Moore',    'angela@holladaywellness.org',       '801-555-0210', 'Holladay Wellness Coalition',      'community', 'en'],
    // 5 Internal staff (James persona)
    ['james',     'James Thompson',  'james@imail.org',                   '801-555-0101', 'Intermountain Healthcare',         'staff',     'en'],
    ['emily',     'Emily Harris',    'emily@imail.org',                   '801-555-0102', 'Intermountain Healthcare',         'staff',     'en'],
    ['michael',   'Michael Clark',   'michael@imail.org',                 '801-555-0103', 'Intermountain Healthcare',         'staff',     'en'],
    ['jessica',   'Jessica Wang',    'jessica@imail.org',                 '801-555-0104', 'Intermountain Healthcare',         'staff',     'en'],
    ['kevin',     'Kevin Turner',    'kevin@imail.org',                   '801-555-0105', 'Intermountain Healthcare',         'staff',     'en'],
    // 3 Admins (Sarah persona)
    ['sarah',     'Sarah Johnson',   'sarah@childrenshealth.org',         '801-555-0301', "Children's Health Community",      'admin',     'en'],
    ['rachel',    'Rachel Kim',      'rachel@childrenshealth.org',        '801-555-0302', "Children's Health Community",      'admin',     'en'],
    ['mark',      'Mark Davis',      'mark@childrenshealth.org',          '801-555-0303', "Children's Health Community",      'admin',     'en'],
    // 3 Leaders (Dr. Chen persona)
    ['dr.chen',   'Dr. Linda Chen',  'dr.chen@childrenshealth.org',       '801-555-0401', "Children's Health \u2014 Leadership", 'leader',    'en'],
    ['dr.patel',  'Dr. Raj Patel',   'dr.patel@childrenshealth.org',      '801-555-0402', "Children's Health \u2014 Leadership", 'leader',    'en'],
    ['dr.nguyen', 'Dr. Anh Nguyen',  'dr.nguyen@childrenshealth.org',     '801-555-0403', "Children's Health \u2014 Leadership", 'leader',    'en'],
];
foreach ($users as $u) $ins->execute([$u[0], $hash, $u[1], $u[2], $u[3], $u[4], $u[5], $u[6]]);

$zips = [
    ['84101','Salt Lake City','Central SL'],   ['84102','Salt Lake City','Central SL'],
    ['84103','Salt Lake City','Central SL'],   ['84104','Salt Lake City','West SL'],
    ['84105','Salt Lake City','East SL'],      ['84106','Salt Lake City','East SL'],
    ['84107','Murray','South Valley'],         ['84108','Salt Lake City','East SL'],
    ['84109','Salt Lake City','East SL'],      ['84115','Salt Lake City','South SL'],
    ['84116','Salt Lake City','Northwest SL'], ['84117','Murray','South Valley'],
    ['84118','Kearns','West Valley'],          ['84119','West Valley City','West Valley'],
    ['84120','West Valley City','West Valley'],['84121','Cottonwood','South Valley'],
    ['84123','Murray','South Valley'],         ['84124','Holladay','East Valley'],
    ['84128','West Valley City','West Valley'],['84047','Midvale','South Valley'],
    ['84070','Sandy','South Valley'],          ['84094','Sandy','South Valley'],
    ['84095','South Jordan','Southwest Valley'],['84096','Herriman','Southwest Valley'],
];
$insZ = $db->prepare('INSERT INTO service_area_zips (zip_code,city,region) VALUES (?,?,?)');
foreach ($zips as $z) $insZ->execute($z);

$cats = [
    ['Educational materials',         'Printed handouts, brochures, guides'],
    ['Safety devices',                'Gun locks and other safety equipment'],
    ['Behavioral reinforcement tools','Stickers, charts, incentive tools'],
    ['Program-specific toolkit',      'Bundled kits for specific programs'],
    ['Promotional items',             'Branded giveaways and awareness items'],
];
$insC = $db->prepare('INSERT INTO material_categories (name,description) VALUES (?,?)');
foreach ($cats as $c) $insC->execute($c);

$uid = function(string $u) use ($db): int {
    $s = $db->prepare('SELECT id FROM users WHERE username=?');
    $s->execute([$u]);
    return (int)$s->fetchColumn();
};

$insR = $db->prepare(
    'INSERT INTO requests
     (user_id,requestor_name,requestor_email,requestor_phone,organization,is_internal,
      event_name,event_date,city,zip_code,estimated_attendees,audience_type,
      request_type,material_category,notes,status,ai_classification,ai_priority_score,
      ai_routing_recommendation,ai_flags,in_service_area)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
);

$samples = [
    [$uid('james'),'James Thompson','james@imail.org','801-555-0101','Intermountain Healthcare',1,
     'Spring Health Fair','2025-04-12','Salt Lake City','84101',120,'General community',
     'inperson_support','Educational materials','Please bring blood pressure kits and handouts.',
     'approved','In-person support at high-attendance event, service area confirmed',
     8,'In-person support','High attendance — prioritize',1],
    [$uid('maria'),'Maria Gonzalez','maria@westsideclinic.org','801-555-0201','Westside Community Clinic',0,
     'Westside Community Day','2025-03-28','West Valley City','84119',60,'Pediatric / families',
     'mailing','Program-specific toolkit','Necesitamos materiales en español también.',
     'fulfilled','Mailing — bilingual materials needed',
     5,'Mailing','Spanish materials requested',1],
    [$uid('james'),'James Thompson','james@imail.org','801-555-0101','Intermountain Healthcare',1,
     'School Wellness Week','2025-04-22','Salt Lake City','84105',200,'Schools / youth',
     'presentation','Behavioral reinforcement tools','For 4 classrooms, need sticker charts.',
     'in_review','Large youth presentation with high volume material order',
     7,'In-person presentation','Large order — confirm quantities',1],
    [$uid('rosa'),'Rosa Mendez','rosa@ogdencenter.org','801-555-0202','Ogden Community Center',0,
     'Ogden Family Wellness Day','2025-05-10','Ogden','84401',150,'General community',
     'mailing','Educational materials','Large event, need materials 2 weeks early.',
     'submitted','Mailing — outside service area, high attendance',
     9,'Mailing','Outside service area — flag for review',0],
    [$uid('michael'),'Michael Clark','michael@imail.org','801-555-0103','Intermountain Healthcare',1,
     'Nutrition Awareness Month','2025-05-15','Salt Lake City','84106',300,'Schools / youth',
     'inperson_support','Educational materials','Multi-school program across 3 campuses.',
     'in_review','Very high attendance multi-site event requiring coordination',
     10,'In-person support','Multi-site event — needs coordination',1],
    [$uid('linda'),'Linda Ortiz','linda@southjordanhealth.org','801-555-0203','South Jordan Health Alliance',0,
     'Summer Safety Fair','2025-06-07','South Jordan','84095',90,'General community',
     'presentation','Safety devices',null,
     'approved','Community presentation — moderate attendance, service area confirmed',
     5,'In-person presentation',null,1],
    [$uid('tom'),'Tom Baker','tom@herrimanrec.org','801-555-0207','Herriman Recreation Center',0,
     'Youth Sports Health Day','2025-06-14','Herriman','84096',180,'Schools / youth',
     'mailing','Behavioral reinforcement tools','Ship to rec center front desk.',
     'submitted','Mailing — youth audience, moderate priority',
     6,'Mailing',null,1],
    [$uid('david'),'David Park','david@slcschools.org','801-555-0206','SLC School District',0,
     'Bilingual Health Workshop','2025-04-28','West Valley City','84120',75,'General community',
     'presentation','Educational materials','Please provide bilingual presenter if available.',
     'submitted','Bilingual presentation request, moderate priority',
     6,'In-person presentation','Bilingual presenter requested',1],
];
foreach ($samples as $s) $insR->execute($s);

$insH = $db->prepare('INSERT INTO status_history (request_id,status,changed_by,notes) VALUES (?,?,?,?)');
$hist = [
    [1,'submitted','System',       'Request received via web form'],
    [1,'in_review','Sarah Johnson','Reviewed and verified service area'],
    [1,'approved', 'Sarah Johnson','Approved for in-person support'],
    [2,'submitted','System',       'Request received via web form'],
    [2,'in_review','Sarah Johnson','Confirmed bilingual materials available'],
    [2,'approved', 'Sarah Johnson','Approved for mailing'],
    [2,'sent_to_qualtrics','System','Exported to Qualtrics for fulfillment tracking'],
    [2,'fulfilled','Sarah Johnson','Materials shipped March 20'],
    [3,'submitted','System',       'Request received via web form'],
    [3,'in_review','Sarah Johnson','Verifying quantities with warehouse'],
    [4,'submitted','System',       'Request received via web form'],
    [5,'submitted','System',       'Request received via web form'],
    [5,'in_review','Rachel Kim',   'Multi-site coordination required'],
    [6,'submitted','System',       'Request received via web form'],
    [6,'approved', 'Sarah Johnson','Approved for presentation'],
    [7,'submitted','System',       'Request received via web form'],
    [8,'submitted','System',       'Request received via web form'],
];
foreach ($hist as $h) $insH->execute($h);

$allUsers = $db->query('SELECT username, role, full_name, preferred_lang FROM users ORDER BY role, username')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HealthLink Setup</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="container">
    <div class="card" style="max-width:720px; margin:40px auto;">
        <h2>HealthLink Setup Complete</h2>
        <div class="alert alert-success">Database initialized. 21 users created, 8 sample requests inserted.</div>
        <div class="alert alert-info">Default password for all accounts: <strong>pass123</strong></div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Username</th><th>Role</th><th>Lang</th><th>Full name</th></tr></thead>
                <tbody>
                <?php foreach ($allUsers as $u): ?>
                <tr>
                    <td class="td-name"><?= htmlspecialchars($u['username']) ?></td>
                    <td><span class="badge badge-role-<?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                    <td class="td-muted"><?= htmlspecialchars(strtoupper($u['preferred_lang'])) ?></td>
                    <td class="td-muted"><?= htmlspecialchars($u['full_name']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:var(--space-lg);">
            <a href="/index.php" class="btn btn-primary">Go to login</a>
        </div>
    </div>
</div>
</body>
</html>
