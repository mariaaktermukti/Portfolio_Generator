<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html>
<head><title>Dashboard</title></head>
<body>
    <h1>Welcome, <?= htmlspecialchars($username) ?></h1>
    <p>Manage your portfolio sections:</p>
    <ul>
        <li><a href="about.php">About</a></li>
        <li><a href="contact.php">Contact</a></li>
        <li><a href="education.php">Education</a></li>
        <li><a href="skills.php">Skills</a></li>
        <li><a href="work.php">Work Experience</a></li>
        <li><a href="achievements.php">Achievements</a></li>
        <li><a href="blogs.php">Blogs</a></li>
    </ul>
    <p><a href="../auth/logout.php">Logout</a></p>
</body>
</html>