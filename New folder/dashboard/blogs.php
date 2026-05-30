<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE blogs SET is_deleted = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $_SESSION['success_msg'] = "Blog post deleted.";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $stmt = $pdo->prepare("UPDATE blogs SET title = ?, content = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $content, $id, $user_id]);
        $_SESSION['success_msg'] = "Blog post updated.";
    } else {
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO blogs (user_id, title, content) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $title, $content]);
        $_SESSION['success_msg'] = "Blog post published.";
    }
    header('Location: blogs.php');
    exit;
}

if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

$stmt = $pdo->prepare("SELECT * FROM blogs WHERE user_id = ? AND is_deleted = 0 ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$blogs = $stmt->fetchAll();
?>
<?php include 'inc/head.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<main class="main-content">
    <div class="glass-panel">
        <h2>Manage Blogs</h2>
        
        <?php if ($success): ?>
            <div class="msg-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php
        $edit_blog = null;
        if (isset($_GET['edit'])) {
            $stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = ? AND user_id = ? AND is_deleted = 0");
            $stmt->execute([$_GET['edit'], $user_id]);
            $edit_blog = $stmt->fetch();
        }
        ?>

        <form method="POST">
            <?php if ($edit_blog): ?>
                <h3>Edit Blog Post</h3>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $edit_blog['id']; ?>">
            <?php else: ?>
                <h3>Write New Blog Post</h3>
            <?php endif; ?>
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required placeholder="Catchy title..." value="<?php echo $edit_blog ? htmlspecialchars($edit_blog['title']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label>Content</label>
                <textarea name="content" rows="10" required placeholder="Write your content here..."><?php echo $edit_blog ? htmlspecialchars($edit_blog['content']) : ''; ?></textarea>
            </div>
            
            <button type="submit" class="btn"><?php echo $edit_blog ? 'Update Blog Post' : 'Publish Blog Post'; ?></button>
            <?php if ($edit_blog): ?>
                <a href="blogs.php" class="btn" style="background: var(--text-muted); margin-top: 10px; text-align: center; display: block; text-decoration: none;">Cancel Edit</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="glass-panel">
        <h3>Your Blog Posts</h3>
        <?php if (count($blogs) > 0): ?>
            <div class="card-grid">
                <?php foreach ($blogs as $blog): ?>
                    <div class="card" style="display: flex; flex-direction: column;">
                        <h4 style="margin-bottom: 0.2rem; word-break: break-word;"><?php echo htmlspecialchars($blog['title']); ?></h4>
                        <div style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 1rem;">
                            <i class="fas fa-clock"></i> <?php echo date('F j, Y, g:i a', strtotime($blog['created_at'])); ?>
                        </div>
                        <p style="margin-bottom: 1rem; color: var(--text-muted); font-size: 0.95rem; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;">
                            <?php echo nl2br(htmlspecialchars($blog['content'])); ?>
                        </p>
                        <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: auto;">
                            <a href="blogs.php?edit=<?php echo $blog['id']; ?>" class="btn" style="width: auto; padding: 0.4rem 1rem; font-size: 0.8rem; text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;"><i class="fas fa-edit"></i> Edit</a>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $blog['id']; ?>">
                                <button type="submit" class="btn btn-danger" style="width: auto; padding: 0.4rem 1rem; font-size: 0.8rem;"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">No blog posts found. Start sharing your thoughts!</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'inc/foot.php'; ?>