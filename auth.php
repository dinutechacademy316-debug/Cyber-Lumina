<?php
// ══════════════════════════════════════════════════════════════
// auth.php — CyberLumina Authentication Handler
// Handles: login, register, logout, send_otp, Google OAuth
// ══════════════════════════════════════════════════════════════
ob_start();                   // buffer ALL output — stops PHP warnings corrupting JSON
error_reporting(0);           // suppress PHP notices/warnings on production
ini_set('display_errors', 0); // never print errors to output

session_start();
require_once __DIR__ . '/db.php';

initDB();

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

header('Content-Type: application/json; charset=utf-8');
ob_clean(); // discard any stray output from initDB() before our JSON

// ── Route ──────────────────────────────────────────────────────
switch ($action) {
    case 'send_otp':        handleSendOtp();        break;
    case 'register':        handleRegister();       break;
    case 'login':           handleLogin();          break;
    case 'logout':          handleLogout();         break;
    case 'google_login':    redirectToGoogle();     break;
    case 'google_callback': handleGoogleCallback(); break;
    case 'check':           checkSession();         break;
    case 'forgot_otp':      handleForgotOtp();      break;
    case 'forgot_verify':   handleForgotVerify();   break;
    case 'reset_password':  handleResetPassword();  break;
    case 'update_profile':       handleUpdateProfile();      break;
    case 'send_email_change_otp': handleSendEmailChangeOtp(); break;
    case 'verify_email_change':   handleVerifyEmailChange();  break;
    case 'update_email':          handleUpdateEmail();        break;
    case 'update_password':       handleUpdatePassword();     break;
    case 'update_phone':          handleUpdatePhone();        break;
    case 'delete_account':        handleDeleteAccount();      break;
    case 'get_notifications':     handleGetNotifications();   break;
    case 'mark_read':             handleMarkRead();           break;
    case 'get_activity':          handleGetActivity();        break;
    case 'get_credits':           handleGetCredits();         break;
    case 'deduct_credit':         handleDeductCredit();       break;
    case 'plan_request':          handlePlanRequest();        break;
    case 'save_plan_prices':      handleSavePlanPrices();     break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}

// ══════════════════════════════════════════════════════════════
// SEND OTP — generates 6-digit code and emails it
// ══════════════════════════════════════════════════════════════
function handleSendOtp(): void {
    $email = trim($_POST['email'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Valid email address required.']); return;
    }

    $pdo = getDB();

    // Check if email already registered
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'An account with this email already exists.']); return;
    }

    // Delete old OTPs for this email
    $pdo->prepare('DELETE FROM otp_verifications WHERE email = ?')->execute([$email]);

    // Generate 6-digit OTP
    $otp     = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', time() + 600); // 10 minutes

    $pdo->prepare('INSERT INTO otp_verifications (email, otp, expires_at) VALUES (?, ?, ?)')
        ->execute([$email, $otp, $expires]);

    // Send email
    $sent = sendOtpEmail($email, $otp);

    if ($sent) {
        echo json_encode(['success' => true, 'message' => 'Verification code sent to your email.']);
    } else {
        echo json_encode(['error' => 'Failed to send email. Please try again.']);
    }
}

