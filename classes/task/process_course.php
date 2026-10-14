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
 * Asynchronous one-course task.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Inventories precisely one course per cron execution.
 */
final class process_course extends \core\task\adhoc_task {
    /**
     * @return void
     */
    public function execute(): void {
        $data = $this->get_custom_data();
        if (empty($data->reportid)) {
            throw new \coding_exception('Missing reportid in File X-Ray task.');
        }
        \local_filexray\runner::one((int) $data->reportid);
    }
}
