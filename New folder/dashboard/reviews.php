<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Add status column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE reviews ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
} catch (PDOException $e) {
    // Column already exists
}

$user_id = $_SESSION['user_id'];

// Handle approval/deletion/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $review_id = $_POST['review_id'] ?? 0;

    // Verify review belongs to this user
    $verify_stmt = $pdo->prepare("SELECT id FROM reviews WHERE id = ? AND user_id = ?");
    $verify_stmt->execute([$review_id, $user_id]);
    
    if ($verify_stmt->fetch()) {
        if ($action === 'approve' && $review_id) {
            $stmt = $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?");
            $stmt->execute([$review_id]);
            $_SESSION['success'] = "Review approved! It will now show on your portfolio.";
        } elseif ($action === 'delete' && $review_id) {
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->execute([$review_id]);
            $_SESSION['success'] = "Review deleted successfully!";
        } elseif ($action === 'reject' && $review_id) {
            $stmt = $pdo->prepare("UPDATE reviews SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$review_id]);
            $_SESSION['success'] = "Review rejected!";
        }
    }

    header('Location: reviews.php');
    exit;
}

// Fetch reviews for this user only
$stmt = $pdo->prepare("
    SELECT * FROM reviews 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$reviews = $stmt->fetchAll();

// Group by status
$pending = array_filter($reviews, fn($r) => $r['status'] === 'pending');
$approved = array_filter($reviews, fn($r) => $r['status'] === 'approved');
$rejected = array_filter($reviews, fn($r) => $r['status'] === 'rejected');
?>
<?php include 'inc/head.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<main class="main-content">
    <div class="glass-panel" style="margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2>Manage Reviews</h2>
                <p style="color: var(--text-muted); margin-top: 0.5rem;">Moderate and manage reviews on your portfolio</p>
            </div>
            <a href="index.php" class="btn" style="width: auto;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="msg-success" style="margin-top: 1rem;"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
    </div>

    <!-- Pending Reviews Tab -->
    <div class="glass-panel" style="margin-bottom: 2rem;">
        <h3 style="display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-hourglass-half" style="color: #f97316;"></i> Pending Reviews</span>
            <span style="background: rgba(251, 146, 60, 0.2); color: #f97316; padding: 0.2rem 0.8rem; border-radius: 20px; font-size: 0.9rem;"><?php echo count($pending); ?></span>
        </h3>
        
        <?php if (empty($pending)): ?>
            <p style="color: var(--text-muted); margin-top: 1rem;">No pending reviews</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1.5rem; margin-top: 1.5rem;">
                <?php foreach ($pending as $review): ?>
                    <div class="card">
                        <h4 style="margin-bottom: 0.2rem;"><?php echo htmlspecialchars($review['visitor_name']); ?></h4>
                        <div style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 1rem;">
                            <i class="fas fa-clock"></i> <?php echo date('F j, Y, g:i a', strtotime($review['created_at'])); ?> 
                            <span style="color: #fbbf24; font-size: 1rem; margin-left: 0.5rem;"><?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?></span>
                        </div>
                        <p style="margin-bottom: 1.5rem; font-style: italic;">"<?php echo nl2br(htmlspecialchars($review['comment'])); ?>"</p>
                        <form method="POST" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-success" style="width: auto; padding: 0.5rem 1rem;"><i class="fas fa-check"></i> Show on Portfolio</button>
                            <button type="submit" name="action" value="reject" class="btn" style="width: auto; padding: 0.5rem 1rem; background: rgba(251, 146, 60, 0.2); color: #f97316; border: 1px solid rgba(251, 146, 60, 0.3);"><i class="fas fa-times"></i> Reject</button>
                            <button type="submit" name="action" value="delete" class="btn btn-danger" style="width: auto; padding: 0.5rem 1rem;" onclick="return confirm('Delete this review permanently?');"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Approved Reviews Tab -->
    <div class="glass-panel" style="margin-bottom: 2rem;">
        <h3 style="display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-check-circle" style="color: #22c55e;"></i> Showing on Portfolio</span>
            <span style="background: rgba(34, 197, 94, 0.2); color: #22c55e; padding: 0.2rem 0.8rem; border-radius: 20px; font-size: 0.9rem;"><?php echo count($approved); ?></span>
        </h3>
        
        <?php if (empty($approved)): ?>
            <p style="color: var(--text-muted); margin-top: 1rem;">No reviews showing on your portfolio yet</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1.5rem; margin-top: 1.5rem;">
                <?php foreach ($approved as $review): ?>
                    <div class="card">
                        <h4 style="margin-bottom: 0.2rem;"><?php echo htmlspecialchars($review['visitor_name']); ?></h4>
                        <div style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 1rem;">
                            <i class="fas fa-clock"></i> <?php echo date('F j, Y, g:i a', strtotime($review['created_at'])); ?> 
                            <span style="color: #fbbf24; font-size: 1rem; margin-left: 0.5rem;"><?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?></span>
                        </div>
                        <p style="margin-bottom: 1.5rem; font-style: italic;">"<?php echo nl2br(htmlspecialchars($review['comment'])); ?>"</p>
                        <form method="POST" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                            <button type="submit" name="action" value="reject" class="btn" style="width: auto; padding: 0.5rem 1rem; background: rgba(251, 146, 60, 0.2); color: #f97316; border: 1px solid rgba(251, 146, 60, 0.3);"><i class="fas fa-times"></i> Reject</button>
                            <button type="submit" name="action" value="delete" class="btn btn-danger" style="width: auto; padding: 0.5rem 1rem;" onclick="return confirm('Delete this review permanently?');"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Rejected Reviews Tab -->
    <div class="glass-panel">
        <h3 style="display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-times-circle" style="color: #ef4444;"></i> Rejected Reviews</span>
            <span style="background: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 0.2rem 0.8rem; border-radius: 20px; font-size: 0.9rem;"><?php echo count($rejected); ?></span>
        </h3>
        
        <?php if (empty($rejected)): ?>
            <p style="color: var(--text-muted); margin-top: 1rem;">No rejected reviews</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1.5rem; margin-top: 1.5rem;">
                <?php foreach ($rejected as $review): ?>
                    <div class="card">
                        <h4 style="margin-bottom: 0.2rem;"><?php echo htmlspecialchars($review['visitor_name']); ?></h4>
                        <div style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 1rem;">
                            <i class="fas fa-clock"></i> <?php echo date('F j, Y, g:i a', strtotime($review['created_at'])); ?> 
                            <span style="color: #fbbf24; font-size: 1rem; margin-left: 0.5rem;"><?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?></span>
                        </div>
                        <p style="margin-bottom: 1.5rem; font-style: italic;">"<?php echo nl2br(htmlspecialchars($review['comment'])); ?>"</p>
                        <form method="POST" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-success" style="width: auto; padding: 0.5rem 1rem;"><i class="fas fa-check"></i> Show on Portfolio</button>
                            <button type="submit" name="action" value="delete" class="btn btn-danger" style="width: auto; padding: 0.5rem 1rem;" onclick="return confirm('Delete this review permanently?');"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php include 'inc/foot.php'; ?>
