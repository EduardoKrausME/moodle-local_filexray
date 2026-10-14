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
 * Administrator navigation.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_filexray_settings', get_string('pluginname', 'local_filexray'));
    $settings->add(new admin_setting_configtext(
        'local_filexray/maxreports',
        get_string('maxreports', 'local_filexray'),
        get_string('maxreports_desc', 'local_filexray'),
        5,
        PARAM_INT
    ));
    $ADMIN->add('localplugins', $settings);


    $ADMIN->add('reports', new admin_externalpage(
        'local_filexray',
        get_string('pluginname', 'local_filexray'),
        new moodle_url('/local/filexray/index.php'),
        'local/filexray:view'
    ));
}
