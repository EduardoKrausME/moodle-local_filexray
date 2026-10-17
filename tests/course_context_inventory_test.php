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
 * Tests for supplementary course and activity context inventories.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray;

use advanced_testcase;
use context_course;
use context_module;
use context_user;

/**
 * Verify complete context coverage and idempotency of file ID insertion.
 *
 * @covers \local_filexray\course_context_inventory
 * @covers \local_filexray\runner
 */
final class course_context_inventory_test extends advanced_testcase {
    /**
     * Existing backup references are retained, but file IDs outside the current course are excluded.
     */
    public function test_append_finds_course_and_module_contexts_without_duplicates(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module("page", ["course" => $course->id]);
        $othermodule = $this->getDataGenerator()->create_module("page", ["course" => $othercourse->id]);
        $user = $this->getDataGenerator()->create_user();
        $storage = get_file_storage();

        $files = [];
        $contexts = [
            "course" => [context_course::instance($course->id)->id, "course", "filexray_extra"],
            "module" => [context_module::instance($module->cmid)->id, "mod_page", "filexray_extra"],
            "other" => [context_module::instance($othermodule->cmid)->id, "mod_page", "filexray_extra"],
            "user" => [context_user::instance($user->id)->id, "user", "draft"],
        ];
        foreach ($contexts as $name => [$contextid, $component, $area]) {
            $files[$name] = $storage->create_file_from_string([
                "contextid" => $contextid,
                "component" => $component,
                "filearea" => $area,
                "itemid" => 0,
                "filepath" => "/",
                "filename" => "context-{$name}.txt",
            ], "File X-Ray {$name}");
        }

        $reportid = report_manager::create((int) get_admin()->id);
        $job = $DB->get_record("local_filexray_course", [
            "reportid" => $reportid, "courseid" => $course->id,
        ], "*", MUST_EXIST);

        // File IDs for both the course and its module are already stored during create().
        foreach (["course", "module"] as $name) {
            $this->assertTrue($DB->record_exists("local_filexray_file", [
                "reportcourseid" => $job->id, "fileid" => $files[$name]->get_id(),
            ]), $name);
        }
        $this->assertSame(2, (int) $DB->get_field("local_filexray_course", "filecount", ["id" => $job->id]));

        // A new file created after the report must be picked up only by the append pass.
        $newfile = $storage->create_file_from_string([
            "contextid" => context_module::instance($module->cmid)->id,
            "component" => "mod_page",
            "filearea" => "filexray_extra",
            "itemid" => 0,
            "filepath" => "/",
            "filename" => "new-file-after-report.txt",
        ], "New file after creation");
        $before = $DB->count_records("local_filexray_file", ["reportcourseid" => $job->id]);
        $added = course_context_inventory::append($reportid, (int) $job->id, (int) $course->id);
        $this->assertSame(1, $added);
        $this->assertTrue($DB->record_exists("local_filexray_file", [
            "reportcourseid" => $job->id, "fileid" => $newfile->get_id(),
        ]));
        $this->assertSame($before + $added,
            $DB->count_records("local_filexray_file", ["reportcourseid" => $job->id]));

        foreach (["course", "module"] as $name) {
            $this->assertSame(1, $DB->count_records("local_filexray_file", [
                "reportcourseid" => $job->id, "fileid" => $files[$name]->get_id(),
            ]), $name);
        }
        foreach (["other", "user"] as $name) {
            $this->assertFalse($DB->record_exists("local_filexray_file", [
                "reportcourseid" => $job->id, "fileid" => $files[$name]->get_id(),
            ]), $name);
        }
        $this->assertSame(0, course_context_inventory::append($reportid, (int) $job->id, (int) $course->id));
    }

    /**
     * A newly queued report automatically captures a module area not selected by native backup.
     */
    public function test_report_worker_adds_module_files_when_user_data_is_skipped(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        set_config("skipuserdata", 1, "local_filexray");
        set_config("backup_general_users", 1, "backup");
        set_config("backup_general_users_locked", 0, "backup");

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module("page", ["course" => $course->id]);
        $file = get_file_storage()->create_file_from_string([
            "contextid" => context_module::instance($module->cmid)->id,
            "component" => "mod_page",
            "filearea" => "filexray_not_backed_up",
            "itemid" => 0,
            "filepath" => "/",
            "filename" => "not-selected-by-backup.txt",
        ], "Unselected file from an activity context");

        $reportid = report_manager::create((int) get_admin()->id);
        $job = $DB->get_record("local_filexray_course", [
            "reportid" => $reportid, "courseid" => $course->id,
        ], "*", MUST_EXIST);
        $this->assertSame("pending", $job->status);
        $this->assertTrue($DB->record_exists("local_filexray_file", [
            "reportcourseid" => $job->id, "fileid" => $file->get_id(),
        ]));
        $this->assertSame($DB->count_records("local_filexray_file", ["reportcourseid" => $job->id]),
            (int) $job->filecount);

        runner::one($reportid);

        $job = $DB->get_record("local_filexray_course", [
            "reportid" => $reportid, "courseid" => $course->id,
        ], "*", MUST_EXIST);
        $this->assertSame("completed", $job->status, (string) $job->errormessage);
        $this->assertTrue($DB->record_exists("local_filexray_file", [
            "reportcourseid" => $job->id, "fileid" => $file->get_id(),
        ]));
        $this->assertSame((int) $job->filecount,
            $DB->count_records("local_filexray_file", ["reportcourseid" => $job->id]));
    }
}
