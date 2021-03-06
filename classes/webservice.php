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
 * Handles API calls to Quickom REST API.
 *
 * @package    mod_quickom
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quickom/locallib.php');
require_once($CFG->dirroot . '/lib/filelib.php');

require_once($CFG->dirroot . '/mod/quickom/extend.php');

define('QK_API_URL', 'conference-api-prod.quickom.com');
define('API_CREATE_QR_CODE', QK_API_URL.'/api/account/qrcode/create');
define('API_DELETE_QR_CODE', QK_API_URL.'/api/account/qrcode/delete');
define('API_UPDATE_QR_CODE', QK_API_URL.'/api/account/qrcode/update');

/**
 * Web service class.
 *
 * @package    mod_quickom
 * @copyright  2020 Beowulf Blockchain.
 * @copyright  based on work by 2015 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quickom_webservice {

    /**
     * API key
     * @var string
     */
    protected $apikey;

    /**
     * API secret
     * @var string
     */
    protected $apisecret;

    /**
     * Whether to recycle licenses.
     * @var bool
     */
    protected $recyclelicenses;

    /**
     * Maximum limit of paid users
     * @var int
     */
    protected $numlicenses;

    /**
     * List of users
     * @var array
     */
    protected static $userslist;

    /**
     * The constructor for the webservice class.
     * @throws moodle_exception Moodle exception is thrown for missing config settings.
     */
    public function __construct() {
        $config = get_config('mod_quickom');
        if (!empty($config->apikey)) {
            $this->apikey = $config->apikey;
        } else {
            throw new moodle_exception('errorwebservice', 'mod_quickom', '', get_string('quickomerr_apikey_missing', 'quickom'));
        }
        if (!empty($config->utmost)) {
            $this->recyclelicenses = $config->utmost;
        }
        if ($this->recyclelicenses) {
            if (!empty($config->licensescount)) {
                $this->numlicenses = $config->licensescount;
            } else {
                throw new moodle_exception(
                    'errorwebservice',
                    'mod_quickom',
                    '',
                    get_string('quickomerr_licensescount_missing', 'quickom')
                );
            }
        }
    }

    /**
     * Makes a REST call.
     *
     * @param string $url The URL to append to the API URL
     * @param array|string $data The data to attach to the call.
     * @param string $method The HTTP method to use.
     * @return stdClass The call's result in JSON format.
     * @throws moodle_exception Moodle exception is thrown for curl errors.
     */
    protected function _make_call($url, $data = [], $method = 'get') {
        $url = QK_API_URL . '/' . $url;
        $method = strtolower($method);
        $curl = new curl();
        $curl->setHeader('Authorization: ' . $this->apikey);

        if ($method != 'get') {
            $curl->setHeader('Content-Type: application/json');
            $data = is_array($data) ? json_encode($data) : $data;
        }
        $response = call_user_func_array([$curl, $method], [$url, $data]);

        if ($curl->get_errno()) {
            throw new moodle_exception('errorwebservice', 'mod_quickom', '', $curl->error);
        }

        $response = json_decode($response);

        $httpstatus = $curl->get_info()['http_code'];
        if ($httpstatus >= 400) {
            if ($response) {
                throw new moodle_exception('errorwebservice', 'mod_quickom', '', $response->message);
            } else {
                throw new moodle_exception('errorwebservice', 'mod_quickom', '', "HTTP Status $httpstatus");
            }
        }

        return $response;
    }

    /**
     * Makes a paginated REST call.
     * Makes a call like _make_call() but specifically for GETs with paginated results.
     *
     * @param string $url The URL to append to the API URL
     * @param array|string $data The data to attach to the call.
     * @param string $datatoget The name of the array of the data to get.
     * @return array The retrieved data.
     * @see _make_call()
     * @link https://quickom.github.io/api/#list-users
     */
    protected function _make_paginated_call($url, $data = [], $datatoget) {
        $aggregatedata = [];
        $data['page_size'] = QUICKOM_MAX_RECORDS_PER_CALL;
        $reportcheck = explode('/', $url);
        $isreportcall = in_array('report', $reportcheck);
        // The $currentpage call parameter is 1-indexed.
        for ($currentpage = $numpages = 1; $currentpage <= $numpages; $currentpage++) {
            $data['page_number'] = $currentpage;
            $callresult = null;
            if ($isreportcall) {
                $numcalls = get_config('mod_quickom', 'calls_left');
                if ($numcalls > 0) {
                    $callresult = $this->_make_call($url, $data);
                    set_config('calls_left', $numcalls - 1, 'mod_quickom');
                    sleep(1);
                }
            } else {
                $callresult = $this->_make_call($url, $data);
            }

            if ($callresult) {
                $aggregatedata = array_merge($aggregatedata, $callresult->$datatoget);
                // Note how continually updating $numpages accomodates for the edge case that users are added in between calls.
                $numpages = $callresult->page_count;
            }
        }

        return $aggregatedata;
    }

    /**
     * Autocreate a user on Quickom.
     *
     * @param stdClass $user The user to create.
     * @return bool Whether the user was succesfully created.
     * @link https://quickom.github.io/api/#create-a-user
     */
    public function autocreate_user($user) {
        $url = 'users';
        $data = ['action' => 'autocreate'];
        $data['user_info'] = [
            'email' => $user->email,
            'type' => QUICKOM_USER_TYPE_PRO,
            'first_name' => $user->firstname,
            'last_name' => $user->lastname,
            'password' => base64_encode(random_bytes(16)),
        ];

        try {
            $this->_make_call($url, $data, 'post');
        } catch (moodle_exception $error) {
            // If the user already exists, the error will contain 'User already in the account'.
            if (strpos($error->getMessage(), 'User already in the account') === true) {
                return false;
            } else {
                throw $error;
            }
        }

        return true;
    }

    /**
     * Get users list.
     *
     * @return array An array of users.
     * @link https://quickom.github.io/api/#list-users
     */
    public function list_users() {
        if (empty(self::$userslist)) {
            self::$userslist = $this->_make_paginated_call('users', null, 'users');
        }
        return self::$userslist;
    }

    /**
     * Checks whether the paid user license limit has been reached.
     *
     * Incrementally retrieves the active paid users and compares against $numlicenses.
     * @see $numlicenses
     * @return bool Whether the paid user license limit has been reached.
     */
    protected function _paid_user_limit_reached() {
        $userslist = $this->list_users();
        $numusers = 0;
        foreach ($userslist as $user) {
            if ($user->type != QUICKOM_USER_TYPE_BASIC && ++$numusers >= $this->numlicenses) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gets the ID of the user, of all the paid users, with the oldest last login time.
     *
     * @return string|false If user is found, returns the User ID. Otherwise, returns false.
     */
    protected function _get_least_recently_active_paid_user_id() {
        $usertimes = [];
        $userslist = $this->list_users();
        foreach ($userslist as $user) {
            if ($user->type != QUICKOM_USER_TYPE_BASIC && isset($user->last_login_time)) {
                $usertimes[$user->id] = strtotime($user->last_login_time);
            }
        }

        if (!empty($usertimes)) {
            return array_search(min($usertimes), $usertimes);
        }

        return false;
    }

    /**
     * Gets a user's settings.
     *
     * @param string $userid The user's ID.
     * @return stdClass The call's result in JSON format.
     * @link https://quickom.github.io/api/#retrieve-a-users-settings
     */
    public function _get_user_settings($userid) {
        $temp = TempData::get_temp_user_settings($userid);
        return $temp;
    }

    /**
     * Gets a user.
     *
     * @param string|int $identifier The user's email or the user's ID per Quickom API.
     * @return stdClass|false If user is found, returns the User object. Otherwise, returns false.
     * @link https://quickom.github.io/api/#users
     */
    public function get_user($identifier) {
        $temp = TempData::get_temp_user();
        return $temp;
    }

    /**
     * Converts a quickom object from database format to API format.
     *
     * The database and the API use different fields and formats for the same information. This function changes the
     * database fields to the appropriate API request fields.
     *
     * @param stdClass $quickom The quickom meeting to format.
     * @return array The formatted meetings for the meeting.
     * @todo Add functionality for 'alternative_hosts' => $quickom->option_alternative_hosts in $data['settings']
     * @todo Make UCLA data fields and API data fields match?
     */
    protected function _database_to_api($quickom) {
        global $CFG;

        $data = [
            'topic' => $quickom->name,
            'settings' => [
                'host_video' => (bool) empty($quickom->option_host_video) ? true : $quickom->option_host_video,
                'audio' => empty($quickom->option_audio) ? true : $quickom->option_audio,
            ],
        ];
        if (isset($quickom->intro)) {
            $data['agenda'] = strip_tags($quickom->intro);
        }
        if (isset($CFG->timezone) && !empty($CFG->timezone)) {
            $data['timezone'] = $CFG->timezone;
        } else {
            $data['timezone'] = date_default_timezone_get();
        }
        if (isset($quickom->password)) {
            $data['password'] = $quickom->password;
        }
        if (isset($quickom->alternative_hosts)) {
            $data['settings']['alternative_hosts'] = $quickom->alternative_hosts;
        }

        if (!empty($quickom->webinar)) {
            $data['type'] = (!empty($quickom->recurring)) ? QUICKOM_RECURRING_WEBINAR : QUICKOM_SCHEDULED_WEBINAR;
        } else {
            $data['type'] = (!empty($quickom->recurring)) ? QUICKOM_RECURRING_MEETING : QUICKOM_SCHEDULED_MEETING;
            $data['settings']['join_before_host'] = (bool) empty($quickom->option_jbh) ? true : $quickom->option_jbh;
            $data['settings']['participant_video'] = (bool) empty($quickom->option_participants_video) ? true : $quickom->option_participants_video;
        }

        if ($data['type'] == QUICKOM_SCHEDULED_MEETING || $data['type'] == QUICKOM_SCHEDULED_WEBINAR) {
            // Convert timestamp to ISO-8601. The API seems to insist that it end with 'Z' to indicate UTC.
            $data['start_time'] = gmdate('Y-m-d\TH:i:s\Z', $quickom->start_time);
            $data['duration'] = (int) ceil($quickom->duration / 60);
        }

        return $data;
    }

    /**
     * Create a meeting/webinar on Quickom.
     * Take a $quickom object as returned from the Moodle form and respond with an object that can be saved to the database.
     *
     * @param stdClass $quickom The meeting to create.
     * @param array $params more data.
     * @return stdClass The call response.
     */
    public function create_meeting($quickom, $params = []) {
        global $USER;
        $temp = (object) TempData::get_temp_users_id_meetings($this->_database_to_api($quickom));

        $qkqrcode = Extend::create_quickom_qr_code($quickom);
        if (!empty($qkqrcode['url'])) {
            $temp->start_url = $qkqrcode['tutor_url'];
            $temp->join_url = $qkqrcode['student_url'];
            $temp->creator_id = $USER->id;
            $temp->host_key = $qkqrcode['host_key'];
            $temp->alias = $qkqrcode['alias'];
        }
        return $temp;
    }

    /**
     * Update a meeting/webinar on Quickom.
     *
     * @param stdClass $quickom The meeting to update.
     * @return void
     */
    public function update_meeting($quickom) {
        $response = Extend::update_quickom_qr_code($quickom);
    }

    /**
     * Delete a classroom on Quickom.
     *
     * @param stdClass $quickom The classroom to delete.
     * @return void
     */
    public function delete_meeting($quickom) {
        $response = Extend::delete_quickom_qr_code($quickom);
    }

    /**
     * Get a meeting or webinar's information from Quickom.
     *
     * @param int $id The meeting_id or webinar_id of the meeting or webinar to retrieve.
     * @param bool $webinar Whether the meeting or webinar whose information you want is a webinar.
     * @return stdClass The meeting's or webinar's information.
     */
    public function get_meeting_webinar_info($id, $webinar) {
        $temp = TempData::get_temp_meeting_webinar_info();
        return $temp;
    }

    /**
     * Retrieve ended meetings report for a specified user and period. Handles multiple pages.
     *
     * @param int $userid Id of user of interest
     * @param string $from Start date of period in the form YYYY-MM-DD
     * @param string $to End date of period in the form YYYY-MM-DD
     * @return array The retrieved meetings.
     * @link https://quickom.github.io/api/#retrieve-meetings-report
     */
    public function get_user_report($userid, $from, $to) {
        $url = 'report/users/' . $userid . '/meetings';
        $data = ['from' => $from, 'to' => $to, 'page_size' => QUICKOM_MAX_RECORDS_PER_CALL];
        return $this->_make_paginated_call($url, $data, 'meetings');
    }

    /**
     * List all meeting or webinar information for a user.
     *
     * @param string $userid The user whose meetings or webinars to retrieve.
     * @param boolean $webinar Whether to list meetings or to list webinars.
     * @return array An array of meeting information.
     * @link https://quickom.github.io/api/#list-webinars
     * @link https://quickom.github.io/api/#list-meetings
     */
    public function list_meetings($userid, $webinar) {
        $url = 'users/' . $userid . ($webinar ? '/webinars' : '/meetings');
        $instances = $this->_make_paginated_call($url, null, ($webinar ? 'webinars' : 'meetings'));
        return $instances;
    }

    /**
     * Get attendees for a particular UUID ("session") of a webinar.
     *
     * @param string $uuid The UUID of the webinar session to retrieve.
     * @return array The attendees.
     * @link https://quickom.github.io/api/#list-a-webinars-registrants
     */
    public function list_webinar_attendees($uuid) {
        $url = 'webinars/' . $uuid . '/registrants';
        return $this->_make_paginated_call($url, null, 'registrants');
    }

    /**
     * Get details about a particular webinar UUID/session.
     *
     * @param string $uuid The uuid of the webinar to retrieve.
     * @return stdClass A JSON object with the webinar's details.
     * @link https://quickom.github.io/api/#retrieve-a-webinar
     */
    public function get_metrics_webinar_detail($uuid) {
        return $this->_make_call('webinars/' . $uuid);
    }

    /**
     * Get the participants who attended a meeting
     * @param string $meetinguuid The meeting or webinar's UUID.
     * @param bool $webinar Whether the meeting or webinar whose information you want is a webinar.
     * @return stdClass The meeting report.
     */
    public function get_meeting_participants($meetinguuid, $webinar) {
        return $this->_make_paginated_call('report/' . ($webinar ? 'webinars' : 'meetings') . '/'
            . $meetinguuid . '/participants', null, 'participants');
    }

    /**
     * Retrieves ended webinar details report.
     *
     * @param string|int $identifier The webinar ID or webinar UUID.
     * If given webinar ID, Quickom will take the last webinar instance.
     */
    public function get_webinar_details_report($identifier) {
        return $this->_make_call('report/webinars/' . $identifier);
    }

    /**
     * Retrieve the UUIDs of hosts that were active in the last 30 days.
     *
     * @param int $from The time to start the query from, in Unix timestamp format.
     * @param int $to The time to end the query at, in Unix timestamp format.
     * @return array An array of UUIDs.
     */
    public function get_active_hosts_uuids($from, $to) {
        $users = $this->_make_paginated_call('report/users', ['type' => 'active', 'from' => $from, 'to' => $to], 'users');
        $uuids = [];
        foreach ($users as $user) {
            $uuids[] = $user->id;
        }
        return $uuids;
    }
}
