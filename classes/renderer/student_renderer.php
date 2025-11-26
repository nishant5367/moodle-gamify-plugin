<?php
namespace local_gamify\renderer;

defined('MOODLE_INTERNAL') || die();

class student_renderer extends \plugin_renderer_base {
    public function render_student_xp($data) {
        return $this->render_from_template('local_gamify/student_xp', $data);
    }
}
