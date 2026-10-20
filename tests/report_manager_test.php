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
 * Tests for course name normalisation in report snapshots.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray;

use advanced_testcase;
use context_course;

/**
 * File X-Ray report creation tests.
 *
 * @covers \local_filexray\report_manager
 */
final class report_manager_test extends advanced_testcase {
    /**
     * Course full names with multilang markup must be stored as plain text.
     */
    public function test_create_report_removes_course_name_html(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $fullname = '<span lang="pt_br" class="multilang">Governança de Dados</span>' .
            '<span lang="en" class="multilang">Data Governance</span>';
        $DB->set_field('course', 'fullname', $fullname, ['id' => $course->id]);

        $reportid = report_manager::create((int) get_admin()->id);
        $snapshot = $DB->get_record('local_filexray_course',
            ['reportid' => $reportid, 'courseid' => $course->id], '*', MUST_EXIST);

        $this->assertNotEmpty($snapshot->coursename);
        $this->assertStringNotContainsString('<', $snapshot->coursename);
        $this->assertStringNotContainsString('>', $snapshot->coursename);
        $this->assertNotSame($fullname, $snapshot->coursename);
    }
    /**
     * The explicit resume action must permit retrying failed jobs in the same report.
     */
    public function test_retry_failed_course(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $reportid = report_manager::create((int) get_admin()->id);
        $row = $DB->get_record('local_filexray_course', [
            'reportid' => $reportid, 'courseid' => $course->id,
        ], '*', MUST_EXIST);
        $DB->update_record('local_filexray_course', (object) [
            'id' => $row->id, 'status' => 'failed', 'errormessage' => 'error/error_writing_file',
        ]);

        $this->assertSame(1, report_manager::retry_failed($reportid));
        $updated = $DB->get_record('local_filexray_course', ['id' => $row->id], '*', MUST_EXIST);
        $this->assertSame('pending', $updated->status);
        $this->assertEmpty($updated->errormessage);
        $this->assertSame(0, report_manager::retry_failed($reportid));
    }
    /**
     * Deleting a report removes only File X-Ray inventory records.
     */
    public function test_delete_report_removes_its_rows_without_touching_moodle_files(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $reportid = report_manager::create((int) get_admin()->id);
        $row = $DB->get_record('local_filexray_course',
            ['reportid' => $reportid, 'courseid' => $course->id], '*', MUST_EXIST);

        $DB->insert_record('local_filexray_file', (object) [
            'reportid' => $reportid,
            'reportcourseid' => $row->id,
            'courseid' => $course->id,
            'fileid' => 123456789,
            'contenthash' => str_repeat('a', 40),
            'filesize' => 100,
            'contextid' => context_course::instance($course->id)->id,
            'component' => 'mod_resource',
            'filearea' => 'content',
            'filename' => 'sample.pdf',
        ]);
        $filesbefore = $DB->count_records('files');

        report_manager::delete($reportid);

        $this->assertFalse($DB->record_exists('local_filexray_report', ['id' => $reportid]));
        $this->assertEquals(0, $DB->count_records('local_filexray_course', ['reportid' => $reportid]));
        $this->assertEquals(0, $DB->count_records('local_filexray_file', ['reportid' => $reportid]));
        $this->assertEquals($filesbefore, $DB->count_records('files'));
    }

    /**
     * Report progress includes the sum of inventoried file bytes per course.
     *
     * The response must preserve pagination and use a human-readable Moodle size.
     */
    public function test_status_includes_file_bytes_per_course(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $first = $this->getDataGenerator()->create_course();
        $second = $this->getDataGenerator()->create_course();
        $reportid = report_manager::create((int) get_admin()->id);
        $firstjob = $DB->get_record("local_filexray_course",
            ["reportid" => $reportid, "courseid" => $first->id], "*", MUST_EXIST);
        $secondjob = $DB->get_record("local_filexray_course",
            ["reportid" => $reportid, "courseid" => $second->id], "*", MUST_EXIST);

        foreach ([
            [$firstjob, 91000001, 1024],
            [$firstjob, 91000002, 2048],
            [$secondjob, 91000003, 8192],
        ] as [$job, $fileid, $filesize]) {
            $DB->insert_record("local_filexray_file", (object) [
                "reportid" => $reportid,
                "reportcourseid" => $job->id,
                "courseid" => $job->courseid,
                "fileid" => $fileid,
                "contenthash" => str_repeat("a", 40),
                "filesize" => $filesize,
                "contextid" => context_course::instance($job->courseid)->id,
                "component" => "mod_resource",
                "filearea" => "content",
                "filename" => "status-test-$fileid.txt",
            ]);
        }

        $status = report_manager::status($reportid, 0, 100);
        $courses = array_column($status["courses"], null, "courseid");
        $this->assertArrayHasKey($first->id, $courses);
        $this->assertArrayHasKey($second->id, $courses);

        foreach ([$firstjob, $secondjob] as $job) {
            $expected = (int) $DB->get_field_sql(
                "SELECT COALESCE(SUM(filesize), 0)
                   FROM {local_filexray_file} WHERE reportcourseid = :coursejob",
                ["coursejob" => $job->id]
            );
            $this->assertSame($expected, $courses[$job->courseid]["bytecount"]);
            $this->assertSame(display_size($expected), $courses[$job->courseid]["size"]);
            $this->assertSame(
                number_format($courses[$job->courseid]["filecount"], 0, ",", "."),
                $courses[$job->courseid]["formattedfilecount"]
            );
        }

        $page = report_manager::status($reportid, 0, 1);
        $this->assertCount(1, $page["courses"]);
        $this->assertArrayHasKey("formattedfilecount", $page["courses"][0]);
        $this->assertArrayHasKey("bytecount", $page["courses"][0]);
        $this->assertArrayHasKey("size", $page["courses"][0]);
    }

    /**
     * Creating a new report at the retention limit evicts only the oldest.
     */
    public function test_create_report_respects_maximum_retention(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $this->getDataGenerator()->create_course();
        set_config('maxreports', 2, 'local_filexray');

        $first = report_manager::create((int) get_admin()->id);
        $second = report_manager::create((int) get_admin()->id);
        $third = report_manager::create((int) get_admin()->id);

        $this->assertEquals(2, $DB->count_records('local_filexray_report'));
        $this->assertFalse($DB->record_exists('local_filexray_report', ['id' => $first]));
        $this->assertTrue($DB->record_exists('local_filexray_report', ['id' => $second]));
        $this->assertTrue($DB->record_exists('local_filexray_report', ['id' => $third]));
        $this->assertEquals(0, $DB->count_records('local_filexray_course', ['reportid' => $first]));
        $this->assertEquals(0, $DB->count_records('local_filexray_file', ['reportid' => $first]));
    }

}
