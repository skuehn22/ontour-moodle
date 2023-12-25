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
 * This file contains a library of functions and constants for the helixmedia module
 *
 * @package    mod
 * @subpackage helixmedia
 * @author     Tim Williams (For Streaming LTD)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/mod/lti/lib.php');
require_once($CFG->dirroot.'/mod/lti/locallib.php');

// Activity types.
define('HML_LAUNCH_NORMAL', 1);
define('HML_LAUNCH_THUMBNAILS', 2);
define('HML_LAUNCH_EDIT', 3);

// Special type for migration from the repository module. Now Redundant so disabled.
//define('HML_LAUNCH_RELINK', 4);

// Assignment submission types.
define('HML_LAUNCH_STUDENT_SUBMIT', 5);
define('HML_LAUNCH_STUDENT_SUBMIT_PREVIEW', 17);
define('HML_LAUNCH_STUDENT_SUBMIT_THUMBNAILS', 6);
define('HML_LAUNCH_VIEW_SUBMISSIONS', 7);
define('HML_LAUNCH_VIEW_SUBMISSIONS_THUMBNAILS', 8);

// TinyMCE types. Do not change these values, they are embedded in the TinyMCE plugin html code.
define('HML_LAUNCH_TINYMCE_EDIT', 9);
define('HML_LAUNCH_TINYMCE_VIEW', 10);

// Submission Feedback types.
define('HML_LAUNCH_FEEDBACK', 11);
define('HML_LAUNCH_FEEDBACK_THUMBNAILS', 12);

// Submission Feedback types.
define('HML_LAUNCH_VIEW_FEEDBACK', 13);
define('HML_LAUNCH_VIEW_FEEDBACK_THUMBNAILS', 14);

// ATTO Types. Do not change these values, they are embedded in the ATTO plugin html code.
define('HML_LAUNCH_ATTO_EDIT', 15);
define('HML_LAUNCH_ATTO_VIEW', 16);

// Note next ID should be 18.

// For version check.
define('MEDIAL_MIN_VERSION', '6.0.054');


function helixmedia_is_group_assign($cmid) {
    global $DB;
    $cm = $DB->get_record('course_modules', array('id' => $cmid));
    $assign = $DB->get_record('assign', array('id' => $cm->instance));

    if ($assign->teamsubmission) {
         return "Y";
    } else {
         return "N";
    }
}

function helixmedia_get_assign_into_refs($assignid) {
    global $DB;
    $refs = "";

    $module = $DB->get_record("course_modules", array("id" => $assignid));

    if (!$module) {
        return "";
    }

    $assignment = $DB->get_record("assign", array("id" => $module->instance));

    if (!$assignment) {
        return "";
    }

    $first = true;
    $pos = strpos($assignment->intro, "/mod/helixmedia/launch.php");

    while ($pos != false) {

        $l = strpos($assignment->intro, "l=", $pos);

        if ($l != false) {
            $l = $l + 2;
            $e = strpos($assignment->intro, "\"", $l);
            if ($e != false) {
                if (!$first) {
                    $refs .= ",";
                } else {
                    $first = false;
                }
                $refs .= substr($assignment->intro, $l, $e - $l);
            }
        }
        $pos = strpos($assignment->intro, "/mod/helixmedia/launch.php", $pos + 1);
    }
    return $refs;
}

function helixmedia_curl_post_launch_html($params, $endpoint) {
    global $CFG;
    $modconfig = get_config("helixmedia");
    $params['oauth_consumer_key'] = $modconfig->consumer_key;

    set_time_limit(0);
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_TIMEOUT, 50);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; curl; like Firefox)");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $cookiesfile = $CFG->dataroot.DIRECTORY_SEPARATOR."temp".DIRECTORY_SEPARATOR."helixmedia-curl-cookies-".microtime(true).".tmp";
    while (file_exists($cookiesfile)) {
        $cookiesfile = $CFG->dataroot.DIRECTORY_SEPARATOR."temp".DIRECTORY_SEPARATOR.
            "helixmedia-curl-cookies-".microtime(true).".tmp";
    }
    curl_setopt($ch, CURLOPT_COOKIESESSION, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiesfile);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiesfile);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

    // Uncomment this for verbose debugging
    // curl_setopt($ch, CURLOPT_VERBOSE, true);
    // $verbose = fopen('php://temp', 'rw+');
    // curl_setopt($ch, CURLOPT_STDERR, $verbose);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        notice("CURL Error connecting to HML LTI: ".curl_error($ch));
    }
    curl_close($ch);

    // Uncomment this for verbose debugging
    // if ($result === FALSE) {
    // printf("cUrl error (#%d): %s<br>\n", curl_errno($curlHandle),
    // htmlspecialchars(curl_error($curlHandle)));
    // }
    // rewind($verbose);
    // $verboseLog = stream_get_contents($verbose);
    // echo "Verbose information:\n<pre>", htmlspecialchars($verboseLog), "</pre>\n";

    if (file_exists($cookiesfile)) {
        unlink($cookiesfile);
    }

    return $result;
}

