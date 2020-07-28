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
 * Recreate a meeting that exists on Moodle but cannot be found on Quickom.
 *
 * @package    mod_quickom
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2017 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Login check require_login() is called in quickom_get_instance_setup();.
// @codingStandardsIgnoreLine
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/classes/webservice.php');

list($course, $cm, $quickom) = quickom_get_instance_setup();

require_sesskey();
$context = context_module::instance($cm->id);
// This capability is for managing Quickom instances in general.
require_capability('mod/quickom:addinstance', $context);

$PAGE->set_url('/mod/quickom/recreate.php', array('id' => $cm->id));

// Create a new meeting with Quickom API to replace the missing one.
// We will use the logged-in user's Quickom account to recreate,
// in case the meeting's former owner no longer exists on Quickom.
$quickom->host_id = quickom_get_user_id();
$service = new mod_quickom_webservice();

// Set the current quickom table entry to use the new meeting (meeting_id/etc).
$response = $service->create_meeting($quickom);
$quickom = populate_quickom_from_response($quickom, $response);

$DB->update_record('quickom', $quickom);

// Return to course page.
redirect(course_get_url($course->id));
