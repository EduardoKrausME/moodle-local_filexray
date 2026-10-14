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
 * File X-Ray database upgrades.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Apply database schema upgrades for File X-Ray.
 *
 * @param int $oldversion Previously installed plugin version.
 * @return bool
 */
function xmldb_local_filexray_upgrade(int $oldversion): bool {
    global $DB;

    if ($oldversion < 2026101401) {
        // Course names may contain long multilang HTML. Preserve the entire value.
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_filexray_course');
        $field = new xmldb_field('coursename', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'courseid');
        $dbman->change_field_type($table, $field);

        upgrade_plugin_savepoint(true, 2026101401, 'local', 'filexray');
    }

    return true;
}
