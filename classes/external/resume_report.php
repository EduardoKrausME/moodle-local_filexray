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
 * AJAX resume endpoint for interrupted reports.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_filexray\report_manager;
use local_filexray\runner;
use required_capability_exception;

/**
 * Enqueue a recovery attempt; the worker lock guards against duplicates.
 */
final class resume_report extends external_api {

    /**
     * execute_parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'reportid' => new external_value(PARAM_INT, 'Report ID'),
        ]);
    }

    /**
     * execute
     *
     * @param int $reportid
     * @return true[]
     * @throws required_capability_exception
     */
    public static function execute(int $reportid): array {
        $args = self::validate_parameters(self::execute_parameters(), ['reportid' => $reportid]);
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/filexray:run', $context);
        // Failed rows are terminal until an administrator requests a retry.
        // Preserve the report ID, so the existing progress page can continue.
        report_manager::retry_failed((int) $args['reportid']);
        runner::enqueue((int) $args['reportid']);
        return ['success' => true];
    }

    /**
     * execute_returns
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Queued'),
        ]);
    }
}
