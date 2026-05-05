<?php
session_start(); require_once '../config/config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
$uid=$_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['company'])&&!isset($_POST['update_id'])){
    $pdo->prepare("INSERT INTO work_experience (user_id,company,position,start_date,end_date,description) VALUES(?,?,?,?,?,?)")
        ->execute([$uid,trim($_POST['company']),trim($_POST['position']),$_POST['start_date']?:null,$_POST['end_date']?:null,trim($_POST['description']??'')]);
    $newId = $pdo->lastInsertId();
    header("Location: work.php?edit={$newId}&saved=1"); exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['update_id'])){
    $id=(int)$_POST['update_id'];
    $pdo->prepare("UPDATE work_experience SET company=?,position=?,start_date=?,end_date=?,description=? WHERE id=? AND user_id=?")
        ->execute([trim($_POST['company']),trim($_POST['position']),$_POST['start_date']?:null,$_POST['end_date']?:null,trim($_POST['description']??''),$id,$uid]);
    header("Location: work.php?edit={$id}&saved=1"); exit;
}
if (isset($_GET['delete'])){$pdo->prepare("UPDATE work_experience SET is_deleted=1 WHERE id=? AND user_id=?")->execute([$_GET['delete'],$uid]); header('Location: work.php'); exit;}
$msg=isset($_GET['saved'])?'saved':'';
$list=$pdo->prepare("SELECT * FROM work_experience WHERE user_id=? AND is_deleted=0 ORDER BY start_date DESC"); $list->execute([$uid]); $works=$list->fetchAll();
$ei=null; if(isset($_GET['edit'])){$s=$pdo->prepare("SELECT * FROM work_experience WHERE id=? AND user_id=? AND is_deleted=0"); $s->execute([$_GET['edit'],$uid]); $ei=$s->fetch();}
$pageTitle='Work Experience'; $activeNav='work'; require_once 'inc/head.php'; require_once 'inc/sidebar.php';
?>
<div class="page-title">Work Experience</div>
<div class="page-sub">Your professional career history.</div>
<div style="margin-top:1.5rem;" class="card">
  <div class="card-head">
    <div class="card-icon"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/></svg></div>
    <div><div class="card-title"><?= $ei?'Edit Record':'Add Job' ?></div><div class="card-sub">Company, role and duration</div></div>
  </div>
  <?php if($msg==='saved'): ?><div class="alert alert-success"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Saved successfully! Work record updated.</div><?php endif; ?>
  <?php if($ei): ?><div style="margin-bottom:1rem;"><a href="work.php" class="btn btn-secondary btn-sm"><svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Add New Job</a></div><?php endif; ?>
  <form method="POST">
    <?php if($ei): ?><input type="hidden" name="update_id" value="<?= $ei['id'] ?>"><?php endif; ?>
    <div class="form-grid">
      <div class="fg"><label>Company</label><input type="text" name="company" placeholder="e.g. Google" value="<?= htmlspecialchars($ei['company']??'') ?>" required></div>
      <div class="fg"><label>Position</label><input type="text" name="position" placeholder="e.g. Senior Developer" value="<?= htmlspecialchars($ei['position']??'') ?>" required></div>
      <div class="fg"><label>Start Date</label><input type="date" name="start_date" value="<?= htmlspecialchars($ei['start_date']??'') ?>"></div>
      <div class="fg"><label>End Date <span style="color:var(--muted);font-weight:400;">(leave blank if current)</span></label><input type="date" name="end_date" value="<?= htmlspecialchars($ei['end_date']??'') ?>"></div>
      <div class="fg span2"><label>Description</label><textarea name="description" placeholder="Key responsibilities and achievements..."><?= htmlspecialchars($ei['description']??'') ?></textarea></div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary"><?= $ei?'Update':'Add Job' ?></button>
      <?php if($ei): ?><a href="work.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>
<?php if($works): ?>
<div class="card">
  <div class="card-head"><div class="card-icon"><svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/></svg></div><div class="card-title">Work History</div></div>
  <?php foreach($works as $w): ?>
  <div style="padding:1rem;border:1px solid var(--border);border-radius:12px;margin-bottom:.75rem;background:rgba(255,255,255,.03);">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.5rem;">
      <div>
        <div style="font-weight:600;font-size:.95rem;"><?= htmlspecialchars($w['position']) ?></div>
        <div style="color:var(--accent);font-size:.85rem;margin-top:.1rem;"><?= htmlspecialchars($w['company']) ?></div>
        <div style="color:var(--muted);font-size:.78rem;margin-top:.3rem;">
          <?= htmlspecialchars($w['start_date']??'') ?> — <?= $w['end_date']?htmlspecialchars($w['end_date']):'<span style="color:#6ee7b7;">Present</span>' ?>
        </div>
        <?php if($w['description']): ?><div style="font-size:.83rem;color:rgba(255,255,255,.6);margin-top:.5rem;"><?= nl2br(htmlspecialchars($w['description'])) ?></div><?php endif; ?>
      </div>
      <div class="td-actions">
        <a href="work.php?edit=<?= $w['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
        <a href="work.php?delete=<?= $w['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Delete</a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require_once 'inc/foot.php'; ?>