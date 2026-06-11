# Portfolio Generator System - 10 Minute Demo Video Script

## Video Goal

This script is designed for an approximately 10 minute DBMS project demo video. It follows the required structure:

- Project Overview: 1 minute
- Database Design: 3 minutes
- SQL Query Demonstration: 3 minutes
- Core Functionalities Demonstration: 3 minutes

It also highlights the rubric areas: problem analysis, ERD, relational schema, normalization, SQL queries, constraints, integrity, security, and advanced DBMS features.

---

## 0:00-1:00 - Project Overview

### Screen To Show

Show the homepage, login page, or public portfolio page. Then briefly show the folder/project name.

### Script

Assalamu Alaikum / Hello everyone. My project title is **Portfolio Generator System**. This is a database-driven web application built using **PHP, MySQL, PDO, HTML, CSS, JavaScript, Dompdf, and XAMPP**.

The main objective of this project is to help users create and publish a professional online portfolio without manually coding a website. A user can register, wait for admin approval, log in to the dashboard, enter personal and professional information, and the system automatically generates a public portfolio page.

The problem this project solves is that students, developers, researchers, freelancers, and job seekers often need a clean digital portfolio, but updating a static portfolio manually is time-consuming. Information such as skills, education, work experience, projects, publications, research, blogs, achievements, reviews, and contact details can change frequently.

This system solves that problem using a normalized relational database. The application separates content from presentation. Users manage data through forms, and the public portfolio dynamically retrieves that data from MySQL.

The system also includes admin approval, review moderation, analytics, QR/shareable link support, and PDF export. From a DBMS perspective, it demonstrates entity modeling, primary keys, foreign keys, cascading rules, indexing, joins, aggregation, grouping, subqueries, and optional triggers and views.

---

## 1:00-4:00 - Database Design

### 1:00-1:50 - ER Diagram

### Screen To Show

Show the ER diagram from the report or explain it using the database tables in phpMyAdmin.

### Script

Now I will explain the database design. The central entity of the system is the **users** table. Almost every other table depends on users through the `user_id` foreign key.

The `users` table stores authentication and account information such as `id`, `username`, `email`, `password_hash`, `account_status`, `is_admin`, `section_order`, `section_hidden`, `created_at`, and `is_deleted`.

The relationship model is mainly one-to-many. One user can have many education records, many skills, many work experiences, many achievements, many projects, many blogs, many research records, many publications, many reviews, many portfolio views, and many logs.

The main entities are:

- `users` for account and role information.
- `about` for biography, title, and profile images.
- `contact` for phone, address, LinkedIn, GitHub, and contact image.
- `education` for academic history.
- `skills` for skill names, proficiency, group, and image.
- `work_experience` for job history.
- `achievements` for certificates and honors.
- `projects` for portfolio projects, images, videos, GitHub links, live demo links, and tags.
- `blogs`, `research`, and `publications` for written and academic work.
- `reviews` for visitor feedback.
- `portfolio_views` for analytics.
- `logs` for audit activities.

The ERD is complete because it identifies entities, attributes, relationships, and major constraints. The parent-child relationship is clear: `users.id` is referenced by child tables using `user_id`.

### 1:50-2:35 - Relational Schema

### Screen To Show

Open `sql/portfolio_db.sql` or phpMyAdmin table structure.

### Script

Now I will show the relational schema. Every table has a primary key, usually an auto-increment `id`. The child tables include a `user_id` column as a foreign key.

For example, the `skills` table has:

```sql
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
skill_name VARCHAR(100),
proficiency INT,
skill_group VARCHAR(100),
image_url VARCHAR(255),
is_deleted BOOLEAN DEFAULT FALSE,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
INDEX (user_id)
```

This means every skill belongs to one valid user. If that user is deleted, the related skills are also deleted automatically because of `ON DELETE CASCADE`.

The `reviews` table has a check constraint:

```sql
rating INT CHECK (rating >= 1 AND rating <= 5)
```

This protects the database from invalid ratings outside the range 1 to 5.

