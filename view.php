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
 * Prints a particular instance of quickom
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_quickom
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Login check require_login() is called in quickom_get_instance_setup();.
// @codingStandardsIgnoreLine
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(__FILE__) . '/lib.php';
require_once dirname(__FILE__) . '/locallib.php';
require_once dirname(__FILE__) . '/../../lib/moodlelib.php';

$config = get_config('mod_quickom');

list($course, $cm, $quickom) = quickom_get_instance_setup();

$context = context_module::instance($cm->id);
$isquickommanager = has_capability('mod/quickom:addinstance', $context);

$event = \mod_quickom\event\course_module_viewed::create([
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
]);
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $quickom);
$event->trigger();

// Print the page header.

$PAGE->set_url('/mod/quickom/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($quickom->name));
$PAGE->set_heading(format_string($course->fullname));

$quickomuserid = quickom_get_user_id(false);
$alternativehosts = [];
if (!is_null($quickom->alternative_hosts)) {
    $alternativehosts = explode(",", $quickom->alternative_hosts);
}

$userishost = ($USER->id == $quickom->creator_id);

$service = new mod_quickom_webservice();
try {
    $service->get_meeting_webinar_info($quickom->meeting_id, $quickom->webinar);
    $showrecreate = false;
} catch (moodle_exception $error) {
    $showrecreate = quickom_is_meeting_gone_error($error);
}

$stryes = get_string('yes');
$strno = get_string('no');
$strstart = get_string('start_meeting', 'mod_quickom');
$strjoin = get_string('join_meeting', 'mod_quickom');
$strunavailable = get_string('unavailable', 'mod_quickom');
$strtime = get_string('meeting_time', 'mod_quickom');
$strduration = get_string('duration', 'mod_quickom');
$strpassprotect = get_string('passwordprotected', 'mod_quickom');
$strpassword = get_string('password', 'mod_quickom');
$strjoinlink = get_string('join_link', 'mod_quickom');
$strjoinbeforehost = get_string('joinbeforehost', 'mod_quickom');
$strstartvideohost = get_string('starthostjoins', 'mod_quickom');
$strstartvideopart = get_string('startpartjoins', 'mod_quickom');
$straudioopt = get_string('option_audio', 'mod_quickom');
$strstatus = get_string('status', 'mod_quickom');
$strall = get_string('allmeetings', 'mod_quickom');

// Output starts here.
echo $OUTPUT->header();

if ($showrecreate) {
    // Only show recreate/delete links in the message for users that can edit.
    if ($isquickommanager) {
        $message = get_string('quickomerr_meetingnotfound', 'mod_quickom', quickom_meetingnotfound_param($cm->id));
        $style = 'notifywarning';
    } else {
        $message = get_string('quickomerr_meetingnotfound_info', 'mod_quickom');
        $style = 'notifymessage';
    }
    echo $OUTPUT->notification($message, $style);
}

echo $OUTPUT->heading(format_string($quickom->name), 2);
if ($quickom->intro) {
    echo $OUTPUT->box(format_module_intro('quickom', $quickom, $cm->id), 'generalbox mod_introbox', 'intro');
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_view';

$table->align = ['center', 'left'];
$numcolumns = 2;

list($inprogress, $available, $finished) = quickom_get_state($quickom);

if ($available) {
    if ($userishost) {
        $buttonhtml = html_writer::tag('button', $strstart, ['type' => 'submit', 'class' => 'btn btn-success']);
    } else {
        $buttonhtml = html_writer::tag('button', $strjoin, ['type' => 'submit', 'class' => 'btn btn-primary']);
    }
    $aurl = new moodle_url('/mod/quickom/loadmeeting.php', ['id' => $cm->id]);
    $buttonhtml .= html_writer::input_hidden_params($aurl);
    $link = html_writer::tag('form', $buttonhtml, ['action' => $aurl->out_omit_querystring(), 'target' => '_blank']);
} else {
    $precheck = false;
    if (is_siteadmin()) {
        $precheck = true;
    } else {
        $roles = get_user_roles($context, $USER->id);
        $roles_in_course = [];
        foreach ($roles as $role) {
            $roles_in_course[] = $role->shortname;
        }
        $allow_precheck = ['teacher', 'editingteacher', 'manager'];
        $matches = array_intersect($allow_precheck, $roles_in_course);
        if ($matches) {
            $precheck = true;
        }
    }

    if ($precheck) {
        $buttonhtml = html_writer::tag('button', $strjoin, ['type' => 'submit', 'class' => 'btn btn-secondary']);
        $aurl = new moodle_url('/mod/quickom/loadmeeting.php', ['id' => $cm->id]);
        $buttonhtml .= html_writer::input_hidden_params($aurl);
        $link = html_writer::tag('form', $buttonhtml, ['action' => $aurl->out_omit_querystring(), 'target' => '_blank']);
    } else {
        $link = html_writer::tag('span', $strunavailable, ['style' => 'font-size:20px']);
    }
}

$title = new html_table_cell($link);
$title->header = true;
$title->colspan = $numcolumns;
$table->data[] = [$title];

if ($quickom->recurring) {
    $recurringmessage = new html_table_cell(get_string('recurringmeetinglong', 'mod_quickom'));
    $recurringmessage->colspan = $numcolumns;
    $table->data[] = [$recurringmessage];
} else {
    $table->data[] = [$strtime, userdate($quickom->start_time)];
    $table->data[] = [$strduration, format_time($quickom->duration)];
}

if (!$quickom->webinar) {
    $haspassword = (isset($quickom->password) && $quickom->password !== '');
    $strhaspass = ($haspassword) ? $stryes : $strno;
    $table->data[] = [$strpassprotect, $strhaspass];

    if ($userishost && $haspassword) {
        $table->data[] = [$strpassword, $quickom->password];
    }
}

if (!$quickom->webinar) {
    $strjbh = ($quickom->option_jbh) ? $stryes : $strno;
    $table->data[] = [$strjoinbeforehost, $strjbh];

    $strvideohost = ($quickom->option_host_video) ? $stryes : $strno;
    $table->data[] = [$strstartvideohost, $strvideohost];

    $strparticipantsvideo = ($quickom->option_participants_video) ? $stryes : $strno;
    $table->data[] = [$strstartvideopart, $strparticipantsvideo];
}

if (!$quickom->recurring) {
    if (!$quickom->exists_on_quickom) {
        $status = get_string('meeting_nonexistent_on_quickom', 'mod_quickom');
    } else if ($finished) {
        $status = get_string('meeting_finished', 'mod_quickom');
    } else if ($inprogress) {
        $status = get_string('meeting_started', 'mod_quickom');
    } else {
        $status = get_string('meeting_not_started', 'mod_quickom');
    }

    $table->data[] = [$strstatus, $status];
}

$urlall = new moodle_url('/mod/quickom/index.php', ['id' => $course->id]);
$linkall = html_writer::link($urlall, $strall);
$linktoall = new html_table_cell($linkall);
$linktoall->colspan = $numcolumns;
$table->data[] = [$linktoall];

echo html_writer::table($table);

// Finish the page.
echo $OUTPUT->footer();
