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
 * Live progress details for a persisted report.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_filexray\report_manager;

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
require_login();
$context = context_system::instance();
require_capability('local/filexray:view', $context);

$report = report_manager::get($id);
if (in_array($report->status, ['completed', 'partial'], true)) {
    // Completed reports always open their comparison, including on later visits.
    redirect(new moodle_url('/local/filexray/comparison.php', ['id' => $id]));
}
$PAGE->set_url(new moodle_url('/local/filexray/report.php', ['id' => $id]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('report', 'local_filexray', $id));
$PAGE->set_heading(get_string('report', 'local_filexray', $id));
$PAGE->requires->js_call_amd('local_filexray/reports', 'initReport', [$id]);

$data = [
    'id' => $id,
    'total' => $report->totalcourses,
    'canresume' => has_capability('local/filexray:run', $context),
    'comparisonurl' => (new moodle_url('/local/filexray/comparison.php', ['id' => $id]))->out(false),
    'coursebaseurl' => (new moodle_url('/local/filexray/course.php'))->out(false),
    'indexurl' => (new moodle_url('/local/filexray/index.php'))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_filexray/report', $data);
echo $OUTPUT->footer();
