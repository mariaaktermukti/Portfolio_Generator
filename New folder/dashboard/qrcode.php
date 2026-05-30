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
        
        <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?php echo htmlspecialchars($portfolio_url); ?>" target="_blank" class="btn" style="width: auto; padding: 0.7rem 1.5rem; font-size: 1rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-eye"></i> Preview Portfolio
            </a>
            <button type="button" class="btn" id="downloadQR" style="width: auto; padding: 0.7rem 1.5rem; font-size: 1rem;">
                <i class="fas fa-download"></i> Download QR Code
            </button>
        </div>

        <div style="margin-top: 2rem; padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 8px; word-break: break-all;">
            <strong>Your Portfolio URL:</strong><br>
            <div style="display: flex; align-items: center; justify-content: center; gap: 1rem; margin-top: 0.75rem; flex-wrap: wrap;">
                <span style="font-size: 1rem; color: var(--text-muted);"><?php echo htmlspecialchars($portfolio_url); ?></span>
                <button type="button" class="btn" id="copyUrlBtn" style="width: auto; padding: 0.5rem 1rem; font-size: 0.9rem;" onclick="copyToClipboard('<?php echo htmlspecialchars($portfolio_url); ?>', this)">
                    <i class="fas fa-copy"></i> Copy
                </button>
            </div>
            <div id="copyTooltip" style="margin-top: 0.5rem; color: var(--success); font-size: 0.9rem; display: none;">
                <i class="fas fa-check-circle"></i> URL copied to clipboard!
            </div>
        </div>
    </div>
</main>

<?php include 'inc/foot.php'; ?>

<script>
function copyToClipboard(text, button) {
    // Copy to clipboard
    navigator.clipboard.writeText(text).then(function() {
        // Show tooltip
        const tooltip = document.getElementById('copyTooltip');
        tooltip.style.display = 'block';
        
        // Change button appearance
        const originalHTML = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check"></i> Copied!';
        button.style.background = 'var(--success)';
        
        // Revert after 3 seconds
        setTimeout(function() {
            button.innerHTML = originalHTML;
            button.style.background = 'var(--accent)';
            tooltip.style.display = 'none';
        }, 3000);
    }).catch(function(err) {
        alert('Failed to copy URL');
    });
}

// Download QR Code
document.getElementById('downloadQR').addEventListener('click', function() {
    const qrImage = document.querySelector('img[alt="Portfolio QR Code"]');
    const link = document.createElement('a');
    link.href = qrImage.src;
    link.download = 'portfolio-qrcode.png';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
});
</script>