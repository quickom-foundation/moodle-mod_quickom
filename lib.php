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
 * @copyright  based on work by 2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quickom/extend.php');

/* Moodle core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function quickom_supports($feature) {
    switch ($feature) {
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_GROUPINGS:
        case FEATURE_GROUPMEMBERSONLY:
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the quickom object into the database.
 *
 * Given an object containing all the necessary data (defined by the form in mod_form.php), this function
 * will create a new instance and return the id number of the new instance.
 *
 * @param stdClass $quickom Submitted data from the form in mod_form.php
 * @param mod_quickom_mod_form $mform The form instance (included because the function is used as a callback)
 * @return int The id of the newly inserted quickom record
 */
function quickom_add_instance(stdClass $quickom, mod_quickom_mod_form $mform = null) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/quickom/classes/webservice.php');

    $quickom->course = (int) $quickom->course;

    $service = new mod_quickom_webservice();
    $response = $service->create_meeting($quickom);
    $quickom = populate_quickom_from_response($quickom, $response);

    $quickom->id = $DB->insert_record('quickom', $quickom);

    quickom_calendar_item_update($quickom);
    quickom_grade_item_update($quickom);

    return $quickom->id;
}

/**
 * Updates an instance of the quickom in the database and on Quickom servers.
 *
 * Given an object containing all the necessary data (defined by the form in mod_form.php), this function
 * will update an existing instance with new data.
 *
 * @param stdClass $quickom An object from the form in mod_form.php
 * @param mod_quickom_mod_form $mform The form instance (included because the function is used as a callback)
 * @return boolean Success/Failure
 */
function quickom_update_instance(stdClass $quickom, mod_quickom_mod_form $mform = null) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/quickom/classes/webservice.php');

    // The object received from mod_form.php returns instance instead of id for some reason.
    $quickom->id = $quickom->instance;
    $quickom->timemodified = time();

    $quickomrecord = $DB->get_record('quickom', ['id' => $quickom->instance]);
    $quickom->alias = $quickomrecord->alias;

    if (empty($quickomrecord->alias)) {
        return false;
    }
    $service = new mod_quickom_webservice();
    try {
        $service->update_meeting($quickom);
        $DB->update_record('quickom', $quickom);
    } catch (moodle_exception $error) {
        return false;
    }

    quickom_calendar_item_update($quickom);
    quickom_grade_item_update($quickom);

    return true;
}

/**
 * Populates a quickom meeting or webinar from a response object.
 *
 * Given a quickom meeting object from mod_form.php, this function uses the response to repopulate some of the object properties.
 *
 * @param stdClass $quickom An object from the form in mod_form.php
 * @param stdClass $response A response from an API call like 'create meeting' or 'update meeting'
 * @return stdClass A $quickom object ready to be added to the database.
 */
function populate_quickom_from_response(stdClass $quickom, stdClass $response) {
    global $CFG;
    // Inlcuded for constants.
    require_once($CFG->dirroot . '/mod/quickom/locallib.php');

    $newquickom = clone $quickom;

    $samefields = ['start_url', 'join_url', 'created_at', 'timezone', 'creator_id', 'host_key', 'alias'];
    foreach ($samefields as $field) {
        if (isset($response->$field)) {
            $newquickom->$field = $response->$field;
        }
    }
    if (isset($response->duration)) {
        $newquickom->duration = $response->duration * 60;
    }
    $newquickom->meeting_id = $response->id;
    $newquickom->name = $response->topic;
    if (isset($response->agenda)) {
        $newquickom->intro = $response->agenda;
    }
    if (isset($response->start_time)) {
        $newquickom->start_time = strtotime($response->start_time);
    }
    $newquickom->recurring = $response->type == QUICKOM_RECURRING_MEETING || $response->type == QUICKOM_RECURRING_WEBINAR;
    if (isset($response->password)) {
        $newquickom->password = $response->password;
    }
    if (isset($response->settings->join_before_host)) {
        $newquickom->option_jbh = $response->settings->join_before_host;
    }
    if (isset($response->settings->participant_video)) {
        $newquickom->option_participants_video = $response->settings->participant_video;
    }
    if (isset($response->settings->alternative_hosts)) {
        $newquickom->alternative_hosts = $response->settings->alternative_hosts;
    }
    $newquickom->timemodified = time();

    return $newquickom;
}