The `users` table also uses `UNIQUE` constraints on `username` and `email`, which prevents duplicate accounts. The `account_status` field uses an enum such as `pending`, `approved`, and `rejected`, so account workflow is controlled at the database level.

Indexes are added on `user_id` in child tables. This improves performance when the system loads a user's portfolio data.

### 2:35-4:00 - Normalization

### Screen To Show

Show tables one by one and explain why data is separated.

### Script

The database follows normalization principles.

First, in **1NF**, each table stores atomic values. For example, skills are not stored as one comma-separated text field inside the users table. Instead, every skill is stored as a separate row in the `skills` table. Similarly, projects, education, reviews, blogs, and achievements are stored in their own rows.

Second, in **2NF**, non-key attributes depend on the whole primary key. Since each table uses a single primary key such as `id`, attributes describe that record only. In the `education` table, fields like `degree`, `institution`, `start_date`, `end_date`, `description`, and `result` describe one education record, not unrelated user data.

Third, in **3NF**, the design avoids transitive dependency. User account information is stored in `users`, profile biography is stored in `about`, contact information is stored in `contact`, and project-specific information is stored in `projects`. This avoids repeating username, email, phone, and profile data inside every table.

The schema is also close to **BCNF** for the main tables because determinants are candidate keys or foreign keys. For example, `users.id` determines username, email, and account status. Each child table's `id` determines its own attributes, while `user_id` only represents the relationship to the owner.

This normalization reduces redundancy, improves consistency, and makes updates easier. If a user changes a phone number, only the `contact` table needs to be updated. If a user adds a new skill, only a new row is inserted into `skills`.

---

## 4:00-7:00 - SQL Query Demonstration

### 4:00-4:45 - Authentication And Admin Queries

### Screen To Show

Show phpMyAdmin SQL tab or the relevant query in code.

### Script

Now I will demonstrate important SQL queries used in the project.

First, during registration, the system checks whether the username or email already exists:

```sql
SELECT id FROM users WHERE username = ? OR email = ?;
```

If the account is unique, it inserts the user with pending status:

```sql
INSERT INTO users (username, email, password_hash, account_status)
VALUES (?, ?, ?, 'pending');
```

The password is stored as a hash, not plain text. This improves security.

For admin approval, the admin can update a user's account status:

```sql
UPDATE users
SET account_status = 'approved', is_deleted = 0
WHERE id = ?;
```

This query is important because a public portfolio is visible only after admin approval.

### 4:45-5:35 - Join Query For Public Portfolio

### Screen To Show

Run or show the public portfolio fetch query.

### Script

The public portfolio page uses joins to fetch data from multiple related tables. For example:

```sql
SELECT u.id as user_id, u.username, u.email,
       a.bio, a.title, a.profile_image, a.about_image,
       c.phone, c.address, c.linkedin, c.github, c.contact_image
FROM users u
LEFT JOIN about a ON u.id = a.user_id AND a.is_deleted = 0
LEFT JOIN contact c ON u.id = c.user_id AND c.is_deleted = 0
WHERE u.username = ?
  AND u.account_status = 'approved'
  AND u.is_deleted = 0;
```

This query joins `users`, `about`, and `contact`. It also filters only approved and active users. The use of `LEFT JOIN` is useful because the portfolio can still load even if some optional profile data is missing.

The system also loads child records using joins, such as:

```sql
SELECT s.* FROM skills s
JOIN users u ON s.user_id = u.id
WHERE u.id = ? AND s.is_deleted = 0
ORDER BY s.proficiency DESC;
```

This query retrieves the active skills of a user and sorts them by proficiency.

### 5:35-6:20 - Aggregation, Grouping, And Subqueries

### Screen To Show

Show dashboard or analytics query.

### Script

The system also uses aggregation queries for dashboard statistics.

For example, total portfolio views:

```sql
SELECT COUNT(*) FROM portfolio_views WHERE user_id = ?;
```

Average review rating:

```sql
SELECT AVG(rating) FROM reviews WHERE user_id = ?;
```

Pending reviews:

```sql
SELECT COUNT(*) FROM reviews
WHERE user_id = ? AND status = 'pending';
```

For analytics, the system uses grouping to find the most common skill:

