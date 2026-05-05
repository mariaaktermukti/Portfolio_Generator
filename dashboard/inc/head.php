<?php $pageTitle = $pageTitle ?? 'Dashboard'; $activeNav = $activeNav ?? ''; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — Portfolio Generator</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --primary:#7c3aed;--primary-end:#4f46e5;--accent:#a78bfa;--accent-light:#c4b5fd;
  --glass:rgba(255,255,255,0.06);--border:rgba(255,255,255,0.12);
  --text:#fff;--muted:rgba(255,255,255,0.5);--dim:rgba(255,255,255,0.25);
  --sidebar-w:230px;
}
body{font-family:'Inter',sans-serif;min-height:100vh;display:flex;
  background:linear-gradient(135deg,#0f0c29 0%,#302b63 50%,#24243e 100%) fixed;
  background-size:cover;color:var(--text);}

/* ── SIDEBAR ── */
.sidebar{width:var(--sidebar-w);min-height:100vh;position:fixed;top:0;left:0;z-index:100;
  background:rgba(0,0,0,0.35);backdrop-filter:blur(20px);border-right:1px solid var(--border);
  display:flex;flex-direction:column;padding:1.25rem 0.85rem;transition:transform .3s ease;}
.sb-logo{display:flex;align-items:center;gap:.6rem;padding:.5rem .6rem 1.25rem;border-bottom:1px solid var(--border);margin-bottom:.75rem;}
.sb-logo .icon{width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,var(--primary),var(--primary-end));display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.sb-logo .icon svg{width:17px;height:17px;fill:#fff;}
.sb-logo span{font-weight:700;font-size:.88rem;letter-spacing:-.01em;}
.nav-lbl{font-size:.62rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--dim);padding:.4rem .6rem .25rem;margin-top:.4rem;}
.nav-link{display:flex;align-items:center;gap:.55rem;padding:.6rem .7rem;border-radius:10px;color:var(--muted);text-decoration:none;font-size:.85rem;font-weight:500;transition:all .18s;margin-bottom:2px;border:1px solid transparent;}
.nav-link svg{width:16px;height:16px;flex-shrink:0;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;opacity:.7;transition:opacity .18s;}
.nav-link:hover{background:rgba(255,255,255,0.07);color:var(--text);}
.nav-link:hover svg{opacity:1;}
.nav-link.active{background:rgba(124,58,237,.2);color:var(--accent-light);border-color:rgba(124,58,237,.3);}
.nav-link.active svg{opacity:1;}
.nav-link.danger:hover{background:rgba(239,68,68,.12);color:#fca5a5;border-color:rgba(239,68,68,.25);}
.sb-footer{margin-top:auto;padding-top:.85rem;border-top:1px solid var(--border);}

/* ── MAIN ── */
.main{margin-left:var(--sidebar-w);flex:1;padding:1.75rem 2rem;min-height:100vh;}
.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.75rem;}
.page-title{font-size:1.35rem;font-weight:700;letter-spacing:-.02em;}
.page-sub{font-size:.82rem;color:var(--muted);margin-top:.15rem;}
.user-badge{display:flex;align-items:center;gap:.55rem;background:var(--glass);border:1px solid var(--border);border-radius:50px;padding:.35rem .85rem .35rem .45rem;font-size:.8rem;color:var(--muted);}
.user-badge .av{width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-end));display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#fff;}

/* ── CARD ── */
.card{background:var(--glass);backdrop-filter:blur(20px);border:1px solid var(--border);border-radius:18px;padding:1.5rem;box-shadow:0 8px 32px rgba(0,0,0,.2);animation:slideUp .45s cubic-bezier(.16,1,.3,1) both;}
@keyframes slideUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
.card+.card{margin-top:1.25rem;}
.card-head{display:flex;align-items:center;gap:.7rem;margin-bottom:1.4rem;padding-bottom:1rem;border-bottom:1px solid var(--border);}
.card-icon{width:38px;height:38px;border-radius:11px;background:linear-gradient(135deg,var(--primary),var(--primary-end));display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(124,58,237,.35);flex-shrink:0;}
.card-icon svg{width:17px;height:17px;fill:none;stroke:#fff;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}
.card-title{font-size:.95rem;font-weight:600;}
.card-sub{font-size:.78rem;color:var(--muted);margin-top:.1rem;}

/* ── FORM ── */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:.9rem;}
.form-grid.cols1{grid-template-columns:1fr;}
.fg{display:flex;flex-direction:column;gap:.38rem;}
.fg.span2{grid-column:span 2;}
label{font-size:.8rem;font-weight:500;color:rgba(255,255,255,.7);letter-spacing:.02em;}
input[type=text],input[type=email],input[type=date],input[type=url],input[type=tel],input[type=password],textarea,select{
  width:100%;padding:.68rem .85rem;background:rgba(255,255,255,.07);border:1px solid var(--border);
  border-radius:11px;color:#fff;font-size:.88rem;font-family:'Inter',sans-serif;outline:none;
  transition:border-color .2s,background .2s,box-shadow .2s;}
