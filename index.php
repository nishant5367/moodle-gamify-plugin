<?php
// local/gamify/index.php
require_once(__DIR__ . '/../../config.php');

use local_gamify\manager\xp_manager;

require_login(); // Must login first.

// 1) USER ID = logged-in user
$userid = $USER->id;

// 2) COURSE ID (auto-detect if missing)
$courseid = optional_param('courseid', 0, PARAM_INT);

if ($courseid <= 0) {
    // Try to detect enrolled courses
    $enrolled = enrol_get_users_courses($userid);

    if (!empty($enrolled)) {
        $firstcourse = reset($enrolled);
        $courseid = $firstcourse->id;
    } else {
        // No courses found → show clean error
        print_error('nocourses', 'local_gamify');
    }
}

// Set Moodle page context
$context = context_course::instance($courseid);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/gamify/index.php', ['courseid' => $courseid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('studentdashboardtitle', 'local_gamify'));
$PAGE->set_heading(get_string('studentdashboardheading', 'local_gamify'));

// XP manager
$xpman = new xp_manager();

// Ensure totals exist
$xpinfo = $xpman->get_user_xp_and_level($userid, $courseid);

// Get recent XP events (latest 5)
global $DB;
$events = $DB->get_records('local_gamify_xp_events',
    ['userid' => $userid, 'courseid' => $courseid],
    'createdat DESC',
    '*',
    0,
    5
);

$eventsarray = [];
foreach ($events as $e) {
    $eventsarray[] = [
        'points' => (int)$e->points,
        'source' => format_string($e->source),
        'reason' => format_text($e->reason ?? '', FORMAT_PLAIN),
        'grader' => ($e->graderid
            ? fullname(\core_user::get_user($e->graderid))
            : get_string('system', 'local_gamify')),
        'time' => userdate($e->createdat),
    ];
}

// Template data
$data = [
    'total_xp'      => $xpinfo['xp'],
    'level'         => $xpinfo['level'],
    'progress'      => round($xpinfo['progress'] * 100), // 0-100
    'min'           => $xpinfo['min'],
    'max'           => $xpinfo['max'],
    'events'        => $eventsarray,
    'hasevents'     => !empty($eventsarray),
    'sitename'      => format_string($SITE->fullname),
    'userfullname'  => fullname($USER),
    'courseid'      => $courseid,
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_gamify/student_xp', $data);
echo $OUTPUT->footer();