```sql
SELECT skill_name, COUNT(*) as cnt
FROM skills
WHERE is_deleted = 0
GROUP BY skill_name
ORDER BY cnt DESC
LIMIT 1;
```

This demonstrates `GROUP BY`, `COUNT`, sorting, and limiting.

The system also uses subqueries to calculate a credibility score:

```sql
SELECT u.username,
       (
         (SELECT IFNULL(AVG(rating),0) FROM reviews WHERE user_id = u.id) * 0.4 +
         (SELECT COUNT(*) FROM skills WHERE user_id = u.id AND is_deleted=0) * 0.2 +
         (SELECT COUNT(*) FROM work_experience WHERE user_id = u.id AND is_deleted=0) * 0.2 +
         (SELECT COUNT(*) FROM achievements WHERE user_id = u.id AND is_deleted=0) * 0.1 +
         (SELECT COUNT(*) FROM blogs WHERE user_id = u.id AND is_deleted=0) * 0.1
       ) AS credibility_score
FROM users u
WHERE u.account_status = 'approved' AND u.is_deleted = 0
ORDER BY credibility_score DESC;
```

This query combines ratings and portfolio activity into a ranked score.

### 6:20-7:00 - Advanced DBMS Features

### Screen To Show

Show the trigger and view section in `sql/portfolio_db.sql`.

### Script

The project also includes advanced DBMS features.

First, the schema uses indexes on `user_id` in child tables. This improves query performance when fetching portfolio sections.

Second, the SQL file includes trigger examples for audit logging. For example, after a skill is inserted, a trigger can automatically insert an activity log:

```sql
CREATE TRIGGER after_skill_insert
AFTER INSERT ON skills
FOR EACH ROW
BEGIN
    INSERT INTO logs (user_id, action)
    VALUES (NEW.user_id, CONCAT('Added new skill: ', NEW.skill_name));
END;
```

There is also a trigger example for blog creation.

Third, the project includes a view called `v_portfolio_summary`. This view summarizes user profile data with counts for skills and work experience:

```sql
CREATE OR REPLACE VIEW v_portfolio_summary AS
SELECT
    u.id AS user_id,
    u.username,
    u.email,
    a.title AS professional_title,
    a.profile_image,
    c.phone,
    (SELECT COUNT(*) FROM skills WHERE user_id = u.id AND is_deleted = 0) AS skill_count,
    (SELECT COUNT(*) FROM work_experience WHERE user_id = u.id AND is_deleted = 0) AS work_count
FROM users u
LEFT JOIN about a ON u.id = a.user_id AND a.is_deleted = 0
LEFT JOIN contact c ON u.id = c.user_id AND c.is_deleted = 0
WHERE u.is_deleted = 0 AND u.account_status = 'approved';
```

This view can help the admin dashboard or summary page load user portfolio metrics more easily.

---

## 7:00-10:00 - Core Functionalities Demonstration

### 7:00-7:40 - Registration, Login, And Approval Workflow

### Screen To Show

Show register page, login page, and admin approval page.

### Script

Now I will demonstrate the main workflow of the system.

First, a new user registers with username, email, and password. The system validates duplicate username and email, hashes the password, and stores the account with `pending` status.

After registration, the user cannot publish a public portfolio immediately. The admin logs in and opens the user approval section. From here, the admin can approve, reject, or pause users.

Once the account status becomes approved, the user can log in and the public portfolio can be accessed through the username-based link.

This workflow demonstrates security and data integrity because only approved users can publish content.

### 7:40-8:45 - Dashboard CRUD Operations

### Screen To Show

Show dashboard pages: about, contact, education, skills, work, projects, achievements, blogs, research, publications.

### Script

After login, the user enters the dashboard. The dashboard provides CRUD operations for the main portfolio sections.

The user can update the About section with biography, title, profile image, and about image.

The Contact section stores phone, address, LinkedIn, GitHub, and contact image.

The Education page allows the user to add degrees, institutions, dates, descriptions, and results.

The Skills page stores skill name, proficiency, group, and image. This data is stored in the `skills` table and connected to the current user through `user_id`.

