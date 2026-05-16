<?php
require_once '../config/db.php';

// Add contact_image column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE contact ADD COLUMN contact_image VARCHAR(255) DEFAULT ''");
} catch (PDOException $e) {
    // Column already exists
}

// Add status column to reviews if it doesn't exist
try {
    $pdo->exec("ALTER TABLE reviews ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
} catch (PDOException $e) {
    // Column already exists
}

$username = $_GET['user'] ?? '';
$review_submitted = isset($_GET['review_submitted']) && $_GET['review_submitted'] === '1';

if (!$username) {
    die("User not specified.");
}

// Fetch user, about, contact via JOIN
$stmt = $pdo->prepare("
    SELECT u.id as user_id, u.username, u.email, 
           a.bio, a.title, a.profile_image, a.about_image, 
           c.phone, c.address, c.linkedin, c.github, c.contact_image
    FROM users u
    LEFT JOIN about a ON u.id = a.user_id AND a.is_deleted = 0
    LEFT JOIN contact c ON u.id = c.user_id AND c.is_deleted = 0
    WHERE u.username = ? AND u.account_status = 'approved' AND u.is_deleted = 0
");
$stmt->execute([$username]);
$profile = $stmt->fetch();

if (!$profile) {
    die("Portfolio not found or not approved.");
}

$user_id = $profile['user_id'];

// Track view
$ip_address = $_SERVER['REMOTE_ADDR'];
$stmt_view = $pdo->prepare("INSERT INTO portfolio_views (user_id, ip_address) VALUES (?, ?)");
$stmt_view->execute([$user_id, $ip_address]);

// Fetch other data
$stmt_edu = $pdo->prepare("SELECT e.* FROM education e JOIN users u ON e.user_id = u.id WHERE u.id = ? AND e.is_deleted = 0 ORDER BY e.start_date DESC");
$stmt_edu->execute([$user_id]);
$education = $stmt_edu->fetchAll();

$stmt_skills = $pdo->prepare("SELECT s.* FROM skills s JOIN users u ON s.user_id = u.id WHERE u.id = ? AND s.is_deleted = 0 ORDER BY s.proficiency DESC");
$stmt_skills->execute([$user_id]);
$skills = $stmt_skills->fetchAll();

$stmt_work = $pdo->prepare("SELECT w.* FROM work_experience w JOIN users u ON w.user_id = u.id WHERE u.id = ? AND w.is_deleted = 0 ORDER BY w.start_date DESC");
$stmt_work->execute([$user_id]);
$work = $stmt_work->fetchAll();

$stmt_ach = $pdo->prepare("SELECT a.* FROM achievements a JOIN users u ON a.user_id = u.id WHERE u.id = ? AND a.is_deleted = 0 ORDER BY a.date_earned DESC");
$stmt_ach->execute([$user_id]);
$achievements = $stmt_ach->fetchAll();

$stmt_blogs = $pdo->prepare("SELECT b.* FROM blogs b JOIN users u ON b.user_id = u.id WHERE u.id = ? AND b.is_deleted = 0 ORDER BY b.created_at DESC");
$stmt_blogs->execute([$user_id]);
$blogs = $stmt_blogs->fetchAll();

// Add status column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE reviews ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
} catch (PDOException $e) {
    // Column already exists
}

// Fetch only APPROVED Reviews
$stmt_reviews = $pdo->prepare("SELECT * FROM reviews WHERE user_id = ? AND status = 'approved' ORDER BY created_at DESC");
$stmt_reviews->execute([$user_id]);
$reviews = $stmt_reviews->fetchAll();

$stmt_avg_rating = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE user_id = ?");
$stmt_avg_rating->execute([$user_id]);
$avg_rating_row = $stmt_avg_rating->fetch();
$avg_rating = $avg_rating_row['avg_rating'] ? round($avg_rating_row['avg_rating'], 1) : 'No ratings yet';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile['username']); ?>'s Portfolio</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Desktop first - 1280px max width */
        .portfolio-container {
            max-width: 1280px;
            width: 100%;
            margin: 0 auto;
            padding: 2rem 0;
            box-sizing: border-box;
        }

        /* Navigation Header Styles */
        .portfolio-nav {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.95));
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .portfolio-nav ul {
            list-style: none;
            display: flex;
            gap: 0.5rem;
            margin: 0 auto;
            padding: 0;
            flex-wrap: wrap;
            max-width: 1280px;
            width: 100%;
            align-items: center;
            justify-content: flex-start;
            box-sizing: border-box;
        }

        .portfolio-nav a {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .portfolio-nav a:hover {
            color: var(--accent);
            background: rgba(59, 130, 246, 0.1);
            border-color: rgba(59, 130, 246, 0.3);
            transform: translateY(-2px);
        }

        .portfolio-nav a.active {
            color: #fff;
            background: rgba(59, 130, 246, 0.2);
            border-color: var(--accent);
        }

        html {
            scroll-behavior: smooth;
        }

        /* Large Desktop (1920px and up) */
        @media (min-width: 1920px) {
            .portfolio-container {
                padding: 2.5rem 0;
            }
            
            .portfolio-nav {
                padding: 1.2rem 2.5rem;
            }
        }

        /* Tablet (1024px - 1279px) */
        @media (max-width: 1279px) {
            .portfolio-container {
                padding: 1.5rem 0;
            }
            
            .portfolio-nav {
                padding: 0.75rem 1.5rem;
            }
            
            .portfolio-nav a {
                padding: 0.5rem 0.75rem;
                font-size: 0.85rem;
            }
        }

        /* Tablet (768px - 1023px) */
        @media (max-width: 1023px) {
            .portfolio-container {
                padding: 1.25rem 0;
            }

            .portfolio-nav {
                padding: 0.5rem 1.25rem;
                overflow-x: auto;
            }

            .portfolio-nav ul {
                gap: 0.25rem;
            }

            .portfolio-nav a {
                padding: 0.4rem 0.6rem;
                font-size: 0.8rem;
                flex-shrink: 0;
            }
        }

        /* Mobile (480px - 767px) */
        @media (max-width: 767px) {
            .portfolio-container {
                padding: 1rem 0;
            }

            .portfolio-nav {
                padding: 0.5rem 1rem;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .portfolio-nav ul {
                gap: 0.2rem;
            }

            .portfolio-nav a {
                padding: 0.35rem 0.5rem;
                font-size: 0.75rem;
                flex-shrink: 0;
            }
        }

        /* Small Mobile (Below 480px) */
        @media (max-width: 479px) {
            .portfolio-container {
                padding: 0.75rem 0;
            }

            .portfolio-nav {
                padding: 0.5rem 0.75rem;
            }

            .portfolio-nav a {
                padding: 0.3rem 0.4rem;
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Header -->
    <nav class="portfolio-nav">
        <ul>
            <li><a href="#hero" onclick="scrollToSection('hero')" class="active"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="#about" onclick="scrollToSection('about')"><i class="fas fa-user"></i> About</a></li>
            <?php if ($skills): ?><li><a href="#skills" onclick="scrollToSection('skills')"><i class="fas fa-star"></i> Skills</a></li><?php endif; ?>
            <?php if ($work): ?><li><a href="#work" onclick="scrollToSection('work')"><i class="fas fa-briefcase"></i> Work</a></li><?php endif; ?>
            <?php if ($education): ?><li><a href="#education" onclick="scrollToSection('education')"><i class="fas fa-graduation-cap"></i> Education</a></li><?php endif; ?>
            <?php if ($achievements): ?><li><a href="#achievements" onclick="scrollToSection('achievements')"><i class="fas fa-trophy"></i> Achievements</a></li><?php endif; ?>
            <?php if ($blogs): ?><li><a href="#blogs" onclick="scrollToSection('blogs')"><i class="fas fa-blog"></i> Blogs</a></li><?php endif; ?>
            <li><a href="#contact" onclick="scrollToSection('contact')"><i class="fas fa-envelope"></i> Contact</a></li>
            <?php if ($reviews): ?><li><a href="#reviews" onclick="scrollToSection('reviews')"><i class="fas fa-comments"></i> Reviews</a></li><?php endif; ?>
        </ul>
    </nav>

    <!-- Success Notification -->
    <?php if ($review_submitted): ?>
        <div style="max-width: 1280px; margin: 1rem auto; padding: 0 1rem;">
            <div style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.15), rgba(34, 197, 94, 0.05)); border-left: 4px solid #22c55e; border-radius: 8px; padding: 1.5rem; display: flex; align-items: center; gap: 1rem; animation: slideDown 0.4s ease-out;">
                <i class="fas fa-check-circle" style="font-size: 1.5rem; color: #22c55e;"></i>
                <div>
                    <h3 style="margin: 0 0 0.25rem 0; color: #22c55e; font-weight: 600;">Review Submitted!</h3>
                    <p style="margin: 0; color: rgba(34, 197, 94, 0.8); font-size: 0.95rem;">Thank you for your review! It will appear on this portfolio after admin approval.</p>
                </div>
            </div>
            <style>
                @keyframes slideDown {
                    from {
                        opacity: 0;
                        transform: translateY(-10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
            </style>
        </div>
    <?php endif; ?>

    <div class="portfolio-container">
        <!-- Hero Section -->
        <header class="hero-section glass-panel" id="hero" style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center; padding: 3rem 2rem; border-radius: 16px; background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));">
            
            <!-- Left Content -->
            <div style="display: flex; flex-direction: column; justify-content: center;">
                <div style="margin-bottom: 1.5rem;">
                    <p style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 0.5rem;">Welcome to my portfolio</p>
                    <h1 style="font-size: clamp(2rem, 5vw, 3.5rem); font-weight: 700; line-height: 1.2; margin-bottom: 0.5rem; color: #fff;">
                        Hi, I am <span style="background: linear-gradient(135deg, #3b82f6, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"><?php echo htmlspecialchars($profile['username']); ?></span>
                    </h1>
                    <h2 style="font-size: clamp(1.2rem, 3vw, 2rem); color: var(--accent); font-weight: 500; margin: 0;"><?php echo htmlspecialchars($profile['title'] ?? 'Portfolio'); ?></h2>
                </div>

                <!-- CTA Buttons & Contact -->
                <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
                    <a href="../export/export_pdf.php?user=<?php echo urlencode($username); ?>" class="btn" style="display: inline-flex; width: fit-content !important; align-items: center; gap: 0.5rem; padding: 0.6rem 1.2rem; background: var(--accent); color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s; border: 2px solid var(--accent); white-space: nowrap; font-size: 0.95rem;">
                        <i class="fas fa-download"></i> Download Resume
                    </a>
                </div>

                <!-- Social Links -->
                <div class="social-links" style="display: flex; gap: 1.2rem; margin-bottom: 1.5rem;">
                    <?php if ($profile['email']): ?>
                        <a href="mailto:<?php echo htmlspecialchars($profile['email']); ?>" style="font-size: 1.3rem; color: var(--text-muted); transition: color 0.3s, transform 0.3s; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: rgba(59, 130, 246, 0.1); border-radius: 50%; border: 1px solid var(--border);" onmouseover="this.style.color='var(--accent)'; this.style.background='rgba(59, 130, 246, 0.2)'; this.style.transform='scale(1.1)';" onmouseout="this.style.color='var(--text-muted)'; this.style.background='rgba(59, 130, 246, 0.1)'; this.style.transform='scale(1)';">
                            <i class="fas fa-envelope"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($profile['linkedin']): ?>
                        <a href="<?php echo htmlspecialchars($profile['linkedin']); ?>" target="_blank" style="font-size: 1.3rem; color: var(--text-muted); transition: color 0.3s, transform 0.3s; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: rgba(59, 130, 246, 0.1); border-radius: 50%; border: 1px solid var(--border);" onmouseover="this.style.color='var(--accent)'; this.style.background='rgba(59, 130, 246, 0.2)'; this.style.transform='scale(1.1)';" onmouseout="this.style.color='var(--text-muted)'; this.style.background='rgba(59, 130, 246, 0.1)'; this.style.transform='scale(1)';">
                            <i class="fab fa-linkedin"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($profile['github']): ?>
                        <a href="<?php echo htmlspecialchars($profile['github']); ?>" target="_blank" style="font-size: 1.3rem; color: var(--text-muted); transition: color 0.3s, transform 0.3s; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: rgba(59, 130, 246, 0.1); border-radius: 50%; border: 1px solid var(--border);" onmouseover="this.style.color='var(--accent)'; this.style.background='rgba(59, 130, 246, 0.2)'; this.style.transform='scale(1.1)';" onmouseout="this.style.color='var(--text-muted)'; this.style.background='rgba(59, 130, 246, 0.1)'; this.style.transform='scale(1)';">
                            <i class="fab fa-github"></i>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Location & Rating -->
                <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                    <?php if ($profile['address']): ?>
                        <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 1rem; color: var(--text-muted);">
                            <i class="fas fa-map-marker-alt" style="color: var(--accent);"></i>
                            <span><?php echo htmlspecialchars($profile['address']); ?></span>
                        </div>
                    <?php endif; ?>
                    <span style="font-size: 1rem; color: var(--text-muted);">•</span>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="color: #fbbf24;"><i class="fas fa-star"></i></span>
                        <span style="color: var(--text-muted);"><?php echo $avg_rating; ?>/5 <span style="color: #888; font-size: 0.9rem;">rating</span></span>
                    </div>
                </div>
            </div>

            <!-- Right Content - Profile Image -->
            <div style="display: flex; align-items: center; justify-content: center;">
                <?php if ($profile['profile_image']): ?>
                    <div style="position: relative; width: 100%; max-width: 350px;">
                        <!-- Rotating light glow layer 1 -->
                        <div style="position: absolute; inset: -15px; background: conic-gradient(from 0deg, #3b82f6, #8b5cf6, #3b82f6); border-radius: 50%; opacity: 0.4; animation: rotateBg 6s linear infinite;"></div>
                        <!-- Expanding pulse layer -->
                        <div style="position: absolute; inset: -10px; background: radial-gradient(circle, rgba(59, 130, 246, 0.6), transparent); border-radius: 50%; opacity: 0.4; animation: expandPulse 4s ease-in-out infinite;"></div>
                        <!-- Inner rotating light -->
                        <div style="position: absolute; inset: -5px; background: linear-gradient(135deg, var(--accent), #8b5cf6); border-radius: 50%; opacity: 0.3; animation: rotateBg 8s linear infinite;"></div>
                        <img src="<?php echo htmlspecialchars($profile['profile_image']); ?>" alt="Profile" style="width: 100%; max-width: 320px; aspect-ratio: 1; border-radius: 50%; object-fit: cover; border: 6px solid var(--accent); box-shadow: 0 0 50px rgba(59, 130, 246, 0.6), 0 0 100px rgba(139, 92, 246, 0.3), inset 0 0 30px rgba(59, 130, 246, 0.2); position: relative; z-index: 1;">
                    </div>
                <?php else: ?>
                    <div style="position: relative; width: 100%; max-width: 350px;">
                        <!-- Rotating light glow layer 1 -->
                        <div style="position: absolute; inset: -15px; background: conic-gradient(from 0deg, #3b82f6, #8b5cf6, #3b82f6); border-radius: 50%; opacity: 0.4; animation: rotateBg 6s linear infinite;"></div>
                        <!-- Expanding pulse layer -->
                        <div style="position: absolute; inset: -10px; background: radial-gradient(circle, rgba(59, 130, 246, 0.6), transparent); border-radius: 50%; opacity: 0.4; animation: expandPulse 4s ease-in-out infinite;"></div>
                        <!-- Inner rotating light -->
                        <div style="position: absolute; inset: -5px; background: linear-gradient(135deg, var(--accent), #8b5cf6); border-radius: 50%; opacity: 0.3; animation: rotateBg 8s linear infinite;"></div>
                        <div style="width: 100%; max-width: 320px; aspect-ratio: 1; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--bg-secondary); font-size: 5rem; color: var(--text-muted); border: 6px solid var(--accent); box-shadow: 0 0 50px rgba(59, 130, 246, 0.6), 0 0 100px rgba(139, 92, 246, 0.3), inset 0 0 30px rgba(59, 130, 246, 0.2); position: relative; z-index: 1;">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <style>
                @keyframes rotateBg {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }

                @keyframes expandPulse {
                    0%, 100% { transform: scale(1); opacity: 0.2; }
                    50% { transform: scale(1.15); opacity: 0.5; }
                }

                @keyframes floatImage {
                    0%, 100% { transform: translateY(0); }
                    50% { transform: translateY(-8px); }
                }

                /* Desktop & Large Devices */
                @media (min-width: 1024px) {
                    .hero-section, .about-section {
                        grid-template-columns: 1fr 1fr !important;
                        gap: 3rem !important;
                        padding: 3rem 2rem !important;
                    }
                    
                    #contact {
                        grid-template-columns: 1.5fr 1fr !important;
                        gap: 3rem !important;
                        padding: 3rem 2rem !important;
                    }
                }

                /* Tablet Devices (1024px and below) */
                @media (max-width: 1023px) {
                    .hero-section, .about-section {
                        grid-template-columns: 1fr !important;
                        gap: 2rem !important;
                        padding: 2.5rem 1.5rem !important;
                    }

                    #contact {
                        grid-template-columns: 1fr !important;
                        gap: 2rem !important;
                        padding: 2.5rem 1.5rem !important;
                    }

                    .hero-section h1 {
                        font-size: 2.2rem !important;
                    }

                    .hero-section h2 {
                        font-size: 1.4rem !important;
                    }
                }

                /* Small Tablets & Large Phones (768px - 1023px) */
                @media (max-width: 767px) {
                    .hero-section, .about-section {
                        grid-template-columns: 1fr !important;
                        gap: 1.5rem !important;
                        padding: 2rem 1.25rem !important;
                    }

                    #contact {
                        grid-template-columns: 1fr !important;
                        gap: 1.5rem !important;
                        padding: 2rem 1.25rem !important;
                    }

                    .hero-section h1 {
                        font-size: 1.9rem !important;
                    }

                    .hero-section h2 {
                        font-size: 1.2rem !important;
                    }
                }

                /* Phones (480px - 767px) */
                @media (max-width: 767px) {
                    .hero-section {
                        padding: 1.5rem 1rem !important;
                        gap: 1.25rem !important;
                    }

                    .hero-section h1 {
                        font-size: 1.7rem !important;
                    }

                    .hero-section h2 {
                        font-size: 1.1rem !important;
                    }

                    .hero-section .social-links {
                        gap: 0.75rem !important;
                    }
                }

                /* Small Phones (Below 480px) */
                @media (max-width: 479px) {
                    .hero-section, .about-section {
                        grid-template-columns: 1fr !important;
                        gap: 1rem !important;
                        padding: 1.25rem 0.75rem !important;
                    }

                    #contact {
                        grid-template-columns: 1fr !important;
                        gap: 1rem !important;
                        padding: 1.25rem 0.75rem !important;
                    }

                    .hero-section h1 {
                        font-size: 1.5rem !important;
                    }

                    .hero-section h2 {
                        font-size: 1rem !important;
                    }

                    .hero-section .social-links {
                        gap: 0.5rem !important;
                    }
                }
            </style>
        </header>

        <!-- About Me Section -->
        <section class="glass-panel about-section" id="about" style="animation-delay: 0.05s; display: grid; grid-template-columns: 1fr 1.5fr; gap: 3rem; align-items: center; margin-top: 2rem;">
            <!-- Left Content - Animated Image -->
            <div style="display: flex; align-items: center; justify-content: center;">
                <?php 
                $display_image = !empty($profile['about_image']) ? $profile['about_image'] : $profile['profile_image'];
                if ($display_image): 
                ?>
                    <div style="position: relative; width: 100%; max-width: 280px; animation: floatImage 4s ease-in-out infinite;">
                        <img src="<?php echo htmlspecialchars($display_image); ?>" alt="About Me" style="width: 100%; border-radius: 20px; object-fit: cover;">
                    </div>
                <?php else: ?>
                    <div style="position: relative; width: 100%; max-width: 280px; aspect-ratio: 1; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 5rem; color: var(--text-muted); animation: floatImage 4s ease-in-out infinite;">
                        <i class="fas fa-user-circle"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Content - Bio -->
            <div>
                <h2 style="color: var(--accent); margin-bottom: 1.5rem; font-size: 2rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-user"></i> About Me
                </h2>
                <div style="font-size: 1.1rem; color: var(--text-muted); line-height: 1.8;">
                    <?php echo nl2br(htmlspecialchars($profile['bio'] ?? '')); ?>
                </div>
            </div>
        </section>

        <!-- Skills -->
        <?php if ($skills): ?>
        <section class="glass-panel" id="skills" style="animation-delay: 0.1s;">
            <h2><i class="fas fa-star" style="color: var(--accent);"></i> Skills</h2>
            <div class="card-grid" style="margin-top: 1.5rem;">
                <?php foreach ($skills as $s): ?>
                    <div class="card">
                        <strong style="font-size: 1.1rem;"><?php echo htmlspecialchars($s['skill_name']); ?></strong> <span style="float: right; color: var(--accent);"><?php echo $s['proficiency']; ?>%</span>
                        <div class="skill-bar">
                            <div class="skill-progress" style="width: <?php echo $s['proficiency']; ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Work Experience -->
        <?php if ($work): ?>
        <section class="glass-panel" id="work" style="animation-delay: 0.2s;">
            <h2><i class="fas fa-briefcase" style="color: var(--accent);"></i> Work Experience</h2>
            <div class="timeline" style="margin-top: 1.5rem;">
                <?php foreach ($work as $w): ?>
                    <div class="timeline-item">
                        <h3 style="color: #fff; margin-bottom: 0.2rem;"><?php echo htmlspecialchars($w['job_title']); ?></h3>
                        <div style="color: var(--accent); font-weight: 500; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($w['company']); ?></div>
                        <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;"><i class="fas fa-calendar-alt"></i> <?php echo $w['start_date']; ?> - <?php echo $w['end_date'] ?: 'Present'; ?></div>
                        <p><?php echo htmlspecialchars($w['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Education -->
        <?php if ($education): ?>
        <section class="glass-panel" id="education" style="animation-delay: 0.3s;">
            <h2><i class="fas fa-graduation-cap" style="color: var(--accent);"></i> Education</h2>
            <div class="timeline" style="margin-top: 1.5rem;">
                <?php foreach ($education as $e): ?>
                    <div class="timeline-item">
                        <h3 style="color: #fff; margin-bottom: 0.2rem;"><?php echo htmlspecialchars($e['degree']); ?></h3>
                        <div style="color: var(--accent); font-weight: 500; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($e['institution']); ?></div>
                        <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;"><i class="fas fa-calendar-alt"></i> <?php echo $e['start_date']; ?> - <?php echo $e['end_date'] ?: 'Present'; ?></div>
                        <p><?php echo htmlspecialchars($e['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Achievements -->
        <?php if ($achievements): ?>
        <section class="glass-panel" id="achievements" style="animation-delay: 0.4s;">
            <h2><i class="fas fa-trophy" style="color: var(--accent);"></i> Achievements</h2>
            <div class="card-grid" style="margin-top: 1.5rem;">
                <?php foreach ($achievements as $a): ?>
                    <div class="card">
                        <h3 style="color: #fff; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($a['title']); ?></h3>
                        <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;"><i class="fas fa-calendar-check"></i> <?php echo $a['date_earned']; ?></div>
                        <p><?php echo htmlspecialchars($a['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Blogs -->
        <?php if ($blogs): ?>
        <section class="glass-panel" id="blogs" style="animation-delay: 0.5s;">
            <h2><i class="fas fa-blog" style="color: var(--accent);"></i> Blog Posts</h2>
            <div style="margin-top: 1.5rem;">
                <?php foreach ($blogs as $b): ?>
                    <article class="card" style="margin-bottom: 1.5rem;">
                        <h3 style="color: var(--accent); margin-bottom: 0.5rem;"><?php echo htmlspecialchars($b['title']); ?></h3>
                        <div style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1rem;"><i class="fas fa-clock"></i> <?php echo date('M j, Y', strtotime($b['created_at'])); ?></div>
                        <p><?php echo nl2br(htmlspecialchars($b['content'])); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Contact & Review -->
        <section class="glass-panel" id="contact" style="animation-delay: 0.6s; display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem;">
            <!-- Left: Review Form -->
            <div style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.08), rgba(14, 165, 233, 0.05)); border: 1px solid rgba(79, 70, 229, 0.2); border-radius: 16px; padding: 2.5rem; backdrop-filter: blur(10px);">
                <h2 style="color: var(--accent); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.75rem; font-size: 1.8rem;">
                    <i class="fas fa-star"></i> Leave a Review
                </h2>
                <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 0.95rem;">Share your thoughts about this portfolio</p>
                
                <form action="submit_review.php" method="POST">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
                    <input type="hidden" name="rating" id="ratingInput" value="">
                    
                    <!-- Name Input -->
                    <div class="form-group">
                        <label style="color: var(--text-secondary); font-weight: 600; margin-bottom: 0.75rem; display: block;">Your Name</label>
                        <input type="text" name="visitor_name" required placeholder="Enter your full name" style="width: 100%; padding: 1rem 1.25rem; background: rgba(255, 255, 255, 0.03); border: 1.5px solid var(--border); border-radius: 10px; color: var(--text-main); font-family: 'Inter', sans-serif; font-size: 1rem; transition: all 0.3s ease;" onmouseover="this.style.borderColor='rgba(79, 70, 229, 0.3)'; this.style.background='rgba(79, 70, 229, 0.05)';" onmouseout="this.style.borderColor='var(--border)'; this.style.background='rgba(255, 255, 255, 0.03)';">
                    </div>
                    
                    <!-- Star Rating -->
                    <div class="form-group">
                        <label style="color: var(--text-secondary); font-weight: 600; margin-bottom: 1rem; display: block;">Rate this Portfolio</label>
                        <div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem;">
                            <div id="starRating" style="display: flex; gap: 0.5rem;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <button type="button" class="star-btn" data-rating="<?php echo $i; ?>" style="font-size: 2.5rem; color: rgba(79, 70, 229, 0.3); cursor: pointer; background: none; border: none; transition: all 0.2s ease; padding: 0.25rem;" onmouseover="this.style.color='#f59e0b'; this.style.transform='scale(1.15)';" onmouseout="this.style.transform='scale(1)';">
                                        ★
                                    </button>
                                <?php endfor; ?>
                            </div>
                            <span id="ratingText" style="color: var(--text-muted); font-weight: 600; margin-left: 1rem; min-width: 80px;">Select rating</span>
                        </div>
                    </div>
                    
                    <!-- Comment Input -->
                    <div class="form-group">
                        <label style="color: var(--text-secondary); font-weight: 600; margin-bottom: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                            <span>Your Review</span>
                            <span id="charCount" style="font-size: 0.8rem; color: var(--text-muted); font-weight: 400;">0/500</span>
                        </label>
                        <textarea name="comment" required placeholder="Share your honest feedback about this portfolio..." rows="4" style="width: 100%; padding: 1rem 1.25rem; background: rgba(255, 255, 255, 0.03); border: 1.5px solid var(--border); border-radius: 10px; color: var(--text-main); font-family: 'Inter', sans-serif; font-size: 1rem; resize: none; transition: all 0.3s ease;" onmouseover="this.style.borderColor='rgba(79, 70, 229, 0.3)'; this.style.background='rgba(79, 70, 229, 0.05)';" onmouseout="this.style.borderColor='var(--border)'; this.style.background='rgba(255, 255, 255, 0.03)';" oninput="updateCharCount(this);" maxlength="500"></textarea>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" style="width: 100%; padding: 1.1rem 1.5rem; background: linear-gradient(135deg, var(--accent), var(--accent-light)); color: #fff; border: none; border-radius: 10px; font-size: 1rem; font-weight: 700; letter-spacing: 0.3px; cursor: pointer; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); text-align: center; position: relative; overflow: hidden; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3); display: flex; align-items: center; justify-content: center; gap: 0.75rem;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 25px rgba(79, 70, 229, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(79, 70, 229, 0.3)';">
                        <i class="fas fa-paper-plane"></i> Submit Your Review
                    </button>
                </form>
                
                <script>
                    const ratingInput = document.getElementById('ratingInput');
                    const ratingText = document.getElementById('ratingText');
                    const starBtns = document.querySelectorAll('.star-btn');
                    
                    const ratingLabels = ['', 'Poor 😞', 'Average 😐', 'Good 😊', 'Excellent 😍', 'Outstanding ⭐'];
                    const ratingColors = ['', '#ef4444', '#f59e0b', '#10b981', '#0ea5e9', '#6366f1'];
                    
                    starBtns.forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            e.preventDefault();
                            const rating = this.dataset.rating;
                            ratingInput.value = rating;
                            
                            // Update all stars
                            starBtns.forEach(b => {
                                if (b.dataset.rating <= rating) {
                                    b.style.color = ratingColors[rating];
                                } else {
                                    b.style.color = 'rgba(79, 70, 229, 0.3)';
                                }
                            });
                            
                            // Update text
                            ratingText.textContent = ratingLabels[rating];
                            ratingText.style.color = ratingColors[rating];
                        });
                        
                        btn.addEventListener('mouseover', function() {
                            const hoverRating = this.dataset.rating;
                            starBtns.forEach(b => {
                                if (b.dataset.rating <= hoverRating) {
                                    b.style.color = ratingColors[hoverRating];
                                } else {
                                    b.style.color = 'rgba(79, 70, 229, 0.3)';
                                }
                            });
                        });
                        
                        btn.addEventListener('mouseout', function() {
                            const selectedRating = ratingInput.value || 0;
                            starBtns.forEach(b => {
                                if (b.dataset.rating <= selectedRating) {
                                    b.style.color = ratingColors[selectedRating];
                                } else {
                                    b.style.color = 'rgba(79, 70, 229, 0.3)';
                                }
                            });
                        });
                    });
                    
                    function updateCharCount(textarea) {
                        const count = textarea.value.length;
                        document.getElementById('charCount').textContent = count + '/500';
                        
                        if (count > 400) {
                            document.getElementById('charCount').style.color = '#f59e0b';
                        } else if (count > 450) {
                            document.getElementById('charCount').style.color = '#ef4444';
                        } else {
                            document.getElementById('charCount').style.color = 'var(--text-muted)';
                        }
                    }
                </script>
            </div>
            
            <!-- Right: Contact Info with Image -->
            <div style="background: linear-gradient(135deg, rgba(14, 165, 233, 0.05), rgba(79, 70, 229, 0.08)); border: 1px solid rgba(14, 165, 233, 0.2); border-radius: 16px; padding: 2.5rem; backdrop-filter: blur(10px); display: flex; flex-direction: column; gap: 2rem; height: 100%;">
                <!-- Contact Image -->
                <?php if (!empty($profile['contact_image'])): ?>
                    <div style="width: 100%; border-radius: 12px; overflow: hidden; ">
                        <img src="<?php echo htmlspecialchars($profile['contact_image']); ?>" alt="Contact" style="width: 100%; height: 260px; object-fit: cover; display: block; border-radius: 12px; transition: transform 0.4s ease;" onmouseover="this.style.transform='scale(1.05)';" onmouseout="this.style.transform='scale(1)';">
                    </div>
                <?php endif; ?>
                
                <!-- Contact Information -->
                <div style="flex-grow: 1; display: flex; flex-direction: column; justify-content: center;">
                    <h2 style="color: var(--accent); margin-bottom: 1.5rem; font-size: 1.8rem; display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-envelope-open-text"></i> Get In Touch
                    </h2>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 1.25rem;">
                        <li style="display: flex; align-items: center; gap: 1.25rem; padding: 1.25rem; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.3s ease;" onmouseover="this.style.background='rgba(79, 70, 229, 0.1)'; this.style.borderColor='rgba(79, 70, 229, 0.2)'; this.style.transform='translateX(5px)';" onmouseout="this.style.background='rgba(255, 255, 255, 0.03)'; this.style.borderColor='rgba(255, 255, 255, 0.05)'; this.style.transform='translateX(0)';">
                            <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(79, 70, 229, 0.15); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.25rem; flex-shrink: 0;">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Email</span>
                                <span style="color: var(--text-main); font-weight: 500; font-size: 1rem; word-break: break-all;"><?php echo htmlspecialchars($profile['email']); ?></span>
                            </div>
                        </li>
                        <?php if ($profile['phone']): ?>
                            <li style="display: flex; align-items: center; gap: 1.25rem; padding: 1.25rem; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.3s ease;" onmouseover="this.style.background='rgba(79, 70, 229, 0.1)'; this.style.borderColor='rgba(79, 70, 229, 0.2)'; this.style.transform='translateX(5px)';" onmouseout="this.style.background='rgba(255, 255, 255, 0.03)'; this.style.borderColor='rgba(255, 255, 255, 0.05)'; this.style.transform='translateX(0)';">
                                <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(79, 70, 229, 0.15); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.25rem; flex-shrink: 0;">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                    <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Phone</span>
                                    <span style="color: var(--text-main); font-weight: 500; font-size: 1rem;"><?php echo htmlspecialchars($profile['phone']); ?></span>
                                </div>
                            </li>
                        <?php endif; ?>
                        <?php if ($profile['address']): ?>
                            <li style="display: flex; align-items: center; gap: 1.25rem; padding: 1.25rem; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.3s ease;" onmouseover="this.style.background='rgba(79, 70, 229, 0.1)'; this.style.borderColor='rgba(79, 70, 229, 0.2)'; this.style.transform='translateX(5px)';" onmouseout="this.style.background='rgba(255, 255, 255, 0.03)'; this.style.borderColor='rgba(255, 255, 255, 0.05)'; this.style.transform='translateX(0)';">
                                <div style="width: 48px; height: 48px; border-radius: 50%; background: rgba(79, 70, 229, 0.15); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.25rem; flex-shrink: 0;">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                    <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Location</span>
                                    <span style="color: var(--text-main); font-weight: 500; font-size: 1rem; line-height: 1.4;"><?php echo htmlspecialchars($profile['address']); ?></span>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Reviews List - Carousel -->
        <?php if ($reviews): ?>
        <section class="glass-panel" id="reviews" style="animation-delay: 0.7s; overflow: hidden;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2><i class="fas fa-comments" style="color: var(--accent);"></i> Recent Reviews</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <button class="carousel-btn" onclick="reviewCarousel(-1)" style="width: 40px; height: 40px; border-radius: 50%; background: rgba(79, 70, 229, 0.2); border: 1px solid rgba(79, 70, 229, 0.3); color: var(--accent); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;" onmouseover="this.style.background='rgba(79, 70, 229, 0.3)'; this.style.transform='scale(1.1)';" onmouseout="this.style.background='rgba(79, 70, 229, 0.2)'; this.style.transform='scale(1)';">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="carousel-btn" onclick="reviewCarousel(1)" style="width: 40px; height: 40px; border-radius: 50%; background: rgba(79, 70, 229, 0.2); border: 1px solid rgba(79, 70, 229, 0.3); color: var(--accent); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;" onmouseover="this.style.background='rgba(79, 70, 229, 0.3)'; this.style.transform='scale(1.1)';" onmouseout="this.style.background='rgba(79, 70, 229, 0.2)'; this.style.transform='scale(1)';">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            
            <div style="position: relative; overflow: hidden;">
                <div id="reviewsCarousel" style="display: flex; gap: 1.5rem; transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);">
                    <?php foreach ($reviews as $r): ?>
                        <div class="review-slide" style="flex: 0 0 calc(100% - 1.5rem); min-width: calc(100% - 1.5rem); background: linear-gradient(135deg, rgba(79, 70, 229, 0.08), rgba(14, 165, 233, 0.05)); border: 1px solid rgba(79, 70, 229, 0.2); border-radius: 12px; padding: 2rem; backdrop-filter: blur(10px); animation: slideIn 0.6s ease-out;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1.5rem;">
                                <div>
                                    <h3 style="color: #fff; margin: 0 0 0.5rem 0; font-size: 1.2rem;"><?php echo htmlspecialchars($r['visitor_name']); ?></h3>
                                    <span style="color: #fbbf24; font-size: 1.1rem;">
                                        <?php echo str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']); ?>
                                    </span>
                                </div>
                                <span style="color: var(--text-muted); font-size: 0.9rem;">
                                    <?php echo date('M j, Y', strtotime($r['created_at'])); ?>
                                </span>
                            </div>
                            <p style="color: var(--text-muted); font-size: 1rem; line-height: 1.6; margin: 1rem 0; font-style: italic;">
                                "<?php echo htmlspecialchars($r['comment']); ?>"
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem;">
                <?php foreach (array_keys($reviews) as $i): ?>
                    <div class="carousel-dot" onclick="goToReview(<?php echo $i; ?>)" style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo $i === 0 ? 'var(--accent)' : 'rgba(79, 70, 229, 0.3)'; ?>; cursor: pointer; transition: all 0.3s;"></div>
                <?php endforeach; ?>
            </div>

            <style>
                @keyframes slideIn {
                    from {
                        opacity: 0;
                        transform: translateX(20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }

                /* Responsive carousel */
                @media (min-width: 768px) {
                    .review-slide {
                        flex: 0 0 calc(50% - 0.75rem) !important;
                        min-width: calc(50% - 0.75rem) !important;
                    }
                }

                @media (min-width: 1200px) {
                    .review-slide {
                        flex: 0 0 calc(33.333% - 1rem) !important;
                        min-width: calc(33.333% - 1rem) !important;
                    }
                }
            </style>

            <script>
                let currentReviewIndex = 0;
                const reviewSlides = document.querySelectorAll('.review-slide');
                const reviewCount = reviewSlides.length;

                function updateCarouselPosition() {
                    const carousel = document.getElementById('reviewsCarousel');
                    const slideWidth = reviewSlides[0].offsetWidth + 24; // 24px gap
                    carousel.style.transform = `translateX(-${currentReviewIndex * slideWidth}px)`;
                    
                    // Update dots
                    document.querySelectorAll('.carousel-dot').forEach((dot, i) => {
                        dot.style.background = i === currentReviewIndex ? 'var(--accent)' : 'rgba(79, 70, 229, 0.3)';
                    });
                }

                function reviewCarousel(direction) {
                    currentReviewIndex += direction;
                    if (currentReviewIndex < 0) {
                        currentReviewIndex = reviewCount - 1;
                    } else if (currentReviewIndex >= reviewCount) {
                        currentReviewIndex = 0;
                    }
                    updateCarouselPosition();
                }

                function goToReview(index) {
                    currentReviewIndex = index;
                    updateCarouselPosition();
                }

                // Auto-advance carousel every 5 seconds
                setInterval(() => {
                    if (reviewCount > 1) {
                        reviewCarousel(1);
                    }
                }, 5000);

                // Update on resize
                window.addEventListener('resize', updateCarouselPosition);
            </script>
        </section>
        <?php endif; ?>

        
    </div>

    <script>
        // Navigation active state on scroll
        function updateActiveNav() {
            const sections = document.querySelectorAll('section[id], header[id]');
            const navLinks = document.querySelectorAll('.portfolio-nav a');
            
            let current = 'hero';
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                
                if (window.scrollY >= sectionTop - 200) {
                    current = section.getAttribute('id');
                }
            });
            
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        }

        // Smooth scroll function
        function scrollToSection(id) {
            event.preventDefault();
            const element = document.getElementById(id);
            if (element) {
                const navHeight = document.querySelector('.portfolio-nav').offsetHeight;
                const elementPosition = element.offsetTop - navHeight - 20;
                window.scrollTo({
                    top: elementPosition,
                    behavior: 'smooth'
                });
            }
        }

        // Update active state on scroll
        window.addEventListener('scroll', updateActiveNav);
        
        // Initial call
        updateActiveNav();
    </script>
</body>
</html>
