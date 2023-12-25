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
 * Edwiser Bridge - WordPress and Moodle integration.
 * This class will add the web services for edwiser bridge.
 *
 * @package local_edwiserbridge
 * @copyright  2016 Wisdmlabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->libdir . "/externallib.php");

class local_edwiserbridge_external extends external_api {
    public function __construct() {
    }


    /**
     * Request to test connection
     *
     * @param  string $wpurl   wpurl.
     * @param  string $wptoken wptoken.
     *
     * @return array
     */
    public static function eb_test_connection($wpurl, $wptoken) {
        $params = self::validate_parameters(
            self::eb_test_connection_parameters(),
            array(
                'wp_url' => $wpurl,
                "wp_token" => $wptoken
            )
        );

        $requestdata = array(
            'action'     => "test_connection",
            'secret_key' => $params["wp_token"]
        );

        $apihandler = api_handler_instance();
        $response   = $apihandler->connect_to_wp_with_args($params["wp_url"], $requestdata);

        $status = 0;
        $msg    = $response["msg"];

        if (!$response["error"]) {
            $status = $response["data"]->status;
            $msg = $response["data"]->msg;
            if (!$status) {
                $msg = $response["data"]->msg . get_string('wp_test_connection_failed', 'local_edwiserbridge');
            }
        }

        return array("status" => $status, "msg" => $msg);
    }

    /**
     * Request to test connection parameter.
     */
    public static function eb_test_connection_parameters() {
        return new external_function_parameters(
            array(
                'wp_url'   => new external_value(PARAM_TEXT, get_string('web_service_wp_url', 'local_edwiserbridge')),
                'wp_token' => new external_value(PARAM_TEXT, get_string('web_service_wp_token', 'local_edwiserbridge'))
            )
        );
    }

