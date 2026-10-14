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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Tests for File X-Ray comparison summary data.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray;

defined('MOODLE_INTERNAL') || die();

/**
 * Verify file counts and file-area attribution.
 *
 * @covers \local_filexray\comparison_summary
 */
final class comparison_summary_test extends \advanced_testcase {
    /**
     * The course view sums only inventoried non-directory records.
     */
    public function test_course_totals_ignore_directory_metadata(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $reportid = report_manager::create((int) get_admin()->id);
        $coursejob = $DB->get_record('local_filexray_course', [
            'reportid' => $reportid,
            'courseid' => $course->id,
        ], '*', MUST_EXIST);
        foreach ([
            ['fileid' => 90000001, 'filename' => 'document.pdf', 'filesize' => 2048],
            ['fileid' => 90000002, 'filename' => '.', 'filesize' => 0],
        ] as $file) {
            $DB->insert_record('local_filexray_file', (object) [
                'reportid' => $reportid,
                'reportcourseid' => $coursejob->id,
                'courseid' => $course->id,
                'fileid' => $file['fileid'],
                'contenthash' => str_repeat('a', 40),
                'filesize' => $file['filesize'],
                'contextid' => \context_course::instance($course->id)->id,
                'component' => 'mod_resource',
                'filearea' => 'content',
                'filename' => $file['filename'],
            ]);
        }
        $result = comparison_summary::courses($reportid, 0, 50);
        $courseentry = null;
        foreach ($result as $row) {
            if ($row['courseid'] === (int) $course->id) {
                $courseentry = $row;
                break;
            }
        }
        $this->assertNotNull($courseentry);
        $this->assertSame(1, $courseentry['filecount']);
        $this->assertSame(2048, $courseentry['bytecount']);
    }

    /**
     * User drafts include exactly the logical bytes in mdl_files.
     */
    public function test_file_area_summary_counts_user_draft_and_profile_areas(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $storage = get_file_storage();
        $storage->create_file_from_string([
            'contextid' => \context_user::instance($user->id)->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => 1,
            'filepath' => '/',
            'filename' => 'draft-summary-test.txt',
            'userid' => $user->id,
        ], '12345');

        $reportid = report_manager::create((int) get_admin()->id);
        $areas = comparison_summary::fileareas($reportid);
        $draft = null;
        $icon = null;
        foreach ($areas as $area) {
            if ($area['component'] === 'user' && $area['filearea'] === 'draft') {
                $draft = $area;
            }
            if ($area['component'] === 'user' && $area['filearea'] === 'icon') {
                $icon = $area;
            }
        }

        $expected = (int) $DB->get_field_sql(
            "SELECT COALESCE(SUM(filesize), 0)
               FROM {files}
              WHERE component = :component AND filearea = :area",
            ['component' => 'user', 'area' => 'draft']
        );
        $this->assertNotNull($draft);
        $this->assertNotNull($icon);
        $this->assertGreaterThanOrEqual(5, $expected);
        $this->assertSame($expected, $draft['allbytes']);
        $this->assertSame($expected, $draft['outsidebytes']);
    }
}
