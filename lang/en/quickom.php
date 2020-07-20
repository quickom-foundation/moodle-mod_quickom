<?php
// This file is part of the Quickom plugin for Moodle - http://moodle.org/
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
 * English strings for quickom.
 *
 * @package    mod_quickom
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['actions'] = 'Actions';
$string['addtocalendar'] = 'Add to calendar';
$string['alternative_hosts'] = 'Alternative Hosts';
$string['alternative_hosts_help'] = 'The alternative host option allows you to schedule classrooms and designate another Pro user on the same account to start the classroom or webinar if you are unable to. This user will receive an email notifying them that they\'ve been added as an alternative host, with a link to start the classroom. Separate multiple emails by comma (without spaces).';
$string['allmeetings'] = 'All classrooms';
$string['apikey'] = 'Quickom API key';
$string['apikey_desc'] = '';
$string['apisecret'] = 'Quickom API secret';
$string['apisecret_desc'] = '';
$string['apiurl'] = 'Quickom API url';
$string['apiurl_desc'] = '';
$string['attentiveness_score'] = 'Attentiveness score*';
$string['attentiveness_score_help'] = '*Attentiveness score is lowered when a participant does not have Quickom in focus for more than 30 seconds when someone is sharing a screen.';
$string['audio_both'] = 'VoIP and Telephony';
$string['audio_telephony'] = 'Telephony only';
$string['audio_voip'] = 'VoIP only';
$string['cachedef_quickomid'] = 'The quickom user id of the user';
$string['cachedef_sessions'] = 'Information from the quickom get user report request';
$string['calendardescriptionURL'] = 'Classroom join URL: {$a}.';
$string['calendardescriptionintro'] = "\nDescription:\n{\$a}";
$string['calendariconalt'] = 'Calendar icon';
$string['clickjoin'] = 'Clicked join classroom button';
$string['connectionok'] = 'Connection working.';
$string['connectionfailed'] = 'Connection failed: ';
$string['connectionstatus'] = 'Connection status';
$string['defaultsettings'] = 'Default Quickom settings';
$string['defaultsettings_help'] = 'These settings define the defaults for all new Quickom classrooms.';
$string['downloadical'] = 'Download iCal';
$string['duration'] = 'Duration (minutes)';
$string['endtime'] = 'End time';
$string['err_duration_nonpositive'] = 'The duration must be positive.';
$string['err_duration_too_long'] = 'The duration cannot exceed 150 hours.';
$string['err_long_timeframe'] = 'Requested time frame too long, showing results of latest month in range.';
$string['err_password'] = 'Password may only contain the following characters: [a-z A-Z 0-9 @ - _ *]. Max of 10 characters.';
$string['err_start_time_past'] = 'The start date cannot be in the past.';
$string['errorwebservice'] = 'Quickom webservice error: {$a}.';
$string['export'] = 'Export';
$string['firstjoin'] = 'First able to join';
$string['firstjoin_desc'] = 'The earliest a user can join a scheduled classroom (minutes before start).';
$string['getmeetingreports'] = 'Get classroom report from Quickom';
$string['invalid_status'] = 'Status invalid, check the database.';
$string['join'] = 'Join';
$string['joinbeforehost'] = 'Join classroom before host';
$string['join_link'] = 'Join link';
$string['join_meeting'] = 'Join classroom';
$string['jointime'] = 'Join time';
$string['leavetime'] = 'Leave time';
$string['licensesnumber'] = 'Number of licenses';
$string['redefinelicenses'] = 'Redefine licenses';
$string['lowlicenses'] = 'If the number of your licenses exceeds those required, then when you create each new activity by the user, it will be assigned a PRO license by lowering the status of another user. The option is effective when the number of active PRO-licenses is more than 5.';
$string['meeting_nonexistent_on_quickom'] = 'Nonexistent on Quickom';
$string['meeting_finished'] = 'Finished';
$string['meeting_not_started'] = 'Not started';
$string['meetingoptions'] = 'Classroom option';
$string['meetingoptions_help'] = '*Join before host* allows attendees to join the classroom before the host joins or when the host cannot attend the classroom.';
$string['meeting_started'] = 'In progress';
$string['meeting_time'] = 'Start Time';
$string['moduleintro'] = 'Description';
$string['modulename'] = 'Quickom classroom';
$string['modulenameplural'] = 'Quickom classrooms';
$string['modulename_help'] = 'Quickom is a video and web conferencing platform that gives authorized users the ability to host online classrooms.';
$string['newmeetings'] = 'New classrooms';
$string['nomeetinginstances'] = 'No sessions found for this classroom.';
$string['noparticipants'] = 'No participants found for this session at this time.';
$string['nosessions'] = 'No sessions found for specified range.';
$string['noquickoms'] = 'No classrooms';
$string['off'] = 'Off';
$string['oldmeetings'] = 'Concluded classrooms';
$string['on'] = 'On';
$string['option_audio'] = 'Audio options';
$string['option_host_video'] = 'Host video';
$string['option_jbh'] = 'Enable join before host';
$string['option_participants_video'] = 'Participants video';
$string['participants'] = 'Participants';
$string['password'] = 'Password';
$string['passwordprotected'] = 'Password Protected';
$string['pluginadministration'] = 'Manage Quickom classroom';
$string['pluginname'] = 'Quickom classroom';
$string['privacy:metadata:quickom_meeting_details'] = 'The database table that stores information about each classroom instance.';
$string['privacy:metadata:quickom_meeting_details:topic'] = 'The name of the classroom that the user attended.';
$string['privacy:metadata:quickom_meeting_participants'] = 'The database table that stores information about classroom participants.';
$string['privacy:metadata:quickom_meeting_participants:attentiveness_score'] = 'The participant\'s attentiveness score';
$string['privacy:metadata:quickom_meeting_participants:duration'] = 'How long the participant was in the classroom';
$string['privacy:metadata:quickom_meeting_participants:join_time'] = 'The time that the participant joined the classroom';
$string['privacy:metadata:quickom_meeting_participants:leave_time'] = 'The time that the participant left the classroom';
$string['privacy:metadata:quickom_meeting_participants:name'] = 'The name of the participant';
$string['privacy:metadata:quickom_meeting_participants:user_email'] = 'The email of the participant';
$string['recurringmeeting'] = 'Recurring';
$string['recurringmeeting_help'] = 'Has no end date';
$string['recurringmeetinglong'] = 'Recurring classroom (classroom with no end date or time)';
$string['report'] = 'Reports';
$string['reportapicalls'] = 'Report API calls exhausted';
$string['requirepassword'] = 'Require classroom password';
$string['resetapicalls'] = 'Reset the number of available API calls';
$string['search:activity'] = 'Quickom - activity information';
$string['sessions'] = 'Sessions';
$string['showdescription'] = 'Display description on course page';
$string['start'] = 'Start';
$string['starthostjoins'] = 'Start video when host joins';
$string['start_meeting'] = 'Start classroom';
$string['startpartjoins'] = 'Start video when participant joins';
$string['start_time'] = 'When';
$string['starttime'] = 'Start time';
$string['status'] = 'Status';
$string['title'] = 'Title';
$string['topic'] = 'Topic';
$string['unavailable'] = 'Unable to join at this time';
$string['updatemeetings'] = 'Update classroom settings from Quickom';
$string['usepersonalmeeting'] = 'Use personal classroom ID {$a}';
$string['webinar'] = 'Webinar';
$string['webinar_help'] = 'This option is only available to pre-authorized Quickom accounts.';
$string['webinar_already_true'] = '<p><b>This module was already set as a webinar, not classroom. You cannot toggle this setting after creating the webinar.</b></p>';
$string['webinar_already_false'] = '<p><b>This module was already set as a classroom, not webinar. You cannot toggle this setting after creating the classroom.</b></p>';
$string['quickom:addinstance'] = 'Add a new Quickom classroom';
$string['quickomerr'] = 'An error occured with Quickom.'; // Generic error.
$string['quickomerr_apikey_missing'] = 'Quickom API key not found';
$string['quickomerr_apisecret_missing'] = 'Quickom API secret not found';
$string['quickomerr_id_missing'] = 'You must specify a course_module ID or an instance ID';
$string['quickomerr_licensescount_missing'] = 'Quickom utmost setting found but, licensescount setting not found';
$string['quickomerr_meetingnotfound'] = 'This classroom cannot be found on Quickom. You can <a href="{$a->recreate}">recreate it here</a> or <a href="{$a->delete}">delete it completely</a>.';
$string['quickomerr_meetingnotfound_info'] = 'This classroom cannot be found on Quickom. Please contact the classroom host if you have questions.';
$string['quickomerr_usernotfound'] = 'Unable to find your account on Quickom. If you are using Quickom for the first time, you must Quickom account by logging into Quickom <a href="{$a}" target="_blank">{$a}</a>. Once you\'ve activated your Quickom account, reload this page and continue setting up your classroom. Else make sure your email on Quickom matches your email on this system.';
$string['quickomurl'] = 'Quickom home page URL';
$string['quickomurl_desc'] = '';
$string['quickom:view'] = 'View Quickom classrooms';
