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
 * Tests for securely previewing files in File X-Ray course reports.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray;

/**
 * Preview classification and short-lived signed file verification.
 *
 * @covers \local_filexray\file_preview
 */
final class file_preview_test extends \advanced_testcase {
    /**
     * Browser-safe files are inline, while scriptable formats must download.
     */
    public function test_preview_kind_checks_extensions(): void {
        $this->assertSame('pdf', file_preview::kind('SYLLABUS.PDF'));
        $this->assertSame('image', file_preview::kind('graphic.png'));
        $this->assertSame('image', file_preview::kind('picture.webp'));
        $this->assertSame('media', file_preview::kind('lecture.mp4'));
        $this->assertSame('office', file_preview::kind('gradebook.XLSX'));
        $this->assertSame('office', file_preview::kind('presentation.pptx'));
        $this->assertSame('download', file_preview::kind('untrusted.html'));
        $this->assertSame('download', file_preview::kind('script.svg'));
        $this->assertSame('download', file_preview::kind('.'));
    }

    /**
     * Signed Google preview tokens are bound to one report, course, file and
     * content hash and stop working after expiry or if their HMAC is altered.
     */
    public function test_office_token_rejects_tampering_and_mismatched_snapshots(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('previewsecret', bin2hex(random_bytes(32)), 'local_filexray');

        $course = $this->getDataGenerator()->create_course();
        $reportid = report_manager::create((int) get_admin()->id);
        $job = $DB->get_record('local_filexray_course', [
            'reportid' => $reportid, 'courseid' => $course->id,
        ], '*', MUST_EXIST);
        $file = get_file_storage()->create_file_from_string([
            'contextid' => \context_course::instance($course->id)->id,
            'component' => 'mod_resource',
            'filearea' => 'content',
            'itemid' => 42,
            'filepath' => '/',
            'filename' => 'example.docx',
        ], 'Office example');
        $snapshotid = $DB->insert_record('local_filexray_file', (object) [
            'reportid' => $reportid,
            'reportcourseid' => $job->id,
            'courseid' => $course->id,
            'fileid' => $file->get_id(),
            'contenthash' => $file->get_contenthash(),
            'filesize' => $file->get_filesize(),
            'contextid' => $file->get_contextid(),
            'component' => $file->get_component(),
            'filearea' => $file->get_filearea(),
            'filename' => $file->get_filename(),
        ]);
        // A directory placeholder is excluded from the same query as course.php.
        $DB->insert_record('local_filexray_file', (object) [
            'reportid' => $reportid,
            'reportcourseid' => $job->id,
            'courseid' => $course->id,
            'fileid' => $file->get_id() + 1000000,
            'contenthash' => str_repeat('0', 40),
            'filesize' => 0,
            'contextid' => $file->get_contextid(),
            'component' => $file->get_component(),
            'filearea' => $file->get_filearea(),
            'filename' => '.',
        ]);
        $realcount = $DB->count_records_select('local_filexray_file',
            'reportcourseid = :jobid AND filename <> :directory', [
                'jobid' => $job->id, 'directory' => '.',
            ]);
        $this->assertSame(1, $realcount);

        $url = file_preview::office_url($reportid, (int) $job->id, (int) $file->get_id());
        $params = $url->params();
        $resolved = file_preview::verify_office(
            (int) $params['id'], (int) $params['coursejobid'], (int) $params['fileid'],
            (int) $params['expires'], $params['nonce'], $params['sig']
        );
        $this->assertSame($file->get_id(), $resolved->get_id());

        try {
            file_preview::verify_office(
                (int) $params['id'], (int) $params['coursejobid'], (int) $params['fileid'],
                (int) $params['expires'], $params['nonce'], str_repeat('f', 64)
            );
            $this->fail('Modified HMAC should be rejected.');
        } catch (\moodle_exception $expected) {
            $this->assertSame('expiredpreview', $expected->errorcode);
        }

        $DB->set_field('local_filexray_file', 'contenthash', str_repeat('f', 40),
            ['id' => $snapshotid]);
        $this->expectException(\moodle_exception::class);
        file_preview::resolve($reportid, (int) $job->id, (int) $file->get_id());
    }
}
