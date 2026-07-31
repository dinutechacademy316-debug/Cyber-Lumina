<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/db.php';
initDB();
$uid  = (int)$_SESSION['user_id'];
$pdo  = getDB();
$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50');
$stmt->execute([$uid]);
$notifs = $stmt->fetchAll();
$unread = array_sum(array_map(fn($n) => $n['is_read'] ? 0 : 1, $notifs));
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Notifications — CyberLumina</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Inter:wght@300;400;500;600&family=Share+Tech+Mono&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--cyan:#00f0ff;--purple:#7000ff;--bg:#060b13;--card:#0a1520;--muted:#4a5568;--text:#e2e8f0}
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:2rem 1rem;}
.topbar{width:100%;max-width:640px;display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;}
.topbar-left{display:flex;align-items:center;gap:1rem;}
.back-btn{display:inline-flex;align-items:center;gap:.4rem;color:var(--muted);text-decoration:none;font-size:.8rem;font-family:'Share Tech Mono',monospace;padding:.4rem .8rem;border:1px solid rgba(0,240,255,.1);border-radius:8px;transition:all .2s;}
.back-btn:hover{color:var(--cyan);border-color:rgba(0,240,255,.3);}
.page-title{font-family:'Orbitron',monospace;font-size:1rem;color:var(--cyan);letter-spacing:.1em;}
.mark-all-btn{font-size:.75rem;color:var(--muted);font-family:'Share Tech Mono',monospace;background:none;border:1px solid rgba(0,240,255,.1);border-radius:8px;padding:.4rem .8rem;cursor:pointer;transition:all .2s;}
.mark-all-btn:hover{color:var(--cyan);border-color:rgba(0,240,255,.3);}
.notif-list{width:100%;max-width:640px;display:flex;flex-direction:column;gap:.75rem;}
.notif-card{background:var(--card);border:1px solid rgba(0,240,255,.1);border-radius:14px;padding:1rem 1.2rem;display:flex;gap:1rem;align-items:flex-start;transition:all .2s;position:relative;overflow:hidden;}
.notif-card.unread{border-color:rgba(0,240,255,.25);background:linear-gradient(135deg,rgba(0,240,255,.04),rgba(112,0,255,.03));}
.notif-card.unread::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--cyan);}
.notif-card:hover{border-color:rgba(0,240,255,.3);transform:translateX(3px);}
.notif-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem;}
.notif-icon.info{background:rgba(0,240,255,.08);border:1px solid rgba(0,240,255,.15);}
.notif-icon.success{background:rgba(0,255,136,.08);border:1px solid rgba(0,255,136,.15);}
.notif-icon.warning{background:rgba(255,170,0,.08);border:1px solid rgba(255,170,0,.15);}
.notif-icon.danger{background:rgba(255,68,102,.08);border:1px solid rgba(255,68,102,.15);}
.notif-body{flex:1;overflow:hidden;}
.notif-title{font-size:.85rem;font-weight:600;color:var(--text);margin-bottom:.2rem;}
.notif-msg{font-size:.78rem;color:#6b7fa3;line-height:1.5;}
.notif-time{font-size:.68rem;color:var(--muted);font-family:'Share Tech Mono',monospace;margin-top:.4rem;}
.notif-dot{width:8px;height:8px;border-radius:50%;background:var(--cyan);flex-shrink:0;margin-top:.35rem;box-shadow:0 0 6px rgba(0,240,255,.5);}
.empty-state{text-align:center;padding:3rem 1rem;color:var(--muted);}
.empty-icon{font-size:3rem;margin-bottom:1rem;opacity:.3;}
.empty-text{font-size:.88rem;}
.read-btn{background:none;border:none;cursor:pointer;padding:.25rem;color:var(--muted);transition:color .2s;flex-shrink:0;}
.read-btn:hover{color:var(--cyan);}
.unread-badge{background:rgba(0,240,255,.1);color:var(--cyan);border:1px solid rgba(0,240,255,.2);border-radius:20px;font-size:.68rem;font-weight:700;padding:.15rem .5rem;font-family:'Share Tech Mono',monospace;margin-left:.5rem;}
</style>
</head>
<body>
<div class="topbar">
  <div class="topbar-left">
    <a class="back-btn" href="index.php">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Back
    </a>
    <span class="page-title">
      NOTIFICATIONS
      <?php if ($unread > 0): ?>
        <span class="unread-badge"><?= $unread ?> new</span>
      <?php endif; ?>
    </span>
  </div>
  <?php if ($notifs): ?>
    <button class="mark-all-btn" onclick="markAllRead()">Mark all as read</button>
  <?php endif; ?>
</div>

<div class="notif-list" id="notif-list">
  <?php if (empty($notifs)): ?>
    <div class="empty-state">
      <div class="empty-icon">🔔</div>
      <p class="empty-text">No notifications yet</p>
    </div>
  <?php else: foreach ($notifs as $n):
    $icons = ['info'=>'🔵','success'=>'✅','warning'=>'⚠️','danger'=>'🔴'];
    $icon  = $icons[$n['type']] ?? '🔵';
    $isUnread = !$n['is_read'];
    $time  = date('d M Y, H:i', strtotime($n['created_at']));
  ?>
    <div class="notif-card <?= $isUnread ? 'unread' : '' ?>" id="notif-<?= $n['id'] ?>">
      <div class="notif-icon <?= htmlspecialchars($n['type']) ?>"><?= $icon ?></div>
      <div class="notif-body">
        <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
        <?php if ($n['message']): ?>
          <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
        <?php endif; ?>
        <div class="notif-time"><?= $time ?></div>
      </div>
      <?php if ($isUnread): ?>
        <div class="notif-dot" title="Unread"></div>
      <?php endif; ?>
      <?php if ($isUnread): ?>
        <button class="read-btn" onclick="markRead(<?= $n['id'] ?>)" title="Mark as read">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </button>
      <?php endif; ?>
    </div>
  <?php endforeach; endif; ?>
</div>

<script>
async function markRead(id) {
  const fd = new FormData(); fd.append('action','mark_read'); fd.append('id', id);
  await fetch('auth.php',{method:'POST',body:fd});
  const card = document.getElementById('notif-'+id);
  if (card) {
    card.classList.remove('unread');
    card.querySelector('.notif-dot')?.remove();
    card.querySelector('.read-btn')?.remove();
    const before = card.style.getPropertyValue('::before');
  }
}
async function markAllRead() {
  const fd = new FormData(); fd.append('action','mark_read'); fd.append('id','all');
  await fetch('auth.php',{method:'POST',body:fd});
  document.querySelectorAll('.notif-card').forEach(c => {
    c.classList.remove('unread');
    c.querySelector('.notif-dot')?.remove();
    c.querySelector('.read-btn')?.remove();
  });
  document.querySelector('.unread-badge')?.remove();
}
</script>
</body>
</html>
