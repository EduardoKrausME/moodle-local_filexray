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
 * Tests for File X-Ray comparison summary data.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray;

use advanced_testcase;
use context_course;
use context_system;
use context_user;

/**
 * Verify file counts and file-area attribution.
 *
 * @covers \local_filexray\comparison_summary
 */
final class comparison_summary_test extends advanced_testcase {
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
                'contextid' => context_course::instance($course->id)->id,
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
     * Only recognised multilang span markup may be rendered unescaped.
     */
    public function test_multilang_course_names_are_safely_rendered(): void {
        $original = '<span lang="pt_br" class="multilang">Políticas Públicas</span>' .
            '<span lang="es" class="multilang">Políticas Públicas y Diplomacia</span>' .
            '<span lang="en" class="multilang">Public Policy</span>';
        $rendered = comparison_summary::safe_course_name_html($original);
        $this->assertStringContainsString('<span class="multilang" lang="pt_br">Políticas Públicas</span>',
            $rendered);
        $this->assertStringContainsString('<span class="multilang" lang="en">Public Policy</span>', $rendered);
        $this->assertStringNotContainsString('&lt;span', $rendered);

        $unsafe = '<span class="multilang" lang="en" onclick="alert(1)">' .
            '<img src=x onerror=alert(2)>Unsafe &lt;script&gt;alert(3)&lt;/script&gt;</span>';
        $safe = comparison_summary::safe_course_name_html($unsafe);
        $this->assertStringNotContainsString('onclick=', $safe);
        $this->assertStringNotContainsString('<img', $safe);
        $this->assertStringNotContainsString('<script', $safe);
        $this->assertStringContainsString('<span class="multilang" lang="en">', $safe);
        $this->assertStringContainsString('Unsafe alert(3)', $safe);
    }

