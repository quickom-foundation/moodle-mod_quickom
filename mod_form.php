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
 * The main quickom configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_quickom
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/quickom/lib.php');
require_once($CFG->dirroot.'/mod/quickom/locallib.php');

require_once($CFG->dirroot.'/mod/quickom/extend.php');

/**
 * mod_quickom_mod_form class.
 *
 * @package    mod_quickom
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quickom_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $PAGE, $USER;
        $config = get_config('mod_quickom');
        $service = new mod_quickom_webservice();
        $quickomuser = $service->get_user($USER->email);
        if ($quickomuser === false) {
            // Assume user is using Quickom for the first time.
            $errstring = 'quickomerr_usernotfound';
            // After they set up their account, the user should continue to the page they were on.
            $nexturl = $PAGE->url;
            throw new moodle_exception($errstring, 'mod_quickom', $nexturl, $config->quickomurl);
        }

        // If updating, ensure we can get the meeting on Quickom.
        $isnew = empty($this->_cm);
        if (!$isnew) {
            try {
                $service->get_meeting_webinar_info($this->current->meeting_id, $this->current->webinar);
            } catch (moodle_exception $error) {
                // If the meeting can't be found, offer to recreate the meeting on Quickom.
                if (quickom_is_meeting_gone_error($error)) {
                    $errstring = 'quickomerr_meetingnotfound';
                    $param = quickom_meetingnotfound_param($this->_cm->id);
                    $nexturl = "/mod/quickom/view.php?id=" . $this->_cm->id;
                    throw new moodle_exception($errstring, 'mod_quickom', $nexturl, $param, "meeting/get : $error");
                } else {
                    throw $error;
                }
            }
        }

        // Start of form definition.
        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Add topic (stored in database as 'name').
        $mform->addElement('text', 'name', get_string('topic', 'quickom'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 300), 'maxlength', 300, 'client');

        // Add description ('intro' and 'introformat').
        $this->standard_intro_elements();

        // Add date/time. Validation in validation().
        $mform->addElement('date_time_selector', 'start_time', get_string('start_time', 'quickom'));
        // Disable for recurring meetings.
        $mform->disabledIf('start_time', 'recurring', 'checked');

        // Add duration.
        $mform->addElement('duration', 'duration', get_string('duration', 'quickom'), array('optional' => false));
        // Validation in validation(). Default to one hour.
        $mform->setDefault('duration', array('number' => 1, 'timeunit' => 3600));
        // Disable for recurring meetings.
        $mform->disabledIf('duration', 'recurring', 'checked');

        // Add password.
        $mform->addElement('passwordunmask', 'password', get_string('password', 'quickom'), array('maxlength' => '10'));
        // Check password uses valid characters.
        $regex = '/^[a-zA-Z0-9@_*-]{1,10}$/';
        $mform->addRule('password', get_string('err_password', 'mod_quickom'), 'regex', $regex, 'client');
        $mform->disabledIf('password', 'webinar', 'checked');

        // Add meeting id.
        $mform->addElement('hidden', 'meeting_id', -1);
        $mform->setType('meeting_id', PARAM_ALPHANUMEXT);

        // Add host id (will error if user does not have an account on Quickom).
        $mform->addElement('hidden', 'host_id', quickom_get_user_id());
        $mform->setType('host_id', PARAM_ALPHANUMEXT);

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();
        $mform->setDefault('grade', false);

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }

    /**
     * More validation on form data.
     * See documentation in lib/formslib.php.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $CFG;
        $errors = array();

        // Only check for scheduled meetings.
        if (empty($data['recurring'])) {
            // Make sure start date is in the future.
            if ($data['start_time'] < strtotime('today')) {
                $errors['start_time'] = get_string('err_start_time_past', 'quickom');
            }

            // Make sure duration is positive and no more than 150 hours.
            if ($data['duration'] <= 0) {
                $errors['duration'] = get_string('err_duration_nonpositive', 'quickom');
            } else if ($data['duration'] > 150 * 60 * 60) {
                $errors['duration'] = get_string('err_duration_too_long', 'quickom');
            }
        }

        // Check if the listed alternative hosts are valid users on Quickom.
        require_once($CFG->dirroot.'/mod/quickom/classes/webservice.php');
        $service = new mod_quickom_webservice();
        $alternativehosts = explode(',', $data['alternative_hosts']);
        foreach ($alternativehosts as $alternativehost) {
            if (!($service->get_user($alternativehost))) {
                $errors['alternative_hosts'] = 'User ' . $alternativehost . ' was not found on Quickom.';
                break;
            }
        }

        return $errors;
    }
}

/**
 * Form to search for meeting reports.
 *
 * @package    mod_quickom
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quickom_report_form extends moodleform {
    /**
     * Define form elements.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('date_selector', 'from', get_string('from'));

        $mform->addElement('date_selector', 'to', get_string('to'));

        $mform->addElement('submit', 'submit', get_string('go'));
    }
}