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
 * Tests for file-area documentation and translated Moodle module names.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray;

use advanced_testcase;

/**
 * Verify documentation URLs and component labels.
 *
 * @covers \local_filexray\file_area_guide
 */
final class file_area_guide_test extends advanced_testcase {
    /**
     * Known file areas use the matching guide on the documentation site.
     */
    public function test_documentation_links_for_known_areas(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $language = strpos(current_language(), 'pt') === 0 ? 'pt_br' : 'en';
        $base = 'https://eduardokrausme.github.io/moodle-local_filexray/' .
            $language . '/recursos/';
        $this->assertSame($base . 'user-icon.html',
            file_area_guide::documentation_url('user', 'icon'));
        $this->assertSame($base . 'mod-folder.html',
            file_area_guide::documentation_url('mod_folder', 'content'));
        $this->assertSame($base . 'core-h5p.html',
            file_area_guide::documentation_url('core_h5p', 'libraries'));
        $this->assertSame($base . 'themes.html',
            file_area_guide::documentation_url('theme_degrade', 'logo'));
        $this->assertSame($base . 'core-admin.html',
            file_area_guide::documentation_url('core_admin', 'favicon'));
        $this->assertNull(file_area_guide::documentation_url('mod_resource', 'content'));
        $this->assertSame($base . 'areas.html',
            file_area_guide::documentation_url('mod_resource', 'content', true));
    }

    /**
     * Module components are displayed with Moodle's translated module name.
     */
    public function test_installed_module_uses_module_name(): void {
        $this->assertSame(get_string('modulename', 'mod_folder'),
            file_area_guide::component_name('mod_folder'));
        $this->assertSame('user', file_area_guide::component_name('user'));
        $this->assertSame('mod_unknown_plugin_xyz',
            file_area_guide::component_name('mod_unknown_plugin_xyz'));
    }
}