/**
 * Removes an instance of the quickom from the database
 *
 * Given an ID of an instance of this module, this function will permanently delete the instance and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 * @throws moodle_exception if failed to delete and quickom did not issue a not found error
 */
function quickom_delete_instance($id) {

    global $CFG, $DB;
    require_once($CFG->dirroot . '/mod/quickom/classes/webservice.php');

    if (!$quickom = $DB->get_record('quickom', ['id' => $id])) {
        return false;
    }

    // Include locallib.php for constants.
    require_once($CFG->dirroot . '/mod/quickom/locallib.php');

    // If the meeting is missing from quickom, don't bother with the webservice.
    if ($quickom->exists_on_quickom) {
        $service = new mod_quickom_webservice();
        try {
            $service->delete_meeting($quickom);
        } catch (moodle_exception $error) {
            if (strpos($error, 'is not found or has expired') === false) {
                throw $error;
            }
        }
    }

    $DB->delete_records('quickom', ['id' => $quickom->id]);

    // If we delete a meeting instance, do we want to delete the participants?
    $meetinginstances = $DB->get_records('quickom_meeting_details', ['meeting_id' => $quickom->meeting_id]);
    foreach ($meetinginstances as $meetinginstance) {
        $DB->delete_records('quickom_meeting_participants', ['uuid' => $meetinginstance->uuid]);
    }
    $DB->delete_records('quickom_meeting_details', ['meeting_id' => $quickom->meeting_id]);

    // Delete any dependent records here.
    quickom_calendar_item_delete($quickom);
    quickom_grade_item_delete($quickom);

    return true;
}

/**
 * Given a course and a time, this module should find recent activity that has occurred in quickom activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean true if anything was printed, otherwise false
 * @todo implement this function
 */
function quickom_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {quickom_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @todo implement this function
 */
function quickom_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid = 0, $groupid = 0) {
}

/**
 * Prints single activity item prepared by {quickom_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 * @todo implement this function
 */
function quickom_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 * @todo implement this function
 */
function quickom_get_extra_capabilities() {
    return [];
}

/**
 * Create or update Moodle calendar event of the Quickom instance.
 *
 * @param stdClass $quickom
 */
function quickom_calendar_item_update(stdClass $quickom) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/calendar/lib.php');

    $event = new stdClass();
    $event->name = $quickom->name;
    if ($quickom->intro) {
        $event->description = $quickom->intro;
        $event->format = $quickom->introformat;
    }
    $event->timestart = $quickom->start_time;
    $event->timeduration = $quickom->duration;
    $event->visible = !$quickom->recurring;

    $eventid = $DB->get_field('event', 'id', [
        'modulename' => 'quickom',
        'instance' => $quickom->id,
    ]);

    // Load existing event object, or create a new one.
    if (!empty($eventid)) {
        calendar_event::load($eventid)->update($event);
    } else {
        $event->courseid = $quickom->course;
        $event->modulename = 'quickom';
        $event->instance = $quickom->id;
        $event->eventtype = 'quickom';
        calendar_event::create($event);
    }
}

/**
 * Delete Moodle calendar event of the Quickom instance.
 *
 * @param stdClass $quickom
 */
function quickom_calendar_item_delete(stdClass $quickom) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/calendar/lib.php');

    $eventid = $DB->get_field('event', 'id', [
        'modulename' => 'quickom',
        'instance' => $quickom->id,
    ]);
    if (!empty($eventid)) {
        calendar_event::load($eventid)->delete();
    }
}

/* Gradebook API */

