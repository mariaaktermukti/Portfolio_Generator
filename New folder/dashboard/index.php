<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>
<?php include 'inc/head.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<main class="main-content">
    <div class="glass-panel">
        <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
        <p style="color: var(--text-muted); font-size: 1.1rem;">Manage your portfolio data using the sidebar menu. Ensure all your details are up-to-date to impress visitors.</p>
        
        <div class="card-grid" style="margin-top: 2rem;">
            <div class="card">
                <h3><i class="fas fa-eye" style="color: var(--accent);"></i> Portfolio Views</h3>
                <?php
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM portfolio_views WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $views = $stmt->fetchColumn();
                ?>
                <p style="font-size: 2rem; font-weight: bold; margin-top: 1rem;"><?php echo $views; ?></p>
            </div>
            <div class="card">
                <h3><i class="fas fa-star" style="color: #f59e0b;"></i> Average Rating</h3>
                <?php
                $stmt = $pdo->prepare("SELECT AVG(rating) FROM reviews WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $rating = round($stmt->fetchColumn(), 1) ?: 'N/A';
                ?>
                <p style="font-size: 2rem; font-weight: bold; margin-top: 1rem;"><?php echo $rating; ?></p>
            </div>
            <div class="card">
                <h3><i class="fas fa-comment" style="color: var(--success);"></i> Total Reviews</h3>
                <?php
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $reviews = $stmt->fetchColumn();
                ?>
                <p style="font-size: 2rem; font-weight: bold; margin-top: 1rem;"><?php echo $reviews; ?></p>
            </div>
            <div class="card" style="border: 2px solid rgba(251, 146, 60, 0.3); background: rgba(251, 146, 60, 0.05);">
                <h3><i class="fas fa-hourglass-half" style="color: #f97316;"></i> Pending Reviews</h3>
                <?php
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ? AND status = 'pending'");
                $stmt->execute([$user_id]);
                $pending = $stmt->fetchColumn();
                ?>
                <p style="font-size: 2rem; font-weight: bold; margin-top: 1rem; color: #f97316;"><?php echo $pending; ?></p>
                <?php if ($pending > 0): ?>
                    <a href="reviews.php" style="display: inline-block; margin-top: 1rem; padding: 0.5rem 1rem; background: rgba(251, 146, 60, 0.2); color: #f97316; text-decoration: none; border-radius: 6px; font-weight: 600; transition: all 0.3s;" onmouseover="this.style.background='rgba(251, 146, 60, 0.3)'; this.style.transform='translateY(-2px)';" onmouseout="this.style.background='rgba(251, 146, 60, 0.2)'; this.style.transform='translateY(0)';">
                        Approve Reviews →
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php include 'inc/foot.php'; ?>