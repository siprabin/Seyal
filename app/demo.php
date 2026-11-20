<?php
/**
 * Demo data: a small company with departments, people, clients, projects and tasks.
 * Everything it creates is flagged is_demo = 1 so "Wipe demo data" removes exactly that.
 */
declare(strict_types=1);

const DEMO_PASSWORD = 'Demo@1234';

function demo_loaded(): bool
{
    return (bool)val('SELECT COUNT(*) FROM users WHERE is_demo = 1');
}

function demo_load(): void
{
    if (demo_loaded()) {
        throw new RuntimeException('Demo data is already loaded.');
    }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $now = now();
        $d = fn(int $days) => date('Y-m-d', strtotime(($days >= 0 ? '+' : '') . $days . ' days'));
        $roles = $pdo->query('SELECT slug, id FROM roles')->fetchAll(PDO::FETCH_KEY_PAIR);
        $hash = password_hash(DEMO_PASSWORD, PASSWORD_DEFAULT);

        $dept = [];
        foreach (['dev' => 'Software Development', 'design' => 'Design', 'mkt' => 'Marketing'] as $k => $name) {
            $dept[$k] = insert('departments', ['name' => $name, 'is_demo' => 1, 'created_at' => $now]);
        }

        $people = [
            // key => [name, role, dept, reports_to key, designation]
            'priya'   => ['Priya Raman', ROLE_SENIOR, null, null, 'Operations Director'],
            'karthik' => ['Karthik Subramanian', ROLE_DEPT_HEAD, 'dev', 'priya', 'Head of Engineering'],
            'divya'   => ['Divya Krishnan', ROLE_MANAGER, 'dev', 'karthik', 'Project Manager'],
            'arun'    => ['Arun Kumar', ROLE_MEMBER, 'dev', 'divya', 'Senior Developer'],
            'meena'   => ['Meena Sundaram', ROLE_MEMBER, 'dev', 'divya', 'Developer'],
            'lakshmi' => ['Lakshmi Narayanan', ROLE_MANAGER, 'design', 'priya', 'Design Lead'],
            'suresh'  => ['Suresh Babu', ROLE_MEMBER, 'design', 'lakshmi', 'UI Designer'],
            'anitha'  => ['Anitha Joseph', ROLE_DEPT_HEAD, 'mkt', 'priya', 'Marketing Head'],
            'ravi'    => ['Ravi Shankar', ROLE_MEMBER, 'mkt', 'anitha', 'Content Writer'],
        ];
        $u = [];
        foreach ($people as $key => [$name, $role, $dk, $boss, $desig]) {
            $u[$key] = insert('users', [
                'name' => $name, 'email' => $key . '@demo.local', 'password_hash' => $hash,
                'role_id' => $roles[$role], 'department_id' => $dk ? $dept[$dk] : null,
                'reports_to' => $boss ? $u[$boss] : null, 'designation' => $desig,
                'lang' => ['meena' => 'ta', 'anitha' => 'hi', 'suresh' => 'kn', 'ravi' => 'te', 'lakshmi' => 'ml'][$key] ?? 'en', 'is_demo' => 1, 'created_at' => $now,
            ]);
        }
        update('departments', ['head_user_id' => $u['karthik']], ['id' => $dept['dev']]);
        update('departments', ['head_user_id' => $u['lakshmi']], ['id' => $dept['design']]);
        update('departments', ['head_user_id' => $u['anitha']], ['id' => $dept['mkt']]);

        $c1 = insert('clients', ['name' => 'Sri Lakshmi Textiles', 'contact_person' => 'Mr. Ganesh', 'email' => 'ganesh@example.com', 'phone' => '+91 98400 00001', 'address' => 'Tiruppur, Tamil Nadu', 'is_demo' => 1, 'created_at' => $now]);
        $c2 = insert('clients', ['name' => 'Chennai Foods Pvt Ltd', 'contact_person' => 'Ms. Kavitha', 'email' => 'kavitha@example.com', 'phone' => '+91 98400 00002', 'address' => 'Guindy, Chennai', 'is_demo' => 1, 'created_at' => $now]);
        insert('clients', ['name' => 'GreenLeaf Exports', 'contact_person' => 'Mr. Rahul', 'email' => 'rahul@example.com', 'is_demo' => 1, 'created_at' => $now]);

        $projects = [
            ['E-commerce Website', 'WEB-01', $c1, 'dev', 'divya', 'active', 'high', -30, 20, ['arun', 'meena', 'suresh'], [
                ['Requirements & sitemap', 'divya', 'done', 3, 8, -30, -24],
                ['Home page design', 'suresh', 'done', 3, 12, -24, -15],
                ['Product catalogue module', 'arun', 'in_progress', 5, 40, -14, 5, [
                    ['Category pages', 'arun', 'done', 2, 10, -14, -6],
                    ['Product detail page', 'arun', 'in_progress', 3, 14, -6, 2],
                    ['Search & filters', 'meena', 'todo', 3, 12, 0, 6],
                ]],
                ['Payment gateway integration', 'meena', 'todo', 5, 24, 2, 12],
                ['Order emails', 'meena', 'blocked', 2, 6, -5, -1],
                ['UAT with client', 'divya', 'todo', 4, 16, 12, 18],
            ]],
            ['Mobile App – Order Tracking', 'APP-02', $c2, 'dev', 'divya', 'active', 'medium', -10, 45, ['arun', 'meena'], [
                ['App wireframes', 'suresh', 'review', 3, 10, -10, -2],
                ['API for order status', 'arun', 'in_progress', 4, 20, -5, 7],
                ['Push notifications', 'meena', 'todo', 3, 12, 7, 20],
            ]],
            ['Brand Refresh 2026', 'MKT-03', null, 'mkt', 'anitha', 'planning', 'medium', 0, 60, ['ravi', 'suresh'], [
                ['New logo concepts', 'suresh', 'todo', 4, 16, 3, 14],
                ['Website copy rewrite', 'ravi', 'in_progress', 3, 20, -3, 10],
                ['Social media calendar', 'ravi', 'todo', 2, 6, 10, 15],
            ]],
            ['Internal HR Portal', 'INT-04', null, 'dev', 'karthik', 'on_hold', 'low', -60, -5, ['arun'], [
                ['Leave request form', 'arun', 'done', 2, 8, -60, -50],
                ['Payslip download', 'arun', 'todo', 3, 12, -40, -10],
            ]],
        ];

        $addTasks = function (int $pid, array $list, ?int $parent, int &$sort) use (&$addTasks, $u, $d, $now) {
            foreach ($list as $t) {
                [$title, $who, $status, $w, $hrs, $s, $due] = $t;
                $tid = insert('tasks', [
                    'project_id' => $pid, 'parent_id' => $parent, 'title' => $title,
                    'description' => null, 'assignee_id' => $u[$who], 'status' => $status,
                    'priority' => $w >= 5 ? 'high' : ($w <= 2 ? 'low' : 'medium'), 'weightage' => $w,
                    'estimated_hours' => $hrs, 'start_date' => $d($s), 'due_date' => $d($due),
                    'sort_order' => ++$sort, 'completed_at' => $status === 'done' ? $d($due) . ' 17:00:00' : null,
                    'created_by' => $u['divya'], 'created_at' => $now,
                ]);
                if (!empty($t[7])) {
                    $addTasks($pid, $t[7], $tid, $sort);
                }
            }
        };

        foreach ($projects as [$name, $code, $client, $dk, $pm, $status, $prio, $start, $target, $members, $tasks]) {
            $pid = insert('projects', [
                'code' => $code, 'name' => $name, 'description' => 'Demo project. Safe to edit or delete.',
                'client_id' => $client, 'department_id' => $dept[$dk], 'manager_id' => $u[$pm],
                'status' => $status, 'priority' => $prio, 'start_date' => $d($start), 'target_date' => $d($target),
                'created_by' => $u[$pm], 'is_demo' => 1, 'created_at' => $now,
            ]);
            foreach (array_merge([$pm], $members) as $m) {
                ensure_project_member($pid, $u[$m]);
            }
            $sort = 0;
            $addTasks($pid, $tasks, null, $sort);
        }

        // A few comments and a sample due-date change for the audit log.
        $blocked = row("SELECT t.id, t.title FROM tasks t JOIN projects p ON p.id = t.project_id WHERE p.is_demo = 1 AND t.status = 'blocked' LIMIT 1");
        if ($blocked) {
            insert('task_comments', ['task_id' => $blocked['id'], 'user_id' => $u['meena'], 'body' => 'Waiting for SMTP details from the client.', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))]);
            insert('task_comments', ['task_id' => $blocked['id'], 'user_id' => $u['divya'], 'body' => 'I have followed up with Mr. Ganesh. Expect it by tomorrow.', 'created_at' => date('Y-m-d H:i:s', strtotime('-20 hours'))]);
            audit_raw($u['divya'], 'Divya Krishnan', 'task', (int)$blocked['id'], $blocked['title'], 'update', 'due_date', $d(-3), $d(-1), 'Client delayed sharing the mail server details');
        }
        demo_work_days($u);
        demo_phase3($u, $c1);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/** Phase 2 sample: past working days with entries (some with AI), and a mixed "today". */
