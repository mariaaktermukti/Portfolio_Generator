<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: ../auth/login.php');
    exit;
}

// 1. Most popular skill
$stmt_popular_skill = $pdo->query("
    SELECT skill_name, COUNT(*) as cnt
    FROM skills
    WHERE is_deleted = 0
    GROUP BY skill_name
    ORDER BY cnt DESC
    LIMIT 1
");
$popular_skill = $stmt_popular_skill->fetch();

// 2. Global Counts
$stmt_counts = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM skills WHERE is_deleted = 0) as total_skills,
        (SELECT COUNT(*) FROM work_experience WHERE is_deleted = 0) as total_work,
        (SELECT COUNT(*) FROM achievements WHERE is_deleted = 0) as total_achievements,
        (SELECT COUNT(*) FROM blogs WHERE is_deleted = 0) as total_blogs,
        (SELECT COUNT(*) FROM reviews) as total_reviews
");
$counts = $stmt_counts->fetch();

// 3. User Ranking & Credibility Score using Subqueries
$stmt_ranking = $pdo->query("
    SELECT u.username, 
           ( 
             (SELECT IFNULL(AVG(rating),0) FROM reviews WHERE user_id = u.id) * 0.4 +
             (SELECT COUNT(*) FROM skills WHERE user_id = u.id AND is_deleted=0) * 0.2 +
             (SELECT COUNT(*) FROM work_experience WHERE user_id = u.id AND is_deleted=0) * 0.2 +
             (SELECT COUNT(*) FROM achievements WHERE user_id = u.id AND is_deleted=0) * 0.1 +
             (SELECT COUNT(*) FROM blogs WHERE user_id = u.id AND is_deleted=0) * 0.1
           ) AS credibility_score
    FROM users u
    WHERE u.account_status = 'approved' AND u.is_deleted = 0
    ORDER BY credibility_score DESC
");
$rankings = $stmt_ranking->fetchAll();

// 4. Users by Location - Auto-create location column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN location VARCHAR(100) DEFAULT NULL");
} catch (PDOException $e) {
    // Column already exists, continue
}

