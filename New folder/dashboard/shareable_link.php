<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$username = $_SESSION['username'];
$portfolio_url = "http://" . $_SERVER['HTTP_HOST'] . "/Portfolio_generator/public/portfolio.php?user=" . urlencode($username);
?>
<?php include 'inc/head.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<main class="main-content">
    <div class="glass-panel" style="text-align: center;">
        <h2>Share Your Portfolio</h2>
        <p style="color: var(--text-muted); margin-bottom: 2rem;">Share this link to showcase your portfolio to potential employers, clients, or anyone interested in your work!</p>
        
        <div style="margin-top: 2rem; padding: 2rem; background: rgba(100,200,255,0.1); border: 1px solid rgba(100,200,255,0.3); border-radius: 12px;">
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: var(--text-muted); font-size: 0.9rem;">Your Shareable Link</label>
                <div style="display: flex; align-items: center; justify-content: center; gap: 1rem; flex-wrap: wrap; background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 8px;">
                    <input type="text" id="portfolioUrl" value="<?php echo htmlspecialchars($portfolio_url); ?>" readonly style="flex: 1; min-width: 250px; padding: 0.8rem; background: rgba(0,0,0,0.2); border: 1px solid var(--border); border-radius: 6px; color: #fff; font-family: monospace; font-size: 0.95rem; cursor: text;">
                    <button type="button" class="btn" id="copyLinkBtn" style="width: auto; padding: 0.8rem 1.5rem; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-copy"></i> Copy Link
                    </button>
                </div>
                <div id="copyTooltip" style="margin-top: 1rem; color: var(--success); font-size: 0.95rem; display: none; text-align: center;">
                    <i class="fas fa-check-circle"></i> Link copied to clipboard!
                </div>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="<?php echo htmlspecialchars($portfolio_url); ?>" target="_blank" class="btn" style="width: auto; padding: 0.7rem 1.5rem; font-size: 1rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-external-link-alt"></i> Preview Portfolio
                </a>
            </div>
        </div>

        <div style="margin-top: 2rem; padding: 1.5rem; background: rgba(255,255,255,0.05); border-radius: 8px;">
            <h4 style="margin-bottom: 1rem; color: var(--accent);">Share on Social Media</h4>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($portfolio_url); ?>" target="_blank" class="btn" style="width: auto; padding: 0.5rem 1rem; font-size: 0.9rem; background: rgba(0,119,182,0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fab fa-linkedin"></i> LinkedIn
                </a>
                <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode($portfolio_url); ?>&text=Check%20out%20my%20portfolio!" target="_blank" class="btn" style="width: auto; padding: 0.5rem 1rem; font-size: 0.9rem; background: rgba(29,161,242,0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fab fa-twitter"></i> Twitter
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($portfolio_url); ?>" target="_blank" class="btn" style="width: auto; padding: 0.5rem 1rem; font-size: 0.9rem; background: rgba(59,89,152,0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fab fa-facebook"></i> Facebook
                </a>
                <a href="mailto:?subject=Check%20out%20my%20portfolio&body=<?php echo urlencode("Visit my portfolio: " . $portfolio_url); ?>" class="btn" style="width: auto; padding: 0.5rem 1rem; font-size: 0.9rem; background: rgba(255,100,100,0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-envelope"></i> Email
                </a>
            </div>
        </div>
    </div>
</main>

<?php include 'inc/foot.php'; ?>

<script>
// Copy link to clipboard
document.getElementById('copyLinkBtn').addEventListener('click', function() {
    const urlInput = document.getElementById('portfolioUrl');
    urlInput.select();
    
    navigator.clipboard.writeText(urlInput.value).then(function() {
        // Show tooltip
        const tooltip = document.getElementById('copyTooltip');
        tooltip.style.display = 'block';
        
        // Change button appearance
        const originalHTML = this.innerHTML;
        this.innerHTML = '<i class="fas fa-check"></i> Copied!';
        this.style.background = 'var(--success)';
        
        // Revert after 3 seconds
        setTimeout(() => {
            this.innerHTML = originalHTML;
            this.style.background = 'var(--accent)';
            tooltip.style.display = 'none';
        }, 3000);
    }.bind(this)).catch(function(err) {
        alert('Failed to copy link');
    });
});

// Allow selecting the URL input
document.getElementById('portfolioUrl').addEventListener('click', function() {
    this.select();
});
</script>