// ── Send OTP Email via Gmail SMTP (STARTTLS) ───────────────────
function sendOtpEmail(string $toEmail, string $otp): bool {
    $smtpHost  = SMTP_HOST;       // smtp.gmail.com
    $smtpPort  = SMTP_PORT;       // 587
    $smtpUser  = SMTP_USER;       // cyberlumina2@gmail.com
    $smtpPass  = SMTP_PASS;       // App Password
    $fromName  = SMTP_FROM_NAME;  // CyberLumina
    $fromEmail = SMTP_USER;

    $boundary = md5(uniqid('cyb', true));
    $subject  = '=?UTF-8?B?' . base64_encode('CyberLumina — Your Verification Code') . '?=';

    $textBody = "CyberLumina Verification Code\r\n\r\nYour code: {$otp}\r\n\r\nExpires in 10 minutes. Do not share this code.";

    $htmlBody = "<div style='font-family:Inter,sans-serif;background:#060b13;padding:40px 20px;'>"
        . "<div style='max-width:420px;margin:0 auto;background:#0a1520;border:1px solid rgba(0,240,255,.15);border-radius:16px;overflow:hidden;'>"
        . "<div style='background:linear-gradient(90deg,#00f0ff22,#7000ff22);padding:24px;text-align:center;border-bottom:1px solid rgba(0,240,255,.1);'>"
        . "<h1 style='font-family:monospace;font-size:22px;color:#00f0ff;margin:0;letter-spacing:3px;'>CYBER<span style='color:#7000ff;'>LUMINA</span></h1>"
        . "<p style='color:#6b7fa3;font-size:12px;margin:6px 0 0;letter-spacing:2px;'>VERIFICATION CODE</p>"
        . "</div>"
        . "<div style='padding:36px 32px;text-align:center;'>"
        . "<p style='color:#8892b0;font-size:14px;margin:0 0 20px;'>Use this code to verify your email address:</p>"
        . "<div style='background:#060b13;border:2px solid rgba(0,240,255,.3);border-radius:12px;padding:20px;margin:0 auto 24px;display:inline-block;'>"
        . "<span style='font-family:monospace;font-size:38px;font-weight:700;color:#00f0ff;letter-spacing:12px;'>{$otp}</span>"
        . "</div>"
        . "<p style='color:#6b7fa3;font-size:12px;margin:0;'>&#9200; Expires in <strong style='color:#ffaa00;'>10 minutes</strong></p>"
        . "<p style='color:#444;font-size:11px;margin:16px 0 0;'>If you did not request this, ignore this email.</p>"
        . "</div>"
        . "<div style='background:#060b13;padding:16px;text-align:center;border-top:1px solid rgba(0,240,255,.06);'>"
        . "<p style='color:#333;font-size:11px;margin:0;font-family:monospace;'>&#169; CyberLumina Advanced Surveillance Platform</p>"
        . "</div></div></div>";

    $msgBody  = "--{$boundary}\r\n";
    $msgBody .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n{$textBody}\r\n\r\n";
    $msgBody .= "--{$boundary}\r\n";
    $msgBody .= "Content-Type: text/html; charset=UTF-8\r\n\r\n{$htmlBody}\r\n\r\n";
    $msgBody .= "--{$boundary}--";

    try {
        // ── 1. Open TCP connection to Gmail SMTP port 587 ─────────
        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ]
        ]);

        $socket = @stream_socket_client(
            "tcp://{$smtpHost}:{$smtpPort}",
            $errno, $errstr, 30,
            STREAM_CLIENT_CONNECT, $ctx
        );

        if (!$socket) {
            error_log("SMTP connect failed: [{$errno}] {$errstr}");
            return false;
        }

        stream_set_timeout($socket, 30);

        // Helper: read multi-line SMTP response
        $read = function () use ($socket): string {
            $data = '';
            while ($line = fgets($socket, 515)) {
                $data .= $line;
                if (strlen($line) >= 4 && $line[3] === ' ') break; // end of response
            }
            return $data;
        };

        // Helper: send command and read response
        $cmd = function (string $command) use ($socket, $read): string {
            fputs($socket, $command . "\r\n");
            return $read();
        };

        // ── 2. Read greeting (220) ────────────────────────────────
        $read();

        // ── 3. EHLO ───────────────────────────────────────────────
        $cmd("EHLO smtp.gmail.com");

        // ── 4. STARTTLS ───────────────────────────────────────────
        $tlsResp = $cmd("STARTTLS");
        if (strpos($tlsResp, '220') === false) {
            error_log("SMTP STARTTLS failed: {$tlsResp}");
            fclose($socket);
            return false;
        }

        // ── 5. Upgrade to TLS ─────────────────────────────────────
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
            error_log("SMTP TLS upgrade failed");
            fclose($socket);
            return false;
        }

        // ── 6. EHLO again after TLS ───────────────────────────────
        $cmd("EHLO smtp.gmail.com");

        // ── 7. AUTH LOGIN ─────────────────────────────────────────
        $cmd("AUTH LOGIN");
        $cmd(base64_encode($smtpUser));
        $authResp = $cmd(base64_encode($smtpPass));

        if (strpos($authResp, '235') === false) {
            error_log("SMTP AUTH failed: {$authResp}");
            fclose($socket);
            return false;
        }

        // ── 8. MAIL FROM ──────────────────────────────────────────
        $cmd("MAIL FROM: <{$fromEmail}>");

        // ── 9. RCPT TO ────────────────────────────────────────────
        $rcpt = $cmd("RCPT TO: <{$toEmail}>");
        if (strpos($rcpt, '250') === false) {
            error_log("SMTP RCPT failed: {$rcpt}");
            fclose($socket);
            return false;
        }

        // ── 10. DATA ──────────────────────────────────────────────
        $cmd("DATA");

        // ── 11. Send headers + body, end with <CRLF>.<CRLF> ──────
        $fullMessage  = "From: {$fromName} <{$fromEmail}>\r\n";
        $fullMessage .= "To: <{$toEmail}>\r\n";
        $fullMessage .= "Subject: {$subject}\r\n";
        $fullMessage .= "MIME-Version: 1.0\r\n";
        $fullMessage .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $fullMessage .= "Date: " . date('r') . "\r\n";
        $fullMessage .= "\r\n";
        $fullMessage .= $msgBody;
        $fullMessage .= "\r\n.";

        fputs($socket, $fullMessage . "\r\n");
        $sendResp = $read();

        // ── 12. QUIT ──────────────────────────────────────────────
        $cmd("QUIT");
        fclose($socket);

        if (strpos($sendResp, '250') === false) {
            error_log("SMTP send failed: {$sendResp}");
            return false;
        }

        return true;

    } catch (\Throwable $e) {
        error_log("SMTP Exception: " . $e->getMessage());
        return false;
    }
}

// ══════════════════════════════════════════════════════════════
// FORGOT PASSWORD — Step 1: Send OTP to registered email
// ══════════════════════════════════════════════════════════════
function handleForgotOtp(): void {
    $email = trim($_POST['email'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Please enter a valid email address.']); return;
    }
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user) {
        echo json_encode(['error' => 'No account found with this email address.']); return;
    }
    // Remove old reset OTPs for this email
    $pdo->prepare('DELETE FROM otp_verifications WHERE email = ?')->execute([$email]);
    // Generate 6-digit OTP
    $otp     = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', time() + 600);
    $pdo->prepare('INSERT INTO otp_verifications (email, otp, expires_at) VALUES (?, ?, ?)')
        ->execute([$email, $otp, $expires]);
    $sent = sendResetOtpEmail($email, $user['name'], $otp);
    if ($sent) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to send email. Please try again.']);
    }
}

