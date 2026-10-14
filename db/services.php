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
 * AJAX service definitions.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_filexray_create_report' => [
        'classname' => 'local_filexray\\external\\create_report',
        'description' => 'Create an inventory report and enqueue its first course.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/filexray:run',
    ],
    'local_filexray_report_status' => [
        'classname' => 'local_filexray\\external\\report_status',
        'description' => 'Get report progress and a page of course statuses.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/filexray:view',
    ],
    'local_filexray_resume_report' => [
        'classname' => 'local_filexray\\external\\resume_report',
        'description' => 'Resume pending or abandoned inventory courses.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/filexray:run',
    ],
];
