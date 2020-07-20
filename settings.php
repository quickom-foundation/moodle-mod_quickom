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
 * Settings.
 *
 * @package    mod_quickom
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot . '/mod/quickom/locallib.php';

if ($ADMIN->fulltree) {
    require_once $CFG->dirroot . '/mod/quickom/locallib.php';
    require_once $CFG->dirroot . '/mod/quickom/classes/webservice.php';

    $settings = new admin_settingpage('modsettingquickom', get_string('pluginname', 'mod_quickom'));

    // Test whether connection works and display result to user.
    if (!CLI_SCRIPT && $PAGE->url == $CFG->wwwroot . '/' . $CFG->admin . '/settings.php?section=modsettingquickom') {
        $status = 'connectionok';
        $notifyclass = 'notifysuccess';
        $errormessage = '';
        try {
            $service = new mod_quickom_webservice();
            $service->get_user($USER->email);
        } catch (moodle_exception $error) {
            $notifyclass = 'notifyproblem';
            $status = 'connectionfailed';
            $errormessage = $error->a;
        }
        $statusmessage = $OUTPUT->notification(get_string('connectionstatus', 'quickom') .
            ': ' . get_string($status, 'quickom') . $errormessage, $notifyclass);
        $connectionstatus = new admin_setting_heading('mod_quickom/connectionstatus', $statusmessage, '');
        $settings->add($connectionstatus);
    }

    $apikey = new admin_setting_configtext('mod_quickom/apikey', get_string('apikey', 'mod_quickom'),
        get_string('apikey_desc', 'mod_quickom'), '');
    $settings->add($apikey);

    $quickomurl = new admin_setting_configtext('mod_quickom/quickomurl', get_string('quickomurl', 'mod_quickom'),
        get_string('quickomurl_desc', 'mod_quickom'), '', PARAM_URL);
    $settings->add($quickomurl);

    $jointimechoices = array(0, 5, 10, 15, 20, 30, 45, 60);
    $jointimeselect = array();
    foreach ($jointimechoices as $minutes) {
        $jointimeselect[$minutes] = $minutes . ' ' . get_string('mins');
    }
    $firstabletojoin = new admin_setting_configselect('mod_quickom/firstabletojoin',
        get_string('firstjoin', 'mod_quickom'), get_string('firstjoin_desc', 'mod_quickom'),
        15, $jointimeselect);
    $settings->add($firstabletojoin);

    $settings->add(new admin_setting_heading(
        'defaultsettings', get_string('defaultsettings', 'mod_quickom'), get_string('defaultsettings_help', 'mod_quickom')));

    $defaulthostvideo = new admin_setting_configcheckbox(
        'mod_quickom/defaulthostvideo', get_string('option_host_video', 'quickom'), '', 1, 1, 0);
    $settings->add($defaulthostvideo);

    $defaultparticipantsvideo = new admin_setting_configcheckbox(
        'mod_quickom/defaultparticipantsvideo', get_string('option_participants_video', 'quickom'), '', 1, 1, 0);
    $settings->add($defaultparticipantsvideo);

    $defaultjoinbeforehost = new admin_setting_configcheckbox(
        'mod_quickom/defaultjoinbeforehost', get_string('option_jbh', 'quickom'), '', 0, 1, 0);
    $settings->add($defaultjoinbeforehost);

}
