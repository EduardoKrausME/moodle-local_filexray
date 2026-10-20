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
 * PHPUnit coverage for the optional File X-Ray MCP provider.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray;

use advanced_testcase;
use core_component;
use local_filexray\mcp\provider;
use local_filexray\mcp\read_tool;
use local_filexray\mcp\write_tool;
use local_mcp\security\authenticated_identity;

/**
 * Optional MCP extension contract, authorization and snapshot boundaries.
 *
 * @covers \local_filexray\mcp\provider
 * @covers \local_filexray\mcp\read_tool
 * @covers \local_filexray\mcp\write_tool
 * @covers \local_filexray\mcp\service
 */
final class mcp_provider_test extends advanced_testcase {
    /**
     * Skip when the optional MCP integration is not installed.
     *
     * @return void
     */
    private function require_mcp(): void {
        if (!core_component::get_plugin_directory("local", "mcp")) {
            $this->markTestSkipped("Moodle MCP is not installed.");
        }
    }

    /**
     * Ensure that MCP tools expose unique names and safe write contracts.
     *
     * @return void
     */
    public function test_provider_tools_are_unique_and_write_tools_require_confirmation(): void {
        $this->require_mcp();
        $provider = new provider();
        $read = $provider->get_read_tools();
        $write = $provider->get_write_tools();
        $this->assertCount(5, $read);
        $this->assertCount(3, $write);
        $names = [];
        foreach (array_merge($read, $write) as $tool) {
            $names[] = $tool->get_name();
            $this->assertSame("object", $tool->get_input_schema()["type"]);
            $this->assertSame(false, $tool->get_name() === "");
        }
        $this->assertSame(count($names), count(array_unique($names)));
        foreach ($write as $tool) {
            $this->assertTrue($tool->supports_dry_run());
            $this->assertTrue($tool->requires_confirmation());
        }
        $this->assertTrue($write[0]->is_destructive());
        $this->assertFalse($write[1]->is_destructive());
        $this->assertTrue($write[2]->is_destructive());
    }

    /**
     * List report snapshots and read progress as administrator.
     *
     * @return void
     */
    public function test_admin_can_read_reports_with_bounded_pagination(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $identity = new authenticated_identity((int) get_admin()->id, "test", []);
        $id = report_manager::create($identity->userid);
        $tool = new read_tool("list_reports");
        $result = $tool->execute(["limit" => 1], $identity);
        $this->assertSame(1, $result["limit"]);
        $this->assertSame($id, $result["reports"][0]["id"]);
        $this->assertGreaterThanOrEqual(1, $result["total"]);
        $status = new read_tool("report_status");
        $this->assertSame($id, $status->execute(["reportid" => $id], $identity)["reportid"]);
    }

    /**
     * Reject read access without the required system capability.
     *
     * @return void
     */
    public function test_read_rejects_identity_without_filexray_capability(): void {
        $this->require_mcp();
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $tool = new read_tool("list_reports");
        $this->expectException(\local_mcp\exception\api_exception::class);
        $tool->execute([], new authenticated_identity((int) $user->id, "test", []));
    }

    /**
     * Prevent reading course inventories through another report.
     *
     * @return void
     */
    public function test_inventory_cannot_be_read_under_another_report_id(): void {
        global $DB;
        $this->require_mcp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->getDataGenerator()->create_course();
        $identity = new authenticated_identity((int) get_admin()->id, "test", []);
        $first = report_manager::create($identity->userid);
        $second = report_manager::create($identity->userid);
        $job = $DB->get_record("local_filexray_course", ["reportid" => $first],
            "*", MUST_EXIST);
        $tool = new read_tool("list_course_files");
        $result = $tool->execute(["reportid" => $first, "coursejobid" => (int) $job->id],
            $identity);
        $this->assertSame(0, $result["total"]);
        $this->expectException(\dml_missing_record_exception::class);
        $tool->execute(["reportid" => $second, "coursejobid" => (int) $job->id],
            $identity);
    }

    /**
     * Preview write operations without changing database records.
     *
     * @return void
     */
    public function test_preview_does_not_modify_existing_reports(): void {
        global $DB;
        $this->require_mcp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $identity = new authenticated_identity((int) get_admin()->id, "test", []);
        $id = report_manager::create($identity->userid);
        $before = $DB->count_records("local_filexray_report");
        $create = new write_tool("create_report");
        $this->assertTrue($create->preview([], $identity)["requires_confirmation"]);
        $delete = new write_tool("delete_report");
        $this->assertSame($id,
            $delete->preview(["reportid" => $id], $identity)["reportid"]);
        $this->assertSame($before, $DB->count_records("local_filexray_report"));
    }

    /**
     * Delete report snapshots without modifying Moodle file records.
     *
     * @return void
     */
    public function test_write_tool_deletes_only_filexray_snapshot_records(): void {
        global $DB;
        $this->require_mcp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $identity = new authenticated_identity((int) get_admin()->id, "test", []);
        $id = report_manager::create($identity->userid);
        $filesbefore = $DB->count_records("files");
        $delete = new write_tool("delete_report");
        $result = $delete->execute(["reportid" => $id], $identity);
        $this->assertTrue($result["deleted"]);
        $this->assertFalse($DB->record_exists("local_filexray_report", ["id" => $id]));
        $this->assertSame($filesbefore, $DB->count_records("files"));
    }
}
