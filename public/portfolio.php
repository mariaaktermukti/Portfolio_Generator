<?php
require_once '../config/db.php';

$username = $_GET['user'] ?? '';

if (!$username) {
    die("User not specified.");
}

// Fetch user, about, contact via JOIN
$stmt = $pdo->prepare("
    SELECT u.id as user_id, u.username, u.email, 
           a.bio, a.title, a.profile_image, 
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        .hero-section {
            text-align: center;
            padding: 4rem 0;
        }
        .profile-img {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--accent);
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.4);
            margin-bottom: 1.5rem;
        }
        .social-links a {
            font-size: 1.5rem;
            margin: 0 0.5rem;
            color: var(--text-muted);
            transition: color 0.3s;
        }
        .social-links a:hover {
            color: var(--accent);
        }
    </style>
</head>
<body>
    <div class="portfolio-container">
        <!-- Hero Section -->
        <header class="hero-section glass-panel">
            <?php if ($profile['profile_image']): ?>
                <img src="<?php echo htmlspecialchars($profile['profile_image']); ?>" alt="Profile" class="profile-img">
            <?php else: ?>
                <div class="profile-img" style="display:inline-flex; align-items:center; justify-content:center; background:var(--bg-secondary); font-size:4rem; color:var(--text-muted);">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
            
            <h1 style="font-size: 3.5rem; margin-bottom: 0.5rem; background: linear-gradient(135deg, #fff, #94a3b8); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo htmlspecialchars($profile['username']); ?></h1>
            <h2 style="color: var(--accent); font-weight: 400;"><?php echo htmlspecialchars($profile['title'] ?? 'Portfolio'); ?></h2>
            
            <div style="margin: 1.5rem 0;">
                <span class="badge badge-approved" style="font-size: 1rem;"><i class="fas fa-star"></i> <?php echo $avg_rating; ?> / 5</span>
            </div>

            <div class="social-links" style="margin-top: 1.5rem;">
                <?php if ($profile['email']): ?><a href="mailto:<?php echo htmlspecialchars($profile['email']); ?>"><i class="fas fa-envelope"></i></a><?php endif; ?>
                <?php if ($profile['linkedin']): ?><a href="<?php echo htmlspecialchars($profile['linkedin']); ?>" target="_blank"><i class="fab fa-linkedin"></i></a><?php endif; ?>
                <?php if ($profile['github']): ?><a href="<?php echo htmlspecialchars($profile['github']); ?>" target="_blank"><i class="fab fa-github"></i></a><?php endif; ?>
            </div>
            
            <p style="max-width: 600px; margin: 2rem auto 0; font-size: 1.1rem; color: var(--text-muted); line-height: 1.8;">
                <?php echo nl2br(htmlspecialchars($profile['bio'] ?? '')); ?>
            </p>
        </header>

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

        <div style="text-align: center; margin-top: 3rem; margin-bottom: 2rem;">
            <a href="../export/export_pdf.php?user=<?php echo urlencode($username); ?>" class="btn" style="display: inline-block; width: auto;"><i class="fas fa-file-pdf"></i> Download Resume PDF</a>
        </div>
    </div>
</body>
</html>
