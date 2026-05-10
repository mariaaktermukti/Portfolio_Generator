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
        
        <form method="POST">
            <h3>Write New Blog Post</h3>
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required placeholder="Catchy title...">
            </div>
            
            <div class="form-group">
                <label>Content</label>
                <textarea name="content" rows="10" required placeholder="Write your content here..."></textarea>
            </div>
            
            <button type="submit" class="btn">Publish Blog Post</button>
        </form>
    </div>

    <div class="glass-panel">
        <h3>Your Blog Posts</h3>
        <?php if (count($blogs) > 0): ?>
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <?php foreach ($blogs as $blog): ?>
                    <div class="card">
                        <h4 style="margin-bottom: 0.2rem;"><?php echo htmlspecialchars($blog['title']); ?></h4>
                        <div style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 1rem;">
                            <i class="fas fa-clock"></i> <?php echo date('F j, Y, g:i a', strtotime($blog['created_at'])); ?>
                        </div>
                        <p style="margin-bottom: 1rem;"><?php echo nl2br(htmlspecialchars($blog['content'])); ?></p>
                        <form method="POST">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $blog['id']; ?>">
                            <button type="submit" class="btn btn-danger" style="width: auto; padding: 0.3rem 0.8rem; font-size: 0.8rem;"><i class="fas fa-trash"></i> Delete Post</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted);">No blog posts found. Start sharing your thoughts!</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'inc/foot.php'; ?>