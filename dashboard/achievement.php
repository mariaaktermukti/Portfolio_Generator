<?php
session_start(); require_once '../config/config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
$uid=$_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['title'])&&!isset($_POST['update_id'])){
    $pdo->prepare("INSERT INTO achievements (user_id,title,description,date_achieved) VALUES(?,?,?,?)")
        ->execute([$uid,trim($_POST['title']),trim($_POST['description']??''),$_POST['date_achieved']?:null]);
    $newId = $pdo->lastInsertId();
    header("Location: achievement.php?edit={$newId}&saved=1"); exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['update_id'])){
    $id = (int)$_POST['update_id'];
    $pdo->prepare("UPDATE achievements SET title=?,description=?,date_achieved=? WHERE id=? AND user_id=?")
        ->execute([trim($_POST['title']),trim($_POST['description']??''),$_POST['date_achieved']?:null,$id,$uid]);
    header("Location: achievement.php?edit={$id}&saved=1"); exit;
}
if (isset($_GET['delete'])){$pdo->prepare("UPDATE achievements SET is_deleted=1 WHERE id=? AND user_id=?")->execute([$_GET['delete'],$uid]); header('Location: achievement.php'); exit;}
$msg = isset($_GET['saved']) ? 'saved' : '';
$list=$pdo->prepare("SELECT * FROM achievements WHERE user_id=? AND is_deleted=0 ORDER BY date_achieved DESC"); $list->execute([$uid]); $achs=$list->fetchAll();
$ei=null; if(isset($_GET['edit'])){$s=$pdo->prepare("SELECT * FROM achievements WHERE id=? AND user_id=? AND is_deleted=0"); $s->execute([$_GET['edit'],$uid]); $ei=$s->fetch();}
$pageTitle='Achievements'; $activeNav='achievement'; require_once 'inc/head.php'; require_once 'inc/sidebar.php';
?>
<div class="page-title">Achievements</div>
<div class="page-sub">Awards, certifications and milestones.</div>
<div style="margin-top:1.5rem;" class="card">
  <div class="card-head">
    <div class="card-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg></div>
    <div><div class="card-title"><?= $ei?'Edit Achievement':'Add Achievement' ?></div><div class="card-sub">Recognitions and milestones</div></div>
  </div>
  <?php if($msg==='saved'): ?><div class="alert alert-success"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Saved successfully! Your achievement is updated.</div><?php endif; ?>
  <?php if($ei): ?><div style="margin-bottom:1rem;"><a href="achievement.php" class="btn btn-secondary btn-sm"><svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Add New Achievement</a></div><?php endif; ?>
  <form method="POST">
    <?php if($ei): ?><input type="hidden" name="update_id" value="<?= $ei['id'] ?>"><?php endif; ?>
    <div class="form-grid">
      <div class="fg"><label>Title</label><input type="text" name="title" placeholder="e.g. Best Developer Award" value="<?= htmlspecialchars($ei['title']??'') ?>" required></div>
      <div class="fg"><label>Date Achieved</label><input type="date" name="date_achieved" value="<?= htmlspecialchars($ei['date_achieved']??'') ?>"></div>
      <div class="fg span2"><label>Description</label><textarea name="description" placeholder="Describe your achievement..."><?= htmlspecialchars($ei['description']??'') ?></textarea></div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary"><?= $ei?'Update':'Add Achievement' ?></button>
      <?php if($ei): ?><a href="achievement.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>
<?php if($achs): ?>
<div class="card">
  <div class="card-head"><div class="card-icon"><svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/></svg></div><div class="card-title">Your Achievements</div></div>
  <?php foreach($achs as $a): ?>
  <div style="display:flex;align-items:flex-start;gap:.85rem;padding:.9rem;border:1px solid var(--border);border-radius:12px;margin-bottom:.65rem;background:rgba(255,255,255,.03);">
    <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,rgba(124,58,237,.3),rgba(79,70,229,.3));display:flex;align-items:center;justify-content:center;flex-shrink:0;">🏆</div>
    <div style="flex:1;">
      <div style="font-weight:600;font-size:.9rem;"><?= htmlspecialchars($a['title']) ?></div>
      <?php if($a['date_achieved']): ?><div style="font-size:.78rem;color:var(--accent);margin-top:.15rem;"><?= htmlspecialchars($a['date_achieved']) ?></div><?php endif; ?>
      <?php if($a['description']): ?><div style="font-size:.82rem;color:rgba(255,255,255,.55);margin-top:.35rem;"><?= nl2br(htmlspecialchars($a['description'])) ?></div><?php endif; ?>
    </div>
    <div class="td-actions">
      <a href="achievement.php?edit=<?= $a['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
      <a href="achievement.php?delete=<?= $a['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Delete</a>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card"><div class="empty"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg><p>No achievements yet. Add your first one above!</p></div></div>
<?php endif; ?>
<?php require_once 'inc/foot.php'; ?>