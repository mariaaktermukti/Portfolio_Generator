<?php
require_once '../config/db.php';

function getDirectImageUrl($url)
{
    if (empty($url))
        return '';

    // 1. Google Drive Links -> Convert to direct lh3.googleusercontent image
    if (
        preg_match('/drive\.google\.com\/file\/d\/([^\/]+)/', $url, $matches) ||
        preg_match('/drive\.google\.com\/open\?id=([^&]+)/', $url, $matches) ||
        preg_match('/drive\.google\.com\/uc\?.*id=([^&]+)/', $url, $matches)
    ) {
        return 'https://lh3.googleusercontent.com/d/' . $matches[1];
    }

    // 2. Already an Image URL -> return as is
    if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg)(\?.*)?$/i', $url)) {
        return $url;
    }

    // 3. Webpage links (HackerRank, Coursera, Google, Credly certificates/profiles)
    // We use a free public OpenGraph Image API to fetch the image preview of the URL!
    if (
        strpos($url, 'hackerrank.com') !== false ||
        strpos($url, 'google.com') !== false ||
        strpos($url, 'coursera.org') !== false ||
        strpos($url, 'credly.com') !== false
    ) {
        return 'https://v1.opengraph.11ty.dev/' . urlencode($url) . '/large/';
    }

    return $url;
}

// Add contact_image column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE contact ADD COLUMN contact_image VARCHAR(255) DEFAULT ''");
} catch (PDOException $e) {
    // Column already exists
}

// Add status column to reviews if it doesn't exist
try {
    $pdo->exec("ALTER TABLE reviews ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
} catch (PDOException $e) {
    // Column already exists
}

$username = isset($_GET['user']) ? $_GET['user'] : '';
$review_submitted = isset($_GET['review_submitted']) && $_GET['review_submitted'] === '1';

if (!$username) {
    die("User not specified.");
}

// Fetch user, about, contact via JOIN
$stmt = $pdo->prepare("
    SELECT u.id as user_id, u.username, u.email, 
           a.bio, a.title, a.profile_image, a.about_image, 
           c.phone, c.address, c.linkedin, c.github, c.contact_image
    FROM users u
    LEFT JOIN about a ON u.id = a.user_id AND a.is_deleted = 0
    LEFT JOIN contact c ON u.id = c.user_id AND c.is_deleted = 0
    WHERE u.username = ? AND u.account_status = 'approved' AND u.is_deleted = 0
");
$stmt->execute([$username]);
$profile = $stmt->fetch();

if (!$profile) {
    die("Portfolio not found or not approved.");
}

$user_id = $profile['user_id'];

// Track view
$ip_address = $_SERVER['REMOTE_ADDR'];
$stmt_view = $pdo->prepare("INSERT INTO portfolio_views (user_id, ip_address) VALUES (?, ?)");
$stmt_view->execute([$user_id, $ip_address]);

// Fetch other data
$stmt_edu = $pdo->prepare("SELECT e.* FROM education e JOIN users u ON e.user_id = u.id WHERE u.id = ? AND e.is_deleted = 0 ORDER BY e.start_date DESC");
$stmt_edu->execute([$user_id]);
$education = $stmt_edu->fetchAll();

$stmt_skills = $pdo->prepare("SELECT s.* FROM skills s JOIN users u ON s.user_id = u.id WHERE u.id = ? AND s.is_deleted = 0 ORDER BY s.proficiency DESC");
$stmt_skills->execute([$user_id]);
$skills = $stmt_skills->fetchAll();

$stmt_work = $pdo->prepare("SELECT w.* FROM work_experience w JOIN users u ON w.user_id = u.id WHERE u.id = ? AND w.is_deleted = 0 ORDER BY w.start_date DESC");
$stmt_work->execute([$user_id]);
$work = $stmt_work->fetchAll();

$stmt_ach = $pdo->prepare("SELECT a.* FROM achievements a JOIN users u ON a.user_id = u.id WHERE u.id = ? AND a.is_deleted = 0 ORDER BY a.date_earned DESC");
$stmt_ach->execute([$user_id]);
$achievements = $stmt_ach->fetchAll();

$stmt_proj = $pdo->prepare("SELECT p.* FROM projects p JOIN users u ON p.user_id = u.id WHERE u.id = ? AND p.is_deleted = 0 ORDER BY p.created_at DESC");
$stmt_proj->execute([$user_id]);
$projects = $stmt_proj->fetchAll();

$stmt_blogs = $pdo->prepare("SELECT b.* FROM blogs b JOIN users u ON b.user_id = u.id WHERE u.id = ? AND b.is_deleted = 0 ORDER BY b.created_at DESC");
$stmt_blogs->execute([$user_id]);
$blogs = $stmt_blogs->fetchAll();

// Fetch Research
$stmt_research = $pdo->prepare("SELECT r.* FROM research r JOIN users u ON r.user_id = u.id WHERE u.id = ? AND r.is_deleted = 0 ORDER BY r.publication_date DESC, r.created_at DESC");
$stmt_research->execute([$user_id]);
$researches = $stmt_research->fetchAll();

// Fetch Publications
$stmt_pub = $pdo->prepare("SELECT p.* FROM publications p JOIN users u ON p.user_id = u.id WHERE u.id = ? AND p.is_deleted = 0 ORDER BY p.publish_date DESC, p.created_at DESC");
$stmt_pub->execute([$user_id]);
$publications = $stmt_pub->fetchAll();

// Add status column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE reviews ADD COLUMN status VARCHAR(20) DEFAULT 'pending'");
} catch (PDOException $e) {
    // Column already exists
}

// Fetch only APPROVED Reviews
$stmt_reviews = $pdo->prepare("SELECT * FROM reviews WHERE user_id = ? AND status = 'approved' ORDER BY created_at DESC");
$stmt_reviews->execute([$user_id]);
$reviews = $stmt_reviews->fetchAll();

$stmt_avg_rating = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE user_id = ?");
$stmt_avg_rating->execute([$user_id]);
$avg_rating_row = $stmt_avg_rating->fetch();
$avg_rating = $avg_rating_row['avg_rating'] ? round($avg_rating_row['avg_rating'], 1) : 'No ratings yet';

// Fetch Section Order
$stmt_order = $pdo->prepare("SELECT section_order FROM users WHERE id = ?");
$stmt_order->execute([$user_id]);
$section_order_str = $stmt_order->fetchColumn();

$default_sections = ['about', 'skills', 'work', 'projects', 'education', 'achievements', 'blogs', 'research', 'publications', 'contact', 'reviews'];
if (empty($section_order_str)) {
    $section_order = $default_sections;
} else {
    $section_order = explode(',', $section_order_str);
    foreach ($default_sections as $sec) {
        if (!in_array($sec, $section_order)) {
            $section_order[] = $sec;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile['username']); ?>'s Portfolio</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Desktop first - 1280px max width */
        .portfolio-container {
            max-width: 1280px;
            width: 100%;
            margin: 0 auto;
            padding: 2rem 0;
            box-sizing: border-box;
        }

        /* Navigation Header Styles - Professional & Compact */
        .portfolio-nav {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            padding: 0;
        }

        .portfolio-nav-inner {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0 1rem;
        }

        .portfolio-nav ul {
            list-style: none;
            display: flex;
            margin: 0;
            padding: 0;
            align-items: center;
        }

        .portfolio-nav > .portfolio-nav-inner > ul > li {
            position: relative;
        }

        .portfolio-nav a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1.25rem 1rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: color 0.3s ease;
            white-space: nowrap;
        }

        .portfolio-nav a:hover,
        .portfolio-nav a.active {
            color: #fff;
        }

        .portfolio-nav a.active::after,
        .portfolio-nav li.nav-dropdown.active-parent > a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 70%;
            height: 3px;
            background: var(--accent);
            border-radius: 3px 3px 0 0;
            box-shadow: 0 -2px 10px rgba(59, 130, 246, 0.4);
        }

        /* Dropdown Styles */
        .nav-dropdown .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(15px);
            background: rgba(30, 41, 59, 0.98);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            min-width: 180px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            padding: 0.5rem 0;
        }

        .nav-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateX(-50%) translateY(0);
        }

        .nav-dropdown .dropdown-menu li {
            width: 100%;
        }

        .nav-dropdown .dropdown-menu a {
            padding: 0.8rem 1.2rem;
            width: 100%;
            box-sizing: border-box;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            font-size: 0.9rem;
        }

        .nav-dropdown .dropdown-menu a:last-child {
            border-bottom: none;
        }

        .nav-dropdown .dropdown-menu a:hover {
            background: rgba(255, 255, 255, 0.03);
            color: var(--accent);
        }

        .nav-dropdown .dropdown-menu a.active {
            color: var(--accent);
        }

        .nav-dropdown .dropdown-menu a.active::after {
            display: none;
        }

        html {
            scroll-behavior: smooth;
        }

        /* Mobile Adjustments */
        @media (max-width: 1023px) {
            .portfolio-nav-inner {
                justify-content: flex-start;
                overflow-x: auto;
                -ms-overflow-style: none;  
                scrollbar-width: none; 
            }
            .portfolio-nav-inner::-webkit-scrollbar {
                display: none;
            }
            .portfolio-nav a {
                padding: 1rem 0.75rem;
                font-size: 0.85rem;
            }
            /* Change Dropdown to Inline on small screens to avoid clipping */
            .nav-dropdown .dropdown-menu {
                display: none;
                position: static;
                transform: none;
                box-shadow: inset 0 0 10px rgba(0,0,0,0.5);
                border: none;
                border-radius: 0;
                background: rgba(0, 0, 0, 0.3);
                flex-direction: row; 
                padding: 0;
                min-width: auto;
                opacity: 1;
                visibility: visible;
            }
            .nav-dropdown:hover .dropdown-menu {
                display: flex;
            }
            .nav-dropdown .dropdown-menu a {
                border-bottom: none;
                border-left: 1px solid rgba(255, 255, 255, 0.05);
                padding: 1rem 0.75rem;
            }
        }
    </style>
</head>

