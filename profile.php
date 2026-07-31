<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/db.php';
initDB();
$uid    = (int)$_SESSION['user_id'];
$pdo    = getDB();
$stmt   = $pdo->prepare('SELECT name, email, phone, avatar, profile_photo FROM users WHERE id=?');
$stmt->execute([$uid]);
$user   = $stmt->fetch();
$name   = htmlspecialchars($user['name'] ?? '');
$email  = htmlspecialchars($user['email'] ?? '');
$photo  = $user['profile_photo'] ?? $user['avatar'] ?? '';
$initial = strtoupper(substr($user['name'] ?? 'U', 0, 1));
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>My Profile — CyberLumina</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Inter:wght@300;400;500;600&family=Share+Tech+Mono&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--cyan:#00f0ff;--purple:#7000ff;--bg:#060b13;--card:#0a1520;--muted:#4a5568;--text:#e2e8f0}
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:2rem 1rem;}
.topbar{width:100%;max-width:600px;display:flex;align-items:center;gap:1rem;margin-bottom:2rem;}
.back-btn{display:inline-flex;align-items:center;gap:.4rem;color:var(--muted);text-decoration:none;font-size:.8rem;font-family:'Share Tech Mono',monospace;padding:.4rem .8rem;border:1px solid rgba(0,240,255,.1);border-radius:8px;transition:all .2s;}
.back-btn:hover{color:var(--cyan);border-color:rgba(0,240,255,.3);}
.page-title{font-family:'Orbitron',monospace;font-size:1rem;color:var(--cyan);letter-spacing:.1em;}
.card{width:100%;max-width:600px;background:var(--card);border:1px solid rgba(0,240,255,.12);border-radius:20px;padding:2.5rem 2rem;position:relative;overflow:hidden;}
.card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--purple),var(--cyan));}
/* Avatar area */
.avatar-section{display:flex;flex-direction:column;align-items:center;margin-bottom:2rem;}
.avatar-wrap{position:relative;width:110px;height:110px;margin-bottom:1rem;}
.avatar-img,.avatar-init{width:110px;height:110px;border-radius:50%;border:3px solid rgba(0,240,255,.35);object-fit:cover;display:flex;align-items:center;justify-content:center;}
.avatar-init{background:linear-gradient(135deg,var(--purple),var(--cyan));font-size:2.5rem;font-weight:700;color:#fff;font-family:'Orbitron',monospace;}
.avatar-edit-btn{position:absolute;bottom:2px;right:2px;width:32px;height:32px;border-radius:50%;background:#0a1520;border:2px solid rgba(0,240,255,.3);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;}
.avatar-edit-btn:hover{background:rgba(0,240,255,.1);border-color:var(--cyan);}
.avatar-edit-btn svg{width:14px;height:14px;color:var(--cyan);}
#photo-input{display:none;}
.avatar-hint{font-size:.72rem;color:var(--muted);font-family:'Share Tech Mono',monospace;text-align:center;}
/* Form */
.field{margin-bottom:1.2rem;}
.field label{display:block;font-size:.72rem;color:var(--muted);font-family:'Share Tech Mono',monospace;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.45rem;}
.field input{width:100%;padding:.78rem 1rem;background:rgba(0,240,255,.04);border:1px solid rgba(0,240,255,.15);border-radius:10px;color:var(--text);font-size:.88rem;outline:none;transition:all .2s;font-family:'Inter',sans-serif;}
.field input:focus{border-color:var(--cyan);background:rgba(0,240,255,.07);}
.field .readonly{color:var(--muted);cursor:not-allowed;}
.btn-save{width:100%;padding:.9rem;background:linear-gradient(135deg,var(--purple),var(--cyan));border:none;border-radius:10px;color:#fff;font-size:.9rem;font-weight:700;cursor:pointer;letter-spacing:.05em;transition:all .25s;display:flex;align-items:center;justify-content:center;gap:.5rem;margin-top:.5rem;}
.btn-save:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 8px 24px rgba(0,240,255,.15);}
.btn-save:disabled{opacity:.5;cursor:not-allowed;transform:none;}
.alert{padding:.7rem 1rem;border-radius:8px;font-size:.82rem;margin-bottom:1rem;display:none;}
.alert.error{background:rgba(255,68,102,.1);border:1px solid rgba(255,68,102,.25);color:#ff8899;display:block;}
.alert.success{background:rgba(0,255,136,.08);border:1px solid rgba(0,255,136,.2);color:#00ff88;display:block;}
.spinner{width:16px;height:16px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;display:inline-block;}
@keyframes spin{to{transform:rotate(360deg)}}
.section-label{font-size:.68rem;color:var(--muted);font-family:'Share Tech Mono',monospace;letter-spacing:.12em;text-transform:uppercase;margin-bottom:1.2rem;padding-bottom:.5rem;border-bottom:1px solid rgba(0,240,255,.07);}
</style>
</head>
<body>
<div class="topbar">
  <a class="back-btn" href="index.php">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    Back to Dashboard
  </a>
  <span class="page-title">MY PROFILE</span>
</div>

<div class="card">
  <div id="js-error" class="alert error"></div>
  <div id="js-success" class="alert success"></div>

  <!-- Avatar -->
  <div class="avatar-section">
    <div class="avatar-wrap">
      <?php if ($photo): ?>
        <img src="<?= htmlspecialchars($photo) ?>" class="avatar-img" id="avatar-preview" alt="avatar"/>
      <?php else: ?>
        <div class="avatar-init" id="avatar-preview-init"><?= $initial ?></div>
      <?php endif; ?>
      <label class="avatar-edit-btn" for="photo-input" title="Change photo">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
      </label>
    </div>
    <input type="file" id="photo-input" accept="image/*" onchange="previewPhoto(this)"/>
    <p class="avatar-hint">JPG / PNG / WEBP · Max 3 MB</p>
  </div>

  <div class="section-label">Profile Information</div>
  <form id="profile-form">
    <div class="field">
      <label>Display Name</label>
      <input type="text" id="f-name" value="<?= $name ?>" placeholder="Your full name" required maxlength="100"/>
    </div>
    <div class="field">
      <label>Email Address <span style="color:#ff4466;font-size:.65rem;">(Change in Account Settings)</span></label>
      <input type="email" value="<?= $email ?>" class="readonly" readonly/>
    </div>
    <button class="btn-save" type="submit" id="btn-save">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
      Save Changes
    </button>
  </form>
</div>

<script>
function previewPhoto(input) {
  const file = input.files[0];
  if (!file) return;
  if (file.size > 3*1024*1024) { alert('Photo must be under 3 MB.'); input.value=''; return; }
  const reader = new FileReader();
  reader.onload = e => {
    // Replace avatar display
    const wrap = document.querySelector('.avatar-wrap');
    let img = wrap.querySelector('.avatar-img');
    if (!img) {
      const init = wrap.querySelector('.avatar-init');
      if (init) init.remove();
      img = document.createElement('img');
      img.className = 'avatar-img';
      img.alt = 'avatar';
      wrap.insertBefore(img, wrap.querySelector('.avatar-edit-btn'));
    }
    img.src = e.target.result;
  };
  reader.readAsDataURL(file);
}

document.getElementById('profile-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const errEl = document.getElementById('js-error');
  const sucEl = document.getElementById('js-success');
  errEl.style.display = 'none'; sucEl.style.display = 'none';
  const btn  = document.getElementById('btn-save');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Saving...';

  const fd = new FormData();
  fd.append('action', 'update_profile');
  fd.append('name',   document.getElementById('f-name').value.trim());
  const photoFile = document.getElementById('photo-input').files[0];
  if (photoFile) fd.append('photo', photoFile);

  try {
    const res  = await fetch('auth.php', {method:'POST', body:fd});
    const data = await res.json();
    if (data.error) {
      errEl.textContent = data.error; errEl.style.display='block';
    } else {
      sucEl.textContent = '✅ Profile updated successfully!'; sucEl.style.display='block';
      setTimeout(() => sucEl.style.display='none', 3000);
    }
  } catch(e) {
    errEl.textContent = 'Network error. Please try again.'; errEl.style.display='block';
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Save Changes';
  }
});
</script>
</body>
</html>