/**
 * This function builds the request that must be sent to the tool producer
 *
 * @param object    $instance       HML instance object
 * @param object    $typeconfig     HML tool configuration
 * @param object    $course         Course object
 * @param int       $type           The launch type
 * @param object    $user           User object if the launch isn't for the current user
 * @param boolean   $modtype          Set to true if we are in a modtype
 *
 * @return array    $request        Request details
 */
function helixmedia_build_request($instance, $typeconfig, $course, $type, $user = null, $modtype = "") {
    global $USER, $CFG;

    if ($user == null) {
        $user = $USER;
    }

    if (empty($instance->cmid)) {
        $instance->cmid = 0;
    }

    // We need to always use CRLF line endings for LTI, otherwise the signature validation will fail.
    // Moodle backup/restore sometimes converts the line endings to LF only.
    // The Moodle core code uses a straight str_replace of \n with \r\n
    // which won't cope properly with text where the line endings have been mixed up and \r only from the mac.
    $intro = html_to_text($instance->intro);
    $intro = preg_replace('/\r\n|\r|\n/', "\r\n", $intro);

    $role = helixmedia_get_ims_role($user, $instance->cmid, $course->id, $type, $modtype);

    $requestparams = array(
        'resource_link_id' => $instance->preid,
        'resource_link_title' => $instance->name,
        'resource_link_description' => substr($intro, 0, 1000),
        'user_id' => $user->id,
        'roles' => $role,
        'context_id' => $course->id,
        'context_label' => $course->shortname,
        'context_title' => $course->fullname,
        'launch_presentation_locale' => current_language()
    );

    $placementsecret = $instance->servicesalt;

    if (isset($placementsecret)) {
        $sourcedid = json_encode(lti_build_sourcedid($instance->id, $user->id, null, $placementsecret));
    }

    if (isset($placementsecret) &&
         ($typeconfig['acceptgrades'] == LTI_SETTING_ALWAYS ||
         ($typeconfig['acceptgrades'] == LTI_SETTING_DELEGATE && $instance->instructorchoiceacceptgrades == LTI_SETTING_ALWAYS ))) {
        $requestparams['lis_result_sourcedid'] = $sourcedid;

        if ($typeconfig['forcessl'] == '1') {
            $serviceurl = lti_ensure_url_is_https($serviceurl);
        }

        $requestparams['lis_outcome_service_url'] = $serviceurl;
    }

    // Send user's name and email data if appropriate.
    if ( $typeconfig['sendname'] == LTI_SETTING_ALWAYS ||
         ( $typeconfig['sendname'] == LTI_SETTING_DELEGATE && $instance->instructorchoicesendname == LTI_SETTING_ALWAYS ) ) {
        $requestparams['lis_person_name_given'] = $user->firstname;
        $requestparams['lis_person_name_family'] = $user->lastname;
        $requestparams['lis_person_name_full'] = $user->firstname." ".$user->lastname;
    }

    if ( $typeconfig['sendemailaddr'] == LTI_SETTING_ALWAYS ||
        ($typeconfig['sendemailaddr'] == LTI_SETTING_DELEGATE &&
        $instance->instructorchoicesendemailaddr == LTI_SETTING_ALWAYS ) ) {
        $requestparams['lis_person_contact_email_primary'] = $user->email;
    }

    // Concatenate the custom parameters from the administrator and the instructor
    // Instructor parameters are only taken into consideration if the administrator
    // has given permission.
    $customstr = $typeconfig['customparameters'];

    $instructorcustomstr = "";
    $custom = array();
    $instructorcustom = array();
    if ($customstr) {
        $custom = helix_split_custom_parameters($customstr);
    }

    if (isset($typeconfig['allowinstructorcustom']) && $typeconfig['allowinstructorcustom'] == LTI_SETTING_NEVER) {
        $requestparams = array_merge($custom, $requestparams);
    } else {
        if ($instructorcustomstr) {
            $instructorcustom = helix_split_custom_parameters($instructorcustomstr);
        }
        foreach ($instructorcustom as $key => $val) {
            // Ignore the instructor's parameter.
            if (!array_key_exists($key, $custom)) {
                $custom[$key] = $val;
            }
        }
        $requestparams = array_merge($custom, $requestparams);
    }

    // Make sure we let the tool know what LMS they are being called from.
    $requestparams["ext_lms"] = "moodle-2";
    $requestparams['tool_consumer_info_product_family_code'] = 'moodle';
    $requestparams['tool_consumer_info_version'] = strval($CFG->version);

    // Add oauth_callback to be compliant with the 1.0A spec.
    $requestparams['oauth_callback'] = 'about:blank';

    $requestparams['lti_version'] = 'LTI-1p0';
    $requestparams['lti_message_type'] = 'hml-launch-request';

    return $requestparams;
}

