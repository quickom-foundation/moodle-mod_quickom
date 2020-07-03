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
 * Quickom external functions and service definitions.
 *
 * @package    mod_quickom
 * @category   external
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2017 Auguste Escoffier School of Culinary Arts {@link https://www.escoffier.edu}
 * @author     based on work by Nick Stefanski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */

defined('MOODLE_INTERNAL') || die;

$functions = array(

    'mod_quickom_get_state' => array(
        'classname'     => 'mod_quickom_external',
        'methodname'    => 'get_state',
        'classpath'     => 'mod/quickom/classes/external.php',
        'description'   => 'Determine if a quickom meeting is available, meeting '
        . 'status, and the start time, duration, and other meeting options.',
        'type'          => 'read',
        'capabilities'  => 'mod/quickom:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'),
    ),

    'mod_quickom_grade_item_update' => array(
        'classname'     => 'mod_quickom_external',
        'methodname'    => 'grade_item_update',
        'classpath'     => 'mod/quickom/classes/external.php',
        'description'   => 'Creates or updates grade item for the given quickom instance and returns join url.',
        'type'          => 'write',
        'capabilities'  => 'mod/quickom:view',
        'services'      => array(MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile'),
    ),

);
