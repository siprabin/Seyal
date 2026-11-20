<?php
/** The signed-in user's own profile: phone, language, password. */
declare(strict_types=1);

$me = user();

if (is_post()) {
    if ($action === 'password') {
        $current = (string)($_POST['current_password'] ?? '');
        $pw = (string)($_POST['password'] ?? '');
        $hash = (string)val('SELECT password_hash FROM users WHERE id = ?', [uid()]);
        if (!password_verify($current, $hash)) {
            flash('danger', t('Your current password is not correct.'));
        } elseif ($err = validate_password($pw, (string)($_POST['password_confirm'] ?? ''))) {
            flash('danger', $err);
        } else {
            update('users', ['password_hash' => password_hash($pw, PASSWORD_DEFAULT), 'updated_at' => now()], ['id' => uid()]);
            audit('user', uid(), $me['name'], 'password_reset', 'Changed by the user');
            session_regenerate_id(true);
            flash('success', t('Your password has been changed.'));
        }
        redirect(url('profile'));
    }
    $lang = isset(SUPPORTED_LANGS[input('lang')]) ? input('lang') : 'en';
    $phone = mb_substr((string)input('phone', ''), 0, 40) ?: null;
    audit_diff('user', uid(), $me['name'], ['phone' => $me['phone'], 'lang' => $me['lang']], ['phone' => $phone, 'lang' => $lang]);
    update('users', ['phone' => $phone, 'lang' => $lang, 'updated_at' => now()], ['id' => uid()]);
    set_lang($lang);
    flash('success', t('Profile saved.'));
    redirect(url('profile'));
}

$manager = $me['reports_to'] ? row('SELECT id, name, designation FROM users WHERE id = ?', [$me['reports_to']]) : null;
render('profile/index', ['pageTitle' => t('My Profile'), 'me' => $me, 'manager' => $manager]);
