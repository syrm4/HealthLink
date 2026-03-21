# HealthLink

Community Health Request Management System — Intermountain Healthcare  
Built for the 2025 GenAI Hackathon, David Eccles School of Business, University of Utah.

## Stack
- PHP 8.x
- MySQL 8.x
- HTML/CSS (vanilla)
- Anthropic Claude API (`claude-sonnet-4-6`)

## Quick Start

### 1. Create MySQL database
```sql
CREATE DATABASE healthlink CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Configure database connection
Edit `config/db.php` with your MySQL credentials, or set environment variables:
```
DB_HOST, DB_NAME, DB_USER, DB_PASS
```

### 3. Set Anthropic API key
```bash
export ANTHROPIC_API_KEY=your_key_here
```
Or edit `api/ai_classify.php` directly.

### 4. Run setup
Start your PHP server and visit `/setup.php` to create tables and seed 21 synthetic users.

### 5. Log in
Visit `/index.php`. Default password for all users: **HealthLink2025!**

## User Accounts

| Role | Count | Example Username |
|------|-------|------------------|
| community | 10 | maria.gonzalez |
| staff | 5 | james.thompson |
| admin | 3 | sarah.mitchell |
| leader | 3 | dr.chen |

## Running Locally
```bash
php -S localhost:8080
```
Then visit http://localhost:8080

## Features
- Role-based authentication
- Community partner request submission (public + optional login)
- Internal staff portal with request history
- Admin queue with AI-powered classification and routing
- Claude AI auto-classifies requests: priority score, routing recommendation, flags
- Rule-based fallback if API key not set
- Geographic service area routing (Salt Lake Valley zip codes)
- Status tracking: Submitted → In Review → Approved → Fulfilled
- Full audit trail (status_history table)
- Leader analytics dashboard
