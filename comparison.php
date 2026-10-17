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

// One shared classification is used for the file-count and logical-byte charts.
$coverage = comparison_summary::coverage($id);
$total = $coverage["totalfiles"];
$matched = $coverage["matchedfiles"];
$unmatched = $coverage["unmatchedfiles"];
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

// Both distributions have the same exclusive groups, measured independently.
$classified = $coverage["classifiedfiles"];
$matchedpercent = $total ? 100 * $matched / $total : 0;
$classifiedendpercent = $total ? 100 * ($matched + $classified) / $total : 0;
$totalbytes = $coverage["totalbytes"];
$matchedbytespercent = $totalbytes ? 100 * $coverage["matchedbytes"] / $totalbytes : 0;
$classifiedbytesendpercent = $totalbytes
    ? 100 * ($coverage["matchedbytes"] + $coverage["classifiedbytes"]) / $totalbytes : 0;

// Rank courses on this page by the logical bytes in their saved inventories.
$topcourses = comparison_summary::top_courses($courses);

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
    "chart_has_bytes" => $totalbytes > 0,
    "chart_has_courses" => !empty($topcourses) && $topcourses[0]["bytecount"] > 0,
    "chartmatchedpercent" => number_format($matchedpercent, 2, ".", ""),
    "chartclassifiedendpercent" => number_format($classifiedendpercent, 2, ".", ""),
    "chartmatchedpercentlabel" => number_format($matchedpercent, 1, ",", ".") . "%",
    "chartclassifiedformatted" => number_format($classified, 0, ",", "."),
    "chartmatchedbytespercent" => number_format($matchedbytespercent, 2, ".", ""),
    "chartclassifiedbytesendpercent" => number_format($classifiedbytesendpercent, 2, ".", ""),
    "chartmatchedbytespercentlabel" => number_format($matchedbytespercent, 1, ",", ".") . "%",
    "chartmatchedbytesformatted" => display_size($coverage["matchedbytes"]),
    "chartclassifiedbytesformatted" => display_size($coverage["classifiedbytes"]),
    "chartunmatchedbytesformatted" => display_size($coverage["unmatchedbytes"]),
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
