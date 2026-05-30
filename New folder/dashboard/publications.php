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
    $pdo->exec("CREATE TABLE IF NOT EXISTS publications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        authors VARCHAR(255),
        journal_conference VARCHAR(255),
        publish_date DATE,
        link VARCHAR(255),
        abstract TEXT,
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
        $stmt = $pdo->prepare("UPDATE publications SET is_deleted = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $_SESSION['success_msg'] = "Publication deleted.";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $edit_id = $_POST['id'];
        $title = $_POST['title'] ?? '';
        $authors = $_POST['authors'] ?? '';
        $journal_conference = $_POST['journal_conference'] ?? '';
        $publish_date = !empty($_POST['publish_date']) ? $_POST['publish_date'] : null;
        $link = $_POST['link'] ?? '';
        $abstract = $_POST['abstract'] ?? '';

        $stmt = $pdo->prepare("UPDATE publications SET title = ?, authors = ?, journal_conference = ?, publish_date = ?, link = ?, abstract = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $authors, $journal_conference, $publish_date, $link, $abstract, $edit_id, $user_id]);
        $_SESSION['success_msg'] = "Publication updated.";
    } else {
        $title = $_POST['title'] ?? '';
        $authors = $_POST['authors'] ?? '';
        $journal_conference = $_POST['journal_conference'] ?? '';
        $publish_date = !empty($_POST['publish_date']) ? $_POST['publish_date'] : null;
        $link = $_POST['link'] ?? '';
        $abstract = $_POST['abstract'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO publications (user_id, title, authors, journal_conference, publish_date, link, abstract) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $authors, $journal_conference, $publish_date, $link, $abstract]);
        $_SESSION['success_msg'] = "Publication added.";
    }
    header('Location: publications.php');
    exit;
}

if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

// Check if editing
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM publications WHERE id = ? AND user_id = ? AND is_deleted = 0");
    $stmt->execute([$edit_id, $user_id]);
    $edit_data = $stmt->fetch();
}

$stmt = $pdo->prepare("SELECT * FROM publications WHERE user_id = ? AND is_deleted = 0 ORDER BY publish_date DESC, created_at DESC");
$stmt->execute([$user_id]);
$publications = $stmt->fetchAll();
?>

<?php include 'inc/head.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<main class="main-content">
    <header class="top-nav">
        <h1>Publications</h1>
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
            <h3><?php echo $edit_data ? 'Edit Publication' : 'Add Publication'; ?></h3>
            <?php if ($edit_data): ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
            <?php endif; ?>
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required placeholder="Publication Title" value="<?php echo htmlspecialchars(isset($edit_data['title']) ? $edit_data['title'] : ''); ?>">
            </div>
            
            <div class="form-group" style="display: flex; gap: 1rem;">
                <div style="flex: 1;">
                    <label>Authors</label>
                    <input type="text" name="authors" placeholder="e.g. John Doe, Jane Smith" value="<?php echo htmlspecialchars(isset($edit_data['authors']) ? $edit_data['authors'] : ''); ?>">
                </div>
                <div style="flex: 1;">
                    <label>Journal / Conference</label>
                    <input type="text" name="journal_conference" placeholder="e.g. IEEE, ACM, etc." value="<?php echo htmlspecialchars(isset($edit_data['journal_conference']) ? $edit_data['journal_conference'] : ''); ?>">
                </div>
            </div>

            <div class="form-group" style="display: flex; gap: 1rem;">
                <div style="flex: 1;">
                    <label>Publish Date</label>
                    <input type="date" name="publish_date" placeholder="YYYY-MM-DD" value="<?php echo htmlspecialchars(isset($edit_data['publish_date']) ? $edit_data['publish_date'] : ''); ?>">
                </div>
                <div style="flex: 1;">
                    <label>Link (URL)</label>
                    <input type="url" name="link" placeholder="https://..." value="<?php echo htmlspecialchars(isset($edit_data['link']) ? $edit_data['link'] : ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label>Abstract</label>
                <textarea name="abstract" rows="4" placeholder="Brief abstract..."><?php echo htmlspecialchars(isset($edit_data['abstract']) ? $edit_data['abstract'] : ''); ?></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn"><?php echo $edit_data ? 'Update Publication' : 'Add Publication'; ?></button>
                <?php if ($edit_data): ?>
                    <a href="publications.php" class="btn" style="background: rgba(255,255,255,0.1); text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="glass-panel">
        <h3>Your Publications</h3>
        <?php if (count($publications) > 0): ?>
            <div class="card-grid">
                <?php foreach ($publications as $pub): ?>
                    <div class="card">
                        <h4 style="margin-bottom: 0.5rem; word-break: break-word;"><?php echo htmlspecialchars($pub['title']); ?></h4>
                        
                        <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem;">
                            <?php if ($pub['authors']): ?>
                                <i class="fas fa-users"></i> <?php echo htmlspecialchars($pub['authors']); ?><br>
                            <?php endif; ?>
                            <?php if ($pub['journal_conference']): ?>
                                <i class="fas fa-book"></i> <?php echo htmlspecialchars($pub['journal_conference']); ?> 
                            <?php endif; ?>
                            <?php if ($pub['publish_date']): ?>
                                | <i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($pub['publish_date']); ?>
                            <?php endif; ?>
                        </div>

                        <?php if ($pub['abstract']): ?>
                            <p style="font-size: 0.95rem; margin-bottom: 1rem; color: var(--text-muted);"><?php echo nl2br(htmlspecialchars($pub['abstract'])); ?></p>
                        <?php endif; ?>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto;">
                            <?php if ($pub['link']): ?>
                                <a href="<?php echo htmlspecialchars($pub['link']); ?>" target="_blank" class="btn" style="width: auto; padding: 0.4rem 1rem; font-size: 0.8rem; background: rgba(255,255,255,0.1); color: white;">View Link</a>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            <div style="display: flex; gap: 0.5rem;">
                                <a href="publications.php?edit=<?php echo $pub['id']; ?>" class="btn" style="width: auto; padding: 0.4rem 1rem; font-size: 0.8rem; background: rgba(100,200,255,0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;"><i class="fas fa-edit"></i> Edit</a>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $pub['id']; ?>">
                                    <button type="submit" class="btn btn-danger" style="width: auto; padding: 0.4rem 1rem; font-size: 0.8rem;"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: var(--text-muted); padding: 2rem 0;">No publications added yet.</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'inc/foot.php'; ?>