/**
 * Splits the custom parameters field to the various parameters
 *
 * @param string $customstr     String containing the parameters
 *
 * @return Array of custom parameters
 */
function helix_split_custom_parameters($customstr) {
    $lines = preg_split("/[\n;]/", $customstr);
    $retval = array();
    foreach ($lines as $line) {
        $pos = strpos($line, "=");
        if ( $pos === false || $pos < 1 ) {
            continue;
        }
        $key = trim(core_text::substr($line, 0, $pos));
        $val = trim(core_text::substr($line, $pos + 1, strlen($line)));
        $key = lti_map_keyname($key);
        $retval['custom_'.$key] = $val;
    }
    return $retval;
}

/**
 * Gets the IMS role string for the specified user and Helixmedia course module.
 *
 * @param mixed $user User object or user id
 * @param int $cmid The course module id of the LTI activity
 * @param int $courseid The course id
 * @param int $type The launch type
 * @param boolean $modtype Set to true if we are in a modtype
 *
 * @return string A role string suitable for passing with an LTI launch
 */
function helixmedia_get_ims_role($user, $cmid, $courseid, $type, $modtype) {
    $roles = array();

    // Always use the Learner role if this is a student submission or a view operation, MEDIAL expects this.
    if ($type == HML_LAUNCH_NORMAL ||
        $type == HML_LAUNCH_TINYMCE_VIEW ||
        $type == HML_LAUNCH_ATTO_VIEW ||
        $type == HML_LAUNCH_STUDENT_SUBMIT ||
        $type == HML_LAUNCH_STUDENT_SUBMIT_PREVIEW ||
        $type == HML_LAUNCH_STUDENT_SUBMIT_THUMBNAILS) {
        return "Learner";
    }

    $coursecontext = context_course::instance($courseid);
    if (empty($cmid) || $cmid == -1) {
        // If no cmid is passed, check if the user is a teacher in the course
        // This allows other modules to programmatically "fake" a launch without
        // a real Helixmedia instance.

        if (has_capability('moodle/course:manageactivities', $coursecontext)) {
            array_push($roles, 'Instructor');
        } else {
            array_push($roles, 'Learner');
        }
    } else {
        if (has_capability('mod/helixmedia:manage', $coursecontext)) {
            array_push($roles, 'Instructor');
        } else {
            array_push($roles, 'Learner');
        }
    }

    if (is_siteadmin($user)) {
        array_push($roles, 'urn:lti:sysrole:ims/lis/Administrator');
    }

    return join(',', $roles);
}

/**
 * Checks the moduletype we are viewing here to see if we can use the more permissive modtype permission
 * @param $modtype The module type
 * @return The permission to use
 **/
function helixmedia_get_visiblecap($modtype = false) {
    if (!$modtype) {
        return 'atto/helixatto:visible';
    }

    global $DB;
    $config = get_config('atto_helixatto', 'modtypeperm');
    $types = explode("\n", $config);

    for ($i = 0; $i < count($types); $i++) {
        $types[$i] = trim($types[$i]);
        if (strlen($types[$i]) > 0 && $types[$i] == $modtype && $DB->get_record('modules', array('name' => $types[$i]))) {
            return 'atto/helixatto:visiblemodtype';
        }
    }

    return 'atto/helixatto:visible';
}

function curpageurl() {
    $pageurl = 'http';
    if (array_key_exists("HTTPS", $_SERVER) && $_SERVER["HTTPS"] == "on") {
        $pageurl .= "s";
    }

    $pageurl .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") {
        $pageurl .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    } else {
        $pageurl .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }
    return $pageurl;
}

function helixmedia_get_instance_size($preid, $course) {
    global $CFG;
    $url = helixmedia_get_playerwidthurl();
    $retdata = helixmedia_curl_post_launch_html(array("context_id" => $course, "resource_link_id" => $preid,
        "include_height" => "Y"), $url);

    $parts = explode(":", $retdata);
    // If there is more than one part, then MEDIAL understands the include_height param.
    if (count($parts) > 1) {
        $vals = new stdclass();
        $vals->width = intval($parts[0]);
        $vals->height = intval($parts[1]);
        if (count($parts) > 1 && $parts[2] == 'Y') {
            $vals->audioonly = true;
        } else {
            $vals->audioonly = false;
        }
        return $vals;
    }

    // Old version of MEDIAL, return standard data.
    $vals = new stdclass();
    $vals->width = intval($retdata);
    $vals->height = -1;
    $vals->audioonly = false;
    return $vals;
}

