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
 * MCP integration: classes/mcp/provider.php.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray\mcp;

use local_mcp\extension\read_provider_interface;
use local_mcp\extension\write_provider_interface;

/**
 * Optional provider discovered automatically by local_mcp.
 */
final class provider implements read_provider_interface, write_provider_interface {
    /**
     * Register the available read operations.
     *
     * @return array
     */
    public function get_read_tools(): array {
        return [
            new read_tool("list_reports"),
            new read_tool("report_status"),
            new read_tool("list_course_files"),
            new read_tool("get_comparison"),
            new read_tool("list_unmatched_files"),
        ];
    }

    /**
     * Register the available write operations.
     *
     * @return array
     */
    public function get_write_tools(): array {
        return [
            new write_tool("create_report"),
            new write_tool("resume_report"),
            new write_tool("delete_report"),
        ];
    }
}
