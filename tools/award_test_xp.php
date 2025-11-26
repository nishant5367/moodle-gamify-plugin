<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
if (!is_siteadmin()) {
    die('only admin');
}

$userid = required_param('userid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$points = required_param('points', PARAM_INT);

$xpman = new \local_gamify\manager\xp_manager();
$xpman->award_xp($userid, $courseid, $cmid, $points, 'manual_test', 'Admin test awarding XP', $USER->id);

echo "Awarded $points XP to user $userid in course $courseid.";
