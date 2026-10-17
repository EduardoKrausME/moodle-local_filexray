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
 * Read-only paginated file references for one course snapshot.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_filexray\comparison_summary;
use local_filexray\file_area_guide;
use local_filexray\file_preview;
use local_filexray\report_manager;

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$coursejobid = required_param('coursejobid', PARAM_INT);
$page = max(0, optional_param('page', 0, PARAM_INT));
$component = optional_param("component", "", PARAM_ALPHANUMEXT);
require_login();
$context = context_system::instance();
require_capability('local/filexray:view', $context);

report_manager::get($id);
$coursejob = $DB->get_record('local_filexray_course',
    ['id' => $coursejobid, 'reportid' => $id], '*', MUST_EXIST);
$baseurl = "/local/filexray/course.php";
$baseparams = ["id" => $id, "coursejobid" => $coursejobid];
$filterparams = $baseparams;
if ($component !== "") {
    $filterparams["component"] = $component;
}
$PAGE->set_url(new moodle_url($baseurl, $filterparams + ["page" => $page]));
$PAGE->set_context($context);
$PAGE->set_title(format_string($coursejob->coursename));
$PAGE->set_heading(get_string('matchedfiles', 'local_filexray'));

$limit = 50;
// Component totals use all saved records, not only the current file page.
$components = comparison_summary::course_components($coursejobid);
foreach ($components as &$entry) {
    $entry["componentname"] = file_area_guide::component_name($entry["component"]);
    $entry["selected"] = $component !== "" && $component === $entry["component"];
    $entry["url"] = (new moodle_url($baseurl,
        $baseparams + ["component" => $entry["component"]]))->out(false);
}
unset($entry);

// Directory placeholders have filename "." and are not downloadable files.
$where = "reportcourseid = :courseid AND filename <> :directory";
$params = ["courseid" => $coursejobid, "directory" => "."];
if ($component !== "") {
    $where .= " AND component = :component";
    $params["component"] = $component;
}
$totalfiles = $DB->count_records_select("local_filexray_file", $where, $params);
$rows = $DB->get_records_select("local_filexray_file", $where, $params,
    "fileid ASC", "id, fileid, filename, component, filearea, contextid, filesize",
    $page * $limit, $limit);
$files = [];
foreach ($rows as $row) {
    $kind = file_preview::kind($row->filename);
    $url = new moodle_url($kind === 'office'
        ? '/local/filexray/office.php' : '/local/filexray/preview.php', [
        'id' => $id, 'coursejobid' => $coursejobid, 'fileid' => $row->fileid,
    ]);
    $files[] = [
        'fileid' => $row->fileid, 'filename' => $row->filename,
        'component' => $row->component, 'filearea' => $row->filearea,
        'componentname' => file_area_guide::component_name($row->component),
        'docurl' => file_area_guide::documentation_url($row->component, $row->filearea),
        'contextid' => $row->contextid, 'size' => display_size($row->filesize),
        'previewurl' => $url->out(false),
        'canpreview' => $kind !== 'download',
        'candownload' => $kind === 'download',
        'isoffice' => $kind === 'office',
    ];
}
$data = [
    "components" => $components,
    "hascomponents" => !empty($components),
    "selectedcomponent" => $component,
    "selectedcomponentname" => $component !== "" ? file_area_guide::component_name($component) : "",
    "allcomponentsurl" => (new moodle_url($baseurl, $baseparams))->out(false),
    'coursename' => format_string($coursejob->coursename),
    'files' => $files,
    'filecount' => $totalfiles,
    "hasfiles" => $totalfiles > 0,
    'previous' => $page > 0,
    'previousurl' => (new moodle_url($baseurl,
        $filterparams + ["page" => max(0, $page - 1)]))->out(false),
    'next' => ($page + 1) * $limit < $totalfiles,
    'nexturl' => (new moodle_url($baseurl,
        $filterparams + ["page" => $page + 1]))->out(false),
    'reporturl' => (new moodle_url('/local/filexray/report.php', ['id' => $id]))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_filexray/course', $data);
echo $OUTPUT->footer();
