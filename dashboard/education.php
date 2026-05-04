<?php
session_start();
require_once '../config/config/db.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
$uid = $_SESSION['user_id'];

// ---------- ADD ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['institution']) && !isset($_POST['update_id'])) {
    $institution = trim($_POST['institution']);
    $degree = trim($_POST['degree']);
    $field = trim($_POST['field_of_study'] ?? '');
    $start = $_POST['start_date'] ?: null;
    $end = $_POST['end_date'] ?: null;
    $desc = trim($_POST['description'] ?? '');

    $stmt = $pdo->prepare("INSERT INTO education (user_id, institution, degree, field_of_study, start_date, end_date, description)
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$uid, $institution, $degree, $field, $start, $end, $desc]);
    header('Location: education.php');
    exit;
}

// ---------- UPDATE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id = $_POST['update_id'];
    $institution = trim($_POST['institution']);
    $degree = trim($_POST['degree']);
    $field = trim($_POST['field_of_study'] ?? '');
    $start = $_POST['start_date'] ?: null;
    $end = $_POST['end_date'] ?: null;
    $desc = trim($_POST['description'] ?? '');

    $stmt = $pdo->prepare("UPDATE education SET institution=?, degree=?, field_of_study=?, start_date=?, end_date=?, description=?
                           WHERE id=? AND user_id=?");
    $stmt->execute([$institution, $degree, $field, $start, $end, $desc, $id, $uid]);
    header('Location: education.php');
    exit;
}

// ---------- SOFT DELETE ----------
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("UPDATE education SET is_deleted = 1 WHERE id = ? AND user_id = ?")->execute([$id, $uid]);
    header('Location: education.php');
    exit;
}

// ---------- FETCH ALL ----------
$list = $pdo->prepare("SELECT * FROM education WHERE user_id = ? AND is_deleted = 0 ORDER BY start_date DESC");
$list->execute([$uid]);
$educations = $list->fetchAll();

// ---------- EDIT FORM DATA ----------
$editItem = null;
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM education WHERE id = ? AND user_id = ? AND is_deleted = 0");
    $stmt->execute([$editId, $uid]);
    $editItem = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head><title>Manage Education</title></head>
<body>
    <h2>Education</h2>

    <!-- ADD / UPDATE FORM -->
    <form method="POST">
        <?php if ($editItem): ?>
            <input type="hidden" name="update_id" value="<?= $editItem['id'] ?>">
            <h4>Edit Record</h4>
        <?php else: ?>
            <h4>Add New Education</h4>
        <?php endif; ?>

        Institution: <input type="text" name="institution" value="<?= htmlspecialchars($editItem['institution'] ?? '') ?>" required><br><br>
        Degree: <input type="text" name="degree" value="<?= htmlspecialchars($editItem['degree'] ?? '') ?>" required><br><br>
        Field of Study: <input type="text" name="field_of_study" value="<?= htmlspecialchars($editItem['field_of_study'] ?? '') ?>"><br><br>
        Start Date: <input type="date" name="start_date" value="<?= htmlspecialchars($editItem['start_date'] ?? '') ?>"><br><br>
        End Date: <input type="date" name="end_date" value="<?= htmlspecialchars($editItem['end_date'] ?? '') ?>"><br><br>
        Description: <textarea name="description"><?= htmlspecialchars($editItem['description'] ?? '') ?></textarea><br><br>

        <button type="submit"><?= $editItem ? 'Update' : 'Add' ?></button>
        <?php if ($editItem): ?>
            <a href="education.php">Cancel Edit</a>
        <?php endif; ?>
    </form>

    <hr>

    <!-- DISPLAY LIST -->
    <h3>Your Education Records</h3>
    <?php if (count($educations) > 0): ?>
        <table border="1" cellpadding="5">
            <tr>
                <th>Institution</th><th>Degree</th><th>Field</th><th>Start</th><th>End</th><th>Description</th><th>Actions</th>
            </tr>
            <?php foreach ($educations as $edu): ?>
            <tr>
                <td><?= htmlspecialchars($edu['institution']) ?></td>
                <td><?= htmlspecialchars($edu['degree']) ?></td>
                <td><?= htmlspecialchars($edu['field_of_study']) ?></td>
                <td><?= htmlspecialchars($edu['start_date']) ?></td>
                <td><?= htmlspecialchars($edu['end_date']) ?></td>
                <td><?= nl2br(htmlspecialchars($edu['description'])) ?></td>
                <td>
                    <a href="education.php?edit=<?= $edu['id'] ?>">Edit</a> |
                    <a href="education.php?delete=<?= $edu['id'] ?>" onclick="return confirm('Delete this education?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No education records yet.</p>
    <?php endif; ?>

    <p><a href="index.php">Back to Dashboard</a></p>
</body>
</html>