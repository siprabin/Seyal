<?php
/** Email via PHPMailer (bundled in lib/, no Composer needed). */
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once APP_ROOT . '/lib/PHPMailer/Exception.php';
require_once APP_ROOT . '/lib/PHPMailer/PHPMailer.php';
require_once APP_ROOT . '/lib/PHPMailer/SMTP.php';

function mail_configured(): bool
{
    return (bool)setting('smtp_host') && (bool)setting('mail_from');
}

/**
 * Send an HTML email. Returns null on success or an error message.
 * $to may be a single address or a list.
 */
function send_mail($to, string $subject, string $html, ?string $text = null): ?string
{
    if (!mail_configured()) {
        return t('Email is not set up yet. Ask the administrator to fill in Settings → Email.');
    }
    $m = new PHPMailer(true);
    try {
        $m->isSMTP();
        $m->Host       = setting('smtp_host');
        $m->Port       = (int)setting('smtp_port', 587);
        $enc           = setting('smtp_encryption', 'tls');
        $m->SMTPSecure = $enc === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : ($enc === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : '');
        $m->SMTPAutoTLS = $enc !== 'none';
        if (setting('smtp_user')) {
            $m->SMTPAuth = true;
            $m->Username = setting('smtp_user');
            $m->Password = decrypt_secret(setting('smtp_pass'));
        }
        $m->CharSet = 'UTF-8';
        $m->Timeout = 20;
        $m->setFrom(setting('mail_from'), setting('mail_from_name', setting('company_name', 'Project Management')));
        foreach ((array)$to as $addr) {
            $m->addAddress($addr);
        }
        $m->isHTML(true);
        $m->Subject = $subject;
        $m->Body    = mail_wrap($subject, $html);
        $m->AltBody = $text ?? trim(html_entity_decode(strip_tags(str_replace(['<br>', '</p>'], "\n", $html))));
        $m->send();
        return null;
    } catch (Throwable $e) {
        error_log('Mail error: ' . $m->ErrorInfo . ' / ' . $e->getMessage());
        return t('Email could not be sent: :err', ['err' => $m->ErrorInfo ?: $e->getMessage()]);
    }
}

function mail_wrap(string $title, string $body): string
{
    $company = e(setting('company_name', 'Project Management'));
    $dir = text_dir();
    return '<!doctype html><html lang="' . current_lang() . '" dir="' . $dir . '"><body dir="' . $dir . '" style="margin:0;background:#f4f5f7;font-family:Segoe UI,Arial,sans-serif;color:#222;text-align:' . ($dir === 'rtl' ? 'right' : 'left') . '">'
        . '<div style="max-width:600px;margin:24px auto;background:#fff;border-radius:8px;overflow:hidden;border:1px solid #e3e5e8">'
        . '<div style="background:#1f3a5f;color:#fff;padding:14px 20px;font-weight:600">' . $company . '</div>'
        . '<div style="padding:20px;line-height:1.55">' . $body . '</div>'
        . '<div style="padding:12px 20px;color:#888;font-size:12px;border-top:1px solid #eee">' . e(t('This is an automatic message.')) . '</div>'
        . '</div></body></html>';
}
