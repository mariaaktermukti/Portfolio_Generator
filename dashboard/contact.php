<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';

// Add contact_image column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE contact ADD COLUMN contact_image VARCHAR(255) DEFAULT ''");
} catch (PDOException $e) {
    // Column already exists
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $linkedin = $_POST['linkedin'] ?? '';
    $github = $_POST['github'] ?? '';
    $contact_image = $_POST['contact_image'] ?? '';

    $stmt = $pdo->prepare("SELECT id FROM contact WHERE user_id = ? AND is_deleted = 0");
    $stmt->execute([$user_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        $stmt = $pdo->prepare("UPDATE contact SET phone = ?, address = ?, linkedin = ?, github = ?, contact_image = ? WHERE user_id = ? AND is_deleted = 0");
        $stmt->execute([$phone, $address, $linkedin, $github, $contact_image, $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO contact (user_id, phone, address, linkedin, github, contact_image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $phone, $address, $linkedin, $github, $contact_image]);
    }
    
    $_SESSION['success_msg'] = "Contact information updated successfully!";
    header('Location: contact.php');
    exit;
}

if (isset($_SESSION['success_msg'])) {
    $success = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']);
}

$stmt = $pdo->prepare("SELECT * FROM contact WHERE user_id = ? AND is_deleted = 0");
$stmt->execute([$user_id]);
$contact = $stmt->fetch() ?: ['phone' => '', 'address' => '', 'linkedin' => '', 'github' => '', 'contact_image' => ''];

?>
<?php include 'inc/head.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<main class="main-content">
    <div class="glass-panel">
        <h2>Manage Contact Section</h2>
        
        <?php if ($success): ?>
            <div class="msg-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Contact Section Image</label>
                <div style="display: flex; gap: 1rem; align-items: flex-start;">
                    <div style="flex: 1;">
                        <div style="margin-bottom: 1rem;">
                            <label style="font-size: 0.9rem; color: #aaa; display: block; margin-bottom: 0.5rem;">From URL</label>
                            <input type="text" id="contactImageUrlInput" placeholder="Enter image URL" style="padding: 0.8rem; background: rgba(0,0,0,0.2); border: 1px solid var(--border); border-radius: 8px; color: #fff; width: 100%;">
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <label style="font-size: 0.9rem; color: #aaa; display: block; margin-bottom: 0.5rem;">Or Upload File</label>
                            <input type="file" id="contactImageInput" accept="image/*" style="padding: 0.8rem; background: rgba(0,0,0,0.2); border: 1px solid var(--border); border-radius: 8px; color: #fff; width: 100%; cursor: pointer;">
                        </div>
                        <input type="hidden" name="contact_image" id="contactImageUrl" value="<?php echo htmlspecialchars($contact['contact_image']); ?>">
                        <div id="contactImageLoader" style="display: none; margin-top: 0.5rem;">
                            <i class="fas fa-spinner fa-spin"></i> Processing image...
                        </div>
                        <div id="contactImagePreview" style="margin-top: 1rem;">
                            <?php if (!empty($contact['contact_image'])): ?>
                                <img src="<?php echo htmlspecialchars($contact['contact_image']); ?>" alt="Contact Preview" style="max-width: 150px; max-height: 150px; border-radius: 8px;">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($contact['phone']); ?>" placeholder="+1 234 567 890">
            </div>
            
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="3" placeholder="City, Country"><?php echo htmlspecialchars($contact['address']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>LinkedIn URL</label>
                <input type="url" name="linkedin" value="<?php echo htmlspecialchars($contact['linkedin']); ?>" placeholder="https://linkedin.com/in/username">
            </div>
            
            <div class="form-group">
                <label>GitHub URL</label>
                <input type="url" name="github" value="<?php echo htmlspecialchars($contact['github']); ?>" placeholder="https://github.com/username">
            </div>
            
            <button type="submit" class="btn">Save Changes</button>
        </form>
    </div>
</main>

<?php include 'inc/foot.php'; ?>

<script>
function setupImageUpload(inputId, urlInputId, uploaderId, previewId, urlInputFieldId) {
    const fileInput = document.getElementById(inputId);
    const urlInputField = document.getElementById(urlInputFieldId);
    const loader = document.getElementById(uploaderId);
    const preview = document.getElementById(previewId);
    const urlInput = document.getElementById(urlInputId);
    
    // Handle URL input
    urlInputField.addEventListener('blur', function() {
        const url = this.value.trim();
        if (url) {
            // Update hidden input with URL
            urlInput.value = url;
            
            // Show preview
            preview.innerHTML = `<img src="${url}" alt="Image Preview" style="max-width: 150px; max-height: 150px; border-radius: 8px;" onerror="this.parentElement.innerHTML='<p style=\"color: #f44;\">Failed to load image</p>'">`;
            
            // Show success message
            loader.style.display = 'block';
            loader.innerHTML = '<i class="fas fa-check-circle" style="color: var(--success);"></i> Image URL set!';
            setTimeout(() => {
                loader.style.display = 'none';
            }, 2000);
        }
    });
    
    // Handle file upload
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
                
                // Clear URL input field
                urlInputField.value = '';
                
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
    setupImageUpload('contactImageInput', 'contactImageUrl', 'contactImageLoader', 'contactImagePreview', 'contactImageUrlInput');
});
</script>