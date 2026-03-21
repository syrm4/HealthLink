-- ============================================================
-- HEALTHLINK — Community Health Request Management System
-- MySQL Schema reference for MAMP (localhost)
-- NOTE: Run setup.php to create tables and seed data with
--       correctly hashed passwords (pass123 for all accounts)
-- ============================================================

CREATE DATABASE IF NOT EXISTS healthlink
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE healthlink;

-- ============================================================
-- TABLE 1: users
-- Roles: community | staff | admin | leader
-- Passwords stored as bcrypt hashes via password_hash()
-- Run setup.php to seed with pass123 for all accounts
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(200) NOT NULL,
  email         VARCHAR(200) NOT NULL UNIQUE,
  phone         VARCHAR(50),
  organization  VARCHAR(200),
  role          ENUM('community','staff','admin','leader') NOT NULL DEFAULT 'community',
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE 2: requests
-- ============================================================
CREATE TABLE IF NOT EXISTS requests (
  id                         INT AUTO_INCREMENT PRIMARY KEY,
  created_at                 DATETIME     DEFAULT CURRENT_TIMESTAMP,
  updated_at                 DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  user_id                    INT,
  requestor_name             VARCHAR(200) NOT NULL,
  requestor_email            VARCHAR(200) NOT NULL,
  requestor_phone            VARCHAR(50),
  organization               VARCHAR(200) NOT NULL,
  is_internal                TINYINT(1)   NOT NULL DEFAULT 0,
  event_name                 VARCHAR(300) NOT NULL,
  event_date                 DATE         NOT NULL,
  city                       VARCHAR(100) NOT NULL,
  zip_code                   VARCHAR(10)  NOT NULL,
  estimated_attendees        INT,
  audience_type              VARCHAR(100),
  -- request_type values match original MS Form options:
  --   mailing          = Mailing of education materials or safety devices
  --   presentation     = In-Person or Virtual Presentation
  --   inperson_support = Community Health In-Person Support at event
  request_type               ENUM('mailing','presentation','inperson_support') NOT NULL,
  material_category          VARCHAR(200) NOT NULL,
  notes                      TEXT,
  status                     ENUM('submitted','in_review','approved','sent_to_qualtrics','fulfilled') DEFAULT 'submitted',
  admin_notes                TEXT,
  assigned_staff             VARCHAR(200),
  ai_classification          TEXT,
  ai_priority_score          INT DEFAULT 5,
  ai_routing_recommendation  VARCHAR(200),
  ai_flags                   TEXT,
  in_service_area            TINYINT(1) DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE 3: status_history
-- ============================================================
CREATE TABLE IF NOT EXISTS status_history (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  request_id  INT          NOT NULL,
  status      VARCHAR(50)  NOT NULL,
  changed_by  VARCHAR(200) NOT NULL,
  notes       TEXT,
  changed_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE 4: material_categories
-- ============================================================
CREATE TABLE IF NOT EXISTS material_categories (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(200) NOT NULL,
  description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE 5: service_area_zips
-- ============================================================
CREATE TABLE IF NOT EXISTS service_area_zips (
  id       INT AUTO_INCREMENT PRIMARY KEY,
  zip_code VARCHAR(10) NOT NULL UNIQUE,
  city     VARCHAR(100),
  region   VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- USER ACCOUNTS (21 total)
-- Password: pass123 for all — use setup.php to seed correctly
-- ============================================================
--
-- Community partners (Maria persona):
--   maria, rosa, linda, carmen, sofia,
--   david, tom, patricia, james.lee, angela
--
-- Internal staff (James persona):
--   james, emily, michael, jessica, kevin
--
-- Admins (Sarah persona):
--   sarah, rachel, mark
--
-- Leaders (Dr. Chen persona):
--   dr.chen, dr.patel, dr.nguyen
--
-- Run setup.php to insert all users with correct bcrypt hashes.
-- ============================================================
