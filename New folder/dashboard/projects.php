<?php
session_start();
require_once '../config/db.php';
require_once '../config/imgbb.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$edit_data = null;
$edit_id = null;

// Ensure projects table exists
$create_table_sql = "
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200),
    description TEXT,
    image_url VARCHAR(500),
    video_url VARCHAR(500),
    git_url VARCHAR(500),
    live_demo_url VARCHAR(500),
    tags VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";
try {
    $pdo->exec($create_table_sql);
} catch (PDOException $e) {
    // Ignore error if table exists or issues
}

// Function to upload image to imgbb
function uploadToImgbb($filePath) {
    $postFields = array(
        'image' => new CURLFile($filePath)
    );
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.imgbb.com/1/upload?key=' . IMGBB_API_KEY,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
    ));
    
    $response = curl_exec($curl);
    curl_close($curl);
    
    $result = json_decode($response, true);
    
    if ($result && isset($result['success']) && $result['success']) {
        return $result['data']['url'];
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE projects SET is_deleted = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $_SESSION['success_msg'] = "Project deleted.";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $edit_id = $_POST['id'];
        $title = isset($_POST['title']) ? $_POST['title'] : '';
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $video_url = isset($_POST['video_url']) ? $_POST['video_url'] : '';
        $git_url = isset($_POST['git_url']) ? $_POST['git_url'] : '';
        $live_demo_url = isset($_POST['live_demo_url']) ? $_POST['live_demo_url'] : '';
        $tags = isset($_POST['tags']) ? $_POST['tags'] : '';
        $image_url = isset($_POST['image_url']) ? $_POST['image_url'] : '';
        
        // Handle new project image upload
        if (isset($_FILES['project_image']) && $_FILES['project_image']['size'] > 0) {
            $fileTmpPath = $_FILES['project_image']['tmp_name'];
            $fileName = $_FILES['project_image']['name'];
            $fileSize = $_FILES['project_image']['size'];
            $fileType = $_FILES['project_image']['type'];
            
            // Validate file (max 5MB)
            if ($fileSize > 5 * 1024 * 1024) {
                $_SESSION['error_msg'] = "File too large. Maximum size is 5MB.";
            } else if (!in_array($fileType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                $_SESSION['error_msg'] = "Invalid file type. Please upload an image.";
            } else {
                $imgbbUrl = uploadToImgbb($fileTmpPath);
                if ($imgbbUrl) {
                    $image_url = $imgbbUrl;
                } else {
                    $_SESSION['error_msg'] = "Failed to upload image. Please try again.";
                }
            }
        }

        $stmt = $pdo->prepare("UPDATE projects SET title = ?, description = ?, image_url = ?, video_url = ?, git_url = ?, live_demo_url = ?, tags = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $description, $image_url, $video_url, $git_url, $live_demo_url, $tags, $edit_id, $user_id]);
        $_SESSION['success_msg'] = "Project updated.";
    } else {
        $title = isset($_POST['title']) ? $_POST['title'] : '';
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $video_url = isset($_POST['video_url']) ? $_POST['video_url'] : '';
        $git_url = isset($_POST['git_url']) ? $_POST['git_url'] : '';
        $live_demo_url = isset($_POST['live_demo_url']) ? $_POST['live_demo_url'] : '';
        $tags = isset($_POST['tags']) ? $_POST['tags'] : '';
        $image_url = '';
        
        // Handle project image upload
        if (isset($_FILES['project_image']) && $_FILES['project_image']['size'] > 0) {
            $fileTmpPath = $_FILES['project_image']['tmp_name'];
            $fileName = $_FILES['project_image']['name'];
            $fileSize = $_FILES['project_image']['size'];
            $fileType = $_FILES['project_image']['type'];
            
            // Validate file (max 5MB)
            if ($fileSize > 5 * 1024 * 1024) {
                $_SESSION['error_msg'] = "File too large. Maximum size is 5MB.";
                header('Location: projects.php');
                exit;
            } else if (!in_array($fileType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                $_SESSION['error_msg'] = "Invalid file type. Please upload an image.";
                header('Location: projects.php');
                exit;
            } else {
                $imgbbUrl = uploadToImgbb($fileTmpPath);
                if ($imgbbUrl) {
                    $image_url = $imgbbUrl;
                } else {
                    $_SESSION['error_msg'] = "Failed to upload image. Please try again.";
                    header('Location: projects.php');
                    exit;
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO projects (user_id, title, description, image_url, video_url, git_url, live_demo_url, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $description, $image_url, $video_url, $git_url, $live_demo_url, $tags]);
        $_SESSION['success_msg'] = "Project added.";
    }
    header('Location: projects.php');
    exit;
}

// Check if editing
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? AND user_id = ? AND is_deleted = 0");
    $stmt->execute([$edit_id, $user_id]);
    $edit_data = $stmt->fetch();
}

if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

$stmt = $pdo->prepare("SELECT * FROM projects WHERE user_id = ? AND is_deleted = 0 ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$projects = $stmt->fetchAll();
?>
<?php include 'inc/head.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<main class="main-content">
    <div class="glass-panel">
        <h2>Manage Projects</h2>
        
        <?php if ($success): ?>
            <div class="msg-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="msg-error"><?php echo htmlspecialchars($_SESSION['error_msg']); ?></div>
            <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <h3><?php echo $edit_data ? 'Edit Project' : 'Add New Project'; ?></h3>
            <?php if ($edit_data): ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                <input type="hidden" name="image_url" value="<?php echo htmlspecialchars(isset($edit_data['image_url']) ? $edit_data['image_url'] : ''); ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Title <span style="color: var(--danger);">*</span></label>
                <input type="text" name="title" required placeholder="e.g. E-Commerce Website" value="<?php echo htmlspecialchars(isset($edit_data['title']) ? $edit_data['title'] : ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Description <span style="color: var(--danger);">*</span></label>
                <textarea name="description" rows="4" required placeholder="Describe your project..."><?php echo htmlspecialchars(isset($edit_data['description']) ? $edit_data['description'] : ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Project Image (Optional)</label>
                <div style="margin-bottom: 0.5rem;">
                    <input type="file" name="project_image" accept="image/*" style="padding: 0.5rem;">
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">Supported formats: JPG, PNG, GIF, WebP (Max 5MB)</p>
                </div>
                <?php if ($edit_data && !empty($edit_data['image_url'])): ?>
                    <div style="margin-top: 1rem;">
                        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 0.5rem;">Current Image:</p>
                        <img src="<?php echo htmlspecialchars($edit_data['image_url']); ?>" alt="Project Image" style="max-width: 300px; max-height: 200px; border-radius: 8px; object-fit: contain; border: 1px solid rgba(79, 70, 229, 0.3);">
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Video URL (Optional)</label>
                <input type="url" name="video_url" placeholder="e.g. YouTube or Vimeo link" value="<?php echo htmlspecialchars(isset($edit_data['video_url']) ? $edit_data['video_url'] : ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Git URL (Optional)</label>
                <input type="url" name="git_url" placeholder="e.g. https://github.com/username/project" value="<?php echo htmlspecialchars(isset($edit_data['git_url']) ? $edit_data['git_url'] : ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Live Demo URL (Optional)</label>
                <input type="url" name="live_demo_url" placeholder="e.g. https://yourproject.com" value="<?php echo htmlspecialchars(isset($edit_data['live_demo_url']) ? $edit_data['live_demo_url'] : ''); ?>">
            </div>

            <div class="form-group">
                <label>Tags (Comma-separated) <span style="color: var(--danger);">*</span></label>
                <input type="text" name="tags" required placeholder="e.g. PHP, MySQL, JavaScript" value="<?php echo htmlspecialchars(isset($edit_data['tags']) ? $edit_data['tags'] : ''); ?>">
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn"><?php echo $edit_data ? 'Update Project' : 'Add Project'; ?></button>
                <?php if ($edit_data): ?>
                    <a href="projects.php" class="btn" style="background: rgba(255,255,255,0.1); text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="glass-panel">
        <h3>Your Projects</h3>
        <?php if (count($projects) > 0): ?>
            <div class="card-grid">
                <?php foreach ($projects as $proj): ?>
                    <div class="card">
                        <h4 style="margin-bottom: 0.5rem; color: var(--accent);"><i class="fas fa-project-diagram"></i> <?php echo htmlspecialchars($proj['title']); ?></h4>
                        
                        <?php if (!empty($proj['image_url'])): ?>
                            <div style="margin-bottom: 1rem;">
                                <img src="<?php echo htmlspecialchars($proj['image_url']); ?>" alt="Project Image" style="max-width: 100%; max-height: 150px; border-radius: 6px; object-fit: cover; border: 1px solid rgba(79, 70, 229, 0.3);">
                            </div>
                        <?php endif; ?>

                        <p style="margin-bottom: 1rem; font-size: 0.95rem; white-space: pre-line;"><?php echo htmlspecialchars($proj['description']); ?></p>
                        
                        <div style="margin-bottom: 1rem; color: var(--text-muted); font-size: 0.85rem;">
                            <strong>Tags:</strong> 
                            <?php 
                            $tags = explode(',', $proj['tags']);
                            foreach($tags as $tag) {
                                echo '<span style="display: inline-block; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.2); padding: 0.2rem 0.5rem; border-radius: 4px; margin: 0.2rem 0.2rem 0 0;">' . htmlspecialchars(trim($tag)) . '</span>';
                            }
                            ?>
                        </div>

                        <div style="margin-bottom: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            <?php if (!empty($proj['git_url'])): ?>
                                <a href="<?php echo htmlspecialchars($proj['git_url']); ?>" target="_blank" class="btn" style="width: auto; padding: 0.3rem 0.8rem; font-size: 0.8rem; background: rgba(100,200,255,0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;"><i class="fab fa-github"></i> Git</a>
                            <?php endif; ?>
                            <?php if (!empty($proj['live_demo_url'])): ?>
                                <a href="<?php echo htmlspecialchars($proj['live_demo_url']); ?>" target="_blank" class="btn" style="width: auto; padding: 0.3rem 0.8rem; font-size: 0.8rem; background: rgba(100,200,255,0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;"><i class="fas fa-external-link-alt"></i> Live Demo</a>
                            <?php endif; ?>
                            <?php if (!empty($proj['video_url'])): ?>
                                <a href="<?php echo htmlspecialchars($proj['video_url']); ?>" target="_blank" class="btn" style="width: auto; padding: 0.3rem 0.8rem; font-size: 0.8rem; background: rgba(100,200,255,0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;"><i class="fas fa-video"></i> Video</a>
                            <?php endif; ?>
                        </div>
                        
                        <div style="display: flex; gap: 0.5rem; margin-top: auto;">
                            <a href="projects.php?edit=<?php echo $proj['id']; ?>" class="btn" style="width: auto; padding: 0.3rem 0.8rem; font-size: 0.8rem; background: rgba(100,200,255,0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;"><i class="fas fa-edit"></i> Edit</a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $proj['id']; ?>">
                                <button type="submit" class="btn btn-danger" style="width: auto; padding: 0.3rem 0.8rem; font-size: 0.8rem;"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted);">No projects found. Add your awesome projects here!</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'inc/foot.php'; ?>