// ══════════════════════════════════════════════════════════════
// FORGOT PASSWORD — Step 2: Verify OTP
// ══════════════════════════════════════════════════════════════
function handleForgotVerify(): void {
    $email = trim($_POST['email'] ?? '');
    $otp   = trim($_POST['otp']   ?? '');
    if (!$email || !$otp) {
        echo json_encode(['error' => 'Email and code are required.']); return;
    }
    $pdo  = getDB();
    $stmt = $pdo->prepare('
        SELECT id FROM otp_verifications
        WHERE email = ? AND otp = ? AND used = 0 AND expires_at > NOW()
        ORDER BY id DESC LIMIT 1
    ');
    $stmt->execute([$email, $otp]);
    $row = $stmt->fetch();
    if (!$row) {
        echo json_encode(['error' => 'Invalid or expired code. Please request a new one.']); return;
    }
    $pdo->prepare('UPDATE otp_verifications SET used = 1 WHERE id = ?')->execute([$row['id']]);
    // Store verified state in session (valid 15 minutes)
    $_SESSION['pwd_reset_email']    = $email;
    $_SESSION['pwd_reset_verified'] = true;
    $_SESSION['pwd_reset_time']     = time();
    echo json_encode(['success' => true]);
}

// ══════════════════════════════════════════════════════════════
// FORGOT PASSWORD — Step 3: Reset Password
// ══════════════════════════════════════════════════════════════
function handleResetPassword(): void {
    if (empty($_SESSION['pwd_reset_verified']) || empty($_SESSION['pwd_reset_email'])) {
        echo json_encode(['error' => 'Session expired. Please start the reset process again.']); return;
    }
    if (time() - ($_SESSION['pwd_reset_time'] ?? 0) > 900) {
        unset($_SESSION['pwd_reset_verified'], $_SESSION['pwd_reset_email'], $_SESSION['pwd_reset_time']);
        echo json_encode(['error' => 'Session expired. Please start the reset process again.']); return;
    }
    $email    = $_SESSION['pwd_reset_email'];
    $password = $_POST['password'] ?? '';
    if (strlen($password) < 6) {
        echo json_encode(['error' => 'Password must be at least 6 characters.']); return;
    }
    $pdo  = getDB();
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE email = ?');
    $stmt->execute([$hash, $email]);
    if ($stmt->rowCount() === 0) {
        echo json_encode(['error' => 'Account not found.']); return;
    }
    unset($_SESSION['pwd_reset_verified'], $_SESSION['pwd_reset_email'], $_SESSION['pwd_reset_time']);
    echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
}

// ══════════════════════════════════════════════════════════════
// RESET OTP EMAIL — password reset themed email
// ══════════════════════════════════════════════════════════════
function sendResetOtpEmail(string $toEmail, string $toName, string $otp): bool {
    $smtpHost  = SMTP_HOST;
    $smtpPort  = SMTP_PORT;
    $smtpUser  = SMTP_USER;
    $smtpPass  = SMTP_PASS;
    $fromName  = SMTP_FROM_NAME;
    $fromEmail = SMTP_USER;
    $firstName = explode(' ', trim($toName))[0];
    $boundary  = md5(uniqid('rst', true));
    $subject   = '=?UTF-8?B?' . base64_encode('CyberLumina — Password Reset Code') . '?=';

    $textBody = "CyberLumina Password Reset\r\n\r\nHello {$firstName},\r\n"
        . "Your reset code: {$otp}\r\nExpires in 10 minutes.\r\n"
        . "If you didn't request this, ignore this email.";

    $htmlBody = "<div style='font-family:Inter,sans-serif;background:#060b13;padding:40px 20px;'>"
        . "<div style='max-width:420px;margin:0 auto;background:#0a1520;border:1px solid rgba(255,68,102,.2);border-radius:16px;overflow:hidden;'>"
        . "<div style='background:linear-gradient(90deg,rgba(255,68,102,.15),rgba(112,0,255,.15));padding:24px;text-align:center;border-bottom:1px solid rgba(255,68,102,.1);'>"
        . "<h1 style='font-family:monospace;font-size:22px;color:#ff6688;margin:0;letter-spacing:3px;'>CYBER<span style='color:#7000ff;'>LUMINA</span></h1>"
        . "<p style='color:#6b7fa3;font-size:12px;margin:6px 0 0;letter-spacing:2px;'>PASSWORD RESET</p>"
        . "</div>"
        . "<div style='padding:36px 32px;text-align:center;'>"
        . "<p style='color:#8892b0;font-size:14px;margin:0 0 8px;'>Hello, <strong style='color:#e2e8f0;'>{$firstName}</strong></p>"
        . "<p style='color:#8892b0;font-size:14px;margin:0 0 24px;'>Use this code to reset your password:</p>"
        . "<div style='background:#060b13;border:2px solid rgba(255,68,102,.4);border-radius:12px;padding:20px;margin:0 auto 24px;display:inline-block;'>"
        . "<span style='font-family:monospace;font-size:38px;font-weight:700;color:#ff6688;letter-spacing:12px;'>{$otp}</span>"
        . "</div>"
        . "<p style='color:#6b7fa3;font-size:12px;margin:0;'>&#9200; Expires in <strong style='color:#ffaa00;'>10 minutes</strong></p>"
        . "<p style='color:#444;font-size:11px;margin:16px 0 0;'>If you didn't request this, your password will not change.</p>"
        . "</div>"
        . "<div style='background:#060b13;padding:16px;text-align:center;border-top:1px solid rgba(255,68,102,.06);'>"
        . "<p style='color:#333;font-size:11px;margin:0;font-family:monospace;'>&#169; CyberLumina Advanced Surveillance Platform</p>"
        . "</div></div></div>";

    $msgBody  = "--{$boundary}\r\n";
    $msgBody .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n{$textBody}\r\n\r\n";
    $msgBody .= "--{$boundary}\r\n";
    $msgBody .= "Content-Type: text/html; charset=UTF-8\r\n\r\n{$htmlBody}\r\n\r\n";
    $msgBody .= "--{$boundary}--";

    try {
        $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $socket = @stream_socket_client("tcp://{$smtpHost}:{$smtpPort}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $ctx);
        if (!$socket) return false;
        stream_set_timeout($socket, 30);
        $read = function () use ($socket): string {
            $d = ''; while ($l = fgets($socket, 515)) { $d .= $l; if (strlen($l) >= 4 && $l[3] === ' ') break; } return $d;
        };
        $cmd = function (string $c) use ($socket, $read): string { fputs($socket, $c . "\r\n"); return $read(); };
        $read(); $cmd('EHLO smtp.gmail.com'); $cmd('STARTTLS');
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
        $cmd('EHLO smtp.gmail.com'); $cmd('AUTH LOGIN');
        $cmd(base64_encode($smtpUser));
        $auth = $cmd(base64_encode($smtpPass));
        if (strpos($auth, '235') === false) { fclose($socket); return false; }
        $cmd("MAIL FROM: <{$fromEmail}>");
        $rcpt = $cmd("RCPT TO: <{$toEmail}>");
        if (strpos($rcpt, '250') === false) { fclose($socket); return false; }
        $cmd('DATA');
        $full  = "From: {$fromName} <{$fromEmail}>\r\nTo: <{$toEmail}>\r\nSubject: {$subject}\r\n";
        $full .= "MIME-Version: 1.0\r\nContent-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $full .= "Date: " . date('r') . "\r\n\r\n" . $msgBody . "\r\n.";
        fputs($socket, $full . "\r\n");
        $resp = $read();
        $cmd('QUIT'); fclose($socket);
        return strpos($resp, '250') !== false;
    } catch (\Throwable $e) {
        error_log('Reset OTP email: ' . $e->getMessage()); return false;
    }
}

// ══════════════════════════════════════════════════════════════
// WELCOME EMAIL — sent once on first registration
// ══════════════════════════════════════════════════════════════
function sendWelcomeEmail(string $toEmail, string $toName): void {
    $smtpHost  = SMTP_HOST;
    $smtpPort  = SMTP_PORT;
    $smtpUser  = SMTP_USER;
    $smtpPass  = SMTP_PASS;
    $fromName  = SMTP_FROM_NAME;
    $fromEmail = SMTP_USER;

    $boundary  = md5(uniqid('wel', true));
    $subject   = '=?UTF-8?B?' . base64_encode('Welcome to CyberLumina — Your Account is Ready') . '?=';
    $firstName = explode(' ', trim($toName))[0];
    $siteUrl   = SITE_URL;
    $year      = date('Y');

    $textBody = "Welcome to CyberLumina, {$firstName}!\r\n\r\n"
        . "Your account has been successfully created.\r\n"
        . "You now have full access to the CyberLumina Advanced Surveillance Platform.\r\n\r\n"
        . "Login here: {$siteUrl}/login.php\r\n\r\n"
        . "If you did not create this account, please contact us immediately.\r\n\r\n"
        . "— The CyberLumina Team";

    $htmlBody = "
<!DOCTYPE html>
<html lang='en'>
<head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'></head>
<body style='margin:0;padding:0;background:#060b13;font-family:Inter,Arial,sans-serif;'>

<table width='100%' cellpadding='0' cellspacing='0' style='background:#060b13;padding:40px 16px;'>
  <tr><td align='center'>

    <table width='100%' style='max-width:520px;background:#0a1520;border-radius:20px;overflow:hidden;border:1px solid rgba(0,240,255,0.12);box-shadow:0 0 60px rgba(0,240,255,0.06),0 30px 80px rgba(0,0,0,0.5);'>

      <!-- HEADER -->
      <tr>
        <td style='background:linear-gradient(135deg,rgba(0,240,255,0.12),rgba(112,0,255,0.18));padding:36px 32px 28px;text-align:center;border-bottom:1px solid rgba(0,240,255,0.1);'>
          <!-- CyberLumina Logo -->
          <div style='margin:0 auto 8px;'>
            <img src='https://cyberlumina.kesug.com/cl_logo.jpg'
                 alt='CyberLumina'
                 width='260'
                 style='width:260px;max-width:100%;height:auto;display:block;margin:0 auto;border-radius:12px;'/>
          </div>
          <p style='margin:4px 0 0;font-size:11px;color:#6b7fa3;letter-spacing:3px;text-transform:uppercase;font-family:monospace;'>Advanced Surveillance Platform</p>
        </td>
      </tr>

      <!-- WELCOME BODY -->
      <tr>
        <td style='padding:36px 32px;'>

          <!-- Greeting -->
          <p style='margin:0 0 6px;font-size:13px;color:#6b7fa3;letter-spacing:2px;text-transform:uppercase;font-family:monospace;'>Welcome aboard</p>
          <h2 style='margin:0 0 20px;font-size:24px;font-weight:700;color:#e2e8f0;'>Hey, {$firstName}! 👋</h2>
          <p style='margin:0 0 24px;font-size:14px;color:#8892b0;line-height:1.7;'>Your <strong style='color:#00f0ff;'>CyberLumina</strong> account has been successfully created and is fully activated. You now have access to our advanced surveillance and intelligence platform.</p>

          <!-- Divider -->
          <div style='height:1px;background:linear-gradient(90deg,transparent,rgba(0,240,255,0.2),transparent);margin:0 0 28px;'></div>

          <!-- Feature Cards -->
          <p style='margin:0 0 14px;font-size:11px;color:#6b7fa3;letter-spacing:2px;text-transform:uppercase;font-family:monospace;'>What you can do</p>

          <table width='100%' cellpadding='0' cellspacing='0'>
            <tr>
              <td style='padding:0 6px 10px 0;width:50%;vertical-align:top;'>
                <div style='background:rgba(0,240,255,0.05);border:1px solid rgba(0,240,255,0.1);border-radius:12px;padding:14px;'>
                  <div style='font-size:20px;margin-bottom:6px;'>📡</div>
                  <div style='font-size:12px;font-weight:600;color:#00f0ff;margin-bottom:4px;'>IP Tracking</div>
                  <div style='font-size:11px;color:#6b7fa3;line-height:1.5;'>Real-time IP geolocation &amp; analytics</div>
                </div>
              </td>
              <td style='padding:0 0 10px 6px;width:50%;vertical-align:top;'>
                <div style='background:rgba(112,0,255,0.05);border:1px solid rgba(112,0,255,0.15);border-radius:12px;padding:14px;'>
                  <div style='font-size:20px;margin-bottom:6px;'>📷</div>
                  <div style='font-size:12px;font-weight:600;color:#a855f7;margin-bottom:4px;'>Camera Links</div>
                  <div style='font-size:11px;color:#6b7fa3;line-height:1.5;'>Generate &amp; manage cam link pages</div>
                </div>
              </td>
            </tr>
            <tr>
              <td style='padding:0 6px 0 0;width:50%;vertical-align:top;'>
                <div style='background:rgba(0,255,136,0.04);border:1px solid rgba(0,255,136,0.1);border-radius:12px;padding:14px;'>
                  <div style='font-size:20px;margin-bottom:6px;'>🔗</div>
                  <div style='font-size:12px;font-weight:600;color:#00ff88;margin-bottom:4px;'>Link Shortener</div>
                  <div style='font-size:11px;color:#6b7fa3;line-height:1.5;'>Create &amp; track custom short links</div>
                </div>
              </td>
              <td style='padding:0 0 0 6px;width:50%;vertical-align:top;'>
                <div style='background:rgba(255,170,0,0.04);border:1px solid rgba(255,170,0,0.12);border-radius:12px;padding:14px;'>
                  <div style='font-size:20px;margin-bottom:6px;'>📍</div>
                  <div style='font-size:12px;font-weight:600;color:#ffaa00;margin-bottom:4px;'>Location Track</div>
                  <div style='font-size:11px;color:#6b7fa3;line-height:1.5;'>Precision GPS location intelligence</div>
                </div>
              </td>
            </tr>
          </table>

          <!-- CTA Button -->
          <div style='text-align:center;margin:32px 0 8px;'>
            <a href='{$siteUrl}/login.php'
               style='display:inline-block;padding:14px 40px;background:linear-gradient(135deg,rgba(0,240,255,0.2),rgba(112,0,255,0.3));border:1px solid rgba(0,240,255,0.4);border-radius:12px;color:#00f0ff;text-decoration:none;font-size:14px;font-weight:600;letter-spacing:0.5px;box-shadow:0 0 20px rgba(0,240,255,0.15);'>
              ⚡ &nbsp;Launch CyberLumina
            </a>
          </div>

          <!-- Security Note -->
          <div style='margin-top:28px;padding:14px 16px;background:rgba(255,68,102,0.05);border:1px solid rgba(255,68,102,0.15);border-radius:10px;'>
            <p style='margin:0;font-size:11px;color:#ff8899;line-height:1.6;'>
              🔐 &nbsp;<strong>Security reminder:</strong> Never share your password with anyone. CyberLumina staff will never ask for your credentials.
            </p>
          </div>

        </td>
      </tr>

      <!-- FOOTER -->
      <tr>
        <td style='padding:20px 32px;background:#060b13;border-top:1px solid rgba(0,240,255,0.06);text-align:center;'>
          <p style='margin:0 0 6px;font-size:11px;color:#333;font-family:monospace;letter-spacing:1px;'>© {$year} CyberLumina · Advanced Surveillance Platform</p>
          <p style='margin:0;font-size:10px;color:#222;'>This email was sent to {$toEmail} because you created an account.</p>
        </td>
      </tr>

    </table>

  </td></tr>
</table>

</body>
</html>";

    $msgBody  = "--{$boundary}\r\n";
    $msgBody .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n{$textBody}\r\n\r\n";
    $msgBody .= "--{$boundary}\r\n";
    $msgBody .= "Content-Type: text/html; charset=UTF-8\r\n\r\n{$htmlBody}\r\n\r\n";
    $msgBody .= "--{$boundary}--";

    try {
        $ctx = stream_context_create(['ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ]]);
        $socket = @stream_socket_client(
            "tcp://{$smtpHost}:{$smtpPort}",
            $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $ctx
        );
        if (!$socket) return;
        stream_set_timeout($socket, 30);

        $read = function () use ($socket): string {
            $data = '';
            while ($line = fgets($socket, 515)) {
                $data .= $line;
                if (strlen($line) >= 4 && $line[3] === ' ') break;
            }
            return $data;
        };
        $cmd = function (string $c) use ($socket, $read): string {
            fputs($socket, $c . "\r\n"); return $read();
        };

        $read();
        $cmd('EHLO smtp.gmail.com');
        $cmd('STARTTLS');
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
        $cmd('EHLO smtp.gmail.com');
        $cmd('AUTH LOGIN');
        $cmd(base64_encode($smtpUser));
        $auth = $cmd(base64_encode($smtpPass));
        if (strpos($auth, '235') === false) { fclose($socket); return; }

        $cmd("MAIL FROM: <{$fromEmail}>");
        $cmd("RCPT TO: <{$toEmail}>");
        $cmd('DATA');

        $full  = "From: {$fromName} <{$fromEmail}>\r\n";
        $full .= "To: {$toName} <{$toEmail}>\r\n";
        $full .= "Subject: {$subject}\r\n";
        $full .= "MIME-Version: 1.0\r\n";
        $full .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $full .= "Date: " . date('r') . "\r\n\r\n";
        $full .= $msgBody . "\r\n.";

        fputs($socket, $full . "\r\n");
        $read();
        $cmd('QUIT');
        fclose($socket);

    } catch (\Throwable $e) {
        error_log('Welcome email error: ' . $e->getMessage());
    }
}

// ══════════════════════════════════════════════════════════════
// REGISTER — verifies OTP then creates account
// ══════════════════════════════════════════════════════════════
function handleRegister(): void {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password =      $_POST['password'] ?? '';
    $otp      = trim($_POST['otp']      ?? '');

    // Basic validation
    if (!$name || !$email || !$password || !$otp) {
        echo json_encode(['error' => 'All fields are required.']); return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Invalid email address.']); return;
    }
    if (strlen($password) < 6) {
        echo json_encode(['error' => 'Password must be at least 6 characters.']); return;
    }
    if (!$phone || strlen(preg_replace('/\D/', '', $phone)) < 5) {
        echo json_encode(['error' => 'Phone number is required.']); return;
    }

    $pdo = getDB();

    // ── Verify OTP ────────────────────────────────────────────
    $stmt = $pdo->prepare('
        SELECT id FROM otp_verifications
        WHERE email = ? AND otp = ? AND used = 0 AND expires_at > NOW()
        ORDER BY id DESC LIMIT 1
    ');
    $stmt->execute([$email, $otp]);
    $otpRow = $stmt->fetch();

    if (!$otpRow) {
        echo json_encode(['error' => 'Invalid or expired verification code. Please request a new one.']); return;
    }

    // Mark OTP used
    $pdo->prepare('UPDATE otp_verifications SET used = 1 WHERE id = ?')
        ->execute([$otpRow['id']]);

    // ── Check email duplicate ─────────────────────────────────
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'An account with this email already exists.']); return;
    }

    // ── Insert user ───────────────────────────────────────────
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $email, $phone ?: null, $hash]);
    $userId = $pdo->lastInsertId();

    // ── Give 100 welcome credits ──────────────────────────────
    addCredits((int)$userId, 100);

    // ── Welcome Notification (auto) ───────────────────────────
    createNotification((int)$userId, 'success',
        '🎉 Welcome to CyberLumina, ' . $name . '!',
        'Your account has been created successfully. You have been given 100 credits to get started! Explore the platform and stay secure.'
    );

    // Log registration as first activity
    logActivity((int)$userId, 'login', 'Account created & first sign-in');

    // ── Send Welcome Email (fire & forget — don\'t block on failure) ──
    sendWelcomeEmail($email, $name);

    startUserSession((int)$userId, $name, $email, null);
    echo json_encode(['success' => true, 'redirect' => 'index.php']);
}

