<?php
session_start(); require_once '../config/config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
$uid=$_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['skill_name'])&&!isset($_POST['update_id'])){
    $pdo->prepare("INSERT INTO skills (user_id,skill_name,proficiency) VALUES(?,?,?)")->execute([$uid,trim($_POST['skill_name']),$_POST['proficiency']]);
    $newId = $pdo->lastInsertId();
    header("Location: skills.php?edit={$newId}&saved=1"); exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['update_id'])){
    $id = (int)$_POST['update_id'];
    $pdo->prepare("UPDATE skills SET skill_name=?,proficiency=? WHERE id=? AND user_id=?")->execute([trim($_POST['skill_name']),$_POST['proficiency'],$id,$uid]);
    header("Location: skills.php?edit={$id}&saved=1"); exit;
}
if (isset($_GET['delete'])){$pdo->prepare("UPDATE skills SET is_deleted=1 WHERE id=? AND user_id=?")->execute([$_GET['delete'],$uid]); header('Location: skills.php'); exit;}
$msg = isset($_GET['saved']) ? 'saved' : '';
$skl=$pdo->prepare("SELECT * FROM skills WHERE user_id=? AND is_deleted=0 ORDER BY FIELD(proficiency,'Expert','Advanced','Intermediate','Beginner')"); $skl->execute([$uid]); $skills=$skl->fetchAll();
$ei=null; if(isset($_GET['edit'])){$s=$pdo->prepare("SELECT * FROM skills WHERE id=? AND user_id=? AND is_deleted=0"); $s->execute([$_GET['edit'],$uid]); $ei=$s->fetch();}
$pageTitle='Skills'; $activeNav='skills'; require_once 'inc/head.php'; require_once 'inc/sidebar.php';
$lvlMap=['Beginner'=>'badge-b','Intermediate'=>'badge-i','Advanced'=>'badge-a','Expert'=>'badge-e'];
?>
<div class="page-title">Skills</div>
<div class="page-sub">Showcase your technical and professional skills.</div>
<div style="margin-top:1.5rem;" class="card">
  <div class="card-head">
    <div class="card-icon"><svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
    <div><div class="card-title"><?= $ei?'Edit Skill':'Add Skill' ?></div><div class="card-sub">Add your expertise</div></div>
  </div>
  <?php if($msg==='saved'): ?><div class="alert alert-success"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Saved successfully! Skill updated.</div><?php endif; ?>
  <?php if($ei): ?><div style="margin-bottom:1rem;"><a href="skills.php" class="btn btn-secondary btn-sm"><svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Add New Skill</a></div><?php endif; ?>
  <form method="POST">
    <?php if($ei): ?><input type="hidden" name="update_id" value="<?= $ei['id'] ?>"><?php endif; ?>
    <div class="form-grid">
      <div class="fg"><label>Skill Name</label><input type="text" name="skill_name" placeholder="e.g. PHP, React, Figma" value="<?= htmlspecialchars($ei['skill_name']??'') ?>" required></div>
      <div class="fg"><label>Proficiency Level</label>
        <select name="proficiency">
          <?php foreach(['Beginner','Intermediate','Advanced','Expert'] as $lvl): ?>
          <option <?= ($ei['proficiency']??'')===$lvl?'selected':'' ?>><?= $lvl ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary"><?= $ei?'Update Skill':'Add Skill' ?></button>
      <?php if($ei): ?><a href="skills.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>
<?php if($skills): ?>
<div class="card">
  <div class="card-head"><div class="card-icon"><svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/></svg></div><div class="card-title">Your Skills</div></div>
  <div style="display:flex;flex-wrap:wrap;gap:.65rem;">
    <?php foreach($skills as $s): ?>
    <div style="display:flex;align-items:center;gap:.55rem;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:.55rem .85rem;">
      <span style="font-size:.88rem;font-weight:500;"><?= htmlspecialchars($s['skill_name']) ?></span>
      <span class="badge <?= $lvlMap[$s['proficiency']]??'' ?>"><?= $s['proficiency'] ?></span>
      <div style="display:flex;gap:.3rem;margin-left:.3rem;">
        <a href="skills.php?edit=<?= $s['id'] ?>" class="btn btn-secondary btn-sm" style="padding:.28rem .55rem;">✏️</a>
        <a href="skills.php?delete=<?= $s['id'] ?>" class="btn btn-danger btn-sm" style="padding:.28rem .55rem;" onclick="return confirm('Delete?')">🗑</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
<?php require_once 'inc/foot.php'; ?>