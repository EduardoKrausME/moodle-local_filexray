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
 * File X-Ray landing page.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/filexray:view', $context);

$PAGE->set_url(new moodle_url('/local/filexray/index.php'));

\local_filexray\mcp_banner::dismiss_if_requested($PAGE->url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_filexray'));
$PAGE->set_heading(get_string('pluginname', 'local_filexray'));
$PAGE->requires->js_call_amd('local_filexray/reports', 'initIndex');

$recent = $DB->get_records('local_filexray_report', [], 'id DESC',
    'id, status, totalcourses, timecreated', 0, 30);
$reports = [];
foreach ($recent as $r) {
    $reports[] = [
        'id' => $r->id, 'status' => get_string($r->status, 'local_filexray'),
        'totalcourses' => $r->totalcourses, 'created' => userdate($r->timecreated),
        'url' => (new moodle_url('/local/filexray/report.php', ['id' => $r->id]))->out(false),
        'candelete' => has_capability('local/filexray:run', $context),
    ];
}

$data = [
    'canrun' => has_capability('local/filexray:run', $context),
    'courses' => $DB->count_records_select('course', 'id <> :siteid', ['siteid' => SITEID]),
    'reports' => $reports,
    'hasreports' => !empty($reports),
    'reportbaseurl' => (new moodle_url('/local/filexray/report.php'))->out(false),
    'labelgenerate' => get_string('generate', 'local_filexray'),
];

echo $OUTPUT->header();
if (\local_filexray\mcp_banner::should_show()) {
    echo \local_filexray\mcp_banner::render($OUTPUT, $PAGE->url);
}

echo $OUTPUT->render_from_template('local_filexray/index', $data);
echo $OUTPUT->footer();
