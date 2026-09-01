<?php
/**
 * General-purpose helper functions used throughout the app.
 */

// ---------------------------------------------------------------------
// Output / formatting helpers
// ---------------------------------------------------------------------
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function money(float $amount, bool $withSign = false): string
{
    $sign = '';
    if ($withSign) {
        $sign = $amount > 0 ? '+' : ($amount < 0 ? '-' : '');
    }
    return $sign . '$' . number_format(abs($amount), 2);
}

function percent(float $value): string
{
    $formatted = rtrim(rtrim(number_format($value, 3), '0'), '.');
    return $formatted . '%';
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    $units = [
        31536000 => 'year', 2592000 => 'month', 86400 => 'day',
        3600 => 'hour', 60 => 'minute',
    ];
    foreach ($units as $seconds => $label) {
        $count = intdiv($diff, $seconds);
        if ($count >= 1) {
            return $count . ' ' . $label . ($count > 1 ? 's' : '') . ' ago';
        }
    }
    return 'just now';
}

function format_date(string $datetime, string $format = 'M j, Y'): string
{
    return date($format, strtotime($datetime));
}

function badge_class(string $status): string
{
    return match ($status) {
        'active', 'approved', 'completed', 'success', 'answered' => 'badge-success',
        'pending', 'open', 'warning' => 'badge-warning',
        'rejected', 'cancelled', 'suspended', 'danger' => 'badge-danger',
        default => 'badge-neutral',
    };
}

// ---------------------------------------------------------------------
// Redirect / flash messaging
// ---------------------------------------------------------------------
function redirect(string $path): never
{
    $url = str_starts_with($path, 'http') ? $path : rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    header('Location: ' . $url);
    exit;
}

function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return rtrim(BASE_URL, '/') . '/assets/' . ltrim($path, '/');
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

// ---------------------------------------------------------------------
// Settings
// ---------------------------------------------------------------------
function get_setting(string $key, $default = null)
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $stmt = db()->query('SELECT setting_key, setting_value FROM settings');
        foreach ($stmt->fetchAll() as $row) {
            $cache[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $cache[$key] ?? $default;
}

function set_setting(string $key, string $value): void
{
    $stmt = db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)
        ON DUPLICATE KEY UPDATE setting_value = :v2');
    $stmt->execute(['k' => $key, 'v' => $value, 'v2' => $value]);
}

// ---------------------------------------------------------------------
// Users
// ---------------------------------------------------------------------
function find_user_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function find_user_by_email(string $email): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function generate_referral_code(string $name): string
{
    $base = strtoupper(preg_replace('/[^A-Za-z]/', '', explode(' ', $name)[0] ?? 'USER'));
    $base = substr($base ?: 'USER', 0, 6);
    do {
        $code = $base . random_int(10, 99);
        $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE referral_code = ?');
        $stmt->execute([$code]);
    } while ($stmt->fetchColumn() > 0);
    return $code;
}

function avatar_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $initials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[count($parts) - 1] ?? '', 0, 1));
    return $initials ?: 'U';
}

// ---------------------------------------------------------------------
// Ledger: the ONE place balances are ever mutated.
// amount is signed: positive credits the user, negative debits them.
// ---------------------------------------------------------------------
function record_transaction(int $userId, string $type, float $amount, ?string $refType = null, ?int $refId = null, ?string $description = null, string $status = 'completed'): float
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = ? FOR UPDATE');
        $stmt->execute([$userId]);
        $current = (float) $stmt->fetchColumn();
        $newBalance = round($current + $amount, 2);

        $update = $pdo->prepare('UPDATE users SET balance = ? WHERE id = ?');
        $update->execute([$newBalance, $userId]);

        // Track aggregate lifetime stats per type.
        $aggColumn = match ($type) {
            'deposit' => 'total_deposited',
            'investment' => 'total_invested',
            'roi_payout', 'maturity_payout', 'referral_bonus' => 'total_earned',
            'withdrawal' => 'total_withdrawn',
            default => null,
        };
        if ($aggColumn) {
            $delta = in_array($type, ['investment', 'withdrawal'], true) ? abs($amount) : abs($amount);
            $pdo->prepare("UPDATE users SET {$aggColumn} = {$aggColumn} + ? WHERE id = ?")
                ->execute([$delta, $userId]);
        }

        $insert = $pdo->prepare('INSERT INTO transactions (user_id, type, amount, balance_after, reference_type, reference_id, description, status)
            VALUES (:user_id, :type, :amount, :balance_after, :reference_type, :reference_id, :description, :status)');
        $insert->execute([
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $newBalance,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'description' => $description,
            'status' => $status,
        ]);

        $pdo->commit();
        return $newBalance;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// ---------------------------------------------------------------------
// Notifications
// ---------------------------------------------------------------------
function notify(int $userId, string $title, string $message, string $type = 'info'): void
{
    $stmt = db()->prepare('INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $title, $message, $type]);
}

function unread_notification_count(int $userId): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

// ---------------------------------------------------------------------
// Email verification (soft-gate — never blocks login, just nudges)
// ---------------------------------------------------------------------
function send_verification_email(int $userId, string $email, string $fullName): string
{
    $token = bin2hex(random_bytes(32));
    $stmt = db()->prepare('INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))');
    $stmt->execute([$userId, $token]);

    $link = url('verify-email.php?token=' . $token);
    send_mail($email, 'Verify your ' . get_setting('site_name', APP_NAME) . ' email address',
        "Hi {$fullName},\n\nPlease confirm your email address by visiting the link below (valid for 24 hours):\n\n{$link}\n\nIf you didn't create this account, you can ignore this email.");

    return $link;
}

// ---------------------------------------------------------------------
// Activity log (admin audit trail)
// ---------------------------------------------------------------------
function log_activity(?int $adminId, string $action, ?string $targetType = null, ?int $targetId = null, ?string $details = null): void
{
    $stmt = db()->prepare('INSERT INTO activity_log (admin_id, action, target_type, target_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$adminId, $action, $targetType, $targetId, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
}

// ---------------------------------------------------------------------
// Validation helpers
// ---------------------------------------------------------------------
function validate_password_strength(string $password): ?string
{
    if (strlen($password) < 8) {
        return 'Password must be at least 8 characters long.';
    }
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return 'Password must include at least one uppercase letter and one number.';
    }
    return null;
}

// ---------------------------------------------------------------------
// CSV export — used by admin & user list pages ("Export CSV" buttons)
// ---------------------------------------------------------------------
function export_csv(string $filename, array $headers, array $rows): never
{
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens accented/currency text cleanly
    // PHP 8.4 deprecates the implicit default for the $escape parameter — pass it
    // explicitly (the historical default) so this keeps working warning-free on 8.4+.
    fputcsv($out, $headers, ',', '"', '\\');
    foreach ($rows as $row) {
        fputcsv($out, $row, ',', '"', '\\');
    }
    fclose($out);
    exit;
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['old'][$key] ?? $default);
}

function set_old(array $data): void
{
    $_SESSION['old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}
