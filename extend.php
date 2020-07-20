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
 * Internal library of functions for module quickom
 *
 * All the quickom specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_quickom
 * @copyright  2020 Beowulf Blockchain.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once $CFG->libdir . "/externallib.php";

header('Content-Type: application/json');

class TempData {

    public static function get_temp_user() {
        global $USER;
        // For get_user.
        return [
            "id" => "55elw9y_SQSphrafVw_xdQ",
            "first_name" => $USER->firstname,
            "last_name" => $USER->lastname,
            "email" => $USER->email,
            "type" => 1,
            "role_name" => "Owner",
            "pmi" => 6830294026,
            "use_pmi" => false,
            "personal_meeting_url" => "https://quickom.beowulfchain.com/call/71811585310839099",
            "timezone" => "Asia/Bangkok",
            "verified" => 1,
            "dept" => "",
            "created_at" => "2020-03-25T06:10:21Z",
            "last_login_time" => "2020-03-30T03:30:53Z",
            "host_key" => "638016",
            "jid" => "55elw9y_sqsphrafvw_xdq@xmpp.quickom.com",
            "group_ids" => [],
            "im_group_ids" => [],
            "account_id" => "bqWaMJLzQjyGwQ6oQzdc7A",
            "language" => "en-US",
            "phone_country" => "",
            "phone_number" => "",
            "status" => "active",
        ];
    }

    public static function get_temp_user_settings($userid) {
        // For _get_user_settings.
        return [
            "schedule_meeting" =>
            [
                "host_video" => false,
                "participants_video" => false,
                "audio_type" => "both",
                "join_before_host" => true,
                "use_pmi_for_scheduled_meetings" => false,
                "use_pmi_for_instant_meetings" => false,
                "enforce_login" => false,
                "not_store_meeting_topic" => false,
                "force_pmi_jbh_password" => false,
                "require_password_for_scheduling_new_meetings" => true,
                "require_password_for_instant_meetings" => true,
                "require_password_for_pmi_meetings" => "none",
                "pmi_password" => "",
                "pstn_password_protected" => false,
            ],
            "in_meeting" =>
            [
                "e2e_encryption" => false,
                "chat" => true,
                "private_chat" => true,
                "auto_saving_chat" => false,
                "entry_exit_chime" => "none",
                "record_play_voice" => false,
                "file_transfer" => false,
                "feedback" => false,
                "attendee_on_hold" => false,
                "show_meeting_control_toolbar" => false,
                "annotation" => true,
                "remote_control" => true,
                "non_verbal_feedback" => false,
                "breakout_room" => false,
                "remote_support" => false,
                "closed_caption" => false,
                "virtual_background" => true,
                "far_end_camera_control" => false,
                "attention_tracking" => false,
                "waiting_room" => false,
            ],
            "email_notification" => [
                "jbh_reminder" => true,
                "cancel_meeting_reminder" => true,
            ],
            "recording" => [
                "local_recording" => true,
                "auto_recording" => "none",
                "auto_delete_cmr" => false,
            ],
            "telephony" => [
                "show_international_numbers_link" => true,
            ],
            "tsp" => [],
            "feature" => [
                "meeting_capacity" => 100,
                "large_meeting" => false,
                "webinar" => false,
                "cn_meeting" => false,
                "in_meeting" => false,
                "quickom_phone" => false,
            ],
            "integration" => [
                "linkedin_sales_navigator" => false,
            ],
        ];
    }

    public static function get_temp_meeting_webinar_info() {
        // For get_meeting_webinar_info.
        return [
            "uuid" => "icGbe5LTSBSiXtKOSuhmsg==",
            "id" => 174202572,
            "host_id" => "55elw9y_SQSphrafVw_xdQ",
            "topic" => "test4",
            "type" => 2,
            "status" => "waiting",
            "start_time" => "2020-03-30T04:03:00Z",
            "duration" => 60 * 24,
            "timezone" => "Europe/Berlin",
            "created_at" => "2020-03-30T04:04:14Z",
            "start_url" => "https://quickom.beowulfchain.com/call/71811585310839099",
            "join_url" => "https://quickom.beowulfchain.com/call/71811585310839099",
            "settings" => [
                "host_video" => true,
                "participant_video" => true,
                "cn_meeting" => false,
                "in_meeting" => false,
                "join_before_host" => false,
                "mute_upon_entry" => false,
                "watermark" => false,
                "use_pmi" => false,
                "approval_type" => 2,
                "audio" => "both",
                "auto_recording" => "none",
                "enforce_login" => false,
                "enforce_login_domains" => "",
                "alternative_hosts" => "",
                "close_registration" => false,
                "registrants_confirmation_email" => true,
                "waiting_room" => false,
                "registrants_email_notification" => true,
                "meeting_authentication" => false,
            ],
        ];
    }

