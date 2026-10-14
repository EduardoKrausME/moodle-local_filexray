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
 * Secure report-scoped file preview handling.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_filexray;

use moodle_exception;
use moodle_url;
use stored_file;

/**
 * Secure access to saved inventory entries and short-lived Office previews.
 */
final class file_preview {
    /** Temporary Google Viewer fetch URL lifespan in seconds. */
    private const OFFICE_LIFETIME = 600;

    /**
     * Choose the safest supported preview for a known file extension.
     *
     * @param string $filename Filename.
     * @return string image, pdf, media, office or download.
     */
    public static function kind(string $filename): string {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'avif'], true)) {
            return 'image';
        }
        if ($extension === 'pdf') {
            return 'pdf';
        }
        if (in_array($extension, ['mp3', 'ogg', 'wav', 'm4a', 'mp4', 'webm'], true)) {
            return 'media';
        }
        if (in_array($extension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp'], true)) {
            return 'office';
        }
        // Never render arbitrary HTML, SVG or script files inline.
        return 'download';
    }

    /**
     * Resolve a current Moodle stored_file and match it with the report snapshot.
     *
     * @param int $reportid Report ID.
     * @param int $coursejobid Report course row ID.
     * @param int $fileid File ID in mdl_files.
     * @return stored_file
     */
    public static function resolve(int $reportid, int $coursejobid, int $fileid): stored_file {
        global $DB;

        $DB->get_record('local_filexray_report', ['id' => $reportid], 'id', MUST_EXIST);
        $DB->get_record('local_filexray_course', [
            'id' => $coursejobid, 'reportid' => $reportid,
        ], 'id', MUST_EXIST);
        $snapshot = $DB->get_record('local_filexray_file', [
            'reportid' => $reportid, 'reportcourseid' => $coursejobid, 'fileid' => $fileid,
        ], 'id, contextid, component, filearea, filename, contenthash', MUST_EXIST);
        if ($snapshot->filename === '.') {
            throw new moodle_exception('filenotavailable', 'local_filexray');
        }
        $file = get_file_storage()->get_file_by_id($fileid);
        if (!$file || $file->is_directory() ||
                (int) $file->get_contextid() !== (int) $snapshot->contextid ||
                $file->get_component() !== $snapshot->component ||
                $file->get_filearea() !== $snapshot->filearea ||
                $file->get_filename() !== $snapshot->filename ||
                $file->get_contenthash() !== $snapshot->contenthash) {
            throw new moodle_exception('filenotavailable', 'local_filexray');
        }
        return $file;
    }

    /**
     * Return a time-bound URL accessible by Google without a Moodle session.
     * Only call after the authenticated administrator explicitly consents.
     *
     * @param int $reportid Report ID.
     * @param int $coursejobid Report course row ID.
     * @param int $fileid File ID.
     * @return moodle_url
     */
    public static function office_url(int $reportid, int $coursejobid, int $fileid): moodle_url {
        $file = self::resolve($reportid, $coursejobid, $fileid);
        if (self::kind($file->get_filename()) !== 'office') {
            throw new moodle_exception('invalidpreview', 'local_filexray');
        }
        $expires = time() + self::OFFICE_LIFETIME;
        $nonce = bin2hex(random_bytes(16));
        $signature = self::signature($reportid, $coursejobid, $fileid, $expires, $nonce,
            $file->get_contenthash());
        return new moodle_url('/local/filexray/officefile.php', [
            'id' => $reportid, 'coursejobid' => $coursejobid, 'fileid' => $fileid,
            'expires' => $expires, 'nonce' => $nonce, 'sig' => $signature,
        ]);
    }

    /**
     * Verify the share token and recheck the report-scoped file is unchanged.
     *
     * @param int $reportid Report ID.
     * @param int $coursejobid Report course row ID.
     * @param int $fileid File ID.
     * @param int $expires Expiry timestamp.
     * @param string $nonce Random nonce.
     * @param string $signature HMAC.
     * @return stored_file
     */
    public static function verify_office(
        int $reportid,
        int $coursejobid,
        int $fileid,
        int $expires,
        string $nonce,
        string $signature
    ): stored_file {
        if ($expires < time() || $expires > time() + self::OFFICE_LIFETIME ||
                !preg_match('/^[a-f0-9]{32}$/D', $nonce) ||
                !preg_match('/^[a-f0-9]{64}$/D', $signature)) {
            throw new moodle_exception('expiredpreview', 'local_filexray');
        }
        $file = self::resolve($reportid, $coursejobid, $fileid);
        if (self::kind($file->get_filename()) !== 'office') {
            throw new moodle_exception('invalidpreview', 'local_filexray');
        }
        $expected = self::signature($reportid, $coursejobid, $fileid, $expires, $nonce,
            $file->get_contenthash());
        if (!hash_equals($expected, $signature)) {
            throw new moodle_exception('expiredpreview', 'local_filexray');
        }
        return $file;
    }

    /**
     * Sign the file identity and expiry using the installation-specific secret.
     *
     * @param int $reportid Report ID.
     * @param int $coursejobid Report course row ID.
     * @param int $fileid File ID.
     * @param int $expires Timestamp.
     * @param string $nonce Random nonce.
     * @param string $contenthash SHA-1 of Moodle stored file content.
     * @return string HMAC SHA-256 hexadecimal signature.
     */
    private static function signature(
        int $reportid,
        int $coursejobid,
        int $fileid,
        int $expires,
        string $nonce,
        string $contenthash
    ): string {
        $key = get_config('local_filexray', 'previewsecret');
        if (!is_string($key) || !preg_match('/^[a-f0-9]{64}$/D', $key)) {
            throw new moodle_exception('previewnotconfigured', 'local_filexray');
        }
        $data = implode('|', [$reportid, $coursejobid, $fileid, $expires, $nonce, $contenthash]);
        return hash_hmac('sha256', $data, hex2bin($key));
    }
}
