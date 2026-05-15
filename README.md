# 🎯 Auto Portfolio Generator System

## 📌 Project Overview
The **Auto Portfolio Generator** is a dynamic, fully responsive platform built with PHP and MySQL. It allows users to register, fill in their professional details, and instantly generate a premium, fully customized portfolio website. The system features a modern glassmorphism design, comprehensive user dashboard, advanced analytics, PDF resume generation, and a dedicated admin panel for user management.

---

## 🚀 Key Features
- **User Authentication**: Secure Login and Registration system.
- **Dynamic Dashboard**: Interactive UI using PRG pattern to manage About, Education, Work Experience, Skills, Achievements, and Blogs.
- **Auto-Generated Portfolio**: A beautifully designed, unique portfolio page for each user that updates instantly.
- **PDF Export**: Users can download their portfolio details as a professionally formatted PDF resume using FPDF.
- **QR Code Generation**: Instantly generate a shareable QR code for the public portfolio link.
- **Visitor Analytics**: Built-in analytics dashboard to track portfolio profile views.
- **Reviews & Ratings**: Visitors can leave reviews and rate user portfolios.
- **Admin Panel**: Dedicated admin interface to monitor the system and approve or manage registered users.

---

## 🔗 Project Links

*(Assuming the project is running on XAMPP at `http://localhost/Portfolio_generator/`)*

### 👤 User Site & Authentication
- **Landing Page**: [http://localhost/Portfolio_generator/index.php](http://localhost/Portfolio_generator/index.php)
- **User Registration**: [http://localhost/Portfolio_generator/auth/register.php](http://localhost/Portfolio_generator/auth/register.php)
- **User Login**: [http://localhost/Portfolio_generator/auth/login.php](http://localhost/Portfolio_generator/auth/login.php)

### 📊 User Dashboard *(Requires Login)*
- **Dashboard Home**: [http://localhost/Portfolio_generator/dashboard/index.php](http://localhost/Portfolio_generator/dashboard/index.php)
- **Manage About**: [http://localhost/Portfolio_generator/dashboard/about.php](http://localhost/Portfolio_generator/dashboard/about.php)
- **Manage Education**: [http://localhost/Portfolio_generator/dashboard/education.php](http://localhost/Portfolio_generator/dashboard/education.php)
- **Manage Work Experience**: [http://localhost/Portfolio_generator/dashboard/work.php](http://localhost/Portfolio_generator/dashboard/work.php)
- **Manage Skills**: [http://localhost/Portfolio_generator/dashboard/skills.php](http://localhost/Portfolio_generator/dashboard/skills.php)
- **Manage Achievements**: [http://localhost/Portfolio_generator/dashboard/achievements.php](http://localhost/Portfolio_generator/dashboard/achievements.php)
- **Manage Blogs**: [http://localhost/Portfolio_generator/dashboard/blogs.php](http://localhost/Portfolio_generator/dashboard/blogs.php)
- **Manage Contact Info**: [http://localhost/Portfolio_generator/dashboard/contact.php](http://localhost/Portfolio_generator/dashboard/contact.php)
- **Share Portfolio**: [http://localhost/Portfolio_generator/dashboard/shareable_link.php](http://localhost/Portfolio_generator/dashboard/shareable_link.php)

### 🌍 Public Portfolio & Tools
- **Public Portfolio View**: `http://localhost/Portfolio_generator/public/portfolio.php?user=YOUR_USERNAME`
- **Submit Review/Rating**: [http://localhost/Portfolio_generator/public/submit_review.php](http://localhost/Portfolio_generator/public/submit_review.php)
- **User Analytics**: [http://localhost/Portfolio_generator/analytics/analytics.php](http://localhost/Portfolio_generator/analytics/analytics.php)
- **Export to PDF**: [http://localhost/Portfolio_generator/export/export_pdf.php](http://localhost/Portfolio_generator/export/export_pdf.php)

### 👑 Admin Site
- **Admin Dashboard**: [http://localhost/Portfolio_generator/admin/dashboard.php](http://localhost/Portfolio_generator/admin/dashboard.php)
- **Approve & Manage Users**: [http://localhost/Portfolio_generator/admin/approve_users.php](http://localhost/Portfolio_generator/admin/approve_users.php)

---

## 🛠 Tech Stack
- **Backend**: PHP 8.x
- **Database**: MySQL
- **Frontend**: HTML5, Vanilla CSS (Glassmorphism & Modern UI), JavaScript
- **Libraries**: FPDF (for PDF generation), PHP QR Code (for QR codes)

---

## ⚡ Setup & Installation

1. **Start XAMPP**: Open XAMPP Control Panel and start **Apache** and **MySQL**.
2. **Clone the Repository**: Place the `Portfolio_generator` folder inside `C:\xampp\htdocs\`.
3. **Database Setup**:
   - Open `http://localhost/phpmyadmin/`
   - Create a new database named `smart_portfolio` (or check `sql/` folder for schema).
   - Import the database SQL file provided with the project.
4. **Composer Dependencies**: 
   - Ensure you run `php composer.phar install` or `composer install` inside the project folder to install dependencies like FPDF.
5. **Launch**: Navigate to `http://localhost/Portfolio_generator/` in your browser.

---

## 📌 Author
**Maria Akter Mukti**