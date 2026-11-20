<?php
/** Switch language. Signed-in users have the choice saved to their profile. */
declare(strict_types=1);

$to = (string)input('to', 'en');
if (isset(SUPPORTED_LANGS[$to])) {
    set_lang($to);
    if (user()) {
        update('users', ['lang' => $to], ['id' => uid()]);
    }
}
back(url('dashboard'));
