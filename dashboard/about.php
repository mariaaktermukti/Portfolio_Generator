<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';

// Add about_image column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE about ADD COLUMN about_image VARCHAR(255) DEFAULT ''");
} catch (PDOException $e) {
    // Column already exists
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = $_POST['bio'] ?? '';
    $title = $_POST['title'] ?? '';
    $profile_image = $_POST['profile_image'] ?? '';
    $about_image = $_POST['about_image'] ?? '';

    $stmt = $pdo->prepare("SELECT id FROM about WHERE user_id = ? AND is_deleted = 0");
    $stmt->execute([$user_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        $stmt = $pdo->prepare("UPDATE about SET bio = ?, title = ?, profile_image = ?, about_image = ? WHERE user_id = ? AND is_deleted = 0");
        $stmt->execute([$bio, $title, $profile_image, $about_image, $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO about (user_id, bio, title, profile_image, about_image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $bio, $title, $profile_image, $about_image]);
    }
    
    $_SESSION['success_msg'] = "About information updated successfully!";
    header('Location: about.php');
    exit;
}

if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

$stmt = $pdo->prepare("SELECT * FROM about WHERE user_id = ? AND is_deleted = 0");
$stmt->execute([$user_id]);
$about = $stmt->fetch() ?: ['bio' => '', 'title' => '', 'profile_image' => '', 'about_image' => ''];

?>
<?php include 'inc/head.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<main class="main-content">
    <div class="glass-panel">
        <h2>Manage About Section</h2>
        
        <?php if ($success): ?>
            <div class="msg-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Professional Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($about['title']); ?>" required placeholder="e.g. Full Stack Developer">
            </div>
            
            <div class="form-group">
                <label>Profile Image</label>
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <div style="flex: 1;">
                        <input type="file" id="profileImageInput" accept="image/*" style="padding: 0.8rem; background: rgba(0,0,0,0.2); border: 1px solid var(--border); border-radius: 8px; color: #fff; width: 100%; cursor: pointer;">
                        <input type="hidden" name="profile_image" id="profileImageUrl" value="<?php echo htmlspecialchars($about['profile_image']); ?>">
                        <div id="profileImageLoader" style="display: none; margin-top: 0.5rem;">
                            <i class="fas fa-spinner fa-spin"></i> Uploading image...
                        </div>
                        <div id="profileImagePreview" style="margin-top: 1rem;">
                            <?php if (!empty($about['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($about['profile_image']); ?>" alt="Profile Preview" style="max-width: 150px; max-height: 150px; border-radius: 8px;">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>About Image</label>
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <div style="flex: 1;">
                        <input type="file" id="aboutImageInput" accept="image/*" style="padding: 0.8rem; background: rgba(0,0,0,0.2); border: 1px solid var(--border); border-radius: 8px; color: #fff; width: 100%; cursor: pointer;">
                        <input type="hidden" name="about_image" id="aboutImageUrl" value="<?php echo htmlspecialchars($about['about_image']); ?>">
                        <div id="aboutImageLoader" style="display: none; margin-top: 0.5rem;">
                            <i class="fas fa-spinner fa-spin"></i> Uploading image...
                        </div>
                        <div id="aboutImagePreview" style="margin-top: 1rem;">
                            <?php if (!empty($about['about_image'])): ?>
                                <img src="<?php echo htmlspecialchars($about['about_image']); ?>" alt="About Preview" style="max-width: 150px; max-height: 150px; border-radius: 8px;">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio" rows="5" required placeholder="Write a short bio about yourself..."><?php echo htmlspecialchars($about['bio']); ?></textarea>
            </div>
            
            <button type="submit" class="btn">Save Changes</button>
        </form>
    </div>
</main>

<?php include 'inc/foot.php'; ?>

<script>
function setupImageUpload(inputId, uploaderId, previewId, urlInputId) {
    const fileInput = document.getElementById(inputId);
    const loader = document.getElementById(uploaderId);
    const preview = document.getElementById(previewId);
    const urlInput = document.getElementById(urlInputId);
    
    fileInput.addEventListener('change', async function(e) {
        const file = this.files[0];
        if (!file) return;
        
        // Show loader
        loader.style.display = 'block';
        
        // Create FormData
        const formData = new FormData();
        formData.append('image', file);
        
        try {
            const response = await fetch('upload_image.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Update hidden input with URL
                urlInput.value = result.url;
                
                // Show preview
                preview.innerHTML = `<img src="${result.url}" alt="Image Preview" style="max-width: 150px; max-height: 150px; border-radius: 8px;">`;
                
                // Show success message
                loader.innerHTML = '<i class="fas fa-check-circle" style="color: var(--success);"></i> Upload successful!';
                setTimeout(() => {
                    loader.style.display = 'none';
                }, 2000);
            } else {
                throw new Error(result.error || 'Upload failed');
            }
        } catch (error) {
            loader.innerHTML = '<i class="fas fa-exclamation-circle" style="color: var(--danger);"></i> ' + error.message;
            setTimeout(() => {
                loader.style.display = 'none';
            }, 3000);
        }
    });
}

// Initialize uploads
document.addEventListener('DOMContentLoaded', function() {
    setupImageUpload('profileImageInput', 'profileImageLoader', 'profileImagePreview', 'profileImageUrl');
    setupImageUpload('aboutImageInput', 'aboutImageLoader', 'aboutImagePreview', 'aboutImageUrl');
});
</script>