<?php
// local/gamify/teacher.php

require_once(__DIR__ . '/../../config.php');
require_login();

global $DB;

// Params
$courseid = optional_param('courseid', 0, PARAM_INT);
$page     = optional_param('page', 1, PARAM_INT);
$search   = optional_param('search', '', PARAM_TEXT);

// Determine context safely
if ($courseid > 0) {

    if (!$DB->record_exists('course', ['id' => $courseid])) {
        throw new moodle_exception('invalidcourseid', 'error');
    }

    $context = context_course::instance($courseid);

} else {
    // No course selected → dashboard home
    $context = context_system::instance();
}

$PAGE->set_context($context);
$PAGE->set_url('/local/gamify/teacher.php', [
    'courseid' => $courseid,
    'page'     => $page,
    'search'   => $search
]);
$PAGE->set_pagelayout('standard');

$PAGE->set_title(get_string('teacherxptitle', 'local_gamify'));
$PAGE->set_heading(get_string('teacherxpheading', 'local_gamify'));


// -----------------------------------------------------
// SAFE PERMISSION CHECK
// -----------------------------------------------------
if ($courseid > 0) {
    // Inside a course → teacher/admin only
    if (!is_siteadmin() &&
        !has_capability('moodle/course:update', $context)) {
        throw new required_capability_exception($context, 'moodle/course:update', 'nopermissions', '');
    }
} else {
    // No course selected → NO capability check
    // Let teachers land on the dashboard safely
}

// Renderer
$renderer = $PAGE->get_renderer('local_gamify');

echo $OUTPUT->header();
echo $renderer->render_teacher_xp_view($courseid, $page, $search);
echo $OUTPUT->footer();
