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
 * Read-only File X-Ray queries for the optional MCP transport.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray\mcp;

use local_filexray\comparison_summary;
use local_filexray\report_manager;
use moodle_url;

/**
 * Query existing report snapshots without running backups or changing file data.
 */
final class service {
    /**
     * List stored reports with bounded pagination.
     *
     * @param int $offset First report index.
     * @param int $limit Number of results.
     * @return array
     */
    public static function list_reports(int $offset, int $limit): array {
        global $DB;
        $reports = $DB->get_records("local_filexray_report", [], "id DESC",
            "id, userid, status, totalcourses, timecreated, timestarted, timefinished",
            $offset, $limit);
        $rows = [];
        foreach ($reports as $report) {
            $rows[] = [
                "id" => (int) $report->id,
                "status" => $report->status,
                "userid" => (int) $report->userid,
                "totalcourses" => (int) $report->totalcourses,
                "timecreated" => (int) $report->timecreated,
                "timestarted" => (int) $report->timestarted,
                "timefinished" => (int) $report->timefinished,
                "url" => (new moodle_url("/local/filexray/report.php",
                    ["id" => $report->id]))->out(false),
            ];
        }
        return [
            "total" => (int) $DB->count_records("local_filexray_report"),
            "offset" => $offset, "limit" => $limit, "reports" => $rows,
        ];
    }

    /**
     * Read the progress of a stored report.
     *
     * @param int $reportid Report identifier.
     * @param int $offset First course index.
     * @param int $limit Page limit.
     * @return array
     */
    public static function report_status(int $reportid, int $offset, int $limit): array {
        $result = report_manager::status($reportid, $offset, $limit);
        $result["url"] = (new moodle_url("/local/filexray/report.php",
            ["id" => $reportid]))->out(false);
        return $result;
    }

    /**
     * Read a course inventory belonging to one report.
     *
     * @param int $reportid Report identifier.
     * @param int $coursejobid Snapshot course row ID.
     * @param int $offset First file index.
     * @param int $limit Page limit.
     * @return array
     */
    public static function course_files(int $reportid, int $coursejobid,
                                        int $offset, int $limit): array {
        global $DB;
        report_manager::get($reportid);
        // Bind both IDs so no course inventory from another snapshot can leak.
        $course = $DB->get_record("local_filexray_course",
            ["id" => $coursejobid, "reportid" => $reportid], "*", MUST_EXIST);
        $where = "reportid = :reportid AND reportcourseid = :jobid AND filename <> :directory";
        $params = ["reportid" => $reportid, "jobid" => $coursejobid, "directory" => "."];
        $total = (int) $DB->count_records_select("local_filexray_file", $where, $params);
        $records = $DB->get_records_select("local_filexray_file", $where, $params,
            "fileid ASC", "fileid, filename, component, filearea, contextid, filesize",
            $offset, $limit);
        $files = [];
        foreach ($records as $record) {
            $files[] = [
                "fileid" => (int) $record->fileid,
                "filename" => $record->filename,
                "component" => $record->component,
                "filearea" => $record->filearea,
                "contextid" => (int) $record->contextid,
                "filesize" => (int) $record->filesize,
            ];
        }
        return [
            "reportid" => $reportid, "coursejobid" => $coursejobid,
            "courseid" => (int) $course->courseid,
            "coursename" => $course->coursename, "course_status" => $course->status,
            "total" => $total, "offset" => $offset, "limit" => $limit,
            "files" => $files,
            "url" => (new moodle_url("/local/filexray/course.php",
                ["id" => $reportid, "coursejobid" => $coursejobid]))->out(false),
        ];
    }

    /**
     * Summarize backup inventory coverage and file areas.
     *
     * @param int $reportid Report identifier.
     * @param int $offset Course offset.
     * @param int $limit Course page limit.
     * @param bool $includeareas Include classified file-area totals.
     * @return array
     */
    public static function comparison(int $reportid, int $offset, int $limit,
                                      bool $includeareas): array {
        global $DB;
        $report = report_manager::get($reportid);
        [$condition, $params] = comparison_summary::unmatched_condition($reportid);
        $total = $DB->count_records_select("files",
            "filename <> :directory", ["directory" => "."]);
        $matched = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT fileid) FROM {local_filexray_file}
               WHERE reportid = :rid AND filename <> :directory",
            ["rid" => $reportid, "directory" => "."]);
        $unmatched = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {files} f WHERE " . $condition, $params);
        $courses = comparison_summary::courses($reportid, $offset, $limit);
        foreach ($courses as &$course) {
            // The HTML-safe course display name is only markup for Mustache pages.
            $course["coursename"] = html_entity_decode(strip_tags($course["coursename"]),
                ENT_QUOTES | ENT_HTML5, "UTF-8");
        }
        unset($course);
        return [
            "reportid" => $reportid,
            "status" => $report->status,
            "provisional" => $report->status !== "completed",
            "caution" => "Records outside a course backup inventory are not proven orphaned or safe to delete.",
            "totalfiles" => (int) $total, "matched" => (int) $matched,
            "unmatched" => (int) $unmatched,
            "coursecount" => (int) $DB->count_records("local_filexray_course",
                ["reportid" => $reportid]),
            "offset" => $offset, "limit" => $limit, "courses" => $courses,
            "areas" => $includeareas ? comparison_summary::fileareas($reportid) : [],
            "url" => (new moodle_url("/local/filexray/comparison.php",
                ["id" => $reportid]))->out(false),
        ];
    }

    /**
     * List records excluded from a completed course inventory.
     *
     * @param int $reportid Report identifier.
     * @param int $offset File offset.
     * @param int $limit File page limit.
     * @return array
     */
    public static function unmatched_files(int $reportid, int $offset, int $limit): array {
        global $DB;
        $report = report_manager::get($reportid);
        [$condition, $params] = comparison_summary::unmatched_condition($reportid);
        $total = $DB->count_records_sql(
            "SELECT COUNT(*) FROM {files} f WHERE " . $condition, $params);
        $records = $DB->get_records_sql(
            "SELECT f.id, f.filename, f.component, f.filearea, f.contextid, f.filesize
               FROM {files} f WHERE " . $condition . " ORDER BY f.id ASC",
            $params, $offset, $limit);
        $files = [];
        foreach ($records as $record) {
            $files[] = [
                "fileid" => (int) $record->id, "filename" => $record->filename,
                "component" => $record->component, "filearea" => $record->filearea,
                "contextid" => (int) $record->contextid,
                "filesize" => (int) $record->filesize,
            ];
        }
        return [
            "reportid" => $reportid,
            "provisional" => $report->status !== "completed",
            "caution" => "Records outside a backup inventory are not necessarily orphaned.",
            "total" => (int) $total, "offset" => $offset,
            "limit" => $limit, "files" => $files,
            "url" => (new moodle_url("/local/filexray/unmatched.php",
                ["id" => $reportid]))->out(false),
        ];
    }
}
