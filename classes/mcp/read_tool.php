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
 * MCP integration: classes/mcp/read_tool.php.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray\mcp;

use context;
use context_system;
use local_mcp\read\tool\base_tool;
use local_mcp\security\authenticated_identity;
use local_mcp\security\capability_guard;

/**
 * Expose bounded File X-Ray metadata to MCP READ clients.
 */
final class read_tool extends base_tool {
    /** @var string */
    private string $operation;

    /**
     * Store the selected MCP operation.
     *
     * @param string $operation Supported read operation.
     */
    public function __construct(string $operation) {
        $this->operation = $operation;
    }

    /**
     * Return the MCP tool name.
     *
     * @return string
     */
    public function get_name(): string {
        return "filexray_" . $this->operation;
    }

    /**
     * Return the MCP tool display title.
     *
     * @return string
     */
    public function get_title(): string {
        return "File X-Ray: " . str_replace("_", " ", $this->operation);
    }

    /**
     * Describe the MCP tool operation.
     *
     * @return string
     */
    public function get_description(): string {
        return match ($this->operation) {
            "list_reports" => "List persisted File X-Ray reports.",
            "report_status" => "Read report progress and course processing statuses.",
            "list_course_files" => "List stored file metadata for one course inventory; no raw file contents.",
            "get_comparison" => "Compare inventories to Moodle file records; unmatched is not proof of orphaned files.",
            "list_unmatched_files" => "List residual file records; never interpret these as safe-to-delete files.",
        };
    }

    /**
     * Return the system-level capability.
     *
     * @return string
     */
    public function get_required_capability(): string {
        return "local/filexray:view";
    }

    /**
     * Define the MCP input arguments.
     *
     * @return array
     */
    public function get_input_schema(): array {
        $fields = [];
        $required = [];
        if ($this->operation !== "list_reports") {
            $fields["reportid"] = ["type" => "integer", "minimum" => 1];
            $required[] = "reportid";
        }
        if ($this->operation === "list_course_files") {
            $fields["coursejobid"] = ["type" => "integer", "minimum" => 1];
            $required[] = "coursejobid";
        }
        $fields["offset"] = ["type" => "integer", "minimum" => 0];
        $fields["limit"] = ["type" => "integer", "minimum" => 1, "maximum" => 100];
        if ($this->operation === "get_comparison") {
            $fields["includeareas"] = ["type" => "boolean"];
        }
        return $this->object_schema($fields, $required);
    }

    /**
     * Resolve the system context for capability enforcement.
     *
     * @param array $arguments
     * @return context
     */
    public function resolve_context(array $arguments): context {
        return context_system::instance();
    }

    /**
     * Execute the requested operation after authorization.
     *
     * @param array $arguments
     * @param authenticated_identity $identity
     * @return array
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        capability_guard::check($this->get_required_capability(),
            $this->resolve_context($arguments), $identity->userid);
        $offset = max(0, (int) ($arguments["offset"] ?? 0));
        $limit = max(1, min(100, (int) ($arguments["limit"] ?? 50)));
        if ($this->operation === "list_reports") {
            return service::list_reports($offset, $limit);
        }
        $reportid = (int) $arguments["reportid"];
        return match ($this->operation) {
            "report_status" => service::report_status($reportid, $offset, $limit),
            "list_course_files" => service::course_files(
                $reportid, (int) $arguments["coursejobid"], $offset, $limit),
            "get_comparison" => service::comparison(
                $reportid, $offset, $limit, !empty($arguments["includeareas"])),
            "list_unmatched_files" => service::unmatched_files($reportid, $offset, $limit),
        };
    }
}
