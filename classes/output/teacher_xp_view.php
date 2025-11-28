<?php
namespace local_gamify\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

class teacher_xp_view implements renderable, templatable {

    private $courseid;

    public function __construct(int $courseid = 0) {
        $this->courseid = $courseid;
    }

    public function export_for_template(renderer_base $output) {
        global $DB, $USER;

        // Fetch teacher courses
        $courses = enrol_get_users_courses($USER->id);

        $courselist = [];
        foreach ($courses as $c) {
            $courselist[] = [
                'id' => $c->id,
                'fullname' => format_string($c->fullname),
                'selected' => ($c->id == $this->courseid)
            ];
        }

        if ($this->courseid <= 0) {
            return [
                'hascourse' => false,
                'courselist' => $courselist
            ];
        }

        // Course name
        $course = $DB->get_record('course', ['id' => $this->courseid], '*', MUST_EXIST);

        // Pagination
        $page = optional_param('page', 0, PARAM_INT);
        $perpage = 10;
        $offset = $page * $perpage;

        // Count total
        $total = $DB->count_records('local_gamify_xp', ['courseid' => $this->courseid]);

        // Fetch paginated leaderboard
        $records = $DB->get_records_sql("
            SELECT u.id, u.firstname, u.lastname, xp.totalxp
            FROM {local_gamify_xp} xp
            JOIN {user} u ON u.id = xp.userid
            WHERE xp.courseid = :cid
            ORDER BY xp.totalxp DESC
            LIMIT $perpage OFFSET $offset
        ", ['cid' => $this->courseid]);

        $leaderboard = [];
        $rank = $offset + 1;

        foreach ($records as $u) {
            $userpicture = new \user_picture($u);
            $userpicture->size = 80;
            $profileimg = $userpicture->get_url($GLOBALS['PAGE'])->out();

            $leaderboard[] = [
                'rank' => $rank++,
                'fullname' => fullname($u),
                'xp' => (int)$u->totalxp,
                'level' => floor($u->totalxp / 100) + 1,
                'progress' => min(100, ($u->totalxp % 100)),
                'profileimg' => $profileimg
            ];
        }

        return [
            'hascourse' => true,
            'courseid' => $this->courseid,
            'coursename' => $course->fullname,
            'courselist' => $courselist,
            'leaderboard' => $leaderboard,
            'hasstudents' => count($leaderboard) > 0,

            // Pagination
            'showpagination' => ($total > $perpage),
            'prevpage' => ($page > 0) ? $page - 1 : null,
            'nextpage' => ($offset + $perpage < $total) ? $page + 1 : null
        ];
    }
}
