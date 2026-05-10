<?php
require_once 'config/config/db.php';

$username = $_GET['user'] ?? '';
if (!$username) die("No user specified.");

$stmt = $pdo->prepare("
    SELECT u.id, u.username, a.full_name, a.profession, a.bio, a.profile_image,
           c.email, c.phone, c.address, c.website, c.linkedin, c.github
    FROM users u
    LEFT JOIN about a ON u.id = a.user_id AND a.is_deleted = 0
    LEFT JOIN contact c ON u.id = c.user_id AND c.is_deleted = 0
    WHERE u.username = :uname AND u.is_deleted = 0
");
$stmt->execute([':uname' => $username]);
$profile = $stmt->fetch();
if (!$profile) die("Portfolio not found.");
$user_id = $profile['id'];

// Track view
$ip = $_SERVER['REMOTE_ADDR'];
$track = $pdo->prepare("INSERT INTO portfolio_views (user_id, viewer_ip) VALUES (?, ?)");
$track->execute([$user_id, $ip]);

// Fetch sections
$education = $pdo->prepare("SELECT * FROM education WHERE user_id=? AND is_deleted=0 ORDER BY start_date DESC");
$education->execute([$user_id]);
$skills = $pdo->prepare("SELECT * FROM skills WHERE user_id=? AND is_deleted=0");
$skills->execute([$user_id]);
$work = $pdo->prepare("SELECT * FROM work_experience WHERE user_id=? AND is_deleted=0 ORDER BY start_date DESC");
$work->execute([$user_id]);
$achievements = $pdo->prepare("SELECT * FROM achievements WHERE user_id=? AND is_deleted=0");
$achievements->execute([$user_id]);
$blogs = $pdo->prepare("SELECT * FROM blogs WHERE user_id=? AND is_deleted=0 ORDER BY created_at DESC");
$blogs->execute([$user_id]);

// Reviews + average
$reviews = $pdo->prepare("SELECT * FROM reviews WHERE reviewed_user_id=? AND is_deleted=0 ORDER BY created_at DESC");
$reviews->execute([$user_id]);
$avg = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM reviews WHERE reviewed_user_id=? AND is_deleted=0");
$avg->execute([$user_id]);
$rating = $avg->fetch();

// Analytics
$skillCount = $pdo->prepare("SELECT COUNT(*) FROM skills WHERE user_id=? AND is_deleted=0");
$skillCount->execute([$user_id]);
$totalSkills = $skillCount->fetchColumn();
$workCount = $pdo->prepare("SELECT COUNT(*) FROM work_experience WHERE user_id=? AND is_deleted=0");
$workCount->execute([$user_id]);
$totalWork = $workCount->fetchColumn();

// Most popular skill (GROUP BY + ORDER)
$popular = $pdo->query("SELECT skill_name, COUNT(*) as cnt FROM skills WHERE is_deleted=0 GROUP BY skill_name ORDER BY cnt DESC LIMIT 1")->fetch();

// Ranking subquery
$rank = $pdo->prepare("
    SELECT COUNT(*)+1 as rank FROM users u2
    WHERE u2.is_deleted = 0
      AND (SELECT COALESCE(AVG(r.rating),0) FROM reviews r WHERE r.reviewed_user_id = u2.id AND r.is_deleted=0)
          > (SELECT COALESCE(AVG(r2.rating),0) FROM reviews r2 WHERE r2.reviewed_user_id = ? AND r2.is_deleted=0)
");
$rank->execute([$user_id]);
$userRank = $rank->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head><title><?= htmlspecialchars($profile['full_name']) ?> – Portfolio</title>    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto; padding: 20px; color: #333; }
        .header { border-bottom: 2px solid #007bff; padding-bottom: 20px; margin-bottom: 20px; text-align: center; }
        h1 { color: #007bff; margin-bottom: 5px; }
        h2 { color: #007bff; border-bottom: 1px solid #007bff; padding-bottom: 5px; margin-top: 30px; }
        h3 { color: #444; }
        .contact-info { background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .item { margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .item:last-child { border-bottom: none; }
        .date { font-style: italic; color: #777; font-size: 0.9em; }
        .download-btn { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-bottom: 20px; }
        .download-btn:hover { background: #0056b3; }
        .review-form { background: #f9f9f9; padding: 20px; border-radius: 5px; margin-top: 30px; }
        .review-form input[type="text"], .review-form select, .review-form textarea { width: 100%; padding: 8px; margin-top: 5px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .review-form button { background: #28a745; color: white; border: none; padding: 10px 15px; cursor: pointer; border-radius: 4px; font-weight: bold; }
        .review-form button:hover { background: #218838; }
        .review { background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin-bottom: 10px; }
        .rating { color: #f39c12; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?= htmlspecialchars($profile['full_name']) ?></h1>
        <h3><?= htmlspecialchars($profile['profession']) ?></h3>
        <p><?= nl2br(htmlspecialchars($profile['bio'])) ?></p>
        <h4>Average Rating: <?= round($rating['avg_rating'] ?? 0, 1) ?>/5 (<?= $rating['total'] ?> reviews) | Rank: #<?= $userRank ?></h4>
        
        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id): ?>
            <a href="dashboard/export_pdf.php" class="download-btn" target="_blank">Download as PDF</a>
        <?php endif; ?>
        <a href="rating.php?user=<?= urlencode($username) ?>" class="download-btn" style="background: #28a745;">Rate This Portfolio</a>
    </div>

    <!-- contact info -->
    <div class="contact-info">
        <strong>Email:</strong> <?= htmlspecialchars($profile['email'] ?? 'N/A') ?> | 
        <strong>Phone:</strong> <?= htmlspecialchars($profile['phone'] ?? 'N/A') ?> <br>
        <strong>Address:</strong> <?= htmlspecialchars($profile['address'] ?? 'N/A') ?> <br>
        <strong>Website:</strong> <?= htmlspecialchars($profile['website'] ?? 'N/A') ?> | 
        <strong>LinkedIn:</strong> <?= htmlspecialchars($profile['linkedin'] ?? 'N/A') ?>
    </div>

    <!-- education list -->
    <?php if($education->rowCount() > 0): ?>
    <h2>Education</h2>
    <?php foreach($education as $edu): ?>
        <div class="item">
            <h3><?= htmlspecialchars($edu['degree']) ?> in <?= htmlspecialchars($edu['field_of_study']) ?></h3>
            <p><strong><?= htmlspecialchars($edu['institution']) ?></strong></p>
            <p class="date"><?= htmlspecialchars($edu['start_date']) ?> to <?= htmlspecialchars($edu['end_date'] ?? 'Present') ?></p>
            <p><?= nl2br(htmlspecialchars($edu['description'] ?? '')) ?></p>
        </div>
    <?php endforeach; endif; ?>

    <!-- skills list -->
    <?php if($skills->rowCount() > 0): ?>
    <h2>Skills (<?= $totalSkills ?>)</h2>
    <ul>
    <?php foreach($skills as $skill): ?>
        <li><strong><?= htmlspecialchars($skill['skill_name']) ?></strong> - <?= htmlspecialchars($skill['proficiency']) ?></li>
    <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <!-- work list -->
    <?php if($work->rowCount() > 0): ?>
    <h2>Work Experience (<?= $totalWork ?>)</h2>
    <?php foreach($work as $job): ?>
        <div class="item">
            <h3><?= htmlspecialchars($job['position']) ?> at <?= htmlspecialchars($job['company']) ?></h3>
            <p class="date"><?= htmlspecialchars($job['start_date']) ?> to <?= htmlspecialchars($job['end_date'] ?? 'Present') ?></p>
            <p><?= nl2br(htmlspecialchars($job['description'] ?? '')) ?></p>
        </div>
    <?php endforeach; endif; ?>

    <!-- achievements -->
    <?php if($achievements->rowCount() > 0): ?>
    <h2>Achievements</h2>
    <ul>
    <?php foreach($achievements as $ach): ?>
        <li><strong><?= htmlspecialchars($ach['title']) ?></strong> (<?= htmlspecialchars($ach['date_achieved']) ?>)<br><?= nl2br(htmlspecialchars($ach['description'])) ?></li>
    <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <!-- blogs -->
    <?php if($blogs->rowCount() > 0): ?>
    <h2>Recent Blogs</h2>
    <?php foreach($blogs as $blog): ?>
        <div class="item">
            <h3><?= htmlspecialchars($blog['title']) ?></h3>
            <p class="date">Published on: <?= date('F j, Y', strtotime($blog['created_at'])) ?></p>
            <p><?= nl2br(htmlspecialchars(substr($blog['content'], 0, 200))) ?>...</p>
        </div>
    <?php endforeach; endif; ?>

    <!-- Review Section -->
    <div class="review-form">
        <h2>Leave a Review</h2>
        <form method="POST" action="submit_review.php">
            <input type="hidden" name="rev_user_id" value="<?= $user_id ?>">
            <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
            
            <label>Name:</label>
            <input type="text" name="reviewer_name" required>
            
            <label>Rating:</label>
            <select name="rating" required>
                <option value="5">5 - Excellent</option>
                <option value="4">4 - Good</option>
                <option value="3">3 - Average</option>
                <option value="2">2 - Poor</option>
                <option value="1">1 - Terrible</option>
            </select>
            
            <label>Comment:</label>
            <textarea name="comment" rows="4" required></textarea>
            
            <button type="submit">Submit Review</button>
        </form>
    </div>

    <h2>Reviews</h2>
    <?php if($reviews->rowCount() > 0): ?>
        <?php foreach ($reviews as $rev): ?>
            <div class="review">
                <p><strong><?= htmlspecialchars($rev['reviewer_name']) ?></strong> rated this <span class="rating"><?= $rev['rating'] ?>/5 Stars</span></p>
                <p><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>
                <p class="date"><?= date('F j, Y', strtotime($rev['created_at'])) ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No reviews yet. Be the first to review!</p>
    <?php endif; ?>
</body>
</html>