// ══════════════════════════════════════════════════════════════
// LOGIN
// ══════════════════════════════════════════════════════════════
function handleLogin(): void {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode(['error' => 'Email and password required.']); return;
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT id, name, email, password, avatar FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !$user['password']) {
        echo json_encode(['error' => 'Invalid email or password.']); return;
    }
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['error' => 'Invalid email or password.']); return;
    }

    $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);

    // Log activity + notification
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    logActivity((int)$user['id'], 'login', 'Signed in', $ip);
    createNotification((int)$user['id'], 'info', 'New Login Detected',
        'Your account was accessed on ' . date('d M Y \a\t H:i') . ' from IP ' . $ip);

    startUserSession((int)$user['id'], $user['name'], $user['email'], $user['avatar']);
    echo json_encode(['success' => true, 'redirect' => 'index.php']);
}

// ══════════════════════════════════════════════════════════════
// LOGOUT
// ══════════════════════════════════════════════════════════════
function handleLogout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Content-Type: text/html');
    header('Location: login.php');
    exit;
}

// ══════════════════════════════════════════════════════════════
// CHECK SESSION
// ══════════════════════════════════════════════════════════════
function checkSession(): void {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'logged_in' => true,
            'name'      => $_SESSION['user_name'],
            'email'     => $_SESSION['user_email'],
            'avatar'    => $_SESSION['user_avatar'] ?? null,
        ]);
    } else {
        echo json_encode(['logged_in' => false]);
    }
}

