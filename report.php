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
// Login check require_login() is called in quickom_get_instance_setup();.
// @codingStandardsIgnoreLine
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(__FILE__) . '/lib.php';
require_once dirname(__FILE__) . '/locallib.php';
require_once dirname(__FILE__) . '/mod_form.php';
require_once dirname(__FILE__) . '/../../lib/moodlelib.php';

list($course, $cm, $quickom) = quickom_get_instance_setup();

// Check capability.
$context = context_module::instance($cm->id);
require_capability('mod/quickom:addinstance', $context);

$PAGE->set_url('/mod/quickom/report.php', array('id' => $cm->id));

$strname = $quickom->name;
$strtitle = get_string('sessions', 'mod_quickom');
$PAGE->navbar->add($strtitle);
$PAGE->set_title("$course->shortname: $strname");
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();
echo $OUTPUT->heading($strname);
echo $OUTPUT->heading($strtitle, 4);

$sessions = quickom_get_sessions_for_display($quickom->meeting_id, $quickom->webinar, $quickom->host_id);
if (!empty($sessions)) {
    $table = new html_table();
    $table->head = array(
        get_string('title', 'mod_quickom'),
        get_string('starttime', 'mod_quickom'),
        get_string('endtime', 'mod_quickom'),
        get_string('duration', 'mod_quickom'),
        get_string('participants', 'mod_quickom'));
    $table->align = array('left', 'left', 'left', 'left', 'left');
    $format = get_string('strftimedatetimeshort', 'langconfig');

    foreach ($sessions as $uuid => $meet) {
        $row = array();
        $row[] = $meet['topic'];
        $row[] = $meet['starttime'];
        $row[] = $meet['endtime'];
        $row[] = $meet['duration'];

        if ($meet['count'] > 0) {
            $url = new moodle_url('/mod/quickom/participants.php', array('id' => $cm->id, 'uuid' => $uuid));
            $row[] = html_writer::link($url, $meet['count']);
        } else {
            $row[] = 0;
        }

        $table->data[] = $row;
    }
}

if (!empty($table->data)) {
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('nomeetinginstances', 'mod_quickom'), 'notifymessage');
}

echo $OUTPUT->footer();