input::placeholder,textarea::placeholder{color:var(--dim);}
input:focus,textarea:focus,select:focus{border-color:var(--primary);background:rgba(124,58,237,.1);box-shadow:0 0 0 3px rgba(124,58,237,.2);}
select option{background:#302b63;color:#fff;}
textarea{resize:vertical;min-height:95px;}
.form-actions{display:flex;gap:.65rem;margin-top:1.1rem;flex-wrap:wrap;}

/* ── BUTTONS ── */
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.6rem 1.15rem;border:1px solid transparent;border-radius:10px;font-size:.85rem;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;transition:all .18s;text-decoration:none;line-height:1;}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--primary-end));color:#fff;box-shadow:0 3px 12px rgba(124,58,237,.4);}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(124,58,237,.5);filter:brightness(1.07);}
.btn-secondary{background:rgba(255,255,255,.08);color:var(--muted);border-color:var(--border);}
.btn-secondary:hover{background:rgba(255,255,255,.12);color:var(--text);}
.btn-danger{background:rgba(239,68,68,.12);color:#fca5a5;border-color:rgba(239,68,68,.3);}
.btn-danger:hover{background:rgba(239,68,68,.22);}
.btn-sm{padding:.38rem .7rem;font-size:.78rem;border-radius:8px;}
.btn svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;}

/* ── ALERTS ── */
.alert{display:flex;align-items:center;gap:.55rem;padding:.75rem .95rem;border-radius:11px;font-size:.85rem;margin-bottom:1.1rem;}
.alert svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;flex-shrink:0;}
.alert-success{background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.3);color:#6ee7b7;}
.alert-error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.35);color:#fca5a5;animation:shake .4s ease;}
@keyframes shake{0%,100%{transform:translateX(0)}20%{transform:translateX(-5px)}40%{transform:translateX(5px)}60%{transform:translateX(-3px)}80%{transform:translateX(3px)}}

/* ── TABLE ── */
.tbl-wrap{overflow-x:auto;margin-top:1.25rem;}
table{width:100%;border-collapse:collapse;}
th{padding:.65rem .9rem;text-align:left;font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);border-bottom:1px solid var(--border);}
td{padding:.8rem .9rem;font-size:.85rem;border-bottom:1px solid rgba(255,255,255,.05);vertical-align:middle;}
tr:hover td{background:rgba(255,255,255,.03);}
tr:last-child td{border-bottom:none;}
.td-actions{display:flex;gap:.35rem;align-items:center;}

/* ── BADGE ── */
.badge{display:inline-block;padding:.18rem .55rem;border-radius:6px;font-size:.72rem;font-weight:600;}
.badge-b{background:rgba(148,163,184,.15);color:#94a3b8;}
.badge-i{background:rgba(59,130,246,.15);color:#93c5fd;}
.badge-a{background:rgba(16,185,129,.15);color:#6ee7b7;}
.badge-e{background:rgba(124,58,237,.2);color:var(--accent);}

/* ── EMPTY STATE ── */
.empty{text-align:center;padding:2.5rem 1rem;color:var(--muted);}
.empty svg{width:44px;height:44px;opacity:.22;margin-bottom:.75rem;stroke:currentColor;fill:none;stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round;}

/* ── HAMBURGER ── */
.hamburger{display:none;background:var(--glass);border:1px solid var(--border);border-radius:10px;width:36px;height:36px;align-items:center;justify-content:center;cursor:pointer;}
.hamburger svg{width:18px;height:18px;stroke:var(--text);stroke-width:1.8;fill:none;}
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99;}
.overlay.show{display:block;}

/* ── RESPONSIVE ── */
@media(max-width:768px){
  .sidebar{transform:translateX(-100%);}
  .sidebar.open{transform:translateX(0);}
  .main{margin-left:0;padding:1rem;}
  .form-grid{grid-template-columns:1fr;}
  .fg.span2{grid-column:auto;}
  .hamburger{display:flex;}
  .topbar{gap:.75rem;}
}
</style>
</head>
<body>