The Work Experience page stores job title, company, dates, and description.

The Projects page stores project title, description, image, video URL, GitHub URL, live demo URL, and tags.

The system also supports achievements, blogs, research, and publications. These sections make the generated portfolio suitable for students, developers, researchers, and professionals.

### 8:45-9:25 - Public Portfolio, Reviews, And Analytics

### Screen To Show

Open `public/portfolio.php?user=username`, submit a review, and show review/analytics pages.

### Script

Now I will show the public portfolio page. The portfolio page receives the username from the URL, checks whether the account is approved, then retrieves all related records from the database.

The public page dynamically displays about information, skills, education, work experience, projects, achievements, publications, research, blogs, and contact details.

Visitors can also submit reviews. Reviews are stored in the `reviews` table with a status such as pending, approved, or rejected. This allows moderation before feedback is displayed.

The system also tracks portfolio views in the `portfolio_views` table. These records are used for analytics, such as total views and activity insights.

### 9:25-10:00 - Section Control, QR/Share Link, PDF Export, And Closing

### Screen To Show

Show order sections page, shareable link/QR page, and PDF export.

### Script

The system also provides customization features. In the section ordering page, the user can control the order and visibility of portfolio sections. This is stored in `section_order` and `section_hidden` in the `users` table.

The shareable link and QR code feature helps users share their public portfolio easily.

The PDF export feature generates a resume-style PDF from the stored portfolio data. This shows how the same normalized database can support multiple outputs: web portfolio, dashboard statistics, analytics, and PDF resume.

To conclude, the Portfolio Generator System is a complete DBMS-based web application. It has a clear problem statement, structured requirements, a complete ERD, normalized relational schema, strong primary key and foreign key design, constraints, cascading rules, indexes, join queries, aggregation, grouping, subqueries, and optional advanced features like triggers and views.

Thank you.

---

## Rubric Coverage Checklist

### 1. Problem Analysis & Requirement Modeling - 10 Marks

- Clearly explain the problem: manual portfolio creation is time-consuming and difficult to update.
- Mention target users: students, developers, researchers, freelancers, job seekers, and institutions.
- Mention requirements: registration, admin approval, dashboard CRUD, public portfolio, reviews, analytics, share link, QR code, and PDF export.

### 2. ER Diagram & Conceptual Design - 15 Marks

- Show `users` as the central parent entity.
- Explain one-to-many relationships from `users` to skills, projects, education, reviews, views, logs, etc.
- Mention key attributes and relationship constraints.

### 3. Relational Schema & Normalization - 15 Marks

- Show primary keys and foreign keys.
- Explain `ON DELETE CASCADE`.
- Explain 1NF, 2NF, 3NF, and BCNF applicability.
- Emphasize reduced redundancy by separating portfolio sections into individual tables.

### 4. SQL Query Implementation - 20 Marks

- Demonstrate authentication queries.
- Demonstrate join query for public portfolio.
- Demonstrate aggregation with `COUNT` and `AVG`.
- Demonstrate grouping with `GROUP BY`.
- Demonstrate subqueries using credibility score.
- Demonstrate admin update queries.

### 5. Constraints, Integrity & Security - 10 Marks

- Mention `PRIMARY KEY`, `FOREIGN KEY`, `UNIQUE`, `CHECK`, `NOT NULL`, enum status, and cascading rules.
- Mention password hashing and PDO prepared statements.
- Mention soft deletion through `is_deleted`.

### 6. Advanced DBMS Features - 10 Marks

- Mention indexes on `user_id`.
- Show optional triggers for audit logs.
- Show optional view `v_portfolio_summary`.
- Explain how analytics queries use aggregation and subqueries.

---

## Quick Recording Tips

- Keep the database design section focused because it carries many rubric marks.
- When showing SQL, do not read every line; explain what each query proves.
- Mention constraints loudly and clearly: PK, FK, UNIQUE, CHECK, NOT NULL, CASCADE, indexes.
- If triggers and views are commented in the SQL file, say: "The project includes optional trigger and view definitions, and they can be enabled in MySQL for the advanced DBMS part."
- End by connecting features back to DBMS concepts.
