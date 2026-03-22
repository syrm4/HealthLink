# HealthLink

> AI-Powered Community Health Request Management System

![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)
![PHP](https://img.shields.io/badge/PHP-8%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)
![AI](https://img.shields.io/badge/AI-Claude%20Sonnet-CC785C?logo=anthropic&logoColor=white)
![Hackathon](https://img.shields.io/badge/2026%20GenAI-Hackathon-CC0000)

Built for the **[2026 Generative AI Hackathon — Eccles Business Case Competition](https://eccles.utah.edu/programs/undergraduate/generative-ai-hackathon/)** — David Eccles School of Business, University of Utah.

HealthLink replaces a manual email-and-spreadsheet workflow with a structured, intelligent web application. Community partners submit requests for health education materials, safety devices, and staffed event support. An AI agent automatically classifies, validates, and routes each request to the correct fulfillment pathway — with a human-in-the-loop approval process at every step.

---

## Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Getting Started](#getting-started)
- [Demo Accounts](#demo-accounts)
- [User Roles](#user-roles)
- [AI Features](#ai-features)
- [Team](#team)
- [License](#license)

---

## Features

- **Role-based access** — four distinct user experiences (community partner, staff, admin, leader)
- **AI classification** — Claude auto-tags every request with type, priority score, routing recommendation, and flags
- **Geographic routing** — zip code checked against Salt Lake Valley service area; out-of-area requests auto-routed to mailing
- **Priority queue** — requests sorted by AI-generated urgency score (1–10)
- **Admin dashboard** — real-time queue with filter, search, status updates, and full audit trail
- **Executive dashboard** — Chart.js visualizations (demographics, trends, staffing), AI summary, and leadership approvals
- **Qualtrics handoff** — approved requests exported downstream for recordkeeping and fulfillment tracking
- **Bilingual support** — Spanish/English language preference captured per user
- **Guest submission** — community partners can submit without an account and optionally create a profile

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3 (custom Intermountain Health brand stylesheet) |
| Backend | PHP 8+ |
| Database | MySQL 8 (via PDO with prepared statements) |
| AI | Anthropic Claude (`claude-sonnet-4-6`, with rule-based fallback) |
| Charts | Chart.js 4.4 |
| Local dev | MAMP (macOS) |

---

## Project Structure

```
HealthLink/
├── index.php                   # Login page with role-based redirect
├── dashboard.php               # Role router — sends users to correct page
├── logout.php                  # Session destroy + redirect
├── setup.php                   # One-time DB initializer + seed data
├── schema.sql                  # Reference SQL schema (see setup.php for seeding)
│
├── config/
│   └── db.php                  # PDO connection (reads env vars or defaults)
│
├── database/
│   └── healthlink_mysql.sql    # Annotated MySQL schema reference
│
├── includes/
│   ├── auth.php                # Session helpers: is_logged_in, require_role, login_user, CSRF
│   ├── helpers.php             # Shared UI helpers: priorityClass()
│   ├── header.php              # Shared navbar (role-aware nav links)
│   └── footer.php              # Shared footer
│
├── pages/
│   ├── community_portal.php    # Maria — request submission, status tracking, guest path
│   ├── staff_portal.php        # James — request queue, AI summary panel, send to admin
│   ├── admin_dashboard.php     # Sarah — metric cards, queue, status updates, audit trail
│   └── leader_dashboard.php   # Dr. Chen — charts, AI executive summary, approvals
│
├── api/
│   ├── ai_classify.php         # Claude classification endpoint (with rule-based fallback)
│   ├── submit_request.php      # POST endpoint for new request submission
│   └── update_status.php       # POST endpoint for status changes (admin/leader only)
│
└── assets/
    ├── style.css               # Full brand stylesheet (Intermountain Health guidelines)
    └── images/
        └── ihcHEALTHLINK.png  # Logo
```

---

## Getting Started

### Prerequisites

- [MAMP](https://www.mamp.info/) (macOS — tested with MAMP 6+)
- PHP 8.0 or higher
- MySQL 8.0 or higher
- An [Anthropic API key](https://console.anthropic.com/) (optional — app falls back to rule-based classification without one)

### Installation

1. **Clone the repository into a subfolder of your MAMP webroot**

   ```bash
   git clone https://github.com/syrm4/HealthLink.git /Applications/MAMP/htdocs/HealthLink
   ```

   > **Note:** MAMP's default Apache port is **8888** on macOS and MySQL runs on port **8889**. Both are already set as defaults in `config/db.php`.

2. **Start MAMP** — ensure Apache and MySQL are running (both indicators green).

3. **Run the setup script** in your browser:

   ```
   http://localhost:8888/HealthLink/setup.php
   ```

   This creates all database tables, seeds 21 demo users, and inserts 8 sample requests. You will see a confirmation table of all users when setup is complete.

4. **(Optional) Set your Anthropic API key**

   Set the environment variable `ANTHROPIC_API_KEY` in your MAMP environment, or edit `config/db.php` to hard-code it for local development only. Without a key, the app uses rule-based classification as a fallback.

5. **Go to the login page:**

   ```
   http://localhost:8888/HealthLink/index.php
   ```

---

## Demo Accounts

**Password for all accounts: `pass123`**

Use the quick-fill buttons on the login page, or sign in manually with any username below.

| Username | Role | Persona |
|---|---|---|
| `maria` | Community partner | Maria Gonzalez — Westside Community Clinic |
| `rosa` | Community partner | Rosa Mendez — Ogden Community Center |
| `linda` | Community partner | Linda Ortiz — South Jordan Health Alliance |
| `carmen` | Community partner | Carmen Reyes — Salt Lake Parent Resource Center |
| `sofia` | Community partner | Sofia Ramirez — Kearns Recreation Center |
| `david` | Community partner | David Park — SLC School District |
| `tom` | Community partner | Tom Baker — Herriman Recreation Center |
| `patricia` | Community partner | Patricia White — Murray Family Clinic |
| `james.lee` | Community partner | James Lee — West Valley Community Center |
| `angela` | Community partner | Angela Moore — Holladay Wellness Coalition |
| `james` | Staff | James Thompson — Intermountain Healthcare |
| `emily` | Staff | Emily Harris — Intermountain Healthcare |
| `michael` | Staff | Michael Clark — Intermountain Healthcare |
| `jessica` | Staff | Jessica Wang — Intermountain Healthcare |
| `kevin` | Staff | Kevin Turner — Intermountain Healthcare |
| `sarah` | Admin | Sarah Johnson — Children's Health Community |
| `rachel` | Admin | Rachel Kim — Children's Health Community |
| `mark` | Admin | Mark Davis — Children's Health Community |
| `dr.chen` | Leader | Dr. Linda Chen — Children's Health Leadership |
| `dr.patel` | Leader | Dr. Raj Patel — Children's Health Leadership |
| `dr.nguyen` | Leader | Dr. Anh Nguyen — Children's Health Leadership |

---

## User Roles

| Role | Count | What they see |
|---|---|---|
| **Community partner** | 10 | Submit requests, track status, guest path available |
| **Staff** | 5 | Full request queue, AI summary panel, send to admin |
| **Admin** | 3 | Metric cards, filterable queue, status updates, audit trail |
| **Leader** | 3 | Executive charts, AI summary, leadership approvals |

---

## AI Features

| Feature | Description |
|---|---|
| **Auto-classification** | Claude reads the request and generates a one-sentence classification |
| **Priority scoring** | Score of 1–10 based on attendance size, request type, service area, and flags |
| **Routing recommendation** | Mailing / In-person support / Presentation |
| **Flag detection** | Multi-site events, out-of-area zip codes, bilingual material needs, safety devices |
| **Service area check** | Zip code matched against 24 Salt Lake Valley service area zips |
| **Rule-based fallback** | If no API key is set, a deterministic scoring algorithm runs instead |
| **Human-in-the-loop** | AI recommends — admin reviews and approves before any action is taken |

---

## Request Types

Matching the original Intermountain Health Microsoft Form:

| Value | Label |
|---|---|
| `mailing` | Mailing of education materials or safety devices |
| `presentation` | In-person or virtual presentation |
| `inperson_support` | Community Health in-person support at event with education materials or safety devices |

---

## Team

Built by **Team HealthLink** in under 6 hours at the **[2026 Generative AI Hackathon — Eccles Business Case Competition](https://eccles.utah.edu/programs/undergraduate/generative-ai-hackathon/)**, David Eccles School of Business, University of Utah.

| Name | Role |
|---|---|
| [Athavan Elangko](https://github.com/atgko) | Developer |
| [Bethany Chung](https://github.com/bethanalyst) | Developer |
| RJ Spratling | Developer |
| [Joe Milner](https://github.com/syrm4) | Developer |

---

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.

---

*Built with PHP, MySQL, Anthropic Claude, and Chart.js. Designed for Intermountain Health — Community Health division.*
