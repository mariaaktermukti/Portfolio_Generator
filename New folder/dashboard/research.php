<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';
$edit_data = null;
$edit_id = null;

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS research (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        publication_date DATE,
        link VARCHAR(255),
        tags VARCHAR(255),
        is_deleted TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
} catch (PDOException $e) {
    // Silently ignore or log
}

if (isset($_SESSION['error_msg'])) {
    $error = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE research SET is_deleted = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $_SESSION['success_msg'] = "Research entry deleted.";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $edit_id = $_POST['id'];
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $publication_date = !empty($_POST['publication_date']) ? $_POST['publication_date'] : null;
        $link = $_POST['link'] ?? '';
        $tags = $_POST['tags'] ?? '';

        $stmt = $pdo->prepare("UPDATE research SET title = ?, description = ?, publication_date = ?, link = ?, tags = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $description, $publication_date, $link, $tags, $edit_id, $user_id]);
        $_SESSION['success_msg'] = "Research entry updated.";
    } else {
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $publication_date = !empty($_POST['publication_date']) ? $_POST['publication_date'] : null;
        $link = $_POST['link'] ?? '';
        $tags = $_POST['tags'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO research (user_id, title, description, publication_date, link, tags) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $description, $publication_date, $link, $tags]);
        $_SESSION['success_msg'] = "Research entry added.";
    }
    header('Location: research.php');
    exit;
}

if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

// Check if editing
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM research WHERE id = ? AND user_id = ? AND is_deleted = 0");
    $stmt->execute([$edit_id, $user_id]);
    $edit_data = $stmt->fetch();
}

$stmt = $pdo->prepare("SELECT * FROM research WHERE user_id = ? AND is_deleted = 0 ORDER BY publication_date DESC, created_at DESC");
$stmt->execute([$user_id]);
$researches = $stmt->fetchAll();
?>

<?php include 'inc/head.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<main class="main-content">
    <header class="top-nav">
        <h1>Research</h1>
        <div class="user-menu">
            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
        </div>
    </header>

    <div class="glass-panel" style="margin-bottom: 2rem;">
        <?php if ($success): ?>
            <div class="msg-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="msg-error" style="color: #ff4d4d; background: rgba(255, 77, 77, 0.1); padding: 10px; border-radius: 5px; margin-bottom: 15px; border: 1px solid rgba(255, 77, 77, 0.3);"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <h3><?php echo $edit_data ? 'Edit Research' : 'Add Research'; ?></h3>
            <?php if ($edit_data): ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
            <?php endif; ?>
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required placeholder="Research Title" value="<?php echo htmlspecialchars(isset($edit_data['title']) ? $edit_data['title'] : ''); ?>">
            </div>
            
            <div class="form-group" style="display: flex; gap: 1rem;">
                <div style="flex: 1;">
                    <label>Publication Date</label>
                    <input type="date" name="publication_date" placeholder="YYYY-MM-DD" value="<?php echo htmlspecialchars(isset($edit_data['publication_date']) ? $edit_data['publication_date'] : ''); ?>">
                </div>
                <div style="flex: 1;">
                    <label>Link (URL)</label>
                    <input type="url" name="link" placeholder="https://..." value="<?php echo htmlspecialchars(isset($edit_data['link']) ? $edit_data['link'] : ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>Tags (comma-separated)</label>
                <input type="text" name="tags" placeholder="e.g. AI, Machine Learning, Data Science" value="<?php echo htmlspecialchars(isset($edit_data['tags']) ? $edit_data['tags'] : ''); ?>">
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4" placeholder="Describe the research..."><?php echo htmlspecialchars(isset($edit_data['description']) ? $edit_data['description'] : ''); ?></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn"><?php echo $edit_data ? 'Update Research' : 'Add Research'; ?></button>
                <?php if ($edit_data): ?>
                    <a href="research.php" class="btn" style="background: rgba(255,255,255,0.1); text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="glass-panel">
        <h3>Your Research</h3>
        <?php if (count($researches) > 0): ?>
            <div class="card-grid">
                <?php foreach ($researches as $research): ?>
                    <div class="card">
                        <h4 style="margin-bottom: 0.5rem; word-break: break-word;"><?php echo htmlspecialchars($research['title']); ?></h4>
                        
                        <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem;">
                            <?php if ($research['publication_date']): ?>
                                <i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($research['publication_date']); ?>
                            <?php endif; ?>
                        </div>

                        <?php if ($research['tags']): ?>
                            <div style="margin-bottom: 0.5rem;">
                                <?php 
                                $tags = explode(',', $research['tags']);
                                foreach($tags as $tag): 
                                ?>
                                    <span style="display: inline-block; background: rgba(59, 130, 246, 0.1); color: var(--primary); padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.8rem; margin-right: 0.3rem; margin-bottom: 0.3rem;">
                                        <?php echo htmlspecialchars(trim($tag)); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <p style="font-size: 0.95rem; margin-bottom: 1rem; color: var(--text-muted);"><?php echo nl2br(htmlspecialchars($research['description'])); ?></p>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto;">
                            <?php if ($research['link']): ?>
                                <a href="<?php echo htmlspecialchars($research['link']); ?>" target="_blank" class="btn" style="width: auto; padding: 0.4rem 1rem; font-size: 0.8rem; background: rgba(255,255,255,0.1); color: white;">View Link</a>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="research.php?edit=<?php echo $research['id']; ?>" class="btn" style="width: auto; padding: 0.4rem 1rem; font-size: 0.8rem; background: rgba(100,200,255,0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;"><i class="fas fa-edit"></i> Edit</a>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $research['id']; ?>">
                                    <button type="submit" class="btn btn-danger" style="width: auto; padding: 0.4rem 1rem; font-size: 0.8rem;"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-muted); padding: 2rem 0;">No research entries added yet.</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'inc/foot.php'; ?>