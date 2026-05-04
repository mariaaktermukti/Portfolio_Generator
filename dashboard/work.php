<?php
session_start();
require_once '../config/config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
$uid = $_SESSION['user_id'];

// ADD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['company']) && !isset($_POST['update_id'])) {
    $company = trim($_POST['company']);
    $position = trim($_POST['position']);
    $start = $_POST['start_date'] ?: null;
    $end = $_POST['end_date'] ?: null;
    $desc = trim($_POST['description'] ?? '');
    $pdo->prepare("INSERT INTO work_experience (user_id, company, position, start_date, end_date, description) VALUES (?,?,?,?,?,?)")
        ->execute([$uid, $company, $position, $start, $end, $desc]);
    header('Location: work.php');
    exit;
}

// UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id = $_POST['update_id'];
    $company = trim($_POST['company']);
    $position = trim($_POST['position']);
    $start = $_POST['start_date'] ?: null;
    $end = $_POST['end_date'] ?: null;
    $desc = trim($_POST['description'] ?? '');
    $pdo->prepare("UPDATE work_experience SET company=?, position=?, start_date=?, end_date=?, description=? WHERE id=? AND user_id=?")
        ->execute([$company, $position, $start, $end, $desc, $id, $uid]);
    header('Location: work.php');
    exit;
}

// SOFT DELETE
if (isset($_GET['delete'])) {
    $pdo->prepare("UPDATE work_experience SET is_deleted=1 WHERE id=? AND user_id=?")->execute([$_GET['delete'], $uid]);
    header('Location: work.php');
    exit;
}

$list = $pdo->prepare("SELECT * FROM work_experience WHERE user_id=? AND is_deleted=0 ORDER BY start_date DESC");
$list->execute([$uid]);
$works = $list->fetchAll();

$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM work_experience WHERE id=? AND user_id=? AND is_deleted=0");
    $stmt->execute([$_GET['edit'], $uid]);
    $editItem = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head><title>Manage Work Experience</title></head>
<body>
    <h2>Work Experience</h2>
    <form method="POST">
        <?php if ($editItem): ?>
            <input type="hidden" name="update_id" value="<?= $editItem['id'] ?>"><h4>Edit Record</h4>
        <?php else: ?><h4>Add New Job</h4><?php endif; ?>
        Company: <input type="text" name="company" value="<?= htmlspecialchars($editItem['company'] ?? '') ?>" required><br><br>
        Position: <input type="text" name="position" value="<?= htmlspecialchars($editItem['position'] ?? '') ?>" required><br><br>
        Start Date: <input type="date" name="start_date" value="<?= htmlspecialchars($editItem['start_date'] ?? '') ?>"><br><br>
        End Date: <input type="date" name="end_date" value="<?= htmlspecialchars($editItem['end_date'] ?? '') ?>"><br><br>
        Description: <textarea name="description"><?= htmlspecialchars($editItem['description'] ?? '') ?></textarea><br><br>
        <button type="submit"><?= $editItem ? 'Update' : 'Add' ?></button>
        <?php if ($editItem): ?><a href="work.php">Cancel</a><?php endif; ?>
    </form>
    <hr>
    <h3>Your Work History</h3>
    <?php if (count($works)): ?>
        <table border="1" cellpadding="5">
            <tr><th>Company</th><th>Position</th><th>Start</th><th>End</th><th>Description</th><th>Actions</th></tr>
            <?php foreach ($works as $w): ?>
                <tr>
                    <td><?= htmlspecialchars($w['company']) ?></td>
                    <td><?= htmlspecialchars($w['position']) ?></td>
                    <td><?= htmlspecialchars($w['start_date']) ?></td>
                    <td><?= htmlspecialchars($w['end_date']) ?></td>
                    <td><?= nl2br(htmlspecialchars($w['description'])) ?></td>
                    <td>
                        <a href="work.php?edit=<?= $w['id'] ?>">Edit</a> |
                        <a href="work.php?delete=<?= $w['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?><p>No work experience added.</p><?php endif; ?>
    <p><a href="index.php">Back</a></p>
</body>
</html>