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

// Default sections available for ordering
$default_sections = ['about', 'skills', 'work', 'projects', 'education', 'achievements', 'blogs', 'research', 'publications', 'contact', 'reviews'];
$section_labels = [
    'about' => ['About Me', 'Biography and personal story'],
    'skills' => ['Skills', 'Display skills with proficiency levels'],
    'work' => ['Work Experience', 'Professional work history'],
    'projects' => ['Projects', 'Showcase your best projects'],
    'education' => ['Education', 'Academic background'],
    'achievements' => ['Achievements', 'Awards and recognitions'],
    'blogs' => ['Blogs', 'Articles and blog posts'],
    'research' => ['Research', 'Academic and scientific research'],
    'publications' => ['Publications', 'Published papers and books'],
    'contact' => ['Contact', 'Contact form and details'],
    'reviews' => ['Reviews', 'Testimonials from others']
];

// Add section_order column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN section_order VARCHAR(500) DEFAULT ''");
} catch (PDOException $e) {
    // Column already exists
}

// Add section_hidden column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN section_hidden VARCHAR(500) DEFAULT ''");
} catch (PDOException $e) {
    // Column already exists
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['section_order'])) {
    $new_order = $_POST['section_order'];
    $new_hidden = isset($_POST['section_hidden']) ? $_POST['section_hidden'] : '';
    $stmt = $pdo->prepare("UPDATE users SET section_order = ?, section_hidden = ? WHERE id = ?");
    if ($stmt->execute([$new_order, $new_hidden, $user_id])) {
        $success = "Portfolio layout updated successfully!";
    } else {
        $error = "Failed to update portfolio layout.";
    }
}

// Fetch current order & hidden sections
$stmt = $pdo->prepare("SELECT section_order, section_hidden FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$current_order_str = $row['section_order'] ?? '';
$current_hidden_str = $row['section_hidden'] ?? '';

if (empty($current_order_str)) {
    $current_order = $default_sections;
} else {
    $current_order = explode(',', $current_order_str);
    // Add any missing default sections to the end
    foreach ($default_sections as $sec) {
        if (!in_array($sec, $current_order)) {
            $current_order[] = $sec;
        }
    }
}
$current_hidden = empty($current_hidden_str) ? [] : explode(',', $current_hidden_str);

?>

<?php include 'inc/head.php'; ?>
<?php include 'inc/sidebar.php'; ?>

<main class="main-content">
    <header class="top-nav" style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="font-size: 2rem; margin-bottom: 0.2rem;">Section Order</h1>
            <p style="color: var(--text-muted); font-size: 1rem;">Customize the order of sections on your portfolio</p>
        </div>
    </header>

    <div class="glass-panel" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); padding: 2rem;">
        <div style="margin-bottom: 1.5rem;">
            <h2 style="display: flex; align-items: center; gap: 0.5rem; font-size: 1.25rem;"><i class="fas fa-list-ul"></i> Portfolio Sections</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem;">Drag sections to reorder them, or use the position number.</p>
        </div>

        <?php if ($success): ?>
            <div class="msg-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="msg-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" id="order-form">
            <input type="hidden" name="section_order" id="section_order_input" value="">
            <input type="hidden" name="section_hidden" id="section_hidden_input" value="">

            <div id="sortable-list" style="display: flex; flex-direction: column; gap: 1rem;">
                <?php $pos = 1; foreach ($current_order as $section): ?>
                    <?php if (isset($section_labels[$section])): ?>
                        <div class="sortable-item" data-id="<?php echo htmlspecialchars($section); ?>" 
                             style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between; cursor: grab; transition: all 0.2s ease;">
                            
                            <!-- Left: Position Box -->
                            <div style="display: flex; align-items: center; gap: 1.5rem;">
                                <div style="display: flex; flex-direction: column; align-items: center; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 0.4rem 0.6rem; background: rgba(0,0,0,0.2); min-width: 60px;">
                                    <span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">Position</span>
                                    <input type="text" class="pos-input" value="<?php echo $pos; ?>" readonly style="border: none; background: transparent; text-align: center; font-weight: 600; font-size: 1.1rem; color: #fff; width: 30px; padding: 0; outline: none;">
                                </div>
                                
                                <!-- Middle: Titles -->
                                <div>
                                    <div style="font-size: 1.1rem; font-weight: 700; color: #fff; margin-bottom: 0.2rem;">
                                        <?php echo htmlspecialchars($section_labels[$section][0]); ?>
                                    </div>
                                    <div style="font-size: 0.9rem; color: var(--text-muted);">
                                        <?php echo htmlspecialchars($section_labels[$section][1]); ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Controls -->
                            <div style="display: flex; align-items: center; gap: 1.5rem;">
                                <!-- Up/Down Arrows -->
                                <div style="display: flex; flex-direction: column; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; overflow: hidden; background: rgba(0,0,0,0.1);">
                                    <button type="button" onclick="moveItemUp(this)" style="background: transparent; border: none; border-bottom: 1px solid rgba(255,255,255,0.1); padding: 0.4rem 0.6rem; cursor: pointer; color: var(--text-muted); transition: background 0.2s ease;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'"><i class="fas fa-arrow-up"></i></button>
                                    <button type="button" onclick="moveItemDown(this)" style="background: transparent; border: none; padding: 0.4rem 0.6rem; cursor: pointer; color: var(--text-muted); transition: background 0.2s ease;" onmouseover="this.style.background='rgba(255,255,255,0.05)'" onmouseout="this.style.background='transparent'"><i class="fas fa-arrow-down"></i></button>
                                </div>
                            </div>
                        </div>
                    <?php $pos++; endif; ?>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
                <button type="button" class="btn" style="background: #3b82f6; color: white; padding: 0.75rem 1.5rem; font-size: 1rem; font-weight: 600; border-radius: 8px; border: none; cursor: pointer; transition: opacity 0.2s ease;" onclick="saveOrder()" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Save Order</button>
            </div>
        </form>
    </div>
</main>

<style>
    .sortable-item:hover {
        background: rgba(255,255,255,0.06) !important;
        border-color: rgba(79, 70, 229, 0.4) !important;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
    var el = document.getElementById('sortable-list');
    var sortable = Sortable.create(el, {
        animation: 150,
        handle: '.sortable-item',
        onEnd: function() {
            updatePositions();
        }
    });

    function updatePositions() {
        var items = el.querySelectorAll('.sortable-item');
        items.forEach(function(item, index) {
            item.querySelector('.pos-input').value = index + 1;
        });
    }

    function moveItemUp(btn) {
        var item = btn.closest('.sortable-item');
        if (item.previousElementSibling) {
            el.insertBefore(item, item.previousElementSibling);
            updatePositions();
        }
    }

    function moveItemDown(btn) {
        var item = btn.closest('.sortable-item');
        if (item.nextElementSibling) {
            el.insertBefore(item.nextElementSibling, item);
            updatePositions();
        }
    }

    function saveOrder() {
        var items = el.querySelectorAll('.sortable-item');
        var order = [];
        var hidden = [];
        items.forEach(function(item) {
            var id = item.getAttribute('data-id');
            order.push(id);
        });
        document.getElementById('section_order_input').value = order.join(',');
        document.getElementById('section_hidden_input').value = hidden.join(',');
        document.getElementById('order-form').submit();
    }
</script>

<?php include 'inc/foot.php'; ?>