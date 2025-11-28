<?php
namespace local_gamify\output;

defined('MOODLE_INTERNAL') || die();

// Import required view classes
use local_gamify\output\teacher_xp_view;

class renderer extends \plugin_renderer_base {

    /**
     * Render Teacher XP Dashboard (with search + pagination)
     *
     * @param int $courseid
     * @param int $page
     * @param string $search
     * @return string
     */
    public function render_teacher_xp_view($courseid = 0, $page = 1, $search = '') {

        // Build the view object
        $view = new teacher_xp_view($courseid, $page, $search);

        // Render through Mustache template
        return $this->render_from_template(
            'local_gamify/teacher_xp',
            $view->export_for_template($this)
        );
    }

    /**
     * Render Student XP Dashboard
     *
     * @param array $data
     * @return string
     */
    public function render_student_xp(array $data) {

        return $this->render_from_template(
            'local_gamify/student_xp',
            $data
        );
    }
}
