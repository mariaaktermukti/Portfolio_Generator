<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <h2>Portfolio Gen</h2>
    <nav>
        <ul>
            <li><a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Overview</a></li>
            <li><a href="about.php" class="<?php echo $current_page == 'about.php' ? 'active' : ''; ?>"><i class="fas fa-user"></i> About</a></li>
            <li><a href="contact.php" class="<?php echo $current_page == 'contact.php' ? 'active' : ''; ?>"><i class="fas fa-envelope"></i> Contact</a></li>
            <li><a href="education.php" class="<?php echo $current_page == 'education.php' ? 'active' : ''; ?>"><i class="fas fa-graduation-cap"></i> Education</a></li>
            <li><a href="skills.php" class="<?php echo $current_page == 'skills.php' ? 'active' : ''; ?>"><i class="fas fa-star"></i> Skills</a></li>
            <li><a href="work.php" class="<?php echo $current_page == 'work.php' ? 'active' : ''; ?>"><i class="fas fa-briefcase"></i> Work</a></li>
            <li><a href="achievements.php" class="<?php echo $current_page == 'achievements.php' ? 'active' : ''; ?>"><i class="fas fa-trophy"></i> Achievements</a></li>
            <li><a href="blogs.php" class="<?php echo $current_page == 'blogs.php' ? 'active' : ''; ?>"><i class="fas fa-blog"></i> Blogs</a></li>
            <li><a href="shareable_link.php" class="<?php echo $current_page == 'shareable_link.php' ? 'active' : ''; ?>"><i class="fas fa-share-alt"></i> Share Portfolio</a></li>
            <li style="margin-top: 2rem;">
                <a href="../public/portfolio.php?user=<?php echo urlencode($_SESSION['username']); ?>" target="_blank"><i class="fas fa-external-link-alt"></i> View Portfolio</a>
            </li>
            <li>
                <a href="../export/export_pdf.php?user=<?php echo urlencode($_SESSION['username']); ?>" target="_blank"><i class="fas fa-file-pdf"></i> Export PDF</a>
            </li>
            <li>
                <a href="../auth/logout.php" style="color: var(--danger);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </nav>
</aside>
