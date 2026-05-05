<?php
session_start(); require_once '../config/config/db.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
$uid=$_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['title'])&&!isset($_POST['update_id'])){
    $pdo->prepare("INSERT INTO blogs (user_id,title,content,image) VALUES(?,?,?,?)")
        ->execute([$uid,trim($_POST['title']),trim($_POST['content']),$_POST['image']??'']);
    $newId = $pdo->lastInsertId();
    header("Location: blogs.php?edit={$newId}&saved=1"); exit;
}
if ($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['update_id'])){
    $id = (int)$_POST['update_id'];
    $pdo->prepare("UPDATE blogs SET title=?,content=?,image=? WHERE id=? AND user_id=?")
        ->execute([trim($_POST['title']),trim($_POST['content']),$_POST['image']??'',$id,$uid]);
    header("Location: blogs.php?edit={$id}&saved=1"); exit;
}
if (isset($_GET['delete'])){$pdo->prepare("UPDATE blogs SET is_deleted=1 WHERE id=? AND user_id=?")->execute([$_GET['delete'],$uid]); header('Location: blogs.php'); exit;}
$msg = isset($_GET['saved']) ? 'saved' : '';
$list=$pdo->prepare("SELECT * FROM blogs WHERE user_id=? AND is_deleted=0 ORDER BY created_at DESC"); $list->execute([$uid]); $blogs=$list->fetchAll();
$ei=null; if(isset($_GET['edit'])){$s=$pdo->prepare("SELECT * FROM blogs WHERE id=? AND user_id=? AND is_deleted=0"); $s->execute([$_GET['edit'],$uid]); $ei=$s->fetch();}
$pageTitle='Blog Posts'; $activeNav='blogs'; require_once 'inc/head.php'; require_once 'inc/sidebar.php';
?>
<div class="page-title">Blog Posts</div>
<div class="page-sub">Articles, tutorials and writings you want to share.</div>
<div style="margin-top:1.5rem;" class="card">
  <div class="card-head">
    <div class="card-icon"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
    <div><div class="card-title"><?= $ei?'Edit Post':'New Post' ?></div><div class="card-sub">Write and publish blog content</div></div>
  </div>
  <?php if($msg==='saved'): ?><div class="alert alert-success"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Saved successfully! Post updated.</div><?php endif; ?>
  <?php if($ei): ?><div style="margin-bottom:1rem;"><a href="blogs.php" class="btn btn-secondary btn-sm"><svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Write New Post</a></div><?php endif; ?>
  <form method="POST">
    <?php if($ei): ?><input type="hidden" name="update_id" value="<?= $ei['id'] ?>"><?php endif; ?>
    <div class="form-grid cols1">
      <div class="fg"><label>Title</label><input type="text" name="title" placeholder="Post title..." value="<?= htmlspecialchars($ei['title']??'') ?>" required></div>
      <div class="fg"><label>Content</label><textarea name="content" rows="6" placeholder="Write your blog content here..."><?= htmlspecialchars($ei['content']??'') ?></textarea></div>
      <div class="fg"><label>Cover Image URL <span style="color:var(--muted);font-weight:400;">(optional)</span></label><input type="url" name="image" placeholder="https://example.com/image.jpg" value="<?= htmlspecialchars($ei['image']??'') ?>"></div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary"><?= $ei?'Update Post':'Publish Post' ?></button>
      <?php if($ei): ?><a href="blogs.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>
<?php if($blogs): ?>
<div style="margin-top:1.25rem;">
  <?php foreach($blogs as $b): ?>
  <div class="card" style="margin-bottom:.85rem;animation-delay:.05s;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
      <div style="flex:1;min-width:0;">
        <?php if($b['image']): ?><img src="<?= htmlspecialchars($b['image']) ?>" alt="" style="width:100%;max-width:320px;height:140px;object-fit:cover;border-radius:10px;margin-bottom:.75rem;"><?php endif; ?>
        <div style="font-weight:600;font-size:.95rem;margin-bottom:.3rem;"><?= htmlspecialchars($b['title']) ?></div>
        <div style="font-size:.8rem;color:var(--muted);margin-bottom:.55rem;">📅 <?= date('M d, Y', strtotime($b['created_at'])) ?></div>
        <div style="font-size:.85rem;color:rgba(255,255,255,.6);line-height:1.55;"><?= nl2br(htmlspecialchars(mb_substr($b['content'],0,200))) ?><?= strlen($b['content'])>200?'…':'' ?></div>
      </div>
      <div class="td-actions" style="flex-shrink:0;">
        <a href="blogs.php?edit=<?= $b['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
        <a href="blogs.php?delete=<?= $b['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete post?')">Delete</a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card" style="margin-top:1rem;"><div class="empty"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg><p>No posts yet. Write your first one above!</p></div></div>
<?php endif; ?>
<?php require_once 'inc/foot.php'; ?>