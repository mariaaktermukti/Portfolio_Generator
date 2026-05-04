<?php
session_start();
require_once '../config/config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
$uid = $_SESSION['user_id'];

// ADD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title']) && !isset($_POST['update_id'])) {
    $title = trim($_POST['title']);
    $desc = trim($_POST['description'] ?? '');
    $date = $_POST['date_achieved'] ?: null;
    $pdo->prepare("INSERT INTO achievements (user_id, title, description, date_achieved) VALUES (?,?,?,?)")->execute([$uid, $title, $desc, $date]);
    header('Location: achievements.php');
    exit;
}

// UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id = $_POST['update_id'];
    $title = trim($_POST['title']);
    $desc = trim($_POST['description'] ?? '');
    $date = $_POST['date_achieved'] ?: null;
    $pdo->prepare("UPDATE achievements SET title=?, description=?, date_achieved=? WHERE id=? AND user_id=?")->execute([$title, $desc, $date, $id, $uid]);
    header('Location: achievements.php');
    exit;
}

// SOFT DELETE
if (isset($_GET['delete'])) {
    $pdo->prepare("UPDATE achievements SET is_deleted=1 WHERE id=? AND user_id=?")->execute([$_GET['delete'], $uid]);
    header('Location: achievements.php');
    exit;
}

$list = $pdo->prepare("SELECT * FROM achievements WHERE user_id=? AND is_deleted=0 ORDER BY date_achieved DESC");
$list->execute([$uid]);
$achievements = $list->fetchAll();

$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM achievements WHERE id=? AND user_id=? AND is_deleted=0");
    $stmt->execute([$_GET['edit'], $uid]);
    $editItem = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head><title>Manage Achievements</title></head>
<body>
    <h2>Achievements</h2>
    <form method="POST">
        <?php if ($editItem): ?><input type="hidden" name="update_id" value="<?= $editItem['id'] ?>"><h4>Edit</h4><?php else: ?><h4>Add New</h4><?php endif; ?>
        Title: <input type="text" name="title" value="<?= htmlspecialchars($editItem['title'] ?? '') ?>" required><br><br>
        Description: <textarea name="description"><?= htmlspecialchars($editItem['description'] ?? '') ?></textarea><br><br>
        Date Achieved: <input type="date" name="date_achieved" value="<?= htmlspecialchars($editItem['date_achieved'] ?? '') ?>"><br><br>
        <button type="submit"><?= $editItem ? 'Update' : 'Add' ?></button>
        <?php if ($editItem): ?><a href="achievements.php">Cancel</a><?php endif; ?>
    </form>
    <hr>
    <h3>Your Achievements</h3>
    <?php if (count($achievements)): ?>
        <ul>
            <?php foreach ($achievements as $a): ?>
                <li>
                    <strong><?= htmlspecialchars($a['title']) ?></strong> (<?= htmlspecialchars($a['date_achieved']) ?>)
                    <p><?= nl2br(htmlspecialchars($a['description'])) ?></p>
                    <a href="achievements.php?edit=<?= $a['id'] ?>">Edit</a> |
                    <a href="achievements.php?delete=<?= $a['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?><p>No achievements.</p><?php endif; ?>
    <p><a href="index.php">Back</a></p>
</body>
</html>