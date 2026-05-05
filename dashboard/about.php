<?php
session_start(); require_once '../config/config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
$uid = $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fn=trim($_POST['full_name']); $pr=trim($_POST['profession']??''); $bi=trim($_POST['bio']??''); $img=trim($_POST['profile_image']??'');
    $chk=$pdo->prepare("SELECT COUNT(*) FROM about WHERE user_id=? AND is_deleted=0"); $chk->execute([$uid]);
    if($chk->fetchColumn()){
        $pdo->prepare("UPDATE about SET full_name=?,profession=?,bio=?,profile_image=? WHERE user_id=? AND is_deleted=0")->execute([$fn,$pr,$bi,$img,$uid]);
    } else {
        $pdo->prepare("INSERT INTO about (user_id,full_name,profession,bio,profile_image) VALUES(?,?,?,?,?)")->execute([$uid,$fn,$pr,$bi,$img]);
    }
    header('Location: about.php?saved=1'); exit; // PRG: prevent re-submit on refresh
}
if (isset($_GET['delete'])) { $pdo->prepare("UPDATE about SET is_deleted=1 WHERE user_id=?")->execute([$uid]); header('Location: about.php'); exit; }
$msg = isset($_GET['saved']) ? 'saved' : '';
$stmt=$pdo->prepare("SELECT * FROM about WHERE user_id=? AND is_deleted=0"); $stmt->execute([$uid]); $about=$stmt->fetch();
$pageTitle='About'; $activeNav='about'; require_once 'inc/head.php'; require_once 'inc/sidebar.php';
?>
<div class="page-title">About Me</div>
<div class="page-sub">Set up your personal introduction and profile.</div>
<div style="margin-top:1.5rem;" class="card">
  <div class="card-head">
    <div class="card-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg></div>
    <div><div class="card-title">Personal Info</div><div class="card-sub">Update your profile details</div></div>
  </div>
  <?php if($msg==='saved'): ?><div class="alert alert-success"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Saved successfully!</div><?php endif; ?>
  <form method="POST">
    <div class="form-grid">
      <div class="fg"><label>Full Name</label><input type="text" name="full_name" placeholder="John Doe" value="<?= htmlspecialchars($about['full_name']??'') ?>" required></div>
      <div class="fg"><label>Profession / Title</label><input type="text" name="profession" placeholder="e.g. Full Stack Developer" value="<?= htmlspecialchars($about['profession']??'') ?>"></div>
      <div class="fg span2"><label>Bio</label><textarea name="bio" placeholder="Write a short bio about yourself..."><?= htmlspecialchars($about['bio']??'') ?></textarea></div>
      <div class="fg span2"><label>Profile Image URL</label><input type="text" name="profile_image" placeholder="https://example.com/photo.jpg" value="<?= htmlspecialchars($about['profile_image']??'') ?>"></div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary"><svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>Save Changes</button>
      <?php if($about): ?><a href="about.php?delete=1" class="btn btn-danger" onclick="return confirm('Delete your about info?')"><svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>Delete</a><?php endif; ?>
    </div>
  </form>
</div>
<?php require_once 'inc/foot.php'; ?>