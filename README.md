# 🚀 Portfolio Generator System

A **full-stack database-driven web application** that enables users to create, customize, and publish professional online portfolios without writing any code. Built with **PHP**, **MySQL**, **PDO**, modern **CSS**, and **JavaScript** on XAMPP.

> **Problem:** Students, developers, researchers, freelancers, and job seekers need clean digital portfolios, but manually coding and updating static portfolio sites is time-consuming and technically demanding.
>
> **Solution:** This system separates content from presentation via a normalized relational database. Users manage portfolio data through an intuitive dashboard, and a public-facing page dynamically renders that data into a beautiful glassmorphic portfolio.

---

## ✨ Key Features

### 👤 User Features
| Feature | Description |
|---------|-------------|
| **Registration & Login** | Secure sign-up with password hashing (bcrypt), role-based sessions |
| **Admin Approval Workflow** | New accounts are `pending` until an admin approves them |
| **CRUD Dashboard** | Full Create, Read, Update, Delete for all portfolio sections |
| **Section Ordering** | Drag-and-drop reordering & hide/show toggles for portfolio sections |
| **QR Code Generator** | Auto-generated QR code for sharing your portfolio URL |
| **Shareable Link** | One-click copy of your public portfolio URL |
| **PDF Resume Export** | Generate a professional PDF resume from your portfolio data using Dompdf |
| **Review Moderation** | Review & approve/reject visitor feedback before it appears |

### 🎨 Public Portfolio
- **Glassmorphic UI** — Modern, responsive design with blur effects, gradients, and animations
- **Dynamic Content** — All sections (About, Skills, Education, Work, Projects, Blog, Research, Publications, etc.) load from the database
- **Visitor Reviews** — Visitors can leave ratings (1–5) and comments
- **View Tracking** — Every visit is logged for analytics

### 🔐 Admin Panel
- User management: approve, reject, or pause accounts
- Global review moderation across all users
- Platform analytics dashboard

### 📊 Analytics
- Most popular skills across the platform (GROUP BY + COUNT)
- Global content counts (skills, work, achievements, blogs, reviews)
- User credibility scoring via weighted subqueries
- User grouping by location

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | PHP 8.x with PDO (prepared statements) |
| **Database** | MySQL 8.x via phpMyAdmin / XAMPP |
| **Frontend** | HTML5, CSS3 (glassmorphism, custom variables), Vanilla JS |
| **Icons** | Font Awesome 6 |
| **PDF Generation** | Dompdf (Composer) |
| **QR Code** | Google Charts API + chillerlan/php-qrcode |
| **Image Hosting** | ImgBB API (cloud image uploads) |
| **Server** | Apache (XAMPP local / InfinityFree production) |

---

## 🗄️ Database Architecture

### Entity Relationship Overview

```
users (central parent)
 ├── about              (1:1 — biography, title, images)
 ├── contact            (1:1 — phone, address, social links)
 ├── education          (1:N — degrees, institutions)
 ├── skills             (1:N — skill names, proficiency %)
 ├── work_experience    (1:N — job history)
 ├── achievements       (1:N — certificates, honors)
 ├── projects           (1:N — portfolio projects)
 ├── blogs              (1:N — blog articles)
 ├── research           (1:N — academic research)
 ├── publications       (1:N — published papers)
 ├── reviews            (1:N — visitor ratings & comments)
 ├── portfolio_views    (1:N — visit tracking)
 └── logs               (1:N — audit trail)
```

### Normalization

| Normal Form | How It's Applied |
|-------------|------------------|
| **1NF** | Each portfolio item (skill, education, project, etc.) is stored as a separate atomic row — no comma-separated lists |
| **2NF** | Every table has a single-column primary key (`id`); all non-key attributes depend on the full key |
| **3NF** | Account info, profile bio, contact details, and portfolio content are separated into distinct tables — no transitive dependencies |
| **BCNF** | All determinants are candidate keys or foreign keys |

### Key DBMS Concepts Demonstrated

| Concept | Implementation |
|---------|---------------|
| **Primary Keys** | `INT AUTO_INCREMENT` on every table |
| **Foreign Keys** | `user_id` references `users(id)` with `ON DELETE CASCADE` |
| **Unique Constraints** | `username` and `email` are UNIQUE in `users` |
| **Check Constraints** | `rating` in `reviews` constrained to 1–5 |
| **Enum Types** | `account_status` (`pending`, `approved`, `rejected`, `paused`); `reviews.status` |
| **Indexes** | `INDEX(user_id)` on all child tables for query performance |
| **Soft Deletion** | `is_deleted` flag instead of hard DELETE; data is preserved |
| **Joins** | `LEFT JOIN` in portfolio.php to load optional profile data |
| **Aggregation** | `COUNT()`, `AVG()`, `GROUP BY` for dashboard stats & analytics |
| **Subqueries** | Weighted credibility score calculation in analytics |
| **Triggers** (optional) | `after_skill_insert`, `after_blog_insert` — auto-log user actions |
| **Views** (optional) | `v_portfolio_summary` — pre-aggregated user metrics for admin |

