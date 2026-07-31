<?php
// ══════════════════════════════════════════════════════════════
// db.php — CyberLumina Database Configuration
// ══════════════════════════════════════════════════════════════
// ⚠️  InfinityFree cPanel → MySQL Databases page eka gihilla
//     database + user hadala details meka fill karanna.
// ══════════════════════════════════════════════════════════════

define('DB_HOST', 'sql301.infinityfree.com');    // ✅ ඔයාගෙ MySQL Hostname
define('DB_NAME', 'if0_41824129_cyberlumin');    // ✅ ඔයාගෙ Database Name
define('DB_USER', 'if0_41824129');               // ✅ ඔයාගෙ MySQL Username
define('DB_PASS', '6fsW44SY5t6MX');              // ✅ MySQL Password

// ── Google OAuth 2.0 Credentials ──────────────────────────────
// Google Cloud Console → APIs & Services → Credentials → OAuth 2.0 Client IDs
define('GOOGLE_CLIENT_ID',     '1019067884779-44quicarhj28oca4kvqksgm2eri42j7f.apps.googleusercontent.com'); // ✅
define('GOOGLE_CLIENT_SECRET', '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'); // ✅

// ← ඔයාගේ site URL (trailing slash නෑ)
// Example: 'https://cyberlumin.infinityfreeapp.com'
define('SITE_URL', 'https://cyberlumina.kesug.com'); // ✅ ඔයාගෙ Site URL

// ── Gmail SMTP Credentials ─────────────────────────────────────
// Gmail → Google Account → Security → App Passwords
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);                              // STARTTLS port
define('SMTP_USER', 'cyberlumina2@gmail.com');         // ✅ Gmail address
define('SMTP_PASS', 'miwzeriaqnfsuuun');               // ✅ App Password (no spaces)
define('SMTP_FROM_NAME', 'CyberLumina');

// ── Derived ───────────────────────────────────────────────────
define('GOOGLE_REDIRECT_URI', SITE_URL . '/auth.php?action=google_callback');

// ══════════════════════════════════════════════════════════════
// PDO Connection — singleton pattern
// ══════════════════════════════════════════════════════════════
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Don't expose DB errors to browser
            error_log('DB Connection failed: ' . $e->getMessage());
            die(json_encode(['error' => 'Database connection failed. Please try again later.']));
        }
    }
    return $pdo;
}

