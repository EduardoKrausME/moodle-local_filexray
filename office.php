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
 * Consent-gated Google Docs Viewer for private Office documents.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_filexray\file_preview;

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$coursejobid = required_param('coursejobid', PARAM_INT);
$fileid = required_param('fileid', PARAM_INT);
$authorise = optional_param('authorise', 0, PARAM_BOOL);

require_login();
$context = context_system::instance();
require_capability('local/filexray:view', $context);
$file = file_preview::resolve($id, $coursejobid, $fileid);
if (file_preview::kind($file->get_filename()) !== 'office') {
    throw new moodle_exception('invalidpreview', 'local_filexray');
}

$url = new moodle_url('/local/filexray/office.php', [
    'id' => $id, 'coursejobid' => $coursejobid, 'fileid' => $fileid,
]);
$downloadurl = new moodle_url('/local/filexray/preview.php', [
    'id' => $id, 'coursejobid' => $coursejobid, 'fileid' => $fileid,
    'download' => 1,
]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('preview', 'local_filexray'));
$PAGE->set_heading(get_string('preview', 'local_filexray'));

// Google Docs Viewer must be able to fetch the file from the public internet.
// Never issue a temporary share URL on an insecure HTTP installation.
$https = strtolower((string) parse_url($CFG->wwwroot, PHP_URL_SCHEME)) === 'https';
if ($authorise) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new moodle_exception('invalidpreview', 'local_filexray');
    }
    require_sesskey();
    if (!$https) {
        throw new moodle_exception('officehttpsrequired', 'local_filexray');
    }
    $temporary = file_preview::office_url($id, $coursejobid, $fileid);
    $googleurl = new moodle_url('https://docs.google.com/gview', [
        'embedded' => 1,
        'url' => $temporary->out(false),
    ]);
}
header('Cache-Control: private, no-store');
header('Referrer-Policy: no-referrer');
$data = [
    'filename' => $file->get_filename(),
    'https' => $https,
    'authorised' => $authorise && $https,
    'officeurl' => $url->out(false),
    'downloadurl' => $downloadurl->out(false),
    'googleurl' => isset($googleurl) ? $googleurl->out(false) : '',
    'sesskey' => sesskey(),
];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_filexray/office', $data);
echo $OUTPUT->footer();
