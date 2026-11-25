<?php
/** Help guide (views/help/…) and the guided tour's "seen" marker. Staff only; client logins use the portal. */
declare(strict_types=1);

switch ($action) {
    case 'tour_done':
        // Called by tour.js when the tour is finished or skipped, so it does not start by itself again.
        if (!is_post()) {
            abort(405);
        }
        if (array_key_exists('tour_seen_at', user()) && user()['tour_seen_at'] === null) {
            update('users', ['tour_seen_at' => now()], ['id' => uid()]);
        }
        json_out(['ok' => true]);

    default:
        // The guide is written per language in views/help/<code>.php; English is used when there is no translation yet.
        $lang = current_lang();
        $guide = is_file(APP_ROOT . "/views/help/$lang.php") ? $lang : 'en';
        render('help/index', [
            'pageTitle' => t('Help guide'),
            'guide'     => $guide,
            'translated' => $guide === $lang,
        ]);
}