// ══════════════════════════════════════════════════════════════
// Auto-create tables if not exists
// ══════════════════════════════════════════════════════════════
function initDB(): void {
    $pdo = getDB();

    // ── Users table ───────────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id            INT AUTO_INCREMENT PRIMARY KEY,
            name          VARCHAR(100)  NOT NULL,
            email         VARCHAR(150)  NOT NULL UNIQUE,
            phone         VARCHAR(30)   DEFAULT NULL,
            password      VARCHAR(255)  DEFAULT NULL,
            google_id     VARCHAR(100)  DEFAULT NULL,
            avatar        VARCHAR(500)  DEFAULT NULL,
            credits       INT           DEFAULT 0,
            created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            last_login    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Add columns for existing installs
    try { $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(30) DEFAULT NULL"); }
    catch (PDOException $e) { /* already exists */ }
    try { $pdo->exec("ALTER TABLE users ADD COLUMN credits INT DEFAULT 0"); }
    catch (PDOException $e) { /* already exists */ }
    try { $pdo->exec("ALTER TABLE users ADD COLUMN profile_photo VARCHAR(500) DEFAULT NULL"); }
    catch (PDOException $e) { /* already exists */ }

    // ── OTP Verifications table ────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS otp_verifications (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            email      VARCHAR(150) NOT NULL,
            otp        VARCHAR(10)  NOT NULL,
            expires_at DATETIME     NOT NULL,
            used       TINYINT(1)   DEFAULT 0,
            created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // ── Activity Log table ─────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activity_log (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            user_id    INT          NOT NULL,
            action     VARCHAR(100) NOT NULL,
            detail     VARCHAR(300) DEFAULT NULL,
            ip         VARCHAR(45)  DEFAULT NULL,
            created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // ── Notifications table ────────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            user_id    INT          NOT NULL,
            type       VARCHAR(30)  DEFAULT 'info',
            title      VARCHAR(200) NOT NULL,
            message    TEXT,
            is_read    TINYINT(1)   DEFAULT 0,
            created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // ── Deleted Accounts blacklist ─────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS deleted_accounts (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            email      VARCHAR(150) DEFAULT NULL,
            phone      VARCHAR(30)  DEFAULT NULL,
            deleted_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // (columns added above in bulk)

    // ── Site Settings table ──────────────────────────────────
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS site_settings (
            `key`   VARCHAR(64) NOT NULL PRIMARY KEY,
            `value` TEXT        DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Insert defaults only if table is empty
    $count = (int)$pdo->query('SELECT COUNT(*) FROM site_settings')->fetchColumn();
    if ($count === 0) {
        $ins = $pdo->prepare("INSERT IGNORE INTO site_settings (`key`,`value`) VALUES (?,?)");
        $ins->execute(['site_name',       'CyberLumina']);
        $ins->execute(['site_name_accent', 'Lumina']);
        $ins->execute(['site_title',       'CyberLumina - Advanced Surveillance & Tracking Platform']);
        $ins->execute(['site_tagline',     'Advanced Surveillance & Tracking Platform']);
        $ins->execute(['meta_description', 'Advanced online webcam capture, location tracking, IP intelligence and gallery access platform.']);
        $ins->execute(['accent_color',     '#00f0ff']);
    }
}

// ── Helper: Get User Credits ─────────────────────────────
function getCredits(int $userId): int {
    try {
        $s = getDB()->prepare('SELECT credits FROM users WHERE id=? LIMIT 1');
        $s->execute([$userId]);
        return (int)($s->fetchColumn() ?? 0);
    } catch (\Throwable $e) { return 0; }
}

// ── Helper: Add Credits ───────────────────────────────────
function addCredits(int $userId, int $amount): void {
    try {
        getDB()->prepare('UPDATE users SET credits = credits + ? WHERE id=?')
               ->execute([$amount, $userId]);
    } catch (\Throwable $e) { error_log('addCredits: ' . $e->getMessage()); }
}

// ── Helper: Deduct Credits (returns false if insufficient) ─
function deductCredits(int $userId, int $amount): bool {
    try {
        $pdo = getDB();
        // Atomic check-and-deduct — only deducts if enough balance
        $affected = $pdo->prepare(
            'UPDATE users SET credits = credits - ? WHERE id=? AND credits >= ?'
        );
        $affected->execute([$amount, $userId, $amount]);
        return $affected->rowCount() > 0;
    } catch (\Throwable $e) {
        error_log('deductCredits: ' . $e->getMessage());
        return false;
    }
}

// ── Helper: Log Activity ──────────────────────────────────
function logActivity(int $userId, string $action, string $detail = '', string $ip = ''): void {
    try {
        getDB()->prepare('INSERT INTO activity_log (user_id, action, detail, ip) VALUES (?,?,?,?)')
               ->execute([$userId, $action, $detail, $ip]);
    } catch (\Throwable $e) { error_log('logActivity: ' . $e->getMessage()); }
}

// ── Helper: Create Notification ────────────────────────────
function createNotification(int $userId, string $type, string $title, string $message = ''): void {
    try {
        getDB()->prepare('INSERT INTO notifications (user_id, type, title, message) VALUES (?,?,?,?)')
               ->execute([$userId, $type, $title, $message]);
    } catch (\Throwable $e) { error_log('createNotification: ' . $e->getMessage()); }
}

// ── Helper: Get Site Setting ─────────────────────────────
function getSiteSetting(string $key, string $default = ''): string {
    try {
        $s = getDB()->prepare("SELECT `value` FROM site_settings WHERE `key`=? LIMIT 1");
        $s->execute([$key]);
        $v = $s->fetchColumn();
        return ($v !== false && $v !== null) ? $v : $default;
    } catch (\Throwable $e) { return $default; }
}

// ── Helper: Set Site Setting ─────────────────────────────
function setSiteSetting(string $key, string $value): void {
    try {
        getDB()->prepare("INSERT INTO site_settings (`key`,`value`) VALUES (?,?)
            ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)")
            ->execute([$key, $value]);
    } catch (\Throwable $e) { error_log('setSiteSetting: ' . $e->getMessage()); }
}

