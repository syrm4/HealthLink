-- ============================================================
-- HEALTHLINK — Community Health Request Management System
-- MySQL Schema + Synthetic Users + Seed Data
-- Stack: PHP / MySQL / HTML / CSS
-- ============================================================

-- Users table (all roles)
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  phone         VARCHAR(20),
  organization  VARCHAR(150),
  role          ENUM('community','staff','admin','leader') NOT NULL,
  preferred_lang ENUM('en','es') DEFAULT 'en',
  is_active     BOOLEAN DEFAULT TRUE,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Requests table
CREATE TABLE IF NOT EXISTS requests (
  id                         INT AUTO_INCREMENT PRIMARY KEY,
  created_at                 TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at                 TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  submitted_by               INT,

  -- Requestor info
  requestor_name             VARCHAR(100) NOT NULL,
  requestor_email            VARCHAR(150) NOT NULL,
  requestor_phone            VARCHAR(20),
  organization               VARCHAR(150) NOT NULL,

  -- Event details
  event_name                 VARCHAR(200) NOT NULL,
  event_date                 DATE NOT NULL,
  city                       VARCHAR(100) NOT NULL,
  zip_code                   VARCHAR(10) NOT NULL,
  estimated_attendees        INT,
  audience_type              VARCHAR(100),

  -- Request types matching MS Form options exactly
  -- 'mailing'          = Mailing of education materials or safety devices
  -- 'presentation'     = In-Person or Virtual Presentation
  -- 'inperson_support' = Community Health In-Person Support at event
  request_type               ENUM('mailing','presentation','inperson_support') NOT NULL,
  material_category          VARCHAR(100) NOT NULL,
  notes                      TEXT,

  -- Workflow
  status                     ENUM('submitted','in_review','approved','fulfilled','cancelled') DEFAULT 'submitted',
  assigned_to                INT,
  admin_notes                TEXT,

  -- AI-generated fields
  ai_classification          TEXT,
  ai_priority_score          TINYINT,
  ai_routing_recommendation  VARCHAR(100),
  ai_flags                   TEXT,
  in_service_area            BOOLEAN
);

-- Status history (audit trail)
CREATE TABLE IF NOT EXISTS status_history (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  request_id   INT NOT NULL,
  status       VARCHAR(50) NOT NULL,
  changed_by   INT,
  changed_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  notes        TEXT
);

-- Material categories (lookup)
CREATE TABLE IF NOT EXISTS material_categories (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  description TEXT
);

-- Service area zip codes
CREATE TABLE IF NOT EXISTS service_area_zips (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  zip_code VARCHAR(10) NOT NULL UNIQUE,
  city     VARCHAR(100),
  region   VARCHAR(100)
);

-- ============================================================
-- SEED: MATERIAL CATEGORIES
-- ============================================================
INSERT INTO material_categories (name, description) VALUES
  ('Educational materials',          'Printed handouts, brochures, guides'),
  ('Safety devices',                 'Gun locks and other safety equipment'),
  ('Behavioral reinforcement tools', 'Stickers, charts, incentive tools'),
  ('Program-specific toolkit',       'Bundled kits for specific programs'),
  ('Promotional items',              'Branded giveaways and awareness items');

-- ============================================================
-- SEED: SERVICE AREA ZIP CODES (Salt Lake Valley)
-- ============================================================
INSERT INTO service_area_zips (zip_code, city, region) VALUES
  ('84101','Salt Lake City','Central SL'),
  ('84102','Salt Lake City','Central SL'),
  ('84103','Salt Lake City','Central SL'),
  ('84104','Salt Lake City','West SL'),
  ('84105','Salt Lake City','East SL'),
  ('84106','Salt Lake City','East SL'),
  ('84107','Murray','South Valley'),
  ('84108','Salt Lake City','East SL'),
  ('84109','Salt Lake City','East SL'),
  ('84115','Salt Lake City','South SL'),
  ('84116','Salt Lake City','Northwest SL'),
  ('84117','Murray','South Valley'),
  ('84118','Kearns','West Valley'),
  ('84119','West Valley City','West Valley'),
  ('84120','West Valley City','West Valley'),
  ('84121','Cottonwood','South Valley'),
  ('84123','Murray','South Valley'),
  ('84124','Holladay','East Valley'),
  ('84128','West Valley City','West Valley'),
  ('84047','Midvale','South Valley'),
  ('84070','Sandy','South Valley'),
  ('84094','Sandy','South Valley'),
  ('84095','South Jordan','Southwest Valley'),
  ('84096','Herriman','Southwest Valley');

