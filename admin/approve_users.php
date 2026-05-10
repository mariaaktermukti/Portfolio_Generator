<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? 0;
    $action = $_POST['action'] ?? '';

    if ($user_id && $action) {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE users SET account_status = 'approved' WHERE id = ?");
            $stmt->execute([$user_id]);
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE users SET account_status = 'rejected', is_deleted = 1 WHERE id = ?");
            $stmt->execute([$user_id]);
        }
    }
    header('Location: approve_users.php');
    exit;
}

$stmt = $pdo->query("SELECT id, username, email, created_at FROM users WHERE account_status = 'pending' AND is_deleted = 0");
$pending_users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Users</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div style="max-width: 1000px; margin: 2rem auto; padding: 0 1rem;">
        <p><a href="dashboard.php" style="display: inline-block; margin-bottom: 1rem;"><i class="fas fa-arrow-left"></i> Back to Dashboard</a></p>
        
        <div class="glass-panel">
            <h2><i class="fas fa-user-check"></i> Approve Users</h2>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Review and manage pending user registrations.</p>
            
            <?php if (count($pending_users) > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Registered At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_users as $user): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('M j, Y, g:i a', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-success" style="width: auto; padding: 0.3rem 0.8rem; font-size: 0.8rem;"><i class="fas fa-check"></i> Approve</button>
                                        </form>
                                        <form method="POST" style="display:inline; margin-left: 0.5rem;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="action" value="reject" class="btn btn-danger" style="width: auto; padding: 0.3rem 0.8rem; font-size: 0.8rem;"><i class="fas fa-times"></i> Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem 0;">
                    <i class="fas fa-check-circle" style="font-size: 4rem; color: var(--success); margin-bottom: 1rem;"></i>
                    <p style="font-size: 1.2rem;">All caught up! No pending users.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
