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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * English language strings.
 *
 * @package    local_filexray
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['administration'] = 'File X-Ray administration';
$string['areaavatar'] = 'User profile pictures';
$string['areadraft'] = 'User drafts';
$string['areaname'] = 'Category';
$string['areaoutsidebytes'] = 'Outside inventory bytes';
$string['areaoutsidefiles'] = 'Outside inventory';
$string['areaprivate'] = 'Private user files';
$string['areaprofile'] = 'User profile files';
$string['areasummary'] = 'Other file areas in mdl_files';
$string['areasummary_note'] = 'Groups are identified by component and filearea. Total bytes sum the current mdl_files records in that area; user/draft corresponds to SUM(filesize) for user drafts. Outside-inventory columns count only file IDs missing from the selected report. Missing from a course backup does not mean unused or unrelated to courses.';
$string['areatotalbytes'] = 'Total bytes';
$string['areatotalfiles'] = 'Total file records';
$string['bytes'] = 'Bytes';
$string['comparison'] = 'Compare with mdl_files';
$string['comparison_note'] = 'Files not included in course inventories are not necessarily orphaned. Other Moodle contexts and unsupported backup plugins may still use them. No deletion is performed.';
$string['completed'] = 'Completed';
$string['component'] = 'Component';
$string['course'] = 'Course';
$string['coursecount'] = 'Courses to inspect';
$string['courses'] = 'Courses';
$string['coursesummary'] = 'Files by course';
$string['coursesummary_note'] = 'This section uses the inventory snapshot for each course, excluding directories. Bytes are logical sizes and files shared between courses appear in each course. They are not unique physical filedir bytes.';
$string['created'] = 'Created';
$string['createdby'] = 'Created by';
$string['createfailed'] = 'Unable to create the report.';
$string['croninfo'] = 'Courses are processed sequentially by Moodle ad-hoc tasks. Keep cron running; closing this page does not stop processing.';
$string['deletereport'] = 'Delete report';
$string['deletereportconfirm'] = 'Permanently delete this report, its course processing history and its file inventory? The actual Moodle files and course data will not be deleted.';
$string['deletereporttitle'] = 'Delete report #{$a}?';
$string['donecount'] = 'Courses completed';
$string['endtime'] = 'End';
$string['error'] = 'Error';
$string['failed'] = 'Failed';
$string['failedbackup'] = 'Course inventory failed.';
$string['failedcount'] = 'Courses failed';
$string['filearea'] = 'File area';
$string['fileid'] = 'File ID';
$string['filename'] = 'Filename';
$string['files'] = 'File references';
$string['filesize'] = 'File size';
$string['filexray:run'] = 'Generate and resume File X-Ray reports';
$string['filexray:view'] = 'View File X-Ray reports';
$string['finished'] = 'Finished';
$string['generate'] = 'Generate report';
$string['id'] = 'ID';
$string['invalidcourse'] = 'The course is not available.';
$string['matched'] = 'Records found in course inventories';
$string['matchedfiles'] = 'Files found in this course';
$string['maxreports'] = 'Maximum reports to keep';
$string['maxreports_desc'] = 'Maximum number of File X-Ray reports retained, including unfinished reports. When generating a new report at this limit, the oldest report and its inventory will be removed first. Minimum is 1. If the oldest report is currently being processed, report creation is blocked until its course finishes.';
$string['newreport'] = 'New report';
$string['next'] = 'Next';
$string['no_reports'] = 'No reports have been created yet.';
$string['notbackup'] = 'File X-Ray executes the Moodle course backup process up to the stage where the files selected for inclusion are identified. It captures this list and stops before copying physical file contents or creating the final ZIP/MBZ archive. This allows file analysis without generating a backup.';
$string['notfound'] = 'Report not found.';
$string['nothingpending'] = 'No more courses are pending.';
$string['onlyread'] = 'Read-only inventory. File deletion is deliberately not supported.';
$string['partial'] = 'Completed with errors';
$string['pending'] = 'Pending';
$string['pluginname'] = 'File X-Ray';
$string['previous'] = 'Previous';
$string['previousreports'] = 'Recent reports';
$string['privacy:metadata'] = 'File X-Ray stores administrator IDs in reports and file metadata already held by Moodle.';
$string['progress'] = 'Progress';
$string['queued'] = 'Queued';
$string['readmore'] = 'File details';
$string['recordcount'] = 'Entries';
$string['report'] = 'Report #{$a}';
$string['reportbusy'] = 'This report is currently being processed. Try again after its current course has finished.';
$string['reportcreated'] = 'Report created.';
$string['reportnotready'] = 'The inventory is not complete. The comparison is provisional until all courses succeed.';
$string['reports'] = 'Previous reports';
$string['reportsbusy'] = 'Another report operation is in progress. Please try again.';
$string['resume'] = 'Resume and retry failed courses';
$string['resumeok'] = 'Processing was requested.';
$string['running'] = 'Running';
$string['staleinfo'] = 'Use Resume to retry abandoned courses after two hours and schedule any still pending work.';
$string['started'] = 'Started';
$string['starttime'] = 'Start';
$string['status'] = 'Status';
$string['totaldatabase'] = 'File records in Moodle';
$string['unmatched'] = 'Not included in inspected course inventories';
$string['unmatchedcount'] = 'Records outside inspected course inventories';
$string['view'] = 'View';
