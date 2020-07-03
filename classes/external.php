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
 * Quickom external API
 *
 * @package    mod_quickom
 * @category   external
 * @author     based on work by Nick Stefanski <nstefanski@escoffier.edu>
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2017 Auguste Escoffier School of Culinary Arts {@link https://www.escoffier.edu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

class mod_quickom_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function get_state_parameters() {
        return new external_function_parameters(
            array(
                'quickomid' => new external_value(PARAM_INT, 'quickom course module id')
            )
        );
    }

    /**
     * Determine if a quickom meeting is available, meeting status, and the start time, duration, and other meeting options.
     * This function grabs most of the options to display for users in /mod/quickom/view.php
     * Host functions are not currently supported
     *
     * @param int $quickomid the quickom course module id
     * @return array of warnings and status result
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function get_state($quickomid) {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/mod/quickom/locallib.php");

        $params = self::validate_parameters(self::get_state_parameters(),
                                            array(
                                                'quickomid' => $quickomid
                                            ));
        $warnings = array();

        // Request and permission validation.
        $cm = $DB->get_record('course_modules', array('id' => $params['quickomid']), '*', MUST_EXIST);
        $quickom  = $DB->get_record('quickom', array('id' => $cm->instance), '*', MUST_EXIST);
        $course  = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/quickom:view', $context);

        // Call the quickom/locallib API.
        list($inprogress, $available, $finished) = quickom_get_state($quickom);

        $result = array();
        $result['available'] = $available;

        if ($quickom->recurring) {
            $result['start_time'] = 0;
            $result['duration'] = 0;
        } else {
            $result['start_time'] = $quickom->start_time;
            $result['duration'] = $quickom->duration;
        }
        $result['haspassword'] = (isset($quickom->password) && $quickom->password !== '');
        $result['joinbeforehost'] = $quickom->option_jbh;
        $result['startvideohost'] = $quickom->option_host_video;
        $result['startvideopart'] = $quickom->option_participants_video;
        $result['audioopt'] = $quickom->option_audio;

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
        } else {
            $status = get_string('recurringmeetinglong', 'mod_quickom');
        }
        $result['status'] = $status;

        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.1
     */
    public static function get_state_returns() {
        return new external_single_structure(
            array(
                'available' => new external_value(PARAM_BOOL, 'if true, run grade_item_update and redirect to meeting url'),

                'start_time' => new external_value(PARAM_INT, 'meeting start time as unix timestamp (0 if recurring)'),
                'duration' => new external_value(PARAM_INT, 'meeting duration in seconds (0 if recurring)'),

                'haspassword' => new external_value(PARAM_BOOL, ''),
                'joinbeforehost' => new external_value(PARAM_BOOL, ''),
                'startvideohost' => new external_value(PARAM_BOOL, ''),
                'startvideopart' => new external_value(PARAM_BOOL, ''),
                'audioopt' => new external_value(PARAM_TEXT, ''),

                'status' => new external_value(PARAM_TEXT, 'meeting status: not_started, started, finished, expired, recurring'),

                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.1
     */
    public static function grade_item_update_parameters() {
        return new external_function_parameters(
            array(
                'quickomid' => new external_value(PARAM_INT, 'quickom course module id')
            )
        );
    }

    /**
     * Creates or updates grade item for the given quickom instance and returns join url.
     * This function grabs most of the options to display for users in /mod/quickom/view.php
     *
     * @param int $quickomid the quickom course module id
     * @return array of warnings and status result
     * @since Moodle 3.1
     * @throws moodle_exception
     */
    public static function grade_item_update($quickomid) {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . "/mod/quickom/lib.php");
        require_once($CFG->libdir . '/gradelib.php');

        $params = self::validate_parameters(self::get_state_parameters(),
                                            array(
                                                'quickomid' => $quickomid
                                            ));
        $warnings = array();

        // Request and permission validation.
        $cm = $DB->get_record('course_modules', array('id' => $params['quickomid']), '*', MUST_EXIST);
        $quickom  = $DB->get_record('quickom', array('id' => $cm->instance), '*', MUST_EXIST);
        $course  = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        require_capability('mod/quickom:view', $context);

        // Check whether user had a grade. If no, then assign full credits to him or her.
        $gradelist = grade_get_grades($course->id, 'mod', 'quickom', $cm->instance, $USER->id);

        // Assign full credits for user who has no grade yet, if this meeting is gradable
        // (i.e. the grade type is not "None").
        if (!empty($gradelist->items) && empty($gradelist->items[0]->grades[$USER->id]->grade)) {
            $grademax = $gradelist->items[0]->grademax;
            $grades = array('rawgrade' => $grademax,
                            'userid' => $USER->id,
                            'usermodified' => $USER->id,
                            'dategraded' => '',
                            'feedbackformat' => '',
                            'feedback' => '');
            // Call the quickom/lib API.
            quickom_grade_item_update($quickom, $grades);
        }

        // Pass url to join quickom meeting in order to redirect user.
        $joinurl = new moodle_url($quickom->join_url, array('uname' => fullname($USER)));

        $result = array();
        $result['status'] = true;
        $result['joinurl'] = $joinurl->__toString();
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.1
     */
    public static function grade_item_update_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'joinurl' => new external_value(PARAM_RAW, 'Quickom meeting join url'),
                'warnings' => new external_warnings()
            )
        );
    }

}
