<?php
// local/gamify/student.php

require_once(__DIR__ . '/../../config.php');

require_login();  // Must be logged in always

global $USER, $DB, $PAGE, $SITE;

$userid   = $USER->id;
$courseid = optional_param('courseid', 0, PARAM_INT);

/**
 * Step 1: Determine which course to show XP for.
 * If student navigated inside a course → that course.
 * Otherwise → pick FIRST enrolled course.
 */
if ($courseid <= 0) {
    $courses = enrol_get_users_courses($userid);

    if (!empty($courses)) {
        $first = reset($courses);
        $courseid = $first->id;
    } else {
        // Student is not enrolled anywhere
        print_error("nocourses", "local_gamify");
    }
}

// Require student to be enrolled in this course
require_login($courseid);

$context = context_course::instance($courseid);
$PAGE->set_context($context);

$PAGE->set_url('/local/gamify/student.php', ['courseid' => $courseid]);
$PAGE->set_pagelayout('standard');

$PAGE->set_title("My XP Dashboard");
$PAGE->set_heading("My XP Dashboard");

use local_gamify\manager\xp_manager;

$xp = new xp_manager();

/**  
 * Step 2: Get XP + Level
 */
$levelinfo = $xp->get_user_xp_and_level($userid, $courseid);

/**
 * Step 3: Fetch last 10 XP events
 */
$events = $DB->get_records_sql("
    SELECT *
      FROM {local_gamify_xp_events}
     WHERE userid = :userid 
       AND courseid = :courseid
  ORDER BY createdat DESC
     LIMIT 10
", [
    'userid'   => $userid,
    'courseid' => $courseid
]);

$events_out = [];
foreach ($events as $e) {
    $events_out[] = [
        'points' => $e->points,
        'source' => format_string($e->source),
        'reason' => $e->reason ? format_string($e->reason) : null,
        'grader' => $e->graderid ? fullname(core_user::get_user($e->graderid)) : "System",
        'time'   => userdate($e->createdat)
    ];
}

/**
 * Step 4: Build leaderboard for THIS COURSE
 */
$records = $DB->get_records_sql("
    SELECT u.id, u.firstname, u.lastname, xp.totalxp
      FROM {local_gamify_xp} xp
      JOIN {user} u ON u.id = xp.userid
     WHERE xp.courseid = :cid
  ORDER BY xp.totalxp DESC
", ['cid' => $courseid]);

$leaderboard = [];
$rank = 1;
foreach ($records as $r) {
    $userpicture = new \user_picture($r);
$userpicture->size = 80;
$profileimg = $userpicture->get_url($PAGE)->out();

$leaderboard[] = [
    'rank'     => $rank++,
    'fullname' => fullname($r),
    'xp'       => (int)$r->totalxp,
    'level'    => floor($r->totalxp / 100) + 1,
    'progress' => min(100, ($r->totalxp % 100)),
    'profileimg' => $profileimg
];

}

/**
 * Step 5: Final template data
 */
$data = [
    'userfullname' => fullname($USER),
    'sitename'     => format_string($SITE->fullname),

    'total_xp' => $levelinfo['xp'],
    'level'    => $levelinfo['level'],
    'min'      => $levelinfo['min'],
    'max'      => $levelinfo['max'],
    'progress' => round($levelinfo['progress'] * 100),

    'events'    => $events_out,
    'hasevents' => !empty($events_out),

    'leaderboard' => $leaderboard,
    'hasleaderboard' => !empty($leaderboard),

    'courseid' => $courseid
];

/**
 * Step 6: Render
 */
$renderer = $PAGE->get_renderer('local_gamify');

echo $OUTPUT->header();
echo $renderer->render_student_xp($data);  // Correct render method
echo $OUTPUT->footer();
