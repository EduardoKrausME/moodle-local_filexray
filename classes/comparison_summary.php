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
 * Grouped course and file-area statistics for the comparison report.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray;

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

        $sql = "SELECT rc.id, rc.courseid, rc.coursename, c.fullname AS currentfullname, rc.status,
                       COALESCE(inventory.filecount, 0) AS filecount,
                       COALESCE(inventory.bytecount, 0) AS bytecount
                  FROM {local_filexray_course} rc
             LEFT JOIN (
                     SELECT reportcourseid, COUNT(*) AS filecount, SUM(filesize) AS bytecount
                       FROM {local_filexray_file}
                      WHERE reportid = :inventoryreport AND filename <> :directory
                   GROUP BY reportcourseid
                       ) inventory ON inventory.reportcourseid = rc.id
             LEFT JOIN {course} c ON c.id = rc.courseid
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
                'coursename' => self::safe_course_name_html($record->currentfullname ?? $record->coursename),
                'status' => $status,
                'filecount' => (int) $record->filecount,
                'bytecount' => $bytes,
                'size' => display_size($bytes),
            ];
        }
        return $courses;
    }

    /**
     * Count records and logical bytes in three exclusive coverage groups.
     *
     * Inventory references are deduplicated by Moodle file ID, because the
     * same stored file may belong to several course inventories.
     *
     * @param int $reportid Inventory report ID.
     * @return array Counts and logical bytes for each group.
     */
    public static function coverage(int $reportid): array {
        global $DB;

        $total = $DB->get_record_sql(
            "SELECT COUNT(*) AS filecount, COALESCE(SUM(filesize), 0) AS bytecount
               FROM {files}
              WHERE filename <> :directory",
            ["directory" => "."]
        );
        $matched = $DB->get_record_sql(
            "SELECT COUNT(*) AS filecount, COALESCE(SUM(f.filesize), 0) AS bytecount
               FROM {files} f
               JOIN (SELECT DISTINCT fileid FROM {local_filexray_file}
                      WHERE reportid = :reportid) selected ON selected.fileid = f.id
              WHERE f.filename <> :directory",
            ["reportid" => $reportid, "directory" => "."]
        );
        [$condition, $params] = self::unmatched_condition($reportid);
        $unmatched = $DB->get_record_sql(
            "SELECT COUNT(*) AS filecount, COALESCE(SUM(f.filesize), 0) AS bytecount
               FROM {files} f
              WHERE " . $condition,
            $params
        );

        $totalfiles = (int) $total->filecount;
        $totalbytes = (int) $total->bytecount;
        $matchedfiles = (int) $matched->filecount;
        $matchedbytes = (int) $matched->bytecount;
        $unmatchedfiles = (int) $unmatched->filecount;
        $unmatchedbytes = (int) $unmatched->bytecount;
        return [
            "totalfiles" => $totalfiles,
            "totalbytes" => $totalbytes,
            "matchedfiles" => $matchedfiles,
            "matchedbytes" => $matchedbytes,
            "unmatchedfiles" => $unmatchedfiles,
            "unmatchedbytes" => $unmatchedbytes,
            "classifiedfiles" => max(0, $totalfiles - $matchedfiles - $unmatchedfiles),
            "classifiedbytes" => max(0, $totalbytes - $matchedbytes - $unmatchedbytes),
        ];
    }

    /**
     * Rank the largest course inventories on the current page by logical bytes.
     *
     * @param array $courses Current page of course summary rows.
     * @return array At most five entries with chart widths and readable sizes.
     */
    public static function top_courses(array $courses): array {
        usort($courses, static function(array $first, array $second): int {
            return ($second["bytecount"] <=> $first["bytecount"])
                ?: ($second["filecount"] <=> $first["filecount"])
                ?: ($first["courseid"] <=> $second["courseid"]);
        });
        $top = array_slice($courses, 0, 5);
        $maxbytes = $top ? max(array_column($top, "bytecount")) : 0;
        foreach ($top as &$course) {
            $course["chartwidth"] = $maxbytes > 0
                ? round(100 * $course["bytecount"] / $maxbytes, 1) : 0;
            $course["formattedbytes"] = display_size($course["bytecount"]);
        }
        unset($course);
        return $top;
    }

    /**
     * Group a saved course inventory by component, without directory entries.
     *
     * @param int $coursejobid Saved inventory job ID.
     * @return array Components sorted by logical byte count.
     */
    public static function course_components(int $coursejobid): array {
        global $DB;

        $records = $DB->get_records_sql(
            "SELECT component, COUNT(*) AS filecount, COALESCE(SUM(filesize), 0) AS bytecount
               FROM {local_filexray_file}
              WHERE reportcourseid = :courseid AND filename <> :directory
           GROUP BY component
           ORDER BY bytecount DESC, component ASC",
            ["courseid" => $coursejobid, "directory" => "."]
        );
        $result = [];
        $maxfiles = 0;
        $maxbytes = 0;
        foreach ($records as $record) {
            $maxfiles = max($maxfiles, (int) $record->filecount);
            $maxbytes = max($maxbytes, (int) $record->bytecount);
        }
        foreach ($records as $record) {
            $filecount = (int) $record->filecount;
            $bytes = (int) $record->bytecount;
            $result[] = [
                "component" => $record->component,
                "filecount" => $filecount,
                "bytecount" => $bytes,
                "formattedfilecount" => number_format($filecount, 0, ",", "."),
                "size" => display_size($bytes),
                "fileswidth" => $maxfiles > 0 ? round(100 * $filecount / $maxfiles, 1) : 0,
                "byteswidth" => $maxbytes > 0 ? round(100 * $bytes / $maxbytes, 1) : 0,
            ];
        }
        return $result;
    }

    /**
     * Escape course names while retaining only the explicit multilang spans.
     *
     * Original course fullnames can contain HTML; the template intentionally
     * uses triple Mustache braces so content MUST be escaped here. Any other
     * HTML tag, including event handlers and scripts, is reduced to plain text.
     *
     * @param string $name Current fullname or stored fallback.
     * @return string HTML-safe name with optional multilang spans.
     */
    public static function safe_course_name_html(string $name): string {
        $cleantext = static function(string $value): string {
            return s(strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        };

        // Without multilang markup, do not pass arbitrary course HTML through.
        if (stripos($name, 'multilang') === false) {
            return $cleantext($name);
        }

        $pattern = '~<span\\b([^>]*)>(.*?)</span>~is';
        if (!preg_match_all($pattern, $name, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return $cleantext($name);
        }

        $html = '';
        $position = 0;
        foreach ($matches as $match) {
            $start = $match[0][1];
            $html .= $cleantext(substr($name, $position, $start - $position));
            $attributes = $match[1][0];
            $content = $cleantext($match[2][0]);
            $hasclass = preg_match('/\\bclass\\s*=\\s*([\'"])(.*?)\\1/is', $attributes, $classmatch)
                && preg_match('/(?:^|\\s)multilang(?:\\s|$)/i', $classmatch[2]);
            $haslanguage = preg_match('/\\blang\\s*=\\s*([\'"])([a-z]{2,3}(?:[-_][a-z]{2,8})?)\\1/i',
                $attributes, $langmatch);
            if ($hasclass && $haslanguage) {
                $html .= '<span class="multilang" lang="' . strtolower($langmatch[2]) . '">' . $content . '</span>';
            } else {
                $html .= $content;
            }
            $position = $start + strlen($match[0][0]);
        }
        $html .= $cleantext(substr($name, $position));
        return $html;
    }

    /**
     * Return a cross-database filter for files grouped as other areas.
     *
     * Apply the SAME filter to the area summary and to residual unmatched
     * files so one file record cannot appear in both tables.
     *
     * @param string $alias Table alias for mdl_files.
     * @return array SQL condition and named parameters.
     */
    public static function classified_condition(string $alias): array {
        global $DB;

        $condition = "({$alias}.component IN (:otheruser, :otherbadges, :otherbackup,
                 :otherh5p, :otheradmin)
             OR ({$alias}.component = :othercore AND {$alias}.filearea = :otherpreview)
             OR ({$alias}.component = :otherfolder AND {$alias}.filearea = :othercontent)
             OR ({$alias}.component = :otherrecycle AND {$alias}.filearea = :otherrecyclearea)
             OR ({$alias}.component = :othertiles AND {$alias}.filearea = :othertilephoto)
             OR ({$alias}.component = :othereditpdf AND {$alias}.filearea = :otherstamps)
             OR " . $DB->sql_substr($alias . '.component', 1, 6) . ' = :othertheme)';
        return [$condition, [
            'otheruser' => 'user',
            'otherbadges' => 'badges',
            'otherbackup' => 'backup',
            'otherh5p' => 'core_h5p',
            'otheradmin' => 'core_admin',
            'othercore' => 'core',
            'otherpreview' => 'preview',
            'otherfolder' => 'mod_folder',
            'othercontent' => 'content',
            'otherrecycle' => 'tool_recyclebin',
            'otherrecyclearea' => 'recyclebin_course',
            'othertiles' => 'format_tiles',
            'othertilephoto' => 'tilephoto',
            'othereditpdf' => 'assignfeedback_editpdf',
            'otherstamps' => 'stamps',
            'othertheme' => 'theme_',
        ]];
    }

    /**
     * Filter file IDs not selected by the given inventory and not already
     * listed under the separately classified file areas.
     *
     * @param int $reportid Report identifier.
     * @return array SQL condition and bound parameters.
     */
    public static function unmatched_condition(int $reportid): array {
        [$classifiedsql, $classifiedparams] = self::classified_condition('f');
        $where = "f.filename <> :dir
                  AND NOT EXISTS (
                      SELECT 1 FROM {local_filexray_file} fx
                       WHERE fx.reportid = :reportid AND fx.fileid = f.id
                  )
                  AND NOT " . $classifiedsql;

        return [$where, array_merge($classifiedparams, [
            'dir' => '.',
            'reportid' => $reportid,
        ])];
    }

    /**
     * Summarize non-course component areas and show one line for H5P and themes.
     *
     * @param int $reportid File X-Ray report ID.
     * @return array
     */
    public static function fileareas(int $reportid): array {
        global $DB;

        [$condition, $classificationparams] = self::classified_condition('f');
        // Distinct file IDs avoid joining one Moodle file multiple times
        // when more than one course backup inventories the same file.
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
                 WHERE " . $condition . "
              GROUP BY f.component, f.filearea";

        // Explicitly described categories are followed by any remaining
        // user or badge areas with files outside this course inventory.
        $labels = [
            'user/draft' => ['areadraft', 'areadraft_desc'],
            'user/backup' => ['areauserbackup', 'areauserbackup_desc'],
            'user/icon' => ['areaavatar', 'areaavatar_desc'],
            'badges/badgeimage' => ['areabadgeimage', 'areabadgeimage_desc'],
            'backup/*' => ['areabackup', 'areabackup_desc'],
            'core_h5p/*' => ['areacoreh5p', 'areacoreh5p_desc'],
            'theme_*/*' => ['areathemes', 'areathemes_desc'],
            'user/private' => ['areaprivate', 'areaprivate_desc'],
            'user/profile' => ['areaprofile', 'areaprofile_desc'],
        ];
        $priority = array_keys($labels);
        $totals = [
            'backup' => ['files' => 0, 'bytes' => 0, 'outsidefiles' => 0, 'outsidebytes' => 0, 'areas' => []],
            'core_h5p' => ['files' => 0, 'bytes' => 0, 'outsidefiles' => 0, 'outsidebytes' => 0, 'areas' => []],
            'theme_*' => ['files' => 0, 'bytes' => 0, 'outsidefiles' => 0, 'outsidebytes' => 0, 'areas' => []],
        ];

        $rows = [];
        $params = array_merge($classificationparams, [
            'directory' => '.',
            'outside_directory' => '.',
            'reportid' => $reportid,
        ]);
        $rs = $DB->get_recordset_sql($sql, $params);
        try {
            foreach ($rs as $record) {
                $component = $record->component;
                $filearea = $record->filearea;
                $count = (int) $record->allcount;
                $bytes = (int) $record->allbytes;
                $outsidecount = (int) $record->outsidecount;
                $outsidebytes = (int) $record->outsidebytes;
                $key = $component . '/' . $filearea;

                $group = null;
                if ($component === 'backup' || $component === 'core_h5p') {
                    $group = $component;
                } else if (strpos($component, 'theme_') === 0) {
                    $group = 'theme_*';
                }
                if ($group !== null) {
                    $totals[$group]['files'] += $count;
                    $totals[$group]['bytes'] += $bytes;
                    $totals[$group]['outsidefiles'] += $outsidecount;
                    $totals[$group]['outsidebytes'] += $outsidebytes;
                    if ($count > 0) {
                        $totals[$group]['areas'][$filearea] = true;
                    }
                }

                // Core_h5p and every theme_* component appear only in their..
                // consolidated row, never as duplicate per-area entries.
                if ($component === 'core_h5p' || $group === 'theme_*') {
                    continue;
                }
                if ($count === 0 || ($outsidecount === 0 && !isset($labels[$key]))) {
                    continue;
                }
                $rows[$key] = self::area_row(
                    $component,
                    $filearea,
                    $count,
                    $bytes,
                    $outsidecount,
                    $outsidebytes,
                    $labels[$key] ?? null,
                    isset($labels[$key]) ? (int) array_search($key, $priority, true) : 100
                );
            }
        } finally {
            $rs->close();
        }

        // All backup areas remain a roll-up as before. Core H5P shows every
        // existing filearea, whereas themes show at most five and "etc...".
        foreach ($totals as $component => $aggregate) {
            if ($aggregate['files'] === 0) {
                continue;
            }
            $key = $component . '/*';
            $areas = array_keys($aggregate['areas']);
            sort($areas, SORT_STRING);
            if ($component === 'theme_*' && count($areas) > 5) {
                $displayareas = implode(' / ', array_slice($areas, 0, 5)) . ' / etc...';
            } else if ($component === 'backup') {
                $displayareas = '*';
            } else {
                $displayareas = implode(' / ', $areas);
            }
            $rows[$key] = self::area_row(
                $component,
                $displayareas,
                $aggregate['files'],
                $aggregate['bytes'],
                $aggregate['outsidefiles'],
                $aggregate['outsidebytes'],
                $labels[$key],
                (int) array_search($key, $priority, true),
                true
            );
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
     * Prepare an individual file-area or component summary row.
     *
     * @param string $component Moodle component.
     * @param string $filearea Stored area or summary of areas.
     * @param int $count File count.
     * @param int $bytes Logical bytes.
     * @param int $outsidecount Unreferenced-by-inventory file records.
     * @param int $outsidebytes Logical bytes outside the course inventory.
     * @param array|null $labelids Optional label and explanation keys.
     * @param int $priority Sort priority.
     * @param bool $iscomponent Whether row totals a component or group.
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
        int $priority,
        bool $iscomponent = false
    ): array {
        return [
            'name' => $labelids ? get_string($labelids[0], 'local_filexray')
                : $component . '/' . $filearea,
            'description' => $labelids ? get_string($labelids[1], 'local_filexray')
                : get_string('areaother_desc', 'local_filexray'),
            'component' => $component,
            'filearea' => $filearea,
            'iscomponent' => $iscomponent || $filearea === '*',
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
