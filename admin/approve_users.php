<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../auth/login.php');
    exit;
}

// Add 'paused' to the account_status enum in case it doesn't exist
try {
    $pdo->exec("ALTER TABLE users MODIFY COLUMN account_status ENUM('pending', 'approved', 'rejected', 'paused') DEFAULT 'pending'");
} catch (PDOException $e) {
    // Ignore error if already modified or syntax unsupported;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? 0;
    $action = $_POST['action'] ?? '';

    if ($user_id && $action) {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE users SET account_status = 'approved', is_deleted = 0 WHERE id = ?");
            $stmt->execute([$user_id]);
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE users SET account_status = 'rejected', is_deleted = 1 WHERE id = ?");
            $stmt->execute([$user_id]);
        } elseif ($action === 'pause') {
            $stmt = $pdo->prepare("UPDATE users SET account_status = 'paused' WHERE id = ?");
            $stmt->execute([$user_id]);
        }
    }
    header('Location: approve_users.php');
    exit;
}

// Fetch all non-admin users
$stmt = $pdo->query("SELECT id, username, email, account_status, created_at FROM users WHERE is_admin = 0 ORDER BY created_at DESC");
$users_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div style="max-width: 1000px; margin: 2rem auto; padding: 0 1rem;">
        <p><a href="dashboard.php" style="display: inline-block; margin-bottom: 1rem;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></p>
        
        <div class="glass-panel">
            <h2><i class="fas fa-users-cog"></i> Manage Users</h2>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Review and manage all user accounts on the platform.</p>
            
            <?php if (count($users_list) > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Registered At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users_list as $user): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <?php if ($user['account_status'] === 'approved'): ?>
                                            <span class="badge" style="background: rgba(34,197,94,0.2); color: #22c55e; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.8rem;">Approved</span>
                                        <?php elseif ($user['account_status'] === 'pending'): ?>
                                            <span class="badge" style="background: rgba(234,179,8,0.2); color: #eab308; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.8rem;">Pending</span>
                                        <?php elseif ($user['account_status'] === 'paused'): ?>
                                            <span class="badge" style="background: rgba(249,115,22,0.2); color: #f97316; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.8rem;">Paused</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: rgba(239,68,68,0.2); color: #ef4444; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.8rem;">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if ($user['account_status'] !== 'approved'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-success" style="width: auto; padding: 0.3rem 0.6rem; font-size: 0.8rem;"><i class="fas fa-check"></i> Approve</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($user['account_status'] === 'approved'): ?>
                                            <form method="POST" style="display:inline; margin-left: 0.3rem;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="action" value="pause" class="btn btn-warning" style="width: auto; padding: 0.3rem 0.6rem; font-size: 0.8rem; background: var(--warning);"><i class="fas fa-pause"></i> Pause</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($user['account_status'] !== 'rejected'): ?>
                                            <form method="POST" style="display:inline; margin-left: 0.3rem;">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" name="action" value="reject" class="btn btn-danger" style="width: auto; padding: 0.3rem 0.6rem; font-size: 0.8rem;"><i class="fas fa-times"></i> Reject</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem 0;">
                    <i class="fas fa-users" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                    <p style="font-size: 1.2rem;">No user accounts found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
