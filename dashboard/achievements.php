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

// Add certificate_url and certificate_image_url columns if they don't exist
try {
    $pdo->exec("ALTER TABLE achievements ADD COLUMN certificate_url VARCHAR(500) DEFAULT ''");
} catch (PDOException $e) {
    // Column already exists
}

try {
    $pdo->exec("ALTER TABLE achievements ADD COLUMN certificate_image_url VARCHAR(500) DEFAULT ''");
} catch (PDOException $e) {
    // Column already exists
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
    
    if ($result['success']) {
        return $result['data']['url'];
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE achievements SET is_deleted = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $_SESSION['success_msg'] = "Achievement deleted.";
    } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $edit_id = $_POST['id'];
        $title = $_POST['title'] ?? '';
        $date_earned = $_POST['date_earned'] ?? null;
        $description = $_POST['description'] ?? '';
        $certificate_url = $_POST['certificate_url'] ?? '';
        $certificate_image_url = $_POST['certificate_image_url'] ?? '';
        
        // Handle new certificate image upload
        if (isset($_FILES['certificate_image']) && $_FILES['certificate_image']['size'] > 0) {
            $fileTmpPath = $_FILES['certificate_image']['tmp_name'];
            $fileName = $_FILES['certificate_image']['name'];
            $fileSize = $_FILES['certificate_image']['size'];
            $fileType = $_FILES['certificate_image']['type'];
            
            // Validate file (max 5MB)
            if ($fileSize > 5 * 1024 * 1024) {
                $_SESSION['error_msg'] = "File too large. Maximum size is 5MB.";
            } else if (!in_array($fileType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                $_SESSION['error_msg'] = "Invalid file type. Please upload an image.";
            } else {
                $imgbbUrl = uploadToImgbb($fileTmpPath);
                if ($imgbbUrl) {
                    $certificate_image_url = $imgbbUrl;
                } else {
                    $_SESSION['error_msg'] = "Failed to upload image. Please try again.";
                }
            }
        }

        $stmt = $pdo->prepare("UPDATE achievements SET title = ?, date_earned = ?, description = ?, certificate_url = ?, certificate_image_url = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $date_earned, $description, $certificate_url, $certificate_image_url, $edit_id, $user_id]);
        $_SESSION['success_msg'] = "Achievement updated.";
    } else {
        $title = $_POST['title'] ?? '';
        $date_earned = $_POST['date_earned'] ?? null;
        $description = $_POST['description'] ?? '';
        $certificate_url = $_POST['certificate_url'] ?? '';
        $certificate_image_url = '';
        
        // Handle certificate image upload
        if (isset($_FILES['certificate_image']) && $_FILES['certificate_image']['size'] > 0) {
            $fileTmpPath = $_FILES['certificate_image']['tmp_name'];
            $fileName = $_FILES['certificate_image']['name'];
            $fileSize = $_FILES['certificate_image']['size'];
            $fileType = $_FILES['certificate_image']['type'];
            
            // Validate file (max 5MB)
            if ($fileSize > 5 * 1024 * 1024) {
                $_SESSION['error_msg'] = "File too large. Maximum size is 5MB.";
                header('Location: achievements.php');
                exit;
            } else if (!in_array($fileType, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
                $_SESSION['error_msg'] = "Invalid file type. Please upload an image.";
                header('Location: achievements.php');
                exit;
            } else {
                $imgbbUrl = uploadToImgbb($fileTmpPath);
                if ($imgbbUrl) {
                    $certificate_image_url = $imgbbUrl;
                } else {
                    $_SESSION['error_msg'] = "Failed to upload image. Please try again.";
                    header('Location: achievements.php');
                    exit;
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO achievements (user_id, title, date_earned, description, certificate_url, certificate_image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $date_earned, $description, $certificate_url, $certificate_image_url]);
        $_SESSION['success_msg'] = "Achievement added.";
    }
    header('Location: achievements.php');
    exit;
}

// Check if editing
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM achievements WHERE id = ? AND user_id = ? AND is_deleted = 0");
    $stmt->execute([$edit_id, $user_id]);
    $edit_data = $stmt->fetch();
}

if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

$stmt = $pdo->prepare("SELECT * FROM achievements WHERE user_id = ? AND is_deleted = 0 ORDER BY date_earned DESC");
$stmt->execute([$user_id]);
$achievements = $stmt->fetchAll();
?>
<?php include 'inc/head.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<main class="main-content">
    <div class="glass-panel">
        <h2>Manage Achievements</h2>
        
        <?php if ($success): ?>
            <div class="msg-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="msg-error"><?php echo htmlspecialchars($_SESSION['error_msg']); ?></div>
            <?php unset($_SESSION['error_msg']); ?>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <h3><?php echo $edit_data ? 'Edit Achievement' : 'Add New Achievement'; ?></h3>
            <?php if ($edit_data): ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                <input type="hidden" name="certificate_image_url" value="<?php echo htmlspecialchars($edit_data['certificate_image_url'] ?? ''); ?>">
            <?php endif; ?>
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" required placeholder="e.g. Hackathon Winner" value="<?php echo htmlspecialchars($edit_data['title'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Date Earned</label>
                <input type="date" name="date_earned" required value="<?php echo htmlspecialchars($edit_data['date_earned'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Describe the achievement..."><?php echo htmlspecialchars($edit_data['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Certificate Image (Optional)</label>
                <div style="margin-bottom: 0.5rem;">
                    <input type="file" name="certificate_image" accept="image/*" placeholder="Upload certificate image" style="padding: 0.5rem;">
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">Supported formats: JPG, PNG, GIF, WebP (Max 5MB)</p>
                </div>
                <?php if ($edit_data && !empty($edit_data['certificate_image_url'])): ?>
                    <div style="margin-top: 1rem;">
                        <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 0.5rem;">Current Certificate:</p>
                        <img src="<?php echo htmlspecialchars($edit_data['certificate_image_url']); ?>" alt="Certificate" style="max-width: 300px; max-height: 200px; border-radius: 8px; object-fit: contain; border: 1px solid rgba(79, 70, 229, 0.3);">
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>Certificate Original Link (Optional)</label>
                <input type="url" name="certificate_url" placeholder="e.g. https://example.com/certificate.pdf or https://drive.google.com/..." value="<?php echo htmlspecialchars($edit_data['certificate_url'] ?? ''); ?>">
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">Link to the original certificate (PDF, document, or any URL)</p>
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn"><?php echo $edit_data ? 'Update Achievement' : 'Add Achievement'; ?></button>
                <?php if ($edit_data): ?>
                    <a href="achievements.php" class="btn" style="background: rgba(255,255,255,0.1); text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="glass-panel">
        <h3>Your Achievements</h3>
        <?php if (count($achievements) > 0): ?>
            <div class="card-grid">
                <?php foreach ($achievements as $ach): ?>
                    <div class="card">
                        <h4 style="margin-bottom: 0.2rem; color: var(--accent);"><i class="fas fa-award"></i> <?php echo htmlspecialchars($ach['title']); ?></h4>
                        <div style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-calendar-alt"></i> <?php echo $ach['date_earned']; ?>
                        </div>
                        <p style="margin-bottom: 1rem; font-size: 0.95rem;"><?php echo htmlspecialchars($ach['description']); ?></p>
                        
                        <?php if (!empty($ach['certificate_image_url'])): ?>
                            <div style="margin-bottom: 1rem;">
                                <img src="<?php echo htmlspecialchars($ach['certificate_image_url']); ?>" alt="Certificate" style="max-width: 100%; max-height: 150px; border-radius: 6px; object-fit: contain; border: 1px solid rgba(79, 70, 229, 0.3);">
                            </div>
                        <?php endif; ?>
                        
                        <div style="margin-bottom: 1rem; display: flex; flex-wrap: wrap; gap: 0.5rem;">
                            <?php if (!empty($ach['certificate_image_url'])): ?>
                                <a href="<?php echo htmlspecialchars($ach['certificate_image_url']); ?>" target="_blank" class="btn" style="width: auto; padding: 0.3rem 0.8rem; font-size: 0.8rem; background: rgba(100,200,255,0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;"><i class="fas fa-image"></i> View Image</a>
                            <?php endif; ?>
                            <?php if (!empty($ach['certificate_url'])): ?>
                                <a href="<?php echo htmlspecialchars($ach['certificate_url']); ?>" target="_blank" class="btn" style="width: auto; padding: 0.3rem 0.8rem; font-size: 0.8rem; background: rgba(100,200,255,0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;"><i class="fas fa-external-link-alt"></i> Original Link</a>
                            <?php endif; ?>
                        </div>
                        
                        <div style="display: flex; gap: 0.5rem;">
                            <a href="achievements.php?edit=<?php echo $ach['id']; ?>" class="btn" style="width: auto; padding: 0.3rem 0.8rem; font-size: 0.8rem; background: rgba(100,200,255,0.3); text-decoration: none; display: inline-flex; align-items: center; gap: 0.3rem;"><i class="fas fa-edit"></i> Edit</a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $ach['id']; ?>">
                                <button type="submit" class="btn btn-danger" style="width: auto; padding: 0.3rem 0.8rem; font-size: 0.8rem;"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted);">No achievements found. Add some to stand out!</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'inc/foot.php'; ?>
