# HealthLink

**AI-Powered Community Health Request Management System**

Built for the 2025 GenAI Hackathon — David Eccles School of Business, University of Utah.

---

## Overview

HealthLink modernizes the Children's Health Community request workflow. Community partners and internal staff submit requests for health education materials, safety devices, and staffed event support through a structured web interface. An AI agent automatically classifies, validates, and routes each request — replacing manual email triage and spreadsheet tracking.

---

## Tech Stack

- **Frontend:** HTML, CSS
- **Backend:** PHP
- **Database:** MySQL
- **AI:** OpenAI GPT-4o

---

## User Roles

| Role | Count | Description |
|---|---|---|
| Community partner | 10 | External partners submitting requests |
| Internal staff | 5 | Intermountain Healthcare staff |
| Admin | 3 | Community Health operations staff |
| Leader | 3 | Program managers / leadership |

**Demo password for all users:** `HealthLink2025!`

---

## File Structure

```
HealthLink/
├── index.php              # Login page
├── register.php           # Optional registration
├── dashboard.php          # Role-based router
├── logout.php             # Session destroy
├── views/
│   ├── community.php      # Maria — request form + status tracking
│   ├── staff.php          # James — request form + history
│   ├── admin.php          # Sarah — queue + detail panel
│   └── leader.php         # Dr. Chen — reporting + analytics
├── api/
│   └── classify.php       # GPT-4o classification endpoint
├── includes/
│   ├── db.php             # MySQL connection
│   ├── auth.php           # Session + auth helpers
│   └── ai.php             # OpenAI API wrapper
├── assets/
│   └── style.css          # Shared stylesheet
└── schema.sql             # Full DB schema + seed data
```

---

## Setup

1. Import `schema.sql` into your MySQL database
2. Copy `includes/db.php` and update your DB credentials
3. Add your OpenAI API key to `includes/ai.php`
4. Deploy to any PHP host (XAMPP, WAMP, or live server)
5. Login with any username from the schema using password `HealthLink2025!`

---

## AI Features

- Auto-classification of request type and fulfillment pathway
- Geographic routing (zip code service area check)
- Priority scoring (1-10) based on attendance, urgency, and flags
- Natural language flag detection (multi-site events, bilingual needs, safety devices)
- Human-in-the-loop: AI recommends, admin approves
