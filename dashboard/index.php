<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
$username = $_SESSION['username'];
$pageTitle = 'Dashboard'; $activeNav = 'dashboard';
require_once 'inc/head.php';
require_once 'inc/sidebar.php';
?>
<div style="margin-bottom:1.5rem;">
  <div class="page-title">Welcome back, <?= htmlspecialchars($username) ?> 👋</div>
  <div class="page-sub">Manage all sections of your portfolio from here.</div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;">
<?php
$links=[
  ['about.php','About Me','Tell your story','M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5'],
  ['contact.php','Contact','Email, phone & socials','M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z'],
  ['education.php','Education','Degrees & institutions','M22 10v6M2 10l10-5 10 5-10 5zM6 12v5c3 3 9 3 12 0v-5'],
  ['skills.php','Skills','Your expertise','M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z'],
  ['work.php','Work Experience','Career history','M2 7h20v14H2zM16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2'],
  ['achievement.php','Achievements','Awards & milestones','M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z'],
  ['blogs.php','Blog Posts','Articles & writings','M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6'],
];
foreach($links as [$href,$title,$sub,$path]):?>
<a href="<?= $href ?>" style="text-decoration:none;">
  <div class="card" style="cursor:pointer;transition:transform .2s,box-shadow .2s;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 16px 40px rgba(124,58,237,.25)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#7c3aed,#4f46e5);display:flex;align-items:center;justify-content:center;margin-bottom:.85rem;box-shadow:0 4px 12px rgba(124,58,237,.35);">
      <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="<?= $path ?>"/></svg>
    </div>
    <div style="font-weight:600;font-size:.9rem;color:#fff;margin-bottom:.25rem;"><?= $title ?></div>
    <div style="font-size:.78rem;color:rgba(255,255,255,.45);"><?= $sub ?></div>
  </div>
</a>
<?php endforeach; ?>
</div>

<div class="card" style="margin-top:1.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
  <div>
    <div style="font-weight:600;font-size:.95rem;">Your Portfolio is ready!</div>
    <div style="font-size:.8rem;color:rgba(255,255,255,.45);margin-top:.2rem;">Fill in your sections and share your portfolio with the world.</div>
  </div>
  <a href="../portfolio.php" target="_blank" class="btn btn-primary">
    <svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
    View Portfolio
  </a>
</div>
<?php require_once 'inc/foot.php'; ?>