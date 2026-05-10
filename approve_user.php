<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../auth/login.php');
    exit;
}

// Approve or Reject action
if (isset($_GET['action']) && isset($_GET['uid'])) {
    $uid = (int)$_GET['uid'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE users SET account_status = 'approved' WHERE id = ?");
        $stmt->execute([$uid]);
    } elseif ($action === 'reject') {
        // সফট ডিলিট (রিজেক্ট করলে একাউন্ট ব্লক করা)
        $stmt = $pdo->prepare("UPDATE users SET account_status = 'rejected', is_deleted = 1 WHERE id = ?");
        $stmt->execute([$uid]);
    }
    header('Location: approve_users.php');
    exit;
}

// Pending users list
$pending = $pdo->query("SELECT id, username, email, created_at FROM users WHERE account_status = 'pending' AND is_deleted = 0 ORDER BY created_at ASC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Approve Users</title></head>
<body>
    <h2>Pending User Approvals</h2>
    <?php if (count($pending) > 0): ?>
        <table border="1" cellpadding="8">
            <tr><th>ID</th><th>Username</th><th>Email</th><th>Registered</th><th>Action</th></tr>
            <?php foreach ($pending as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= $user['created_at'] ?></td>
                    <td>
                        <a href="approve_users.php?action=approve&uid=<?= $user['id'] ?>" onclick="return confirm('Approve this user?')">Approve</a> |
                        <a href="approve_users.php?action=reject&uid=<?= $user['id'] ?>" onclick="return confirm('Reject and block this user?')">Reject</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No pending approvals.</p>
    <?php endif; ?>
    <p><a href="dashboard.php">Back to Admin Dashboard</a></p>
</body>
</html>