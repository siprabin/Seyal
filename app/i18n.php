<?php
/**
 * Translations. English text is the key; lang/<code>.php maps English -> that language.
 * Missing strings fall back to English, so nothing ever shows blank.
 * The order here is the order shown in the language menu.
 */
declare(strict_types=1);

const SUPPORTED_LANGS = [
    'en' => 'English',
    'hi' => 'हिन्दी',
    'ta' => 'தமிழ்',
    'kn' => 'ಕನ್ನಡ',
    'te' => 'తెలుగు',
    'ml' => 'മലയാളം',
    'ar' => 'العربية',
    'de' => 'Deutsch',
    'es' => 'Español',
];

/** Languages written right-to-left: the whole layout mirrors for these. */
const RTL_LANGS = ['ar'];

/** English names, shown next to the native name so everyone can find their language. */
const LANG_ENGLISH_NAMES = ['en' => 'English', 'hi' => 'Hindi', 'ta' => 'Tamil', 'kn' => 'Kannada', 'te' => 'Telugu', 'ml' => 'Malayalam', 'ar' => 'Arabic', 'de' => 'German', 'es' => 'Spanish'];

function is_rtl(?string $lang = null): bool
{
    return in_array($lang ?? current_lang(), RTL_LANGS, true);
}

/** dir attribute value for <html>. */
function text_dir(?string $lang = null): string
{
    return is_rtl($lang) ? 'rtl' : 'ltr';
}

/** Language menu (dropdown) used in the top bar and on the login page. */
function lang_menu(string $btnClass = 'btn-outline-secondary'): string
{
    $cur = current_lang();
    $h = '<div class="dropdown lang-menu">'
        . '<button class="btn btn-sm ' . $btnClass . ' dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="' . e(t('Language')) . '">'
        . '<i class="bi bi-translate"></i> <span class="d-none d-sm-inline">' . e(SUPPORTED_LANGS[$cur]) . '</span></button>'
        . '<ul class="dropdown-menu dropdown-menu-end">';
    foreach (SUPPORTED_LANGS as $code => $native) {
        $h .= '<li><a class="dropdown-item' . ($code === $cur ? ' active' : '') . '" lang="' . $code . '" href="' . e(url('lang', ['to' => $code])) . '">'
            . e($native) . (!in_array($code, ['en', 'de', 'es'], true) ? ' <span class="small opacity-75">· ' . e(LANG_ENGLISH_NAMES[$code]) . '</span>' : '') . '</a></li>';
    }
    return $h . '</ul></div>';
}

function current_lang(): string
{
    $lang = $_SESSION['lang'] ?? setting('default_lang', 'en');
    return isset(SUPPORTED_LANGS[$lang]) ? $lang : 'en';
}

function set_lang(string $lang): void
{
    if (isset(SUPPORTED_LANGS[$lang])) {
        $_SESSION['lang'] = $lang;
    }
}

function lang_table(): array
{
    static $tables = [];
    $lang = current_lang();
    if (!isset($tables[$lang])) {
        $file = APP_ROOT . "/lang/$lang.php";
        $tables[$lang] = ($lang !== 'en' && is_file($file)) ? require $file : [];
    }
    return $tables[$lang];
}

/** Translate and replace :placeholders. */
function t(string $text, array $replace = []): string
{
    $table = lang_table();
    $out = $table[$text] ?? $text;
    foreach ($replace as $k => $v) {
        $out = str_replace(':' . $k, (string)$v, $out);
    }
    return $out;
}
