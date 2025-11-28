<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\mod_assign\event\submission_graded',
        'callback'  => 'local_gamify\observer::submission_graded',
        'includefile' => '/local/gamify/classes/observer.php',
        'internal' => false,
    ],
];
