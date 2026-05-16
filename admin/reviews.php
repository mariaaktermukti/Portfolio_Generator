<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../auth/login.php');
    exit;
}

// Add status column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE reviews ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
} catch (PDOException $e) {
    // Column already exists
}

// Handle approval/deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $review_id = $_POST['review_id'] ?? 0;

    if ($action === 'approve' && $review_id) {
        $stmt = $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?");
        $stmt->execute([$review_id]);
        $_SESSION['success'] = "Review approved successfully!";
    } elseif ($action === 'delete' && $review_id) {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        $_SESSION['success'] = "Review deleted successfully!";
    } elseif ($action === 'reject' && $review_id) {
        $stmt = $pdo->prepare("UPDATE reviews SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$review_id]);
        $_SESSION['success'] = "Review rejected!";
    }

    header('Location: reviews.php');
    exit;
}

// Fetch all reviews with user info
$stmt = $pdo->prepare("
    SELECT r.*, u.username 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    ORDER BY r.created_at DESC
");
$stmt->execute();
$reviews = $stmt->fetchAll();

// Group by status
$pending = array_filter($reviews, fn($r) => $r['status'] === 'pending');
$approved = array_filter($reviews, fn($r) => $r['status'] === 'approved');
$rejected = array_filter($reviews, fn($r) => $r['status'] === 'rejected');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .review-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .review-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(79, 70, 229, 0.3);
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-pending {
            background: rgba(251, 146, 60, 0.2);
            color: #f97316;
        }

        .status-approved {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .rating-stars {
            color: #fbbf24;
            font-size: 1.2rem;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            margin-right: 0.5rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-approve {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .btn-approve:hover {
            background: rgba(34, 197, 94, 0.3);
        }

        .btn-reject {
            background: rgba(251, 146, 60, 0.2);
            color: #f97316;
            border: 1px solid rgba(251, 146, 60, 0.3);
        }

        .btn-reject:hover {
            background: rgba(251, 146, 60, 0.3);
        }

        .btn-delete {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.3);
        }

        .tab-container {
            margin-bottom: 2rem;
        }

        .tab-buttons {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .tab-btn {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            color: var(--text-muted);
            font-weight: 600;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .tab-btn:hover {
            color: var(--accent);
        }

        .tab-btn.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .stat-badge {
            display: inline-block;
            background: rgba(79, 70, 229, 0.15);
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            margin-left: 0.5rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div style="max-width: 1200px; margin: 2rem auto; padding: 0 1rem;">
        <!-- Header -->
        <div class="glass-panel" style="margin-bottom: 2rem; padding: 2rem; border-radius: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1><i class="fas fa-comments"></i> Review Management</h1>
                    <p style="color: var(--text-muted); margin-top: 0.5rem;">Moderate and manage portfolio reviews</p>
                </div>
                <a href="dashboard.php" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: rgba(59, 130, 246, 0.2); color: var(--accent); text-decoration: none; border-radius: 8px; transition: all 0.3s; border: 1px solid rgba(59, 130, 246, 0.3);" onmouseover="this.style.background='rgba(59, 130, 246, 0.3)'; this.style.transform='translateY(-2px)';" onmouseout="this.style.background='rgba(59, 130, 246, 0.2)'; this.style.transform='translateY(0)';">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($_SESSION['success'])): ?>
            <div style="background: rgba(34, 197, 94, 0.2); border-left: 4px solid #22c55e; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem;">
                <i class="fas fa-check-circle" style="font-size: 1.5rem; color: #22c55e;"></i>
                <span style="color: #22c55e; font-weight: 500;"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div class="tab-container">
            <div class="tab-buttons">
                <button class="tab-btn active" onclick="switchTab('pending')" style="border-bottom-color: var(--accent);">
                    <i class="fas fa-hourglass-half"></i> Pending
                    <span class="stat-badge"><?php echo count($pending); ?></span>
                </button>
                <button class="tab-btn" onclick="switchTab('approved')">
                    <i class="fas fa-check-circle"></i> Approved
                    <span class="stat-badge"><?php echo count($approved); ?></span>
                </button>
                <button class="tab-btn" onclick="switchTab('rejected')">
                    <i class="fas fa-times-circle"></i> Rejected
                    <span class="stat-badge"><?php echo count($rejected); ?></span>
                </button>
            </div>

            <!-- Pending Reviews Tab -->
            <div id="pending" class="tab-content active">
                <?php if (empty($pending)): ?>
                    <div style="text-align: center; padding: 3rem; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px dashed rgba(255, 255, 255, 0.1);">
                        <i class="fas fa-inbox" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                        <p style="color: var(--text-muted); font-size: 1.1rem;">No pending reviews</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($pending as $review): ?>
                        <div class="review-card">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <div>
                                    <h3 style="margin: 0 0 0.5rem 0; color: #fff;">
                                        <?php echo htmlspecialchars($review['visitor_name']); ?>
                                        <span style="color: var(--text-muted); font-weight: 400; font-size: 0.9rem;">on <?php echo htmlspecialchars($review['username']); ?>'s portfolio</span>
                                    </h3>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <span class="rating-stars">
                                            <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                                        </span>
                                        <span class="status-badge status-pending">
                                            <i class="fas fa-hourglass-half"></i> Pending Approval
                                        </span>
                                    </div>
                                </div>
                                <span style="color: var(--text-muted); font-size: 0.9rem;">
                                    <?php echo date('M j, Y H:i', strtotime($review['created_at'])); ?>
                                </span>
                            </div>

                            <p style="margin: 1rem 0; color: var(--text-muted); line-height: 1.6; font-style: italic;">
                                "<?php echo htmlspecialchars($review['comment']); ?>"
                            </p>

                            <div style="margin-top: 1.5rem;">
                                <form method="POST" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    
                                    <button type="submit" name="action" value="approve" class="action-btn btn-approve">
                                        <i class="fas fa-check"></i> Approve & Show
                                    </button>
                                    <button type="submit" name="action" value="reject" class="action-btn btn-reject">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                    <button type="submit" name="action" value="delete" class="action-btn btn-delete" onclick="return confirm('Delete this review permanently?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Approved Reviews Tab -->
            <div id="approved" class="tab-content">
                <?php if (empty($approved)): ?>
                    <div style="text-align: center; padding: 3rem; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px dashed rgba(255, 255, 255, 0.1);">
                        <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                        <p style="color: var(--text-muted); font-size: 1.1rem;">No approved reviews yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($approved as $review): ?>
                        <div class="review-card">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <div>
                                    <h3 style="margin: 0 0 0.5rem 0; color: #fff;">
                                        <?php echo htmlspecialchars($review['visitor_name']); ?>
                                        <span style="color: var(--text-muted); font-weight: 400; font-size: 0.9rem;">on <?php echo htmlspecialchars($review['username']); ?>'s portfolio</span>
                                    </h3>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <span class="rating-stars">
                                            <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                                        </span>
                                        <span class="status-badge status-approved">
                                            <i class="fas fa-check-circle"></i> Showing on Portfolio
                                        </span>
                                    </div>
                                </div>
                                <span style="color: var(--text-muted); font-size: 0.9rem;">
                                    <?php echo date('M j, Y H:i', strtotime($review['created_at'])); ?>
                                </span>
                            </div>

                            <p style="margin: 1rem 0; color: var(--text-muted); line-height: 1.6; font-style: italic;">
                                "<?php echo htmlspecialchars($review['comment']); ?>"
                            </p>

                            <div style="margin-top: 1.5rem;">
                                <form method="POST">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <button type="submit" name="action" value="reject" class="action-btn btn-reject">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                    <button type="submit" name="action" value="delete" class="action-btn btn-delete" onclick="return confirm('Delete this review permanently?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Rejected Reviews Tab -->
            <div id="rejected" class="tab-content">
                <?php if (empty($rejected)): ?>
                    <div style="text-align: center; padding: 3rem; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px dashed rgba(255, 255, 255, 0.1);">
                        <i class="fas fa-times-circle" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                        <p style="color: var(--text-muted); font-size: 1.1rem;">No rejected reviews</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($rejected as $review): ?>
                        <div class="review-card">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <div>
                                    <h3 style="margin: 0 0 0.5rem 0; color: #fff;">
                                        <?php echo htmlspecialchars($review['visitor_name']); ?>
                                        <span style="color: var(--text-muted); font-weight: 400; font-size: 0.9rem;">on <?php echo htmlspecialchars($review['username']); ?>'s portfolio</span>
                                    </h3>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <span class="rating-stars">
                                            <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                                        </span>
                                        <span class="status-badge status-rejected">
                                            <i class="fas fa-times-circle"></i> Rejected
                                        </span>
                                    </div>
                                </div>
                                <span style="color: var(--text-muted); font-size: 0.9rem;">
                                    <?php echo date('M j, Y H:i', strtotime($review['created_at'])); ?>
                                </span>
                            </div>

                            <p style="margin: 1rem 0; color: var(--text-muted); line-height: 1.6; font-style: italic;">
                                "<?php echo htmlspecialchars($review['comment']); ?>"
                            </p>

                            <div style="margin-top: 1.5rem;">
                                <form method="POST">
                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                    <button type="submit" name="action" value="approve" class="action-btn btn-approve">
                                        <i class="fas fa-check"></i> Approve & Show
                                    </button>
                                    <button type="submit" name="action" value="delete" class="action-btn btn-delete" onclick="return confirm('Delete this review permanently?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tabs
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Remove active state from all buttons
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            
            // Add active state to clicked button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