<body>
    <?php
    // Prepare menu items dynamically and group Academic items together
    $menu_items = [];
    $academic_items = [];
    $has_academic = false;
    $academic_index = -1;

    foreach ($section_order as $sec) {
        if (in_array($sec, ['education', 'research', 'publications'])) {
            if ($sec == 'education' && $education) {
                $academic_items[] = ['id' => 'education', 'icon' => 'fa-graduation-cap', 'label' => 'Education'];
            } elseif ($sec == 'research' && $researches) {
                $academic_items[] = ['id' => 'research', 'icon' => 'fa-microscope', 'label' => 'Research'];
            } elseif ($sec == 'publications' && $publications) {
                $academic_items[] = ['id' => 'publications', 'icon' => 'fa-book', 'label' => 'Publications'];
            }
            
            if (!$has_academic && count($academic_items) > 0) {
                $has_academic = true;
                $academic_index = count($menu_items);
                $menu_items[] = 'ACADEMICS_PLACEHOLDER';
            }
        } else {
            if ($sec == 'about') $menu_items[] = ['id' => 'about', 'icon' => 'fa-user', 'label' => 'About'];
            elseif ($sec == 'skills' && $skills) $menu_items[] = ['id' => 'skills', 'icon' => 'fa-star', 'label' => 'Skills'];
            elseif ($sec == 'work' && $work) $menu_items[] = ['id' => 'work', 'icon' => 'fa-briefcase', 'label' => 'Work'];
            elseif ($sec == 'projects' && $projects) $menu_items[] = ['id' => 'projects', 'icon' => 'fa-project-diagram', 'label' => 'Projects'];
            elseif ($sec == 'achievements' && $achievements) $menu_items[] = ['id' => 'achievements', 'icon' => 'fa-trophy', 'label' => 'Achievements'];
            elseif ($sec == 'blogs' && $blogs) $menu_items[] = ['id' => 'blogs', 'icon' => 'fa-blog', 'label' => 'Blogs'];
            elseif ($sec == 'contact') $menu_items[] = ['id' => 'contact', 'icon' => 'fa-envelope', 'label' => 'Contact'];
            elseif ($sec == 'reviews' && $reviews) $menu_items[] = ['id' => 'reviews', 'icon' => 'fa-comments', 'label' => 'Reviews'];
        }
    }

    if ($has_academic) {
        $menu_items[$academic_index] = [
            'type' => 'dropdown',
            'id' => 'academic',
            'icon' => 'fa-university',
            'label' => 'Academics',
            'items' => $academic_items
        ];
    }
    ?>

    <!-- Navigation Header -->
    <nav class="portfolio-nav">
        <div class="portfolio-nav-inner">
            <ul>
                <li><a href="#hero" onclick="scrollToSection('hero')" class="nav-link active"><i class="fas fa-home"></i> Home</a></li>
                <?php foreach ($menu_items as $item): ?>
                    <?php if (is_array($item) && isset($item['type']) && $item['type'] === 'dropdown'): ?>
                        <li class="nav-dropdown">
                            <a href="javascript:void(0)" class="dropdown-trigger"><i class="fas <?php echo $item['icon']; ?>"></i> <?php echo $item['label']; ?> <i class="fas fa-chevron-down" style="font-size: 0.7em; margin-left: 4px;"></i></a>
                            <ul class="dropdown-menu">
                                <?php foreach ($item['items'] as $subitem): ?>
                                    <li><a href="#<?php echo $subitem['id']; ?>" onclick="scrollToSection('<?php echo $subitem['id']; ?>')" class="nav-link"><i class="fas <?php echo $subitem['icon']; ?>"></i> <?php echo $subitem['label']; ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php elseif (is_array($item)): ?>
                        <li><a href="#<?php echo $item['id']; ?>" onclick="scrollToSection('<?php echo $item['id']; ?>')" class="nav-link"><i class="fas <?php echo $item['icon']; ?>"></i> <?php echo $item['label']; ?></a></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>

    <!-- Success Notification -->
    <?php if ($review_submitted): ?>
        <div id="toastNotification" style="position: fixed; top: 5rem; right: 2rem; z-index: 9999; max-width: 400px; background: rgba(34, 197, 94, 0.95); backdrop-filter: blur(10px); border-left: 4px solid #16a34a; border-radius: 8px; padding: 1.25rem 1.5rem; display: flex; align-items: flex-start; gap: 1rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2); animation: toastSlideIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;">
            <i class="fas fa-check-circle" style="font-size: 1.5rem; color: #fff; margin-top: 0.1rem;"></i>
            <div>
                <h3 style="margin: 0 0 0.25rem 0; color: #fff; font-weight: 600; font-size: 1.1rem;">Review Submitted!</h3>
                <p style="margin: 0; color: rgba(255, 255, 255, 0.9); font-size: 0.9rem; line-height: 1.4;">Thank you for your review! It will appear on this portfolio after admin approval.</p>
            </div>
        </div>
        <style>
            @keyframes toastSlideIn {
                from { opacity: 0; transform: translateX(120%); }
                to { opacity: 1; transform: translateX(0); }
            }
            @keyframes toastSlideOut {
                from { opacity: 1; transform: translateX(0); }
                to { opacity: 0; transform: translateX(120%); }
            }
        </style>
        <script>
            setTimeout(() => {
                const toast = document.getElementById('toastNotification');
                if (toast) {
                    toast.style.animation = 'toastSlideOut 0.5s ease-in forwards';
                    setTimeout(() => toast.remove(), 500);
                }
                
                // Clean up URL without reloading the page
                const url = new URL(window.location);
                url.searchParams.delete('review_submitted');
                window.history.replaceState({}, document.title, url);
            }, 3000);
        </script>
    <?php endif; ?>

    <div class="portfolio-container">
        <!-- Hero Section -->
        <header class="hero-section glass-panel" id="hero"
            style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center; padding: 3rem 2rem; border-radius: 16px; background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));">

            <!-- Left Content -->
            <div style="display: flex; flex-direction: column; justify-content: center;">
                <div style="margin-bottom: 1.5rem;">
                    <p style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 0.5rem;">Welcome to my
                        portfolio</p>
                    <h1
                        style="font-size: clamp(2rem, 5vw, 3.5rem); font-weight: 700; line-height: 1.2; margin-bottom: 0.5rem; color: #fff;">
                        Hi, I am <span
                            style="background: linear-gradient(135deg, #3b82f6, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"><?php echo htmlspecialchars($profile['username']); ?></span>
                    </h1>
                    <h2 style="font-size: clamp(1.2rem, 3vw, 2rem); color: var(--accent); font-weight: 500; margin: 0;">
                        <?php echo htmlspecialchars(isset($profile['title']) ? $profile['title'] : 'Portfolio'); ?>
                    </h2>
                </div>

                <!-- CTA Buttons & Contact -->
                <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
                    <a href="../export/export_pdf.php?user=<?php echo urlencode($username); ?>" class="btn"
                        style="display: inline-flex; width: fit-content !important; align-items: center; gap: 0.5rem; padding: 0.6rem 1.2rem; background: var(--accent); color: #fff; text-decoration: none; border-radius: 8px; font-weight: 600; transition: all 0.3s; border: 2px solid var(--accent); white-space: nowrap; font-size: 0.95rem;">
                        <i class="fas fa-download"></i> Download Resume
                    </a>
                </div>

                <!-- Social Links -->
                <div class="social-links" style="display: flex; gap: 1.2rem; margin-bottom: 1.5rem;">
                    <?php if ($profile['email']): ?>
                        <a href="mailto:<?php echo htmlspecialchars($profile['email']); ?>"
                            style="font-size: 1.3rem; color: var(--text-muted); transition: color 0.3s, transform 0.3s; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: rgba(59, 130, 246, 0.1); border-radius: 50%; border: 1px solid var(--border);"
                            onmouseover="this.style.color='var(--accent)'; this.style.background='rgba(59, 130, 246, 0.2)'; this.style.transform='scale(1.1)';"
                            onmouseout="this.style.color='var(--text-muted)'; this.style.background='rgba(59, 130, 246, 0.1)'; this.style.transform='scale(1)';">
                            <i class="fas fa-envelope"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($profile['linkedin']): ?>
                        <a href="<?php echo htmlspecialchars($profile['linkedin']); ?>" target="_blank"
                            style="font-size: 1.3rem; color: var(--text-muted); transition: color 0.3s, transform 0.3s; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: rgba(59, 130, 246, 0.1); border-radius: 50%; border: 1px solid var(--border);"
                            onmouseover="this.style.color='var(--accent)'; this.style.background='rgba(59, 130, 246, 0.2)'; this.style.transform='scale(1.1)';"
                            onmouseout="this.style.color='var(--text-muted)'; this.style.background='rgba(59, 130, 246, 0.1)'; this.style.transform='scale(1)';">
                            <i class="fab fa-linkedin"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($profile['github']): ?>
                        <a href="<?php echo htmlspecialchars($profile['github']); ?>" target="_blank"
                            style="font-size: 1.3rem; color: var(--text-muted); transition: color 0.3s, transform 0.3s; display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: rgba(59, 130, 246, 0.1); border-radius: 50%; border: 1px solid var(--border);"
                            onmouseover="this.style.color='var(--accent)'; this.style.background='rgba(59, 130, 246, 0.2)'; this.style.transform='scale(1.1)';"
                            onmouseout="this.style.color='var(--text-muted)'; this.style.background='rgba(59, 130, 246, 0.1)'; this.style.transform='scale(1)';">
                            <i class="fab fa-github"></i>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Location & Rating -->
                <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                    <?php if ($profile['address']): ?>
                        <div
                            style="display: flex; align-items: center; gap: 0.5rem; font-size: 1rem; color: var(--text-muted);">
                            <i class="fas fa-map-marker-alt" style="color: var(--accent);"></i>
                            <span><?php echo htmlspecialchars($profile['address']); ?></span>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="color: #fbbf24;"><i class="fas fa-star"></i></span>
                        <span style="color: var(--text-muted);"><?php echo $avg_rating; ?>/5 <span
                                style="color: #888; font-size: 0.9rem;">rating</span></span>
                    </div>
                </div>
            </div>

            <!-- Right Content - Profile Image -->
            <div style="display: flex; align-items: center; justify-content: center;">
                <?php if ($profile['profile_image']): ?>
                    <div style="position: relative; width: 100%; max-width: 350px;">
                        <!-- Rotating light glow layer 1 -->
                        <div
                            style="position: absolute; inset: -15px; background: conic-gradient(from 0deg, #3b82f6, #8b5cf6, #3b82f6); border-radius: 50%; opacity: 0.4; animation: rotateBg 6s linear infinite;">
                        </div>
                        <!-- Expanding pulse layer -->
                        <div
                            style="position: absolute; inset: -10px; background: radial-gradient(circle, rgba(59, 130, 246, 0.6), transparent); border-radius: 50%; opacity: 0.4; animation: expandPulse 4s ease-in-out infinite;">
                        </div>
                        <!-- Inner rotating light -->
                        <div
                            style="position: absolute; inset: -5px; background: linear-gradient(135deg, var(--accent), #8b5cf6); border-radius: 50%; opacity: 0.3; animation: rotateBg 8s linear infinite;">
                        </div>
                        <img src="<?php echo htmlspecialchars(getDirectImageUrl($profile['profile_image'])); ?>"
                            alt="Profile"
                            style="width: 100%; max-width: 320px; aspect-ratio: 1; border-radius: 50%; object-fit: cover; border: 6px solid var(--accent); box-shadow: 0 0 50px rgba(59, 130, 246, 0.6), 0 0 100px rgba(139, 92, 246, 0.3), inset 0 0 30px rgba(59, 130, 246, 0.2); position: relative; z-index: 1;">
                    </div>
                <?php else: ?>
                    <div style="position: relative; width: 100%; max-width: 350px;">
                        <!-- Rotating light glow layer 1 -->
                        <div
                            style="position: absolute; inset: -15px; background: conic-gradient(from 0deg, #3b82f6, #8b5cf6, #3b82f6); border-radius: 50%; opacity: 0.4; animation: rotateBg 6s linear infinite;">
                        </div>
                        <!-- Expanding pulse layer -->
                        <div
                            style="position: absolute; inset: -10px; background: radial-gradient(circle, rgba(59, 130, 246, 0.6), transparent); border-radius: 50%; opacity: 0.4; animation: expandPulse 4s ease-in-out infinite;">
                        </div>
                        <!-- Inner rotating light -->
                        <div
                            style="position: absolute; inset: -5px; background: linear-gradient(135deg, var(--accent), #8b5cf6); border-radius: 50%; opacity: 0.3; animation: rotateBg 8s linear infinite;">
                        </div>
                        <div
                            style="width: 100%; max-width: 320px; aspect-ratio: 1; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--bg-secondary); font-size: 5rem; color: var(--text-muted); border: 6px solid var(--accent); box-shadow: 0 0 50px rgba(59, 130, 246, 0.6), 0 0 100px rgba(139, 92, 246, 0.3), inset 0 0 30px rgba(59, 130, 246, 0.2); position: relative; z-index: 1;">
                            <i class="fas fa-user"></i>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <style>
                @keyframes rotateBg {
                    0% {
                        transform: rotate(0deg);
                    }

                    100% {
                        transform: rotate(360deg);
                    }
                }

                @keyframes expandPulse {

                    0%,
                    100% {
                        transform: scale(1);
                        opacity: 0.2;
                    }

                    50% {
                        transform: scale(1.15);
                        opacity: 0.5;
                    }
                }

                @keyframes floatImage {

                    0%,
                    100% {
                        transform: translateY(0);
                    }

                    50% {
                        transform: translateY(-8px);
                    }
                }

                /* Desktop & Large Devices */
                @media (min-width: 1024px) {

                    .hero-section,
                    .about-section {
                        grid-template-columns: 1fr 1fr !important;
                        gap: 3rem !important;
                        padding: 3rem 2rem !important;
                    }

                    #contact {
                        grid-template-columns: 1.5fr 1fr !important;
                        gap: 3rem !important;
                        padding: 3rem 2rem !important;
                    }
                }

                /* Tablet Devices (1024px and below) */
                @media (max-width: 1023px) {

                    .hero-section,
                    .about-section {
                        grid-template-columns: 1fr !important;
                        gap: 2rem !important;
                        padding: 2.5rem 1.5rem !important;
                    }

                    #contact {
                        grid-template-columns: 1fr !important;
                        gap: 2rem !important;
                        padding: 2.5rem 1.5rem !important;
                    }

                    .hero-section h1 {
                        font-size: 2.2rem !important;
                    }

                    .hero-section h2 {
                        font-size: 1.4rem !important;
                    }
                }

                /* Small Tablets & Large Phones (768px - 1023px) */
                @media (max-width: 767px) {

                    .hero-section,
                    .about-section {
                        grid-template-columns: 1fr !important;
                        gap: 1.5rem !important;
                        padding: 2rem 1.25rem !important;
                    }

                    #contact {
                        grid-template-columns: 1fr !important;
                        gap: 1.5rem !important;
                        padding: 2rem 1.25rem !important;
                    }

                    .hero-section h1 {
                        font-size: 1.9rem !important;
                    }

                    .hero-section h2 {
                        font-size: 1.2rem !important;
                    }
                }

                /* Phones (480px - 767px) */
                @media (max-width: 767px) {
                    .hero-section {
                        padding: 1.5rem 1rem !important;
                        gap: 1.25rem !important;
                    }

                    .hero-section h1 {
                        font-size: 1.7rem !important;
                    }

                    .hero-section h2 {
                        font-size: 1.1rem !important;
                    }

                    .hero-section .social-links {
                        gap: 0.75rem !important;
                    }
                }

                /* Small Phones (Below 480px) */
                @media (max-width: 479px) {

                    .hero-section,
                    .about-section {
                        grid-template-columns: 1fr !important;
                        gap: 1rem !important;
                        padding: 1.25rem 0.75rem !important;
                    }

                    #contact {
                        grid-template-columns: 1fr !important;
                        gap: 1rem !important;
                        padding: 1.25rem 0.75rem !important;
                    }

                    .hero-section h1 {
                        font-size: 1.5rem !important;
                    }

                    .hero-section h2 {
                        font-size: 1rem !important;
                    }

                    .hero-section .social-links {
                        gap: 0.5rem !important;
                    }
                }
            </style>
        </header>

        <?php foreach ($section_order as $sec): switch ($sec): case 'about': ?>
        <!-- About Me Section -->
        <section class="glass-panel about-section" id="about"
            style="animation-delay: 0.05s; display: grid; grid-template-columns: 1fr 1.5fr; gap: 3rem; align-items: center; margin-top: 2rem;">
            <!-- Left Content - Animated Image -->
            <div style="display: flex; align-items: center; justify-content: center;">
                <?php
                $display_image = !empty($profile['about_image']) ? getDirectImageUrl($profile['about_image']) : getDirectImageUrl($profile['profile_image']);
                if ($display_image):
                    ?>
                    <div
                        style="position: relative; width: 100%; max-width: 280px; animation: floatImage 4s ease-in-out infinite;">
                        <img src="<?php echo htmlspecialchars($display_image); ?>" alt="About Me"
                            style="width: 100%; border-radius: 20px; object-fit: cover;">
                    </div>
                <?php else: ?>
                    <div
                        style="position: relative; width: 100%; max-width: 280px; aspect-ratio: 1; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 5rem; color: var(--text-muted); animation: floatImage 4s ease-in-out infinite;">
                        <i class="fas fa-user-circle"></i>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Content - Bio -->
            <div>
                <h2
                    style="color: var(--accent); margin-bottom: 1.5rem; font-size: 2rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-user"></i> About Me
                </h2>
                <div style="font-size: 1.1rem; color: var(--text-muted); line-height: 1.8;">
                    <?php echo nl2br(htmlspecialchars(isset($profile['bio']) ? $profile['bio'] : '')); ?>
                </div>
            </div>
        </section>
        <?php break; case 'skills': ?>
        <!-- Skills -->
        <?php if ($skills): ?>
            <style>
                .skill-card {
                    background: rgba(255, 255, 255, 0.02);
                    border: 1px solid rgba(255, 255, 255, 0.05);
                    border-radius: 16px;
                    padding: 1.5rem 1rem;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    gap: 1.2rem;
                    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                    position: relative;
                    overflow: hidden;
                }

                .skill-card::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 100%;
                    background: radial-gradient(circle at 50% 0%, rgba(139, 92, 246, 0.15), transparent 70%);
                    opacity: 0;
                    transition: opacity 0.4s ease;
                    z-index: 0;
                }

                .skill-card:hover {
                    transform: translateY(-6px);
                    background: rgba(255, 255, 255, 0.04);
                    border-color: rgba(139, 92, 246, 0.4);
                    box-shadow: 0 10px 30px -10px rgba(139, 92, 246, 0.3);
                }

                .skill-card:hover::before {
                    opacity: 1;
                }

                .skill-card>* {
                    z-index: 1;
                }

                .skill-icon-wrapper {
                    width: 68px;
                    height: 68px;
                    border-radius: 16px;
                    background: rgba(15, 23, 42, 0.6);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    padding: 12px;
                    transition: all 0.4s ease;
                    box-shadow: inset 0 2px 10px rgba(255, 255, 255, 0.05);
                }

                .skill-card:hover .skill-icon-wrapper {
                    transform: scale(1.1) rotate(5deg);
                    border-color: rgba(139, 92, 246, 0.5);
                    background: rgba(15, 23, 42, 0.8);
                    box-shadow: 0 0 20px rgba(139, 92, 246, 0.2), inset 0 2px 10px rgba(255, 255, 255, 0.1);
                }

                .skill-progress-bg {
                    width: 100%;
                    height: 6px;
                    background: rgba(255, 255, 255, 0.08);
                    border-radius: 10px;
                    overflow: hidden;
                    margin-bottom: 0.5rem;
                    position: relative;
                }

                .skill-progress-bar {
                    height: 100%;
                    background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
                    border-radius: 10px;
                    position: relative;
                }

                .skill-progress-bar::after {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    bottom: 0;
                    right: 0;
                    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
                    animation: shimmer 2s infinite linear;
                }

                @keyframes shimmer {
                    0% {
                        transform: translateX(-100%);
                    }

                    100% {
                        transform: translateX(100%);
                    }
                }

                /* Slider styles */
                .skills-slider-wrapper {
                    position: relative;
                    width: 100%;
                    overflow: hidden;
                    padding: 1rem 0;
                }

                .skills-slider-track {
                    display: flex;
                    transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
                }

                .skill-slide {
                    flex: 0 0 100%;
                    width: 100%;
                    opacity: 0;
                    transition: opacity 0.6s ease;
                    padding: 0 5px;
                    /* Slight padding so cards don't touch edges abruptly */
                }

                .skill-slide.active-slide {
                    opacity: 1;
                }

                .skills-slider-nav {
                    display: flex;
                    justify-content: center;
                    gap: 0.6rem;
                    margin-top: 1.5rem;
                }

                .skills-slider-dot {
                    width: 10px;
                    height: 10px;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.2);
                    cursor: pointer;
                    transition: all 0.3s ease;
                }

                .skills-slider-dot:hover {
                    background: rgba(139, 92, 246, 0.6);
                }

                .skills-slider-dot.active {
                    background: var(--accent);
                    transform: scale(1.3);
                }

                /* Arrows */
                .skills-slider-arrow {
                    position: absolute;
                    top: 50%;
                    transform: translateY(-50%);
                    width: 44px;
                    height: 44px;
                    border-radius: 50%;
                    background: rgba(15, 23, 42, 0.5);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    color: rgba(255, 255, 255, 0.7);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    z-index: 10;
                    transition: all 0.3s ease;
                    backdrop-filter: blur(5px);
                    font-size: 1.2rem;
                }

                .skills-slider-arrow:hover {
                    background: var(--accent);
                    color: #fff;
                    border-color: var(--accent);
                    box-shadow: 0 0 15px rgba(139, 92, 246, 0.4);
                }

                .skills-slider-prev {
                    left: 0;
                }

                .skills-slider-next {
                    right: 0;
                }

                @media (max-width: 767px) {
                    .skills-slider-arrow {
                        width: 36px;
                        height: 36px;
                        font-size: 1rem;
                    }
                }
            </style>
            <section class="glass-panel" id="skills" style="animation-delay: 0.1s; position: relative;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                    <h2 style="margin: 0;"><i class="fas fa-magic" style="color: var(--accent);"></i> Professional Skills
                    </h2>
                </div>

                <?php
                // Group skills by skill_group
                $grouped_skills = [];
                foreach ($skills as $s) {
                    $group = isset($s['skill_group']) ? $s['skill_group'] : 'Other';
                    if (!isset($grouped_skills[$group])) {
                        $grouped_skills[$group] = [];
                    }
                    $grouped_skills[$group][] = $s;
                }
                $num_groups = count($grouped_skills);
                ?>

                <?php if ($num_groups > 0): ?>
                    <div class="skills-slider-wrapper" id="skillsSliderWrapper">
                        <?php if ($num_groups > 1): ?>
                            <button class="skills-slider-arrow skills-slider-prev" onclick="moveSkillSlider(-1)"
                                aria-label="Previous"><i class="fas fa-chevron-left"></i></button>
                            <button class="skills-slider-arrow skills-slider-next" onclick="moveSkillSlider(1)" aria-label="Next"><i
                                    class="fas fa-chevron-right"></i></button>
                        <?php endif; ?>

                        <div class="skills-slider-track" id="skillsSliderTrack">
                            <?php foreach ($grouped_skills as $group_name => $group_skills): ?>
                                <div class="skill-slide">
                                    <div class="skill-category">
                                        <div style="display: flex; align-items: center; gap: 1.2rem; margin-bottom: 2rem;">
                                            <h3
                                                style="color: #f8fafc; margin: 0; font-size: 1.3rem; font-weight: 600; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.75rem;">
                                                <span
                                                    style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: rgba(139, 92, 246, 0.15); border-radius: 8px; color: var(--accent); font-size: 0.9rem;">
                                                    <i class="fas fa-layer-group"></i>
                                                </span>
                                                <?php echo htmlspecialchars($group_name); ?>
                                            </h3>
                                            <div
                                                style="height: 1px; flex-grow: 1; background: linear-gradient(90deg, rgba(139, 92, 246, 0.3), transparent);">
                                            </div>
                                        </div>

                                        <div
                                            style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1.5rem;">
                                            <?php foreach ($group_skills as $s): ?>
                                                <div class="skill-card">
                                                    <!-- Icon / Image -->
                                                    <div class="skill-icon-wrapper">
                                                        <?php if (!empty($s['image_url'])): ?>
                                                            <img src="<?php echo htmlspecialchars($s['image_url']); ?>"
                                                                alt="<?php echo htmlspecialchars($s['skill_name']); ?>"
                                                                style="width: 100%; height: 100%; object-fit: contain; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.3));">
                                                        <?php else: ?>
                                                            <i class="fas fa-code" style="font-size: 1.8rem; color: var(--accent);"></i>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Skill Info -->
                                                    <div style="text-align: center; width: 100%;">
                                                        <strong
                                                            style="font-size: 1rem; color: #f8fafc; display: block; margin-bottom: 0.8rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 600;"
                                                            title="<?php echo htmlspecialchars($s['skill_name']); ?>">
                                                            <?php echo htmlspecialchars($s['skill_name']); ?>
                                                        </strong>

                                                        <!-- Progress Bar -->
                                                        <div class="skill-progress-bg">
                                                            <div class="skill-progress-bar"
                                                                style="width: <?php echo $s['proficiency']; ?>%;"></div>
                                                        </div>

                                                        <div
                                                            style="color: rgba(255,255,255,0.6); font-size: 0.85rem; font-weight: 600; text-align: right; letter-spacing: 0.5px;">
                                                            <?php echo $s['proficiency']; ?>%
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($num_groups > 1): ?>
                        <div class="skills-slider-nav" id="skillsSliderNav">
                            <?php for ($i = 0; $i < $num_groups; $i++): ?>
                                <div class="skills-slider-dot <?php echo $i === 0 ? 'active' : ''; ?>"
                                    onclick="goToSkillSlide(<?php echo $i; ?>)"></div>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const skillSlides = document.querySelectorAll('.skill-slide');
                            const totalSkillSlides = skillSlides.length;

                            if (totalSkillSlides > 1) {
                                let currentSkillSlide = 0;
                                const skillsTrack = document.getElementById('skillsSliderTrack');
                                const skillsDots = document.querySelectorAll('.skills-slider-dot');
                                let skillSliderInterval;

                                function updateSkillSlider() {
                                    skillsTrack.style.transform = `translateX(-${currentSkillSlide * 100}%)`;

                                    skillSlides.forEach((slide, index) => {
                                        if (index === currentSkillSlide) {
                                            slide.classList.add('active-slide');
                                        } else {
                                            slide.classList.remove('active-slide');
                                        }
                                    });

                                    skillsDots.forEach((dot, index) => {
                                        if (index === currentSkillSlide) {
                                            dot.classList.add('active');
                                        } else {
                                            dot.classList.remove('active');
                                        }
                                    });
                                }

                                let isHovering = false;

                                window.moveSkillSlider = function (dir) {
                                    currentSkillSlide += dir;
                                    if (currentSkillSlide >= totalSkillSlides) currentSkillSlide = 0;
                                    if (currentSkillSlide < 0) currentSkillSlide = totalSkillSlides - 1;
                                    updateSkillSlider();
                                    if (!isHovering) resetSkillSliderInterval();
                                };

                                window.goToSkillSlide = function (index) {
                                    currentSkillSlide = index;
                                    updateSkillSlider();
                                    if (!isHovering) resetSkillSliderInterval();
                                };

                                function startSkillSliderAuto() {
                                    clearInterval(skillSliderInterval); // ensure no duplicate intervals
                                    skillSliderInterval = setInterval(() => {
                                        if (!isHovering) {
                                            window.moveSkillSlider(1);
                                        }
                                    }, 4000); // Auto-slide every 4 seconds
                                }

                                function resetSkillSliderInterval() {
                                    clearInterval(skillSliderInterval);
                                    if (!isHovering) startSkillSliderAuto();
                                }

                                // Pause on hover
                                const sliderWrapper = document.getElementById('skillsSliderWrapper');
                                sliderWrapper.addEventListener('mouseenter', () => {
                                    isHovering = true;
                                    clearInterval(skillSliderInterval);
                                });
                                sliderWrapper.addEventListener('mouseleave', () => {
                                    isHovering = false;
                                    startSkillSliderAuto();
                                });

                                // Initialize
                                skillSlides[0].classList.add('active-slide');
                                startSkillSliderAuto();
                            } else if (totalSkillSlides === 1) {
                                skillSlides[0].classList.add('active-slide');
                            }
                        });
                    </script>
                <?php endif; ?>
            </section>
        <?php endif; ?>
        <?php break; case 'work': ?>

        <!-- Work Experience -->
        <?php if ($work): ?>
            <section class="glass-panel" id="work" style="animation-delay: 0.2s;">
                <h2><i class="fas fa-briefcase" style="color: var(--accent);"></i> Work Experience</h2>
                <div class="timeline" style="margin-top: 1.5rem;">
                    <?php foreach ($work as $w): ?>
                        <div class="timeline-item">
                            <h3 style="color: #fff; margin-bottom: 0.2rem;"><?php echo htmlspecialchars($w['job_title']); ?>
                            </h3>
                            <div style="color: var(--accent); font-weight: 500; margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars($w['company']); ?>
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;"><i
                                    class="fas fa-calendar-alt"></i> <?php echo $w['start_date']; ?> -
                                <?php echo $w['end_date'] ?: 'Present'; ?>
                            </div>
                            <p><?php echo htmlspecialchars($w['description']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        <?php break; case 'projects': ?>

        <!-- Projects -->
        <?php if ($projects): ?>
            <section class="glass-panel" id="projects" style="animation-delay: 0.25s;">
                <h2><i class="fas fa-project-diagram" style="color: var(--accent);"></i> Projects</h2>
                <div class="card-grid" style="margin-top: 1.5rem;">
                    <?php foreach ($projects as $p): ?>
                        <div class="card" style="display: flex; flex-direction: column; height: 100%;">
                            <!-- Project Image Thumbnail -->
                            <?php if (!empty($p['image_url'])): ?>
                                <div style="margin-bottom: 1rem; width: 100%; border-radius: 8px; overflow: hidden; background: rgba(59, 130, 246, 0.1);">
                                    <img src="<?php echo htmlspecialchars($p['image_url']); ?>" alt="Project Image"
                                        style="width: 100%; height: 180px; object-fit: cover; display: block; border-radius: 8px; transition: transform 0.3s ease;"
                                        onmouseover="this.style.transform='scale(1.05)';"
                                        onmouseout="this.style.transform='scale(1)';">
                                </div>
                            <?php endif; ?>

                            <h3 style="color: #fff; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($p['title']); ?></h3>
                            
                            <div style="margin-bottom: 0.75rem; display: flex; flex-wrap: wrap; gap: 0.4rem;">
                                <?php 
                                $tags = array_filter(array_map('trim', explode(',', $p['tags'])));
                                foreach($tags as $tag): 
                                ?>
                                    <span style="background: rgba(59, 130, 246, 0.1); color: var(--text-muted); font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 4px; border: 1px solid rgba(59, 130, 246, 0.2);">
                                        <?php echo htmlspecialchars($tag); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>

                            <p style="color: var(--text-muted); display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; margin: 0 0 1.25rem 0; flex-grow: 1;">
                                <?php echo nl2br(htmlspecialchars($p['description'])); ?>
                            </p>

                            <!-- Actions -->
                            <div style="display: flex; gap: 0.75rem; margin-top: auto; flex-wrap: wrap;">
                                <button onclick="openProjectModal(<?php echo htmlspecialchars(json_encode($p)); ?>)"
                                    style="flex: 1; display: inline-flex; justify-content: center; align-items: center; gap: 0.5rem; padding: 0.6rem 1rem; background: rgba(139, 92, 246, 0.15); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;"
                                    onmouseover="this.style.background='rgba(139, 92, 246, 0.25)'; this.style.borderColor='rgba(139, 92, 246, 0.5)'; this.style.transform='translateY(-2px)';"
                                    onmouseout="this.style.background='rgba(139, 92, 246, 0.15)'; this.style.borderColor='rgba(139, 92, 246, 0.3)'; this.style.transform='translateY(0)';">
                                    <i class="fas fa-info-circle"></i> Details
                                </button>
                                <?php if (!empty($p['git_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($p['git_url']); ?>" target="_blank" rel="noopener noreferrer"
                                        style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.6rem 1rem; background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; font-size: 0.9rem; font-weight: 600; text-decoration: none; transition: all 0.3s ease;"
                                        onmouseover="this.style.background='rgba(255, 255, 255, 0.2)'; this.style.transform='translateY(-2px)';"
                                        onmouseout="this.style.background='rgba(255, 255, 255, 0.1)'; this.style.transform='translateY(0)';">
                                        <i class="fab fa-github"></i> <span>Code</span>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (!empty($p['live_demo_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($p['live_demo_url']); ?>" target="_blank" rel="noopener noreferrer"
                                        style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.6rem 1rem; background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.1)); color: #22c55e; border: 1.5px solid rgba(34, 197, 94, 0.4); border-radius: 8px; font-size: 0.9rem; font-weight: 600; text-decoration: none; transition: all 0.3s ease;"
                                        onmouseover="this.style.background='rgba(34, 197, 94, 0.3)'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(34, 197, 94, 0.3)';"
                                        onmouseout="this.style.background='linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.1))'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                                        <i class="fas fa-external-link-alt"></i> <span>Live</span>
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($p['video_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($p['video_url']); ?>" target="_blank" rel="noopener noreferrer"
                                        style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.6rem 1rem; background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; font-size: 0.9rem; font-weight: 600; text-decoration: none; transition: all 0.3s ease;"
                                        onmouseover="this.style.background='rgba(239, 68, 68, 0.25)'; this.style.transform='translateY(-2px)';"
                                        onmouseout="this.style.background='rgba(239, 68, 68, 0.15)'; this.style.transform='translateY(0)';">
                                        <i class="fas fa-video"></i> <span>Video</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        <?php break; case 'education': ?>

        <!-- Education -->
        <?php if ($education): ?>
            <section class="glass-panel" id="education" style="animation-delay: 0.3s;">
                <h2><i class="fas fa-graduation-cap" style="color: var(--accent);"></i> Education</h2>
                <div class="timeline" style="margin-top: 1.5rem;">
                    <?php foreach ($education as $e): ?>
                        <div class="timeline-item">
                            <h3 style="color: #fff; margin-bottom: 0.2rem;"><?php echo htmlspecialchars($e['degree']); ?></h3>
                            <div style="color: var(--accent); font-weight: 500; margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars($e['institution']); ?>
                            </div>
                            <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">
                                <i class="fas fa-calendar-alt"></i> <?php echo $e['start_date']; ?> - <?php echo $e['end_date'] ?: 'Present'; ?>
                                <?php if (!empty($e['result'])): ?>
                                    <span style="margin-left: 1rem;"><i class="fas fa-award" style="color: var(--accent);"></i> Result: <strong><?php echo htmlspecialchars($e['result']); ?></strong></span>
                                <?php endif; ?>
                            </div>
                            <p><?php echo htmlspecialchars($e['description']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        <?php break; case 'achievements': ?>

        <!-- Achievements -->
        <?php if ($achievements): ?>
            <section class="glass-panel" id="achievements" style="animation-delay: 0.4s;">
                <h2><i class="fas fa-trophy" style="color: var(--accent);"></i> Achievements</h2>
                <div class="card-grid" style="margin-top: 1.5rem;">
                    <?php foreach ($achievements as $a): ?>
                        <div class="card" style="display: flex; flex-direction: column; height: 100%;">
                            <h3 style="color: #fff; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($a['title']); ?></h3>
                            <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;"><i
                                    class="fas fa-calendar-check"></i> <?php echo $a['date_earned']; ?></div>

                            <!-- Certificate Image Thumbnail -->
                            <?php if (!empty($a['certificate_image_url'])): ?>
                                <div
                                    style="margin-bottom: 1rem; width: 100%; border-radius: 8px; overflow: hidden; background: rgba(59, 130, 246, 0.1);">
                                    <img src="<?php echo htmlspecialchars($a['certificate_image_url']); ?>" alt="Certificate"
                                        style="width: 100%; height: 150px; object-fit: cover; display: block; border-radius: 8px; transition: transform 0.3s ease;"
                                        onmouseover="this.style.transform='scale(1.05)';"
                                        onmouseout="this.style.transform='scale(1)';">
                                </div>
                            <?php endif; ?>

                            <!-- Truncated Description -->
                            <p
                                style="color: var(--text-muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; margin: 0 0 1.25rem 0; height: 3rem; flex-grow: 1;">
                                <?php echo htmlspecialchars($a['description']); ?>
                            </p>

                            <!-- Actions -->
                            <div style="display: flex; gap: 0.75rem; margin-top: auto; flex-wrap: wrap;">
                                <button onclick="openAchievementModal(<?php echo htmlspecialchars(json_encode($a)); ?>)"
                                    style="flex: 1; display: inline-flex; justify-content: center; align-items: center; gap: 0.5rem; padding: 0.6rem 1rem; background: rgba(139, 92, 246, 0.15); color: #a78bfa; border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;"
                                    onmouseover="this.style.background='rgba(139, 92, 246, 0.25)'; this.style.borderColor='rgba(139, 92, 246, 0.5)'; this.style.transform='translateY(-2px)';"
                                    onmouseout="this.style.background='rgba(139, 92, 246, 0.15)'; this.style.borderColor='rgba(139, 92, 246, 0.3)'; this.style.transform='translateY(0)';">
                                    <i class="fas fa-info-circle"></i> Details
                                </button>
                                <?php if (!empty($a['certificate_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($a['certificate_url']); ?>" target="_blank"
                                        rel="noopener noreferrer"
                                        style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.6rem 1rem; background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.1)); color: #22c55e; border: 1.5px solid rgba(34, 197, 94, 0.4); border-radius: 8px; font-size: 0.9rem; font-weight: 600; text-decoration: none; transition: all 0.3s ease;"
                                        onmouseover="this.style.background='rgba(34, 197, 94, 0.3)'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(34, 197, 94, 0.3)';"
                                        onmouseout="this.style.background='linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.1))'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                                        <i class="fas fa-external-link-alt"></i> <span>Link</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        <?php break; case 'research': ?>

        <!-- Research -->
        <?php if (!empty($researches)): ?>
            <section class="glass-panel" id="research" style="animation-delay: 0.45s;">
                <h2><i class="fas fa-microscope" style="color: var(--accent);"></i> Research</h2>
                <div class="card-grid" style="margin-top: 1.5rem;">
                    <?php foreach ($researches as $research): ?>
                        <div class="card" style="display: flex; flex-direction: column; height: 100%;">
                            <h3 style="color: #fff; margin-bottom: 0.5rem; word-break: break-word;"><?php echo htmlspecialchars($research['title']); ?></h3>
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
                                        <span style="display: inline-block; background: rgba(59, 130, 246, 0.1); color: var(--accent); padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.8rem; margin-right: 0.3rem; margin-bottom: 0.3rem;">
                                            <?php echo htmlspecialchars(trim($tag)); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <p style="color: var(--text-muted); margin: 0 0 1.25rem 0; flex-grow: 1;">
                                <?php echo nl2br(htmlspecialchars($research['description'])); ?>
                            </p>
                            <?php if ($research['link']): ?>
                                <div style="display: flex; gap: 0.75rem; margin-top: auto; flex-wrap: wrap;">
                                    <a href="<?php echo htmlspecialchars($research['link']); ?>" target="_blank"
                                        rel="noopener noreferrer"
                                        style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.6rem 1rem; background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.1)); color: #22c55e; border: 1.5px solid rgba(34, 197, 94, 0.4); border-radius: 8px; font-size: 0.9rem; font-weight: 600; text-decoration: none; transition: all 0.3s ease;">
                                        <i class="fas fa-external-link-alt"></i> <span>View Link</span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        <?php break; case 'publications': ?>

        <!-- Publications -->
        <?php if (!empty($publications)): ?>
            <section class="glass-panel" id="publications" style="animation-delay: 0.45s;">
                <h2><i class="fas fa-book" style="color: var(--accent);"></i> Publications</h2>
                <div class="card-grid" style="margin-top: 1.5rem;">
                    <?php foreach ($publications as $pub): ?>
                        <div class="card" style="display: flex; flex-direction: column; height: 100%;">
                            <h3 style="color: #fff; margin-bottom: 0.5rem; word-break: break-word;"><?php echo htmlspecialchars($pub['title']); ?></h3>
                            <div style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                <?php if ($pub['authors']): ?>
                                    <i class="fas fa-users"></i> <?php echo htmlspecialchars($pub['authors']); ?><br>
                                <?php endif; ?>
                                <?php if ($pub['journal_conference']): ?>
                                    <i class="fas fa-book-open"></i> <?php echo htmlspecialchars($pub['journal_conference']); ?> 
                                <?php endif; ?>
                                <?php if ($pub['publish_date']): ?>
                                   <br> <i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($pub['publish_date']); ?>
                                <?php endif; ?>
                            </div>
                            <?php if ($pub['abstract']): ?>
                                <p style="color: var(--text-muted); margin: 0 0 1.25rem 0; flex-grow: 1;">
                                    <?php echo nl2br(htmlspecialchars($pub['abstract'])); ?>
                                </p>
                            <?php endif; ?>
                            <?php if ($pub['link']): ?>
                                <div style="display: flex; gap: 0.75rem; margin-top: auto; flex-wrap: wrap;">
                                    <a href="<?php echo htmlspecialchars($pub['link']); ?>" target="_blank"
                                        rel="noopener noreferrer"
                                        style="flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.6rem 1rem; background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.1)); color: #22c55e; border: 1.5px solid rgba(34, 197, 94, 0.4); border-radius: 8px; font-size: 0.9rem; font-weight: 600; text-decoration: none; transition: all 0.3s ease;">
                                        <i class="fas fa-external-link-alt"></i> <span>View Link</span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        <?php break; case 'blogs': ?>

        <!-- Blogs -->
        <?php if ($blogs): ?>
            <style>
                /* Blogs Slider styles */
                .blog-slide {
                    flex: 0 0 calc(100% - 1.5rem);
                    min-width: calc(100% - 1.5rem);
                    background: rgba(255, 255, 255, 0.02);
                    border: 1px solid rgba(255, 255, 255, 0.08);
                    border-radius: 16px;
                    padding: 2rem;
                    backdrop-filter: blur(10px);
                    display: flex;
                    flex-direction: column;
                    height: 100%;
                    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
                }

                .blog-slide:hover {
                    transform: translateY(-5px);
                    border-color: rgba(59, 130, 246, 0.3);
                    background: linear-gradient(135deg, rgba(59, 130, 246, 0.08), rgba(139, 92, 246, 0.05));
                    box-shadow: 0 10px 30px rgba(59, 130, 246, 0.15);
                }

                @media (min-width: 768px) {
                    .blog-slide {
                        flex: 0 0 calc(50% - 0.75rem) !important;
                        min-width: calc(50% - 0.75rem) !important;
                    }
                }

                @media (min-width: 1200px) {
                    .blog-slide {
                        flex: 0 0 calc(33.333% - 1rem) !important;
                        min-width: calc(33.333% - 1rem) !important;
                    }
                }
            </style>

            <section class="glass-panel" id="blogs" style="animation-delay: 0.5s; overflow: hidden;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2 style="margin: 0;"><i class="fas fa-blog" style="color: var(--accent);"></i> Blog Posts</h2>
                    <?php if (count($blogs) > 1): ?>
                        <div style="display: flex; gap: 0.5rem;">
                            <button class="carousel-btn" onclick="blogCarousel(-1)"
                                style="width: 40px; height: 40px; border-radius: 50%; background: rgba(79, 70, 229, 0.2); border: 1px solid rgba(79, 70, 229, 0.3); color: var(--accent); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;"
                                onmouseover="this.style.background='rgba(79, 70, 229, 0.3)'; this.style.transform='scale(1.1)';"
                                onmouseout="this.style.background='rgba(79, 70, 229, 0.2)'; this.style.transform='scale(1)';">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button class="carousel-btn" onclick="blogCarousel(1)"
                                style="width: 40px; height: 40px; border-radius: 50%; background: rgba(79, 70, 229, 0.2); border: 1px solid rgba(79, 70, 229, 0.3); color: var(--accent); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;"
                                onmouseover="this.style.background='rgba(79, 70, 229, 0.3)'; this.style.transform='scale(1.1)';"
                                onmouseout="this.style.background='rgba(79, 70, 229, 0.2)'; this.style.transform='scale(1)';">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="position: relative; overflow: hidden; padding: 0.5rem 0;">
                    <div id="blogsCarousel"
                        style="display: flex; gap: 1.5rem; transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);">
                        <?php foreach ($blogs as $b): ?>
                            <div class="blog-slide">
                                <h3 style="color: var(--accent); margin-top: 0; margin-bottom: 0.5rem; word-break: break-word; font-size: 1.25rem;">
                                    <?php echo htmlspecialchars($b['title']); ?>
                                </h3>
                                <div style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1rem;">
                                    <i class="fas fa-clock"></i> <?php echo date('M j, Y', strtotime($b['created_at'])); ?>
                                </div>
                                <p style="color: var(--text-muted); font-size: 0.95rem; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; line-height: 1.6; margin-bottom: 1.5rem; flex-grow: 1;">
                                    <?php echo nl2br(htmlspecialchars($b['content'])); ?>
                                </p>
                                <div style="margin-top: auto; padding-top: 1rem; text-align: right;">
                                    <button class="btn" style="padding: 0.5rem 1.25rem; font-size: 0.85rem; background: rgba(59, 130, 246, 0.15); color: var(--accent); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease;"
                                        onmouseover="this.style.background='rgba(59, 130, 246, 0.25)'; this.style.transform='translateY(-2px)';"
                                        onmouseout="this.style.background='rgba(59, 130, 246, 0.15)'; this.style.transform='translateY(0)';"
                                        onclick="openBlogModal(<?php echo htmlspecialchars(json_encode($b)); ?>)">Read More</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if (count($blogs) > 1): ?>
                    <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem;">
                        <?php foreach (array_keys($blogs) as $i): ?>
                            <div class="blog-carousel-dot" onclick="goToBlog(<?php echo $i; ?>)"
                                style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo $i === 0 ? 'var(--accent)' : 'rgba(79, 70, 229, 0.3)'; ?>; cursor: pointer; transition: all 0.3s; transform: scale(1);">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
        <?php break; case 'contact': ?>

        <!-- Contact & Review -->
        <section class="glass-panel" id="contact"
            style="animation-delay: 0.6s; display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem;">
            <!-- Left: Review Form -->
            <div
                style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.08), rgba(14, 165, 233, 0.05)); border: 1px solid rgba(79, 70, 229, 0.2); border-radius: 16px; padding: 2.5rem; backdrop-filter: blur(10px);">
                <h2
                    style="color: var(--accent); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.75rem; font-size: 1.8rem;">
                    <i class="fas fa-star"></i> Leave a Review
                </h2>
                <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 0.95rem;">Share your thoughts about
                    this portfolio</p>

                <form action="submit_review.php" method="POST">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    <input type="hidden" name="username" value="<?php echo htmlspecialchars($username); ?>">
                    <input type="hidden" name="rating" id="ratingInput" value="">

                    <!-- Name Input -->
                    <div class="form-group">
                        <label
                            style="color: var(--text-secondary); font-weight: 600; margin-bottom: 0.75rem; display: block;">Your
                            Name</label>
                        <input type="text" name="visitor_name" required placeholder="Enter your full name"
                            style="width: 100%; padding: 1rem 1.25rem; background: rgba(255, 255, 255, 0.03); border: 1.5px solid var(--border); border-radius: 10px; color: var(--text-main); font-family: 'Inter', sans-serif; font-size: 1rem; transition: all 0.3s ease;"
                            onmouseover="this.style.borderColor='rgba(79, 70, 229, 0.3)'; this.style.background='rgba(79, 70, 229, 0.05)';"
                            onmouseout="this.style.borderColor='var(--border)'; this.style.background='rgba(255, 255, 255, 0.03)';">
                    </div>

                    <!-- Star Rating -->
                    <div class="form-group">
                        <label
                            style="color: var(--text-secondary); font-weight: 600; margin-bottom: 1rem; display: block;">Rate
                            this Portfolio</label>
                        <div
                            style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1rem; flex-wrap: wrap;">
                            <div id="starRating" style="display: flex; gap: 0.75rem; align-items: center;">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <button type="button" class="star-btn" data-rating="<?php echo $i; ?>"
                                        style="font-size: 2rem; color: rgba(79, 70, 229, 0.4); cursor: pointer; background: none; border: none; transition: all 0.3s ease; padding: 0.5rem; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px;"
                                        onmouseover="this.style.color='#fbbf24'; this.style.transform='scale(1.2)';"
                                        onmouseout="this.style.color=document.getElementById('ratingInput').value ? '#fbbf24' : 'rgba(79, 70, 229, 0.4)'; this.style.transform='scale(1)';">
                                        <i class="fas fa-star" style="display: block;"></i>
                                    </button>
                                <?php endfor; ?>
                            </div>
                            <span id="ratingText"
                                style="color: var(--text-muted); font-weight: 600; min-width: 120px; font-size: 0.95rem;">Select
                                rating</span>
                        </div>
                    </div>

                    <!-- Comment Input -->
                    <div class="form-group">
                        <label
                            style="color: var(--text-secondary); font-weight: 600; margin-bottom: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                            <span>Your Review</span>
                            <span id="charCount"
                                style="font-size: 0.8rem; color: var(--text-muted); font-weight: 400;">0/500</span>
                        </label>
                        <textarea name="comment" required
                            placeholder="Share your honest feedback about this portfolio..." rows="4"
                            style="width: 100%; padding: 1rem 1.25rem; background: rgba(255, 255, 255, 0.03); border: 1.5px solid var(--border); border-radius: 10px; color: var(--text-main); font-family: 'Inter', sans-serif; font-size: 1rem; resize: none; transition: all 0.3s ease;"
                            onmouseover="this.style.borderColor='rgba(79, 70, 229, 0.3)'; this.style.background='rgba(79, 70, 229, 0.05)';"
                            onmouseout="this.style.borderColor='var(--border)'; this.style.background='rgba(255, 255, 255, 0.03)';"
                            oninput="updateCharCount(this);" maxlength="500"></textarea>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                        style="width: 100%; padding: 1.1rem 1.5rem; background: linear-gradient(135deg, var(--accent), var(--accent-light)); color: #fff; border: none; border-radius: 10px; font-size: 1rem; font-weight: 700; letter-spacing: 0.3px; cursor: pointer; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); text-align: center; position: relative; overflow: hidden; box-shadow: 0 4px 15px rgba(79, 70, 229, 0.3); display: flex; align-items: center; justify-content: center; gap: 0.75rem;"
                        onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 25px rgba(79, 70, 229, 0.4)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(79, 70, 229, 0.3)';">
                        <i class="fas fa-paper-plane"></i> Submit Your Review
                    </button>
                </form>

                <script>
                    const ratingInput = document.getElementById('ratingInput');
                    const ratingText = document.getElementById('ratingText');
                    const starBtns = document.querySelectorAll('.star-btn');

                    const ratingLabels = ['', 'Poor', 'Average', 'Good', 'Excellent', 'Outstanding'];
                    const ratingColors = ['rgba(79, 70, 229, 0.4)', '#ef4444', '#f59e0b', '#10b981', '#0ea5e9', '#6366f1'];

                    // Initialize stars with proper styling
                    starBtns.forEach(btn => {
                        btn.style.color = 'rgba(79, 70, 229, 0.4)';

                        btn.addEventListener('click', function (e) {
                            e.preventDefault();
                            const rating = this.dataset.rating;
                            ratingInput.value = rating;

                            // Update all stars
                            starBtns.forEach(b => {
                                if (b.dataset.rating <= rating) {
                                    b.style.color = ratingColors[rating];
                                } else {
                                    b.style.color = 'rgba(79, 70, 229, 0.4)';
                                }
                            });

                            // Update text
                            ratingText.textContent = ratingLabels[rating];
                            ratingText.style.color = ratingColors[rating];
                        });

                        btn.addEventListener('mouseover', function () {
                            const hoverRating = this.dataset.rating;
                            starBtns.forEach(b => {
                                if (b.dataset.rating <= hoverRating) {
                                    b.style.color = ratingColors[hoverRating];
                                } else {
                                    b.style.color = 'rgba(79, 70, 229, 0.4)';
                                }
                            });
                        });

                        btn.addEventListener('mouseout', function () {
                            const selectedRating = ratingInput.value || 0;
                            starBtns.forEach(b => {
                                if (selectedRating > 0 && b.dataset.rating <= selectedRating) {
                                    b.style.color = ratingColors[selectedRating];
                                } else {
                                    b.style.color = 'rgba(79, 70, 229, 0.4)';
                                }
                            });
                        });
                    });

                    function updateCharCount(textarea) {
                        const count = textarea.value.length;
                        document.getElementById('charCount').textContent = count + '/500';

                        if (count > 450) {
                            document.getElementById('charCount').style.color = '#ef4444';
                        } else if (count > 400) {
                            document.getElementById('charCount').style.color = '#f59e0b';
                        } else {
                            document.getElementById('charCount').style.color = 'var(--text-muted)';
                        }
                    }
                </script>
            </div>

            <!-- Right: Contact Info with Image -->
            <div
                style="background: linear-gradient(135deg, rgba(14, 165, 233, 0.05), rgba(79, 70, 229, 0.08)); border: 1px solid rgba(14, 165, 233, 0.2); border-radius: 16px; padding: 2.5rem; backdrop-filter: blur(10px); display: flex; flex-direction: column; gap: 2rem; height: 100%;">
                <!-- Contact Image -->
                <?php if (!empty($profile['contact_image'])): ?>
                    <div style="width: 100%; border-radius: 12px; overflow: hidden; ">
                        <img src="<?php echo htmlspecialchars(getDirectImageUrl($profile['contact_image'])); ?>"
                            alt="Contact"
                            style="width: 100%; height: 260px; object-fit: cover; display: block; border-radius: 12px; transition: transform 0.4s ease;"
                            onmouseover="this.style.transform='scale(1.05)';" onmouseout="this.style.transform='scale(1)';">
                    </div>
                <?php endif; ?>

                <!-- Contact Information -->
                <div style="flex-grow: 1; display: flex; flex-direction: column; justify-content: center;">
                    <h2
                        style="color: var(--accent); margin-bottom: 1.5rem; font-size: 1.8rem; display: flex; align-items: center; gap: 0.75rem;">
                        <i class="fas fa-envelope-open-text"></i> Get In Touch
                    </h2>
                    <ul
                        style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 1.25rem;">
                        <li style="display: flex; align-items: center; gap: 1.25rem; padding: 1.25rem; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.3s ease;"
                            onmouseover="this.style.background='rgba(79, 70, 229, 0.1)'; this.style.borderColor='rgba(79, 70, 229, 0.2)'; this.style.transform='translateX(5px)';"
                            onmouseout="this.style.background='rgba(255, 255, 255, 0.03)'; this.style.borderColor='rgba(255, 255, 255, 0.05)'; this.style.transform='translateX(0)';">
                            <div
                                style="width: 48px; height: 48px; border-radius: 50%; background: rgba(79, 70, 229, 0.15); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.25rem; flex-shrink: 0;">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                <span
                                    style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Email</span>
                                <span
                                    style="color: var(--text-main); font-weight: 500; font-size: 1rem; word-break: break-all;"><?php echo htmlspecialchars($profile['email']); ?></span>
                            </div>
                        </li>
                        <?php if ($profile['phone']): ?>
                            <li style="display: flex; align-items: center; gap: 1.25rem; padding: 1.25rem; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.3s ease;"
                                onmouseover="this.style.background='rgba(79, 70, 229, 0.1)'; this.style.borderColor='rgba(79, 70, 229, 0.2)'; this.style.transform='translateX(5px)';"
                                onmouseout="this.style.background='rgba(255, 255, 255, 0.03)'; this.style.borderColor='rgba(255, 255, 255, 0.05)'; this.style.transform='translateX(0)';">
                                <div
                                    style="width: 48px; height: 48px; border-radius: 50%; background: rgba(79, 70, 229, 0.15); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.25rem; flex-shrink: 0;">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                    <span
                                        style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Phone</span>
                                    <span
                                        style="color: var(--text-main); font-weight: 500; font-size: 1rem;"><?php echo htmlspecialchars($profile['phone']); ?></span>
                                </div>
                            </li>
                        <?php endif; ?>
                        <?php if ($profile['address']): ?>
                            <li style="display: flex; align-items: center; gap: 1.25rem; padding: 1.25rem; background: rgba(255, 255, 255, 0.03); border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.3s ease;"
                                onmouseover="this.style.background='rgba(79, 70, 229, 0.1)'; this.style.borderColor='rgba(79, 70, 229, 0.2)'; this.style.transform='translateX(5px)';"
                                onmouseout="this.style.background='rgba(255, 255, 255, 0.03)'; this.style.borderColor='rgba(255, 255, 255, 0.05)'; this.style.transform='translateX(0)';">
                                <div
                                    style="width: 48px; height: 48px; border-radius: 50%; background: rgba(79, 70, 229, 0.15); display: flex; align-items: center; justify-content: center; color: var(--accent); font-size: 1.25rem; flex-shrink: 0;">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                    <span
                                        style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Location</span>
                                    <span
                                        style="color: var(--text-main); font-weight: 500; font-size: 1rem; line-height: 1.4;"><?php echo htmlspecialchars($profile['address']); ?></span>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </section>
        <?php break; case 'reviews': ?>

        <!-- Reviews List - Carousel -->
        <?php if ($reviews): ?>
            <section class="glass-panel" id="reviews" style="animation-delay: 0.7s; overflow: hidden;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h2><i class="fas fa-comments" style="color: var(--accent);"></i> Recent Reviews</h2>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="carousel-btn" onclick="reviewCarousel(-1)"
                            style="width: 40px; height: 40px; border-radius: 50%; background: rgba(79, 70, 229, 0.2); border: 1px solid rgba(79, 70, 229, 0.3); color: var(--accent); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;"
                            onmouseover="this.style.background='rgba(79, 70, 229, 0.3)'; this.style.transform='scale(1.1)';"
                            onmouseout="this.style.background='rgba(79, 70, 229, 0.2)'; this.style.transform='scale(1)';">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="carousel-btn" onclick="reviewCarousel(1)"
                            style="width: 40px; height: 40px; border-radius: 50%; background: rgba(79, 70, 229, 0.2); border: 1px solid rgba(79, 70, 229, 0.3); color: var(--accent); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s;"
                            onmouseover="this.style.background='rgba(79, 70, 229, 0.3)'; this.style.transform='scale(1.1)';"
                            onmouseout="this.style.background='rgba(79, 70, 229, 0.2)'; this.style.transform='scale(1)';">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <div style="position: relative; overflow: hidden;">
                    <div id="reviewsCarousel"
                        style="display: flex; gap: 1.5rem; transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);">
                        <?php foreach ($reviews as $r): ?>
                            <div class="review-slide"
                                style="flex: 0 0 calc(100% - 1.5rem); min-width: calc(100% - 1.5rem); background: linear-gradient(135deg, rgba(79, 70, 229, 0.08), rgba(14, 165, 233, 0.05)); border: 1px solid rgba(79, 70, 229, 0.2); border-radius: 12px; padding: 2rem; backdrop-filter: blur(10px); animation: slideIn 0.6s ease-out;">
                                <div
                                    style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1.5rem;">
                                    <div>
                                        <h3 style="color: #fff; margin: 0 0 0.5rem 0; font-size: 1.2rem;">
                                            <?php echo htmlspecialchars($r['visitor_name']); ?>
                                        </h3>
                                        <span style="color: #fbbf24; font-size: 1.1rem;">
                                            <?php
                                            for ($i = 0; $i < $r['rating']; $i++) {
                                                echo '<i class="fas fa-star" style="display: inline-block; margin-right: 0.2rem;"></i>';
                                            }
                                            for ($i = $r['rating']; $i < 5; $i++) {
                                                echo '<i class="far fa-star" style="display: inline-block; margin-right: 0.2rem; opacity: 0.4;"></i>';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <span style="color: var(--text-muted); font-size: 0.9rem;">
                                        <?php echo date('M j, Y', strtotime($r['created_at'])); ?>
                                    </span>
                                </div>
                                <p
                                    style="color: var(--text-muted); font-size: 1rem; line-height: 1.6; margin: 1rem 0; font-style: italic;">
                                    "<?php echo htmlspecialchars($r['comment']); ?>"
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem;">
                    <?php foreach (array_keys($reviews) as $i): ?>
                        <div class="carousel-dot" onclick="goToReview(<?php echo $i; ?>)"
                            style="width: 8px; height: 8px; border-radius: 50%; background: <?php echo $i === 0 ? 'var(--accent)' : 'rgba(79, 70, 229, 0.3)'; ?>; cursor: pointer; transition: all 0.3s;">
                        </div>
                    <?php endforeach; ?>
                </div>

                <style>
                    @keyframes slideIn {
                        from {
                            opacity: 0;
                            transform: translateX(20px);
                        }

                        to {
                            opacity: 1;
                            transform: translateX(0);
                        }
                    }

                    /* Responsive carousel */
                    @media (min-width: 768px) {
                        .review-slide {
                            flex: 0 0 calc(50% - 0.75rem) !important;
                            min-width: calc(50% - 0.75rem) !important;
                        }
                    }

                    @media (min-width: 1200px) {
                        .review-slide {
                            flex: 0 0 calc(33.333% - 1rem) !important;
                            min-width: calc(33.333% - 1rem) !important;
                        }
                    }
                </style>

                <script>
                    let currentReviewIndex = 0;
                    const reviewSlides = document.querySelectorAll('.review-slide');
                    const reviewCount = reviewSlides.length;

                    function updateCarouselPosition() {
                        const carousel = document.getElementById('reviewsCarousel');
                        const slideWidth = reviewSlides[0].offsetWidth + 24; // 24px gap
                        carousel.style.transform = `translateX(-${currentReviewIndex * slideWidth}px)`;

                        // Update dots
                        document.querySelectorAll('.carousel-dot').forEach((dot, i) => {
                            dot.style.background = i === currentReviewIndex ? 'var(--accent)' : 'rgba(79, 70, 229, 0.3)';
                        });
                    }

                    function reviewCarousel(direction) {
                        currentReviewIndex += direction;
                        if (currentReviewIndex < 0) {
                            currentReviewIndex = reviewCount - 1;
                        } else if (currentReviewIndex >= reviewCount) {
                            currentReviewIndex = 0;
                        }
                        updateCarouselPosition();
                    }

                    function goToReview(index) {
                        currentReviewIndex = index;
                        updateCarouselPosition();
                    }

                    // Auto-advance carousel every 5 seconds
                    setInterval(() => {
                        if (reviewCount > 1) {
                            reviewCarousel(1);
                        }
                    }, 5000);

                    // Update on resize
                    window.addEventListener('resize', updateCarouselPosition);
                </script>
            </section>
        <?php endif; ?>
        <?php endswitch; endforeach; ?>


    </div>

    <!-- Project Details Modal -->
    <div id="projectModal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 2000; align-items: center; justify-content: center; padding: 1rem; animation: fadeIn 0.3s ease;">
        <div style="background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.95)); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 16px; padding: 2.5rem; backdrop-filter: blur(10px); max-width: 600px; width: 100%; max-height: 80vh; overflow-y: auto; position: relative;">
            <button onclick="closeProjectModal()"
                style="position: absolute; top: 1.5rem; right: 1.5rem; width: 36px; height: 36px; border-radius: 50%; background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.4); color: var(--accent); font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;"
                onmouseover="this.style.background='rgba(59, 130, 246, 0.3)'; this.style.transform='scale(1.1)';"
                onmouseout="this.style.background='rgba(59, 130, 246, 0.2)'; this.style.transform='scale(1)';">
                <i class="fas fa-times"></i>
            </button>

            <h2 id="projectTitle" style="color: var(--accent); font-size: 1.8rem; margin-bottom: 1rem; margin-top: 0; padding-right: 2.5rem;">
            </h2>

            <div id="projectTags" style="margin-bottom: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.4rem;"></div>

            <div id="projectImageContainer" style="display: none; margin-bottom: 1.5rem; width: 100%; border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.3); padding: 0.5rem; background: rgba(59, 130, 246, 0.08);">
                <img id="projectImg" src="" alt="Project" style="width: 100%; max-height: 400px; object-fit: contain; border-radius: 8px; display: block;">
            </div>

            <div id="projectDescription" style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.8; margin-bottom: 1.5rem; white-space: pre-wrap;"></div>

            <!-- Action Buttons -->
            <div id="projectLinks" style="display: flex; flex-wrap: wrap; gap: 1rem;">
                <a id="projectGitLink" href="" target="_blank" rel="noopener noreferrer"
                    style="display: none; align-items: center; gap: 0.5rem; padding: 0.7rem 1.5rem; background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; font-size: 0.95rem; font-weight: 600; text-decoration: none; transition: all 0.3s ease;"
                    onmouseover="this.style.background='rgba(255, 255, 255, 0.2)'; this.style.transform='translateY(-2px)';"
                    onmouseout="this.style.background='rgba(255, 255, 255, 0.1)'; this.style.transform='translateY(0)';">
                    <i class="fab fa-github"></i> <span>Git Repository</span>
                </a>
                
                <a id="projectLiveLink" href="" target="_blank" rel="noopener noreferrer"
                    style="display: none; align-items: center; gap: 0.5rem; padding: 0.7rem 1.5rem; background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.1)); color: #22c55e; border: 1.5px solid rgba(34, 197, 94, 0.4); border-radius: 8px; font-size: 0.95rem; font-weight: 600; text-decoration: none; transition: all 0.3s ease;"
                    onmouseover="this.style.background='rgba(34, 197, 94, 0.3)'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(34, 197, 94, 0.3)';"
                    onmouseout="this.style.background='linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.1))'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    <i class="fas fa-external-link-alt"></i> <span>Live Demo</span>
                </a>

                <a id="projectVideoLink" href="" target="_blank" rel="noopener noreferrer"
                    style="display: none; align-items: center; gap: 0.5rem; padding: 0.7rem 1.5rem; background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; font-size: 0.95rem; font-weight: 600; text-decoration: none; transition: all 0.3s ease;"
                    onmouseover="this.style.background='rgba(239, 68, 68, 0.25)'; this.style.transform='translateY(-2px)';"
                    onmouseout="this.style.background='rgba(239, 68, 68, 0.15)'; this.style.transform='translateY(0)';">
                    <i class="fas fa-video"></i> <span>Watch Video</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Achievement Details Modal -->
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>
    <div id="achievementModal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 2000; align-items: center; justify-content: center; padding: 1rem; animation: fadeIn 0.3s ease;">
        <div
            style="background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.95)); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 16px; padding: 2.5rem; backdrop-filter: blur(10px); max-width: 600px; width: 100%; max-height: 80vh; overflow-y: auto; position: relative;">
            <button onclick="closeAchievementModal()"
                style="position: absolute; top: 1.5rem; right: 1.5rem; width: 36px; height: 36px; border-radius: 50%; background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.4); color: var(--accent); font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;"
                onmouseover="this.style.background='rgba(59, 130, 246, 0.3)'; this.style.transform='scale(1.1)';"
                onmouseout="this.style.background='rgba(59, 130, 246, 0.2)'; this.style.transform='scale(1)';">
                <i class="fas fa-times"></i>
            </button>

            <h2 id="achievementTitle"
                style="color: var(--accent); font-size: 1.8rem; margin-bottom: 1rem; margin-top: 0; padding-right: 2.5rem;">
            </h2>


            <!-- Certificate Image Display -->
            <div id="achievementCertImage"
                style="display: none; margin-bottom: 1.5rem; width: 100%; padding: 1rem; background: rgba(59, 130, 246, 0.08); border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.3);">

                <img id="achievementCertImg" src="" alt="Certificate"
                    style="width: 100%; max-height: 400px; object-fit: contain; border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.4); background: rgba(0, 0, 0, 0.2); display: block;"
                    onmouseover="this.style.transform='scale(1.02)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.4)';">
            </div>

            <div id="achievementDescription"
                style="color: var(--text-muted); font-size: 1.05rem; line-height: 1.8; margin-bottom: 1.5rem;"></div>

            <!-- Action Buttons -->
            <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                <a id="achievementLink" href="" target="_blank" rel="noopener noreferrer"
                    style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.7rem 1.5rem; background: linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.1)); color: #22c55e; border: 1.5px solid rgba(34, 197, 94, 0.4); border-radius: 8px; font-size: 0.95rem; font-weight: 600; text-decoration: none; transition: all 0.3s ease;"
                    onmouseover="this.style.background='rgba(34, 197, 94, 0.3)'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(34, 197, 94, 0.3)';"
                    onmouseout="this.style.background='linear-gradient(135deg, rgba(34, 197, 94, 0.2), rgba(34, 197, 94, 0.1))'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    <i class="fas fa-external-link-alt"></i> <span id="achievementLinkText">Certificate</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Blog Details Modal -->
    <div id="blogModal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 2000; align-items: center; justify-content: center; padding: 1rem; animation: fadeIn 0.3s ease;">
        <div style="background: linear-gradient(135deg, rgba(30, 41, 59, 0.98), rgba(15, 23, 42, 0.98)); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 20px; padding: 2.5rem; backdrop-filter: blur(15px); max-width: 700px; width: 100%; max-height: 85vh; overflow-y: auto; position: relative; box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);">
            <button onclick="closeBlogModal()"
                style="position: absolute; top: 1.5rem; right: 1.5rem; width: 36px; height: 36px; border-radius: 50%; background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.4); color: var(--accent); font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;"
                onmouseover="this.style.background='rgba(59, 130, 246, 0.3)'; this.style.transform='scale(1.1)';"
                onmouseout="this.style.background='rgba(59, 130, 246, 0.2)'; this.style.transform='scale(1)';">
                <i class="fas fa-times"></i>
            </button>

            <h2 id="blogModalTitle"
                style="color: var(--accent); font-size: 1.8rem; margin-bottom: 0.5rem; margin-top: 0; padding-right: 2.5rem; line-height: 1.4; word-break: break-word;">
            </h2>

            <div id="blogModalDate"
                style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.75rem; border-bottom: 1px solid rgba(255, 255, 255, 0.08); padding-bottom: 1rem;">
                <i class="fas fa-clock"></i> <span></span>
            </div>

            <div id="blogModalContent"
                style="color: rgba(241, 245, 249, 0.9); font-size: 1.05rem; line-height: 1.8; margin-bottom: 1rem; white-space: pre-wrap; word-break: break-word;"></div>
        </div>
    </div>


    <script>


        function openAchievementModal(achievement) {
            const modal = document.getElementById('achievementModal');
            const title = document.getElementById('achievementTitle');

            const description = document.getElementById('achievementDescription');
            const certImageDiv = document.getElementById('achievementCertImage');
            const certImg = document.getElementById('achievementCertImg');
            const link = document.getElementById('achievementLink');
            const linkText = document.getElementById('achievementLinkText');

            title.textContent = achievement.title;

            description.textContent = achievement.description;

            // Display certificate image if available
            if (achievement.certificate_image_url && achievement.certificate_image_url.trim() !== '') {
                certImageDiv.style.display = 'block';
                certImg.src = achievement.certificate_image_url;
                certImg.style.opacity = '1';
            } else {
                certImageDiv.style.display = 'none';
            }

            // Display original certificate link if available
            if (achievement.certificate_url && achievement.certificate_url.trim() !== '') {
                link.href = achievement.certificate_url;
                link.style.display = 'inline-flex';
                linkText.textContent = 'See in Original Platform';
            } else {
                link.style.display = 'none';
            }

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeAchievementModal() {
            const modal = document.getElementById('achievementModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        document.addEventListener('click', function (event) {
            const achievModal = document.getElementById('achievementModal');
            const blModal = document.getElementById('blogModal');

            if (event.target === achievModal) {
                closeAchievementModal();
            }
            if (event.target === blModal) {
                closeBlogModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAchievementModal();
                closeProjectModal();
                closeBlogModal();
            }
        });

        // Project Modal logic
        function openProjectModal(project) {
            const modal = document.getElementById('projectModal');
            const title = document.getElementById('projectTitle');
            const description = document.getElementById('projectDescription');
            const imageContainer = document.getElementById('projectImageContainer');
            const img = document.getElementById('projectImg');
            
            const gitLink = document.getElementById('projectGitLink');
            const liveLink = document.getElementById('projectLiveLink');
            const videoLink = document.getElementById('projectVideoLink');
            const tagsContainer = document.getElementById('projectTags');

            title.textContent = project.title;
            description.textContent = project.description;

            // Handle Tags
            tagsContainer.innerHTML = '';
            if (project.tags) {
                const tags = project.tags.split(',').map(t => t.trim()).filter(t => t !== '');
                tags.forEach(tag => {
                    const span = document.createElement('span');
                    span.textContent = tag;
                    span.style.cssText = 'background: rgba(59, 130, 246, 0.1); color: var(--text-muted); font-size: 0.8rem; padding: 0.25rem 0.6rem; border-radius: 4px; border: 1px solid rgba(59, 130, 246, 0.2);';
                    tagsContainer.appendChild(span);
                });
            }

            // Handle Image
            if (project.image_url && project.image_url.trim() !== '') {
                imageContainer.style.display = 'block';
                img.src = project.image_url;
            } else {
                imageContainer.style.display = 'none';
            }

            // Handle Links
            if (project.git_url && project.git_url.trim() !== '') {
                gitLink.href = project.git_url;
                gitLink.style.display = 'inline-flex';
            } else {
                gitLink.style.display = 'none';
            }

            if (project.live_demo_url && project.live_demo_url.trim() !== '') {
                liveLink.href = project.live_demo_url;
                liveLink.style.display = 'inline-flex';
            } else {
                liveLink.style.display = 'none';
            }

            if (project.video_url && project.video_url.trim() !== '') {
                videoLink.href = project.video_url;
                videoLink.style.display = 'inline-flex';
            } else {
                videoLink.style.display = 'none';
            }

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeProjectModal() {
            const modal = document.getElementById('projectModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('click', function (event) {
            const projModal = document.getElementById('projectModal');
            if (event.target === projModal) {
                closeProjectModal();
            }
        });

        // Blog Modal logic
        function openBlogModal(blog) {
            const modal = document.getElementById('blogModal');
            const title = document.getElementById('blogModalTitle');
            const dateSpan = document.querySelector('#blogModalDate span');
            const content = document.getElementById('blogModalContent');

            title.textContent = blog.title;
            
            // Format date beautifully if possible
            if (blog.created_at) {
                const dateOptions = { year: 'numeric', month: 'short', day: 'numeric' };
                const formattedDate = new Date(blog.created_at).toLocaleDateString('en-US', dateOptions);
                dateSpan.textContent = formattedDate;
            } else {
                dateSpan.textContent = '';
            }

            content.textContent = blog.content;

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeBlogModal() {
            const modal = document.getElementById('blogModal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        // Blog Carousel logic
        let currentBlogIndex = 0;
        
        function updateBlogCarouselPosition() {
            const carousel = document.getElementById('blogsCarousel');
            const blogSlides = document.querySelectorAll('.blog-slide');
            if (!carousel || blogSlides.length === 0) return;
            
            const slideWidth = blogSlides[0].offsetWidth + 24; // 24px gap (1.5rem = 24px)
            carousel.style.transform = `translateX(-${currentBlogIndex * slideWidth}px)`;

            // Update dots
            document.querySelectorAll('.blog-carousel-dot').forEach((dot, i) => {
                if (i === currentBlogIndex) {
                    dot.style.background = 'var(--accent)';
                    dot.style.transform = 'scale(1.2)';
                } else {
                    dot.style.background = 'rgba(79, 70, 229, 0.3)';
                    dot.style.transform = 'scale(1)';
                }
            });
        }

        function blogCarousel(direction) {
            const blogSlides = document.querySelectorAll('.blog-slide');
            const blogCount = blogSlides.length;
            if (blogCount === 0) return;

            currentBlogIndex += direction;

            // Determine visible slides based on screen width
            let visibleSlides = 1;
            if (window.innerWidth >= 1200) {
                visibleSlides = 3;
            } else if (window.innerWidth >= 768) {
                visibleSlides = 2;
            }

            const maxIndex = Math.max(0, blogCount - visibleSlides);

            if (currentBlogIndex < 0) {
                currentBlogIndex = maxIndex;
            } else if (currentBlogIndex > maxIndex) {
                currentBlogIndex = 0;
            }

            updateBlogCarouselPosition();
        }

        function goToBlog(index) {
            const blogSlides = document.querySelectorAll('.blog-slide');
            const blogCount = blogSlides.length;
            if (blogCount === 0) return;

            // Determine visible slides based on screen width
            let visibleSlides = 1;
            if (window.innerWidth >= 1200) {
                visibleSlides = 3;
            } else if (window.innerWidth >= 768) {
                visibleSlides = 2;
            }

            const maxIndex = Math.max(0, blogCount - visibleSlides);
            currentBlogIndex = Math.min(index, maxIndex);

            updateBlogCarouselPosition();
        }

        // Initialize Carousel Position on load and resize
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(updateBlogCarouselPosition, 200); // Small delay to ensure styles are computed
        });
        window.addEventListener('resize', updateBlogCarouselPosition);
    </script>

    <script>
        // Navigation active state on scroll
        function updateActiveNav() {
            const sections = document.querySelectorAll('section[id], header[id]');
            const navLinks = document.querySelectorAll('.portfolio-nav a.nav-link');
            const dropdowns = document.querySelectorAll('.nav-dropdown');

            let current = 'hero';
            let minDistance = Infinity;

            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const scrollPosition = window.scrollY + 100;

                if (scrollPosition >= sectionTop) {
                    const distance = scrollPosition - sectionTop;
                    if (distance < minDistance) {
                        minDistance = distance;
                        current = section.getAttribute('id');
                    }
                }
            });

            // Reset active states
            navLinks.forEach(link => link.classList.remove('active'));
            dropdowns.forEach(dropdown => dropdown.classList.remove('active-parent'));

            // Set current active
            navLinks.forEach(link => {
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                    
                    // If this link is inside a dropdown menu, highlight the parent dropdown trigger as well
                    const parentDropdown = link.closest('.nav-dropdown');
                    if (parentDropdown) {
                        parentDropdown.classList.add('active-parent');
                    }
                }
            });
        }

        // Smooth scroll function
        function scrollToSection(id) {
            event.preventDefault();
            const element = document.getElementById(id);
            if (element) {
                const navHeight = document.querySelector('.portfolio-nav').offsetHeight;
                const elementPosition = element.offsetTop - navHeight - 20;
                window.scrollTo({
                    top: elementPosition,
                    behavior: 'smooth'
                });

                // Update active state immediately after scroll
                setTimeout(() => {
                    updateActiveNav();
                }, 100);
            }
        }

        // Update active state on scroll with debouncing
        let scrollTimeout;
        window.addEventListener('scroll', function () {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(updateActiveNav, 50);
        }, { passive: true });

        // Initial call
        updateActiveNav();
    </script>
</body>

</html>