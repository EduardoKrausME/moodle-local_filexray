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
 * Native backup annotation tests without an MBZ.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray;

use advanced_testcase;
use context_course;

/**
 * Ensures that saving a native backup controller does not close its logger.
 *
 * @covers \local_filexray\backup_inventory
 */
final class backup_inventory_test extends advanced_testcase {
    /**
     * Course file annotations must be collected without a logger write failure.
     */
    public function test_inspect_collects_course_summary_file(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $storedfile = get_file_storage()->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'course',
            'filearea' => 'summary',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'filexray.txt',
        ], 'File X-Ray test content');

        $reportid = report_manager::create((int) get_admin()->id);
        $snapshot = $DB->get_record('local_filexray_course',
            ['reportid' => $reportid, 'courseid' => $course->id], '*', MUST_EXIST);

        $count = backup_inventory::inspect($reportid, (int) $snapshot->id,
            (int) $course->id, (int) get_admin()->id);

        // The summary file is preloaded at report creation; backup must not duplicate it.
        $this->assertIsInt($count);
        $this->assertSame(1, $DB->count_records("local_filexray_file", [
            "reportcourseid" => $snapshot->id, "fileid" => $storedfile->get_id(),
        ]));
        $this->assertTrue($DB->record_exists('local_filexray_file', [
            'reportcourseid' => $snapshot->id,
            'fileid' => $storedfile->get_id(),
        ]));
    }
    /**
     * A course-level file must still appear with student data disabled.
     */
    public function test_inspect_keeps_course_files_without_user_data(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        set_config("skipuserdata", 1, "local_filexray");
        set_config("backup_general_users", 1, "backup");
        set_config("backup_general_users_locked", 0, "backup");
        $course = $this->getDataGenerator()->create_course();
        $storedfile = get_file_storage()->create_file_from_string([
            "contextid" => context_course::instance($course->id)->id,
            "component" => "course",
            "filearea" => "summary",
            "itemid" => 0,
            "filepath" => "/",
            "filename" => "summary-fast-mode.txt",
        ], "Course summary remains in the inventory");

        $reportid = report_manager::create((int) get_admin()->id);
        $snapshot = $DB->get_record("local_filexray_course",
            ["reportid" => $reportid, "courseid" => $course->id], "*", MUST_EXIST);
        backup_inventory::inspect($reportid, (int) $snapshot->id,
            (int) $course->id, (int) get_admin()->id);
        $this->assertTrue($DB->record_exists("local_filexray_file", [
            "reportcourseid" => $snapshot->id,
            "fileid" => $storedfile->get_id(),
        ]));
    }
}
