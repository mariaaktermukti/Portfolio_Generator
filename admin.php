<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Admin Dashboard</title></head>
<body>
    <h1>Admin Panel</h1>
    <p>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></p>
    <ul>
        <li><a href="approve_users.php">Manage User Approvals</a></li>
        <li><a href="../auth/logout.php">Logout</a></li>
    </ul>
</body>
</html>