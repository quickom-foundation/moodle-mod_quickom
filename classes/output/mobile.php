<?php

namespace mod_quickom\output;

defined('MOODLE_INTERNAL') || die();

use context_module;
use mod_quickom_external;

/**
 * Mobile output class for quickom
 *
 * @package	   mod_quickom
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2018 Nick Stefanski <nmstefanski@gmail.com>
 * @license	   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * Returns the quickom course view for the mobile app,
     *  including meeting details and launch button (if applicable).
     * @param  array $args Arguments from tool_mobile_get_content WS
     *
     * @return array   	HTML, javascript and otherdata
     */
    public static function mobile_course_view($args) {
        global $OUTPUT, $USER, $DB;

        $args = (object) $args;
        $cm = get_coursemodule_from_id('quickom', $args->cmid);

        // Capabilities check.
        require_login($args->courseid, false, $cm, true, true);

        $context = context_module::instance($cm->id);

        require_capability('mod/quickom:view', $context);
        // right now we're just implementing basic viewing, otherwise we may need to check other capabilities
        $quickom = $DB->get_record('quickom', array('id' => $cm->instance));

        //WS to get quickom state
        try {
            $quickom_state = mod_quickom_external::get_state($cm->id);
        } catch (\Exception $e) {
            $quickom_state = array();
        }

        //format date and time
        $start_time = userdate($quickom->start_time);
        $duration = format_time($quickom->duration);

        //get audio option string
        $option_audio = get_string('audio_' . $quickom->option_audio, 'mod_quickom');

        $data = array(
            'quickom' => $quickom,
            'available' => $quickom_state['available'],
            'status' => $quickom_state['status'],
            'start_time' => $start_time,
            'duration' => $duration,
            'option_audio' => $option_audio,
            //'userishost' => $userishost,
            'cmid' => $cm->id,
            'courseid' => $args->courseid
        );

        return array(
            'templates' => array(
                array(
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_quickom/mobile_view_page', $data),
                ),
            ),
            'javascript' => "this.loadMeeting = function(result) { window.open(result.joinurl, '_system'); };",
            // this JS will redirect to a joinurl passed by the mod_quickom_grade_item_update WS
            'otherdata' => '',
            'files' => ''
        );
    }

}
