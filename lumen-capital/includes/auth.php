<?php
/**
 * Authentication + authorization helpers.
 */

const MAX_LOGIN_ATTEMPTS = 5;
const LOCKOUT_MINUTES = 15;

function current_user(): ?array
{
    static $user = null;
    static $loaded = false;
    if ($loaded) {
        return $user;
    }
    $loaded = true;
    if (!empty($_SESSION['user_id'])) {
        $user = find_user_by_id((int) $_SESSION['user_id']);
        if (!$user || $user['status'] !== 'active') {
            logout_user();
            $user = null;
        }
    }
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();
    return $user !== null && $user['role'] === 'admin';
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];

    $stmt = db()->prepare('UPDATE users SET last_login_at = NOW(), last_login_ip = ?, failed_attempts = 0, locked_until = NULL WHERE id = ?');
    $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? null, $user['id']]);
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * Attempt to authenticate a user by email/password with basic
 * brute-force lockout protection.
 *
 * @return array{success:bool, message?:string, user?:array}
 */
function attempt_login(string $email, string $password): array
{
    $user = find_user_by_email($email);

    if (!$user) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $minutesLeft = (int) ceil((strtotime($user['locked_until']) - time()) / 60);
        return ['success' => false, 'message' => "Too many failed attempts. Try again in {$minutesLeft} minute(s)."];
    }

    if ($user['status'] !== 'active') {
        return ['success' => false, 'message' => 'This account has been suspended. Contact support.'];
    }

    if (!password_verify($password, $user['password_hash'])) {
        $attempts = $user['failed_attempts'] + 1;
        $lockedUntil = null;
        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            $lockedUntil = date('Y-m-d H:i:s', strtotime('+' . LOCKOUT_MINUTES . ' minutes'));
        }
        $stmt = db()->prepare('UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?');
        $stmt->execute([$attempts, $lockedUntil, $user['id']]);

        if ($lockedUntil) {
            return ['success' => false, 'message' => 'Too many failed attempts. Your account is locked for ' . LOCKOUT_MINUTES . ' minutes.'];
        }
        $remaining = MAX_LOGIN_ATTEMPTS - $attempts;
        return ['success' => false, 'message' => "Invalid email or password. {$remaining} attempt(s) remaining before lockout."];
    }

    return ['success' => true, 'user' => $user];
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('warning', 'Please sign in to continue.');
        redirect('login.php');
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        flash('danger', 'You do not have permission to access that page.');
        redirect('user/dashboard.php');
    }
}

function require_guest(): void
{
    if (is_logged_in()) {
        redirect(is_admin() ? 'admin/index.php' : 'user/dashboard.php');
    }
}
