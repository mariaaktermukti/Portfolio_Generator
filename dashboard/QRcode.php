<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$username = $_SESSION['username'];
$portfolio_url = "http://" . $_SERVER['HTTP_HOST'] . "/Portfolio_generator/public/portfolio.php?user=" . urlencode($username);
$qr_api_url = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($portfolio_url) . "&choe=UTF-8";
?>
<?php include 'inc/head.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<main class="main-content">
    <div class="glass-panel" style="text-align: center;">
        <h2>Your Portfolio QR Code</h2>
        <p style="color: var(--text-muted); margin-bottom: 2rem;">Scan this QR code to instantly visit your public portfolio. Share it on your resume or business cards!</p>
        
        <div style="background: white; padding: 20px; display: inline-block; border-radius: 16px; margin-bottom: 2rem; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
            <img src="<?php echo htmlspecialchars($qr_api_url); ?>" alt="Portfolio QR Code" style="display: block;">
        </div>
        
        <div style="margin-top: 1rem; padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 8px; word-break: break-all;">
            <strong>Your Portfolio URL:</strong><br>
            <a href="<?php echo htmlspecialchars($portfolio_url); ?>" target="_blank" style="font-size: 1.1rem;"><?php echo htmlspecialchars($portfolio_url); ?></a>
        </div>
    </div>
</main>

<?php include 'inc/foot.php'; ?>