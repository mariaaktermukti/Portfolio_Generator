<?php
session_start();
require_once '../config/config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
$uid = $_SESSION['user_id'];

// Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['skill_name']) && !isset($_POST['update_id'])) {
    $skill_name = $_POST['skill_name'];
    $proficiency = $_POST['proficiency'];
    $stmt = $pdo->prepare("INSERT INTO skills (user_id, skill_name, proficiency) VALUES (?, ?, ?)");
    $stmt->execute([$uid, $skill_name, $proficiency]);
    header('Location: skills.php');
    exit;
}

// Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id = $_POST['update_id'];
    $skill_name = $_POST['skill_name'];
    $proficiency = $_POST['proficiency'];
    $stmt = $pdo->prepare("UPDATE skills SET skill_name=?, proficiency=? WHERE id=? AND user_id=?");
    $stmt->execute([$skill_name, $proficiency, $id, $uid]);
    header('Location: skills.php');
    exit;
}

// Soft delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("UPDATE skills SET is_deleted=1 WHERE id=? AND user_id=?")->execute([$id, $uid]);
    header('Location: skills.php');
    exit;
}

$skills = $pdo->prepare("SELECT * FROM skills WHERE user_id=? AND is_deleted=0 ORDER BY proficiency DESC");
$skills->execute([$uid]);
$skillList = $skills->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Manage Skills</title></head>
<body>
    <h2>Skills</h2>
    <form method="POST">
        Skill: <input type="text" name="skill_name" required>
        Proficiency: <select name="proficiency">
            <option>Beginner</option><option>Intermediate</option><option>Advanced</option><option>Expert</option>
        </select>
        <button type="submit">Add</button>
    </form>
    <ul>
    <?php foreach ($skillList as $s): ?>
        <li>
            <?= htmlspecialchars($s['skill_name']) ?> (<?= $s['proficiency'] ?>)
            <a href="skills.php?edit=<?= $s['id'] ?>">Edit</a>
            <a href="skills.php?delete=<?= $s['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
            <?php if (isset($_GET['edit']) && $_GET['edit'] == $s['id']): ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="update_id" value="<?= $s['id'] ?>">
                    <input type="text" name="skill_name" value="<?= htmlspecialchars($s['skill_name']) ?>" required>
                    <select name="proficiency">
                        <option <?= $s['proficiency']=='Beginner'?'selected':'' ?>>Beginner</option>
                        <option <?= $s['proficiency']=='Intermediate'?'selected':'' ?>>Intermediate</option>
                        <option <?= $s['proficiency']=='Advanced'?'selected':'' ?>>Advanced</option>
                        <option <?= $s['proficiency']=='Expert'?'selected':'' ?>>Expert</option>
                    </select>
                    <button type="submit">Update</button>
                </form>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
    </ul>
    <p><a href="index.php">Back</a></p>
</body>
</html>