$users_by_location = [];
try {
    $stmt_users = $pdo->query("
        SELECT id, username, location, profile_image, email
        FROM users
        WHERE account_status = 'approved' AND is_deleted = 0 AND location IS NOT NULL AND location != ''
        ORDER BY location, username
    ");
    $all_location_users = $stmt_users->fetchAll();
    foreach ($all_location_users as $user) {
        $loc = $user['location'];
        if (!isset($users_by_location[$loc])) {
            $users_by_location[$loc] = [];
        }
        $users_by_location[$loc][] = $user;
    }
} catch (PDOException $e) {
    // If location column still doesn't exist, set empty array
    $users_by_location = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Analytics</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div style="max-width: 1000px; margin: 2rem auto; padding: 0 1rem;">
        <p><a href="../admin/dashboard.php" style="display: inline-block; margin-bottom: 1rem;"><i class="fas fa-arrow-left"></i> Back to Admin Dashboard</a></p>
        
        <div class="glass-panel" style="text-align: center;">
            <h2><i class="fas fa-chart-pie"></i> Platform Analytics</h2>
            <p style="color: var(--text-muted);">Overview of the platform usage and user credibility rankings.</p>
        </div>

        <div style="margin-bottom: 2rem;">
            <div class="card" style="text-align: center; margin-bottom: 2rem;">
                <h3 style="color: var(--accent);"><i class="fas fa-star"></i> Most Popular Skill</h3>
                <?php if ($popular_skill): ?>
                    <p style="font-size: 1.5rem; font-weight: bold; margin-top: 1rem;"><?php echo htmlspecialchars($popular_skill['skill_name']); ?></p>
                    <p style="color: var(--text-muted);">Added <?php echo $popular_skill['cnt']; ?> times</p>
                <?php else: ?>
                    <p style="color: var(--text-muted); margin-top: 1rem;">No data yet</p>
                <?php endif; ?>
            </div>
            
            <div class="glass-panel">
                <h3 style="text-align: center; margin-bottom: 2rem;"><i class="fas fa-chart-line"></i> Global Platform Statistics</h3>
                
                <?php 
                    $total_content = $counts['total_skills'] + $counts['total_work'] + $counts['total_achievements'] + $counts['total_blogs'] + $counts['total_reviews'];
                    $total_content = max($total_content, 1); // Prevent division by zero
                    
                    // Calculate percentages
                    $skill_percent = ($counts['total_skills'] / $total_content) * 100;
                    $work_percent = ($counts['total_work'] / $total_content) * 100;
                    $achievements_percent = ($counts['total_achievements'] / $total_content) * 100;
                    $blogs_percent = ($counts['total_blogs'] / $total_content) * 100;
                    $reviews_percent = ($counts['total_reviews'] / $total_content) * 100;
                ?>
                
                <div style="overflow-x: auto;">
                    <table style="margin-bottom: 2rem;">
                        <thead>
                            <tr>
                                <th style="text-align: left;">Content Type</th>
                                <th style="text-align: center; width: 100px;">Count</th>
                                <th style="text-align: left; width: 250px;">Distribution</th>
                                <th style="text-align: center; width: 80px;">Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Skills Row -->
                            <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                                <td style="padding: 1.5rem 1.25rem;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="font-size: 1.5rem; color: #4f46e5; width: 40px; text-align: center;">
                                            <i class="fas fa-code"></i>
                                        </div>
                                        <div>
                                            <p style="font-weight: 700; color: var(--text-main);">Technical Skills</p>
                                            <p style="font-size: 0.8rem; color: var(--text-muted);">Coding & tech expertise</p>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: center; font-size: 1.75rem; font-weight: 800; color: #4f46e5; padding: 1.5rem 1.25rem;">
                                    <?php echo $counts['total_skills']; ?>
                                </td>
                                <td style="padding: 1.5rem 1.25rem;">
                                    <div style="background: rgba(79, 70, 229, 0.1); border-radius: 8px; height: 24px; overflow: hidden;">
                                        <div style="height: 100%; background: linear-gradient(90deg, #4f46e5, #6366f1); width: <?php echo $skill_percent; ?>%; transition: width 0.5s ease; display: flex; align-items: center; justify-content: center;">
                                            <?php if ($skill_percent > 15): ?>
                                                <span style="font-size: 0.7rem; font-weight: 700; color: white;"><?php echo round($skill_percent, 1); ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: center; font-weight: 700; color: #4f46e5; padding: 1.5rem 1.25rem;">
                                    <?php echo round($skill_percent, 1); ?>%
                                </td>
                            </tr>

                            <!-- Work Experience Row -->
                            <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                                <td style="padding: 1.5rem 1.25rem;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="font-size: 1.5rem; color: #0ea5e9; width: 40px; text-align: center;">
                                            <i class="fas fa-briefcase"></i>
                                        </div>
                                        <div>
                                            <p style="font-weight: 700; color: var(--text-main);">Work Experience</p>
                                            <p style="font-size: 0.8rem; color: var(--text-muted);">Professional roles & positions</p>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: center; font-size: 1.75rem; font-weight: 800; color: #0ea5e9; padding: 1.5rem 1.25rem;">
                                    <?php echo $counts['total_work']; ?>
                                </td>
                                <td style="padding: 1.5rem 1.25rem;">
                                    <div style="background: rgba(14, 165, 233, 0.1); border-radius: 8px; height: 24px; overflow: hidden;">
                                        <div style="height: 100%; background: linear-gradient(90deg, #0ea5e9, #06b6d4); width: <?php echo $work_percent; ?>%; transition: width 0.5s ease; display: flex; align-items: center; justify-content: center;">
                                            <?php if ($work_percent > 15): ?>
                                                <span style="font-size: 0.7rem; font-weight: 700; color: white;"><?php echo round($work_percent, 1); ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: center; font-weight: 700; color: #0ea5e9; padding: 1.5rem 1.25rem;">
                                    <?php echo round($work_percent, 1); ?>%
                                </td>
                            </tr>

                            <!-- Achievements Row -->
                            <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                                <td style="padding: 1.5rem 1.25rem;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="font-size: 1.5rem; color: #10b981; width: 40px; text-align: center;">
                                            <i class="fas fa-trophy"></i>
                                        </div>
                                        <div>
                                            <p style="font-weight: 700; color: var(--text-main);">Achievements</p>
                                            <p style="font-size: 0.8rem; color: var(--text-muted);">Certifications & awards</p>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: center; font-size: 1.75rem; font-weight: 800; color: #10b981; padding: 1.5rem 1.25rem;">
                                    <?php echo $counts['total_achievements']; ?>
                                </td>
                                <td style="padding: 1.5rem 1.25rem;">
                                    <div style="background: rgba(16, 185, 129, 0.1); border-radius: 8px; height: 24px; overflow: hidden;">
                                        <div style="height: 100%; background: linear-gradient(90deg, #10b981, #34d399); width: <?php echo $achievements_percent; ?>%; transition: width 0.5s ease; display: flex; align-items: center; justify-content: center;">
                                            <?php if ($achievements_percent > 15): ?>
                                                <span style="font-size: 0.7rem; font-weight: 700; color: white;"><?php echo round($achievements_percent, 1); ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: center; font-weight: 700; color: #10b981; padding: 1.5rem 1.25rem;">
                                    <?php echo round($achievements_percent, 1); ?>%
                                </td>
                            </tr>

                            <!-- Blog Posts Row -->
                            <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                                <td style="padding: 1.5rem 1.25rem;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="font-size: 1.5rem; color: #f59e0b; width: 40px; text-align: center;">
                                            <i class="fas fa-pen-fancy"></i>
                                        </div>
                                        <div>
                                            <p style="font-weight: 700; color: var(--text-main);">Blog Posts</p>
                                            <p style="font-size: 0.8rem; color: var(--text-muted);">Articles & insights</p>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: center; font-size: 1.75rem; font-weight: 800; color: #f59e0b; padding: 1.5rem 1.25rem;">
                                    <?php echo $counts['total_blogs']; ?>
                                </td>
                                <td style="padding: 1.5rem 1.25rem;">
                                    <div style="background: rgba(245, 158, 11, 0.1); border-radius: 8px; height: 24px; overflow: hidden;">
                                        <div style="height: 100%; background: linear-gradient(90deg, #f59e0b, #fbbf24); width: <?php echo $blogs_percent; ?>%; transition: width 0.5s ease; display: flex; align-items: center; justify-content: center;">
                                            <?php if ($blogs_percent > 15): ?>
                                                <span style="font-size: 0.7rem; font-weight: 700; color: white;"><?php echo round($blogs_percent, 1); ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: center; font-weight: 700; color: #f59e0b; padding: 1.5rem 1.25rem;">
                                    <?php echo round($blogs_percent, 1); ?>%
                                </td>
                            </tr>

                            <!-- Reviews Row -->
                            <tr>
                                <td style="padding: 1.5rem 1.25rem;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="font-size: 1.5rem; color: #ef4444; width: 40px; text-align: center;">
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <div>
                                            <p style="font-weight: 700; color: var(--text-main);">Reviews</p>
                                            <p style="font-size: 0.8rem; color: var(--text-muted);">User testimonials</p>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: center; font-size: 1.75rem; font-weight: 800; color: #ef4444; padding: 1.5rem 1.25rem;">
                                    <?php echo $counts['total_reviews']; ?>
                                </td>
                                <td style="padding: 1.5rem 1.25rem;">
                                    <div style="background: rgba(239, 68, 68, 0.1); border-radius: 8px; height: 24px; overflow: hidden;">
                                        <div style="height: 100%; background: linear-gradient(90deg, #ef4444, #f87171); width: <?php echo $reviews_percent; ?>%; transition: width 0.5s ease; display: flex; align-items: center; justify-content: center;">
                                            <?php if ($reviews_percent > 15): ?>
                                                <span style="font-size: 0.7rem; font-weight: 700; color: white;"><?php echo round($reviews_percent, 1); ?>%</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: center; font-weight: 700; color: #ef4444; padding: 1.5rem 1.25rem;">
                                    <?php echo round($reviews_percent, 1); ?>%
                                </td>
                            </tr>

                            <!-- Total Row -->
                            <tr style="background: rgba(79, 70, 229, 0.08); border-top: 2px solid rgba(79, 70, 229, 0.3);">
                                <td style="padding: 1.5rem 1.25rem;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div style="font-size: 1.5rem; color: #6366f1; width: 40px; text-align: center;">
                                            <i class="fas fa-chart-pie"></i>
                                        </div>
                                        <div>
                                            <p style="font-weight: 700; color: var(--text-main);">Total Content</p>
                                            <p style="font-size: 0.8rem; color: var(--text-muted);">Platform-wide items</p>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: center; font-size: 1.75rem; font-weight: 800; color: #6366f1; padding: 1.5rem 1.25rem;">
                                    <?php echo $total_content; ?>
                                </td>
                                <td style="padding: 1.5rem 1.25rem;">
                                    <div style="background: rgba(99, 102, 241, 0.1); border-radius: 8px; height: 24px; overflow: hidden;">
                                        <div style="height: 100%; background: linear-gradient(90deg, #6366f1, #818cf8); width: 100%; display: flex; align-items: center; justify-content: center;">
                                            <span style="font-size: 0.7rem; font-weight: 700; color: white;">100%</span>
                                        </div>
                                    </div>
                                </td>
                                <td style="text-align: center; font-weight: 700; color: #6366f1; padding: 1.5rem 1.25rem;">
                                    100%
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="glass-panel">
            <h3><i class="fas fa-map-location-dot" style="color: #0ea5e9;"></i> Users by Location</h3>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem; font-size: 0.9rem;">Discover talented professionals from different locations</p>
            
            <?php if (!empty($users_by_location)): ?>
                <div class="card-grid">
                    <?php foreach ($users_by_location as $location => $users): ?>
                        <div class="card" style="border-left: 4px solid #0ea5e9;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                                <div>
                                    <p style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em;">
                                        <i class="fas fa-map-pin"></i> <?php echo htmlspecialchars($location); ?>
                                    </p>
                                    <p style="font-size: 2rem; font-weight: 800; color: #0ea5e9; margin-top: 0.25rem;">
                                        <?php echo count($users); ?>
                                    </p>
                                </div>
                                <div style="font-size: 2.5rem; color: rgba(14, 165, 233, 0.2);">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            
                            <div style="border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 1rem; margin-top: 1rem;">
                                <?php foreach (array_slice($users, 0, 3) as $user): ?>
                                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                                        <?php if ($user['profile_image']): ?>
                                            <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                                 style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid #0ea5e9;">
                                        <?php else: ?>
                                            <div style="width: 32px; height: 32px; border-radius: 50%; background: rgba(14, 165, 233, 0.2); display: flex; align-items: center; justify-content: center; color: #0ea5e9;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div style="flex: 1; min-width: 0;">
                                            <p style="font-weight: 600; font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </p>
                                        </div>
                                        <a href="../public/portfolio.php?user=<?php echo urlencode($user['username']); ?>" 
                                           target="_blank"
                                           style="padding: 0.4rem 0.8rem; background: rgba(14, 165, 233, 0.15); border: 1px solid #0ea5e9; border-radius: 6px; color: #0ea5e9; font-size: 0.8rem; text-decoration: none; transition: all 0.3s ease; white-space: nowrap;"
                                           onmouseover="this.style.background='rgba(14, 165, 233, 0.25)'; this.style.transform='scale(1.05)';"
                                           onmouseout="this.style.background='rgba(14, 165, 233, 0.15)'; this.style.transform='scale(1)';">
                                            <i class="fas fa-arrow-up-right-from-square"></i> View
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if (count($users) > 3): ?>
                                    <div style="text-align: center; margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                                        <p style="color: var(--accent); font-weight: 600; font-size: 0.9rem;">
                                            +<?php echo count($users) - 3; ?> more profile<?php echo (count($users) - 3) != 1 ? 's' : ''; ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No users with location data yet.</p>
            <?php endif; ?>
        </div>
            <p style="color: var(--text-muted); margin-bottom: 1rem; font-size: 0.9rem;">Score = (Avg Rating * 0.4) + (Skills * 0.2) + (Work * 0.2) + (Achievements * 0.1) + (Blogs * 0.1)</p>
            
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Username</th>
                            <th>Credibility Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rankings as $index => $rank): ?>
                            <tr>
                                <td>
                                    <?php if ($index == 0): ?>
                                        <i class="fas fa-crown" style="color: #f59e0b;"></i>
                                    <?php elseif ($index == 1): ?>
                                        <i class="fas fa-medal" style="color: #94a3b8;"></i>
                                    <?php elseif ($index == 2): ?>
                                        <i class="fas fa-medal" style="color: #b45309;"></i>
                                    <?php else: ?>
                                        #<?php echo $index + 1; ?>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($rank['username']); ?></strong></td>
                                <td><span class="badge badge-approved" style="font-size: 1rem;"><?php echo number_format($rank['credibility_score'], 2); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