---

## 📁 Project Structure

```
portfolio_generator/
├── index.php                    # Redirects to auth/login.php
├── .env                         # Database credentials (local/production)
├── .htaccess                    # Security: deny sensitive files, block config/vendor
├── composer.json                # PHP dependencies (dompdf, php-qrcode)
│
├── config/
│   ├── db.php                   # PDO MySQL connection (auto-detects local vs. live)
│   ├── imgbb.php                # ImgBB API key for cloud image uploads
│   └── database.php             # Secondary DB connection reference
│
├── auth/
│   ├── login.php                # User login with password_verify() + session
│   ├── register.php             # User registration with bcrypt hashing
│   └── logout.php               # Session destroy & redirect
│
├── dashboard/                   # User dashboard (session-protected)
│   ├── index.php                # Home: view count, avg rating, pending reviews
│   ├── about.php                # CRUD: biography, title, profile images
│   ├── contact.php              # CRUD: phone, address, LinkedIn, GitHub
│   ├── education.php            # CRUD: degrees, institutions, dates, results
│   ├── skills.php               # CRUD: skill name, proficiency (1-100), group
│   ├── work.php                 # CRUD: job title, company, dates, description
│   ├── achievements.php         # CRUD: certificates, honors, images
│   ├── projects.php             # CRUD: project details, URLs, tags
│   ├── blogs.php                # CRUD: blog title & content
│   ├── research.php             # CRUD: academic research entries
│   ├── publications.php         # CRUD: published papers & abstracts
│   ├── order_sections.php       # Drag-and-drop section ordering & visibility
│   ├── reviews.php              # Approve/reject visitor reviews
│   ├── shareable_link.php       # Copy portfolio URL, preview button
│   ├── qrcode.php               # QR code display & download
│   ├── upload_image.php         # Cloud image upload via ImgBB API
│   └── inc/
│       ├── head.php             # Dashboard <head> — styles, metadata
│       ├── sidebar.php          # Collapsible navigation sidebar
│       └── foot.php             # Closing scripts & footer
│
├── public/
│   ├── portfolio.php            # Public glassmorphic portfolio page (2,388 lines)
│   └── submit_review.php        # Endpoint for visitor review submission
│
├── admin/
│   ├── dashboard.php            # Admin home: links to user mgmt, analytics
│   ├── approve_users.php        # Approve / reject / pause user accounts
│   └── reviews.php              # Global review management (all users)
│
├── analytics/
│   └── analytics.php            # Platform analytics: top skills, counts, ranking, location
│
├── export/
│   └── export_pdf.php           # PDF resume generation via Dompdf
│
├── sql/
│   └── portfolio_db.sql         # Full database schema + triggers + view (commented)
│
├── assets/
│   ├── css/
│   │   └── style.css            # Global styles — glassmorphism, auth, dashboard, tables
│   └── image/
│       └── register.png         # Registration page illustration
│
├── vendor/                      # Composer-managed dependencies
│
├── PROJECT_DEMO_VIDEO_SCRIPT.md # 10-minute DBMS demo video script
└── PROJECT_REPORT.md            # Detailed project report
```

---

## ⚙️ Installation & Setup

### Prerequisites
- XAMPP (Apache + MySQL + PHP 8.x) or equivalent
- Composer (PHP dependency manager)
- Git (optional)

### Steps

1. **Clone or download** this repository into your XAMPP `htdocs` folder:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/mariaaktermukti/Portfolio_Generator.git
   ```

2. **Create the database:**
   - Open phpMyAdmin (`http://localhost/phpmyadmin`)
   - Import `sql/portfolio_db.sql` or run:
     ```sql
     CREATE DATABASE portfolio_db;
     -- then run the full schema from sql/portfolio_db.sql
     ```

3. **Install PHP dependencies:**
   ```bash
   cd Portfolio_generator
   composer install
   ```

4. **Configure database credentials** in `.env`:
   ```env
   DB_HOST=localhost
   DB_NAME=portfolio_db
   DB_USER=root
   DB_PASS=
   ```
   > For production (InfinityFree), update `config/db.php` with your live credentials.

