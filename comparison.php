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
 * Read-only paged comparison between mdl_files and completed inventories.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_filexray\comparison_summary;
use local_filexray\file_area_guide;
use local_filexray\report_manager;

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$coursepage = max(0, optional_param('coursepage', 0, PARAM_INT));
require_login();
$context = context_system::instance();
require_capability('local/filexray:view', $context);

$report = report_manager::get($id);
$PAGE->set_url(new moodle_url('/local/filexray/comparison.php', ['id' => $id]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('comparison', 'local_filexray'));
$PAGE->set_heading(get_string('comparison', 'local_filexray'));

// Keep the card count consistent with the dedicated residual-files page.
[$where, $params] = comparison_summary::unmatched_condition($id);
$total = (int) $DB->count_records_select('files', "filename <> :dir", ['dir' => '.']);
$matched = (int) $DB->count_records_sql(
    "SELECT COUNT(DISTINCT f.id)
       FROM {local_filexray_file} fx
       JOIN {files} f ON f.id = fx.fileid
      WHERE fx.reportid = :rid AND f.filename <> :dir",
    ['rid' => $id, 'dir' => '.']
);
$unmatched = (int) $DB->count_records_sql(
    "SELECT COUNT(*) FROM {files} f WHERE " . $where, $params
);
// Course totals come from saved backup and context inventories, not a live context lookup.
$coursepagesize = 50;
$allcoursecount = $DB->count_records("local_filexray_course", ["reportid" => $id]);
$coursecount = $DB->count_records("local_filexray_course", [
    "reportid" => $id,
    "status" => "completed",
]);
$courses = comparison_summary::courses(
    $id, $coursepage * $coursepagesize, $coursepagesize
);
foreach ($courses as &$course) {
    $course['url'] = (new moodle_url('/local/filexray/course.php', [
        'id' => $id, 'coursejobid' => $course['id'],
    ]))->out(false);
}
unset($course);
$areas = comparison_summary::fileareas($id);

// Each classified area links to its local, language-aware, read-only guide.
// Use the general overview for less common areas without a dedicated page.
foreach ($areas as &$area) {
    $area['docurl'] = file_area_guide::documentation_url(
        $area['component'], $area['filearea'], true
    );
}
unset($area);

// These groups are mutually exclusive subsets of the current Moodle files table.
$classified = max(0, $total - $matched - $unmatched);
$matchedpercent = $total ? 100 * $matched / $total : 0;
$classifiedendpercent = $total ? 100 * ($matched + $classified) / $total : 0;

// Compare the most populated courses on the currently displayed page only.
$topcourses = $courses;
usort($topcourses, static function(array $first, array $second): int {
    return ($second["filecount"] <=> $first["filecount"])
        ?: ($first["courseid"] <=> $second["courseid"]);
});
$topcourses = array_slice($topcourses, 0, 5);
$maxcoursefiles = $topcourses ? max(array_column($topcourses, "filecount")) : 0;
foreach ($topcourses as &$topcourse) {
    $topcourse["chartwidth"] = $maxcoursefiles
        ? round(100 * $topcourse["filecount"] / $maxcoursefiles, 1) : 0;
    $topcourse["formattedfilecount"] = number_format($topcourse["filecount"], 0, ",", ".");
}
unset($topcourse);

$unmatchedurl = (new moodle_url("/local/filexray/unmatched.php", ["id" => $id]))->out(false);
$settingsurl = (new moodle_url("/admin/settings.php", [
    "section" => "local_filexray_settings",
]))->out(false);

$data = [
    'reportid' => $id,
    'unmatchedurl' => $unmatchedurl,
    "skipuserdatawarning" => (bool) get_config("local_filexray", "skipuserdata"),
    "caneditsettings" => has_capability("moodle/site:config", $context),
    "settingsurl" => $settingsurl,
    "provisional" => $report->status !== "completed",
    "chart_has_files" => $total > 0,
    "chart_has_courses" => $maxcoursefiles > 0,
    "chartmatchedpercent" => number_format($matchedpercent, 2, ".", ""),
    "chartclassifiedendpercent" => number_format($classifiedendpercent, 2, ".", ""),
    "chartmatchedpercentlabel" => number_format($matchedpercent, 1, ",", ".") . "%",
    "chartclassifiedformatted" => number_format($classified, 0, ",", "."),
    "topcourses" => $topcourses,
    'courses' => $courses,
    'coursecount' => $coursecount,
    "coursecountformatted" => number_format($coursecount, 0, ",", "."),
    'areas' => $areas,
    'previouscourse' => $coursepage > 0,
    'previouscourseurl' => (new moodle_url('/local/filexray/comparison.php', [
        'id' => $id, 'coursepage' => max(0, $coursepage - 1),
    ]))->out(false),
    'nextcourse' => ($coursepage + 1) * $coursepagesize < $allcoursecount,
    'nextcourseurl' => (new moodle_url('/local/filexray/comparison.php', [
        'id' => $id, 'coursepage' => $coursepage + 1,
    ]))->out(false),
    'total' => $total,
    'matched' => $matched,
    'unmatched' => $unmatched,
    "totalformatted" => number_format($total, 0, ",", "."),
    "matchedformatted" => number_format($matched, 0, ",", "."),
    "unmatchedformatted" => number_format($unmatched, 0, ",", "."),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_filexray/comparison', $data);
echo $OUTPUT->footer();
