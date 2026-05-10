<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/config/db.php';

$username = $_GET['user'] ?? '';
if (!$username) {
    die("<h1 style='color: red;'>Error:</h1> No user specified. Use: <code>rating.php?user=USERNAME</code>");
}

try {
    // Fetch user profile
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, a.full_name, a.profession
        FROM users u
        LEFT JOIN about a ON u.id = a.user_id AND a.is_deleted = 0
        WHERE u.username = :uname AND u.is_deleted = 0
    ");
    $stmt->execute([':uname' => $username]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        die("<h1 style='color: red;'>Error:</h1> User portfolio not found. Check the username: <strong>" . htmlspecialchars($username) . "</strong>");
    }
    $user_id = $profile['id'];

    // Fetch all portfolio ratings
    $stmt = $pdo->prepare("
        SELECT * FROM portfolio_ratings 
        WHERE portfolio_user_id = :uid AND is_deleted = 0 
        ORDER BY created_at DESC
    ");
    $stmt->execute([':uid' => $user_id]);
    $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate average rating
    $stmt = $pdo->prepare("
        SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings 
        FROM portfolio_ratings 
        WHERE portfolio_user_id = :uid AND is_deleted = 0
    ");
    $stmt->execute([':uid' => $user_id]);
    $ratingStats = $stmt->fetch(PDO::FETCH_ASSOC);
    $avgRating = round($ratingStats['avg_rating'] ?? 0, 1);
    $totalRatings = $ratingStats['total_ratings'] ?? 0;

    // Handle form submission
    $successMessage = '';
    $errorMessage = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raterName = trim($_POST['rater_name'] ?? '');
        $raterEmail = trim($_POST['rater_email'] ?? '');
        $rating = intval($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        // Validation
        if (empty($raterName)) {
            $errorMessage = "Name is required.";
        } elseif (empty($raterEmail) || !filter_var($raterEmail, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = "Valid email is required.";
        } elseif ($rating < 1 || $rating > 5) {
            $errorMessage = "Rating must be between 1 and 5.";
        } elseif (empty($comment)) {
            $errorMessage = "Comment is required.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO portfolio_ratings (portfolio_user_id, rater_name, rater_email, rating, comment, created_at)
                    VALUES (:uid, :name, :email, :rating, :comment, NOW())
                ");
                $stmt->execute([
                    ':uid' => $user_id,
                    ':name' => $raterName,
                    ':email' => $raterEmail,
                    ':rating' => $rating,
                    ':comment' => $comment
                ]);
                $successMessage = "Thank you! Your rating has been submitted successfully.";
                
                // Refresh the page to show new rating
                header("Refresh: 2; url=rating.php?user=" . urlencode($username));
            } catch (PDOException $e) {
                error_log("Rating submission error: " . $e->getMessage());
                $errorMessage = "Error submitting rating. Please try again.";
            }
        }
    }
} catch (PDOException $e) {
    error_log("Database error in rating.php: " . $e->getMessage());
    die("<h1 style='color: red;'>Database Error:</h1> <p>" . htmlspecialchars($e->getMessage()) . "</p><p>Make sure you have imported the updated <code>portfolio.sql</code> file with the <code>portfolio_ratings</code> table.</p>");
} catch (Exception $e) {
    error_log("General error in rating.php: " . $e->getMessage());
    die("<h1 style='color: red;'>Error:</h1> <p>" . htmlspecialchars($e->getMessage()) . "</p>");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Portfolio - <?= htmlspecialchars($profile['full_name']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; line-height: 1.6; background: #f5f5f5; color: #333; }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 30px; border-radius: 5px; margin-bottom: 20px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header h1 { color: #007bff; margin-bottom: 5px; }
        .header p { color: #666; font-size: 18px; }
        .back-link { display: inline-block; margin-bottom: 20px; }
        .back-link a { color: #007bff; text-decoration: none; font-weight: bold; }
        .back-link a:hover { text-decoration: underline; }
        .rating-stats { background: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-box { display: inline-block; margin-right: 40px; text-align: center; }
        .stat-value { font-size: 32px; color: #007bff; font-weight: bold; }
        .stat-label { color: #666; font-size: 14px; }
        .stars { color: #f39c12; font-size: 24px; letter-spacing: 5px; }
        .message { padding: 15px; border-radius: 5px; margin-bottom: 20px; font-weight: bold; }
        .success-message { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-message { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .rating-form { background: white; padding: 30px; border-radius: 5px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group select,
        .form-group textarea { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            font-size: 14px;
            font-family: Arial, sans-serif;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus { 
            outline: none; 
            border-color: #007bff; 
            box-shadow: 0 0 5px rgba(0,123,255,0.3);
        }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .rating-options { display: flex; gap: 10px; }
        .rating-option { 
            flex: 1; 
            text-align: center; 
            padding: 15px; 
            border: 2px solid #ddd; 
            border-radius: 4px; 
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .rating-option input { display: none; }
        .rating-option input:checked + label { 
            font-weight: bold; 
            color: #f39c12;
        }
        .rating-option:has(input:checked) { 
            border-color: #f39c12; 
            background: #fffbf0;
        }
        .rating-option label { cursor: pointer; font-size: 24px; }
        .submit-btn { background: #007bff; color: white; padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 16px; }
        .submit-btn:hover { background: #0056b3; }
        .ratings-list { margin-top: 40px; }
        .ratings-list h2 { color: #007bff; margin-bottom: 20px; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .rating-card { background: white; padding: 20px; border-radius: 5px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-left: 5px solid #f39c12; }
        .rating-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .rating-name { font-weight: bold; color: #333; }
        .rating-stars { color: #f39c12; font-size: 18px; }
        .rating-date { color: #999; font-size: 12px; }
        .rating-comment { color: #555; margin-top: 10px; line-height: 1.5; }
        .no-ratings { background: white; padding: 20px; text-align: center; color: #999; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-link">
            <a href="portfolio.php?user=<?= urlencode($username) ?>">← Back to Portfolio</a>
        </div>

        <div class="header">
            <h1>Rate <?= htmlspecialchars($profile['full_name']) ?>'s Portfolio</h1>
            <p><?= htmlspecialchars($profile['profession'] ?? 'Portfolio') ?></p>
        </div>

        <!-- Rating Statistics -->
        <div class="rating-stats">
            <div class="stat-box">
                <div class="stat-value"><?= $avgRating ?></div>
                <div class="stars"><?= str_repeat('★', intval($avgRating)) . str_repeat('☆', 5 - intval($avgRating)) ?></div>
                <div class="stat-label">Average Rating</div>
            </div>
            <div class="stat-box">
                <div class="stat-value"><?= $totalRatings ?></div>
                <div class="stat-label">Total Ratings</div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($successMessage): ?>
            <div class="message success-message"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="message error-message"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>

        <!-- Rating Form -->
        <div class="rating-form">
            <h2 style="color: #007bff; margin-bottom: 20px;">Share Your Feedback</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="rater_name">Your Name *</label>
                    <input type="text" id="rater_name" name="rater_name" required>
                </div>

                <div class="form-group">
                    <label for="rater_email">Your Email *</label>
                    <input type="email" id="rater_email" name="rater_email" required>
                </div>

                <div class="form-group">
                    <label>Rate This Portfolio *</label>
                    <div class="rating-options">
                        <div class="rating-option">
                            <input type="radio" id="rating1" name="rating" value="1" required>
                            <label for="rating1">★</label>
                        </div>
                        <div class="rating-option">
                            <input type="radio" id="rating2" name="rating" value="2">
                            <label for="rating2">★★</label>
                        </div>
                        <div class="rating-option">
                            <input type="radio" id="rating3" name="rating" value="3">
                            <label for="rating3">★★★</label>
                        </div>
                        <div class="rating-option">
                            <input type="radio" id="rating4" name="rating" value="4">
                            <label for="rating4">★★★★</label>
                        </div>
                        <div class="rating-option">
                            <input type="radio" id="rating5" name="rating" value="5">
                            <label for="rating5">★★★★★</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="comment">Your Comment *</label>
                    <textarea id="comment" name="comment" placeholder="Share your thoughts about this portfolio..." required></textarea>
                </div>

                <button type="submit" class="submit-btn">Submit Rating</button>
            </form>
        </div>

        <!-- All Ratings -->
        <div class="ratings-list">
            <h2>All Ratings (<?= $totalRatings ?>)</h2>
            
            <?php if (empty($ratings)): ?>
                <div class="no-ratings">
                    <p>No ratings yet. Be the first to rate this portfolio!</p>
                </div>
            <?php else: ?>
                <?php foreach ($ratings as $rate): ?>
                    <div class="rating-card">
                        <div class="rating-header">
                            <span class="rating-name"><?= htmlspecialchars($rate['rater_name']) ?></span>
                            <span class="rating-stars"><?= str_repeat('★', intval($rate['rating'])) . str_repeat('☆', 5 - intval($rate['rating'])) ?></span>
                        </div>
                        <div class="rating-date"><?= date('F j, Y', strtotime($rate['created_at'])) ?></div>
                        <div class="rating-comment"><?= nl2br(htmlspecialchars($rate['comment'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
