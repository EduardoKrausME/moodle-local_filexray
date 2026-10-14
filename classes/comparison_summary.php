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
 * Grouped course and file-area statistics for the comparison report.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray;

defined('MOODLE_INTERNAL') || die();

/**
 * Aggregate logical sizes from the inventory and current mdl_files records.
 */
final class comparison_summary {
    /**
     * Read course snapshot totals without counting directory entries.
     *
     * @param int $reportid Report ID.
     * @param int $offset First row.
     * @param int $limit Page size.
     * @return array
     */
    public static function courses(int $reportid, int $offset, int $limit): array {
        global $DB;

        $sql = "SELECT rc.id, rc.courseid, rc.coursename, rc.status,
                       COALESCE(inventory.filecount, 0) AS filecount,
                       COALESCE(inventory.bytecount, 0) AS bytecount
                  FROM {local_filexray_course} rc
             LEFT JOIN (
                     SELECT reportcourseid, COUNT(*) AS filecount, SUM(filesize) AS bytecount
                       FROM {local_filexray_file}
                      WHERE reportid = :inventoryreport AND filename <> :directory
                   GROUP BY reportcourseid
                       ) inventory ON inventory.reportcourseid = rc.id
                 WHERE rc.reportid = :coursereport
              ORDER BY rc.courseid ASC";
        $records = $DB->get_records_sql($sql, [
            'inventoryreport' => $reportid, 'directory' => '.', 'coursereport' => $reportid,
        ], $offset, $limit);
        $courses = [];
        foreach ($records as $record) {
            $bytes = (int) $record->bytecount;
            $status = in_array($record->status, ['pending', 'queued', 'running', 'completed', 'failed'], true)
                ? get_string($record->status, 'local_filexray') : $record->status;
            $courses[] = [
                'id' => (int) $record->id,
                'courseid' => (int) $record->courseid,
                'coursename' => $record->coursename,
                'status' => $status,
                'filecount' => (int) $record->filecount,
                'bytecount' => $bytes,
                'size' => display_size($bytes),
            ];
        }
        return $courses;
    }

    /**
     * Display totals per file area, distinguishing global records from those
     * not referenced by this report's inventory.
     *
     * @param int $reportid Report ID.
     * @return array
     */
    public static function fileareas(int $reportid): array {
        global $DB;

        // Distinct IDs prevent duplication when multiple courses refer to a file.
        $sql = "SELECT f.component, f.filearea,
                       COUNT(*) AS allcount,
                       COALESCE(SUM(f.filesize), 0) AS allbytes,
                       SUM(CASE WHEN selected.fileid IS NULL THEN 1 ELSE 0 END) AS outsidecount,
                       COALESCE(SUM(CASE WHEN selected.fileid IS NULL THEN f.filesize ELSE 0 END), 0) AS outsidebytes
                  FROM {files} f
             LEFT JOIN (
                     SELECT DISTINCT fileid FROM {local_filexray_file}
                      WHERE reportid = :reportid
                       ) selected ON selected.fileid = f.id
                 WHERE f.filename <> :directory
              GROUP BY f.component, f.filearea";
        $labels = [
            'user/draft' => 'areadraft',
            'user/icon' => 'areaavatar',
            'user/private' => 'areaprivate',
            'user/profile' => 'areaprofile',
        ];
        $priority = array_keys($labels);
        $rows = [];
        $recordset = $DB->get_recordset_sql($sql, ['reportid' => $reportid, 'directory' => '.']);
        try {
            foreach ($recordset as $record) {
                $key = $record->component . '/' . $record->filearea;
                if ((int) $record->outsidecount === 0 && !isset($labels[$key])) {
                    continue;
                }
                $allbytes = (int) $record->allbytes;
                $outsidebytes = (int) $record->outsidebytes;
                $rows[$key] = [
                    'name' => isset($labels[$key])
                        ? get_string($labels[$key], 'local_filexray') : $key,
                    'component' => $record->component,
                    'filearea' => $record->filearea,
                    'allcount' => (int) $record->allcount,
                    'allbytes' => $allbytes,
                    'allsize' => display_size($allbytes),
                    'outsidecount' => (int) $record->outsidecount,
                    'outsidebytes' => $outsidebytes,
                    'outsidesize' => display_size($outsidebytes),
                    'priority' => isset($labels[$key]) ? array_search($key, $priority, true) : 100,
                ];
            }
        } finally {
            $recordset->close();
        }

        // Always show these expected areas even when they contain no files.
        foreach ($labels as $key => $stringid) {
            if (isset($rows[$key])) {
                continue;
            }
            [$component, $filearea] = explode('/', $key, 2);
            $rows[$key] = [
                'name' => get_string($stringid, 'local_filexray'),
                'component' => $component,
                'filearea' => $filearea,
                'allcount' => 0,
                'allbytes' => 0,
                'allsize' => display_size(0),
                'outsidecount' => 0,
                'outsidebytes' => 0,
                'outsidesize' => display_size(0),
                'priority' => array_search($key, $priority, true),
            ];
        }
        $result = array_values($rows);
        usort($result, static function(array $left, array $right): int {
            return ($left['priority'] <=> $right['priority'])
                ?: ($right['outsidebytes'] <=> $left['outsidebytes'])
                ?: strcmp($left['name'], $right['name']);
        });
        foreach ($result as &$row) {
            unset($row['priority']);
        }
        unset($row);
        return $result;
    }
}