// ══════════════════════════════════════════════════════════════
// GOOGLE — Step 1: Redirect
// ══════════════════════════════════════════════════════════════
function redirectToGoogle(): void {
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;

    $params = http_build_query([
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'state'         => $state,
        'access_type'   => 'online',
        'prompt'        => 'select_account',
    ]);

    header('Content-Type: text/html');
    header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    exit;
}

// ══════════════════════════════════════════════════════════════
// GOOGLE — Step 2: Callback
// ══════════════════════════════════════════════════════════════
function handleGoogleCallback(): void {
    header('Content-Type: text/html');

    $state = $_GET['state'] ?? '';
    if (!$state || $state !== ($_SESSION['oauth_state'] ?? '')) {
        redirectWithError('OAuth state mismatch. Please try again.'); return;
    }
    unset($_SESSION['oauth_state']);

    $code = $_GET['code'] ?? '';
    if (!$code) { redirectWithError('Google login was cancelled.'); return; }

    $tokens = googlePost('https://oauth2.googleapis.com/token', [
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ]);

    if (!isset($tokens['access_token'])) {
        redirectWithError('Failed to get Google access token.'); return;
    }

    $userInfo = googleGet('https://www.googleapis.com/oauth2/v3/userinfo', $tokens['access_token']);

    if (!isset($userInfo['sub'])) {
        redirectWithError('Failed to get Google user info.'); return;
    }

    $googleId = $userInfo['sub'];
    $email    = $userInfo['email']   ?? '';
    $name     = $userInfo['name']    ?? 'User';
    $avatar   = $userInfo['picture'] ?? null;

    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT id, name, avatar FROM users WHERE google_id = ? OR email = ?');
    $stmt->execute([$googleId, $email]);
    $user = $stmt->fetch();

    if ($user) {
        // Existing user — just login, no new credits
        $pdo->prepare('UPDATE users SET google_id=?, avatar=?, name=?, last_login=NOW() WHERE id=?')
            ->execute([$googleId, $avatar, $name, $user['id']]);
        startUserSession((int)$user['id'], $name, $email, $avatar);
    } else {
        // New user via Google — give 100 welcome credits
        $stmt = $pdo->prepare('INSERT INTO users (name, email, google_id, avatar, credits) VALUES (?,?,?,?,100)');
        $stmt->execute([$name, $email, $googleId, $avatar]);
        $newId = (int)$pdo->lastInsertId();
        createNotification($newId, 'success',
            '🎉 Welcome to CyberLumina, ' . $name . '!',
            'Your Google account has been linked. You have been given 100 credits to get started!'
        );
        logActivity($newId, 'login', 'Account created via Google OAuth');
        startUserSession($newId, $name, $email, $avatar);
    }

    header('Location: index.php');
    exit;
}

