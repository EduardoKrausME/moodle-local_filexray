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
 * Serve a signed short-lived Google Viewer file fetch.
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
$expires = required_param('expires', PARAM_INT);
$nonce = required_param('nonce', PARAM_ALPHANUMEXT);
$signature = required_param('sig', PARAM_ALPHANUMEXT);

require_login();
$context = context_system::instance();
require_capability('local/filexray:view', $context);

// This route intentionally has no Moodle login. Access is granted only by a
// short-lived HMAC created after an administrator has confirmed disclosure.
try {
    $file = \local_filexray\file_preview::verify_office(
        $id, $coursejobid, $fileid, $expires, $nonce, $signature
    );
} catch (\Throwable $exception) {
    send_file_not_found();
}
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
\core\session\manager::write_close();
send_stored_file($file, 0, 0, false, ['cacheability' => 'private']);
