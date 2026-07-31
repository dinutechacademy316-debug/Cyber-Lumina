<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/db.php';
initDB();
$uid   = (int)$_SESSION['user_id'];
$pdo   = getDB();
$stmt  = $pdo->prepare('SELECT name, email, phone FROM users WHERE id=?');
$stmt->execute([$uid]);
$user  = $stmt->fetch();
$name  = htmlspecialchars($user['name']  ?? '');
$email = htmlspecialchars($user['email'] ?? '');
$phone = htmlspecialchars($user['phone'] ?? '');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Account Settings — CyberLumina</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Inter:wght@300;400;500;600&family=Share+Tech+Mono&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--cyan:#00f0ff;--purple:#7000ff;--red:#ff4466;--bg:#060b13;--card:#0a1520;--muted:#4a5568;--text:#e2e8f0}
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:2rem 1rem;}
.topbar{width:100%;max-width:640px;display:flex;align-items:center;gap:1rem;margin-bottom:2rem;}
.back-btn{display:inline-flex;align-items:center;gap:.4rem;color:var(--muted);text-decoration:none;font-size:.8rem;font-family:'Share Tech Mono',monospace;padding:.4rem .8rem;border:1px solid rgba(0,240,255,.1);border-radius:8px;transition:all .2s;}
.back-btn:hover{color:var(--cyan);border-color:rgba(0,240,255,.3);}
.page-title{font-family:'Orbitron',monospace;font-size:1rem;color:var(--cyan);letter-spacing:.1em;}
.card{width:100%;max-width:640px;background:var(--card);border:1px solid rgba(0,240,255,.12);border-radius:20px;padding:2rem;position:relative;overflow:hidden;margin-bottom:1.2rem;}
.card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--purple),var(--cyan));}
.card-danger::before{background:linear-gradient(90deg,#ff1a1a,#ff6600);}
.section-label{font-size:.68rem;color:var(--muted);font-family:'Share Tech Mono',monospace;letter-spacing:.12em;text-transform:uppercase;margin-bottom:1.2rem;padding-bottom:.5rem;border-bottom:1px solid rgba(0,240,255,.07);display:flex;align-items:center;gap:.5rem;}
.section-label svg{flex-shrink:0;}
.field{margin-bottom:1rem;}
.field label{display:block;font-size:.72rem;color:var(--muted);font-family:'Share Tech Mono',monospace;letter-spacing:.08em;text-transform:uppercase;margin-bottom:.4rem;}
.field input{width:100%;padding:.75rem 1rem;background:rgba(0,240,255,.04);border:1px solid rgba(0,240,255,.15);border-radius:10px;color:var(--text);font-size:.88rem;outline:none;transition:all .2s;font-family:'Inter',sans-serif;}
.field input:focus{border-color:var(--cyan);background:rgba(0,240,255,.07);}
.pass-wrap{position:relative;}
.pass-wrap input{padding-right:3rem;}
.pass-toggle{position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);padding:.2rem;transition:color .2s;}
.pass-toggle:hover{color:var(--cyan);}
.pass-toggle svg{width:16px;height:16px;}
.pass-toggle .eye-slash{display:none;}
.pass-toggle.is-visible .eye-slash{display:block;}
.pass-toggle.is-visible .eye-pupil{display:none;}
.btn-action{width:100%;padding:.82rem;background:linear-gradient(135deg,rgba(0,240,255,.08),rgba(112,0,255,.12));border:1px solid rgba(0,240,255,.25);border-radius:10px;color:var(--cyan);font-size:.85rem;font-weight:600;cursor:pointer;letter-spacing:.04em;transition:all .25s;display:flex;align-items:center;justify-content:center;gap:.5rem;margin-top:.3rem;}
.btn-action:hover{background:linear-gradient(135deg,rgba(0,240,255,.15),rgba(112,0,255,.2));box-shadow:0 0 20px rgba(0,240,255,.1);transform:translateY(-1px);}
.btn-action:disabled{opacity:.5;cursor:not-allowed;transform:none;}
.btn-danger-action{width:100%;padding:.82rem;background:rgba(255,68,102,.08);border:1px solid rgba(255,68,102,.3);border-radius:10px;color:var(--red);font-size:.85rem;font-weight:600;cursor:pointer;letter-spacing:.04em;transition:all .25s;display:flex;align-items:center;justify-content:center;gap:.5rem;margin-top:.3rem;}
.btn-danger-action:hover{background:rgba(255,68,102,.15);box-shadow:0 0 20px rgba(255,68,102,.12);transform:translateY(-1px);}
.btn-danger-action:disabled{opacity:.5;cursor:not-allowed;transform:none;}
.alert{padding:.7rem 1rem;border-radius:8px;font-size:.82rem;margin-bottom:.9rem;display:none;}
.alert.error{background:rgba(255,68,102,.1);border:1px solid rgba(255,68,102,.25);color:#ff8899;display:block;}
.alert.success{background:rgba(0,255,136,.08);border:1px solid rgba(0,255,136,.2);color:#00ff88;display:block;}
.spinner{width:14px;height:14px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;display:inline-block;}
@keyframes spin{to{transform:rotate(360deg)}}
.delete-warning{background:rgba(255,68,102,.06);border:1px solid rgba(255,68,102,.15);border-radius:10px;padding:1rem;margin-bottom:1rem;font-size:.8rem;color:#ff8899;line-height:1.6;}
.delete-warning strong{color:var(--red);}
/* Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);backdrop-filter:blur(8px);z-index:9999;align-items:center;justify-content:center;}
.modal-overlay.show{display:flex;}
.modal{background:var(--card);border:1px solid rgba(255,68,102,.25);border-radius:16px;padding:2rem;max-width:380px;width:90%;text-align:center;}
.modal h3{font-family:'Orbitron',monospace;color:var(--red);font-size:.95rem;margin-bottom:.75rem;}
.modal p{font-size:.82rem;color:#8892b0;margin-bottom:1.2rem;line-height:1.6;}
.modal-btns{display:flex;gap:.75rem;}
.modal-btns button{flex:1;padding:.75rem;border-radius:10px;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .2s;}
.modal-cancel{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);color:#8892b0;}
.modal-cancel:hover{background:rgba(255,255,255,.09);}
.modal-confirm{background:var(--red);border:none;color:#fff;}
.modal-confirm:hover{background:#e03355;}
</style>
</head>
<body>
<div class="topbar">
  <a class="back-btn" href="index.php">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    Back
  </a>
  <span class="page-title">ACCOUNT SETTINGS</span>
</div>

<!-- Change Email -->
<div class="card">
  <div class="section-label">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
    Change Email Address
  </div>
  <div id="email-error" class="alert error"></div>
  <div id="email-success" class="alert success"></div>
  <div class="field">
    <label>Current Email</label>
    <input type="email" value="<?= $email ?>" readonly style="color:var(--muted);cursor:not-allowed;"/>
  </div>
  <div class="field">
    <label>New Email Address</label>
    <input type="email" id="new-email" placeholder="new@example.com"/>
  </div>
  <button class="btn-action" onclick="updateEmail()">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    Update Email
  </button>
</div>

<!-- Change Password -->
<div class="card">
  <div class="section-label">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
    Change Password
  </div>
  <div id="pass-error" class="alert error"></div>
  <div id="pass-success" class="alert success"></div>
  <div class="field">
    <label>Current Password</label>
    <div class="pass-wrap">
      <input type="password" id="curr-pass" placeholder="••••••••"/>
      <button type="button" class="pass-toggle" onclick="togglePw('curr-pass',this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle class="eye-pupil" cx="12" cy="12" r="3"/><line class="eye-slash" x1="4" y1="4" x2="20" y2="20"/></svg>
      </button>
    </div>
  </div>
  <div class="field">
    <label>New Password</label>
    <div class="pass-wrap">
      <input type="password" id="new-pass" placeholder="Min. 6 characters"/>
      <button type="button" class="pass-toggle" onclick="togglePw('new-pass',this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle class="eye-pupil" cx="12" cy="12" r="3"/><line class="eye-slash" x1="4" y1="4" x2="20" y2="20"/></svg>
      </button>
    </div>
  </div>
  <button class="btn-action" onclick="updatePassword()">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    Update Password
  </button>
</div>

<!-- Change Phone -->
<div class="card">
  <div class="section-label">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.38 2 2 0 0 1 3.58 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.5a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
    Change Phone Number
  </div>
  <div id="phone-error" class="alert error"></div>
  <div id="phone-success" class="alert success"></div>
  <div class="field">
    <label>Current Phone</label>
    <input value="<?= $phone ?: 'Not set' ?>" readonly style="color:var(--muted);cursor:not-allowed;"/>
  </div>
  <div class="field">
    <label>New Phone Number</label>
    <input type="tel" id="new-phone" placeholder="71 234 5678" inputmode="numeric" oninput="this.value=this.value.replace(/\D/g,'')"/>
  </div>
  <button class="btn-action" onclick="updatePhone()">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    Update Phone
  </button>
</div>

<!-- Delete Account -->
<div class="card card-danger">
  <div class="section-label" style="color:var(--red);">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
    Delete Account
  </div>
  <div class="delete-warning">
    ⚠️ <strong>This action is permanent and cannot be undone.</strong><br>
    Your account, data, and activity history will be deleted.<br>
    Your email and phone number will be <strong>blacklisted</strong> — you will not be able to create a new account with them.
  </div>
  <div class="field">
    <label>Confirm with your password</label>
    <div class="pass-wrap">
      <input type="password" id="del-pass" placeholder="Enter password to confirm"/>
      <button type="button" class="pass-toggle" onclick="togglePw('del-pass',this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle class="eye-pupil" cx="12" cy="12" r="3"/><line class="eye-slash" x1="4" y1="4" x2="20" y2="20"/></svg>
      </button>
    </div>
  </div>
  <div id="del-error" class="alert error"></div>
  <button class="btn-danger-action" onclick="showDeleteModal()">
    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
    Delete My Account
  </button>
</div>

<!-- Confirm Modal -->
<div class="modal-overlay" id="del-modal">
  <div class="modal">
    <h3>⚠ Confirm Deletion</h3>
    <p>Are you absolutely sure? This will permanently delete your account and blacklist your email and phone number.</p>
    <div class="modal-btns">
      <button class="modal-cancel" onclick="closeDeleteModal()">Cancel</button>
      <button class="modal-confirm" id="btn-confirm-del" onclick="deleteAccount()">Delete</button>
    </div>
  </div>
</div>

<script>
function togglePw(id, btn) {
  const inp = document.getElementById(id);
  const hide = inp.type === 'password';
  inp.type = hide ? 'text' : 'password';
  btn.classList.toggle('is-visible', hide);
}
function showAlert(errId, sucId, msg, isError) {
  document.getElementById(errId).style.display='none';
  document.getElementById(sucId).style.display='none';
  if (isError) { document.getElementById(errId).textContent=msg; document.getElementById(errId).style.display='block'; }
  else { document.getElementById(sucId).textContent=msg; document.getElementById(sucId).style.display='block'; }
}
async function postAction(action, data, errId, sucId, successMsg) {
  const fd = new FormData();
  fd.append('action', action);
  for (const [k,v] of Object.entries(data)) fd.append(k, v);
  const res  = await fetch('auth.php', {method:'POST', body:fd});
  const json = await res.json();
  if (json.error) showAlert(errId, sucId, json.error, true);
  else { showAlert(errId, sucId, successMsg, false); setTimeout(()=>document.getElementById(sucId).style.display='none',3000); }
}

async function updateEmail() {
  const v = document.getElementById('new-email').value.trim();
  if (!v) { showAlert('email-error','email-success','Please enter a new email.',true); return; }
  await postAction('update_email',{email:v},'email-error','email-success','✅ Email updated successfully!');
}
async function updatePassword() {
  const c = document.getElementById('curr-pass').value;
  const n = document.getElementById('new-pass').value;
  if (!c||!n) { showAlert('pass-error','pass-success','Both fields are required.',true); return; }
  await postAction('update_password',{current_password:c,new_password:n},'pass-error','pass-success','✅ Password updated!');
  document.getElementById('curr-pass').value=''; document.getElementById('new-pass').value='';
}
async function updatePhone() {
  const v = document.getElementById('new-phone').value.trim();
  if (!v) { showAlert('phone-error','phone-success','Please enter a phone number.',true); return; }
  await postAction('update_phone',{phone:v},'phone-error','phone-success','✅ Phone updated!');
}
function showDeleteModal() {
  const p = document.getElementById('del-pass').value;
  if (!p) { document.getElementById('del-error').textContent='Enter your password first.'; document.getElementById('del-error').style.display='block'; return; }
  document.getElementById('del-modal').classList.add('show');
}
function closeDeleteModal() { document.getElementById('del-modal').classList.remove('show'); }
async function deleteAccount() {
  const btn = document.getElementById('btn-confirm-del');
  btn.textContent = 'Deleting...'; btn.disabled = true;
  const fd = new FormData();
  fd.append('action','delete_account');
  fd.append('password', document.getElementById('del-pass').value);
  const res  = await fetch('auth.php',{method:'POST',body:fd});
  const json = await res.json();
  if (json.error) {
    closeDeleteModal();
    document.getElementById('del-error').textContent=json.error; document.getElementById('del-error').style.display='block';
    btn.textContent='Delete'; btn.disabled=false;
  } else {
    window.location.href = 'login.php';
  }
}
</script>
</body>
</html>