    public static function get_temp_users_id_meetings($data) {
        return [
            "uuid" => "uKLWauBXQtiDW5QOEDojMA==",
            "id" => 214673349,
            "host_id" => "55elw9y_SQSphrafVw_xdQ",
            "topic" => $data["topic"],
            "type" => 2,
            "status" => "waiting",
            "start_time" => $data["start_time"],
            "duration" => $data["duration"],
            "timezone" => $data["timezone"],
            "created_at" => "2020-03-30T04:49:44Z",
            "start_url" => "https://quickom.beowulfchain.com/call/71811585310839099",
            "join_url" => "https://quickom.beowulfchain.com/call/71811585310839099",
            "settings" => [
                "host_video" => true,
                "participant_video" => true,
                "cn_meeting" => false,
                "in_meeting" => false,
                "join_before_host" => false,
                "mute_upon_entry" => false,
                "watermark" => false,
                "use_pmi" => false,
                "approval_type" => 2,
                "audio" => "both",
                "auto_recording" => "none",
                "enforce_login" => false,
                "enforce_login_domains" => "",
                "alternative_hosts" => "",
                "close_registration" => false,
                "registrants_confirmation_email" => true,
                "waiting_room" => false,
                "registrants_email_notification" => true,
                "meeting_authentication" => false,
            ],
        ];
    }
}

class Extend {

    public static function call_api($method, $url, $data, $headers = []) {
        global $CFG;

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_CAINFO, __DIR__ . "/cacert.pem"); // For SSL certification.

        switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            break;
        case "DELETE":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            break;

        default:
            if ($data) {
                $url = sprintf("%s?%s", $url, http_build_query($data));
            }
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        if (empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data),
                'Accept: application/json',
            ));
        } else {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array_merge(
                [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data),
                    'Accept: application/json',
                ],
                $headers
            ));
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        $result = curl_exec($curl);
        if ($result === false) {
            $err = 'Curl error: ' . curl_error($curl);
            throw new moodle_exception('errorwebservice', 'mod_quickom', '', get_string('connectionfailed', 'quickom'), $err);
        }
        curl_close($curl);
        return json_decode($result, true);
    }

    public static function prepare_data_to_create_update_classroom($quickom) {
        global $USER;
        $label = "";
        $course = get_course($quickom->course);
        if ($course) {
            $label = $course->fullname;
            $sectionname = get_section_name($course, $quickom->section);
            if ($sectionname) {
                $label = $label . ' - ' . $sectionname;
            }
        }
        if (empty($label)) {
            $label = $quickom->name;
        } else {
            $label = $label . ' - ' . $quickom->name;
        }

        $starttime = $quickom->start_time * 1000; // Seconds to miliseconds.
        $endtime = $starttime + $quickom->duration * 1000;
        $validfrom = time() * 1000;
        $validto = $endtime + 30 * 60 * 1000; // 30 minute later.
        $expireat = $validto + 30 * 60 * 1000; // 30 minute later.
        $args = [
            "classroom" => true,
            'label' => $label,
            'start_date' => $starttime,
            'end_date' => $endtime,
            'valid_from' => $validfrom,
            'valid_to' => $validto,
            'expire_at' => $expireat,
            'passcode' => empty($quickom->password) ? "" : $quickom->password,
            'host_name' => fullname($USER),
            'alias' => $quickom->alias,
        ];

        return $args;
    }

    public static function create_quickom_qr_code($quickom) {
        $args = self::prepare_data_to_create_update_classroom($quickom);
        $config = get_config('mod_quickom');
        if (empty($config->apikey)) {
            throw new moodle_exception('errorwebservice', 'mod_quickom', '', get_string('quickomerr_apikey_missing', 'quickom'));
        }
        $method = 'POST';
        $url = 'https://' . QK_API_URL . 'apiv1/enterprise/qrcode/create';
        $headers = ['Authorization: ' . $config->apikey];
        $data = json_encode($args);
        $response = self::call_api($method, $url, $data, $headers);
        if (!empty($response['url'])) {
            return $response;
        }
        return null;
    }

    public static function update_quickom_qr_code($quickom) {
        $args = self::prepare_data_to_create_update_classroom($quickom);
        $config = get_config('mod_quickom');
        if (empty($config->apikey)) {
            throw new moodle_exception('errorwebservice', 'mod_quickom', '', get_string('quickomerr_apikey_missing', 'quickom'));
        }
        $method = 'POST';
        $url = 'https://' . QK_API_URL . 'apiv1/enterprise/qrcode/update';
        $headers = ['Authorization: ' . $config->apikey];
        $data = json_encode($args);
        $response = self::call_api($method, $url, $data, $headers);
        if (!empty($response['url'])) {
            return $response;
        }
        if (!empty($response['error'])) {
            throw new moodle_exception(
                'errorwebservice',
                'mod_quickom',
                '',
                get_string('quickomerr', 'quickom'), $response['error'] . " : " . $response['error_description']);
        }
        return null;
    }

    public static function delete_quickom_qr_code($quickom) {
        $args = [
            'alias' => $quickom->alias,
        ];
        $config = get_config('mod_quickom');
        if (empty($config->apikey)) {
            throw new moodle_exception('errorwebservice', 'mod_quickom', '', get_string('quickomerr_apikey_missing', 'quickom'));
        }
        $method = 'DELETE';
        $url = 'https://' . QK_API_URL . 'apiv1/enterprise/qrcode/delete';
        $headers = ['Authorization: ' . $config->apikey];
        $data = json_encode($args);
        $response = self::call_api($method, $url, $data, $headers);
        if (!empty($response['url'])) {
            return $response;
        }
        if (!empty($response['error']) && $response['error'] != "404010") {
            throw new moodle_exception(
                'errorwebservice',
                'mod_quickom',
                '', get_string('quickomerr', 'quickom'), $response['error'] . " : " . $response['error_description']);
        }
        return null;
    }

    public static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64url_decode($data) {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }
}
