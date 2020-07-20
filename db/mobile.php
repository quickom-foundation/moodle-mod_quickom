<?php
// This file is part of the Quickom module for Moodle - http://moodle.org/
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
 * Quickom module capability definition
 *
 * @package    mod_quickom
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2018 Nick Stefanski <nmstefanski@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$addons = array(
    "mod_quickom" => array(
        "handlers" => array(
            'quickommeetingdetails' => array(
                'displaydata' => array(
                    'title' => 'pluginname',
                    'icon' => $CFG->wwwroot . '/mod/quickom/pix/icon.gif',
                    'class' => '',
                ),

                'delegate' => 'CoreCourseModuleDelegate',
                'method' => 'mobile_course_view', // Main function in \mod_quickom\output\mobile.
                'offlinefunctions' => array(
                    'mobile_course_view' => array(),
                ),
            ),
        ),
        'lang' => array(
            array('pluginname', 'quickom'),
            array('join_meeting', 'quickom'),
            array('unavailable', 'quickom'),
            array('meeting_time', 'quickom'),
            array('duration', 'quickom'),
            array('passwordprotected', 'quickom'),
            array('password', 'quickom'),
            array('join_link', 'quickom'),
            array('joinbeforehost', 'quickom'),
            array('starthostjoins', 'quickom'),
            array('startpartjoins', 'quickom'),
            array('option_audio', 'quickom'),
            array('status', 'quickom'),
            array('recurringmeetinglong', 'quickom'),
        ),
    ),
);
