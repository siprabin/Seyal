<?php
/**
 * Seyal help guide — English. Sets $sections for the table of contents (id => title).
 * To translate: copy this file to views/help/<code>.php (e.g. ta.php) and translate the text.
 * Keep the section ids the same.
 */
$sections = [
    'start'     => 'Getting started',
    'day'       => 'Your day: Start, log work, Close',
    'time'      => 'Entering time',
    'ai'        => 'Recording AI use',
    'mytasks'   => 'My Tasks',
    'projects'  => 'Projects and tasks',
    'managers'  => 'For managers',
    'perf'      => 'Performance and appraisals',
    'reports'   => 'Reports and exports',
    'clients'   => 'Clients and the client portal',
    'admin'     => 'For administrators',
    'phone'     => 'Using Seyal on your phone',
    'faq'       => 'Questions and fixes',
    'support'   => 'Getting more help',
];
$who = fn(string $label) => '<span class="badge bg-light text-dark border fw-normal ms-1">' . e($label) . '</span>';
?>

<h2 id="start">Getting started</h2>
<p><strong>Seyal</strong> (செயல், "action") helps your team plan projects, record each day's work, and review performance. A short <strong>guided tour</strong> runs the first time you log in. You can watch it again any time: click the <i class="bi bi-question-circle"></i> button at the top of the screen and choose <strong>Take the tour</strong>.</p>
<ul>
  <li><strong>Log in</strong> with the email and password your administrator gave you. Forgot it? Use <em>Forgot password</em> on the login page. After several wrong passwords the account is locked for 15 minutes.</li>
  <li><strong>Language:</strong> click <i class="bi bi-translate"></i> in the top bar to use Seyal in English, हिन्दी, தமிழ், ಕನ್ನಡ, తెలుగు, മലയാളം, العربية, Deutsch or Español. Your choice is remembered.</li>
  <li><strong>My Profile</strong> (click your name, top right): phone number, language and password.</li>
  <li><strong>Side menu:</strong> click <i class="bi bi-list"></i> to shrink it to icons; point at an icon to see its name.</li>
</ul>

<h2 id="day">Your day: Start, log work, Close</h2>
<ol>
  <li><strong>Start Day</strong> — click the green <em>Start Day</em> button (top bar, Dashboard or My Day) when you begin work. Your start time is recorded. If your company has switched on location, the browser asks to share your location; see <a href="#faq">Questions and fixes</a> if it is not captured.</li>
  <li><strong>Log work</strong> — on <strong>My Day</strong>, add an entry for each piece of work:
    <ul>
      <li><strong>Task</strong> — pick one of your tasks, or <em>Other work (not a task)</em> and describe it.</li>
      <li><strong>What did you do?</strong> — a short description.</li>
      <li><strong>Time spent</strong> — hours and minutes (see <a href="#time">Entering time</a>).</li>
      <li><strong>Task status now</strong> — optionally move the task on, e.g. to <em>Done</em>.</li>
      <li><strong>I used AI for this work</strong> — see <a href="#ai">Recording AI use</a>.</li>
      <li><strong>Blockers</strong> — anything stopping you; your manager sees it in Team Day.</li>
    </ul>
    You can edit or delete entries until the day is closed.</li>
  <li><strong>Close Day</strong> — click <em>Close Day</em>, check the summary, add an optional note and confirm. The day is locked, the summary is emailed to your manager (and posted to Microsoft Teams if set up). If you logged no work, a note explaining why is required.</li>
</ol>
<p>Reminders arrive if you have not started your day by the start time or closed it by the evening. Days left open are closed automatically late at night and flagged. If you need to add something to a closed day, ask your manager to <strong>reopen</strong> it. <strong>My past days</strong> (on My Day) shows your history.</p>

<h2 id="time">Entering time</h2>
<p>Time is always picked as <strong>hours + minutes</strong> — for example <kbd>1</kbd> h <kbd>20</kbd> min — and shown the same way everywhere ("1 h 20 min"). There is no need to convert minutes to decimals.</p>
<ul>
  <li>Use the quick buttons (15 min, 30 min, 1 h…) for common lengths.</li>
  <li>If you pick only minutes, the hours become 0 automatically.</li>
  <li>One entry can be up to 24 hours, and a day cannot total more than 24 hours.</li>
  <li>The same picker is used for AI time, time saved, and a task's estimated time.</li>
