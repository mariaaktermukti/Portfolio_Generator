<?php $user = $_SESSION['username'] ?? 'User'; ?>
<div class="overlay" id="overlay"></div>
<div class="sidebar" id="sidebar">
  <div class="sb-logo">
    <div class="icon"><svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div>
    <span>Portfolio Gen</span>
  </div>
  <div class="nav-lbl">Main</div>
  <a href="index.php" class="nav-link <?= $activeNav==='dashboard'?'active':'' ?>">
    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>Dashboard</a>
  <a href="about.php" class="nav-link <?= $activeNav==='about'?'active':'' ?>">
    <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>About</a>
  <a href="contact.php" class="nav-link <?= $activeNav==='contact'?'active':'' ?>">
    <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>Contact</a>
  <a href="education.php" class="nav-link <?= $activeNav==='education'?'active':'' ?>">
    <svg viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>Education</a>
  <a href="skills.php" class="nav-link <?= $activeNav==='skills'?'active':'' ?>">
    <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>Skills</a>
  <a href="work.php" class="nav-link <?= $activeNav==='work'?'active':'' ?>">
    <svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg>Work Experience</a>
  <a href="achievement.php" class="nav-link <?= $activeNav==='achievement'?'active':'' ?>">
    <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>Achievements</a>
  <a href="blogs.php" class="nav-link <?= $activeNav==='blogs'?'active':'' ?>">
    <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>Blog Posts</a>
  <div class="nav-lbl">Portfolio</div>
  <a href="../portfolio.php" target="_blank" class="nav-link">
    <svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>View Portfolio</a>
  <div class="sb-footer">
    <a href="../auth/logout.php" class="nav-link danger">
      <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Logout</a>
  </div>
</div>
<div class="main">
  <div class="topbar">
    <div>
      <button class="hamburger" id="hamburger"><svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
    </div>
    <div class="user-badge">
      <div class="av"><?= strtoupper(substr($user,0,1)) ?></div>
      <?= htmlspecialchars($user) ?>
    </div>
  </div>