    /**
     * A saved report can still show current multilang course names.
     */
    public function test_course_summary_preserves_current_multilang_markup(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $reportid = report_manager::create((int) get_admin()->id);
        $DB->set_field('course', 'fullname',
            '<span lang="pt_br" class="multilang">Curso PT</span>' .
            '<span lang="en" class="multilang">Course EN</span>',
            ['id' => $course->id]);
        $courses = comparison_summary::courses($reportid, 0, 50);
        $found = false;
        foreach ($courses as $row) {
            if ($row['courseid'] === (int) $course->id) {
                $this->assertStringContainsString('<span class="multilang" lang="pt_br">Curso PT</span>',
                    $row['coursename']);
                $this->assertStringContainsString('<span class="multilang" lang="en">Course EN</span>',
                    $row['coursename']);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    /**
     * The page and the card count must use the same residual file predicate.
     */
    public function test_unmatched_condition_excludes_classified_areas(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $storage = get_file_storage();
        $contextid = context_system::instance()->id;
        $files = [];
        foreach (['user', 'mod_resource'] as $i => $component) {
            $files[$component] = $storage->create_file_from_string([
                'contextid' => $contextid,
                'component' => $component,
                'filearea' => $component === 'user' ? 'draft' : 'content',
                'itemid' => 300 + $i,
                'filepath' => '/',
                'filename' => 'residual-' . $i . '.txt',
            ], 'test-' . $i);
        }

        $reportid = report_manager::create((int) get_admin()->id);
        [$where, $params] = comparison_summary::unmatched_condition($reportid);
        foreach ($files as $component => $file) {
            $count = (int) $DB->count_records_sql(
                "SELECT COUNT(*) FROM {files} f WHERE f.id = :fileid AND " . $where,
                ['fileid' => $file->get_id()] + $params
            );
            $this->assertSame($component === 'mod_resource' ? 1 : 0, $count, $component);
        }
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
            'contextid' => context_user::instance($user->id)->id,
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
        // A category must be omitted when it contains no actual files.
        // Some Moodle fixtures may already contain a user icon.
        foreach ($areas as $area) {
            $this->assertGreaterThan(0, $area['allcount']);
        }
        if ($icon !== null) {
            $this->assertGreaterThan(0, $icon['allcount']);
        }
        $this->assertGreaterThanOrEqual(5, $expected);
        $this->assertSame($expected, $draft['allbytes']);
        $this->assertSame($expected, $draft['outsidebytes']);
    }
    /**
     * User areas and badge image totals match the exact requested filters.
     * Component totals must include all file areas, without counting files
     * selected in the inventory as outside.
     */
    public function test_selected_user_badge_backup_and_h5p_totals(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $reportid = report_manager::create((int) get_admin()->id);
        $job = $DB->get_record('local_filexray_course', [
            'reportid' => $reportid, 'courseid' => $course->id,
        ], '*', MUST_EXIST);
        $usercontext = context_user::instance($user->id)->id;
        $systemcontext = context_system::instance()->id;
        $storage = get_file_storage();
        $fixtures = [
            ['user', 'draft', $usercontext],
            ['user', 'backup', $usercontext],
            ['user', 'icon', $usercontext],
            ['badges', 'badgeimage', $systemcontext],
            ['backup', 'course', $systemcontext],
            ['backup', 'automated', $systemcontext],
            ['core_h5p', 'libraries', $systemcontext],
            ['core_h5p', 'cachedassets', $systemcontext],
        ];
        $created = [];
        foreach ($fixtures as $i => $fixture) {
            [$component, $area, $contextid] = $fixture;
            $created[$component . '/' . $area] = $storage->create_file_from_string([
                'contextid' => $contextid,
                'component' => $component,
                'filearea' => $area,
                'itemid' => $i + 1,
                'filepath' => '/',
                'filename' => 'comparison-' . $i . '.txt',
                'userid' => $user->id,
            ], str_repeat((string) $i, $i + 7));
        }

        // Selecting one H5P library from a backup excludes it from the
        // outside-inventory sum, but not the component's total bytes.
        $selected = $created['core_h5p/libraries'];
        $DB->insert_record('local_filexray_file', (object) [
            'reportid' => $reportid,
            'reportcourseid' => $job->id,
            'courseid' => $course->id,
            'fileid' => $selected->get_id(),
            'contenthash' => $selected->get_contenthash(),
            'filesize' => $selected->get_filesize(),
            'contextid' => $selected->get_contextid(),
            'component' => $selected->get_component(),
            'filearea' => $selected->get_filearea(),
            'filename' => $selected->get_filename(),
        ]);

        $rows = [];
        foreach (comparison_summary::fileareas($reportid) as $area) {
            // Consolidated components have a display area list, not the literal '*' key.
            $key = $area['component'] . '/' . ($area['iscomponent'] ? '*' : $area['filearea']);
            $rows[$key] = $area;
        }
        $filters = [
            'user/draft' => ['component' => 'user', 'filearea' => 'draft'],
            'user/backup' => ['component' => 'user', 'filearea' => 'backup'],
            'user/icon' => ['component' => 'user', 'filearea' => 'icon'],
            'badges/badgeimage' => ['component' => 'badges', 'filearea' => 'badgeimage'],
            'backup/*' => ['component' => 'backup'],
            'core_h5p/*' => ['component' => 'core_h5p'],
        ];
        foreach ($filters as $key => $filter) {
            $expected = (int) $DB->get_field_sql(
                'SELECT COALESCE(SUM(filesize), 0) FROM {files} WHERE ' .
                    implode(' AND ', array_map(static function($field): string {
                        return $field . ' = :' . $field;
                    }, array_keys($filter))),
                $filter
            );
            $this->assertArrayHasKey($key, $rows);
            $this->assertSame($expected, $rows[$key]['allbytes'], $key);
            $this->assertNotEmpty($rows[$key]['description']);
        }

        $this->assertTrue($rows['backup/*']['iscomponent']);
        $this->assertTrue($rows['core_h5p/*']['iscomponent']);
        $this->assertFalse($rows['user/backup']['iscomponent']);
        $this->assertSame(
            $rows['core_h5p/*']['allbytes'] - $selected->get_filesize(),
            $rows['core_h5p/*']['outsidebytes']
        );
        $this->assertSame(
            $rows['backup/course']['allbytes'] + $rows['backup/automated']['allbytes'],
            $rows['backup/*']['allbytes']
        );
    }

    /**
     * H5P has one consolidated row and all themes share a single row with
     * up to five area names, without losing any logical file bytes.
     */
    public function test_h5p_and_themes_are_consolidated(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $storage = get_file_storage();
        $systemcontext = context_system::instance()->id;
        $fileareas = ['javascript', 'css', 'icons', 'images', 'content', 'fonts'];
        $themebytes = 0;
        foreach ($fileareas as $i => $filearea) {
            $data = str_repeat('t', $i + 10);
            $themebytes += strlen($data);
            $storage->create_file_from_string([
                'contextid' => $systemcontext,
                'component' => ($i % 2 === 0) ? 'theme_learnlingo' : 'theme_degrade',
                'filearea' => $filearea,
                'itemid' => $i + 1,
                'filepath' => '/',
                'filename' => 'theme-test-' . $i . '.css',
            ], $data);
        }
        foreach (['libraries', 'export', 'content'] as $i => $area) {
            $storage->create_file_from_string([
                'contextid' => $systemcontext,
                'component' => 'core_h5p',
                'filearea' => $area,
                'itemid' => $i + 10,
                'filepath' => '/',
                'filename' => 'h5p-test-' . $i . '.h5p',
            ], str_repeat('h', $i + 2));
        }

        $reportid = report_manager::create((int) get_admin()->id);
        $areas = comparison_summary::fileareas($reportid);
        $themes = [];
        $h5ps = [];
        foreach ($areas as $row) {
            if ($row['component'] === 'theme_*') {
                $themes[] = $row;
            }
            if ($row['component'] === 'core_h5p') {
                $h5ps[] = $row;
            }
            $this->assertNotSame('theme_learnlingo', $row['component']);
            $this->assertNotSame('theme_degrade', $row['component']);
        }
        $this->assertCount(1, $themes);
        $this->assertCount(1, $h5ps);
        $this->assertTrue($themes[0]['iscomponent']);
        $this->assertTrue($h5ps[0]['iscomponent']);
        $this->assertSame($themebytes, $themes[0]['allbytes']);
        $this->assertCount(5, array_filter(explode(' / ', $themes[0]['filearea']),
            static function(string $area): bool {
                return $area !== 'etc...';
            }));
        $this->assertStringEndsWith(' / etc...', $themes[0]['filearea']);
        foreach (['libraries', 'export', 'content'] as $area) {
            $this->assertStringContainsString($area, $h5ps[0]['filearea']);
        }
    }

    /**
     * The listed Moodle file areas belong in the classified summary, not in
     * the residual table. An unrelated area remains in the residual table.
     */
    public function test_documented_file_areas_are_classified(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $storage = get_file_storage();
        $contextid = context_system::instance()->id;
        $fixtures = [
            ['core_admin', 'logo', true],
            ['core', 'preview', true],
            ['mod_folder', 'content', true],
            ['tool_recyclebin', 'recyclebin_course', true],
            ['format_tiles', 'tilephoto', true],
            ['assignfeedback_editpdf', 'stamps', true],
            ['core', 'unknown', false],
            ['mod_resource', 'content', false],
        ];
        [$classifiedsql, $params] = comparison_summary::classified_condition('f');
        foreach ($fixtures as $i => [$component, $area, $expected]) {
            $file = $storage->create_file_from_string([
                'contextid' => $contextid,
                'component' => $component,
                'filearea' => $area,
                'itemid' => $i + 100,
                'filepath' => '/',
                'filename' => 'documented-area-' . $i . '.txt',
            ], 'fixture-' . $i);
            $count = (int) $DB->count_records_sql(
                'SELECT COUNT(*) FROM {files} f WHERE f.id = :fileid AND ' . $classifiedsql,
                ['fileid' => $file->get_id()] + $params
            );
            $this->assertSame($expected ? 1 : 0, $count, $component . '/' . $area);
        }
        $reportid = report_manager::create((int) get_admin()->id);
        $summaries = comparison_summary::fileareas($reportid);
        foreach ($fixtures as [$component, $area, $expected]) {
            if (!$expected) {
                continue;
            }
            $found = false;
            foreach ($summaries as $summary) {
                if ($summary['component'] === $component && $summary['filearea'] === $area) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, $component . '/' . $area);
        }
    }

    /**
     * The same SQL classification used by both tables leaves unrelated
     * course files in the residual unmatched listing.
     */
    public function test_other_areas_do_not_reappear_in_residual_listing(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $storage = get_file_storage();
        $context = context_system::instance()->id;
        $files = [];
        foreach (['user', 'core_h5p', 'theme_learnlingo', 'mod_resource'] as $i => $component) {
            $area = $component === 'user' ? 'draft' : 'content';
            $files[$component] = $storage->create_file_from_string([
                'contextid' => $context,
                'component' => $component,
                'filearea' => $area,
                'itemid' => 80 + $i,
                'filepath' => '/',
                'filename' => 'classification-' . $i . '.txt',
            ], str_repeat('x', 15 + $i));
        }
        $reportid = report_manager::create((int) get_admin()->id);
        [$classifiedsql, $classifiedparams] = comparison_summary::classified_condition('f');
        foreach ($files as $component => $file) {
            $params = array_merge($classifiedparams, [
                'fileid' => $file->get_id(),
                'reportid' => $reportid,
            ]);
            $sql = "SELECT COUNT(*)
                      FROM {files} f
                     WHERE f.id = :fileid
                       AND NOT EXISTS (
                           SELECT 1 FROM {local_filexray_file} fx
                            WHERE fx.reportid = :reportid AND fx.fileid = f.id
                       )
                       AND NOT " . $classifiedsql;
            $count = (int) $DB->count_records_sql($sql, $params);
            $this->assertSame($component === 'mod_resource' ? 1 : 0, $count, $component);
        }
    }


    /**
     * A file inventoried by two courses contributes bytes only once to coverage.
     */
    public function test_coverage_counts_distinct_files_and_logical_bytes(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $first = $this->getDataGenerator()->create_course();
        $second = $this->getDataGenerator()->create_course();
        $reportid = report_manager::create((int) get_admin()->id);
        $storage = get_file_storage();
        $selected = $storage->create_file_from_string([
            "contextid" => context_system::instance()->id,
            "component" => "mod_resource",
            "filearea" => "content",
            "itemid" => 994,
            "filepath" => "/",
            "filename" => "selected-coverage.txt",
        ], str_repeat("a", 11));
        $storage->create_file_from_string([
            "contextid" => context_system::instance()->id,
            "component" => "user",
            "filearea" => "draft",
            "itemid" => 995,
            "filepath" => "/",
            "filename" => "classified-coverage.txt",
        ], str_repeat("b", 19));
        $storage->create_file_from_string([
            "contextid" => context_system::instance()->id,
            "component" => "mod_resource",
            "filearea" => "content",
            "itemid" => 996,
            "filepath" => "/",
            "filename" => "residual-coverage.txt",
        ], str_repeat("c", 29));
        foreach ([$first, $second] as $course) {
            $job = $DB->get_record("local_filexray_course",
                ["reportid" => $reportid, "courseid" => $course->id], "*", MUST_EXIST);
            $DB->insert_record("local_filexray_file", (object) [
                "reportid" => $reportid,
                "reportcourseid" => $job->id,
                "courseid" => $course->id,
                "fileid" => $selected->get_id(),
                "contenthash" => $selected->get_contenthash(),
                "filesize" => $selected->get_filesize(),
                "contextid" => $selected->get_contextid(),
                "component" => $selected->get_component(),
                "filearea" => $selected->get_filearea(),
                "filename" => $selected->get_filename(),
            ]);
        }
        $coverage = comparison_summary::coverage($reportid);
        $this->assertSame(1, $coverage["matchedfiles"]);
        $this->assertSame(11, $coverage["matchedbytes"]);
        $this->assertGreaterThanOrEqual(19, $coverage["classifiedbytes"]);
        $this->assertGreaterThanOrEqual(29, $coverage["unmatchedbytes"]);
        $this->assertSame($coverage["totalfiles"],
            $coverage["matchedfiles"] + $coverage["classifiedfiles"] + $coverage["unmatchedfiles"]);
        $this->assertSame($coverage["totalbytes"],
            $coverage["matchedbytes"] + $coverage["classifiedbytes"] + $coverage["unmatchedbytes"]);
    }

    /**
     * Top courses must be ranked and scaled by bytes, not file count.
     */
    public function test_top_courses_rank_by_bytes(): void {
        $courses = [
            ["courseid" => 1, "filecount" => 100, "bytecount" => 5],
            ["courseid" => 2, "filecount" => 2, "bytecount" => 1000],
            ["courseid" => 3, "filecount" => 3, "bytecount" => 500],
        ];
        $top = comparison_summary::top_courses($courses);
        $this->assertSame([2, 3, 1], array_column($top, "courseid"));
        $this->assertEquals(100, $top[0]["chartwidth"]);
        $this->assertEquals(50, $top[1]["chartwidth"]);
        $this->assertArrayHasKey("formattedbytes", $top[0]);
    }

    /**
     * Per-course component totals exclude directories and preserve logical bytes.
     */
    public function test_course_components_group_inventory_records(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $reportid = report_manager::create((int) get_admin()->id);
        $job = $DB->get_record("local_filexray_course",
            ["reportid" => $reportid, "courseid" => $course->id], "*", MUST_EXIST);
        foreach ([
            ["mod_resource", "one.txt", 10],
            ["mod_resource", "two.txt", 25],
            ["core_h5p", "three.h5p", 200],
            ["core_h5p", ".", 0],
        ] as $i => [$component, $filename, $bytes]) {
            $DB->insert_record("local_filexray_file", (object) [
                "reportid" => $reportid,
                "reportcourseid" => $job->id,
                "courseid" => $course->id,
                "fileid" => 990001 + $i,
                "contenthash" => str_repeat("b", 40),
                "filesize" => $bytes,
                "contextid" => context_course::instance($course->id)->id,
                "component" => $component,
                "filearea" => "content",
                "filename" => $filename,
            ]);
        }
        $components = comparison_summary::course_components((int) $job->id);
        $this->assertCount(2, $components);
        $this->assertSame("core_h5p", $components[0]["component"]);
        $this->assertSame(200, $components[0]["bytecount"]);
        $this->assertSame(1, $components[0]["filecount"]);
        $this->assertSame("mod_resource", $components[1]["component"]);
        $this->assertSame(35, $components[1]["bytecount"]);
        $this->assertSame(2, $components[1]["filecount"]);
        $this->assertEquals(100, $components[0]["byteswidth"]);
    }
}
