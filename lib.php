<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Load custom Gamify CSS everywhere.
 */
function local_gamify_before_standard_html_head() {
    global $PAGE;
    $PAGE->requires->css('/local/gamify/styles.css');
}

/**
 * Add a role-based "Gamify / XP" link inside course navigation.
 *
 * STUDENT → "My XP"
 * TEACHER → "XP Manager"
 * ADMIN   → "Gamify"
 */
function local_gamify_extend_navigation_course($navigation, $course, $context) {
    global $USER;

    // Only show if user is enrolled
    if (!is_enrolled($context, $USER->id)) {
        return;
    }

    // Determine link name based on role
    if (is_siteadmin($USER)) {
        $label = get_string('navtitle', 'local_gamify'); // Gamify
    }
    else if (has_capability('moodle/course:update', $context)) {
        // Editing teacher or teacher-like roles
        $label = get_string('navtitle_teacher', 'local_gamify'); // XP Manager
    }
    else {
        // Default → student
        $label = get_string('navtitle_student', 'local_gamify'); // My XP
    }

    // URL to student/teacher auto router
    $url = new moodle_url('/local/gamify/index.php', [
        'courseid' => $course->id
    ]);

    // Create navigation node
    $node = navigation_node::create(
        $label,
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'local_gamify',
        new pix_icon('i/star', $label)
    );

    $navigation->add_node($node);
}