function helixmedia_get_playerwidthurl() {
    return helixmedia_get_alturl("PlayerWidth");
}

function helixmedia_get_status_url() {
    return helixmedia_get_alturl("SessionStatus");
}

function helixmedia_get_upload_url() {
    return helixmedia_get_alturl("UploadStatus");
}

function helixmedia_get_alturl($alt) {
    $statusurl = trim(get_config("helixmedia", "launchurl"));
    $pos = helixmedia_str_contains(strtolower($statusurl), "/launch", true);
    return substr($statusurl, 0, $pos).$alt;
}

function helixmedia_is_preid_empty($preid, $as, $userid) {
    global $CFG;

    $retdata = helixmedia_curl_post_launch_html(array("resource_link_id" => $preid, "user_id" => $userid),
        helixmedia_get_upload_url());

    // We got a 404, the MEDIAL server doesn't support this call, so return false.
    // The old method was to check for the presence of a resource link ID so this is consistent.
    if (strpos($retdata, "HTTP 404") > 0) {
        return false;
    }

    if ($retdata == "Y") {
        return false;
    }

    return true;
}


function helixmedia_str_contains($haystack, $needle, $ignorecase = false) {
    if ($ignorecase) {
        $haystack = strtolower($haystack);
        $needle = strtolower($needle);
    }
    $needlepos = strpos($haystack, $needle);
    return ($needlepos === false ? false : ($needlepos + 1));
}


function helixmedia_version_check() {
    $statusurl = trim(get_config("helixmedia", "launchurl"));
    if (strlen($statusurl) == 0) {
        return "<p>".get_string("version_check_not_done", "helixmedia")."</p>";
    }
    $pos = helixmedia_str_contains(strtolower($statusurl), "/lti/launch", true);
    $endpoint = substr($statusurl, 0, $pos)."/version.txt";

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_TIMEOUT, 50);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; curl; like Firefox)");
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = trim(curl_exec($ch));
    if (curl_errno($ch)) {
        return "<p>CURL Error connecting to MEDIAL: ".curl_error($ch)."</p>".
              "<p>".get_string("version_check_fail", "helixmedia")."</p>";
    }
    curl_close($ch);

    $v = new stdclass();
    $v->min = MEDIAL_MIN_VERSION;
    $v->actual = $result;
    $message = "<p>".get_string('version_check_message', 'helixmedia', $v)."</p>";

    $reqver = parse_medial_version(MEDIAL_MIN_VERSION);
    $actualver = parse_medial_version($result);

    if ($actualver < $reqver) {
        $message .= "<p class='warning'>".get_string('version_check_upgrade', 'helixmedia')."</p>";
    }

    return $message."<br />";
}

function parse_medial_version($str) {
    $parts = explode('.', $str);
    $concat = '';
    for ($loop = 0; $loop < count($parts); $loop++) {
        $concat .= $parts[$loop];
    }
    return intval($concat);
}

function helixmedia_legacy_dynamic_size($hmli, $c) {
    // This handles dynamic sizing of the launch frame.
    $size = helixmedia_get_instance_size($hmli->preid, $c);

    if ($size->width == 0) {
        $ratio = 0.605;
        // If height is -1, use old size rules.
        if ($size->height == -1) {
            $ratio = 0.85;
        }
        echo "<script type=\"text/javascript\">\n".
             "var vid=parent.document.getElementById('hmlvid-".$hmli->preid."');\n".
             "var h=parseInt(vid.parentElement.offsetWidth*".$ratio.");\n".
             "vid.style.width='100%';\n".
             "if (h>0) {vid.style.height=h+'px';}\n".
             "</script>\n";
    } else {
        // If height is -1, use old size rules.
        if ($size->height == -1) {
            $w = "530px";
            $h = "420px";
            if ($size->width == 640) {
                $w = "680px";
                $h = "570px";
            } else {
                if ($size->width == 835) {
                    $w = "880px";
                    $h = "694px";
                }
            }
        } else {
            if ($size->audioonly) {
                $w = $size->width."px";
                $h = $size->height."px";
            } else {
                $w = "380px";
                $h = "340px";
                if ($size->width == 640) {
                    $w = "680px";
                    $h = "455px";
                } else {
                    if ($size->width == 835) {
                        $w = "875px";
                        $h = "575px";
                    }
                }
            }
        }

        echo "<script type=\"text/javascript\">\n".
             "var vid=parent.document.getElementById('hmlvid-".$hmli->preid."');".
             "vid.style.width='".$w."';\n".
             "vid.style.height='".$h."';\n".
             "</script>\n";
    }
}