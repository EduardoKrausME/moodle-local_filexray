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
 * Serve a report-scoped file inline or as a safe download.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

$id = required_param('id', PARAM_INT);
$coursejobid = required_param('coursejobid', PARAM_INT);
$fileid = required_param('fileid', PARAM_INT);
$downloadrequested = optional_param('download', 0, PARAM_BOOL);

require_login();
require_capability('local/filexray:view', context_system::instance());

$file = \local_filexray\file_preview::resolve($id, $coursejobid, $fileid);
$kind = \local_filexray\file_preview::kind($file->get_filename());
if ($kind === 'office' && !$downloadrequested) {
    redirect(new moodle_url('/local/filexray/office.php', [
        'id' => $id, 'coursejobid' => $coursejobid, 'fileid' => $fileid,
    ]));
}
// Download arbitrary active content (such as HTML/SVG) instead of rendering.
$download = $kind === 'download' || $downloadrequested || $kind === 'office';
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
\core\session\manager::write_close();
send_stored_file($file, 0, 0, $download, ['cacheability' => 'private']);