-- ============================================================
-- SEED: USERS (21 total)
-- All passwords = 'HealthLink2025!'
-- Hash generated with: password_hash('HealthLink2025!', PASSWORD_DEFAULT)
-- ============================================================

-- 10 Community Partners (Maria persona)
INSERT INTO users (username, password_hash, full_name, email, phone, organization, role, preferred_lang) VALUES
('maria.gonzalez',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Maria Gonzalez',    'maria.gonzalez@westsideclinic.org',  '801-555-0201', 'Westside Community Clinic',       'community', 'es'),
('rosa.mendez',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rosa Mendez',       'rosa.mendez@ogdencenter.org',        '801-555-0202', 'Ogden Community Center',          'community', 'es'),
('ana.ramirez',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ana Ramirez',       'ana.ramirez@slchealth.org',          '801-555-0203', 'SLC Community Health Clinic',     'community', 'es'),
('linda.ortiz',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Linda Ortiz',       'lortiz@southjordanhealth.org',       '801-555-0204', 'South Jordan Health Alliance',    'community', 'en'),
('patricia.flores',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Patricia Flores',   'pflores@wvcfamilycenter.org',        '801-555-0205', 'West Valley Family Center',       'community', 'es'),
('jennifer.nguyen',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jennifer Nguyen',   'jnguyen@murrayrec.org',              '801-555-0206', 'Murray Recreation Center',        'community', 'en'),
('carmen.torres',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Carmen Torres',     'ctorres@kearnsneighborhood.org',     '801-555-0207', 'Kearns Neighborhood Association', 'community', 'es'),
('ashley.johnson',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ashley Johnson',    'ajohnson@sandycommunityed.org',      '801-555-0208', 'Sandy Community Education',       'community', 'en'),
('david.park',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David Park',        'dpark@slcschools.org',               '801-555-0209', 'SLC School District',             'community', 'en'),
('tom.baker',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tom Baker',         'tbaker@herrimanrec.org',             '801-555-0210', 'Herriman Recreation Center',      'community', 'en'),

-- 5 Internal Staff (James persona)
('james.thompson',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'James Thompson',    'james.thompson@imail.org',           '801-555-0301', 'Intermountain Healthcare',        'staff', 'en'),
('emily.carter',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emily Carter',      'emily.carter@imail.org',             '801-555-0302', 'Intermountain Healthcare',        'staff', 'en'),
('michael.lee',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Michael Lee',       'michael.lee@imail.org',              '801-555-0303', 'Intermountain Healthcare',        'staff', 'en'),
('sophia.martin',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sophia Martin',     'sophia.martin@imail.org',            '801-555-0304', 'Intermountain Healthcare',        'staff', 'en'),
('kevin.wright',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kevin Wright',      'kevin.wright@imail.org',             '801-555-0305', 'Intermountain Healthcare',        'staff', 'en'),

-- 3 Admins (Sarah persona)
('sarah.admin',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Anderson',    'sarah.anderson@childrenshealth.org', '801-555-0401', 'Children Health Community',       'admin', 'en'),
('rachel.operations', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rachel Operations', 'rachel.ops@childrenshealth.org',     '801-555-0402', 'Children Health Community',       'admin', 'en'),
('diana.coordinator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Diana Coordinator', 'diana.coord@childrenshealth.org',    '801-555-0403', 'Children Health Community',       'admin', 'en'),

-- 3 Leaders (Dr. Chen persona)
('dr.chen',           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Lisa Chen',     'l.chen@childrenshealth.org',         '801-555-0501', 'Children Health Leadership',      'leader', 'en'),
('dr.patel',          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Raj Patel',     'r.patel@childrenshealth.org',        '801-555-0502', 'Children Health Leadership',      'leader', 'en'),
('dr.morgan',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Susan Morgan',  's.morgan@childrenshealth.org',       '801-555-0503', 'Children Health Leadership',      'leader', 'en');

-- ============================================================
-- SEED: SAMPLE REQUESTS
-- ============================================================
INSERT INTO requests (
  submitted_by, requestor_name, requestor_email, requestor_phone,
  organization, event_name, event_date, city, zip_code,
  estimated_attendees, audience_type, request_type, material_category,
  notes, status, ai_classification, ai_priority_score,
  ai_routing_recommendation, ai_flags, in_service_area
) VALUES
(1,  'Maria Gonzalez',  'maria.gonzalez@westsideclinic.org', '801-555-0201', 'Westside Community Clinic',       'Westside Family Health Day',     '2025-04-15', 'West Valley City', '84119', 75,  'Pediatric / families', 'inperson_support', 'Educational materials',          'Necesitamos materiales en espanol.',          'approved',  'In-person support — bilingual needed, service area confirmed', 8,  'In-person support', 'Spanish materials needed',             TRUE),
(2,  'Rosa Mendez',     'rosa.mendez@ogdencenter.org',       '801-555-0202', 'Ogden Community Center',          'Ogden Family Wellness Day',      '2025-05-10', 'Ogden',            '84401', 150, 'General community',    'mailing',          'Educational materials',          'Large event, need materials 2 weeks early.',  'submitted', 'Mailing — outside service area, high attendance',             9,  'Mailing',           'Outside service area — flag for review', FALSE),
(3,  'Ana Ramirez',     'ana.ramirez@slchealth.org',         '801-555-0203', 'SLC Community Health Clinic',     'Spring Wellness Fair',           '2025-04-20', 'Salt Lake City',   '84104', 90,  'General community',    'mailing',          'Program-specific toolkit',       NULL,                                          'in_review', 'Mailing — moderate attendance, service area',                  6,  'Mailing',           NULL,                                     TRUE),
(4,  'Linda Ortiz',     'lortiz@southjordanhealth.org',      '801-555-0204', 'South Jordan Health Alliance',    'Summer Safety Fair',             '2025-06-07', 'South Jordan',     '84095', 90,  'General community',    'presentation',     'Promotional items',              NULL,                                          'approved',  'Presentation — moderate attendance, service area confirmed',   5,  'Presentation',      NULL,                                     TRUE),
(5,  'Patricia Flores', 'pflores@wvcfamilycenter.org',       '801-555-0205', 'West Valley Family Center',       'Familia Sana Community Night',   '2025-05-01', 'West Valley City', '84120', 110, 'Pediatric / families', 'inperson_support', 'Safety devices',                 'Por favor traer gun locks para familias.',     'in_review', 'In-person support — safety devices, bilingual',               7,  'In-person support', 'Safety devices — confirm inventory',     TRUE),
(6,  'Jennifer Nguyen', 'jnguyen@murrayrec.org',             '801-555-0206', 'Murray Recreation Center',        'Murray Health and Fitness Expo', '2025-05-18', 'Murray',           '84107', 200, 'General community',    'inperson_support', 'Behavioral reinforcement tools', NULL,                                          'submitted', 'In-person support — high attendance, service area confirmed',  8,  'In-person support', 'High attendance — prioritize',            TRUE),
(7,  'Carmen Torres',   'ctorres@kearnsneighborhood.org',    '801-555-0207', 'Kearns Neighborhood Association', 'Kearns Back to School Night',    '2025-08-20', 'Kearns',           '84118', 130, 'Schools / youth',      'mailing',          'Educational materials',          'Need school-age materials.',                  'submitted', 'Mailing — youth audience, service area confirmed',            6,  'Mailing',           NULL,                                     TRUE),
(8,  'Ashley Johnson',  'ajohnson@sandycommunityed.org',     '801-555-0208', 'Sandy Community Education',       'Sandy Senior Wellness Day',      '2025-05-22', 'Sandy',            '84070', 60,  'Seniors',              'presentation',     'Educational materials',          NULL,                                          'fulfilled', 'Presentation — senior audience, service area confirmed',       5,  'Presentation',      NULL,                                     TRUE),
(9,  'David Park',      'dpark@slcschools.org',              '801-555-0209', 'SLC School District',             'Nutrition Awareness Month',      '2025-05-15', 'Salt Lake City',   '84106', 300, 'Schools / youth',      'inperson_support', 'Educational materials',          'Multi-school program across 3 campuses.',     'in_review', 'In-person support — very high attendance, multi-site',        10, 'In-person support', 'Multi-site event — needs coordination',  TRUE),
(10, 'Tom Baker',       'tbaker@herrimanrec.org',            '801-555-0210', 'Herriman Recreation Center',      'Youth Sports Health Day',        '2025-06-14', 'Herriman',         '84096', 180, 'Schools / youth',      'mailing',          'Behavioral reinforcement tools', 'Ship to rec center front desk.',              'submitted', 'Mailing — youth audience, moderate priority',                  6,  'Mailing',           NULL,                                     TRUE),
(11, 'James Thompson',  'james.thompson@imail.org',          '801-555-0301', 'Intermountain Healthcare',        'Spring Health Fair',             '2025-04-12', 'Salt Lake City',   '84101', 120, 'General community',    'inperson_support', 'Educational materials',          'Please bring blood pressure kits.',           'approved',  'In-person support — high attendance, service area confirmed',  8,  'In-person support', 'High attendance — prioritize',            TRUE),
(11, 'James Thompson',  'james.thompson@imail.org',          '801-555-0301', 'Intermountain Healthcare',        'School Wellness Week',           '2025-04-22', 'Salt Lake City',   '84105', 200, 'Schools / youth',      'presentation',     'Behavioral reinforcement tools', 'For 4 classrooms, need sticker charts.',      'in_review', 'Presentation — large youth audience',                         7,  'Presentation',      'Large order — confirm quantities',       TRUE),
(11, 'James Thompson',  'james.thompson@imail.org',          '801-555-0301', 'Intermountain Healthcare',        'Downtown Senior Expo',           '2025-05-03', 'Salt Lake City',   '84102', 80,  'Seniors',              'inperson_support', 'Promotional items',              'Balance screening station requested.',        'submitted', 'In-person support — senior audience, service area confirmed',  6,  'In-person support', NULL,                                     TRUE),
(12, 'Emily Carter',    'emily.carter@imail.org',            '801-555-0302', 'Intermountain Healthcare',        'Midvale Community Health Fair',  '2025-05-08', 'Midvale',          '84047', 95,  'General community',    'inperson_support', 'Educational materials',          NULL,                                          'approved',  'In-person support — moderate attendance, service area',        6,  'In-person support', NULL,                                     TRUE),
(13, 'Michael Lee',     'michael.lee@imail.org',             '801-555-0303', 'Intermountain Healthcare',        'Holladay Senior Health Seminar', '2025-05-29', 'Holladay',         '84124', 45,  'Seniors',              'presentation',     'Educational materials',          NULL,                                          'submitted', 'Presentation — small senior audience, service area confirmed', 4,  'Presentation',      NULL,                                     TRUE);

-- ============================================================
-- SEED: STATUS HISTORY
-- ============================================================
INSERT INTO status_history (request_id, status, changed_by, notes) VALUES
  (1,  'submitted',  1,  'Request received via HealthLink form'),
  (1,  'in_review',  16, 'Reviewed — bilingual materials confirmed available'),
  (1,  'approved',   16, 'Approved for in-person support'),
  (2,  'submitted',  2,  'Request received via HealthLink form'),
  (3,  'submitted',  3,  'Request received via HealthLink form'),
  (3,  'in_review',  16, 'Reviewing toolkit availability'),
  (4,  'submitted',  4,  'Request received via HealthLink form'),
  (4,  'approved',   17, 'Approved for presentation'),
  (5,  'submitted',  5,  'Request received via HealthLink form'),
  (5,  'in_review',  17, 'Confirming safety device inventory'),
  (6,  'submitted',  6,  'Request received via HealthLink form'),
  (7,  'submitted',  7,  'Request received via HealthLink form'),
  (8,  'submitted',  8,  'Request received via HealthLink form'),
  (8,  'approved',   16, 'Approved for presentation'),
  (8,  'fulfilled',  16, 'Materials delivered — event completed'),
  (9,  'submitted',  9,  'Request received via HealthLink form'),
  (9,  'in_review',  17, 'Multi-site coordination required'),
  (10, 'submitted',  10, 'Request received via HealthLink form'),
  (11, 'submitted',  11, 'Request received via HealthLink form'),
  (11, 'in_review',  16, 'Reviewed and verified service area'),
  (11, 'approved',   16, 'Approved for in-person support'),
  (12, 'submitted',  11, 'Request received via HealthLink form'),
  (12, 'in_review',  16, 'Verifying quantities'),
  (13, 'submitted',  11, 'Request received via HealthLink form'),
  (14, 'submitted',  12, 'Request received via HealthLink form'),
  (14, 'approved',   18, 'Approved for in-person support'),
  (15, 'submitted',  13, 'Request received via HealthLink form');