function demo_work_days(array $u): void
{
    if (!val("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'work_days'")) {
        return;
    }
    mt_srand(42);
    $tools = q('SELECT name, id FROM ai_tools')->fetchAll(PDO::FETCH_KEY_PAIR);
    $toolIds = array_values($tools);
    $purposes = array_keys(ai_purposes());
    $people = ['divya', 'arun', 'meena', 'lakshmi', 'suresh', 'anitha', 'ravi', 'karthik'];
    $notes = ['Worked on the next step.', 'Reviewed and fixed issues.', 'Discussed with the team and updated.', 'Continued from yesterday.', 'Testing and small fixes.'];

    // Collect up to 6 working days before today.
    $dates = [];
    for ($i = 1; count($dates) < 6 && $i < 20; $i++) {
        $d = date('Y-m-d', strtotime("-$i days"));
        if (is_working_day($d)) {
            $dates[] = $d;
        }
    }
    $dates = array_reverse($dates);
    $dates[] = today();

    foreach ($people as $key) {
        $uid = $u[$key];
        $tasks = q('SELECT id FROM tasks WHERE assignee_id = ?', [$uid])->fetchAll(PDO::FETCH_COLUMN);
        foreach ($dates as $date) {
            $isToday = $date === today();
            // Today: a mix of states so Team Day has something to show.
            $state = 'closed';
            if ($isToday) {
                $state = ['divya' => 'open', 'arun' => 'open', 'meena' => 'closed', 'lakshmi' => 'open', 'suresh' => 'none', 'anitha' => 'open', 'ravi' => 'none', 'karthik' => 'open'][$key];
            } elseif ($key === 'ravi' && $date === $dates[1]) {
                $state = 'missed';
            } elseif ($key === 'suresh' && $date === $dates[2]) {
                $state = 'auto_closed';
            }
            if ($state === 'none') {
                continue;
            }
            $startMin = 9 * 60 + mt_rand(0, 50) + ($key === 'arun' && $date === $dates[3] ? 150 : 0);
            $start = sprintf('%s %02d:%02d:00', $date, intdiv($startMin, 60), $startMin % 60);
            if ($state === 'missed') {
                insert('work_days', ['user_id' => $uid, 'work_date' => $date, 'status' => 'missed', 'flagged' => 1, 'flag_reason' => 'Day was not started', 'created_at' => now()]);
                continue;
            }
            $closeAt = $state === 'closed' ? sprintf('%s %02d:%02d:00', $date, 18 + mt_rand(0, 1), mt_rand(0, 59)) : ($state === 'auto_closed' ? "$date 23:30:00" : null);
            $dayId = insert('work_days', [
                'user_id' => $uid, 'work_date' => $date, 'status' => $state, 'started_at' => $start, 'closed_at' => $closeAt,
                'closed_by' => $state === 'closed' ? $uid : null,
                'flagged' => $state === 'auto_closed' ? 1 : 0, 'flag_reason' => $state === 'auto_closed' ? 'Day was not closed by the person' : null,
                'note' => $state === 'closed' && mt_rand(0, 3) === 0 ? 'Plan for tomorrow: continue the same tasks.' : null,
                'created_at' => $start,
            ]);
            $n = $isToday && $state === 'open' ? mt_rand(1, 2) : mt_rand(2, 4);
            for ($j = 0; $j < $n; $j++) {
                $ai = mt_rand(0, 9) < 6;
                $hours = [1, 1.5, 2, 2.5, 3][mt_rand(0, 4)];
                $taskId = $tasks ? $tasks[mt_rand(0, count($tasks) - 1)] : null;
                $logId = insert('work_logs', [
                    'work_day_id' => $dayId, 'user_id' => $uid, 'task_id' => $taskId,
                    'description' => $taskId ? $notes[mt_rand(0, count($notes) - 1)] : 'Team meeting and planning',
                    'hours' => $hours, 'ai_used' => $ai ? 1 : 0,
                    'ai_minutes' => $ai ? mt_rand(2, (int)($hours * 4)) * 5 : 0,
                    'ai_purpose' => $ai ? $purposes[mt_rand(0, count($purposes) - 1)] : null,
                    'time_saved_minutes' => $ai ? mt_rand(1, 8) * 10 : 0,
                    'output_quality' => $ai ? mt_rand(3, 5) : null,
                    'blockers' => ($key === 'meena' && $j === 0 && mt_rand(0, 2) === 0) ? 'Waiting for client SMTP details.' : null,
                    'created_at' => $start,
                ]);
                if ($ai && $toolIds) {
                    q('INSERT IGNORE INTO work_log_ai_tools (work_log_id, ai_tool_id) VALUES (?, ?)', [$logId, $toolIds[mt_rand(0, count($toolIds) - 1)]]);
                    if (mt_rand(0, 3) === 0) {
                        q('INSERT IGNORE INTO work_log_ai_tools (work_log_id, ai_tool_id) VALUES (?, ?)', [$logId, $toolIds[mt_rand(0, count($toolIds) - 1)]]);
                    }
                }
            }
        }
    }
}

