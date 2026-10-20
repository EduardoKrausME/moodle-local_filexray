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
 * MCP integration: classes/mcp/write_tool.php.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray\mcp;

use context;
use context_system;
use local_filexray\report_manager;
use local_filexray\runner;
use local_mcp\security\authenticated_identity;
use local_mcp\security\capability_guard;
use local_mcp\write\tool\base_tool;
use moodle_url;

/**
 * Confirmed administrative actions using File X-Ray's existing services.
 */
final class write_tool extends base_tool {
    /** @var string */
    private string $operation;

    /**
     * Store the selected MCP operation.
     *
     * @param string $operation Supported write operation.
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
            "create_report" => "Create a File X-Ray snapshot and queue cron; retention may evict older reports.",
            "resume_report" => "Retry failed inventory jobs and resume processing via cron.",
            "delete_report" => "Delete File X-Ray report data only; never Moodle stored files.",
        };
    }

    /**
     * Return the system-level capability.
     *
     * @return string
     */
    public function get_required_capability(): string {
        return "local/filexray:run";
    }

    /**
     * Define the MCP input arguments.
     *
     * @return array
     */
    public function get_input_schema(): array {
        return $this->operation === "create_report"
            ? $this->object_schema([])
            : $this->object_schema([
                "reportid" => ["type" => "integer", "minimum" => 1],
            ], ["reportid"]);
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
     * Allow a confirmation preview before writing.
     *
     * @return bool
     */
    public function supports_dry_run(): bool {
        return true;
    }

    /**
     * Identify tools which can remove stored snapshot records.
     *
     * @return bool
     */
    public function is_destructive(): bool {
        // Creation may evict an older snapshot due to retention.
        return $this->operation !== "resume_report";
    }

    /**
     * Describe the operation without modifying any records.
     *
     * @param array $arguments
     * @param authenticated_identity $identity
     * @return array
     */
    public function preview(array $arguments, authenticated_identity $identity): array {
        global $DB;
        capability_guard::check($this->get_required_capability(),
            $this->resolve_context($arguments), $identity->userid);
        if ($this->operation === "create_report") {
            $configured = get_config("local_filexray", "maxreports");
            $maximum = max(1, is_numeric($configured) ? (int) $configured : 10);
            $total = $DB->count_records("local_filexray_report");
            $oldest = $DB->get_records("local_filexray_report", [],
                "timecreated ASC, id ASC", "id", 0, 1);
            return [
                "coursecount" => (int) $DB->count_records_select("course",
                    "id <> :siteid", ["siteid" => SITEID]),
                "maxreports" => $maximum,
                "oldest_report_may_be_deleted" => $total >= $maximum && $oldest
                    ? (int) reset($oldest)->id : null,
                "effects" => "Creates a snapshot, may evict old snapshots, and queues cron.",
                "requires_confirmation" => true,
            ];
        }
        $reportid = (int) $arguments["reportid"];
        $report = report_manager::get($reportid);
        return [
            "reportid" => $reportid, "status" => $report->status,
            "totalcourses" => (int) $report->totalcourses,
            "failedcourses" => (int) $DB->count_records("local_filexray_course",
                ["reportid" => $reportid, "status" => "failed"]),
            "effects" => $this->operation === "delete_report"
                ? "Permanently deletes only File X-Ray report, course and inventory rows; not Moodle files."
                : "Resets failed course inventories and queues cron; no Moodle files are altered.",
            "requires_confirmation" => true,
        ];
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
        if ($this->operation === "create_report") {
            $reportid = report_manager::create($identity->userid);
            return ["reportid" => $reportid, "status" => report_manager::get($reportid)->status,
                "url" => (new moodle_url("/local/filexray/report.php",
                    ["id" => $reportid]))->out(false)];
        }
        $reportid = (int) $arguments["reportid"];
        if ($this->operation === "resume_report") {
            $retried = report_manager::retry_failed($reportid);
            runner::enqueue($reportid);
            return ["reportid" => $reportid, "retriedcourses" => $retried, "queued" => true];
        }
        report_manager::delete($reportid);
        return ["reportid" => $reportid, "deleted" => true, "moodle_files_deleted" => false];
    }
}
