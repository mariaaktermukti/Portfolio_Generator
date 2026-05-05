<?php
session_start(); require_once '../config/config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
$uid=$_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $em=trim($_POST['email']??''); $ph=trim($_POST['phone']??''); $ad=trim($_POST['address']??''); $we=trim($_POST['website']??''); $li=trim($_POST['linkedin']??''); $gh=trim($_POST['github']??'');
    $chk=$pdo->prepare("SELECT COUNT(*) FROM contact WHERE user_id=? AND is_deleted=0"); $chk->execute([$uid]);
    if($chk->fetchColumn()){
        $pdo->prepare("UPDATE contact SET email=?,phone=?,address=?,website=?,linkedin=?,github=? WHERE user_id=? AND is_deleted=0")->execute([$em,$ph,$ad,$we,$li,$gh,$uid]);
    } else {
        $pdo->prepare("INSERT INTO contact (user_id,email,phone,address,website,linkedin,github) VALUES(?,?,?,?,?,?,?)")->execute([$uid,$em,$ph,$ad,$we,$li,$gh]);
    }
    header('Location: contact.php?saved=1'); exit;
}
if (isset($_GET['delete'])) { $pdo->prepare("UPDATE contact SET is_deleted=1 WHERE user_id=?")->execute([$uid]); header('Location: contact.php'); exit; }
$msg = isset($_GET['saved']) ? 'saved' : '';
$stmt=$pdo->prepare("SELECT * FROM contact WHERE user_id=? AND is_deleted=0"); $stmt->execute([$uid]); $c=$stmt->fetch();
$pageTitle='Contact'; $activeNav='contact'; require_once 'inc/head.php'; require_once 'inc/sidebar.php';
?>
<div class="page-title">Contact Information</div>
<div class="page-sub">How people can reach you.</div>
<div style="margin-top:1.5rem;" class="card">
  <div class="card-head">
    <div class="card-icon"><svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
    <div><div class="card-title">Contact Details</div><div class="card-sub">Email, social links, and more</div></div>
  </div>
  <?php if($msg==='saved'): ?><div class="alert alert-success"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Saved!</div><?php endif; ?>
  <form method="POST">
    <div class="form-grid">
      <div class="fg"><label>Email</label><input type="email" name="email" placeholder="you@email.com" value="<?= htmlspecialchars($c['email']??'') ?>"></div>
      <div class="fg"><label>Phone</label><input type="tel" name="phone" placeholder="+1 234 567 8900" value="<?= htmlspecialchars($c['phone']??'') ?>"></div>
      <div class="fg span2"><label>Address</label><input type="text" name="address" placeholder="City, Country" value="<?= htmlspecialchars($c['address']??'') ?>"></div>
      <div class="fg"><label>Website</label><input type="url" name="website" placeholder="https://yoursite.com" value="<?= htmlspecialchars($c['website']??'') ?>"></div>
      <div class="fg"><label>LinkedIn URL</label><input type="url" name="linkedin" placeholder="https://linkedin.com/in/..." value="<?= htmlspecialchars($c['linkedin']??'') ?>"></div>
      <div class="fg span2"><label>GitHub URL</label><input type="url" name="github" placeholder="https://github.com/..." value="<?= htmlspecialchars($c['github']??'') ?>"></div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary"><svg viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>Save</button>
      <?php if($c): ?><a href="contact.php?delete=1" class="btn btn-danger" onclick="return confirm('Delete contact info?')"><svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>Delete</a><?php endif; ?>
    </div>
  </form>
</div>
<?php require_once 'inc/foot.php'; ?>