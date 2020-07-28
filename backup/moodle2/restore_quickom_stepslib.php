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
 * Define all the restore steps that will be used by the restore_quickom_activity_task
 *
 * @package    mod_quickom
 * @category   backup
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quickom/lib.php');
require_once($CFG->dirroot . '/mod/quickom/locallib.php');
require_once($CFG->dirroot . '/mod/quickom/classes/webservice.php');

/**
 * Structure step to restore one quickom activity
 *
 * @package    mod_quickom
 * @category   backup
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_quickom_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines structure of path elements to be processed during the restore
     *
     * @return array of {restore_path_element}
     */
    protected function define_structure() {

        $userinfo = $this->get_setting_value('userinfo');
        $paths = array();
        $paths[] = new restore_path_element('quickom', '/activity/quickom');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the given restore path element data
     *
     * @param array $data parsed element data
     */
    protected function process_quickom($data) {
        global $DB;

        $data = (object) $data;
        $service = new mod_quickom_webservice();

        // Either create a new meeting or set meeting as expired.
        $response = $service->create_meeting($data);
        if (!$response) {
            $response = new stdClass;
            $response->status = QUICKOM_MEETING_EXPIRED;
        }
        $data = populate_quickom_from_response($data, $response);

        $data->course = $this->get_courseid();

        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }

        if ($data->grade < 0) {
            // Scale found, get mapping.
            $data->grade = -($this->get_mappingid('scale', abs($data->grade)));
        }

        // Create the quickom instance.
        $newitemid = $DB->insert_record('quickom', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Post-execution actions
     */
    protected function after_execute() {
        // Add quickom related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_quickom', 'intro', null);
    }
}