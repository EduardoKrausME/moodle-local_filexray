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
 * Tests for native backup user-data preferences.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray;

use advanced_testcase;
use backup;
use backup_controller;
use base_setting;
use moodle_exception;

/**
 * Verifies native backup settings in both inventory modes.
 *
 * @covers \\local_filexray\\backup_preferences
 */
final class backup_preferences_test extends advanced_testcase {
    /**
     * Create a backup with an unlocked and enabled user-data default.
     *
     * @return backup_controller
     */
    private function new_controller(): backup_controller {
        global $CFG;

        require_once($CFG->dirroot . "/backup/util/includes/backup_includes.php");
        $this->setAdminUser();
        set_config("backup_general_users", 1, "backup");
        set_config("backup_general_users_locked", 0, "backup");
        $course = $this->getDataGenerator()->create_course();
        return new backup_controller(
            backup::TYPE_1COURSE, $course->id, backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO, backup::MODE_GENERAL, get_admin()->id
        );
    }

    /**
     * Unchecked option preserves Moodle's existing backup choices.
     */
    public function test_default_does_not_change_users_setting(): void {
        $this->resetAfterTest();
        set_config("skipuserdata", 0, "local_filexray");
        $controller = $this->new_controller();
        try {
            $plan = $controller->get_plan();
            $before = $plan->get_setting("users")->get_value();
            backup_preferences::apply($plan);
            $this->assertEquals($before, $plan->get_setting("users")->get_value());
        } finally {
            $controller->destroy();
        }
    }

    /**
     * Fast mode disables Moodle's user-data backup before running tasks.
     */
    public function test_fast_mode_disables_users_setting(): void {
        $this->resetAfterTest();
        set_config("skipuserdata", 1, "local_filexray");
        $controller = $this->new_controller();
        try {
            $plan = $controller->get_plan();
            $this->assertEquals(1, $plan->get_setting("users")->get_value());
            backup_preferences::apply($plan);
            $this->assertEquals(0, $plan->get_setting("users")->get_value());
        } finally {
            $controller->destroy();
        }
    }

    /**
     * A locked, enabled native backup choice must not be silently overridden.
     */
    public function test_fast_mode_rejects_locked_users_setting(): void {
        $this->resetAfterTest();
        set_config("skipuserdata", 1, "local_filexray");
        $controller = $this->new_controller();
        try {
            $users = $controller->get_plan()->get_setting("users");
            $users->set_status(base_setting::LOCKED_BY_CONFIG);
            $this->expectException(moodle_exception::class);
            backup_preferences::apply($controller->get_plan());
        } finally {
            $controller->destroy();
        }
    }

    /**
     * Disabled user data needs no mutation, even with a native lock.
     */
    public function test_fast_mode_accepts_users_already_disabled(): void {
        $this->resetAfterTest();
        set_config("skipuserdata", 1, "local_filexray");
        $controller = $this->new_controller();
        try {
            $users = $controller->get_plan()->get_setting("users");
            $users->set_value(0);
            $users->set_status(base_setting::LOCKED_BY_CONFIG);
            backup_preferences::apply($controller->get_plan());
            $this->assertEquals(0, $users->get_value());
        } finally {
            $controller->destroy();
        }
    }
}
