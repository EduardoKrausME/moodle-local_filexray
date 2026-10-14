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
 * Read-only paginated file references for one course snapshot.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$coursejobid = required_param('coursejobid', PARAM_INT);
$page = max(0, optional_param('page', 0, PARAM_INT));
require_login();
$context = context_system::instance();
require_capability('local/filexray:view', $context);

\local_filexray\report_manager::get($id);
$coursejob = $DB->get_record('local_filexray_course',
    ['id' => $coursejobid, 'reportid' => $id], '*', MUST_EXIST);
$PAGE->set_url(new moodle_url('/local/filexray/course.php', ['id' => $id, 'coursejobid' => $coursejobid]));
$PAGE->set_context($context);
$PAGE->set_title(format_string($coursejob->coursename));
$PAGE->set_heading(get_string('matchedfiles', 'local_filexray'));

$limit = 50;
$rows = $DB->get_records('local_filexray_file', ['reportcourseid' => $coursejobid],
    'fileid ASC', 'id, fileid, filename, component, filearea, contextid, filesize',
    $page * $limit, $limit);
$files = [];
foreach ($rows as $row) {
    $files[] = [
        'fileid' => $row->fileid, 'filename' => $row->filename,
        'component' => $row->component, 'filearea' => $row->filearea,
        'contextid' => $row->contextid, 'size' => display_size($row->filesize),
    ];
}
$baseurl = '/local/filexray/course.php';
$data = [
    'coursename' => format_string($coursejob->coursename),
    'files' => $files,
    'filecount' => $coursejob->filecount,
    'previous' => $page > 0,
    'previousurl' => (new moodle_url($baseurl,
        ['id' => $id, 'coursejobid' => $coursejobid, 'page' => max(0, $page - 1)]))->out(false),
    'next' => ($page + 1) * $limit < $coursejob->filecount,
    'nexturl' => (new moodle_url($baseurl,
        ['id' => $id, 'coursejobid' => $coursejobid, 'page' => $page + 1]))->out(false),
    'reporturl' => (new moodle_url('/local/filexray/report.php', ['id' => $id]))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_filexray/course', $data);
echo $OUTPUT->footer();