5. **Configure image hosting** (optional):
   - Get a free API key from [ImgBB](https://imgbb.com/)
   - Update `config/imgbb.php` with your key

6. **Start the application:**
   - Open `http://localhost/Portfolio_generator` in your browser
   - You'll be redirected to the login page

### Default Admin Account
| Username | Password  | Email             |
|----------|-----------|-------------------|
| `admin`  | `admin123`| admin@example.com |

---

## 🚦 User Workflow

```
Visitor → Register → Account Pending
    ↓
Admin Approves Account
    ↓
User Logs In → Dashboard
    ↓                        ↓
Fill Portfolio Sections    Customize Order
    ↓                        ↓
Public Portfolio Live     Share Link / QR / PDF
```

---

## 🔐 Security Features

- **Password hashing** — `password_hash()` with bcrypt, verified via `password_verify()`
- **Prepared statements** — All SQL queries use PDO parameterized queries (prevents SQL injection)
- **Session protection** — All dashboard/admin pages check `$_SESSION['user_id']` before rendering
- **Admin isolation** — Admin-only pages check `$_SESSION['is_admin']`
- **Soft deletion** — `is_deleted` flags preserve data integrity instead of hard DELETE
- **Input sanitization** — `htmlspecialchars()` on all user output; validation on registration
- **Environment-based DB config** — Auto-detects local vs. production environment
- **.htaccess security** — Blocks direct access to `.env`, `composer.json`, `config/`, and `vendor/`

---

## 📸 API / Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `public/portfolio.php?user={username}` | GET | View a user's public portfolio |
| `public/submit_review.php` | POST | Submit a visitor review |
| `export/export_pdf.php?user={username}` | GET | Download PDF resume |
| `dashboard/upload_image.php` | POST | Upload image to ImgBB (authenticated) |

---

## 📦 PHP Dependencies (Composer)

```json
{
    "require": {
        "dompdf/dompdf": "^3.1",
        "chillerlan/php-qrcode": "*"
    }
}
```

---

## 🧪 Key SQL Queries (Live Examples)

**Join query — Load portfolio with profile + contact:**
```sql
SELECT u.id, u.username, a.bio, a.title, c.phone, c.linkedin, c.github
FROM users u
LEFT JOIN about a ON u.id = a.user_id AND a.is_deleted = 0
LEFT JOIN contact c ON u.id = c.user_id AND c.is_deleted = 0
WHERE u.username = ? AND u.account_status = 'approved' AND u.is_deleted = 0;
```

**Aggregation — Most popular skill across platform:**
```sql
SELECT skill_name, COUNT(*) as cnt
FROM skills WHERE is_deleted = 0
GROUP BY skill_name ORDER BY cnt DESC LIMIT 1;
```

**Subquery — User credibility score:**
```sql
SELECT u.username,
  ( (SELECT IFNULL(AVG(rating),0) FROM reviews WHERE user_id = u.id) * 0.4
  + (SELECT COUNT(*) FROM skills WHERE user_id = u.id AND is_deleted=0) * 0.2
  + (SELECT COUNT(*) FROM work_experience WHERE user_id = u.id AND is_deleted=0) * 0.2
  + (SELECT COUNT(*) FROM achievements WHERE user_id = u.id AND is_deleted=0) * 0.1
  + (SELECT COUNT(*) FROM blogs WHERE user_id = u.id AND is_deleted=0) * 0.1
  ) AS credibility_score
FROM users u
WHERE u.account_status = 'approved' AND u.is_deleted = 0
ORDER BY credibility_score DESC;
```

---

## 🧑‍💻 Author & License

- **Author:** Maria Akter Mukti
- **Repository:** [github.com/mariaaktermukti/Portfolio_Generator](https://github.com/mariaaktermukti/Portfolio_Generator)
- **Purpose:** DBMS academic project demonstrating relational database design, normalization, SQL queries, constraints, and a fully functional web application.

---

## 📋 Requirements Checklist

- [x] User registration & login with password hashing
- [x] Admin approval workflow (pending → approved/rejected/paused)
- [x] Full CRUD for all portfolio sections
- [x] Dynamic public portfolio with glassmorphic UI
- [x] Section ordering & visibility control
- [x] QR code generation & shareable link
- [x] PDF resume export
- [x] Visitor reviews with moderation
- [x] Portfolio view tracking & analytics
- [x] Platform-wide admin analytics dashboard
- [x] Cloud image upload integration
- [x] Soft deletion for data preservation
- [x] PDO prepared statements (SQL injection prevention)
- [x] Normalized relational schema (1NF, 2NF, 3NF, BCNF)
- [x] Foreign keys with CASCADE delete
- [x] Indexed columns for performance
- [x] Triggers and views (optional, documented in schema)