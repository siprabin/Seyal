<?php
/** Login, logout, forgot password and reset password. */
declare(strict_types=1);

switch ($page) {
    case 'login':
        if (user()) {
            redirect(url('dashboard'));
        }
        $error = null;
        if (is_post()) {
            $email = (string)input('email', '');
            $error = attempt_login($email, (string)($_POST['password'] ?? ''));
            if ($error === null) {
                $to = $_SESSION['after_login'] ?? '';
                unset($_SESSION['after_login']);
                // Only follow local paths.
                redirect(($to && str_starts_with($to, base_path() . '/') && !str_contains($to, '://')) ? $to : url('dashboard'));
            }
            remember_old();
        }
        render('auth/login', ['pageTitle' => t('Log in'), 'error' => $error], 'auth_layout');
        break;

    case 'logout':
        if (is_post() && user()) {
            audit('user', uid(), user()['name'], 'logout');
            logout();
        }
        redirect(url('login'));

    case 'forgot':
        $sent = false;
        if (is_post()) {
            $email = mb_strtolower((string)input('email', ''));
            $u = row("SELECT u.* FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = ? AND u.is_active = 1", [$email]);
            // Simple throttle: one request per account every 2 minutes.
            $recent = $u ? val('SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND created_at > ?', [$u['id'], date('Y-m-d H:i:s', time() - 120)]) : 0;
            if ($u && !$recent && mail_configured()) {
                $token = bin2hex(random_bytes(32));
                insert('password_resets', [
                    'user_id'    => $u['id'],
                    'token_hash' => hash('sha256', $token),
                    'expires_at' => date('Y-m-d H:i:s', time() + 3600),
                    'created_at' => now(),
                ]);
                $link = base_url() . '/index.php?' . http_build_query(['p' => 'reset', 'token' => $token]);
                $prevLang = current_lang();
                set_lang($u['lang'] ?: 'en');
                $html = '<p>' . e(t('Hello :name,', ['name' => $u['name']])) . '</p>'
                    . '<p>' . e(t('We received a request to reset your password. Click the button below to choose a new one. The link works for 1 hour.')) . '</p>'
                    . '<p><a href="' . e($link) . '" style="display:inline-block;background:#1f3a5f;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none">' . e(t('Reset password')) . '</a></p>'
                    . '<p style="color:#888;font-size:13px">' . e(t('If you did not ask for this, you can ignore this email.')) . '</p>';
                $err = send_mail($u['email'], t('Reset your password'), $html);
                set_lang($prevLang);
                if ($err) {
                    error_log('Password reset mail failed for user ' . $u['id'] . ': ' . $err);
                }
            }
            $sent = true;
        }
        render('auth/forgot', ['pageTitle' => t('Forgot password'), 'sent' => $sent], 'auth_layout');
        break;

    case 'reset':
        $token = (string)input('token', '');
        $reset = $token !== '' ? row(
            'SELECT pr.*, u.name, u.email FROM password_resets pr JOIN users u ON u.id = pr.user_id
             WHERE pr.token_hash = ? AND pr.used_at IS NULL AND pr.expires_at > ? AND u.is_active = 1',
            [hash('sha256', $token), now()]
        ) : null;
        $error = null;
        if ($reset && is_post()) {
            $pw = (string)($_POST['password'] ?? '');
            $error = validate_password($pw, (string)($_POST['password_confirm'] ?? ''));
            if (!$error) {
                update('users', ['password_hash' => password_hash($pw, PASSWORD_DEFAULT), 'failed_logins' => 0, 'locked_until' => null, 'updated_at' => now()], ['id' => $reset['user_id']]);
                q('UPDATE password_resets SET used_at = ? WHERE user_id = ? AND used_at IS NULL', [now(), $reset['user_id']]);
                audit_raw((int)$reset['user_id'], $reset['name'], 'user', (int)$reset['user_id'], $reset['name'], 'password_reset', null, null, null, 'Via emailed link');
                flash('success', t('Your password has been changed. Please log in.'));
                redirect(url('login'));
            }
        }
        render('auth/reset', ['pageTitle' => t('Reset password'), 'reset' => $reset, 'token' => $token, 'error' => $error], 'auth_layout');
        break;
}
