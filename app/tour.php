<?php
/**
 * Guided tour: the steps shown by assets/js/tour.js. Each step points at an element marked
 * data-tour="…" in the layout; steps whose element is not on the page (for example manager-only
 * menu items) are skipped automatically, and steps without a target appear in the middle of the screen.
 */
declare(strict_types=1);

/** True when the signed-in person has not seen the tour yet (it then starts by itself). */
function tour_should_autostart(): bool
{
    $u = user();
    return $u && array_key_exists('tour_seen_at', $u) && $u['tour_seen_at'] === null && ($u['role'] ?? '') !== ROLE_CLIENT;
}

function tour_steps(): array
{
    $name = (string)(user()['name'] ?? '');
    $first = $name !== '' ? explode(' ', $name)[0] : '';
    return [
        ['target' => null, 'title' => t('Welcome to Seyal, :name!', ['name' => $first]),
         'body' => t('A quick tour of the main screens. It takes about a minute. You can skip it and watch it again any time from the ? button.')],
        ['target' => 'day', 'title' => t('Start your day'),
         'body' => t('Click Start Day when you begin work. Your start time is recorded (and your location, if your company has switched it on).')],
        ['target' => 'nav-myday', 'title' => t('My Day'),
         'body' => t('Log each piece of work: pick the task, choose how long it took, and say whether you used AI. When you finish, Close Day sends a summary to your manager.')],
        ['target' => 'nav-mytasks', 'title' => t('My Tasks'),
         'body' => t('Everything assigned to you across projects. Add a task, change its status, or filter by project, status, priority and due date.')],
        ['target' => 'nav-projects', 'title' => t('Projects'),
         'body' => t('Each project\'s tasks as a list or a board. Open a task to add subtasks, comments and files.')],
        ['target' => 'nav-team', 'title' => t('Team Day'),
         'body' => t('See who has started, closed or not started their day, with hours and AI time for each person.')],
        ['target' => 'nav-overview', 'title' => t('Project Pulse'),
         'body' => t('The health of every project at a glance: on track, at risk or off track, with forecast dates.')],
        ['target' => 'nav-ratings', 'title' => t('Task ratings'),
         'body' => t('Rate the finished tasks of the people who report to you. Ratings feed their performance score.')],
        ['target' => 'nav-performance', 'title' => t('Performance'),
         'body' => t('Performance scores and appraisals. Your own scores appear here once your manager publishes an appraisal.')],
        ['target' => 'lang', 'title' => t('Your language'),
         'body' => t('Use Seyal in any of 9 languages. Your choice is remembered.')],
        ['target' => 'menu-toggle', 'title' => t('More space'),
         'body' => t('Collapse the side menu to icons. Point at an icon to see its name.')],
        ['target' => 'help', 'title' => t('Help is always here'),
         'body' => t('Open this ? button to watch this tour again or read the help guide.')],
        ['target' => null, 'title' => t('You\'re all set'),
         'body' => t('Start your day and log your first piece of work. Enjoy using Seyal!')],
    ];
}

/** Settings for tour.js: the steps, button labels, whether to start now, and where to report "seen". */
function tour_config(): array
{
    return [
        'steps'     => tour_steps(),
        'autostart' => tour_should_autostart(),
        'doneUrl'   => url('help', ['a' => 'tour_done']),
        'labels'    => [
            'next' => t('Next'), 'back' => t('Back'), 'skip' => t('Skip tour'), 'finish' => t('Finish'),
            'progress' => t('Step :n of :total'), 'close' => t('Close'),
        ],
    ];
}
