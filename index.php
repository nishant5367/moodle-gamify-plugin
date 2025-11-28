<?php
// local/gamify/index.php
// This file ONLY decides where to redirect user:
// Student -> student dashboard
// Teacher/Admin -> teacher dashboard

require_once(__DIR__ . '/../../config.php');

require_login(); // Must be logged in

global $USER, $DB;

// 1) Determine course ID
$courseid = optional_param('courseid', 0, PARAM_INT);

if ($courseid <= 0) {
    // Auto-detect first enrolled course
    $courses = enrol_get_users_courses($USER->id);
    if (!empty($courses)) {
        $first = reset($courses);
        $courseid = $first->id;
    } else {
        print_error('nocourses', 'local_gamify');
    }
}

$context = context_course::instance($courseid);

// 2) If user is a teacher/admin → go to teacher dashboard
if (is_siteadmin($USER) || has_capability('moodle/course:update', $context)) {
    redirect(new moodle_url('/local/gamify/teacher.php', [
        'courseid' => $courseid
    ]));
    exit;
}

// 3) Otherwise → student dashboard
redirect(new moodle_url('/local/gamify/student.php', [
    'courseid' => $courseid
]));
exit;
