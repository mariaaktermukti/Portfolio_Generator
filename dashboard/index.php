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
        </div>
    </div>
</main>

<?php include 'inc/foot.php'; ?>