/** Phase 3 sample: task ratings, weekly reviews, two appraisals and a client portal login. */
function demo_phase3(array $u, int $clientId): void
{
    if (!val("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'task_ratings'")) {
        return;
    }
    mt_srand(7);
    $comments = ['Clean work, well tested.', 'Good, but needed a second review.', 'Excellent — client was happy.', 'Done, a few small fixes needed.', null];
    foreach (rows("SELECT t.*, a.reports_to FROM tasks t JOIN projects p ON p.id = t.project_id JOIN users a ON a.id = t.assignee_id
                   WHERE p.is_demo = 1 AND t.status = 'done' AND a.reports_to IS NOT NULL") as $t) {
        $completed = substr((string)$t['completed_at'], 0, 10);
        $late = $t['due_date'] ? (int)round((strtotime($completed) - strtotime($t['due_date'])) / 86400) : 0;
        insert('task_ratings', [
            'task_id' => $t['id'], 'user_id' => $t['assignee_id'], 'rated_by' => $t['reports_to'], 'quality' => mt_rand(3, 5),
            'weightage' => $t['weightage'], 'on_time' => $late <= 0 ? 1 : 0, 'days_late' => max(0, $late), 'completed_on' => $completed,
            'comment' => $comments[mt_rand(0, count($comments) - 1)], 'created_at' => now(),
        ]);
    }
    $notes = [
        ['Delivered the catalogue pages ahead of plan.', 'Raise blockers earlier in the day.', 'Finish product detail page and start search.'],
        ['Good use of AI for test cases.', 'Write clearer task descriptions.', 'Close the open review comments.'],
        ['Helped the team with a tricky bug.', 'Log time daily instead of at the end of the week.', 'Payment gateway research.'],
    ];
    foreach (['arun' => 'divya', 'meena' => 'divya', 'divya' => 'karthik', 'suresh' => 'lakshmi', 'ravi' => 'anitha'] as $who => $mgr) {
        for ($w = 1; $w <= 3; $w++) {
            [$well, $improve, $focus] = $notes[($w + strlen($who)) % 3];
            insert('weekly_reviews', [
                'user_id' => $u[$who], 'reviewer_id' => $u[$mgr], 'week_start' => date('Y-m-d', strtotime("monday this week -$w weeks")),
                'rating' => mt_rand(3, 5), 'went_well' => $well, 'to_improve' => $improve, 'next_focus' => $focus, 'shared' => 1, 'created_at' => now(),
            ]);
        }
    }
    $from = date('Y-m-d', strtotime('-30 days'));
    $to = date('Y-m-d', strtotime('-1 day'));
    $m = person_metrics($u['meena'], $from, $to);
    insert('appraisals', [
        'user_id' => $u['meena'], 'reviewer_id' => $u['divya'], 'title' => 'Monthly check-in (demo)', 'period_from' => $from, 'period_to' => $to,
        'status' => 'published', 'overall_rating' => 4, 'strengths' => 'Reliable delivery and clear communication with the client.',
        'improvements' => 'Estimate tasks more carefully; a few ran over.', 'goals' => 'Own the payment gateway integration end to end.',
        'manager_comments' => 'Great first month on the e-commerce project.', 'metrics_json' => json_encode($m), 'score' => $m['score'],
        'finalised_at' => now(), 'finalised_by' => $u['divya'], 'published_at' => now(), 'published_by' => $u['divya'], 'created_at' => now(),
    ]);
    insert('appraisals', [
        'user_id' => $u['arun'], 'reviewer_id' => $u['divya'], 'title' => 'Monthly check-in (demo)', 'period_from' => $from, 'period_to' => $to,
        'status' => 'draft', 'overall_rating' => null, 'strengths' => 'Strong on backend work.', 'created_at' => now(),
    ]);
    $roleId = (int)val("SELECT id FROM roles WHERE slug = 'client'");
    insert('users', [
        'name' => 'Mr. Ganesh (client)', 'email' => 'client@demo.local', 'password_hash' => password_hash(DEMO_PASSWORD, PASSWORD_DEFAULT),
        'role_id' => $roleId, 'client_id' => $clientId, 'designation' => 'Sri Lakshmi Textiles', 'lang' => 'en', 'is_demo' => 1, 'created_at' => now(),
    ]);
}

function demo_wipe(): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        foreach (rows('SELECT a.stored_name FROM attachments a JOIN tasks t ON t.id = a.task_id JOIN projects p ON p.id = t.project_id WHERE p.is_demo = 1') as $f) {
            @unlink(APP_ROOT . '/uploads/' . basename($f['stored_name']));
        }
        // Delete tasks child-first to avoid self-referencing cascade limits, then projects.
        q('DELETE t FROM tasks t JOIN projects p ON p.id = t.project_id WHERE p.is_demo = 1 AND t.parent_id IS NOT NULL');
        q('DELETE FROM projects WHERE is_demo = 1');
        q('DELETE FROM password_resets WHERE user_id IN (SELECT id FROM users WHERE is_demo = 1)');
        q('UPDATE users SET reports_to = NULL WHERE is_demo = 1');
        q('DELETE FROM users WHERE is_demo = 1');
        q('DELETE FROM departments WHERE is_demo = 1');
        q('DELETE FROM clients WHERE is_demo = 1 AND id NOT IN (SELECT client_id FROM (SELECT client_id FROM projects WHERE client_id IS NOT NULL) x)');
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
