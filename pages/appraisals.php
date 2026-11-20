<?php
/**
 * Appraisals: Draft → Finalised (metrics frozen) → Published (the employee can see it and acknowledge).
 */
declare(strict_types=1);

function find_appraisal(int $id): array
{
    $a = row('SELECT a.*, u.name AS user_name, u.email AS user_email, u.lang AS user_lang, u.designation, r.name AS reviewer_name,
                     f.name AS finalised_by_name, pb.name AS published_by_name
              FROM appraisals a JOIN users u ON u.id = a.user_id LEFT JOIN users r ON r.id = a.reviewer_id
              LEFT JOIN users f ON f.id = a.finalised_by LEFT JOIN users pb ON pb.id = a.published_by WHERE a.id = ?', [$id]);
    if (!$a) {
        abort(404);
    }
    if (!can_view_appraisal($a)) {
        abort(403);
    }
    return $a;
}

function appraisal_metrics(array $a): array
{
    if ($a['status'] !== 'draft' && $a['metrics_json']) {
        return json_decode($a['metrics_json'], true) ?: [];
    }
    return person_metrics((int)$a['user_id'], $a['period_from'], $a['period_to']);
}

switch ($action) {
    case 'index':
        $scope = performance_scope_ids();
        $status = (string)input('status', '');
        if ($scope) {
            $where = ['a.user_id IN (' . in_list($scope) . ')'];
            $params = $scope;
            if (isset(appraisal_statuses()[$status])) {
                $where[] = 'a.status = ?';
                $params[] = $status;
            }
            $list = rows('SELECT a.*, u.name AS user_name, r.name AS reviewer_name FROM appraisals a JOIN users u ON u.id = a.user_id
                          LEFT JOIN users r ON r.id = a.reviewer_id WHERE ' . implode(' AND ', $where) . '
                          ORDER BY FIELD(a.status, \'finalised\', \'draft\', \'published\'), a.period_to DESC, u.name', $params);
        } else {
            $list = [];
        }
        $mine = rows("SELECT a.*, r.name AS reviewer_name FROM appraisals a LEFT JOIN users r ON r.id = a.reviewer_id
                      WHERE a.user_id = ? AND a.status = 'published' ORDER BY a.period_to DESC", [uid()]);
        render('appraisals/index', ['pageTitle' => t('Appraisals'), 'list' => $list, 'mine' => $mine, 'status' => $status, 'hasTeam' => (bool)$scope]);
        break;

    case 'create':
        $scope = performance_scope_ids();
        if (!$scope) {
            abort(403);
        }
        if (is_post()) {
            $userId = (int)input('user_id');
            $from = input_date('period_from');
            $to = input_date('period_to');
            $title = mb_substr(trim((string)input('title', '')), 0, 160);
            if (!in_array($userId, $scope, true) || !$from || !$to || $from > $to || $title === '') {
                flash('danger', t('Please choose a person, a valid period and a title.'));
                remember_old();
                redirect(url('appraisals', ['a' => 'create']));
            }
            $overlap = val("SELECT COUNT(*) FROM appraisals WHERE user_id = ? AND period_from <= ? AND period_to >= ?", [$userId, $to, $from]);
            $id = insert('appraisals', ['user_id' => $userId, 'reviewer_id' => uid(), 'title' => $title, 'period_from' => $from, 'period_to' => $to, 'status' => 'draft', 'created_at' => now()]);
            audit('appraisal', $id, user_name($userId) . ' — ' . $title, 'create');
            if ($overlap) {
                flash('warning', t('Note: this period overlaps another appraisal for the same person.'));
            }
            redirect(url('appraisals', ['a' => 'edit', 'id' => $id]));
        }
        $people = rows('SELECT id, name FROM users WHERE id IN (' . in_list($scope) . ') AND is_active = 1 ORDER BY name', $scope);
        // Default period: the last full quarter.
        $q = (int)ceil((int)date('n') / 3) - 1;
        $year = (int)date('Y');
        if ($q === 0) {
            $q = 4;
            $year--;
        }
        $from = sprintf('%04d-%02d-01', $year, ($q - 1) * 3 + 1);
        $to = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $q * 3)));
        render('appraisals/create', ['pageTitle' => t('New appraisal'), 'people' => $people, 'userId' => input_int('user'), 'from' => $from, 'to' => $to, 'defaultTitle' => "Q$q $year"]);
        break;

    case 'edit':
        $a = find_appraisal((int)input('id'));
        if (!can_edit_appraisal($a)) {
            redirect(url('appraisals', ['a' => 'view', 'id' => $a['id']]));
        }
        if (is_post()) {
            $rating = input_int('overall_rating');
            $d = [
                'title' => mb_substr(trim((string)input('title', $a['title'])), 0, 160) ?: $a['title'],
                'overall_rating' => $rating && $rating >= 1 && $rating <= 5 ? $rating : null,
                'strengths' => trim((string)input('strengths', '')) ?: null,
                'improvements' => trim((string)input('improvements', '')) ?: null,
                'goals' => trim((string)input('goals', '')) ?: null,
                'manager_comments' => trim((string)input('manager_comments', '')) ?: null,
                'updated_at' => now(),
            ];
            update('appraisals', $d, ['id' => $a['id']]);
            if (input('finalise')) {
                if (!$d['overall_rating']) {
                    flash('danger', t('Give an overall rating before finalising.'));
                    redirect(url('appraisals', ['a' => 'edit', 'id' => $a['id']]));
                }
                $m = person_metrics((int)$a['user_id'], $a['period_from'], $a['period_to']);
                update('appraisals', [
                    'status' => 'finalised', 'metrics_json' => json_encode($m), 'score' => $m['score'],
                    'finalised_at' => now(), 'finalised_by' => uid(),
                ], ['id' => $a['id']]);
                audit('appraisal', (int)$a['id'], $a['user_name'] . ' — ' . $a['title'], 'finalise');
                flash('success', t('Appraisal finalised. The figures are now frozen. It can be published to :name.', ['name' => $a['user_name']]));
                redirect(url('appraisals', ['a' => 'view', 'id' => $a['id']]));
            }
            flash('success', t('Draft saved.'));
            redirect(url('appraisals', ['a' => 'edit', 'id' => $a['id']]));
        }
        render('appraisals/edit', ['pageTitle' => $a['title'], 'a' => $a, 'm' => appraisal_metrics($a)]);
        break;

    case 'view':
    case 'print':
        $a = find_appraisal((int)input('id'));
        $vars = ['pageTitle' => $a['user_name'] . ' — ' . $a['title'], 'a' => $a, 'm' => appraisal_metrics($a),
                 'reviews' => rows('SELECT week_start, rating FROM weekly_reviews WHERE user_id = ? AND week_start BETWEEN ? AND ? ORDER BY week_start', [$a['user_id'], week_start($a['period_from']), $a['period_to']])];
        if ($action === 'print') {
            render('appraisals/print', $vars, false);
            exit;
        }
        render('appraisals/view', $vars);
        break;

    case 'publish':
        if (!is_post()) {
            abort(405);
        }
        $a = find_appraisal((int)input('id'));
        if (!can('appraisals.publish') || $a['status'] !== 'finalised' || (int)$a['user_id'] === uid()) {
            abort(403);
        }
        update('appraisals', ['status' => 'published', 'published_at' => now(), 'published_by' => uid(), 'updated_at' => now()], ['id' => $a['id']]);
        audit('appraisal', (int)$a['id'], $a['user_name'] . ' — ' . $a['title'], 'publish');
        $link = base_url() . '/index.php?' . http_build_query(['p' => 'appraisals', 'a' => 'view', 'id' => $a['id']]);
        $err = mail_configured() ? with_lang($a['user_lang'], fn() => send_mail(
            $a['user_email'],
            t('Your appraisal is ready: :title', ['title' => $a['title']]),
            '<p>' . e(t('Hello :name,', ['name' => $a['user_name']])) . '</p><p>' . e(t('Your appraisal ":title" has been published. Please read it and acknowledge it in the app.', ['title' => $a['title']])) . '</p>'
            . '<p><a href="' . e($link) . '" style="background:#1f3a5f;color:#fff;padding:8px 14px;border-radius:6px;text-decoration:none">' . e(t('Open my appraisal')) . '</a></p>'
        )) : null;
        if (mail_configured()) {
            log_notification('appraisal', 'email', (int)$a['user_id'], today(), (int)$a['id'], $a['user_email'], $err);
        }
        flash('success', t('Published. :name can now see this appraisal.', ['name' => $a['user_name']]));
        if ($err) {
            flash('warning', $err);
        }
        redirect(url('appraisals', ['a' => 'view', 'id' => $a['id']]));

    case 'reopen':
        if (!is_post()) {
            abort(405);
        }
        $a = find_appraisal((int)input('id'));
        if (!can('appraisals.publish') || !in_array($a['status'], ['finalised', 'published'], true)) {
            abort(403);
        }
        $reason = trim((string)input('reason', ''));
        if ($reason === '') {
            flash('danger', t('Please give a reason for reopening.'));
            redirect(url('appraisals', ['a' => 'view', 'id' => $a['id']]));
        }
        update('appraisals', ['status' => 'draft', 'published_at' => null, 'published_by' => null, 'acknowledged_at' => null, 'updated_at' => now()], ['id' => $a['id']]);
        audit('appraisal', (int)$a['id'], $a['user_name'] . ' — ' . $a['title'], 'reopen', $reason);
        flash('success', t('The appraisal is a draft again and can be edited.'));
        redirect(url('appraisals', ['a' => 'edit', 'id' => $a['id']]));

    case 'acknowledge':
        if (!is_post()) {
            abort(405);
        }
        $a = find_appraisal((int)input('id'));
        if ((int)$a['user_id'] !== uid() || $a['status'] !== 'published' || $a['acknowledged_at']) {
            abort(403);
        }
        update('appraisals', ['employee_comment' => trim((string)input('employee_comment', '')) ?: null, 'acknowledged_at' => now()], ['id' => $a['id']]);
        audit('appraisal', (int)$a['id'], $a['user_name'] . ' — ' . $a['title'], 'acknowledge');
        flash('success', t('Thank you. Your acknowledgement has been recorded.'));
        redirect(url('appraisals', ['a' => 'view', 'id' => $a['id']]));

    case 'delete':
        if (!is_post()) {
            abort(405);
        }
        $a = find_appraisal((int)input('id'));
        if (!can_edit_appraisal($a)) {
            abort(403);
        }
        q('DELETE FROM appraisals WHERE id = ?', [$a['id']]);
        audit('appraisal', (int)$a['id'], $a['user_name'] . ' — ' . $a['title'], 'delete');
        flash('success', t('Draft deleted.'));
        redirect(url('appraisals'));

    default:
        abort(404);
}
