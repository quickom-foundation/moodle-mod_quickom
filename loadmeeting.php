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
 * Load quickom meeting and assign grade to the user join the meeting.
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_quickom
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Course_module ID.
$id = required_param('id', PARAM_INT);
if ($id) {
    $cm         = get_coursemodule_from_id('quickom', $id, 0, false, MUST_EXIST);
    $course     = get_course($cm->course);
    $quickom  = $DB->get_record('quickom', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    print_error('You must specify a course_module ID');
}
$userishost = ($USER->id == $quickom->creator_id);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$PAGE->set_context($context);

require_capability('mod/quickom:view', $context);
if ($userishost) {
    $accesstoken = "#access_token=" . Extend::base64url_encode($quickom->alias . ":" . $quickom->host_key);
    $nexturl = $quickom->start_url . "?joinType=host" . $accesstoken . "&token_type=Basic";
} else {
    // Check whether user had a grade. If no, then assign full credits to him or her.
    $gradelist = grade_get_grades($course->id, 'mod', 'quickom', $cm->instance, $USER->id);

    // Assign full credits for user who has no grade yet, if this meeting is gradable (i.e. the grade type is not "None").
    if (!empty($gradelist->items) && empty($gradelist->items[0]->grades[$USER->id]->grade)) {
        $grademax = $gradelist->items[0]->grademax;
        $grades = array(
            'rawgrade' => $grademax,
            'userid' => $USER->id,
            'usermodified' => $USER->id,
            'dategraded' => '',
            'feedbackformat' => '',
            'feedback' => ''
        );

        quickom_grade_item_update($quickom, $grades);
    }

    $nexturl = new moodle_url($quickom->join_url, array('name' => fullname($USER)));
}

// Record user's clicking join.
\mod_quickom\event\join_meeting_button_clicked::create(array('context' => $context, 'objectid' => $quickom->id, 'other' =>
array('cmid' => $id, 'meetingid' => (int) $quickom->meeting_id, 'userishost' => $userishost)))->trigger();
redirect($nexturl);
