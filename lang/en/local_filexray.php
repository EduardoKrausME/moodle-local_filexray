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
 * English language strings.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'File X-Ray';
$string['privacy:metadata'] = 'File X-Ray stores administrator IDs in reports and file metadata already held by Moodle.';
$string['generate'] = 'Generate report';
$string['newreport'] = 'New report';
$string['reports'] = 'Previous reports';
$string['courses'] = 'Courses';
$string['files'] = 'File references';
$string['progress'] = 'Progress';
$string['pending'] = 'Pending';
$string['queued'] = 'Queued';
$string['running'] = 'Running';
$string['completed'] = 'Completed';
$string['partial'] = 'Completed with errors';
$string['failed'] = 'Failed';
$string['report'] = 'Report #{$a}';
$string['view'] = 'View';
$string['created'] = 'Created';
$string['no_reports'] = 'No reports have been created yet.';
$string['notbackup'] = 'This inspection does not create an MBZ or copy file contents.';
$string['croninfo'] = 'Courses are processed sequentially by Moodle ad-hoc tasks. Keep cron running; closing this page does not stop processing.';
$string['course'] = 'Course';
$string['status'] = 'Status';
$string['error'] = 'Error';
$string['started'] = 'Started';
$string['finished'] = 'Finished';
$string['next'] = 'Next';
$string['previous'] = 'Previous';
$string['resume'] = 'Resume pending courses';
$string['comparison'] = 'Compare with mdl_files';
$string['comparison_note'] = 'Files not included in course inventories are not necessarily orphaned. Other Moodle contexts and unsupported backup plugins may still use them. No deletion is performed.';
$string['unmatched'] = 'Not included in inspected course inventories';
$string['fileid'] = 'File ID';
$string['filename'] = 'Filename';
$string['component'] = 'Component';
$string['filearea'] = 'File area';
$string['filesize'] = 'File size';
$string['totaldatabase'] = 'File records in Moodle';
$string['matched'] = 'Records found in course inventories';
$string['unmatchedcount'] = 'Records outside inspected course inventories';
$string['reportnotready'] = 'The inventory is not complete. The comparison is provisional until all courses succeed.';
$string['createdby'] = 'Created by';
$string['id'] = 'ID';
$string['onlyread'] = 'Read-only inventory. File deletion is deliberately not supported.';
$string['notfound'] = 'Report not found.';
$string['staleinfo'] = 'Use Resume to retry abandoned courses after two hours and schedule any still pending work.';
$string['createfailed'] = 'Unable to create the report.';
$string['resumeok'] = 'Processing was requested.';
$string['invalidcourse'] = 'The course is not available.';
$string['failedbackup'] = 'Course inventory failed.';
$string['nothingpending'] = 'No more courses are pending.';
$string['readmore'] = 'File details';
$string['matchedfiles'] = 'Files found in this course';
$string['previousreports'] = 'Recent reports';
$string['recordcount'] = 'Entries';
$string['starttime'] = 'Start';
$string['endtime'] = 'End';
$string['reportcreated'] = 'Report created.';
$string['coursecount'] = 'Courses to inspect';
$string['donecount'] = 'Courses completed';
$string['failedcount'] = 'Courses failed';
$string['administration'] = 'File X-Ray administration';