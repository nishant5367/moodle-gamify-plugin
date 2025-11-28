<?php
namespace local_gamify;

defined('MOODLE_INTERNAL') || die();

use local_gamify\manager\xp_manager;

class observer {
    public static function submission_graded(\core\event\base $event) {
        global $DB;

        $data = $event->get_data();

        $userid = (int) ($data['relateduserid'] ?? $data['userid'] ?? 0);
        $courseid = (int) ($data['courseid'] ?? 0);
        $cmid = (int) ($data['contextinstanceid'] ?? 0);
        $graderid = (int) ($data['userid'] ?? 0);

        $gradevalue = 0.0;
        $maxgrade = 100.0;

        $other = $data['other'] ?? null;
        if (is_array($other) && isset($other['grade'])) {
            $gradevalue = (float) $other['grade'];
        } else {
            try {
                $sql = "SELECT gg.finalgrade, gi.grademax
                          FROM {grade_grades} gg
                          JOIN {grade_items} gi ON gi.id = gg.itemid
                         WHERE gg.userid = :userid AND gi.courseid = :courseid
                      ORDER BY gg.timemodified DESC
                         LIMIT 1";
                $rec = $DB->get_record_sql($sql, ['userid' => $userid, 'courseid' => $courseid]);
                if ($rec) {
                    $gradevalue = (float) $rec->finalgrade;
                    $maxgrade = (float) $rec->grademax ?: $maxgrade;
                }
            } catch (\Exception $e) {
            }
        }

        $submittedtime = $event->timecreated ?? time();
        $on_time = true;
        try {
            if ($cmid) {
                $cm = get_coursemodule_from_id(null, $cmid, 0, false, MUST_EXIST);
                if (!empty($cm->instance)) {
                    $assignrec = $DB->get_record('assign', ['id' => $cm->instance]);
                    if ($assignrec && !empty($assignrec->duedate) && $submittedtime > $assignrec->duedate) {
                        $on_time = false;
                    }
                }
            }
        } catch (\Throwable $t) {
        }

        $xpman = new xp_manager();
        $xp = $xpman->calculate_xp_from_grade((float)$gradevalue, (float)$maxgrade, $on_time);

        if ($userid && $courseid) {
            $xpman->award_xp($userid, $courseid, $cmid, $xp, 'assign_grade', 'Awarded from assignment grade', $graderid);
        }
    }
}
