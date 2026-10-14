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
 * Read-only paged comparison between mdl_files and completed inventories.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$page = max(0, optional_param('page', 0, PARAM_INT));
$coursepage = max(0, optional_param('coursepage', 0, PARAM_INT));
require_login();
$context = context_system::instance();
require_capability('local/filexray:view', $context);

$report = \local_filexray\report_manager::get($id);
$PAGE->set_url(new moodle_url('/local/filexray/comparison.php', ['id' => $id]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('comparison', 'local_filexray'));
$PAGE->set_heading(get_string('comparison', 'local_filexray'));

$params = ['reportid' => $id, 'dir' => '.'];
// Use the same classification as the other-area summary, so a file ID
// grouped there does not also appear in the residual unmatched listing.
[$classifiedsql, $classifiedparams] = \local_filexray\comparison_summary::classified_condition('f');
$params = array_merge($params, $classifiedparams);
$where = "f.filename <> :dir
          AND NOT EXISTS (
              SELECT 1 FROM {local_filexray_file} fx
               WHERE fx.reportid = :reportid AND fx.fileid = f.id
          )
          AND NOT " . $classifiedsql;
$total = (int) $DB->count_records_select('files', "filename <> :dir", ['dir' => '.']);
$matched = (int) $DB->count_records_sql(
    "SELECT COUNT(DISTINCT fileid) FROM {local_filexray_file}
      WHERE reportid = :rid AND filename <> :dir",
    ['rid' => $id, 'dir' => '.']
);
$unmatched = (int) $DB->count_records_sql(
    "SELECT COUNT(*) FROM {files} f WHERE " . $where, $params
);
// Course totals come from the saved backup inventories, not a live course context lookup.
$coursepagesize = 50;
$coursecount = $DB->count_records('local_filexray_course', ['reportid' => $id]);
$courses = \local_filexray\comparison_summary::courses(
    $id, $coursepage * $coursepagesize, $coursepagesize
);
foreach ($courses as &$course) {
    $course['url'] = (new moodle_url('/local/filexray/course.php', [
        'id' => $id, 'coursejobid' => $course['id'],
    ]))->out(false);
}
unset($course);
$areas = \local_filexray\comparison_summary::fileareas($id);

// Each classified area links to its local, language-aware, read-only guide.
// Use the general overview for less common areas without a dedicated page.
$doclanguage = strpos(current_language(), 'pt') === 0 ? 'pt_br' : 'en';
$guides = [
    'user/draft' => 'user-draft',
    'user/backup' => 'user-backup',
    'user/icon' => 'user-icon',
    'user/private' => 'user-private',
    'user/profile' => 'user-profile',
    'core_h5p/*' => 'core-h5p',
    'theme_*/*' => 'themes',
    'core_admin/*' => 'core-admin',
    'core/preview' => 'core-preview',
    'mod_folder/content' => 'mod-folder',
    'tool_recyclebin/recyclebin_course' => 'recycle-bin',
    'format_tiles/tilephoto' => 'format-tiles',
    'assignfeedback_editpdf/stamps' => 'editpdf-stamps',
    'badges/badgeimage' => 'badges',
    'backup/*' => 'backup',
];
foreach ($areas as &$area) {
    $key = $area['component'] . '/' . ($area['iscomponent'] ? '*' : $area['filearea']);
    $guide = $guides[$key] ?? 'areas';
    $area['docurl'] = (new moodle_url('/local/filexray/docs/' . $doclanguage .
        '/recursos/' . $guide . '.html'))->out(false);
}
unset($area);

$limit = 50;
$rows = $DB->get_records_sql(
    "SELECT f.id, f.filename, f.component, f.filearea, f.filesize, f.contextid
       FROM {files} f
      WHERE " . $where . " ORDER BY f.id ASC",
    $params, $page * $limit, $limit
);
$files = [];
foreach ($rows as $r) {
    $files[] = [
        'id' => $r->id,
        'name' => $r->filename,
        'component' => $r->component,
        'filearea' => $r->filearea,
        'contextid' => $r->contextid,
        'size' => display_size($r->filesize),
    ];
}
$data = [
    'reportid' => $id,
    'courses' => $courses,
    'coursecount' => $coursecount,
    'areas' => $areas,
    'previouscourse' => $coursepage > 0,
    'previouscourseurl' => (new moodle_url('/local/filexray/comparison.php', [
        'id' => $id, 'page' => $page, 'coursepage' => max(0, $coursepage - 1),
    ]))->out(false),
    'nextcourse' => ($coursepage + 1) * $coursepagesize < $coursecount,
    'nextcourseurl' => (new moodle_url('/local/filexray/comparison.php', [
        'id' => $id, 'page' => $page, 'coursepage' => $coursepage + 1,
    ]))->out(false),
    'total' => $total,
    'matched' => $matched,
    'unmatched' => $unmatched,
    'files' => $files,
    'provisional' => $report->status !== 'completed',
    'previous' => $page > 0,
    'previousurl' => (new moodle_url('/local/filexray/comparison.php',
        ['id' => $id, 'page' => max(0, $page - 1), 'coursepage' => $coursepage]))->out(false),
    'next' => ($page + 1) * $limit < $unmatched,
    'nexturl' => (new moodle_url('/local/filexray/comparison.php',
        ['id' => $id, 'page' => $page + 1, 'coursepage' => $coursepage]))->out(false),
    'reporturl' => (new moodle_url('/local/filexray/report.php', ['id' => $id]))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_filexray/comparison', $data);
echo $OUTPUT->footer();
