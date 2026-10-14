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
     * Group current mdl_files records by component and filearea. Selected
     * components also receive a single roll-up across all their file areas.
     *
     * @param int $reportid File X-Ray report ID.
     * @return array
     */
    public static function fileareas(int $reportid): array {
        global $DB;

        // Distinct file IDs prevent joining the same mdl_files row several
        // times when it belongs to backups from more than one course.
        // SUM(filesize) includes directory records (normally zero bytes),
        // matching an unfiltered SUM(filesize) for each selected component.
        $sql = "SELECT f.component, f.filearea,
                       SUM(CASE WHEN f.filename <> :directory THEN 1 ELSE 0 END) AS allcount,
                       COALESCE(SUM(f.filesize), 0) AS allbytes,
                       SUM(CASE WHEN selected.fileid IS NULL AND f.filename <> :outside_directory
                                THEN 1 ELSE 0 END) AS outsidecount,
                       COALESCE(SUM(CASE WHEN selected.fileid IS NULL
                                    THEN f.filesize ELSE 0 END), 0) AS outsidebytes
                  FROM {files} f
             LEFT JOIN (
                     SELECT DISTINCT fileid
                       FROM {local_filexray_file}
                      WHERE reportid = :reportid
                       ) selected ON selected.fileid = f.id
              GROUP BY f.component, f.filearea";

        // Explicit rows show the exact scopes requested by the administrator.
        // A component/* row is an overlapping total, never an additional category.
        $labels = [
            'user/draft' => ['areadraft', 'areadraft_desc'],
            'user/backup' => ['areauserbackup', 'areauserbackup_desc'],
            'user/icon' => ['areaavatar', 'areaavatar_desc'],
            'badges/badgeimage' => ['areabadgeimage', 'areabadgeimage_desc'],
            'backup/*' => ['areabackup', 'areabackup_desc'],
            'core_h5p/*' => ['areacoreh5p', 'areacoreh5p_desc'],
            'user/private' => ['areaprivate', 'areaprivate_desc'],
            'user/profile' => ['areaprofile', 'areaprofile_desc'],
        ];
        $order = array_keys($labels);
        $componenttotals = [
            'backup' => ['allcount' => 0, 'allbytes' => 0, 'outsidecount' => 0, 'outsidebytes' => 0],
            'core_h5p' => ['allcount' => 0, 'allbytes' => 0, 'outsidecount' => 0, 'outsidebytes' => 0],
        ];
        $rows = [];
        $rs = $DB->get_recordset_sql($sql, [
            'directory' => '.',
            'outside_directory' => '.',
            'reportid' => $reportid,
        ]);
        try {
            foreach ($rs as $record) {
                $key = $record->component . '/' . $record->filearea;
                $count = (int) $record->allcount;
                $bytes = (int) $record->allbytes;
                $outsidecount = (int) $record->outsidecount;
                $outsidebytes = (int) $record->outsidebytes;

                // Accumulate ALL subareas, including those that appear in course backups.
                if (array_key_exists($record->component, $componenttotals)) {
                    $componenttotals[$record->component]['allcount'] += $count;
                    $componenttotals[$record->component]['allbytes'] += $bytes;
                    $componenttotals[$record->component]['outsidecount'] += $outsidecount;
                    $componenttotals[$record->component]['outsidebytes'] += $outsidebytes;
                }

                if ($outsidecount === 0 && !isset($labels[$key])) {
                    continue;
                }
                $rows[$key] = self::area_row(
                    $record->component,
                    $record->filearea,
                    $count,
                    $bytes,
                    $outsidecount,
                    $outsidebytes,
                    $labels[$key] ?? null,
                    isset($labels[$key]) ? (int) array_search($key, $order, true) : 100
                );
            }
        } finally {
            $rs->close();
        }

        // Component-only totals correspond to WHERE component = 'backup' or 'core_h5p'.
        foreach ($componenttotals as $component => $totals) {
            $key = $component . '/*';
            $rows[$key] = self::area_row(
                $component,
                '*',
                $totals['allcount'],
                $totals['allbytes'],
                $totals['outsidecount'],
                $totals['outsidebytes'],
                $labels[$key],
                (int) array_search($key, $order, true)
            );
        }

        // Show the specifically requested areas even on an empty installation.
        foreach ($labels as $key => $labelids) {
            if (isset($rows[$key])) {
                continue;
            }
            [$component, $filearea] = explode('/', $key, 2);
            $rows[$key] = self::area_row($component, $filearea, 0, 0, 0, 0,
                $labelids, (int) array_search($key, $order, true));
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

    /**
     * Prepare the presentation of a single file area or component-wide total.
     *
     * @param string $component File component.
     * @param string $filearea File area or * for all.
     * @param int $count Total non-directory files.
     * @param int $bytes Total logical bytes.
     * @param int $outsidecount Non-directory files outside the backup inventory.
     * @param int $outsidebytes Logical bytes outside the inventory.
     * @param array|null $labelids Optional category title and explanation string keys.
     * @param int $priority Sort priority.
     * @return array
     */
    private static function area_row(
        string $component,
        string $filearea,
        int $count,
        int $bytes,
        int $outsidecount,
        int $outsidebytes,
        ?array $labelids,
        int $priority
    ): array {
        return [
            'name' => $labelids ? get_string($labelids[0], 'local_filexray')
                : $component . '/' . $filearea,
            'description' => $labelids ? get_string($labelids[1], 'local_filexray')
                : get_string('areaother_desc', 'local_filexray'),
            'component' => $component,
            'filearea' => $filearea,
            'iscomponent' => $filearea === '*',
            'allcount' => $count,
            'allbytes' => $bytes,
            'allsize' => display_size($bytes),
            'outsidecount' => $outsidecount,
            'outsidebytes' => $outsidebytes,
            'outsidesize' => display_size($outsidebytes),
            'priority' => $priority,
        ];
    }
}
