<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Manage persisted reports and progress.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray;

defined('MOODLE_INTERNAL') || die();

/**
 * Persistent report snapshot and AJAX progress.
 */
final class report_manager {
    /**
     * Create the immutable course list before scheduling the first task.
     *
     * @param int $userid Administrator user ID.
     * @return int
     */
    public static function create(int $userid): int {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $now = time();
        $report = (object) [
            'userid' => $userid, 'status' => 'queued',
            'totalcourses' => 0, 'timecreated' => $now,
            'timestarted' => 0, 'timefinished' => 0, 'timemodified' => $now,
        ];
        $id = (int) $DB->insert_record('local_filexray_report', $report);
        $count = 0;
        $rs = $DB->get_recordset_select('course', 'id <> :siteid',
            ['siteid' => SITEID], 'id ASC', 'id, fullname');
        try {
            foreach ($rs as $course) {
                $DB->insert_record('local_filexray_course', (object) [
                    'reportid' => $id, 'courseid' => $course->id,
                    'coursename' => $course->fullname, 'status' => 'pending',
                    'filecount' => 0, 'timestarted' => 0,
                    'timefinished' => 0, 'errormessage' => null,
                ], false);
                $count++;
            }
        } finally {
            $rs->close();
        }
        $DB->update_record('local_filexray_report', (object) [
            'id' => $id, 'totalcourses' => $count,
            'status' => $count ? 'queued' : 'completed',
            'timefinished' => $count ? 0 : $now,
        ]);
        $transaction->allow_commit();
        if ($count) {
            runner::enqueue($id);
        }
        return $id;
    }

    /**
     * @param int $reportid Report ID.
     * @return \stdClass
     */
    public static function get(int $reportid): \stdClass {
        global $DB;
        return $DB->get_record('local_filexray_report', ['id' => $reportid], '*', MUST_EXIST);
    }

    /**
     * @param int $reportid Report ID.
     * @param int $offset Offset.
     * @param int $limit Page size.
     * @return array
     */
    public static function status(int $reportid, int $offset, int $limit): array {
        global $DB;
        $report = self::get($reportid);
        $counts = [];
        foreach (['pending', 'running', 'completed', 'failed'] as $state) {
            $counts[$state] = $DB->count_records('local_filexray_course',
                ['reportid' => $reportid, 'status' => $state]);
        }
        $files = (int) $DB->get_field_sql(
            'SELECT COALESCE(SUM(filecount), 0) FROM {local_filexray_course} WHERE reportid = :reportid',
            ['reportid' => $reportid]
        );
        $rs = $DB->get_records('local_filexray_course', ['reportid' => $reportid],
            'courseid ASC', 'id, courseid, coursename, status, filecount, timestarted, timefinished, errormessage',
            $offset, $limit);
        $courses = [];
        foreach ($rs as $row) {
            $courses[] = [
                'id' => (int) $row->id, 'courseid' => (int) $row->courseid,
                'name' => $row->coursename, 'status' => $row->status,
                'filecount' => (int) $row->filecount, 'error' => (string) ($row->errormessage ?? ''),
                'started' => (int) $row->timestarted, 'finished' => (int) $row->timefinished,
            ];
        }
        $processed = $counts['completed'] + $counts['failed'];
        return [
            'reportid' => $reportid, 'status' => $report->status,
            'total' => (int) $report->totalcourses,
            'completed' => $counts['completed'], 'failed' => $counts['failed'],
            'running' => $counts['running'], 'pending' => $counts['pending'],
            'filecount' => $files, 'percent' => $report->totalcourses
                ? (int) floor(100 * $processed / $report->totalcourses) : 100,
            'offset' => $offset, 'limit' => $limit, 'courses' => $courses,
        ];
    }

    /**
     * Mark the snapshot complete only when there are no pending or running courses.
     *
     * @param int $reportid Report ID.
     * @return void
     */
    public static function refresh(int $reportid): void {
        global $DB;
        $report = self::get($reportid);
        $pending = $DB->count_records_select('local_filexray_course',
            'reportid = :rid AND status IN (:pending, :running)',
            ['rid' => $reportid, 'pending' => 'pending', 'running' => 'running']);
        $failed = $DB->count_records('local_filexray_course',
            ['reportid' => $reportid, 'status' => 'failed']);
        $report->status = $pending ? 'running' : ($failed ? 'partial' : 'completed');
        $report->timemodified = time();
        if (!$pending) {
            $report->timefinished = time();
        }
        $DB->update_record('local_filexray_report', $report);
    }
}