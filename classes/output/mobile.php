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
 * Contain mobile output class for quickom
 *
 * @package    mod_quickom
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2018 Nick Stefanski <nmstefanski@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_quickom\output;

defined('MOODLE_INTERNAL') || die();

use context_module;
use mod_quickom_external;

/**
 * Mobile output class for quickom
 *
 * @package    mod_quickom
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2018 Nick Stefanski <nmstefanski@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * Returns the quickom course view for the mobile app,
     *  including meeting details and launch button (if applicable).
     * @param  array $args Arguments from tool_mobile_get_content WS
     *
     * @return array   HTML, javascript and otherdata
     */
    public static function mobile_course_view($args) {
        global $OUTPUT, $USER, $DB;

        $args = (object) $args;
        $versionname = $args->appversioncode >= 3950 ? 'latest' : 'ionic3';
        $cm = get_coursemodule_from_id('quickom', $args->cmid);

        // Capabilities check.
        require_login($args->courseid, false, $cm, true, true);

        $context = context_module::instance($cm->id);

        require_capability('mod/quickom:view', $context);
        // Right now we're just implementing basic viewing, otherwise we may need to check other capabilities.
        $quickom = $DB->get_record('quickom', array('id' => $cm->instance));

        // WS to get quickom state.
        try {
            $quickomstate = mod_quickom_external::get_state($cm->id);
        } catch (\Exception $e) {
            $quickomstate = array();
        }

        // Format date and time.
        $starttime = userdate($quickom->start_time);
        $duration = format_time($quickom->duration);

        // Get audio option string.
        $optionaudio = get_string('audio_' . $quickom->option_audio, 'mod_quickom');

        $data = array(
            'quickom' => $quickom,
            'available' => $quickomstate['available'],
            'status' => $quickomstate['status'],
            'start_time' => $starttime,
            'duration' => $duration,
            'option_audio' => $optionaudio,
            'cmid' => $cm->id,
            'courseid' => $args->courseid,
        );

        return array(
            'templates' => array(
                array(
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template("mod_quickom/mobile_view_page_$versionname", $data),
                ),
            ),
            'javascript' => "this.loadMeeting = function(result) { window.open(result.joinurl, '_system'); };",
            // This JS will redirect to a joinurl passed by the mod_quickom_grade_item_update WS.
            'otherdata' => '',
            'files' => '',
        );
    }

}