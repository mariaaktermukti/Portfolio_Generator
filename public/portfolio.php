<?php
require_once '../config/db.php';

$username = $_GET['user'] ?? '';

if (!$username) {
    die("User not specified.");
}

// Fetch user, about, contact via JOIN
$stmt = $pdo->prepare("
    SELECT u.id as user_id, u.username, u.email, 
           a.bio, a.title, a.profile_image, a.about_image, 
           c.phone, c.address, c.linkedin, c.github
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

// Fetch Reviews
$stmt_reviews = $pdo->prepare("SELECT * FROM reviews WHERE user_id = ? ORDER BY created_at DESC");
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
        .portfolio-container {
            max-width: 1280px;
            width: 100%;
            margin: 0 auto;
            padding-top: 2rem;
            padding-bottom: 2rem;
           
            box-sizing: border-box;
        }

        @media (max-width: 1024px) {
            .portfolio-container {
                padding: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .portfolio-container {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .portfolio-container {
                padding: 0.75rem;
            }
        }

        .hero-section {
            text-align: left;
            padding: 4rem 2rem;
        }
    </style>
</head>
<body>
    <div class="portfolio-container">
        <!-- Hero Section -->
        <header class="hero-section glass-panel" style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center; padding: 3rem 2rem; border-radius: 16px; background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));">
            
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

                @media (max-width: 768px) {
                    .hero-section, .about-section {
                        grid-template-columns: 1fr !important;
                        gap: 2rem !important;
                        padding: 2rem 1.5rem !important;
                    }

                    .hero-section h1 {
                        font-size: 2rem !important;
                    }

                    .hero-section h2 {
                        font-size: 1.3rem !important;
                    }
                }

                @media (max-width: 480px) {
                    .hero-section {
                        padding: 1.5rem 1rem !important;
                        gap: 1.5rem !important;
                    }

                    .hero-section h1 {
                        font-size: 1.7rem !important;
                    }

                    .hero-section h2 {
                        font-size: 1.1rem !important;
                    }
                }
            </style>
        </header>

        <!-- About Me Section -->
        <section class="glass-panel about-section" style="animation-delay: 0.05s; display: grid; grid-template-columns: 1fr 1.5fr; gap: 3rem; align-items: center; margin-top: 2rem;">
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
        <section class="glass-panel" style="animation-delay: 0.1s;">
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
        <section class="glass-panel" style="animation-delay: 0.2s;">
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
        <section class="glass-panel" style="animation-delay: 0.3s;">
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
        <section class="glass-panel" style="animation-delay: 0.4s;">
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
        <section class="glass-panel" style="animation-delay: 0.5s;">
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
        <section class="glass-panel" style="animation-delay: 0.6s; display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div>
                <h2><i class="fas fa-envelope" style="color: var(--accent);"></i> Contact Me</h2>
                <ul style="list-style: none; padding: 0; margin-top: 1.5rem;">
                    <li style="margin-bottom: 1rem;"><i class="fas fa-envelope" style="color: var(--accent); width: 25px;"></i> <?php echo htmlspecialchars($profile['email']); ?></li>
                    <?php if ($profile['phone']): ?><li style="margin-bottom: 1rem;"><i class="fas fa-phone" style="color: var(--accent); width: 25px;"></i> <?php echo htmlspecialchars($profile['phone']); ?></li><?php endif; ?>
                    <?php if ($profile['address']): ?><li style="margin-bottom: 1rem;"><i class="fas fa-map-marker-alt" style="color: var(--accent); width: 25px;"></i> <?php echo htmlspecialchars($profile['address']); ?></li><?php endif; ?>
                </ul>
            </div>
            
            <div>
                <h2><i class="fas fa-star" style="color: var(--accent);"></i> Leave a Review</h2>
                <form action="submit_review.php" method="POST" style="margin-top: 1.5rem;">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
                    
                    <div class="form-group">
                        <input type="text" name="visitor_name" required placeholder="Your Name">
                    </div>
                    
                    <div class="form-group">
                        <select name="rating" required style="width: 100%; padding: 0.8rem; background: rgba(0,0,0,0.2); border: 1px solid var(--border); border-radius: 8px; color: #fff;">
                            <option value="" disabled selected>Rate this portfolio</option>
                            <option value="5">⭐⭐⭐⭐⭐ (5) Excellent</option>
                            <option value="4">⭐⭐⭐⭐ (4) Good</option>
                            <option value="3">⭐⭐⭐ (3) Average</option>
                            <option value="2">⭐⭐ (2) Poor</option>
                            <option value="1">⭐ (1) Terrible</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <textarea name="comment" required placeholder="Leave a comment..." rows="3"></textarea>
                    </div>
                    
                    <button type="submit" class="btn"><i class="fas fa-paper-plane"></i> Submit Review</button>
                </form>
            </div>
        </section>

        <!-- Reviews List -->
        <?php if ($reviews): ?>
        <section class="glass-panel" style="animation-delay: 0.7s;">
            <h2><i class="fas fa-comments" style="color: var(--accent);"></i> Recent Reviews</h2>
            <div class="card-grid" style="margin-top: 1.5rem;">
                <?php foreach ($reviews as $r): ?>
                    <div class="card">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                            <strong style="color: #fff;"><?php echo htmlspecialchars($r['visitor_name']); ?></strong>
                            <span style="color: #f59e0b;">
                                <?php echo str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']); ?>
                            </span>
                        </div>
                        <p style="font-size: 0.95rem; font-style: italic;">"<?php echo htmlspecialchars($r['comment']); ?>"</p>
                        <div style="margin-top: 1rem; font-size: 0.8rem; color: var(--text-muted); text-align: right;">
                            <?php echo date('M j, Y', strtotime($r['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        
    </div>
</body>
</html>