</ul>

<h2 id="ai">Recording AI use</h2>
<p>Switch on <strong>I used AI for this work</strong> on a work entry, then fill in:</p>
<ul>
  <li><strong>Which tools?</strong> — e.g. ChatGPT, Gemini, Claude (your administrator manages the list).</li>
  <li><strong>Time using AI</strong> — time spent asking and working with the AI tool (cannot be more than the entry's time).</li>
  <li><strong>Time saved</strong> — your honest estimate of how much time AI saved.</li>
  <li><strong>Used AI for</strong> — coding, debugging/testing, writing, research, design, data analysis, documentation, translation, email or other.</li>
  <li><strong>Quality of the AI output</strong> — 1 (poor) to 5 (excellent).</li>
</ul>
<p>This shows the team and management where AI really helps.</p>

<h2 id="mytasks">My Tasks</h2>
<p>Everything assigned to you across all projects.</p>
<ul>
  <li><strong>Tabs:</strong> Open, Overdue, Due in 7 days, Done (last 30 days). <strong>List</strong> or <strong>Board</strong> view.</li>
  <li><strong>Filters:</strong> search, plus <em>Project</em>, <em>Status</em>, <em>Priority</em> and <em>Due</em>. Each filter lets you tick several values; close the list to apply. <em>Clear filters</em> resets them.</li>
  <li><strong>Add task:</strong> with one project, opens the new-task form directly; with several, pick the project first. New tasks are assigned to you by default, and you come back to My Tasks after saving.</li>
  <li><strong>Change status</strong> straight from the list, or drag cards on the board.</li>
</ul>

<h2 id="projects">Projects and tasks</h2>
<ul>
  <li><strong>Projects</strong> lists the projects you manage, belong to or have tasks in. Open one for its <strong>List</strong>, <strong>Board</strong> and <strong>History</strong>. The task list and board have the same multi-select filters as My Tasks, plus <em>Assignee</em> (including <em>Unassigned</em>).</li>
  <li><strong>Task details:</strong> assignee, status, priority, <strong>weightage</strong> (1 = very small … 5 = critical; it weights project progress and performance scores), estimated time, start and due dates.</li>
  <li><strong>Changing a due date</strong> on an existing task asks for a reason, which is saved in the history.</li>
  <li><strong>Subtasks:</strong> on a task, type a title in the quick-add row and click <em>Add</em>, or click <i class="bi bi-sliders"></i> <em>More options</em> to open the full form with what you typed already filled in.</li>
  <li><strong>Comments and files</strong> can be added on every task.</li>
  <li><strong>Who adds tasks:</strong> project managers (and above) add and assign tasks. If a project has <em>Team members can add their own tasks</em> switched on, its team members can add tasks too — always assigned to themselves, and without setting the weightage.</li>
</ul>

<h2 id="managers">For managers <?= $who('Managers') ?></h2>
<h3>Team Day</h3>
<p>Who has started, closed, not started, is on leave or started late — with hours, AI time and blockers for each person. A <i class="bi bi-geo-alt-fill"></i> pin next to a time shows where the day was started or closed (when location is switched on); click it to open the map. Click a name to see that person's day. From there you can <strong>Reopen</strong> a closed day (with a reason) or use <strong>Mark leave</strong>.</p>
<h3>Project Pulse</h3>
<p>Every project at a glance: % done, target date vs <strong>forecast</strong> date (from the last 4 weeks' pace), and 🟢 on track / 🟡 at risk / 🔴 off track. Also today's attendance, overdue tasks and weekly AI use.</p>
<h3>Task ratings and weekly reviews</h3>
<ul>
  <li><strong>Task ratings:</strong> when a task of someone who reports to you is done, rate its quality 1–5. Whether it was on time is worked out from the due date. The menu shows how many are waiting.</li>
  <li><strong>Weekly reviews:</strong> rate each direct report's week 1–5 with comments. You can share the comments; the rating stays private.</li>
</ul>

<h2 id="perf">Performance and appraisals</h2>
<ul>
  <li><strong>Performance score (0–100)</strong> = weightage × quality × on-time, compared with the best possible. Late tasks count at a reduced value (set by the administrator).</li>
  <li><strong>Appraisals</strong> go <em>Draft</em> → <em>Finalised</em> (figures frozen) → <em>Published</em>. The employee is emailed, reads it and clicks <em>Acknowledge</em>.</li>
  <li><strong>Employees see their own scores only after an appraisal is published.</strong></li>
</ul>

<h2 id="reports">Reports and exports</h2>
<p><strong>Reports</strong> offers Timesheets, AI usage, Project status, Performance and Attendance, filtered by period, person, department or project. Export to <strong>Excel</strong>, <strong>CSV</strong> or <strong>PDF</strong> (choose <em>Save as PDF</em> in the print window). In Excel and CSV, hours are decimal numbers (1 h 20 min = 1.33) so they can be added up.</p>

<h2 id="clients">Clients and the client portal <?= $who('Managers') ?></h2>
<p>Project managers, department heads and senior management can add clients. On a client's page, <strong>Client portal access → Add a portal login</strong> gives the client a read-only view of their own projects' progress, status and dates — never hours, ratings, comments, files, AI use or staff details. Switch off <em>Show in client portal</em> on a project to hide it.</p>

<h2 id="admin">For administrators <?= $who('Admins') ?></h2>
<ul>
  <li><strong>Users</strong> — add people, set their role, department and <em>Reports to</em> (this builds the hierarchy and decides who rates whom). Deactivate rather than delete, to keep history. <strong>Org Chart</strong> shows the structure.</li>
  <li><strong>Roles &amp; Permissions</strong> — what each role may do.</li>
  <li><strong>AI Tools</strong> — the list of tools people can pick.</li>
  <li><strong>Settings:</strong>
    <ul>
      <li><em>General</em> — company name, time zone, date format, default language for new users.</li>
      <li><em>Email</em> and <em>Microsoft Teams</em> — where Close Day summaries and digests go (each has a test button).</li>
      <li><em>Daily work</em> — who logs their day, working days, holidays, reminder times, and <strong>Location at Start Day and Close Day</strong> (Off / Ask / Required; needs HTTPS).</li>
      <li><em>Performance</em>, <em>File uploads</em>, <em>Backup</em> (back up now, nightly backups), <em>Demo data</em>, <em>System</em> (version, scheduler, <strong>Run database updates</strong> after an upgrade).</li>
    </ul></li>
  <li><strong>Audit Log</strong> — every change: who, what and when.</li>
</ul>

<h2 id="phone">Using Seyal on your phone</h2>
<p>Seyal works in any phone browser. To open it like an app with its own icon: on <strong>Android (Chrome)</strong> choose <em>⋮ → Add to Home screen / Install app</em>; on <strong>iPhone (Safari)</strong> tap <em>Share → Add to Home Screen</em>. On a phone the side menu opens with the <i class="bi bi-list"></i> button.</p>

<h2 id="faq">Questions and fixes</h2>
<dl>
  <dt>My location is not captured at Start / Close Day.</dt>
  <dd>Allow location for the site (🔒 icon next to the address → Location → Allow) and make sure location is on in your phone. Browsers only share location on <strong>https://</strong> addresses (or localhost). If it still fails, tell your administrator.</dd>
  <dt>I can't see "Add task" on a project.</dt>
  <dd>Only project managers can add tasks, unless the project has <em>Team members can add their own tasks</em> switched on and you are on its team.</dd>
  <dt>I closed my day too early.</dt>
  <dd>Ask your manager to reopen it from Team Day.</dd>
  <dt>I can't see my performance score.</dt>
  <dd>Scores become visible once your manager publishes an appraisal for that period.</dd>
  <dt>The page says "The system is being updated".</dt>
  <dd>An administrator needs to run <em>Settings → System → Run database updates</em>. Try again in a few minutes.</dd>
  <dt>Something looks wrong after an update.</dt>
  <dd>Reload the page without the cache: <kbd>Ctrl</kbd>+<kbd>F5</kbd> (Windows) or <kbd>Cmd</kbd>+<kbd>Shift</kbd>+<kbd>R</kbd> (Mac).</dd>
</dl>

<h2 id="support">Getting more help</h2>
<ul>
  <li>Watch the tour again: <i class="bi bi-question-circle"></i> → <strong>Take the tour</strong>.</li>
  <li>Ask your manager or your Seyal administrator.</li>
  <li>Installation and setup support from the developers: <a href="<?= e(APP_SUPPORT_URL) ?>" target="_blank" rel="noopener"><?= e(preg_replace('#^https?://#', '', APP_SUPPORT_URL)) ?></a>.</li>
</ul>
