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
 * Read-only paged comparison between mdl_files and completed inventories.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$page = max(0, optional_param('page', 0, PARAM_INT));
require_login();
$context = context_system::instance();
require_capability('local/filexray:view', $context);

$report = \local_filexray\report_manager::get($id);
$PAGE->set_url(new moodle_url('/local/filexray/comparison.php', ['id' => $id]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('comparison', 'local_filexray'));
$PAGE->set_heading(get_string('comparison', 'local_filexray'));

$params = ['reportid' => $id, 'dir' => '.'];
$where = "f.filename <> :dir
          AND NOT EXISTS (
              SELECT 1 FROM {local_filexray_file} fx
               WHERE fx.reportid = :reportid AND fx.fileid = f.id
          )";
$total = (int) $DB->count_records_select('files', "filename <> :dir", ['dir' => '.']);
$matched = (int) $DB->count_records_sql(
    "SELECT COUNT(DISTINCT fileid) FROM {local_filexray_file}
      WHERE reportid = :rid AND filename <> :dir",
    ['rid' => $id, 'dir' => '.']
);
$unmatched = (int) $DB->count_records_sql(
    "SELECT COUNT(*) FROM {files} f WHERE " . $where, $params
);
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
    'total' => $total,
    'matched' => $matched,
    'unmatched' => $unmatched,
    'files' => $files,
    'provisional' => $report->status !== 'completed',
    'previous' => $page > 0,
    'previousurl' => (new moodle_url('/local/filexray/comparison.php',
        ['id' => $id, 'page' => max(0, $page - 1)]))->out(false),
    'next' => ($page + 1) * $limit < $unmatched,
    'nexturl' => (new moodle_url('/local/filexray/comparison.php',
        ['id' => $id, 'page' => $page + 1]))->out(false),
    'reporturl' => (new moodle_url('/local/filexray/report.php', ['id' => $id]))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_filexray/comparison', $data);
echo $OUTPUT->footer();