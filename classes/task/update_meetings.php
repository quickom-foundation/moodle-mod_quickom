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
 * Library of interface functions and constants for module quickom
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the quickom specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_quickom
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2018 UC Regents
 * @author     based on work by Rohan Khajuria
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_quickom\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/quickom/locallib.php');

/**
 * Scheduled task to sychronize meeting data.
 *
 * @package    mod_quickom
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2018 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_meetings extends \core\task\scheduled_task {

    /**
     * Returns name of task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('updatemeetings', 'mod_quickom');
    }

    /**
     * Updates meetings that are not expired.
     *
     * @return boolean
     */
    public function execute() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/modinfolib.php');
        require_once($CFG->dirroot.'/mod/quickom/lib.php');
        require_once($CFG->dirroot.'/mod/quickom/classes/webservice.php');
        $service = new \mod_quickom_webservice();

        // Check all meetings, in case they were deleted/changed on Quickom.
        $quickomstoupdate = $DB->get_records('quickom', array('exists_on_quickom' => true));
        $courseidstoupdate = array();
        $calendarfields = array('intro', 'introformat', 'start_time', 'duration', 'recurring');

        foreach ($quickomstoupdate as $quickom) {
            $gotinfo = false;
            try {
                $response = $service->get_meeting_webinar_info($quickom->meeting_id, $quickom->webinar);
                $gotinfo = true;
            } catch (\moodle_exception $error) {
                // Outputs error and then goes to next meeting.
                $quickom->exists_on_quickom = false;
                $DB->update_record('quickom', $quickom);
                mtrace('Error updating Quickom meeting with meeting_id ' . $quickom->meeting_id . ': ' . $error);
            }
            if ($gotinfo) {
                $changed = false;
                $newquickom = populate_quickom_from_response($quickom, $response);
                foreach ((array) $quickom as $field => $value) {
                    // The start_url has a parameter that always changes, so it doesn't really count as a change.
                    if ($field != 'start_url' && $newquickom->$field != $value) {
                        $changed = true;
                        break;
                    }
                }

                if ($changed) {
                    $DB->update_record('quickom', $newquickom);

                    // If the topic/title was changed, mark this course for cache clearing.
                    if ($quickom->name != $newquickom->name) {
                        $courseidstoupdate[] = $newquickom->course;
                    }

                    // Check if calendar needs updating.
                    foreach ($calendarfields as $field) {
                        if ($quickom->$field != $newquickom->$field) {
                            quickom_calendar_item_update($newquickom);
                            break;
                        }
                    }
                }
            }
        }

        // Clear caches for meetings whose topic/title changed (and rebuild as needed).
        foreach ($courseidstoupdate as $courseid) {
            rebuild_course_cache($courseid, true);
        }

        return true;
    }
}
