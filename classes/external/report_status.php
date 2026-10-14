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
 * Bounded asynchronous report status endpoint.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Paginated status for polling; does not execute expensive backup work.
 */
final class report_status extends external_api {
    /**
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'reportid' => new external_value(PARAM_INT, 'Report ID'),
            'offset' => new external_value(PARAM_INT, 'Course index', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Page size', VALUE_DEFAULT, 50),
        ]);
    }

    /**
     * @param int $reportid Report ID.
     * @param int $offset Offset.
     * @param int $limit Page size.
     * @return array
     */
    public static function execute(int $reportid, int $offset = 0, int $limit = 50): array {
        $args = self::validate_parameters(self::execute_parameters(),
            ['reportid' => $reportid, 'offset' => $offset, 'limit' => $limit]);
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/filexray:view', $context);
        return \local_filexray\report_manager::status((int) $args['reportid'],
            max(0, (int) $args['offset']), max(1, min(100, (int) $args['limit'])));
    }

    /**
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'reportid' => new external_value(PARAM_INT, 'Report ID'),
            'status' => new external_value(PARAM_ALPHANUMEXT, 'Report status'),
            'total' => new external_value(PARAM_INT, 'Total courses'),
            'completed' => new external_value(PARAM_INT, 'Completed courses'),
            'failed' => new external_value(PARAM_INT, 'Failed courses'),
            'running' => new external_value(PARAM_INT, 'Running courses'),
            'pending' => new external_value(PARAM_INT, 'Pending courses'),
            'filecount' => new external_value(PARAM_INT, 'Matched file references'),
            'percent' => new external_value(PARAM_INT, 'Percentage processed'),
            'offset' => new external_value(PARAM_INT, 'Page offset'),
            'limit' => new external_value(PARAM_INT, 'Page limit'),
            'courses' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Snapshot row ID'),
                'courseid' => new external_value(PARAM_INT, 'Moodle course ID'),
                'name' => new external_value(PARAM_TEXT, 'Course full name'),
                'status' => new external_value(PARAM_ALPHANUMEXT, 'Course status'),
                'filecount' => new external_value(PARAM_INT, 'Referenced files'),
                'error' => new external_value(PARAM_TEXT, 'Error message'),
                'started' => new external_value(PARAM_INT, 'Started timestamp'),
                'finished' => new external_value(PARAM_INT, 'Finished timestamp'),
            ])),
        ]);
    }
}