<?php
session_start();
require_once '../config/config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
$uid = $_SESSION['user_id'];

// ADD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title']) && !isset($_POST['update_id'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $image = $_POST['image'] ?? '';
    $pdo->prepare("INSERT INTO blogs (user_id, title, content, image) VALUES (?,?,?,?)")->execute([$uid, $title, $content, $image]);
    header('Location: blogs.php');
    exit;
}

// UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id = $_POST['update_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $image = $_POST['image'] ?? '';
    $pdo->prepare("UPDATE blogs SET title=?, content=?, image=? WHERE id=? AND user_id=?")->execute([$title, $content, $image, $id, $uid]);
    header('Location: blogs.php');
    exit;
}

// SOFT DELETE
if (isset($_GET['delete'])) {
    $pdo->prepare("UPDATE blogs SET is_deleted=1 WHERE id=? AND user_id=?")->execute([$_GET['delete'], $uid]);
    header('Location: blogs.php');
    exit;
}

$list = $pdo->prepare("SELECT * FROM blogs WHERE user_id=? AND is_deleted=0 ORDER BY created_at DESC");
$list->execute([$uid]);
$blogs = $list->fetchAll();

$editItem = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM blogs WHERE id=? AND user_id=? AND is_deleted=0");
    $stmt->execute([$_GET['edit'], $uid]);
    $editItem = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head><title>Manage Blogs</title></head>
<body>
    <h2>Blog Posts</h2>
    <form method="POST">
        <?php if ($editItem): ?><input type="hidden" name="update_id" value="<?= $editItem['id'] ?>"><h4>Edit</h4><?php else: ?><h4>New Post</h4><?php endif; ?>
        Title: <input type="text" name="title" value="<?= htmlspecialchars($editItem['title'] ?? '') ?>" required><br><br>
        Content: <textarea name="content" rows="5"><?= htmlspecialchars($editItem['content'] ?? '') ?></textarea><br><br>
        Image URL: <input type="text" name="image" value="<?= htmlspecialchars($editItem['image'] ?? '') ?>"><br><br>
        <button type="submit"><?= $editItem ? 'Update' : 'Publish' ?></button>
        <?php if ($editItem): ?><a href="blogs.php">Cancel</a><?php endif; ?>
    </form>
    <hr>
    <h3>Your Posts</h3>
    <?php if (count($blogs)): ?>
        <?php foreach ($blogs as $b): ?>
            <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
                <h3><?= htmlspecialchars($b['title']) ?></h3>
                <?php if ($b['image']): ?><img src="<?= htmlspecialchars($b['image']) ?>" width="100"><br><?php endif; ?>
                <p><?= nl2br(htmlspecialchars($b['content'])) ?></p>
                <small>Posted: <?= $b['created_at'] ?></small><br>
                <a href="blogs.php?edit=<?= $b['id'] ?>">Edit</a> |
                <a href="blogs.php?delete=<?= $b['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?><p>No blog posts.</p><?php endif; ?>
    <p><a href="index.php">Back</a></p>
</body>
</html>