    /**
     * paramters which will be returned from test connection function.
     */
    public static function eb_test_connection_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, get_string('web_service_test_conn_status', 'local_edwiserbridge')),
                'msg'    => new external_value(PARAM_RAW, get_string('web_service_test_conn_msg', 'local_edwiserbridge'))
            )
        );
    }


    /**
     * functionality to get all site related data.
     * @param  string $siteindex siteindex
     * @return array
     */
    public static function eb_get_site_data($siteindex) {
        $params = self::validate_parameters(
        self::eb_get_site_data_parameters(),
        array('site_index' => $siteindex)
        );
        return get_synch_settings($params['site_index']);
    }

    /**
     * paramters defined for get site data function.
     */
    public static function eb_get_site_data_parameters() {
        return new external_function_parameters(
            array(
                'site_index' => new external_value(PARAM_TEXT, get_string('web_service_site_index', 'local_edwiserbridge'))
            )
        );
    }

    /**
     * paramters which will be returned from get site data function.
     */
    public static function eb_get_site_data_returns() {
        return new external_single_structure(
            array(
                'course_enrollment'    => new external_value(
                    PARAM_INT,
                    get_string('web_service_course_enrollment', 'local_edwiserbridge')
                ),
                'course_un_enrollment' => new external_value(
                    PARAM_INT,
                    get_string('web_service_course_un_enrollment', 'local_edwiserbridge')
                ),
                'user_creation'        => new external_value(
                    PARAM_INT,
                    get_string('web_service_user_creation', 'local_edwiserbridge')
                ),
                'user_updation'        => new external_value(
                    PARAM_INT,
                    get_string('web_service_user_update', 'local_edwiserbridge')
                ),
                'user_deletion'        => new external_value(
                    PARAM_INT,
                    get_string('web_service_user_deletion', 'local_edwiserbridge')
                ),
                'course_creation'        => new external_value(
                    PARAM_INT,
                    get_string('web_service_course_creation', 'local_edwiserbridge')
                ),
                'course_deletion'        => new external_value(
                    PARAM_INT,
                    get_string('web_service_course_deletion', 'local_edwiserbridge')
                ),
            )
        );
    }

    /**
     * Functionality to get course progress.
     *
     * @param  string $userid the user id.
     * @return array of the course progress.
     */
    public static function eb_get_course_progress( $userid ) {
        global $DB, $CFG;

        $params = self::validate_parameters(
        self::eb_get_course_progress_parameters(),
        array( 'user_id' => $userid )
        );

        $result = $DB->get_records_sql( 'SELECT ctx.instanceid course, count(cmc.completionstate) as completed, count(cm.id)
            as  outoff FROM {user} u
			LEFT JOIN {role_assignments} ra ON u.id = ra.userid and u.id = ?
			JOIN {context} ctx ON ra.contextid = ctx.id
			JOIN {course_modules} cm ON ctx.instanceid = cm.course AND cm.completion > 0
			LEFT JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid AND u.id = cmc.userid AND cmc.completionstate > 0
			GROUP BY ctx.instanceid, u.id
			ORDER BY u.id',
        array( $params['user_id'] )
        );

        $enrolledcourses  = get_array_of_enrolled_courses( $params['user_id'], 1 );
        $processedcourses = $enrolledcourses;

        $response = array();

        if ( $result && ! empty( $result ) ) {
            foreach ($result as $key => $value) {
                $course     = get_course( $value->course );
                $cinfo      = new completion_info( $course );
                $iscomplete = $cinfo->is_course_complete( $params['user_id'] );
                $progress   = $iscomplete ? 100 : ( $value->completed / $value->outoff ) * 100;
                $response[] = array(
                 'course_id'  => $value->course,
                 'completion' => ceil( $progress ),
                );

                $processedcourses = remove_processed_coures( $value->course, $processedcourses );
            }
        }

        if ( ! empty( $processedcourses ) ) {
            foreach ($processedcourses as $value) {
                $course     = get_course( $value );
                $cinfo      = new completion_info( $course );
                $iscomplete = $cinfo->is_course_complete( $params['user_id'] );
                $progress   = $iscomplete ? 100 : 0;
                $response[] = array(
                'course_id'  => $value,
                'completion' => $progress,
                );

                $processedcourses = remove_processed_coures( $value, $processedcourses );
            }
        }
        return $response;
    }

    /**
     * paramters defined for course progress function.
     */
    public static function eb_get_course_progress_parameters() {
        return new external_function_parameters(
            array(
                'user_id' => new external_value(PARAM_TEXT, '')
            )
        );
    }

    /**
     * paramters which will be returned from course progress function.
     */
    public static function eb_get_course_progress_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'course_id'   => new external_value(PARAM_TEXT, ''),
                    'completion'  => new external_value(PARAM_INT, '')
                )
            )
        );
    }



    /**
     * functionality to get users in chunk.
     * @param  int $offset offset
     * @param  int $limit  limit
     * @param  string $searchstring searchstring
     * @param  int $totalusers totalusers
     * @return array array of users.
     */
    public static function eb_get_users($offset, $limit, $searchstring, $totalusers) {
        global $DB;

        $params = self::validate_parameters(
        self::eb_get_users_parameters(),
        array('offset' => $offset, "limit" => $limit, "search_string" => $searchstring, "total_users" => $totalusers)
        );

        $query = "SELECT id, username, firstname, lastname, email FROM {user} WHERE
        deleted = 0 AND confirmed = 1 AND username != 'guest' ";

        if (!empty($params['search_string'])) {
            $searchstring = "%" . $params['search_string'] . "%";
            $query .= " AND (firstname LIKE '$searchstring' OR lastname LIKE '$searchstring' OR username LIKE '$searchstring')";
        }

        $users = $DB->get_records_sql($query, null, $offset, $limit);
        $usercount = 0;
        if (!empty($params['total_users'])) {
            $usercount = $DB->get_record_sql("SELECT count(*) total_count FROM {user} WHERE
            deleted = 0 AND confirmed = 1 AND username != 'guest' ");
            $usercount = $usercount->total_count;
        }

        return array("total_users" => $usercount, "users" => $users);
    }

    /**
     * paramters defined for get users function.
     */
    public static function eb_get_users_parameters() {
        return new external_function_parameters(
            array(
                'offset'        => new external_value(
                    PARAM_INT,
                    get_string('web_service_offset', 'local_edwiserbridge')
                ),
                'limit'         => new external_value(
                    PARAM_INT,
                    get_string('web_service_limit', 'local_edwiserbridge')
                ),
                'search_string' => new external_value(
                    PARAM_TEXT,
                    get_string('web_service_search_string', 'local_edwiserbridge')
                ),
                'total_users'   => new external_value(
                    PARAM_INT,
                    get_string('web_service_total_users', 'local_edwiserbridge')
                ),
            )
        );
    }

    /**
     * paramters which will be returned from get users function.
     */
    public static function eb_get_users_returns() {
        return new external_function_parameters(
            array(
                'total_users' => new external_value(PARAM_INT, ''),
                'users' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id'        => new external_value(
                                PARAM_INT,
                                get_string('web_service_id', 'local_edwiserbridge')
                            ),
                            'username'  => new external_value(
                                PARAM_TEXT,
                                get_string('web_service_username', 'local_edwiserbridge')
                            ),
                            'firstname' => new external_value(
                                PARAM_TEXT,
                                get_string('web_service_firstname', 'local_edwiserbridge')
                            ),
                            'lastname'  => new external_value(
                                PARAM_TEXT,
                                get_string('web_service_lastname', 'local_edwiserbridge')
                            ),
                            'email'     => new external_value(
                                PARAM_TEXT,
                                get_string('web_service_email', 'local_edwiserbridge')
                            )
                        )
                    )
                )
            )
        );
    }



    /**
     * functionality to create new external service
     * @param  string $webservicename
     * @param  int $userid
     * @return boolean
     */
    public static function eb_create_service($webservicename, $userid) {
        $settingshandler = new eb_settings_handler();
        $response = $settingshandler->eb_create_externle_service($webservicename, $userid);
        return $response;
    }

    /**
     * Paramters defined for create service function.
     */
    public static function eb_create_service_parameters() {
        return new external_function_parameters(
            array(
                'web_service_name' => new external_value(PARAM_TEXT, get_string('web_service_name', 'local_edwiserbridge')),
                'user_id'          => new external_value(PARAM_TEXT, get_string('web_service_auth_user', 'local_edwiserbridge'))
            )
        );
    }

    /**
     * paramters which will be returned from create service function.
     */
    public static function eb_create_service_returns() {
        return new external_single_structure(
        array(
        'token'     => new external_value(PARAM_TEXT, get_string('web_service_token', 'local_edwiserbridge')),
        'site_url'  => new external_value(PARAM_TEXT, get_string('moodle_url', 'local_edwiserbridge')),
        'service_id'  => new external_value(PARAM_INT, get_string('web_service_id', 'local_edwiserbridge')),
        'status'  => new external_value(PARAM_INT, get_string('web_service_creation_status', 'local_edwiserbridge')),
        'msg'  => new external_value(PARAM_TEXT, get_string('web_service_creation_msg', 'local_edwiserbridge'))
        )
        );
    }



    /**
     * Functionality to link existing services.
     *
     * @param  string $serviceid
     * @param  int $token
     * @return array
     */
    public static function eb_link_service($serviceid, $token) {
        $response           = array();
        $response['status'] = 0;
        $response['msg']    = get_string('eb_link_err', 'local_edwiserbridge');

        $settingshandler = new eb_settings_handler();
        $result           = $settingshandler->eb_link_exitsing_service($serviceid, $token);
        if ($result) {
            $response['status'] = 1;
            $response['msg'] = get_string('eb_link_success', 'local_edwiserbridge');
            return $response;
        }
        return $response;
    }

    /**
     * paramters defined for link service function.
     */
    public static function eb_link_service_parameters() {
        return new external_function_parameters(
            array(
                'service_id' => new external_value(PARAM_TEXT, get_string('web_service_id', 'local_edwiserbridge')),
                'token'      => new external_value(PARAM_TEXT, get_string('web_service_token', 'local_edwiserbridge'))
            )
        );
    }

    /**
     * paramters which will be returned from link service function.
     */
    public static function eb_link_service_returns() {
        return new external_single_structure(
            array(
                'status'  => new external_value(PARAM_INT, get_string('web_service_creation_status', 'local_edwiserbridge')),
                'msg'  => new external_value(PARAM_TEXT, get_string('web_service_creation_msg', 'local_edwiserbridge'))
            )
        );
    }


    /**
     * functionality to link existing services.
     * @param  int $serviceid service id.
     * @return array
     */
    public static function eb_get_service_info($serviceid) {
        $response           = array();
        $response['status'] = 1;
        $response['msg']    = '';

        $count = eb_get_service_info($serviceid);
        if ($count) {
            $response['status'] = 0;
            $response['msg'] = $count . get_string('eb_service_info_error', 'local_edwiserbridge');
            return $response;
        }
        return $response;
    }

    /**
     * paramters defined for get service info function.
     */
    public static function eb_get_service_info_parameters() {
        return new external_function_parameters(
            array(
                'service_id' => new external_value(PARAM_TEXT, get_string('web_service_id', 'local_edwiserbridge')),
            )
        );
    }

    /**
     * paramters which will be returned from get service info function.
     */
    public static function eb_get_service_info_returns() {
        return new external_single_structure(
            array(
                'status'  => new external_value(PARAM_INT, get_string('web_service_creation_status', 'local_edwiserbridge')),
                'msg'  => new external_value(PARAM_TEXT, get_string('web_service_creation_msg', 'local_edwiserbridge'))
            )
        );
    }



    /**
     * functionality to link existing services.
     * @return array
     */
    public static function eb_get_edwiser_plugins_info() {
        $response                = array();
        $response['plugin_name'] = 'edwiser_bridge';
        $response['version']     = '';
        $response['plugin_name'] = 'edwiser_bridge';
        $response['version']     = '2.0.7';

        return $response;
    }

    /**
     * paramters defined for get plugin info function.
     */
    public static function eb_get_edwiser_plugins_info_parameters() {
        return new external_function_parameters(array());
    }

    /**
     * paramters which will be returned from get plugin info function.
     */
    public static function eb_get_edwiser_plugins_info_returns() {
        return new external_single_structure(
            array(
                'plugin_name'  => new external_value(PARAM_TEXT, get_string('eb_plugin_name', 'local_edwiserbridge')),
                'version'  => new external_value(PARAM_TEXT, get_string('eb_plugin_version', 'local_edwiserbridge'))
            )
        );
    }
}