/**
 * Is a given scale used by the instance of quickom?
 *
 * This function returns if a scale is being used by one quickom
 * if it has support for grading and scales.
 *
 * @param int $quickomid ID of an instance of this module
 * @param int $scaleid ID of the scale
 * @return bool true if the scale is used by the given quickom instance
 */
function quickom_scale_used($quickomid, $scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('quickom', ['id' => $quickomid, 'grade' => -$scaleid])) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of quickom.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale
 * @return boolean true if the scale is used by any quickom instance
 */
function quickom_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('quickom', ['grade' => -$scaleid])) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given quickom instance
 *
 * Needed by {grade_update_mod_grades()}.
 *
 * @param stdClass $quickom instance object with extra cmidnumber and modname property
 * @param array $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return void
 */
function quickom_grade_item_update(stdClass $quickom, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $item = [];
    $item['itemname'] = clean_param($quickom->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($quickom->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax'] = $quickom->grade;
        $item['grademin'] = 0;
    } else if ($quickom->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid'] = -$quickom->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades === 'reset') {
        $item['reset'] = true;
        $grades = null;
    }

    grade_update(
        'mod/quickom',
        $quickom->course,
        'mod',
        'quickom',
        $quickom->id,
        0,
        $grades,
        $item
    );
}

/**
 * Delete grade item for given quickom instance
 *
 * @param stdClass $quickom instance object
 * @return grade_item
 */
function quickom_grade_item_delete($quickom) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update(
        'mod/quickom',
        $quickom->course,
        'mod',
        'quickom',
        $quickom->id,
        0,
        null,
        ['deleted' => 1]
    );
}

/**
 * Update quickom grades in the gradebook
 *
 * Needed by {grade_update_mod_grades()}.
 *
 * @param stdClass $quickom instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 */
function quickom_update_grades(stdClass $quickom, $userid = 0) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    // Populate array of grade objects indexed by userid.
    if ($quickom->grade == 0) {
        quickom_grade_item_update($quickom);
    } else if ($userid != 0) {
        $grade = grade_get_grades($quickom->course, 'mod', 'quickom', $quickom->id, $userid)->items[0]->grades[$userid];
        $grade->userid = $userid;
        if ($grade->grade == -1) {
            $grade->grade = null;
        }
        quickom_grade_item_update($quickom, $grade);
    } else if ($userid == 0) {
        $context = context_course::instance($quickom->course);
        $enrollusersid = array_keys(get_enrolled_users($context));
        $grades = grade_get_grades($quickom->course, 'mod', 'quickom', $quickom->id, $enrollusersid)->items[0]->grades;
        foreach ($grades as $k => $v) {
            $grades[$k]->userid = $k;
            if ($v->grade == -1) {
                $grades[$k]->grade = null;
            }
        }
        quickom_grade_item_update($quickom, $grades);
    } else {
        quickom_grade_item_update($quickom);
    }
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 * @todo implement this function
 */
function quickom_get_file_areas($course, $cm, $context) {
    return [];
}

/**
 * File browsing support for quickom file areas
 *
 * @package mod_quickom
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 * @todo implement this function
 */
function quickom_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the quickom file areas
 *
 * @package mod_quickom
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the quickom's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function quickom_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options = []) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding quickom nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the quickom module instance
 * @param stdClass $course current course record
 * @param stdClass $module current quickom instance record
 * @param cm_info $cm course module information
 * @todo implement this function
 */
function quickom_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
}

/**
 * Extends the settings navigation with the quickom settings
 *
 * This function is called when the context for the page is a quickom module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $quickomnode quickom administration node
 * @todo implement this function
 */
function quickom_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $quickomnode = null) {
}

/**
 * Get icon mapping for font-awesome.
 *
 * @see https://docs.moodle.org/dev/Moodle_icons
 */
function mod_quickom_get_fontawesome_icon_map() {
    return [
        'mod_quickom:i/calendar' => 'fa-calendar',
    ];
}
