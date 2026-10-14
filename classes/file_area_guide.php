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
 * Documentation links and labels for inventoried Moodle file areas.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray;

use core_component;

/**
 * Shared file-area documentation mapping for course and comparison tables.
 */
final class file_area_guide {
    /**
     * Return a published guide URL, if available for the file area.
     *
     * @param string $component Moodle component.
     * @param string $filearea File area or a consolidated area label.
     * @param bool $fallback Whether to link unknown areas to the overview.
     * @return string|null
     */
    public static function documentation_url(
        string $component,
        string $filearea,
        bool $fallback = false
    ): ?string {
        $guides = [
            'user/draft' => 'user-draft',
            'user/backup' => 'user-backup',
            'user/icon' => 'user-icon',
            'user/private' => 'user-private',
            'user/profile' => 'user-profile',
            'core/preview' => 'core-preview',
            'mod_folder/content' => 'mod-folder',
            'tool_recyclebin/recyclebin_course' => 'recycle-bin',
            'format_tiles/tilephoto' => 'format-tiles',
            'assignfeedback_editpdf/stamps' => 'editpdf-stamps',
            'badges/badgeimage' => 'badges',
        ];

        if ($component === 'core_h5p') {
            $guide = 'core-h5p';
        } else if ($component === 'backup') {
            $guide = 'backup';
        } else if ($component === 'core_admin') {
            $guide = 'core-admin';
        } else if ($component === 'theme_*' || strpos($component, 'theme_') === 0) {
            $guide = 'themes';
        } else {
            $guide = $guides[$component . '/' . $filearea] ?? null;
        }

        if ($guide === null && !$fallback) {
            return null;
        }

        $language = strpos(current_language(), 'pt') === 0 ? 'pt_br' : 'en';
        return 'https://eduardokrausme.github.io/moodle-local_filexray/' .
            $language . '/recursos/' . ($guide ?? 'areas') . '.html';
    }

    /**
     * Display a readable, translated activity module name if installed.
     *
     * @param string $component File component.
     * @return string
     */
    public static function component_name(string $component): string {
        if (strpos($component, 'mod_') === 0) {
            $module = substr($component, 4);
            // A backup may outlive the plugin that originally created this file.
            $installed = core_component::get_plugin_list('mod');
            if (isset($installed[$module]) &&
                    get_string_manager()->string_exists('modulename', $component)) {
                return get_string('modulename', $component);
            }
        }

        return $component;
    }
}
