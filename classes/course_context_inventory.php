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
 * Inventory Moodle files belonging directly to course and activity contexts.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray;

/**
 * Supplements backup annotations with course/module context files.
 */
final class course_context_inventory {
    /**
     * Append files owned by the course and its course modules, without duplicating backup references.
     *
     * Read files as metadata only; no stored-file content is copied or changed.
     * Each query joins indexed context IDs rather than scanning the entire files table.
     *
     * @param int $reportid Report identifier.
     * @param int $reportcourseid Report course row identifier.
     * @param int $courseid Moodle course identifier.
     * @return int Number of additional file records.
     */
    public static function append(int $reportid, int $reportcourseid, int $courseid): int {
        global $DB;

        $sources = [
            ["table" => "{course} c", "instance" => "c.id", "filter" => "c.id", "level" => CONTEXT_COURSE],
            ["table" => "{course_modules} cm", "instance" => "cm.id", "filter" => "cm.course",
                "level" => CONTEXT_MODULE],
        ];
        $count = 0;
        foreach ($sources as $source) {
            $sql = "SELECT f.id AS fileid, f.contenthash, f.filesize, f.contextid,
                           f.component, f.filearea, f.filename
                      FROM {$source["table"]}
                      JOIN {context} ctx ON ctx.contextlevel = :contextlevel
                                        AND ctx.instanceid = {$source["instance"]}
                      JOIN {files} f ON f.contextid = ctx.id
                     WHERE {$source["filter"]} = :courseid
                       AND NOT EXISTS (
                           SELECT 1 FROM {local_filexray_file} fx
                            WHERE fx.reportcourseid = :reportcourseid AND fx.fileid = f.id
                       )";
            $rs = $DB->get_recordset_sql($sql, [
                "contextlevel" => $source["level"],
                "courseid" => $courseid,
                "reportcourseid" => $reportcourseid,
            ]);
            try {
                foreach ($rs as $file) {
                    $DB->insert_record("local_filexray_file", (object) [
                        "reportid" => $reportid,
                        "reportcourseid" => $reportcourseid,
                        "courseid" => $courseid,
                        "fileid" => $file->fileid,
                        "contenthash" => $file->contenthash,
                        "filesize" => $file->filesize,
                        "contextid" => $file->contextid,
                        "component" => $file->component,
                        "filearea" => $file->filearea,
                        "filename" => $file->filename,
                    ], false);
                    $count++;
                }
            } finally {
                $rs->close();
            }
        }
        return $count;
    }
}
