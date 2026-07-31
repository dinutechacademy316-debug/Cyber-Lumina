<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/db.php';
initDB();
$uid  = (int)$_SESSION['user_id'];
$pdo  = getDB();
$stmt = $pdo->prepare('SELECT * FROM activity_log WHERE user_id=? ORDER BY created_at DESC LIMIT 100');
$stmt->execute([$uid]);
$logs = $stmt->fetchAll();

$actionMeta = [
  'login'           => ['icon'=>'🔐','label'=>'Signed In',        'color'=>'#00f0ff'],
  'logout'          => ['icon'=>'🚪','label'=>'Signed Out',       'color'=>'#8892b0'],
  'profile_update'  => ['icon'=>'👤','label'=>'Profile Updated',  'color'=>'#7000ff'],
  'email_change'    => ['icon'=>'📧','label'=>'Email Changed',    'color'=>'#ffaa00'],
  'password_change' => ['icon'=>'🔑','label'=>'Password Changed', 'color'=>'#ff4466'],
  'phone_change'    => ['icon'=>'📱','label'=>'Phone Changed',    'color'=>'#00ff88'],
  'webcam'          => ['icon'=>'📷','label'=>'Webcam Used',      'color'=>'#a855f7'],
  'location'        => ['icon'=>'📍','label'=>'Location Track',   'color'=>'#22d3ee'],
  'ip_track'        => ['icon'=>'🌐','label'=>'IP Tracked',       'color'=>'#4ade80'],
  'gallery'         => ['icon'=>'🖼️','label'=>'Gallery Access',  'color'=>'#f472b6'],
];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Activity History — CyberLumina</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Inter:wght@300;400;500;600&family=Share+Tech+Mono&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--cyan:#00f0ff;--purple:#7000ff;--bg:#060b13;--card:#0a1520;--muted:#4a5568;--text:#e2e8f0}
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:2rem 1rem;}
.topbar{width:100%;max-width:680px;display:flex;align-items:center;justify-content:space-between;margin-bottom:2rem;}
.topbar-left{display:flex;align-items:center;gap:1rem;}
.back-btn{display:inline-flex;align-items:center;gap:.4rem;color:var(--muted);text-decoration:none;font-size:.8rem;font-family:'Share Tech Mono',monospace;padding:.4rem .8rem;border:1px solid rgba(0,240,255,.1);border-radius:8px;transition:all .2s;}
.back-btn:hover{color:var(--cyan);border-color:rgba(0,240,255,.3);}
.page-title{font-family:'Orbitron',monospace;font-size:1rem;color:var(--cyan);letter-spacing:.1em;}
.count-badge{background:rgba(0,240,255,.08);color:var(--cyan);border:1px solid rgba(0,240,255,.15);border-radius:20px;font-size:.68rem;font-weight:700;padding:.15rem .5rem;font-family:'Share Tech Mono',monospace;}
.stats-grid{width:100%;max-width:680px;display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.75rem;margin-bottom:1.5rem;}
.stat-card{background:var(--card);border:1px solid rgba(0,240,255,.1);border-radius:12px;padding:1rem;text-align:center;}
.stat-num{font-family:'Orbitron',monospace;font-size:1.6rem;color:var(--cyan);margin-bottom:.2rem;}
.stat-label{font-size:.68rem;color:var(--muted);font-family:'Share Tech Mono',monospace;letter-spacing:.06em;}
.timeline{width:100%;max-width:680px;position:relative;}
.timeline::before{content:'';position:absolute;left:19px;top:0;bottom:0;width:1px;background:linear-gradient(180deg,rgba(0,240,255,.3),rgba(0,240,255,.05));pointer-events:none;}
.log-item{display:flex;gap:1rem;margin-bottom:1rem;position:relative;}
.log-dot{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1.5px solid rgba(0,240,255,.2);background:var(--card);font-size:1rem;z-index:1;}
.log-content{background:var(--card);border:1px solid rgba(0,240,255,.1);border-radius:12px;padding:.85rem 1.1rem;flex:1;transition:all .2s;}
.log-content:hover{border-color:rgba(0,240,255,.25);transform:translateX(4px);}
.log-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:.25rem;gap:.5rem;flex-wrap:wrap;}
.log-action{font-size:.82rem;font-weight:600;color:var(--text);}
.log-time{font-size:.68rem;color:var(--muted);font-family:'Share Tech Mono',monospace;white-space:nowrap;}
.log-detail{font-size:.75rem;color:#6b7fa3;margin-top:.15rem;}
.log-ip{font-size:.65rem;color:#333;font-family:'Share Tech Mono',monospace;margin-top:.2rem;}
.empty-state{text-align:center;padding:3rem 1rem;color:var(--muted);width:100%;max-width:680px;}
.empty-icon{font-size:3rem;margin-bottom:1rem;opacity:.3;}
.filter-row{width:100%;max-width:680px;display:flex;gap:.5rem;margin-bottom:1.2rem;flex-wrap:wrap;}
.filter-btn{padding:.35rem .85rem;border-radius:20px;border:1px solid rgba(0,240,255,.1);background:transparent;color:var(--muted);font-size:.72rem;font-family:'Share Tech Mono',monospace;cursor:pointer;transition:all .2s;}
.filter-btn:hover,.filter-btn.active{background:rgba(0,240,255,.08);border-color:rgba(0,240,255,.25);color:var(--cyan);}
</style>
</head>
<body>
<div class="topbar">
  <div class="topbar-left">
    <a class="back-btn" href="index.php">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      Back
    </a>
    <span class="page-title">ACTIVITY HISTORY</span>
  </div>
  <span class="count-badge"><?= count($logs) ?> records</span>
</div>

<?php
// Stats
$loginCount  = count(array_filter($logs, fn($l) => $l['action']==='login'));
$totalActions = count($logs);
$uniqueDays  = count(array_unique(array_map(fn($l) => date('Y-m-d', strtotime($l['created_at'])), $logs)));
?>
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-num"><?= $loginCount ?></div>
    <div class="stat-label">Total Logins</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= $totalActions ?></div>
    <div class="stat-label">Total Actions</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= $uniqueDays ?></div>
    <div class="stat-label">Active Days</div>
  </div>
</div>

<!-- Filter -->
<div class="filter-row">
  <button class="filter-btn active" onclick="filterLogs('all',this)">All</button>
  <button class="filter-btn" onclick="filterLogs('login',this)">Logins</button>
  <button class="filter-btn" onclick="filterLogs('profile',this)">Profile</button>
  <button class="filter-btn" onclick="filterLogs('security',this)">Security</button>
</div>

<?php if (empty($logs)): ?>
<div class="empty-state">
  <div class="empty-icon">📊</div>
  <p>No activity recorded yet.</p>
  <p style="font-size:.75rem;margin-top:.5rem;color:#333;">Activity will appear here after you use the platform.</p>
</div>
<?php else: ?>
<div class="timeline" id="timeline">
<?php foreach ($logs as $log):
  $a = $actionMeta[$log['action']] ?? ['icon'=>'⚡','label'=>ucfirst($log['action']),'color'=>'#8892b0'];
  $t = date('d M Y', strtotime($log['created_at']));
  $h = date('H:i', strtotime($log['created_at']));
  $cat = match(true) {
    in_array($log['action'],['login','logout']) => 'login',
    in_array($log['action'],['profile_update']) => 'profile',
    in_array($log['action'],['password_change','email_change','phone_change']) => 'security',
    default => 'other'
  };
?>
  <div class="log-item" data-cat="<?= $cat ?>">
    <div class="log-dot" style="border-color:<?= $a['color'] ?>22;color:<?= $a['color'] ?>"><?= $a['icon'] ?></div>
    <div class="log-content">
      <div class="log-header">
        <span class="log-action" style="color:<?= $a['color'] ?>"><?= $a['label'] ?></span>
        <span class="log-time"><?= $h ?> · <?= $t ?></span>
      </div>
      <?php if ($log['detail']): ?>
        <div class="log-detail"><?= htmlspecialchars($log['detail']) ?></div>
      <?php endif; ?>
      <?php if ($log['ip']): ?>
        <div class="log-ip">IP: <?= htmlspecialchars($log['ip']) ?></div>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function filterLogs(cat, btn) {
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.log-item').forEach(item => {
    item.style.display = (cat === 'all' || item.dataset.cat === cat) ? 'flex' : 'none';
  });
}
</script>
</body>
</html>
