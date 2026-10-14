<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Coordinates one inventory task per course.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray;

defined('MOODLE_INTERNAL') || die();

use core\task\manager;
use local_filexray\task\process_course;
use Throwable;

/**
 * Manages per-report exclusivity and cron hand-off.
 */
final class runner {
    /**
     * @param int $reportid Report ID.
     * @return void
     */
    public static function enqueue(int $reportid): void {
        $task = new process_course();
        $task->set_custom_data(['reportid' => $reportid]);
        manager::queue_adhoc_task($task);
    }

    /**
     * Process one course and queue the next one, even if the browser is closed.
     *
     * @param int $reportid Report ID.
     * @return void
     */
    public static function one(int $reportid): void {
        global $DB;

        $factory = \core\lock\lock_config::get_lock_factory('local_filexray');
        $lock = $factory->get_lock('report_' . $reportid, 0);
        if (!$lock) {
            return;
        }
        try {
            // An already queued cron task may run after the administrator deleted its report.
            $report = $DB->get_record('local_filexray_report', ['id' => $reportid]);
            if (!$report) {
                return;
            }

            // A task can be terminated by a process crash without recording a failure.
            // Only the worker holding this exclusive lock can reclaim an old attempt.
            $cutoff = time() - 7200;
            $stale = $DB->get_records_select('local_filexray_course',
                'reportid = :rid AND status = :state AND timestarted < :cutoff',
                ['rid' => $reportid, 'state' => 'running', 'cutoff' => $cutoff]);
            foreach ($stale as $old) {
                $DB->delete_records('local_filexray_file', ['reportcourseid' => $old->id]);
                $DB->update_record('local_filexray_course', (object) [
                    'id' => $old->id, 'status' => 'pending',
                    'filecount' => 0, 'timestarted' => 0, 'timefinished' => 0,
                    'errormessage' => null,
                ]);
            }

            $pending = $DB->get_records('local_filexray_course',
                ['reportid' => $reportid, 'status' => 'pending'], 'courseid ASC', '*', 0, 1);
            $row = $pending ? reset($pending) : null;
            if (!$row) {
                report_manager::refresh($reportid);
                return;
            }

            $now = time();
            $DB->update_record('local_filexray_course', (object) [
                'id' => $row->id, 'status' => 'running', 'timestarted' => $now,
                'timefinished' => 0, 'errormessage' => null,
            ]);
            $DB->set_field('local_filexray_report', 'status', 'running', ['id' => $reportid]);
            if (empty($report->timestarted)) {
                $DB->set_field('local_filexray_report', 'timestarted', $now, ['id' => $reportid]);
            }

            $DB->delete_records('local_filexray_file', ['reportcourseid' => $row->id]);
            try {
                $count = backup_inventory::inspect($reportid, (int) $row->id,
                    (int) $row->courseid, (int) $report->userid);
                $DB->update_record('local_filexray_course', (object) [
                    'id' => $row->id, 'status' => 'completed',
                    'filecount' => $count, 'timefinished' => time(), 'errormessage' => null,
                ]);
            } catch (Throwable $e) {
                $DB->delete_records('local_filexray_file', ['reportcourseid' => $row->id]);
                $DB->update_record('local_filexray_course', (object) [
                    'id' => $row->id, 'status' => 'failed',
                    'filecount' => 0, 'timefinished' => time(),
                    'errormessage' => \core_text::substr($e->getMessage(), 0, 2000),
                ]);
                debugging('File X-Ray inventory failed for course ' . $row->courseid . ': ' .
                    $e->getMessage(), DEBUG_DEVELOPER);
            }
            report_manager::refresh($reportid);
            if ($DB->record_exists('local_filexray_course',
                    ['reportid' => $reportid, 'status' => 'pending'])) {
                self::enqueue($reportid);
            }
        } finally {
            $lock->release();
        }
    }
}
