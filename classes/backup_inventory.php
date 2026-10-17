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
 * Capture course file annotations without archiving files.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray;

use backup;
use backup_controller;
use backup_controller_dbops;
use backup_final_task;
use backup_helper;
use coding_exception;
use context_course;
use Throwable;

/**
 * Captures the IDs selected by Moodle backup without files.xml copy or zip steps.
 *
 * This relies on the Moodle 4.5 backup final task order. Explicitly fails if
 * the expected migration and fileslist steps are absent or reordered.
 */
final class backup_inventory {
    /**
     * Inventory a single course using the actual Moodle backup plan.
     *
     * @param int $reportid Report ID.
     * @param int $reportcourseid Snapshot row ID.
     * @param int $courseid Moodle course ID.
     * @param int $userid Backup operator.
     * @return int Number of referenced file records.
     */
    public static function inspect(int $reportid, int $reportcourseid, int $courseid, int $userid): int {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        context_course::instance($courseid, MUST_EXIST);
        $controller = null;
        $backupid = null;
        try {
            // MODE_IMPORT would exclude files and produce an incomplete inventory.
            $controller = new backup_controller(
                backup::TYPE_1COURSE, $courseid, backup::FORMAT_MOODLE,
                backup::INTERACTIVE_NO, backup::MODE_GENERAL, $userid
            );
            $backupid = $controller->get_backupid();
            $plan = $controller->get_plan();
            backup_preferences::apply($plan);
            if (!$plan->setting_exists('files') || !$plan->get_setting('files')->get_value()) {
                throw new coding_exception('Course backup does not permit file inclusion.');
            }

            // Native backup plugins select their own file references here. The
            // supplementary course/module context pass runs separately in the worker.
            // set_status() persists the controller and reopens file loggers after
            // serialisation. An explicit save_controller() beforehand would close
            // file_logger::$fhandle and break the very next backup log write.
            $controller->set_status(backup::STATUS_EXECUTING);
            $foundfinal = false;
            foreach ($plan->get_tasks() as $task) {
                $task->build();
                if (!$task instanceof backup_final_task) {
                    $task->execute();
                    continue;
                }

                // All preceding final steps can annotate files (including question files).
                // We stop BEFORE fileslist, which copies physical content.
                $migrated = false;
                foreach ($task->get_steps() as $step) {
                    if ($step->get_name() === 'fileslist') {
                        $foundfinal = $migrated;
                        break;
                    }
                    $step->execute();
                    if ($step->get_name() === 'migrate_inforef') {
                        $migrated = true;
                    }
                }
                break;
            }
            if (!$foundfinal) {
                throw new coding_exception('Unexpected native backup step order. Refusing a partial inventory.');
            }

            // Filefinal is exactly the set selected for files.xml by Moodle.
            $sql = "SELECT DISTINCT f.id AS fileid, f.contenthash, f.filesize, f.contextid,
                           f.component, f.filearea, f.filename
                      FROM {files} f
                      JOIN {backup_ids_temp} bi ON bi.itemid = f.id
                     WHERE bi.backupid = :backupid AND bi.itemname = :itemname";
            $rs = $DB->get_recordset_sql($sql, ['backupid' => $backupid, 'itemname' => 'filefinal']);
            $count = 0;
            try {
                foreach ($rs as $file) {
                    $DB->insert_record('local_filexray_file', (object) [
                        'reportid' => $reportid,
                        'reportcourseid' => $reportcourseid,
                        'courseid' => $courseid,
                        'fileid' => $file->fileid,
                        'contenthash' => $file->contenthash,
                        'filesize' => $file->filesize,
                        'contextid' => $file->contextid,
                        'component' => $file->component,
                        'filearea' => $file->filearea,
                        'filename' => $file->filename,
                    ], false);
                    $count++;
                }
            } finally {
                $rs->close();
            }
            return $count;
        } finally {
            // Normal backup cleanup is intentionally skipped along with fileslist.
            if ($backupid !== null) {
                try {
                    backup_controller_dbops::drop_backup_ids_temp_table($backupid);
                } catch (Throwable $ignored) {
                    debugging('File X-Ray could not remove temporary backup IDs.', DEBUG_DEVELOPER);
                }
                try {
                    backup_helper::delete_backup_dir($backupid);
                } catch (Throwable $ignored) {
                    debugging('File X-Ray could not remove temporary backup directory.', DEBUG_DEVELOPER);
                }
                $DB->delete_records('backup_controllers', ['backupid' => $backupid]);
            }
            if ($controller !== null) {
                $controller->destroy();
            }
        }
    }
}
