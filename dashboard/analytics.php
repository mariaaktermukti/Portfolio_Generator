<?php
require_once '../config/config/db.php';
?>
<!DOCTYPE html>
<html>
<head><title>Analytics (CR Scan)</title></head>
<body>
    <h1>Credibility Ranking</h1>
    <table border="1">
        <tr><th>Username</th><th>Avg Rating</th><th>Skills</th><th>Work</th><th>Achieve.</th><th>Blogs</th><th>CR Score</th></tr>
        <?php
        $stmt = $pdo->query("
            SELECT u.username,
                (SELECT COALESCE(AVG(r.rating),0) FROM reviews r WHERE r.reviewed_user_id=u.id AND r.is_deleted=0) avg_rating,
                (SELECT COUNT(*) FROM skills s WHERE s.user_id=u.id AND s.is_deleted=0) skill_cnt,
                (SELECT COUNT(*) FROM work_experience w WHERE w.user_id=u.id AND w.is_deleted=0) work_cnt,
                (SELECT COUNT(*) FROM achievements a WHERE a.user_id=u.id AND a.is_deleted=0) ach_cnt,
                (SELECT COUNT(*) FROM blogs b WHERE b.user_id=u.id AND b.is_deleted=0) blog_cnt,
                ( (SELECT COALESCE(AVG(r.rating),0) FROM reviews r WHERE r.reviewed_user_id=u.id AND r.is_deleted=0)*0.4
                  + (SELECT COUNT(*) FROM skills s WHERE s.user_id=u.id AND s.is_deleted=0)*2*0.2
                  + (SELECT COUNT(*) FROM work_experience w WHERE w.user_id=u.id AND w.is_deleted=0)*3*0.2
                  + (SELECT COUNT(*) FROM achievements a WHERE a.user_id=u.id AND a.is_deleted=0)*2*0.1
                  + (SELECT COUNT(*) FROM blogs b WHERE b.user_id=u.id AND b.is_deleted=0)*1.5*0.1
                ) as credibility_score
            FROM users u
            WHERE u.is_deleted=0
            ORDER BY credibility_score DESC
        ");
        while ($row = $stmt->fetch()):
        ?>
        <tr>
            <td><?= htmlspecialchars($row['username']) ?></td>
            <td><?= round($row['avg_rating'],1) ?></td>
            <td><?= $row['skill_cnt'] ?></td>
            <td><?= $row['work_cnt'] ?></td>
            <td><?= $row['ach_cnt'] ?></td>
            <td><?= $row['blog_cnt'] ?></td>
            <td><?= round($row['credibility_score'],2) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>