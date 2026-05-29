CREATE DATABASE IF NOT EXISTS portfolio_db;
USE portfolio_db;

-- Table: users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    account_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    is_admin BOOLEAN DEFAULT FALSE,
    section_order TEXT,
    section_hidden TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE
);

-- Pre-insert default admin (password: admin123)
INSERT IGNORE INTO users (username, email, password_hash, account_status, is_admin)
VALUES ('admin', 'admin@example.com', '$2y$10$9stKS6TZqMD/ezDuklvvTuO9/rq9MvOgb9ZUMvtquXStCpGTBm3hW', 'approved', TRUE);

-- Table: about
CREATE TABLE IF NOT EXISTS about (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bio TEXT,
    title VARCHAR(100),
    profile_image VARCHAR(255),
    about_image VARCHAR(255),
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
);

-- Table: contact
CREATE TABLE IF NOT EXISTS contact (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    linkedin VARCHAR(255),
    github VARCHAR(255),
    contact_image VARCHAR(255),
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
);

-- Table: education
CREATE TABLE IF NOT EXISTS education (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    degree VARCHAR(150),
    institution VARCHAR(150),
    start_date DATE,
    end_date DATE,
    description TEXT,
    result VARCHAR(100),
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
);

-- Table: skills
CREATE TABLE IF NOT EXISTS skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_name VARCHAR(100),
    proficiency INT,
    skill_group VARCHAR(100),
    image_url VARCHAR(255),
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
);

-- Table: work_experience
CREATE TABLE IF NOT EXISTS work_experience (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    job_title VARCHAR(150),
    company VARCHAR(150),
    start_date DATE,
    end_date DATE,
    description TEXT,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
);

-- Table: achievements
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150),
    date_earned DATE,
    description TEXT,
    certificate_url VARCHAR(255),
    certificate_image_url VARCHAR(255),
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
);
-- Table: projects
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200),
    description TEXT,
    image_url VARCHAR(500),
    video_url VARCHAR(500),
    git_url VARCHAR(500),
    live_demo_url VARCHAR(500),
    tags VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
);
-- Table: blogs
CREATE TABLE IF NOT EXISTS blogs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200),
    content TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
);

-- Table: research
CREATE TABLE IF NOT EXISTS research (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    publication_date DATE,
    link VARCHAR(255),
    tags VARCHAR(255),
    is_deleted TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
);

-- Table: publications
CREATE TABLE IF NOT EXISTS publications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    authors VARCHAR(255),
    journal_conference VARCHAR(255),
    publish_date DATE,
    link VARCHAR(255),
    abstract TEXT,
    is_deleted TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
);

-- Table: reviews
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    visitor_name VARCHAR(100),
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
);

-- Table: portfolio_views
CREATE TABLE IF NOT EXISTS portfolio_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    view_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
);

-- Table: logs
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(255),
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
);

-- Trigger: after insert on skills
DELIMITER //
CREATE TRIGGER after_skill_insert
AFTER INSERT ON skills
FOR EACH ROW
BEGIN
    INSERT INTO logs (user_id, action) VALUES (NEW.user_id, CONCAT('Added new skill: ', NEW.skill_name));
END;
//
DELIMITER ;

-- Trigger: after insert on blogs
DELIMITER //
CREATE TRIGGER after_blog_insert
AFTER INSERT ON blogs
FOR EACH ROW
BEGIN
    INSERT INTO logs (user_id, action) VALUES (NEW.user_id, CONCAT('Added new blog: ', NEW.title));
END;
//
DELIMITER ;

-- View: v_portfolio_summary
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
