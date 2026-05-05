<?php
session_start(); require_once '../config/config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
$uid=$_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['institution'])&&!isset($_POST['update_id'])){
    $pdo->prepare("INSERT INTO education (user_id,institution,degree,field_of_study,start_date,end_date,description) VALUES(?,?,?,?,?,?,?)")
        ->execute([$uid,trim($_POST['institution']),trim($_POST['degree']),trim($_POST['field_of_study']??''),$_POST['start_date']?:null,$_POST['end_date']?:null,trim($_POST['description']??'')]);
    $newId = $pdo->lastInsertId();
    header("Location: education.php?edit={$newId}&saved=1"); exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['update_id'])){
    $id = (int)$_POST['update_id'];
    $pdo->prepare("UPDATE education SET institution=?,degree=?,field_of_study=?,start_date=?,end_date=?,description=? WHERE id=? AND user_id=?")
        ->execute([trim($_POST['institution']),trim($_POST['degree']),trim($_POST['field_of_study']??''),$_POST['start_date']?:null,$_POST['end_date']?:null,trim($_POST['description']??''),$id,$uid]);
    header("Location: education.php?edit={$id}&saved=1"); exit;
}
if (isset($_GET['delete'])){$pdo->prepare("UPDATE education SET is_deleted=1 WHERE id=? AND user_id=?")->execute([$_GET['delete'],$uid]); header('Location: education.php'); exit;}
$msg = isset($_GET['saved']) ? 'saved' : '';
$list=$pdo->prepare("SELECT * FROM education WHERE user_id=? AND is_deleted=0 ORDER BY start_date DESC"); $list->execute([$uid]); $edus=$list->fetchAll();
$ei=null; if(isset($_GET['edit'])){$s=$pdo->prepare("SELECT * FROM education WHERE id=? AND user_id=? AND is_deleted=0"); $s->execute([$_GET['edit'],$uid]); $ei=$s->fetch();}
$pageTitle='Education'; $activeNav='education'; require_once 'inc/head.php'; require_once 'inc/sidebar.php';
?>
<div class="page-title">Education</div>
<div class="page-sub">Your academic background and qualifications.</div>
<div style="margin-top:1.5rem;" class="card">
  <div class="card-head">
    <div class="card-icon"><svg viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></div>
    <div><div class="card-title"><?= $ei?'Edit Record':'Add Education' ?></div><div class="card-sub">Institution, degree and dates</div></div>
  </div>
  <?php if($msg==='saved'): ?><div class="alert alert-success"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Saved successfully! Your education record is now updated.</div><?php endif; ?>
  <?php if($ei): ?><div style="margin-bottom:1rem;"><a href="education.php" class="btn btn-secondary btn-sm"><svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Add New Education</a></div><?php endif; ?>
  <form method="POST">
    <?php if($ei): ?><input type="hidden" name="update_id" value="<?= $ei['id'] ?>"><?php endif; ?>
    <div class="form-grid">
      <div class="fg"><label>Institution</label><input type="text" name="institution" placeholder="University / College" value="<?= htmlspecialchars($ei['institution']??'') ?>" required></div>
      <div class="fg"><label>Degree</label><input type="text" name="degree" placeholder="e.g. Bachelor of Science" value="<?= htmlspecialchars($ei['degree']??'') ?>" required></div>
      <div class="fg span2"><label>Field of Study</label><input type="text" name="field_of_study" placeholder="e.g. Computer Science" value="<?= htmlspecialchars($ei['field_of_study']??'') ?>"></div>
      <div class="fg"><label>Start Date</label><input type="date" name="start_date" value="<?= htmlspecialchars($ei['start_date']??'') ?>"></div>
      <div class="fg"><label>End Date</label><input type="date" name="end_date" value="<?= htmlspecialchars($ei['end_date']??'') ?>"></div>
      <div class="fg span2"><label>Description</label><textarea name="description" placeholder="Brief description..."><?= htmlspecialchars($ei['description']??'') ?></textarea></div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary"><?= $ei?'Update':'Add Education' ?></button>
      <?php if($ei): ?><a href="education.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>
<?php if($edus): ?>
<div class="card">
  <div class="card-head"><div class="card-icon"><svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg></div><div class="card-title">Education Records</div></div>
  <div class="tbl-wrap"><table>
    <thead><tr><th>Institution</th><th>Degree</th><th>Field</th><th>Start</th><th>End</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($edus as $e): ?>
    <tr>
      <td><strong><?= htmlspecialchars($e['institution']) ?></strong></td>
      <td><?= htmlspecialchars($e['degree']) ?></td>
      <td><?= htmlspecialchars($e['field_of_study']) ?></td>
      <td><?= htmlspecialchars($e['start_date']??'-') ?></td>
      <td><?= htmlspecialchars($e['end_date']??'Present') ?></td>
      <td><div class="td-actions">
        <a href="education.php?edit=<?= $e['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
        <a href="education.php?delete=<?= $e['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')">Delete</a>
      </div></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>
<?php require_once 'inc/foot.php'; ?>