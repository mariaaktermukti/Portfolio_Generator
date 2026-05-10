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

        <div class="card-grid" style="margin-bottom: 2rem;">
            <div class="card" style="text-align: center;">
                <h3 style="color: var(--accent);"><i class="fas fa-star"></i> Most Popular Skill</h3>
                <?php if ($popular_skill): ?>
                    <p style="font-size: 1.5rem; font-weight: bold; margin-top: 1rem;"><?php echo htmlspecialchars($popular_skill['skill_name']); ?></p>
                    <p style="color: var(--text-muted);">Added <?php echo $popular_skill['cnt']; ?> times</p>
                <?php else: ?>
                    <p style="color: var(--text-muted); margin-top: 1rem;">No data yet</p>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h3 style="color: var(--success); text-align: center;"><i class="fas fa-database"></i> Global Stats</h3>
                <ul style="list-style: none; padding: 0; margin-top: 1rem;">
                    <li style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;"><span>Skills:</span> <strong><?php echo $counts['total_skills']; ?></strong></li>
                    <li style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;"><span>Work Exp:</span> <strong><?php echo $counts['total_work']; ?></strong></li>
                    <li style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;"><span>Achievements:</span> <strong><?php echo $counts['total_achievements']; ?></strong></li>
                    <li style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;"><span>Blogs:</span> <strong><?php echo $counts['total_blogs']; ?></strong></li>
                    <li style="display: flex; justify-content: space-between;"><span>Reviews:</span> <strong><?php echo $counts['total_reviews']; ?></strong></li>
                </ul>
            </div>
        </div>

        <div class="glass-panel">
            <h3><i class="fas fa-trophy" style="color: #f59e0b;"></i> User Credibility Rankings</h3>
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
