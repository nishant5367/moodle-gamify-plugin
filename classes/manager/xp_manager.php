<?php
namespace local_gamify\manager;

defined('MOODLE_INTERNAL') || die();

class xp_manager {
    protected $db;

    public function __construct() {
        global $DB;
        $this->db = $DB;
    }

    public function calculate_xp_from_grade(float $grade, float $maxgrade, bool $on_time = true): int {
        if ($maxgrade <= 0) {
            return 0;
        }
        $percent = ($grade / $maxgrade) * 100.0;
        $base = (int) round($percent); // 0..100
        if ($on_time) {
            $base = (int) round($base * 1.10);
        } else {
            $base = (int) round($base * 0.90);
        }
        return max(0, $base);
    }

    public function award_xp(int $userid, int $courseid, int $cmid, int $points, string $source = 'system', ?string $reason = null, ?int $graderid = null): bool {
        global $DB;

        $timenow = time();
        $transaction = $DB->start_delegated_transaction();

        $event = new \stdClass();
        $event->userid    = $userid;
        $event->courseid  = $courseid;
        $event->cmid      = $cmid;
        $event->points    = $points;
        $event->source    = $source;
        $event->reason    = $reason;
        $event->graderid  = $graderid ?: 0;
        $event->createdat = $timenow;

        $DB->insert_record('local_gamify_xp_events', $event);

        $record = $DB->get_record('local_gamify_xp', ['userid' => $userid, 'courseid' => $courseid], '*', IGNORE_MISSING);
        if ($record) {
            $record->totalxp = max(0, ((int)$record->totalxp) + $points);
            $record->updatedat = $timenow;
            $DB->update_record('local_gamify_xp', $record);
        } else {
            $record = new \stdClass();
            $record->userid = $userid;
            $record->courseid = $courseid;
            $record->totalxp = max(0, $points);
            $record->updatedat = $timenow;
            $DB->insert_record('local_gamify_xp', $record);
        }

        $transaction->allow_commit();
        return true;
    }

    public function reset_user_course_xp(int $userid, int $courseid) {
        global $DB;
        $DB->delete_records('local_gamify_xp_events', ['userid' => $userid, 'courseid' => $courseid]);
        $DB->delete_records('local_gamify_xp', ['userid' => $userid, 'courseid' => $courseid]);
    }
        /**
     * Recalculate total XP for one user in one course (sum of all XP events).
     */
    public function recalc_total_for_user_course(int $userid, int $courseid): int {
        global $DB;

        $sql = "SELECT COALESCE(SUM(points), 0) AS total
                  FROM {local_gamify_xp_events}
                 WHERE userid = :userid AND courseid = :courseid";
        $sum = $DB->get_field_sql($sql, ['userid' => $userid, 'courseid' => $courseid]);

        $total = (int) $sum;
        $now = time();

        // Check if total exists
        $record = $DB->get_record('local_gamify_xp', [
            'userid' => $userid,
            'courseid' => $courseid
        ], '*', IGNORE_MISSING);

        if ($record) {
            $record->totalxp = $total;
            $record->updatedat = $now;
            $DB->update_record('local_gamify_xp', $record);
        } else {
            $record = new \stdClass();
            $record->userid = $userid;
            $record->courseid = $courseid;
            $record->totalxp = $total;
            $record->updatedat = $now;
            $DB->insert_record('local_gamify_xp', $record);
        }

        return $total;
    }
        /**
     * Recalculate totals for all users and all courses.
     */
    public function recalc_all_totals(): int {
        global $DB;

        $rows = $DB->get_records_sql(
            "SELECT DISTINCT userid, courseid FROM {local_gamify_xp_events}"
        );

        $count = 0;
        foreach ($rows as $r) {
            $this->recalc_total_for_user_course((int)$r->userid, (int)$r->courseid);
            $count++;
        }

        return $count;
    }
        public function get_user_total_xp(int $userid, int $courseid): int {
        global $DB;

        $record = $DB->get_record('local_gamify_xp', [
            'userid' => $userid,
            'courseid' => $courseid
        ]);

        return $record ? (int) $record->totalxp : 0;
    }
        public function get_level_info_from_xp(int $xp): array {
        $levels = [
            1 => 0,
            2 => 100,
            3 => 250,
            4 => 500,
            5 => 900,
            6 => 1400,
        ];

        $current = 1;
        $nextmin = 0;
        foreach ($levels as $lvl => $req) {
            if ($xp >= $req) {
                $current = $lvl;
                $nextmin = $req;
            } else {
                break;
            }
        }

        // Determine next threshold
        $thresholds = array_values($levels);
        $nextthreshold = null;
        foreach ($thresholds as $t) {
            if ($t > $nextmin) {
                $nextthreshold = $t;
                break;
            }
        }

        $nextmax = $nextthreshold ?? ($nextmin + 500);

        $progress = ($nextmax > $nextmin)
            ? min(1.0, ($xp - $nextmin) / ($nextmax - $nextmin))
            : 1.0;

        return [
            'level' => $current,
            'min' => $nextmin,
            'max' => $nextmax,
            'progress' => $progress
        ];
    }
        public function get_user_xp_and_level(int $userid, int $courseid): array {
        $xp = $this->get_user_total_xp($userid, $courseid);
        $level = $this->get_level_info_from_xp($xp);

        return [
            'xp'       => $xp,
            'level'    => $level['level'],
            'min'      => $level['min'],
            'max'      => $level['max'],
            'progress' => $level['progress'],
        ];
    }

}