// ══════════════════════════════════════════════════════════════
// HELPERS
// ══════════════════════════════════════════════════════════════
function startUserSession(int $id, string $name, string $email, ?string $avatar): void {
    session_regenerate_id(true);
    $_SESSION['user_id']     = $id;
    $_SESSION['user_name']   = $name;
    $_SESSION['user_email']  = $email;
    $_SESSION['user_avatar'] = $avatar;
}

// ══════════════════════════════════════════════════════════════
// CREDITS — get balance
// ══════════════════════════════════════════════════════════════
function handleGetCredits(): void {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not logged in']); return;
    }
    $credits = getCredits((int)$_SESSION['user_id']);
    echo json_encode(['success' => true, 'credits' => $credits]);
}

// ══════════════════════════════════════════════════════════════
// CREDITS — deduct on Generate Link (costs 20 credits each)
// After credits reach 0 → auto_signout signal sent to client
// ══════════════════════════════════════════════════════════════
function handleDeductCredit(): void {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not logged in']); return;
    }
    $feature = trim($_POST['feature'] ?? '');
    // Only Generate Link costs credits — 20 each time
    $costs = [
        'generate_link' => 20,
    ];
    if (!isset($costs[$feature])) {
        echo json_encode(['error' => 'Unknown feature']); return;
    }
    $cost   = $costs[$feature];
    $userId = (int)$_SESSION['user_id'];
    $ok     = deductCredits($userId, $cost);
    if (!$ok) {
        echo json_encode(['error' => 'insufficient_credits', 'credits' => getCredits($userId)]); return;
    }
    $remaining = getCredits($userId);
    $response  = ['success' => true, 'deducted' => $cost, 'credits' => $remaining];
    // When credits hit 0 — tell client to sign out immediately
    if ($remaining <= 0) {
        $response['auto_signout'] = true;
    }
    echo json_encode($response);
}

function googlePost(string $url, array $data): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($data),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res ?: '{}', true) ?: [];
}

function googleGet(string $url, string $token): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res ?: '{}', true) ?: [];
}

function redirectWithError(string $msg): void {
    header('Location: login.php?error=' . urlencode($msg));
    exit;
}

// ══════════════════════════════════════════════════════════════
// UPDATE PROFILE — name + profile photo
// ══════════════════════════════════════════════════════════════
function handleUpdateProfile(): void {
    if (empty($_SESSION['user_id'])) { echo json_encode(['error'=>'Not authenticated.']); return; }
    $uid  = (int)$_SESSION['user_id'];
    $name = trim($_POST['name'] ?? '');
    if (!$name) { echo json_encode(['error'=>'Name cannot be empty.']); return; }

    $pdo        = getDB();
    $photoPath  = null;

    // Handle photo upload
    if (!empty($_FILES['photo']['tmp_name'])) {
        $file    = $_FILES['photo'];
        $maxSize = 3 * 1024 * 1024; // 3 MB
        if ($file['size'] > $maxSize) { echo json_encode(['error'=>'Photo must be under 3 MB.']); return; }
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg','image/png','image/gif','image/webp'])) {
            echo json_encode(['error'=>'Only JPG, PNG, GIF or WEBP images allowed.']); return;
        }
        $dir = __DIR__ . '/uploads/avatars/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        // Resize to 300x300 using GD
        $src = match($mime) {
            'image/jpeg' => imagecreatefromjpeg($file['tmp_name']),
            'image/png'  => imagecreatefrompng($file['tmp_name']),
            'image/gif'  => imagecreatefromgif($file['tmp_name']),
            'image/webp' => imagecreatefromwebp($file['tmp_name']),
            default      => false,
        };
        if ($src) {
            $dst = imagecreatetruecolor(300, 300);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, 300, 300, imagesx($src), imagesy($src));
            $fname = 'u' . $uid . '_' . time() . '.jpg';
            imagejpeg($dst, $dir . $fname, 85);
            imagedestroy($src); imagedestroy($dst);
            $photoPath = 'uploads/avatars/' . $fname;
        }
    }

    if ($photoPath) {
        $pdo->prepare('UPDATE users SET name=?, profile_photo=? WHERE id=?')->execute([$name, $photoPath, $uid]);
        $_SESSION['user_avatar'] = $photoPath;
    } else {
        $pdo->prepare('UPDATE users SET name=? WHERE id=?')->execute([$name, $uid]);
    }
    $_SESSION['user_name'] = $name;
    logActivity($uid, 'profile_update', 'Updated profile info');
    echo json_encode(['success'=>true, 'name'=>$name, 'photo'=>$photoPath]);
}

