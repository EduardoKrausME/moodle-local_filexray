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
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['administration'] = 'File X-Ray administration';
$string['areaavatar'] = 'User profile pictures';
$string['areaavatar_desc'] = 'Profile pictures and generated image sizes used in Moodle user accounts.';
$string['areabackup'] = 'Backup component (all areas)';
$string['areabackup_desc'] = 'All file areas owned by the backup component, without a filearea filter. May contain archives or files used by the backup system.';
$string['areabadgeimage'] = 'Badge images';
$string['areabadgeimage_desc'] = 'Images associated with Moodle badges and achievements, including badges awarded to users.';
$string['areacoreh5p'] = 'Core H5P component (all areas)';
$string['areacoreh5p_desc'] = 'All file areas owned by core_h5p, shown in one row with their distinct area names. Libraries, exports and content may be required for H5P activities.';
$string['areadescription'] = 'What these files are';
$string['areadoclink'] = 'What are these files?';
$string['areadraft'] = 'User drafts';
$string['areadraft_desc'] = 'Temporary files held by users while editing or choosing uploads. Some drafts may still be in use.';
$string['areaname'] = 'Category';
$string['areaother_desc'] = 'Files registered in another Moodle or plugin file area. Their absence from a course backup does not prove they are unused.';
$string['areaoutsidebytes'] = 'Outside inventory bytes';
$string['areaoutsidefiles'] = 'Outside inventory';
$string['areaprivate'] = 'Private user files';
$string['areaprivate_desc'] = 'Personal private files stored by Moodle users and not necessarily related to course backups.';
$string['areaprofile'] = 'User profile files';
$string['areaprofile_desc'] = 'Other files attached to users\' profile areas.';
$string['areasummary'] = 'Other file areas in mdl_files';
$string['areasummary_note'] = 'This table classifies user, badge, backup, H5P, theme, site branding, preview, folder, recycle bin and other documented areas, displaying logical byte totals and records absent from this report\'s course inventory. Core H5P and themes are combined into one row each. Theme file areas are limited to five names, then etc.... Component-wide totals may overlap with their per-area rows and must not be added together.';
$string['areathemes'] = 'Themes';
$string['areathemes_desc'] = 'Files belonging to every theme_* component, grouped together. The area column shows up to five distinct file areas followed by etc... if needed.';
$string['areatotalbytes'] = 'Total bytes';
$string['areatotalfiles'] = 'Total file records';
$string['areauserbackup'] = 'User backup files';
$string['areauserbackup_desc'] = 'Backups stored in a user\'s backup file area. They may be needed for restoring courses or content.';
$string['bytes'] = 'Bytes';
$string['chartcoverage'] = 'Inventory coverage';
$string['chartcoverage_note'] = 'Distribution of current Moodle files by record count and logical bytes across mutually exclusive inventory groups.';
$string['chartcoveragebytes'] = 'Logical bytes';
$string['chartcoveragefiles'] = 'Files';
$string['chartininventory'] = 'In course inventories';
$string['chartnodata'] = 'No records to display.';
$string['chartotherareas'] = 'Outside inventories, in classified areas';
$string['chartresidual'] = 'Outside inventories and classified areas';
$string['charttopcourses'] = 'Courses with the most inventoried bytes on this page';
$string['charttopcourses_note'] = 'Logical sizes are summed per course; shared files can appear in more than one course total.';
$string['comparison'] = 'Compare with mdl_files';
$string['comparison_note'] = 'Files not included in course inventories are not necessarily orphaned. Other Moodle contexts and unsupported backup plugins may still use them. No deletion is performed.';
$string['completed'] = 'Completed';
$string['component'] = 'Component';
$string['contextid'] = 'Context ID';
$string['course'] = 'Course';
$string['courseallcomponents'] = 'All components';
$string['coursechartbytes'] = 'Bytes by component';
$string['coursechartfiles'] = 'Files by component';
$string['coursechartnote'] = 'Select a component row to view its files. Totals cover the entire saved course inventory.';
$string['coursecomponentfilter'] = 'Showing files for component:';
$string['coursecount'] = 'Courses analyzed';
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
$string['downloadfile'] = 'Download file';
$string['editsettings'] = 'Edit settings';
$string['endtime'] = 'End';
$string['error'] = 'Error';
$string['expiredpreview'] = 'The external preview link has expired or is invalid.';
$string['failed'] = 'Failed';
$string['failedbackup'] = 'Course inventory failed.';
$string['failedcount'] = 'Courses failed';
$string['filearea'] = 'File area';
$string['fileid'] = 'File ID';
$string['filename'] = 'Filename';
$string['filenotavailable'] = 'This file is no longer available or no longer matches the saved report.';
$string['files'] = 'File references';
$string['filesize'] = 'File size';
$string['filexray:run'] = 'Generate and resume File X-Ray reports';
$string['filexray:view'] = 'View File X-Ray reports';
$string['finished'] = 'Finished';
$string['generate'] = 'Generate report';
$string['id'] = 'ID';
$string['invalidcourse'] = 'The course is not available.';
$string['invalidpreview'] = 'This file type does not support that preview method.';
$string['matched'] = 'Records found in course inventories';
$string['matchedfiles'] = 'Files found in this course';
$string['maxreports'] = 'Maximum reports to keep';
$string['maxreports_desc'] = 'Maximum number of File X-Ray reports retained, including unfinished reports. When generating a new report at this limit, the oldest report and its inventory will be removed first. Minimum is 1. If the oldest report is currently being processed, report creation is blocked until its course finishes.';
$string['mcp_banner_badge'] = 'AI INTEGRATION';
$string['mcp_banner_benefit_control'] = 'Confirmation required for write actions';
$string['mcp_banner_benefit_discovery'] = 'Automatic tool discovery';
$string['mcp_banner_benefit_security'] = 'Moodle permissions enforced';
$string['mcp_banner_cta'] = 'Explore Moodle MCP';
$string['mcp_banner_description'] = 'Install local_mcp to expose File X-Ray features to ChatGPT through native Moodle permissions.';
$string['mcp_banner_dismiss'] = 'Do not show this again';
$string['mcp_banner_title'] = 'Bring File X-Ray into ChatGPT';
$string['newreport'] = 'New report';
$string['next'] = 'Next';
$string['no_reports'] = 'No reports have been created yet.';
$string['notbackup'] = 'File X-Ray executes the Moodle course backup process up to the stage where the files selected for inclusion are identified. It captures this list and stops before copying physical file contents or creating the final ZIP/MBZ archive. This allows file analysis without generating a backup.';
$string['notfound'] = 'Report not found.';
$string['nothingpending'] = 'No more courses are pending.';
$string['officeapprove'] = 'Allow temporary Google preview';
$string['officeconsent'] = 'This document is private to your Moodle installation. Google Docs Viewer can only display it if you explicitly allow temporary external access. Approving shares a signed file URL with Google and permits anyone holding that URL to fetch the document for up to 10 minutes. Google may process the document under its own privacy terms. Do not approve for confidential documents unless your institution permits this transfer.';
$string['officehttpsrequired'] = 'Google preview is unavailable because this Moodle site does not use HTTPS. Download the original file instead.';
$string['officeopenexternal'] = 'Open Google Docs Viewer in a new tab';
$string['officeviewernote'] = 'The Google Docs Viewer is attempting to load the file through a temporary link that expires within 10 minutes. The site must be publicly reachable over HTTPS. If Google cannot display it, download the original instead.';
$string['onlyread'] = 'Read-only inventory. File deletion is deliberately not supported.';
$string['partial'] = 'Completed with errors';
$string['pending'] = 'Pending';
$string['pluginname'] = 'File X-Ray';
$string['preview'] = 'Preview';
$string['previewnotconfigured'] = 'The external preview signing key has not been installed. Run the Moodle plugin upgrade.';
$string['previous'] = 'Previous';
$string['previousreports'] = 'Recent reports';
$string['privacy:metadata'] = 'File X-Ray stores administrator IDs in reports and file metadata already held by Moodle.';
$string['progress'] = 'Progress';
$string['queued'] = 'Queued';
$string['readmore'] = 'File details';
$string['recordcount'] = 'Entries';
$string['report'] = 'Report #{$a}';
$string['reportbusy'] = 'This report is processing a course right now and cannot be changed until that step finishes. Wait for the current course to finish, then try again. No report data has been deleted.';
$string['reportcreated'] = 'Report created.';
$string['reportnotready'] = 'The inventory is not complete. The comparison is provisional until all courses succeed.';
$string['reports'] = 'Previous reports';
$string['reportsbusy'] = 'Another report operation is currently in progress. Wait until it finishes and try again.';
$string['residual_note'] = 'Only files absent from the virtual course backups AND not already classified above are listed here. These records still require investigation; absence from a course backup does not establish that they are orphaned.';
$string['resume'] = 'Resume and retry failed courses';
$string['resumeok'] = 'Processing was requested.';
$string['running'] = 'Running';
$string['skipuserdata'] = 'Exclude user data from inventory (faster)';
$string['skipuserdata_desc'] = 'When enabled, File X-Ray disables Moodle\'s native \'Include enrolled users\' backup option while inspecting each course. This can make large courses faster to process because enrolments, submissions, forum posts and other user activity are not included in the backup data. <strong>Important:</strong> files used only by student activity may be missing from the inventory and appear as \'not included\' in the comparison even though they are still in use. No students or files are deleted. When disabled (the default), the inventory follows Moodle\'s normal backup settings. This choice applies to courses processed after a change, including pending courses in existing reports; avoid changing it while a report is running. If Moodle locks the user backup setting on, the course inventory will fail rather than silently ignore this choice.';
$string['skipuserdatawarning'] = 'Excluding user data is enabled. Inventories processed with this option did not include enrolled users\' backup data, so files from student activities may appear outside the inventories. Changing this setting will not reprocess completed inventories.';
$string['staleinfo'] = 'Use Resume to retry abandoned courses after two hours and schedule any still pending work.';
$string['started'] = 'Started';
$string['starttime'] = 'Start';
$string['status'] = 'Status';
$string['totaldatabase'] = 'File records in Moodle';
$string['unmatched'] = 'Not included in inspected course inventories';
$string['unmatchedcount'] = 'Records outside course inventories and other-area categories';
$string['unmatchedview'] = 'View files not found in course inventories';
$string['userdatalocked'] = 'Moodle requires user data to be included by a locked backup setting. Unlock the native backup option before using File X-Ray\'s faster mode, or turn off the exclude user data option.';
$string['view'] = 'View';
