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
 * Read-only file records outside the course backup inventories.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$page = max(0, optional_param('page', 0, PARAM_INT));
require_login();
$context = context_system::instance();
require_capability('local/filexray:view', $context);

// A report must exist, even when the original course no longer exists.
$report = \local_filexray\report_manager::get($id);
$PAGE->set_url(new moodle_url('/local/filexray/unmatched.php', ['id' => $id, 'page' => $page]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('unmatched', 'local_filexray'));
$PAGE->set_heading(get_string('unmatched', 'local_filexray'));

[$where, $params] = \local_filexray\comparison_summary::unmatched_condition($id);
$total = (int) $DB->count_records_sql(
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
foreach ($rows as $file) {
    $files[] = [
        'id' => $file->id,
        'name' => $file->filename,
        'component' => $file->component,
        'filearea' => $file->filearea,
        'contextid' => $file->contextid,
        'size' => display_size($file->filesize),
    ];
}
$data = [
    'reportid' => $id,
    'total' => $total,
    'files' => $files,
    'provisional' => $report->status !== 'completed',
    'previous' => $page > 0,
    'previousurl' => (new moodle_url('/local/filexray/unmatched.php', [
        'id' => $id, 'page' => max(0, $page - 1),
    ]))->out(false),
    'next' => ($page + 1) * $limit < $total,
    'nexturl' => (new moodle_url('/local/filexray/unmatched.php', [
        'id' => $id, 'page' => $page + 1,
    ]))->out(false),
    'comparisonurl' => (new moodle_url('/local/filexray/comparison.php', ['id' => $id]))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_filexray/unmatched', $data);
echo $OUTPUT->footer();
