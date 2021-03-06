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
 * Export ical file for a quickom meeting.
 *
 * @package    mod_quickom
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->libdir.'/bennu/bennu.inc.php');

// Course_module ID.
$id = required_param('id', PARAM_INT);
if ($id) {
    $cm         = get_coursemodule_from_id('quickom', $id, 0, false, MUST_EXIST);
    $course     = get_course($cm->course);
    $quickom  = $DB->get_record('quickom', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    print_error('You must specify a course_module ID');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$PAGE->set_context($context);

require_capability('mod/quickom:view', $context);

// Start ical file.
$ical = new iCalendar;
$ical->add_property('method', 'PUBLISH');
$ical->add_property('prodid', '-//Moodle Pty Ltd//NONSGML Moodle Version ' . $CFG->version . '//EN');

// Create event and populate properties.
$event = new iCalendar_event;
$hostaddress = str_replace('http://', '', $CFG->wwwroot);
$hostaddress = str_replace('https://', '', $hostaddress);
$event->add_property('uid', $quickom->meeting_id . '@' . $hostaddress); // A unique identifier.
$event->add_property('summary', $quickom->name); // Title.
$event->add_property('dtstamp', Bennu::timestamp_to_datetime()); // Time of creation.
$event->add_property('last-modified', Bennu::timestamp_to_datetime($quickom->timemodified));
$event->add_property('dtstart', Bennu::timestamp_to_datetime($quickom->start_time)); // Start time.
$event->add_property('dtend', Bennu::timestamp_to_datetime($quickom->start_time + $quickom->duration)); // End time.

// Compute and add description property to event.
$convertedtext = html_to_text($quickom->intro);
$descriptiontext = get_string('calendardescriptionURL', 'mod_quickom', $quickom->join_url);
if (!empty($convertedtext)) {
    $descriptiontext .= get_string('calendardescriptionintro', 'mod_quickom', $convertedtext);
}
$event->add_property('description', $descriptiontext);

// Start formatting ical.
$ical->add_component($event);
$serialized = $ical->serialize();
$filename = 'icalexport.ics';

// Create headers.
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
header('Expires: ' . gmdate('D, d M Y H:i:s', 0) . 'GMT');
header('Pragma: no-cache');
header('Accept-Ranges: none'); // Comment out if PDFs do not work...
header('Content-disposition: attachment; filename=' . $filename);
header('Content-length: ' . strlen($serialized));
header('Content-type: text/calendar; charset=utf-8');

echo $serialized;