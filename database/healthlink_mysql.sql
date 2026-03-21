-- ============================================================
-- HEALTHLINK — Community Health Request Management System
-- MySQL Schema for MAMP (localhost)
-- W3Schools best practices: MySQLi, prepared statements,
-- password_hash(), VARCHAR typed columns, proper FK syntax
-- ============================================================

CREATE DATABASE IF NOT EXISTS healthlink
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE healthlink;

-- ============================================================
-- TABLE 1: users
-- Roles: community | staff | admin | leader
-- Passwords stored as bcrypt hashes (password_hash())
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  email         VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(100) NOT NULL,
  organization  VARCHAR(150),
  phone         VARCHAR(20),
  role          ENUM('community','staff','admin','leader') NOT NULL DEFAULT 'community',
  preferred_lang ENUM('en','es') NOT NULL DEFAULT 'en',
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE 2: requests (core table)
-- request_type matches original MS Form options exactly
-- ============================================================
CREATE TABLE IF NOT EXISTS requests (
  id                         INT AUTO_INCREMENT PRIMARY KEY,
  created_at                 TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  updated_at                 TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  -- Requestor (FK to users — NULL allowed for anonymous submissions)
  user_id                    INT,
  requestor_name             VARCHAR(100) NOT NULL,
  requestor_email            VARCHAR(100) NOT NULL,
  requestor_phone            VARCHAR(20),
  organization               VARCHAR(150) NOT NULL,
  is_internal                TINYINT(1)   NOT NULL DEFAULT 0,

  -- Event details
  event_name                 VARCHAR(200) NOT NULL,
  event_date                 DATE         NOT NULL,
  city                       VARCHAR(100) NOT NULL,
  zip_code                   VARCHAR(10)  NOT NULL,
  estimated_attendees        INT,
  audience_type              VARCHAR(100),

  -- Request details
  -- request_type valid values (matching original MS Form):
  --   'mailing'          = Mailing of education materials or safety devices
  --   'presentation'     = In-Person or Virtual Presentation
  --   'inperson_support' = Community Health In-Person Support at event
  --                        with Education materials or safety devices
  request_type               VARCHAR(50)  NOT NULL,
  material_category          VARCHAR(100) NOT NULL,
  notes                      TEXT,

  -- Workflow status
  status                     ENUM('submitted','in_review','approved','sent_to_qualtrics','fulfilled')
                             NOT NULL DEFAULT 'submitted',
  admin_notes                TEXT,
  assigned_staff             VARCHAR(100),

  -- AI-generated fields
  ai_classification          VARCHAR(255),
  ai_priority_score          INT,
  ai_routing_recommendation  VARCHAR(100),
  ai_flags                   VARCHAR(255),
  in_service_area            TINYINT(1),

  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- TABLE 3: status_history (audit trail)
-- ============================================================
CREATE TABLE IF NOT EXISTS status_history (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  request_id  INT          NOT NULL,
  status      VARCHAR(50)  NOT NULL,
  changed_by  VARCHAR(100) NOT NULL,
  changed_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  notes       TEXT,
  FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE 4: material_categories (lookup)
-- ============================================================
CREATE TABLE IF NOT EXISTS material_categories (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  description VARCHAR(255)
);

-- ============================================================
-- TABLE 5: service_area_zips (geographic routing)
-- ============================================================
CREATE TABLE IF NOT EXISTS service_area_zips (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  zip_code VARCHAR(10)  NOT NULL UNIQUE,
  city     VARCHAR(100),
  region   VARCHAR(100)
);

-- ============================================================
-- SEED DATA — Material categories
-- ============================================================
INSERT INTO material_categories (name, description) VALUES
  ('Educational materials',          'Printed handouts, brochures, guides'),
  ('Safety devices',                 'Gun locks and other safety equipment'),
  ('Behavioral reinforcement tools', 'Stickers, charts, incentive tools'),
  ('Program-specific toolkit',       'Bundled kits for specific programs'),
  ('Promotional items',              'Branded giveaways and awareness items');

-- ============================================================
-- SEED DATA — Service area zip codes (Salt Lake Valley)
-- ============================================================
INSERT INTO service_area_zips (zip_code, city, region) VALUES
  ('84101', 'Salt Lake City',   'Central SL'),
  ('84102', 'Salt Lake City',   'Central SL'),
  ('84103', 'Salt Lake City',   'Central SL'),
  ('84104', 'Salt Lake City',   'West SL'),
  ('84105', 'Salt Lake City',   'East SL'),
  ('84106', 'Salt Lake City',   'East SL'),
  ('84107', 'Murray',           'South Valley'),
  ('84108', 'Salt Lake City',   'East SL'),
  ('84109', 'Salt Lake City',   'East SL'),
  ('84115', 'Salt Lake City',   'South SL'),
  ('84116', 'Salt Lake City',   'Northwest SL'),
  ('84117', 'Murray',           'South Valley'),
  ('84118', 'Kearns',           'West Valley'),
  ('84119', 'West Valley City', 'West Valley'),
  ('84120', 'West Valley City', 'West Valley'),
  ('84121', 'Cottonwood',       'South Valley'),
  ('84123', 'Murray',           'South Valley'),
  ('84124', 'Holladay',         'East Valley'),
  ('84128', 'West Valley City', 'West Valley'),
  ('84047', 'Midvale',          'South Valley'),
  ('84070', 'Sandy',            'South Valley'),
  ('84094', 'Sandy',            'South Valley'),
  ('84095', 'South Jordan',     'Southwest Valley'),
  ('84096', 'Herriman',         'Southwest Valley');

-- ============================================================
-- SEED DATA — Users (21 total)
-- All passwords are 'HealthLink2025!' hashed with bcrypt
-- Hash generated via: password_hash('HealthLink2025!', PASSWORD_BCRYPT)
-- ============================================================

-- 10 Community partners (Maria persona — bilingual mix)
INSERT INTO users (username, email, password_hash, full_name, organization, phone, role, preferred_lang) VALUES
('maria.gonzalez',  'maria.gonzalez@westsideclinic.org',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'Maria Gonzalez',    'Westside Community Clinic',       '801-555-0201', 'community', 'es'),
('rosa.mendez',     'rosa.mendez@ogdencenter.org',           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'Rosa Mendez',       'Ogden Community Center',          '801-555-0202', 'community', 'es'),
('linda.ortiz',     'linda.ortiz@southjordanhealth.org',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'Linda Ortiz',       'South Jordan Health Alliance',    '801-555-0203', 'community', 'es'),
('carmen.reyes',    'carmen.reyes@saltlakeparent.org',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'Carmen Reyes',      'Salt Lake Parent Resource Center','801-555-0204', 'community', 'es'),
('sofia.ramirez',   'sofia.ramirez@kearnsrec.org',           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'Sofia Ramirez',     'Kearns Recreation Center',        '801-555-0205', 'community', 'es'),
('david.park',      'dpark@slcschools.org',                  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'David Park',        'SLC School District',             '801-555-0206', 'community', 'en'),
('tom.baker',       'tbaker@herrimanrec.org',                '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'Tom Baker',         'Herriman Recreation Center',      '801-555-0207', 'community', 'en'),
('patricia.white',  'pwhite@murrayfamilyclinic.org',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'Patricia White',    'Murray Family Clinic',            '801-555-0208', 'community', 'en'),
('james.lee',       'jlee@westvalleycommunity.org',          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'James Lee',         'West Valley Community Center',    '801-555-0209', 'community', 'en'),
('angela.moore',    'amoore@holladaywellness.org',           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'Angela Moore',      'Holladay Wellness Coalition',     '801-555-0210', 'community', 'en');

-- 5 Internal staff users (James persona)
INSERT INTO users (username, email, password_hash, full_name, organization, phone, role, preferred_lang) VALUES
('james.thompson',  'james.thompson@imail.org',              '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'James Thompson',    'Intermountain Healthcare',        '801-555-0101', 'staff', 'en'),
('emily.harris',    'emily.harris@imail.org',                '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'Emily Harris',      'Intermountain Healthcare',        '801-555-0102', 'staff', 'en'),
('michael.clark',   'michael.clark@imail.org',               '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'Michael Clark',     'Intermountain Healthcare',        '801-555-0103', 'staff', 'en'),
('jessica.wang',    'jessica.wang@imail.org',                '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'Jessica Wang',      'Intermountain Healthcare',        '801-555-0104', 'staff', 'en'),
('kevin.turner',    'kevin.turner@imail.org',                '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'Kevin Turner',      'Intermountain Healthcare',        '801-555-0105', 'staff', 'en');

-- 3 Admin users (Sarah persona)
INSERT INTO users (username, email, password_hash, full_name, organization, phone, role, preferred_lang) VALUES
('sarah.admin',     'sarah.johnson@childrenshealth.org',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'Sarah Johnson',     'Children''s Health Community',    '801-555-0301', 'admin', 'en'),
('rachel.admin',    'rachel.kim@childrenshealth.org',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'Rachel Kim',        'Children''s Health Community',    '801-555-0302', 'admin', 'en'),
('mark.admin',      'mark.davis@childrenshealth.org',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'Mark Davis',        'Children''s Health Community',    '801-555-0303', 'admin', 'en');

-- 3 Leader/Doctor users (Dr. Chen persona)
INSERT INTO users (username, email, password_hash, full_name, organization, phone, role, preferred_lang) VALUES
('dr.chen',         'dr.chen@childrenshealth.org',           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'Dr. Linda Chen',    'Children''s Health — Leadership', '801-555-0401', 'leader', 'en'),
('dr.patel',        'dr.patel@childrenshealth.org',          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'Dr. Raj Patel',     'Children''s Health — Leadership', '801-555-0402', 'leader', 'en'),
('dr.nguyen',       'dr.nguyen@childrenshealth.org',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uU.w.xBCy', 'Dr. Anh Nguyen',    'Children''s Health — Leadership', '801-555-0403', 'leader', 'en');

-- ============================================================
-- SEED DATA — Sample requests (8 requests linked to users)
-- ============================================================
INSERT INTO requests (
  user_id, requestor_name, requestor_email, requestor_phone,
  organization, is_internal,
  event_name, event_date, city, zip_code, estimated_attendees, audience_type,
  request_type, material_category, notes,
  status, ai_classification, ai_priority_score,
  ai_routing_recommendation, ai_flags, in_service_area
) VALUES
(
  11, 'James Thompson', 'james.thompson@imail.org', '801-555-0101',
  'Intermountain Healthcare', 1,
  'Spring Health Fair', '2025-04-12', 'Salt Lake City', '84101', 120, 'General community',
  'inperson_support', 'Educational materials', 'Please bring blood pressure kits and handouts.',
  'approved',
  'In-person support — high attendance, service area confirmed',
  8, 'In-person support', 'High attendance — prioritize', 1
),
(
  1, 'Maria Gonzalez', 'maria.gonzalez@westsideclinic.org', '801-555-0201',
  'Westside Community Clinic', 0,
  'Westside Community Day', '2025-03-28', 'West Valley City', '84119', 60, 'Pediatric / families',
  'mailing', 'Program-specific toolkit', 'Necesitamos materiales en español también.',
  'fulfilled',
  'Mailing — bilingual materials needed',
  5, 'Mailing', 'Spanish materials requested', 1
),
(
  11, 'James Thompson', 'james.thompson@imail.org', '801-555-0101',
  'Intermountain Healthcare', 1,
  'School Wellness Week', '2025-04-22', 'Salt Lake City', '84105', 200, 'Schools / youth',
  'presentation', 'Behavioral reinforcement tools', 'For 4 classrooms, need sticker charts.',
  'in_review',
  'In-person presentation — large youth audience',
  7, 'In-person presentation', 'Large order — confirm quantities', 1
),
(
  11, 'James Thompson', 'james.thompson@imail.org', '801-555-0101',
  'Intermountain Healthcare', 1,
  'Downtown Senior Expo', '2025-05-03', 'Salt Lake City', '84102', 80, 'Seniors',
  'inperson_support', 'Promotional items', 'Balance screening station requested.',
  'submitted',
  'In-person support — senior audience, service area confirmed',
  6, 'In-person support', NULL, 1
),
(
  2, 'Rosa Mendez', 'rosa.mendez@ogdencenter.org', '801-555-0202',
  'Ogden Community Center', 0,
  'Ogden Family Wellness Day', '2025-05-10', 'Ogden', '84401', 150, 'General community',
  'mailing', 'Educational materials', 'Large event, need materials 2 weeks early.',
  'submitted',
  'Mailing — outside service area, high attendance',
  9, 'Mailing', 'Outside service area — flag for review', 0
),
(
  6, 'David Park', 'dpark@slcschools.org', '801-555-0206',
  'SLC School District', 0,
  'Nutrition Awareness Month', '2025-05-15', 'Salt Lake City', '84106', 300, 'Schools / youth',
  'inperson_support', 'Educational materials', 'Multi-school program across 3 campuses.',
  'in_review',
  'In-person support — very high attendance, multi-site',
  10, 'In-person support', 'Multi-site event — needs coordination', 1
),
(
  3, 'Linda Ortiz', 'linda.ortiz@southjordanhealth.org', '801-555-0203',
  'South Jordan Health Alliance', 0,
  'Summer Safety Fair', '2025-06-07', 'South Jordan', '84095', 90, 'General community',
  'presentation', 'Safety devices', NULL,
  'approved',
  'In-person presentation — moderate attendance, service area confirmed',
  5, 'In-person presentation', NULL, 1
),
(
  7, 'Tom Baker', 'tbaker@herrimanrec.org', '801-555-0207',
  'Herriman Recreation Center', 0,
  'Youth Sports Health Day', '2025-06-14', 'Herriman', '84096', 180, 'Schools / youth',
  'mailing', 'Behavioral reinforcement tools', 'Ship to rec center front desk.',
  'submitted',
  'Mailing — youth audience, moderate priority',
  6, 'Mailing', NULL, 1
);

-- ============================================================
-- SEED DATA — Status history (audit trail)
-- ============================================================
INSERT INTO status_history (request_id, status, changed_by, notes) VALUES
  (1, 'submitted',         'System',        'Request received via web form'),
  (1, 'in_review',         'Sarah Johnson', 'Reviewed and verified service area'),
  (1, 'approved',          'Sarah Johnson', 'Approved for in-person support'),
  (2, 'submitted',         'System',        'Request received via web form'),
  (2, 'in_review',         'Sarah Johnson', 'Confirmed bilingual materials available'),
  (2, 'approved',          'Sarah Johnson', 'Approved for mailing'),
  (2, 'sent_to_qualtrics', 'System',        'Exported to Qualtrics for fulfillment tracking'),
  (2, 'fulfilled',         'Sarah Johnson', 'Materials shipped March 20'),
  (3, 'submitted',         'System',        'Request received via web form'),
  (3, 'in_review',         'Sarah Johnson', 'Verifying quantities with warehouse'),
  (4, 'submitted',         'System',        'Request received via web form'),
  (5, 'submitted',         'System',        'Request received via web form'),
  (6, 'submitted',         'System',        'Request received via web form'),
  (6, 'in_review',         'Rachel Kim',    'Multi-site coordination required'),
  (7, 'submitted',         'System',        'Request received via web form'),
  (7, 'approved',          'Sarah Johnson', 'Approved for in-person presentation'),
  (8, 'submitted',         'System',        'Request received via web form');