<?php
session_start();
if (isset($_SESSION['user_id'])) { header('Location: index.php'); exit; }
$error = htmlspecialchars($_GET['error'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>CyberLumina — Login</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='12' fill='%23060b13'/%3E%3Cellipse cx='32' cy='32' rx='22' ry='13' fill='none' stroke='%2300f0ff' stroke-width='2.5'/%3E%3Ccircle cx='32' cy='32' r='7' fill='%237000ff' opacity='0.9'/%3E%3Ccircle cx='32' cy='32' r='4' fill='%2300f0ff'/%3E%3Ccircle cx='32' cy='32' r='1.5' fill='%23fff'/%3E%3C/svg%3E"/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@300;400;500;600&family=Share+Tech+Mono&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.2.3/css/flag-icons.min.css"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --cyan: #00f0ff; --purple: #7000ff; --bg: #060b13;
      --surface: #0a1520; --border: rgba(0,240,255,0.12);
      --text: #e2e8f0; --muted: #6b7fa3; --red: #ff4466; --green: #00ff88;
    }
    html, body { min-height: 100vh; background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; overflow-x: hidden; }
    body::before {
      content: ''; position: fixed; inset: 0; z-index: 0;
      background-image: linear-gradient(rgba(0,240,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(0,240,255,0.03) 1px, transparent 1px);
      background-size: 40px 40px; pointer-events: none;
    }
    .glow-orb { position: fixed; border-radius: 50%; pointer-events: none; z-index: 0; filter: blur(80px); opacity: 0.18; }
    .glow-orb.g1 { width:500px; height:500px; background:var(--purple); top:-150px; left:-100px; animation: drift1 12s ease-in-out infinite; }
    .glow-orb.g2 { width:400px; height:400px; background:var(--cyan); bottom:-100px; right:-80px; animation: drift2 14s ease-in-out infinite; }
    @keyframes drift1 { 0%,100%{transform:translate(0,0)} 50%{transform:translate(60px,40px)} }
    @keyframes drift2 { 0%,100%{transform:translate(0,0)} 50%{transform:translate(-40px,-60px)} }
    .page { position:relative;z-index:1;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1rem; }
    .card { width:100%;max-width:460px;background:rgba(10,21,32,0.88);border:1px solid var(--border);border-radius:20px;padding:2.2rem 2rem;backdrop-filter:blur(20px);box-shadow:0 0 0 1px rgba(0,240,255,0.05),0 30px 80px rgba(0,0,0,0.5),0 0 60px rgba(112,0,255,0.08);animation:slideUp 0.5s ease; }
    @keyframes slideUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
    .brand { text-align:center; margin-bottom:1.8rem; }
    .brand-eye { width:64px;height:64px;margin:0 auto 1rem;position:relative;display:flex;align-items:center;justify-content:center; }
    .brand-eye svg { width:64px;height:64px; }
    .eye-pulse { position:absolute;inset:-8px;border-radius:50%;border:1.5px solid rgba(0,240,255,0.3);animation:eyePulse 2s ease-in-out infinite; }
    @keyframes eyePulse { 0%,100%{transform:scale(1);opacity:0.6} 50%{transform:scale(1.15);opacity:0.1} }
    .brand-name { font-family:'Orbitron',sans-serif;font-size:1.5rem;font-weight:900;letter-spacing:.05em;background:linear-gradient(90deg,var(--cyan),var(--purple));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text; }
    .brand-sub { font-size:.72rem;color:var(--muted);font-family:'Share Tech Mono',monospace;letter-spacing:.12em;margin-top:.3rem; }
    .tabs { display:flex;background:rgba(0,240,255,.04);border:1px solid var(--border);border-radius:10px;padding:4px;margin-bottom:1.5rem; }
    .tab-btn { flex:1;padding:.6rem;background:transparent;border:none;color:var(--muted);font-family:'Inter',sans-serif;font-size:.85rem;font-weight:500;border-radius:7px;cursor:pointer;transition:all .25s; }
    .tab-btn.active { background:linear-gradient(135deg,rgba(0,240,255,.15),rgba(112,0,255,.15));color:var(--cyan);border:1px solid rgba(0,240,255,.2);box-shadow:0 2px 12px rgba(0,240,255,.1); }
    .tab-panel { display:none; }
    .tab-panel.active { display:block;animation:fadeIn .3s ease; }
    @keyframes fadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
    .field { margin-bottom:1rem; }
    .field label { display:block;font-size:.72rem;font-weight:500;color:var(--muted);margin-bottom:.4rem;font-family:'Share Tech Mono',monospace;letter-spacing:.05em;text-transform:uppercase; }
    .field input { width:100%;padding:.72rem 1rem;background:rgba(0,240,255,.04);border:1px solid rgba(0,240,255,.15);border-radius:10px;color:var(--text);font-size:.88rem;font-family:'Inter',sans-serif;outline:none;transition:all .2s; }
    .field input:focus { border-color:var(--cyan);background:rgba(0,240,255,.07);box-shadow:0 0 0 3px rgba(0,240,255,.08); }
    .field input::placeholder { color:rgba(107,127,163,0.55); }

    /* ── Phone row ── */
    .phone-row { display:flex;gap:.5rem; }
    .country-select-wrap { position:relative;flex-shrink:0; }
    .country-trigger {
      display:flex;align-items:center;gap:.4rem;
      padding:.72rem .75rem;
      background:rgba(0,240,255,.04);border:1px solid rgba(0,240,255,.15);
      border-radius:10px;cursor:pointer;font-size:.88rem;
      color:var(--text);white-space:nowrap;
      transition:all .2s;min-width:90px;user-select:none;
    }
    .country-trigger:hover { border-color:var(--cyan);background:rgba(0,240,255,.07); }
    /* flag-icons sizing */
    .fi { width:22px!important;height:16px!important;border-radius:3px;display:inline-block;flex-shrink:0;box-shadow:0 0 0 1px rgba(255,255,255,0.08); }
    .cd-item .fi { width:24px!important;height:17px!important; }
    .country-trigger .arrow { font-size:.6rem;opacity:.5;margin-left:auto; }
    .country-dropdown {
      position:absolute;top:calc(100% + 6px);left:0;z-index:999;
      width:280px;max-height:240px;overflow-y:auto;
      background:#0d1f30;border:1px solid rgba(0,240,255,.2);
      border-radius:10px;box-shadow:0 12px 40px rgba(0,0,0,.6);
      display:none;
    }
    .country-dropdown.open { display:block;animation:fadeIn .2s ease; }
    .cd-search { padding:.5rem .75rem;border-bottom:1px solid rgba(0,240,255,.1); }
    .cd-search input {
      width:100%;background:rgba(0,240,255,.05);border:1px solid rgba(0,240,255,.12);
      border-radius:6px;padding:.4rem .6rem;color:var(--text);font-size:.8rem;outline:none;
    }
    .cd-list { }
    .cd-item {
      display:flex;align-items:center;gap:.5rem;
      padding:.5rem .75rem;cursor:pointer;font-size:.82rem;color:#c5d5e8;
      transition:background .15s;
    }
    .cd-item:hover { background:rgba(0,240,255,.08); }
    .cd-item .flag { font-size:1.1rem;flex-shrink:0; }
    .cd-item .cname { flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
    .cd-item .ccode { color:var(--cyan);font-family:'Share Tech Mono',monospace;font-size:.78rem;flex-shrink:0; }
    .phone-input-wrap { flex:1; }

    /* ── OTP Section ── */
    .otp-section { display:none;animation:fadeIn .3s ease; }
    .otp-section.show { display:block; }
    .otp-row { display:flex;gap:.5rem; }
    .otp-row input { flex:1;text-align:center;font-size:1.3rem;font-family:'Share Tech Mono',monospace;letter-spacing:.3em;padding:.72rem .5rem; }
    .otp-timer { font-size:.75rem;color:var(--muted);font-family:'Share Tech Mono',monospace;text-align:center;margin-top:.35rem; }
    .otp-timer span { color:#ffaa00; }
    .otp-resend { font-size:.75rem;text-align:center;margin-top:.4rem;color:var(--muted); }
    .otp-resend a { color:var(--cyan);cursor:pointer;text-decoration:none; }
    .otp-resend a:hover { text-decoration:underline; }

    /* ── Buttons ── */
    .btn-primary { width:100%;padding:.85rem;background:linear-gradient(135deg,rgba(0,240,255,.2),rgba(112,0,255,.25));border:1px solid rgba(0,240,255,.35);border-radius:10px;color:var(--cyan);font-size:.88rem;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;letter-spacing:.03em;transition:all .25s;position:relative;overflow:hidden; }
    .btn-primary:hover { background:linear-gradient(135deg,rgba(0,240,255,.3),rgba(112,0,255,.35));box-shadow:0 0 20px rgba(0,240,255,.2);transform:translateY(-1px); }
    .btn-primary:disabled { opacity:.5;cursor:not-allowed;transform:none; }
    .btn-send-otp { width:100%;padding:.75rem;background:rgba(0,240,255,.08);border:1px solid rgba(0,240,255,.25);border-radius:10px;color:var(--cyan);font-size:.85rem;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;transition:all .25s;margin-top:.25rem; }
    .btn-send-otp:hover { background:rgba(0,240,255,.15); }
    .btn-send-otp:disabled { opacity:.45;cursor:not-allowed; }
    .divider { display:flex;align-items:center;gap:.75rem;margin:1.1rem 0; }
    .divider::before,.divider::after { content:'';flex:1;height:1px;background:var(--border); }
    .divider span { font-size:.72rem;color:var(--muted);font-family:'Share Tech Mono',monospace;letter-spacing:.1em; }
    .btn-google { width:100%;padding:.82rem;display:flex;align-items:center;justify-content:center;gap:.75rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);border-radius:10px;color:#e2e8f0;font-size:.88rem;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;text-decoration:none;transition:all .25s; }
    .btn-google:hover { background:rgba(255,255,255,.09);border-color:rgba(255,255,255,.2);transform:translateY(-1px);box-shadow:0 4px 16px rgba(0,0,0,.3); }
    .btn-google svg { width:20px;height:20px;flex-shrink:0; }

    /* ── Forgot Password ── */
    .forgot-link { display:block;text-align:right;font-size:.75rem;color:var(--muted);text-decoration:none;margin-top:-.3rem;margin-bottom:.8rem;font-family:'Share Tech Mono',monospace;letter-spacing:.03em;transition:color .2s;cursor:pointer; }
    .forgot-link:hover { color:var(--cyan); }
    .forgot-panel { display:none;animation:fadeIn .3s ease; }
    .forgot-panel.show { display:block; }
    .step-indicator { display:flex;align-items:center;justify-content:center;gap:.4rem;margin-bottom:1.4rem; }
    .step-dot { width:28px;height:28px;border-radius:50%;border:1.5px solid rgba(0,240,255,.2);display:flex;align-items:center;justify-content:center;font-size:.7rem;color:var(--muted);font-family:'Share Tech Mono',monospace;transition:all .3s; }
    .step-dot.active { border-color:var(--cyan);color:var(--cyan);background:rgba(0,240,255,.08);box-shadow:0 0 12px rgba(0,240,255,.2); }
    .step-dot.done { border-color:#00ff88;color:#00ff88;background:rgba(0,255,136,.08); }
    .step-line { flex:1;height:1px;background:rgba(0,240,255,.1);max-width:40px; }
    .forgot-step { display:none; }
    .forgot-step.active { display:block;animation:fadeIn .3s ease; }
    .back-link { display:inline-flex;align-items:center;gap:.3rem;font-size:.75rem;color:var(--muted);cursor:pointer;margin-bottom:1rem;font-family:'Share Tech Mono',monospace;letter-spacing:.03em;transition:color .2s;background:none;border:none;padding:0; }
    .back-link:hover { color:var(--cyan); }
    .btn-danger { width:100%;padding:.85rem;background:linear-gradient(135deg,rgba(255,68,102,.15),rgba(112,0,255,.2));border:1px solid rgba(255,68,102,.35);border-radius:10px;color:#ff6688;font-size:.88rem;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;letter-spacing:.03em;transition:all .25s; }
    .btn-danger:hover { background:linear-gradient(135deg,rgba(255,68,102,.25),rgba(112,0,255,.3));box-shadow:0 0 20px rgba(255,68,102,.15);transform:translateY(-1px); }
    .btn-danger:disabled { opacity:.5;cursor:not-allowed;transform:none; }
    .alert { padding:.72rem 1rem;border-radius:8px;font-size:.82rem;margin-bottom:.9rem;display:none;animation:fadeIn .3s ease; }
    .alert.error { background:rgba(255,68,102,.1);border:1px solid rgba(255,68,102,.25);color:#ff8899;display:block; }
    .alert.success { background:rgba(0,255,136,.08);border:1px solid rgba(0,255,136,.2);color:#00ff88;display:block; }
    #js-error { display:none; }
    .strength-bar { height:3px;border-radius:2px;background:rgba(255,255,255,.06);margin-top:.35rem;overflow:hidden; }
    .strength-fill { height:100%;width:0%;border-radius:2px;transition:width .3s,background .3s; }
    .spinner { display:inline-block;width:15px;height:15px;border:2px solid rgba(0,240,255,.3);border-top-color:var(--cyan);border-radius:50%;animation:spin .6s linear infinite;vertical-align:middle;margin-right:5px; }
    @keyframes spin { to{transform:rotate(360deg)} }

    /* ── Password show/hide wrap ── */
    .pass-wrap { position:relative; }
    .pass-wrap input { padding-right:3rem!important; }
    .pass-toggle {
      position:absolute;right:.7rem;top:50%;transform:translateY(-50%);
      background:none;border:none;cursor:pointer;
      width:30px;height:30px;
      display:flex;align-items:center;justify-content:center;
      border-radius:6px;
      color:var(--muted);
      transition:color .2s, background .2s, box-shadow .2s;
      user-select:none;flex-shrink:0;
    }
    .pass-toggle:hover {
      color:var(--cyan);
      background:rgba(0,240,255,0.08);
      box-shadow:0 0 10px rgba(0,240,255,0.15);
    }
    .pass-toggle:active { transform:translateY(-50%) scale(0.9); }
    .pass-toggle svg { width:18px;height:18px;display:block;transition:opacity .2s; }
    /* eye-off line (the slash) animates in/out */
    .pass-toggle .eye-slash {
      position:absolute;
      stroke-dasharray:30;
      stroke-dashoffset:30;
      transition:stroke-dashoffset .25s ease;
    }
    .pass-toggle.is-visible .eye-slash { stroke-dashoffset:0; }
    .pass-toggle.is-visible .eye-pupil { opacity:0; transition:opacity .2s; }
    .card-footer { text-align:center;margin-top:1.3rem;font-size:.72rem;color:var(--muted);font-family:'Share Tech Mono',monospace;letter-spacing:.05em;border-top:1px solid var(--border);padding-top:.9rem; }
    .card-footer span { color:rgba(0,240,255,.5); }
    .card-scan { position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,var(--cyan),transparent);border-radius:20px 20px 0 0;animation:scanLine 3s ease-in-out infinite;opacity:.6; }
    @keyframes scanLine { 0%{opacity:0;transform:translateX(-100%)} 20%{opacity:.8} 80%{opacity:.8} 100%{opacity:0;transform:translateX(100%)} }
    .card-wrap { position:relative; }

    /* Scrollbar for dropdown */
    .country-dropdown::-webkit-scrollbar { width:4px; }
    .country-dropdown::-webkit-scrollbar-track { background:transparent; }
    .country-dropdown::-webkit-scrollbar-thumb { background:rgba(0,240,255,.2);border-radius:4px; }
  </style>
</head>
<body>
  <div class="glow-orb g1"></div>
  <div class="glow-orb g2"></div>

  <div class="page">
    <div class="card-wrap">
      <div class="card-scan"></div>
      <div class="card">
        <!-- Brand -->
        <div class="brand">
          <div class="brand-eye">
            <div class="eye-pulse"></div>
            <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
              <defs><filter id="glow"><feGaussianBlur stdDeviation="2.5" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter></defs>
              <ellipse cx="32" cy="32" rx="26" ry="16" fill="none" stroke="#00f0ff" stroke-width="2" filter="url(#glow)"/>
              <circle cx="32" cy="32" r="9" fill="#7000ff" opacity="0.9"/>
              <circle cx="32" cy="32" r="5.5" fill="#00f0ff" filter="url(#glow)"/>
              <circle cx="32" cy="32" r="2" fill="#ffffff"/>
              <circle cx="6" cy="32" r="1.5" fill="#00f0ff" opacity=".5"/>
              <circle cx="58" cy="32" r="1.5" fill="#00f0ff" opacity=".5"/>
            </svg>
          </div>
          <div class="brand-name">CyberLumina</div>
          <div class="brand-sub">ADVANCED SURVEILLANCE PLATFORM</div>
        </div>

        <?php if ($error): ?>
        <div class="alert error"><?= $error ?></div>
        <?php endif; ?>
        <div class="alert error" id="js-error"></div>
        <div class="alert success" id="js-success"></div>

        <!-- Tabs -->
        <div class="tabs">
          <button class="tab-btn active" onclick="switchTab('login')"    id="tab-login-btn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;margin-right:5px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Login
          </button>
          <button class="tab-btn"        onclick="switchTab('register')" id="tab-reg-btn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;margin-right:5px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>Register
          </button>
        </div>

        <!-- ══ LOGIN PANEL ══ -->
        <div class="tab-panel active" id="panel-login">
          <form id="form-login" onsubmit="submitLogin(event)">
            <div class="field">
              <label>Email Address</label>
              <input type="email" id="l-email" placeholder="you@example.com" required autocomplete="email"/>
            </div>
            <div class="field">
              <label>Password</label>
              <div class="pass-wrap">
                <input type="password" id="l-pass" placeholder="••••••••" required autocomplete="current-password"/>
                <button type="button" class="pass-toggle" id="toggle-l-pass" onclick="togglePassView('l-pass','toggle-l-pass')" title="Show / Hide password" aria-label="Toggle password visibility">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                    <circle class="eye-pupil" cx="12" cy="12" r="3"/>
                    <line class="eye-slash" x1="4" y1="4" x2="20" y2="20" stroke="currentColor" stroke-width="1.8"/>
                  </svg>
                </button>
              </div>
            </div>
            <a class="forgot-link" onclick="showForgotPanel()">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;margin-right:3px;"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>Forgot password?
            </a>
            <button class="btn-primary" type="submit" id="btn-login">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;margin-right:5px;"><polyline points="13 17 18 12 13 7"/><path d="M6 12h11"/><path d="M3 3h18v18H3z" stroke-width="0" fill="none"/></svg>Sign In
            </button>
          </form>
          <div class="divider"><span>OR CONTINUE WITH</span></div>
          <a class="btn-google" href="auth.php?action=google_login">
            <svg viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
            Continue with Google
          </a>
        </div>

        <!-- ══ FORGOT PASSWORD PANEL ══ -->
        <div class="forgot-panel" id="forgot-panel">
          <button class="back-link" onclick="backToLogin()">
            ← Back to Login
          </button>

          <!-- Step indicator -->
          <div class="step-indicator">
            <div class="step-dot active" id="fdot-1">1</div>
            <div class="step-line"></div>
            <div class="step-dot" id="fdot-2">2</div>
            <div class="step-line"></div>
            <div class="step-dot" id="fdot-3">3</div>
          </div>

          <!-- STEP 1: Email -->
          <div class="forgot-step active" id="fstep-1">
            <div style="text-align:center;margin-bottom:1.2rem;">
              <p style="font-size:.72rem;color:var(--muted);font-family:'Share Tech Mono',monospace;letter-spacing:.1em;text-transform:uppercase;margin:0 0 .3rem;">Step 1 of 3</p>
              <p style="font-size:.88rem;color:#8892b0;margin:0;">Enter your registered email address</p>
            </div>
            <div class="field">
              <label>Email Address</label>
              <input type="email" id="f-email" placeholder="you@example.com" autocomplete="email"/>
            </div>
            <button class="btn-danger" type="button" id="btn-forgot-send" onclick="forgotSendOtp()">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;margin-right:5px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>Send Reset Code
            </button>
          </div>

          <!-- STEP 2: OTP -->
          <div class="forgot-step" id="fstep-2">
            <div style="text-align:center;margin-bottom:1.2rem;">
              <p style="font-size:.72rem;color:var(--muted);font-family:'Share Tech Mono',monospace;letter-spacing:.1em;text-transform:uppercase;margin:0 0 .3rem;">Step 2 of 3</p>
              <p style="font-size:.88rem;color:#8892b0;margin:0;">Enter the 6-digit code sent to your email</p>
            </div>
            <div class="field">
              <label>Reset Code <span style="color:#ff6688;font-size:.7rem;" id="f-email-hint"></span></label>
              <div class="otp-row">
                <input type="text" id="f-otp" placeholder="000000" maxlength="6" inputmode="numeric"
                       oninput="this.value=this.value.replace(/\D/g,'')"
                       style="font-size:1.4rem;letter-spacing:.4em;text-align:center;font-family:'Share Tech Mono',monospace;"/>
              </div>
              <div class="otp-timer" id="f-otp-timer" style="display:none;">
                Code expires in <span id="f-countdown" style="color:#ffaa00;">10:00</span>
              </div>
            </div>
            <button class="btn-danger" type="button" id="btn-forgot-verify" onclick="forgotVerifyOtp()">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;margin-right:5px;"><polyline points="20 6 9 17 4 12"/></svg>Verify Code
            </button>
            <div style="text-align:center;margin-top:.7rem;">
              <a style="font-size:.75rem;color:var(--muted);cursor:pointer;font-family:'Share Tech Mono',monospace;" onclick="forgotSendOtp(true)">
                Resend code
              </a>
            </div>
          </div>

          <!-- STEP 3: New Password -->
          <div class="forgot-step" id="fstep-3">
            <div style="text-align:center;margin-bottom:1.2rem;">
              <p style="font-size:.72rem;color:var(--muted);font-family:'Share Tech Mono',monospace;letter-spacing:.1em;text-transform:uppercase;margin:0 0 .3rem;">Step 3 of 3</p>
              <p style="font-size:.88rem;color:#8892b0;margin:0;">Set your new password</p>
            </div>
            <div class="field">
              <label>New Password</label>
              <div class="pass-wrap">
                <input type="password" id="f-newpass" placeholder="Min. 6 characters" autocomplete="new-password" oninput="updateStrength(this.value)"/>
                <button type="button" class="pass-toggle" id="toggle-f-newpass" onclick="togglePassView('f-newpass','toggle-f-newpass')" title="Show / Hide password" aria-label="Toggle password visibility">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                    <circle class="eye-pupil" cx="12" cy="12" r="3"/>
                    <line class="eye-slash" x1="4" y1="4" x2="20" y2="20" stroke="currentColor" stroke-width="1.8"/>
                  </svg>
                </button>
              </div>
              <div class="strength-bar"><div class="strength-fill" id="strength-fill-f"></div></div>
            </div>
            <button class="btn-primary" type="button" id="btn-forgot-reset" onclick="forgotReset()">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;margin-right:5px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>Update Password
            </button>
          </div>
        </div>

        <!-- ══ REGISTER PANEL ══ -->
        <div class="tab-panel" id="panel-register">

          <!-- STEP 1: Form fields -->
          <div id="reg-step1">
            <div class="field">
              <label>Full Name</label>
              <input type="text" id="r-name" placeholder="John Doe" required autocomplete="name"/>
            </div>
            <div class="field">
              <label>Email Address</label>
              <input type="email" id="r-email" placeholder="you@example.com" required autocomplete="email"/>
            </div>
            <div class="field">
              <label>Phone Number</label>
              <div class="phone-row">
                <!-- Country Code Picker -->
                <div class="country-select-wrap" id="country-wrap">
                  <div class="country-trigger" id="country-trigger" onclick="toggleCountryDropdown()">
                    <span id="selected-flag" class="fi fi-lk"></span>
                    <span id="selected-code">+94</span>
                    <span class="arrow">▼</span>
                  </div>
                  <div class="country-dropdown" id="country-dropdown">
                    <div class="cd-search">
                      <input type="text" id="country-search" placeholder="Search country..." oninput="filterCountries(this.value)" autocomplete="off"/>
                    </div>
                    <div class="cd-list" id="country-list"></div>
                  </div>
                </div>
                <!-- Phone input -->
                <div class="phone-input-wrap">
                  <input type="tel" id="r-phone" placeholder="71 234 5678" autocomplete="tel-national"
                         inputmode="numeric" pattern="[0-9]*"
                         oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                         required
                         style="width:100%;padding:.72rem 1rem;background:rgba(0,240,255,.04);border:1px solid rgba(0,240,255,.15);border-radius:10px;color:#e2e8f0;font-size:.88rem;outline:none;transition:all .2s;font-family:Inter,sans-serif;"
                         onfocus="this.style.borderColor='#00f0ff';this.style.background='rgba(0,240,255,.07)'"
                         onblur="this.style.borderColor='rgba(0,240,255,.15)';this.style.background='rgba(0,240,255,.04)'"/>
                </div>
              </div>
            </div>
            <div class="field">
              <label>Password</label>
              <div class="pass-wrap">
                <input type="password" id="r-pass" placeholder="Min. 6 characters" required autocomplete="new-password" oninput="updateStrength(this.value)"/>
                <button type="button" class="pass-toggle" id="toggle-r-pass" onclick="togglePassView('r-pass','toggle-r-pass')" title="Show / Hide password" aria-label="Toggle password visibility">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                    <!-- eye outline -->
                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                    <!-- pupil -->
                    <circle class="eye-pupil" cx="12" cy="12" r="3"/>
                    <!-- slash line (hidden by default) -->
                    <line class="eye-slash" x1="4" y1="4" x2="20" y2="20" stroke="currentColor" stroke-width="1.8"/>
                  </svg>
                </button>
              </div>
              <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
            </div>
            <button class="btn-send-otp" type="button" id="btn-send-otp" onclick="sendOtp()">
              📧 Send Verification Code
            </button>
          </div>

          <!-- STEP 2: OTP Entry -->
          <div class="otp-section" id="otp-section">
            <div class="field">
              <label>Verification Code <span style="color:var(--cyan);font-size:.7rem;" id="otp-email-hint"></span></label>
              <div class="otp-row">
                <input type="text" id="r-otp" placeholder="000000" maxlength="6" inputmode="numeric"
                       oninput="this.value=this.value.replace(/\D/g,'')"/>
              </div>
              <div class="otp-timer" id="otp-timer" style="display:none;">
                Code expires in <span id="otp-countdown">10:00</span>
              </div>
              <div class="otp-resend" id="otp-resend" style="display:none;">
                Didn't receive it? <a onclick="sendOtp(true)">Resend code</a>
              </div>
            </div>
            <button class="btn-primary" type="button" id="btn-register" onclick="submitRegister()">
              🚀 Create Account
            </button>
          </div>

          <div class="divider"><span>OR SIGN UP WITH</span></div>
          <a class="btn-google" href="auth.php?action=google_login">
            <svg viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
            Sign up with Google
          </a>
        </div>

        <div class="card-footer">
          <span>CyberLumina</span> · Advanced Surveillance Platform · <span>v3.0</span>
        </div>
      </div>
    </div>
  </div>

<script>
// ══════════════════════════════════════════════════════════════
// COUNTRY CODES DATA
// ══════════════════════════════════════════════════════════════
const COUNTRIES = [
  {iso:'lk',name:'Sri Lanka',code:'+94'},
  {iso:'in',name:'India',code:'+91'},
  {iso:'us',name:'United States',code:'+1'},
  {iso:'gb',name:'United Kingdom',code:'+44'},
  {iso:'au',name:'Australia',code:'+61'},
  {iso:'ca',name:'Canada',code:'+1'},
  {iso:'de',name:'Germany',code:'+49'},
  {iso:'fr',name:'France',code:'+33'},
  {iso:'jp',name:'Japan',code:'+81'},
  {iso:'cn',name:'China',code:'+86'},
  {iso:'ru',name:'Russia',code:'+7'},
  {iso:'br',name:'Brazil',code:'+55'},
  {iso:'za',name:'South Africa',code:'+27'},
  {iso:'sg',name:'Singapore',code:'+65'},
  {iso:'my',name:'Malaysia',code:'+60'},
  {iso:'pk',name:'Pakistan',code:'+92'},
  {iso:'bd',name:'Bangladesh',code:'+880'},
  {iso:'np',name:'Nepal',code:'+977'},
  {iso:'mv',name:'Maldives',code:'+960'},
  {iso:'ae',name:'UAE',code:'+971'},
  {iso:'sa',name:'Saudi Arabia',code:'+966'},
  {iso:'qa',name:'Qatar',code:'+974'},
  {iso:'kw',name:'Kuwait',code:'+965'},
  {iso:'bh',name:'Bahrain',code:'+973'},
  {iso:'om',name:'Oman',code:'+968'},
  {iso:'id',name:'Indonesia',code:'+62'},
  {iso:'ph',name:'Philippines',code:'+63'},
  {iso:'th',name:'Thailand',code:'+66'},
  {iso:'vn',name:'Vietnam',code:'+84'},
  {iso:'kr',name:'South Korea',code:'+82'},
  {iso:'tr',name:'Turkey',code:'+90'},
  {iso:'it',name:'Italy',code:'+39'},
  {iso:'es',name:'Spain',code:'+34'},
  {iso:'nl',name:'Netherlands',code:'+31'},
  {iso:'be',name:'Belgium',code:'+32'},
  {iso:'ch',name:'Switzerland',code:'+41'},
  {iso:'se',name:'Sweden',code:'+46'},
  {iso:'no',name:'Norway',code:'+47'},
  {iso:'dk',name:'Denmark',code:'+45'},
  {iso:'fi',name:'Finland',code:'+358'},
  {iso:'pl',name:'Poland',code:'+48'},
  {iso:'ua',name:'Ukraine',code:'+380'},
  {iso:'il',name:'Israel',code:'+972'},
  {iso:'eg',name:'Egypt',code:'+20'},
  {iso:'ng',name:'Nigeria',code:'+234'},
  {iso:'ke',name:'Kenya',code:'+254'},
  {iso:'gh',name:'Ghana',code:'+233'},
  {iso:'mx',name:'Mexico',code:'+52'},
  {iso:'ar',name:'Argentina',code:'+54'},
  {iso:'cl',name:'Chile',code:'+56'},
  {iso:'co',name:'Colombia',code:'+57'},
  {iso:'pe',name:'Peru',code:'+51'},
  {iso:'nz',name:'New Zealand',code:'+64'},
  {iso:'ir',name:'Iran',code:'+98'},
  {iso:'iq',name:'Iraq',code:'+964'},
  {iso:'jo',name:'Jordan',code:'+962'},
  {iso:'lb',name:'Lebanon',code:'+961'},
  {iso:'pt',name:'Portugal',code:'+351'},
  {iso:'gr',name:'Greece',code:'+30'},
  {iso:'hu',name:'Hungary',code:'+36'},
  {iso:'ro',name:'Romania',code:'+40'},
  {iso:'cz',name:'Czech Republic',code:'+420'},
  {iso:'at',name:'Austria',code:'+43'},
  {iso:'hk',name:'Hong Kong',code:'+852'},
  {iso:'tw',name:'Taiwan',code:'+886'},
  {iso:'mm',name:'Myanmar',code:'+95'},
  {iso:'kh',name:'Cambodia',code:'+855'},
  {iso:'pg',name:'Papua New Guinea',code:'+675'},
];

let selectedCountry = COUNTRIES[0]; // default Sri Lanka (lk)
let otpTimer = null;
let otpSeconds = 600;

// ── Init dropdown ──
function buildCountryList(filter='') {
  const list = document.getElementById('country-list');
  const items = COUNTRIES.filter(c =>
    c.name.toLowerCase().includes(filter.toLowerCase()) ||
    c.code.includes(filter) ||
    c.iso.includes(filter.toLowerCase())
  );
  list.innerHTML = items.map(c => `
    <div class="cd-item" onclick="selectCountry('${c.iso}','${c.name}','${c.code}')">
      <span class="fi fi-${c.iso}"></span>
      <span class="cname">${c.name}</span>
      <span class="ccode">${c.code}</span>
    </div>
  `).join('');
}

function filterCountries(q) { buildCountryList(q); }

function selectCountry(iso, name, code) {
  selectedCountry = {iso, name, code};
  const flagEl = document.getElementById('selected-flag');
  flagEl.className = 'fi fi-' + iso;
  flagEl.textContent = '';
  document.getElementById('selected-code').textContent = code;
  closeCountryDropdown();
}

function toggleCountryDropdown() {
  const dd = document.getElementById('country-dropdown');
  const open = dd.classList.toggle('open');
  if (open) {
    buildCountryList();
    setTimeout(() => document.getElementById('country-search').focus(), 50);
  }
}
function closeCountryDropdown() {
  document.getElementById('country-dropdown').classList.remove('open');
  document.getElementById('country-search').value = '';
}

// Close on outside click
document.addEventListener('click', e => {
  if (!document.getElementById('country-wrap').contains(e.target)) closeCountryDropdown();
});

// ── OTP Timer ──────────────────────────────────────────────────
function startOtpTimer() {
  clearInterval(otpTimer);
  otpSeconds = 600;
  document.getElementById('otp-timer').style.display = 'block';
  document.getElementById('otp-resend').style.display = 'none';
  updateTimerDisplay();
  otpTimer = setInterval(() => {
    otpSeconds--;
    updateTimerDisplay();
    if (otpSeconds <= 0) {
      clearInterval(otpTimer);
      document.getElementById('otp-countdown').textContent = 'Expired';
      document.getElementById('otp-countdown').style.color = '#ff4466';
      document.getElementById('otp-resend').style.display = 'block';
    }
  }, 1000);
}
function updateTimerDisplay() {
  const m = String(Math.floor(otpSeconds/60)).padStart(2,'0');
  const s = String(otpSeconds%60).padStart(2,'0');
  document.getElementById('otp-countdown').textContent = m+':'+s;
  document.getElementById('otp-countdown').style.color = otpSeconds < 60 ? '#ff4466' : '#ffaa00';
}

// ── Send OTP ───────────────────────────────────────────────────
async function sendOtp(isResend=false) {
  clearAlerts();
  const name  = document.getElementById('r-name').value.trim();
  const email = document.getElementById('r-email').value.trim();
  const pass  = document.getElementById('r-pass').value;

  if (!name)  { showError('Please enter your full name.'); return; }
  if (!email) { showError('Please enter your email address.'); return; }
  const phone = document.getElementById('r-phone').value.trim();
  if (!phone || phone.length < 5) { showError('Please enter a valid phone number.'); return; }
  if (!pass || pass.length < 6) { showError('Password must be at least 6 characters.'); return; }

  const btn = document.getElementById('btn-send-otp');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Sending...';

  try {
    const fd = new FormData();
    fd.append('action','send_otp');
    fd.append('email', email);
    const res  = await fetch('auth.php', {method:'POST', body:fd});
    const data = await res.json();
    if (data.error) {
      showError(data.error);
      btn.disabled = false;
      btn.innerHTML = '📧 Send Verification Code';
    } else {
      // Show OTP step
      showSuccess('✉️ Verification code sent to ' + email);
      document.getElementById('otp-section').classList.add('show');
      document.getElementById('otp-email-hint').textContent = '→ ' + email;
      document.getElementById('r-otp').focus();
      startOtpTimer();
      if (!isResend) {
        btn.innerHTML = '🔄 Resend Code';
        btn.disabled = false;
      } else {
        btn.innerHTML = '📧 Send Verification Code';
        btn.disabled = false;
      }
    }
  } catch(e) {
    showError('Network error. Please try again.');
    btn.disabled = false;
    btn.innerHTML = '📧 Send Verification Code';
  }
}

// ── Submit Register ────────────────────────────────────────────
async function submitRegister() {
  clearAlerts();
  const name  = document.getElementById('r-name').value.trim();
  const email = document.getElementById('r-email').value.trim();
  const phone = document.getElementById('r-phone').value.trim();
  const pass  = document.getElementById('r-pass').value;
  const otp   = document.getElementById('r-otp').value.trim();

  if (!otp || otp.length !== 6) { showError('Please enter the 6-digit verification code.'); return; }

  const fullPhone = phone ? (selectedCountry.code + phone) : '';

  const btn = document.getElementById('btn-register');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Creating Account...';

  try {
    const fd = new FormData();
    fd.append('action',   'register');
    fd.append('name',     name);
    fd.append('email',    email);
    fd.append('phone',    fullPhone);
    fd.append('password', pass);
    fd.append('otp',      otp);
    const res  = await fetch('auth.php', {method:'POST', body:fd});
    const data = await res.json();
    if (data.error) {
      showError(data.error);
      btn.disabled = false;
      btn.innerHTML = '🚀 Create Account';
    } else {
      clearInterval(otpTimer);
      showSuccess('🎉 Account created! Redirecting...');
      setTimeout(() => window.location.href = data.redirect || 'index.php', 900);
    }
  } catch(e) {
    showError('Network error. Please try again.');
    btn.disabled = false;
    btn.innerHTML = '🚀 Create Account';
  }
}

// ── Login ──────────────────────────────────────────────────────
async function submitLogin(e) {
  e.preventDefault(); clearAlerts();
  const email = document.getElementById('l-email').value.trim();
  const pass  = document.getElementById('l-pass').value;
  setLoading('btn-login', true, '⚡ Sign In');
  try {
    const fd = new FormData();
    fd.append('action','login'); fd.append('email',email); fd.append('password',pass);
    const res  = await fetch('auth.php', {method:'POST',body:fd});
    const data = await res.json();
    if (data.error) showError(data.error);
    else { showSuccess('Login successful! Redirecting...'); setTimeout(()=>window.location.href=data.redirect||'index.php',800); }
  } catch(e) { showError('Network error. Please try again.'); }
  finally { setLoading('btn-login',false,'⚡ Sign In'); }
}

// ── Tab switch ─────────────────────────────────────────────────
function switchTab(tab) {
  document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById('panel-'+tab).classList.add('active');
  document.getElementById('tab-'+(tab==='login'?'login':'reg')+'-btn').classList.add('active');
  clearAlerts();
}

// ── Helpers ────────────────────────────────────────────────────
function showError(msg) {
  const el=document.getElementById('js-error');
  el.textContent='⚠️ '+msg; el.style.display='block';
  document.getElementById('js-success').style.display='none';
}
function showSuccess(msg) {
  const el=document.getElementById('js-success');
  el.textContent=msg; el.style.display='block';
  document.getElementById('js-error').style.display='none';
}
function clearAlerts() {
  document.getElementById('js-error').style.display='none';
  document.getElementById('js-success').style.display='none';
}
function setLoading(btnId,loading,label) {
  const btn=document.getElementById(btnId);
  btn.disabled=loading;
  btn.innerHTML=loading?'<span class="spinner"></span> Please wait...':label;
}
function updateStrength(val) {
  const fill=document.getElementById('strength-fill');
  let score=0;
  if(val.length>=6)score++;
  if(val.length>=10)score++;
  if(/[A-Z]/.test(val))score++;
  if(/[0-9]/.test(val))score++;
  if(/[^A-Za-z0-9]/.test(val))score++;
  const colors=['#ff4466','#ff8800','#ffcc00','#00ff88','#00f0ff'];
  const widths=['20%','40%','60%','80%','100%'];
  fill.style.width=widths[score-1]||'0%';
  fill.style.background=colors[score-1]||'transparent';
}

// ── Password show/hide toggle (premium SVG version) ────────────
function togglePassView(inputId, btnId) {
  const inp = document.getElementById(inputId);
  const btn = document.getElementById(btnId);
  const isHidden = inp.type === 'password';
  inp.type = isHidden ? 'text' : 'password';
  btn.classList.toggle('is-visible', isHidden);
  btn.title = isHidden ? 'Hide password' : 'Show password';
  btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
}

// ── Forgot Password Flow ────────────────────────────────────────
let fCountdownTimer = null;
let fSeconds = 600;
let forgotEmail = '';

function showForgotPanel() {
  document.querySelector('.tabs').style.display = 'none';
  document.getElementById('panel-login').style.display = 'none';
  document.getElementById('panel-register').style.display = 'none';
  document.getElementById('forgot-panel').classList.add('show');
  document.getElementById('f-email').value = document.getElementById('l-email').value || '';
  document.getElementById('f-email').focus();
  clearAlerts();
}

function backToLogin() {
  document.querySelector('.tabs').style.display = '';
  document.getElementById('panel-login').style.display = '';
  document.getElementById('forgot-panel').classList.remove('show');
  // Reset steps
  ['fstep-1','fstep-2','fstep-3'].forEach(id => document.getElementById(id).classList.remove('active'));
  document.getElementById('fstep-1').classList.add('active');
  ['fdot-1','fdot-2','fdot-3'].forEach(id => { document.getElementById(id).className = 'step-dot'; });
  document.getElementById('fdot-1').classList.add('active');
  if (fCountdownTimer) { clearInterval(fCountdownTimer); fCountdownTimer = null; }
  clearAlerts();
}

function fGoStep(n) {
  ['fstep-1','fstep-2','fstep-3'].forEach((id,i) => {
    document.getElementById(id).classList.toggle('active', i+1 === n);
  });
  for (let i = 1; i <= 3; i++) {
    const dot = document.getElementById('fdot-'+i);
    dot.className = 'step-dot';
    if (i < n) dot.classList.add('done');
    if (i === n) dot.classList.add('active');
  }
}

function startFCountdown() {
  if (fCountdownTimer) clearInterval(fCountdownTimer);
  fSeconds = 600;
  document.getElementById('f-otp-timer').style.display = 'block';
  fCountdownTimer = setInterval(() => {
    fSeconds--;
    const m = String(Math.floor(fSeconds/60)).padStart(2,'0');
    const s = String(fSeconds%60).padStart(2,'0');
    const cd = document.getElementById('f-countdown');
    cd.textContent = m+':'+s;
    cd.style.color = fSeconds < 60 ? '#ff4466' : '#ffaa00';
    if (fSeconds <= 0) clearInterval(fCountdownTimer);
  }, 1000);
}

async function forgotSendOtp(isResend=false) {
  clearAlerts();
  const email = document.getElementById('f-email').value.trim();
  if (!email) { showError('Please enter your email address.'); return; }
  const btn = document.getElementById('btn-forgot-send');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Sending...';
  try {
    const fd = new FormData();
    fd.append('action','forgot_otp');
    fd.append('email', email);
    const res  = await fetch('auth.php', {method:'POST', body:fd});
    const data = await res.json();
    if (data.error) {
      showError(data.error);
      btn.disabled = false;
      btn.innerHTML = '📧 Send Reset Code';
    } else {
      forgotEmail = email;
      showSuccess('Reset code sent to ' + email);
      document.getElementById('f-email-hint').textContent = '→ ' + email;
      document.getElementById('f-otp').value = '';
      startFCountdown();
      fGoStep(2);
      setTimeout(() => document.getElementById('f-otp').focus(), 200);
    }
  } catch(e) {
    showError('Network error. Please try again.');
    btn.disabled = false;
    btn.innerHTML = '📧 Send Reset Code';
  } finally {
    btn.disabled = false;
    btn.innerHTML = '📧 Send Reset Code';
  }
}

async function forgotVerifyOtp() {
  clearAlerts();
  const otp  = document.getElementById('f-otp').value.trim();
  if (!otp || otp.length < 6) { showError('Enter the 6-digit code from your email.'); return; }
  const btn = document.getElementById('btn-forgot-verify');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Verifying...';
  try {
    const fd = new FormData();
    fd.append('action','forgot_verify');
    fd.append('email', forgotEmail);
    fd.append('otp',   otp);
    const res  = await fetch('auth.php', {method:'POST', body:fd});
    const data = await res.json();
    if (data.error) {
      showError(data.error);
    } else {
      if (fCountdownTimer) clearInterval(fCountdownTimer);
      showSuccess('Code verified! Set your new password below.');
      fGoStep(3);
      setTimeout(() => document.getElementById('f-newpass').focus(), 200);
    }
  } catch(e) {
    showError('Network error. Please try again.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '✓ Verify Code';
  }
}

async function forgotReset() {
  clearAlerts();
  const pass = document.getElementById('f-newpass').value;
  if (!pass || pass.length < 6) { showError('Password must be at least 6 characters.'); return; }
  const btn = document.getElementById('btn-forgot-reset');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Updating...';
  try {
    const fd = new FormData();
    fd.append('action','reset_password');
    fd.append('password', pass);
    const res  = await fetch('auth.php', {method:'POST', body:fd});
    const data = await res.json();
    if (data.error) {
      showError(data.error);
    } else {
      showSuccess('✅ Password updated! Redirecting to login...');
      setTimeout(() => {
        backToLogin();
        document.getElementById('f-newpass').value = '';
      }, 2000);
    }
  } catch(e) {
    showError('Network error. Please try again.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '🔒 Update Password';
  }
}

function updateStrength(val) {
  const fill = document.getElementById('strength-fill-f') || document.getElementById('strength-fill');
  if (!fill) return;
  let score = 0;
  if(val.length>=6)score++;
  if(val.length>=10)score++;
  if(/[A-Z]/.test(val))score++;
  if(/[0-9]/.test(val))score++;
  if(/[^A-Za-z0-9]/.test(val))score++;
  const colors=['#ff4466','#ff8800','#ffcc00','#00ff88','#00f0ff'];
  const widths=['20%','40%','60%','80%','100%'];
  fill.style.width=widths[score-1]||'0%';
  fill.style.background=colors[score-1]||'transparent';
}

// Init country list on load
buildCountryList();

</script>
</body>
</html>