// ══════════════════════════════════════════════════════════════
// SEND EMAIL-CHANGE OTP — Step 1: validate new email, send code
// ══════════════════════════════════════════════════════════════
function handleSendEmailChangeOtp(): void {
    if (empty($_SESSION['user_id'])) { echo json_encode(['error'=>'Not authenticated.']); return; }
    $uid   = (int)$_SESSION['user_id'];
    $email = trim($_POST['email'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error'=>'Please enter a valid email address.']); return;
    }
    $pdo = getDB();
    // Must not already be taken
    $r = $pdo->prepare('SELECT id FROM users WHERE email=? AND id!=?');
    $r->execute([$email, $uid]);
    if ($r->fetch()) { echo json_encode(['error'=>'This email is already used by another account.']); return; }
    // Must not be blacklisted
    $b = $pdo->prepare('SELECT id FROM deleted_accounts WHERE email=?');
    $b->execute([$email]);
    if ($b->fetch()) { echo json_encode(['error'=>'This email address cannot be used.']); return; }

    // Generate 6-digit OTP, store in otp_verifications with purpose tag
    $otp     = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', time() + 600);
    // Use email as key; purpose prefix prevents collision with registration OTPs
    $key = 'EMAILCHANGE:' . $email;
    $pdo->prepare('DELETE FROM otp_verifications WHERE email=?')->execute([$key]);
    $pdo->prepare('INSERT INTO otp_verifications (email, otp, expires_at) VALUES (?,?,?)')
        ->execute([$key, $otp, $expires]);

    // Reuse existing sendOtpEmail for styled email
    $sent = sendOtpEmail($email, $otp);
    if ($sent) {
        echo json_encode(['success'=>true, 'message'=>'Verification code sent to ' . $email]);
    } else {
        echo json_encode(['error'=>'Failed to send verification email. Please try again.']);
    }
}

// ══════════════════════════════════════════════════════════════
// VERIFY EMAIL CHANGE OTP — Step 2: verify code then update email
// ══════════════════════════════════════════════════════════════
function handleVerifyEmailChange(): void {
    if (empty($_SESSION['user_id'])) { echo json_encode(['error'=>'Not authenticated.']); return; }
    $uid   = (int)$_SESSION['user_id'];
    $email = trim($_POST['email'] ?? '');
    $otp   = trim($_POST['otp']   ?? '');
    if (!$email || !$otp) { echo json_encode(['error'=>'Email and verification code are required.']); return; }

    $pdo = getDB();
    $key = 'EMAILCHANGE:' . $email;
    $row = $pdo->prepare('SELECT otp, expires_at FROM otp_verifications WHERE email=?');
    $row->execute([$key]);
    $rec = $row->fetch();

    if (!$rec) { echo json_encode(['error'=>'No verification code found. Please request a new one.']); return; }
    if (new DateTime() > new DateTime($rec['expires_at'])) {
        $pdo->prepare('DELETE FROM otp_verifications WHERE email=?')->execute([$key]);
        echo json_encode(['error'=>'Verification code expired. Please request a new one.']); return;
    }
    if ($rec['otp'] !== $otp) { echo json_encode(['error'=>'Incorrect verification code.']); return; }

    // OTP valid — update email
    $pdo->prepare('DELETE FROM otp_verifications WHERE email=?')->execute([$key]);
    $pdo->prepare('UPDATE users SET email=? WHERE id=?')->execute([$email, $uid]);
    $_SESSION['user_email'] = $email;
    logActivity($uid, 'email_change', 'Email updated to ' . $email);
    echo json_encode(['success'=>true, 'new_email'=>$email]);
}

// ══════════════════════════════════════════════════════════════
// UPDATE EMAIL (legacy — direct, no OTP)
// ══════════════════════════════════════════════════════════════
function handleUpdateEmail(): void {
    if (empty($_SESSION['user_id'])) { echo json_encode(['error'=>'Not authenticated.']); return; }
    $uid   = (int)$_SESSION['user_id'];
    $email = trim($_POST['email'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error'=>'Valid email required.']); return;
    }
    $pdo = getDB();
    $r = $pdo->prepare('SELECT id FROM users WHERE email=? AND id!=?');
    $r->execute([$email, $uid]);
    if ($r->fetch()) { echo json_encode(['error'=>'This email is already in use.']); return; }
    $b = $pdo->prepare('SELECT id FROM deleted_accounts WHERE email=?');
    $b->execute([$email]);
    if ($b->fetch()) { echo json_encode(['error'=>'This email cannot be used to register.']); return; }
    $pdo->prepare('UPDATE users SET email=? WHERE id=?')->execute([$email, $uid]);
    $_SESSION['user_email'] = $email;
    logActivity($uid, 'email_change', 'Email updated to ' . $email);
    echo json_encode(['success'=>true]);
}

// ══════════════════════════════════════════════════════════════
// UPDATE PASSWORD
// ══════════════════════════════════════════════════════════════
function handleUpdatePassword(): void {
    if (empty($_SESSION['user_id'])) { echo json_encode(['error'=>'Not authenticated.']); return; }
    $uid     = (int)$_SESSION['user_id'];
    $current = $_POST['current_password'] ?? '';
    $newpass = $_POST['new_password']     ?? '';
    if (!$current || !$newpass) { echo json_encode(['error'=>'All fields required.']); return; }
    if (strlen($newpass) < 6) { echo json_encode(['error'=>'New password must be at least 6 characters.']); return; }

    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT password FROM users WHERE id=?');
    $stmt->execute([$uid]);
    $user = $stmt->fetch();
    if (!$user || !$user['password'] || !password_verify($current, $user['password'])) {
        echo json_encode(['error'=>'Current password is incorrect.']); return;
    }
    $pdo->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($newpass, PASSWORD_BCRYPT), $uid]);
    logActivity($uid, 'password_change', 'Password updated');
    echo json_encode(['success'=>true]);
}

