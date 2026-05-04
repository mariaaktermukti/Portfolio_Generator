<?php
require_once 'config/config/db.php';

$username = $_GET['user'] ?? '';
if (!$username) die("No user specified.");

$stmt = $pdo->prepare("
    SELECT u.id, u.username, a.full_name, a.profession, a.bio, a.profile_image,
           c.email, c.phone, c.address, c.website, c.linkedin, c.github
    FROM users u
    JOIN about a ON u.id = a.user_id AND a.is_deleted = 0
    JOIN contact c ON u.id = c.user_id AND c.is_deleted = 0
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
<head><title><?= htmlspecialchars($profile['full_name']) ?> – Portfolio</title></head>
<body>
    <h1><?= htmlspecialchars($profile['full_name']) ?></h1>
    <h3><?= htmlspecialchars($profile['profession']) ?></h3>
    <p><?= nl2br(htmlspecialchars($profile['bio'])) ?></p>
    <!-- contact info -->
    <h4>Average Rating: <?= round($rating['avg_rating'] ?? 0, 1) ?> (<?= $rating['total'] ?> reviews) | Rank: #<?= $userRank ?></h4>
    <!-- education list -->
    <!-- skills list -->
    <!-- work list -->
    <!-- achievements -->
    <!-- blogs -->
    <h4>Leave a Review</h4>
    <form method="POST" action="submit_review.php">
        <input type="hidden" name="rev_user_id" value="<?= $user_id ?>">
        <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>">
        Name: <input type="text" name="reviewer_name" required><br>
        Rating: <select name="rating">
            <option>1</option><option>2</option><option>3</option><option>4</option><option>5</option>
        </select><br>
        Comment: <textarea name="comment"></textarea><br>
        <button>Submit Review</button>
    </form>
    <h4>Reviews</h4>
    <?php foreach ($reviews as $rev): ?>
        <p><strong><?= htmlspecialchars($rev['reviewer_name']) ?></strong> rated <?= $rev['rating'] ?>/5<br>
        <?= nl2br(htmlspecialchars($rev['comment'])) ?></p>
    <?php endforeach; ?>
</body>
</html>