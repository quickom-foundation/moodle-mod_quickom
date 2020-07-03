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
 * List all quickom meetings.
 *
 * @package    mod_quickom
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/../../lib/moodlelib.php');

$id = required_param('id', PARAM_INT); // Course.

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course);

$context = context_course::instance($course->id);
require_capability('mod/quickom:view', $context);
$isquickommanager = has_capability('mod/quickom:addinstance', $context);

$params = array(
    'context' => $context
);
$event = \mod_quickom\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

$strname = get_string('modulenameplural', 'mod_quickom');
$strnew = get_string('newmeetings', 'mod_quickom');
$strold = get_string('oldmeetings', 'mod_quickom');

$strtopic = get_string('topic', 'mod_quickom');
$strwebinar = get_string('webinar', 'mod_quickom');
$strtime = get_string('meeting_time', 'mod_quickom');
$strduration = get_string('duration', 'mod_quickom');
$stractions = get_string('actions', 'mod_quickom');
$strsessions = get_string('sessions', 'mod_quickom');

$strmeetingstarted = get_string('meeting_started', 'mod_quickom');
$strstart = get_string('start', 'mod_quickom');
$strjoin = get_string('join', 'mod_quickom');

$PAGE->set_url('/mod/quickom/index.php', array('id' => $id));
$PAGE->navbar->add($strname);
$PAGE->set_title("$course->shortname: $strname");
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();
echo $OUTPUT->heading($strname);

if (! $quickoms = get_all_instances_in_course('quickom', $course)) {
    notice(get_string('noquickoms', 'mod_quickom'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

$usesections = course_format_uses_sections($course->format);

$quickomuserid = quickom_get_user_id(false);

$newtable = new html_table();
$newtable->attributes['class'] = 'generaltable mod_index';
$newhead = array($strtopic, $strtime, $strduration, $stractions);
$newalign = array('left', 'left', 'left', 'left');

$oldtable = new html_table();
$oldhead = array($strtopic, $strtime);
$oldalign = array('left', 'left');

// Show section column if there are sections.
if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_'.$course->format);
    array_unshift($newhead, $strsectionname);
    array_unshift($newalign, 'center');
    array_unshift($oldhead, $strsectionname);
    array_unshift($oldalign, 'center');
}

// Show sessions column only if user can edit Quickom meetings.
if ($isquickommanager) {
    $newhead[] = $strsessions;
    $newalign[] = 'left';
    $oldhead[] = $strsessions;
    $oldalign[] = 'left';
}

$newtable->head = $newhead;
$newtable->align = $newalign;
$oldtable->head = $oldhead;
$oldtable->align = $oldalign;

$now = time();
$modinfo = get_fast_modinfo($course);
$cms = $modinfo->instances['quickom'];
foreach ($quickoms as $z) {
    $row = array();
    list($inprogress, $available, $finished) = quickom_get_state($z);

    $cm = $cms[$z->id];
    if ($usesections && isset($cm->sectionnum)) {
        $row[0] = get_section_name($course, $cm->sectionnum);
    }

    $url = new moodle_url('view.php', array('id' => $cm->id));
    $row[1] = html_writer::link($url, $cm->get_formatted_name());
    if ($z->webinar) {
        $row[1] .= " ($strwebinar)";
    }
    // Recurring meetings have no start time or duration.
    $displaytime = $z->recurring ? get_string('recurringmeetinglong', 'mod_quickom') : userdate($z->start_time);

    $report = new moodle_url('report.php', array('id' => $cm->id));
    $sessions = html_writer::link($report, $strsessions);

    if ($finished) {
        $row[2] = $displaytime;
        if ($isquickommanager) {
            $row[3] = $sessions;
        }
        $oldtable->data[] = $row;
    } else {
        if ($inprogress) {
            $label = html_writer::tag('span', $strmeetingstarted,
                    array('class' => 'label label-info quickom-info'));
            $row[2] = html_writer::tag('div', $label);
        } else {
            $row[2] = $displaytime;
        }

        $row[3] = $z->recurring ? '--' : format_time($z->duration);

        if ($available) {
            if ($quickomuserid === false || $quickomuserid != $z->host_id) {
                $buttonhtml = html_writer::tag('button', $strjoin,
                        array('type' => 'submit', 'class' => 'btn btn-primary'));
                $aurl = new moodle_url('/mod/quickom/loadmeeting.php', array('id' => $cm->id));
            } else {
                $buttonhtml = html_writer::tag('button', $strstart,
                        array('type' => 'submit', 'class' => 'btn btn-success'));
                $aurl = new moodle_url($z->start_url);
            }
            $buttonhtml .= html_writer::input_hidden_params($aurl);
            $row[4] = html_writer::tag('form', $buttonhtml, array('action' => $aurl->out_omit_querystring(), 'target' => '_blank'));
        } else {
            $row[4] = '--';
        }

        if ($isquickommanager) {
            $row[] = $sessions;
        }

        $newtable->data[] = $row;
    }
}

echo $OUTPUT->heading($strnew, 4);
echo html_writer::table($newtable);
echo $OUTPUT->heading($strold, 4);
echo html_writer::table($oldtable);

echo $OUTPUT->footer();