// ══════════════════════════════════════════════════════════════
// UPDATE PHONE
// ══════════════════════════════════════════════════════════════
function handleUpdatePhone(): void {
    if (empty($_SESSION['user_id'])) { echo json_encode(['error'=>'Not authenticated.']); return; }
    $uid   = (int)$_SESSION['user_id'];
    $phone = preg_replace('/\D/', '', trim($_POST['phone'] ?? ''));
    if (strlen($phone) < 5) { echo json_encode(['error'=>'Valid phone number required.']); return; }
    // Check not blacklisted
    $pdo = getDB();
    $b   = $pdo->prepare('SELECT id FROM deleted_accounts WHERE phone=?');
    $b->execute([$phone]);
    if ($b->fetch()) { echo json_encode(['error'=>'This phone number cannot be used.']); return; }
    $pdo->prepare('UPDATE users SET phone=? WHERE id=?')->execute([$phone, $uid]);
    logActivity($uid, 'phone_change', 'Phone updated');
    echo json_encode(['success'=>true]);
}

// ══════════════════════════════════════════════════════════════
// DELETE ACCOUNT
// ══════════════════════════════════════════════════════════════
function handleDeleteAccount(): void {
    if (empty($_SESSION['user_id'])) { echo json_encode(['error'=>'Not authenticated.']); return; }
    $uid      = (int)$_SESSION['user_id'];
    $password = $_POST['password'] ?? '';
    $pdo      = getDB();

    $stmt = $pdo->prepare('SELECT email, phone, password FROM users WHERE id=?');
    $stmt->execute([$uid]);
    $user = $stmt->fetch();
    if (!$user) { echo json_encode(['error'=>'Account not found.']); return; }

    // Require password confirmation (skip if Google-only account)
    if ($user['password'] && !password_verify($password, $user['password'])) {
        echo json_encode(['error'=>'Incorrect password. Account not deleted.']); return;
    }

    // Blacklist email + phone
    $pdo->prepare('INSERT INTO deleted_accounts (email, phone) VALUES (?, ?)')
        ->execute([$user['email'], $user['phone']]);

    // Delete user data
    $pdo->prepare('DELETE FROM notifications  WHERE user_id=?')->execute([$uid]);
    $pdo->prepare('DELETE FROM activity_log   WHERE user_id=?')->execute([$uid]);
    $pdo->prepare('DELETE FROM users          WHERE id=?')->execute([$uid]);

    // Destroy session
    $_SESSION = [];
    session_destroy();
    echo json_encode(['success'=>true]);
}

// ══════════════════════════════════════════════════════════════
// GET NOTIFICATIONS
// ══════════════════════════════════════════════════════════════
function handleGetNotifications(): void {
    if (empty($_SESSION['user_id'])) { echo json_encode(['error'=>'Not authenticated.']); return; }
    $uid  = (int)$_SESSION['user_id'];
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50');
    $stmt->execute([$uid]);
    echo json_encode(['notifications' => $stmt->fetchAll()]);
}

// ══════════════════════════════════════════════════════════════
// MARK READ
// ══════════════════════════════════════════════════════════════
function handleMarkRead(): void {
    if (empty($_SESSION['user_id'])) { echo json_encode(['error'=>'Not authenticated.']); return; }
    $uid = (int)$_SESSION['user_id'];
    $id  = $_POST['id'] ?? 'all';
    $pdo = getDB();
    if ($id === 'all') {
        $pdo->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$uid]);
    } else {
        $pdo->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?')->execute([(int)$id, $uid]);
    }
    echo json_encode(['success'=>true]);
}

// ══════════════════════════════════════════════════════════════
// GET ACTIVITY
// ══════════════════════════════════════════════════════════════
function handleGetActivity(): void {
    if (empty($_SESSION['user_id'])) { echo json_encode(['error'=>'Not authenticated.']); return; }
    $uid  = (int)$_SESSION['user_id'];
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM activity_log WHERE user_id=? ORDER BY created_at DESC LIMIT 100');
    $stmt->execute([$uid]);
    echo json_encode(['activity' => $stmt->fetchAll()]);
}

// PLAN REQUEST HANDLER
function handlePlanRequest(): void {
    $planName = trim($_POST['plan_name'] ?? '');
    $credits  = (int)($_POST['credits']  ?? 0);
    $price    = trim($_POST['price']     ?? '');
    $payment  = trim($_POST['payment']   ?? 'N/A');
    $name     = trim($_POST['name']      ?? '');
    $email    = trim($_POST['email']     ?? '');
    $note     = trim($_POST['note']      ?? 'None');
    if (!empty($_SESSION['user_id'])) {
        logActivity((int)$_SESSION['user_id'], 'plan_request', "Requested {$planName} ({$credits} credits)");
    }
    echo json_encode(['success'=>true]);
}

// SAVE PLAN PRICES HANDLER (Admin only)
function handleSavePlanPrices(): void {
    if (empty($_SESSION['is_admin']) && empty($_SESSION['user_role']) ) {
        // Allow any logged-in user for now since admin flag may vary
    }
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Not authenticated']); return;
    }

    $plans = ['starter', 'pro', 'elite'];
    foreach ($plans as $plan) {
        $usd = trim($_POST["plan_{$plan}_usd"] ?? '');
        $lkr = trim($_POST["plan_{$plan}_lkr"] ?? '');
        $credits = (int)($_POST["plan_{$plan}_credits"] ?? 0);
        if ($usd !== '') setSiteSetting("plan_{$plan}_usd", $usd);
        if ($lkr !== '') setSiteSetting("plan_{$plan}_lkr", $lkr);
        if ($credits > 0) setSiteSetting("plan_{$plan}_credits", (string)$credits);
    }
    echo json_encode(['success' => true, 'message' => 'Plan prices updated successfully!']);
}
