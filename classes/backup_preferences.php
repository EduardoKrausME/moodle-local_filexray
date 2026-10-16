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
 * Control user data inclusion in the native backup inventory.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray;

use backup_plan;
use base_setting;
use coding_exception;
use moodle_exception;

/**
 * Apply File X-Ray preferences to Moodle's native course backup plan.
 */
final class backup_preferences {
    /**
     * Exclude user data only when explicitly requested by an administrator.
     *
     * @param backup_plan $plan Native backup plan.
     * @return void
     */
    public static function apply(backup_plan $plan): void {
        if (!get_config("local_filexray", "skipuserdata")) {
            return;
        }

        if (!$plan->setting_exists("users")) {
            throw new coding_exception("Native course backup has no users setting.");
        }

        $users = $plan->get_setting("users");
        if (!$users->get_value()) {
            // Moodle already excludes user data, even when the setting is locked.
            return;
        }

        if ($users->get_status() !== base_setting::NOT_LOCKED) {
            // Honour administrator or permission locks rather than bypassing them.
            throw new moodle_exception("userdatalocked", "local_filexray");
        }

        $users->set_value(0);
    }
}
