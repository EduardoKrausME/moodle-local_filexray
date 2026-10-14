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
 * Tests for course name normalisation in report snapshots.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray;

defined('MOODLE_INTERNAL') || die();

/**
 * File X-Ray report creation tests.
 *
 * @covers \local_filexray\report_manager
 */
final class report_manager_test extends \advanced_testcase {
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
}
