<?php
/** About Seyal: version, developer, license and bundled components. Open to every signed-in user, clients included. */
declare(strict_types=1);

// The license name is the first line of the LICENSE file, so this page stays correct if the license changes.
$licenseFile = APP_ROOT . '/LICENSE';
$license = is_file($licenseFile) ? trim((string)strtok((string)file_get_contents($licenseFile), "\n")) : '';

$components = [
    ['Bootstrap', '5.3', 'MIT', 'https://getbootstrap.com/'],
    ['Bootstrap Icons', '1.11', 'MIT', 'https://icons.getbootstrap.com/'],
    ['Chart.js', '4.4', 'MIT', 'https://www.chartjs.org/'],
    ['SortableJS', '1.15', 'MIT', 'https://sortablejs.github.io/Sortable/'],
    ['PHPMailer', '6.9', 'LGPL 2.1', 'https://github.com/PHPMailer/PHPMailer'],
    ['Noto Sans', '', 'OFL 1.1', 'https://fonts.google.com/noto'],
];

render('about/index', [
    'pageTitle'  => t('About :app', ['app' => APP_NAME]),
    'license'    => $license,
    'components' => $components,
]);
