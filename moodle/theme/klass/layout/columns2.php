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
 * columns2.php
 *
 * @package   theme_klass
 * @copyright 2015 Lmsace Dev Team,lmsace.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

user_preference_allow_ajax_update('drawer-open-nav', PARAM_ALPHA);
require_once($CFG->libdir . '/behat/lib.php');

/*
if ($_GET['id'] == 13 && !isset($_GET['section']) || empty($_GET['section'])) {
    header("Location: https://reisen.ontour.org/course/view.php?id=13&section=7");
    exit(); // Make sure no further code is executed after the redirect
}

if ($_GET['id'] == 14 && !isset($_GET['section']) || empty($_GET['section'])) {
    header("Location: https://reisen.ontour.org/course/view.php?id=14&section=7");
    exit(); // Make sure no further code is executed after the redirect
}
*/
if (isloggedin()) {
    #$navdraweropen = (get_user_preferences('drawer-open-nav', 'true') == 'true');
    $navdraweropen = false;
    $logged_in = true;
} else {
    $navdraweropen = false;
    $logged_in = false;
}
$extraclasses = [];
if ($navdraweropen) {
    $extraclasses[] = 'drawer-open-left';
}
$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = strpos($blockshtml, 'data-block=') !== false;
$regionmainsettingsmenu = $OUTPUT->region_main_settings_menu();
// Header content.
$logourl = get_logo_url();
$surl = new moodle_url('/course/search.php');
if (!$PAGE->url->compare($surl, URL_MATCH_BASE)) {
    $compare = 1;
} else {
    $compare = 0;
}
$surl = new moodle_url('/course/search.php');
$ssearchcourses = get_string('searchcourses');
$shome = get_string('home', 'theme_klass');

$custom = $OUTPUT->custom_menu();

if ($custom == '') {
    $class = "navbar-toggler hidden-lg-up nocontent-navbar";
} else {
    $class = "navbar-toggler hidden-lg-up";
}

$logout_url =  $CFG->wwwroot . '/login/logout.php?sesskey=' . sesskey();

// Footer Content.
$logourlfooter = get_logo_url('footer');
$footlogo = theme_klass_get_setting('footerlogo');
$footnote = theme_klass_get_setting('footnote', 'format_html');
$fburl    = theme_klass_get_setting('fburl');
$pinurl   = theme_klass_get_setting('pinurl');
$twurl    = theme_klass_get_setting('twurl');
$gpurl    = theme_klass_get_setting('gpurl');
$address  = theme_klass_get_setting('address');
$emailid  = theme_klass_get_setting('emailid');
$phoneno  = theme_klass_get_setting('phoneno');
$copyrightfooter = theme_klass_get_setting('copyright_footer');
$infolink = theme_klass_get_setting('infolink');
$infolink = theme_klass_infolink();

$sinfo = get_string('info', 'theme_klass');
$scontactus = get_string('contact_us', 'theme_klass');
$sphone = get_string('phone', 'theme_klass');
$semail = get_string('email', 'theme_klass');
$sgetsocial = get_string('get_social', 'theme_klass');

$url = ($fburl != '' || $pinurl != '' || $twurl != '' || $gpurl != '') ? 1 : 0;
$contact = ($emailid != '' || $address != '' || $phoneno != '') ? 1 : 0;

if ($footlogo != '' || $footnote != '' || $infolink != '' || $url != 0 || $contact != 0 || $copyrightfooter != '') {
    $footerall = 1;
} else {
    $footerall = 0;
}

$block1 = ($footlogo != '' || $footnote != '') ? 1 : 0;
$infoslink = ($infolink != '') ? 1 : 0;
$blockarrange = $block1 + $infoslink + $contact + $url;

switch ($blockarrange) {
    case 4:
        $colclass = 'col-md-3';
        break;
    case 3:
        $colclass = 'col-md-4';
        break;
    case 2:
        $colclass = 'col-md-6';
        break;
    case 1:
        $colclass = 'col-md-12';
        break;
    case 0:
        $colclass = '';
        break;
    default:
        $colclass = 'col-md-3';
        break;
}

$username = $USER->firstname." ".$USER->lastname;

$admin = false;

if (is_siteadmin() || $username == "Julia Bauer") {
    $admin = true;
}


global $USER;
global $DB;

/*
$sql = "SELECT * FROM mdl_booking_history3 WHERE type = 'mail_co' && state = 1";
$mail_mo = $DB->get_records_sql($sql);

foreach ($mail_mo as $item){

    $data = new stdClass();
    $data->id = $item->id;
    $data->state = 2;
    $DB->update_record('booking_history3', $data);

}

*/

if(isset($_POST['className'])){

    global $DB;
    global $USER;

    $user = new stdClass();
    $user->id = $USER->id;
    $user->policyagreed = 1;
    $user->firstname = $_POST['firstname'];
    $user->lastname = $_POST['lastname'];
    $user->city = $_POST['school'];


    $USER->policyagreed = 1;
    $USER->firstname = $_POST['firstname'];
    $USER->lastname = $_POST['lastname'];

    if(isset($_POST['email']) && $_POST['email'] != ""){
        $user->email = $_POST['email'];
        $USER->email = $_POST['email'];
    }

    $DB->update_record('user', $user);
    $_SESSION['USER']=$USER;

    $school = $_POST['school'];
    $classname = $_POST['className'];
    $age = $_POST['age'];

    $servername = "localhost";
    $username = "skuehn22";
    $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
    $dbname = "ontour_moodle_new";

    $sql = "SELECT * FROM mdl_booking_data3 WHERE user_id = ". $USER->id;
    $booking = $DB->get_record_sql($sql);

    if(empty($booking)){
        $conn = new mysqli($servername, $username, $password, $dbname);
        $sql = "INSERT INTO mdl_booking_data3 (order_id, operators_id, user_id, mailing, classname_teacher, school, class_note)
                    VALUES ('$USER->phone1', '1', '$USER->id', '1', '$classname', '$school', '$age')";
        $conn->query($sql);
    }

    $conn = new mysqli($servername, $username, $password, $dbname);
    $sql = "UPDATE mdl_booking_data3 SET class_note='$age' WHERE user_id=$USER->id";
    $conn->query($sql);

    $conn = new mysqli($servername, $username, $password, $dbname);
    $sql = "UPDATE mdl_booking_data3 SET school='$school' WHERE user_id=$USER->id";
    $conn->query($sql);

    $conn = new mysqli($servername, $username, $password, $dbname);
    $sql = "UPDATE mdl_booking_data3 SET reg_date='".time()."' WHERE user_id=$USER->id";
    $conn->query($sql);

    $conn = new mysqli($servername, $username, $password, $dbname);
    $sql = "UPDATE mdl_booking_data3 SET classname_teacher='$classname' WHERE user_id=$USER->id";
    $conn->query($sql);
    $conn->close();


    $sql = "SELECT * FROM {booking_history3} WHERE type = 'mail_re1' && state = 2 && b_id = '$booking->order_id'";
    $checker = $DB->get_record_sql($sql);

    if($checker){

        $user_update = new stdClass();
        $user_update->id = $checker->id;
        $user_update->state = "1";
        $DB->update_record('booking_history3', $user_update);

    }

    $sql = "SELECT * FROM {booking_history3} WHERE type = 'mail_re2' && state = 2 && b_id = '$booking->order_id'";
    $checker = $DB->get_record_sql($sql);

    if($checker){

        $user_update = new stdClass();
        $user_update->id = $checker->id;
        $user_update->state = "1";
        $DB->update_record('booking_history3', $user_update);

    }

    $sql = "SELECT * FROM {booking_history3} WHERE type = 'mail_re3' && state = 2 && b_id = '$booking->order_id'";
    $checker = $DB->get_record_sql($sql);

    if($checker){

        $user_update = new stdClass();
        $user_update->id = $checker->id;
        $user_update->state = "1";
        $DB->update_record('booking_history3', $user_update);

    }

    /*
    if($_SERVER['HTTP_HOST'] == "www.projektreise.sk"){
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "ontour_wordpress";
    }else{
        $servername = "localhost";
        $username = "skuehn22";
        $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
        $dbname = "projektreisenWordpress_1637922561";
    }

    $conn = new mysqli($servername, $username, $password, $dbname);

    $sql = "SELECT * FROM wp_woocommerce_order_items WHERE order_id = '$USER->phone1' && order_item_name = 'Videoprojekt'";
    $result = $conn->query($sql);
    $order_item = $result->fetch_assoc();
    $order_item_id = $order_item['order_item_id'];

    $sql = "UPDATE wp_woocommerce_order_itemmeta SET meta_value='$classname' WHERE meta_key='gruppenname' && order_item_id = '$order_item_id'";
    $conn->query($sql);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    */

    /* Finishing Mail */
    $obj = [];
    $obj['b_id'] = $booking->order_id;
    $obj['user_id'] = $user->id;
    $obj['receiver'] = "";
    $obj['sender'] = "";
    $obj['type'] = "register";
    $obj['subject'] = "Registrierung abgeschlossen";
    $obj['state'] = 1;
    $DB->insert_record('booking_history3', (array)$obj);


}


$show_modal = false;

if($USER->policyagreed == 0 && $USER->idnumber == "new"){
//if($USER->username == "neu"){
    $show_modal = true;
}

global $DB;

/* get all data about the operator like wbt */
$sql = "SELECT * FROM mdl_booking_data3 WHERE user_id = ". $USER->id;
$booking = $DB->get_record_sql($sql);
$greetings =$booking->greetings;

$sql = "SELECT * FROM mdl_booking_history3 WHERE type = 'film_production' && user_id = ". $USER->id;
$film = $DB->get_record_sql($sql);

$production = false;
if($film){
    $production = true;
}

//mailing for direct bookings are always mandatory
$mailing_direct = false;
if($booking->mailing == 1){
    $mailing = true;
}

//mailing allowed but not mandatory
$mailing_operator = false;
if($booking->mailing == 2 || $booking->mailing == 3){
    $mailing_operator = true;
}

//mailing not allowed
$mailing_operator_no = false;
if($booking->mailing == 0){
    $mailing_operator_no = true;
}


//mailing not allowed
$newsletter = false;
if($booking->newsletter == 1){
    $newsletter = true;
}

//get the amount of students which took part in the course
$sql = "SELECT id, name FROM {finishing_students}  WHERE fk_user = $USER->id && fk_course = 8 && status = 0";
$students = $DB->get_records_sql($sql);
$students_count = count($students);

if($students_count > 0){
    $saved_students =  true;
}else{
    $saved_students =  false;
}

$names = [];
$y = 0;


foreach ($students as $student){
    $names[$y]['name'] = $student->name;
    $names[$y]['id'] = $student->id;
    $y++;
}

$students_count = (int)$students_count;


//get the amount of students which took part in the course
$sql = "SELECT id, name FROM {finishing_lehrer}  WHERE fk_user = $USER->id && fk_course = 8 && status = 0";
$lehrer= $DB->get_records_sql($sql);
$lehrer_count = count($lehrer);

if($lehrer_count > 0){
    $saved_lehrer =  true;
}else{
    $saved_lehrer =  false;
}

$names_lehrer = [];

$z = 0;

foreach ($lehrer as $l){
    $names_lehrer[$z]['name'] = $l->name;
    $names_lehrer[$z]['id'] = $l->id;
    $z++;
}

$lehrer_count = (int)$lehrer_count;

$data_modal = false;

if($USER->policyagreed == 0)
{
    $data_modal = true;
}

if($_GET['id'] == 8){
    $modal_link = "https://reisen.ontour.org/course/view.php?id=8&section=6";
    $img_modal = "https://ontour.org/wp-content/uploads/2022/03/In-Berlin-Videoprojekt-Startseite-onTour.webp";
}else{
    $img_modal = "https://reisen.ontour.org/files/MicrosoftTeams-image.png";
}

if($_GET['id'] == 13){
    $modal_link = "https://reisen.ontour.org/course/view.php?id=13&section=7";

}

if($_GET['id'] == 14){
    $modal_link = "https://reisen.ontour.org/course/view.php?id=14&section=7";
}




$company_modal = false;
if( ($_GET['id'] == 10 && $_GET['section'] == 0)){
    $company_modal = true;
}


$company = false;
if( ($_GET['id'] == 10)){
    $company= true;
}

switch ($_GET['id']) {
    case 8:
        $city = "Berlin";
        break;
    case 10:
        $city = "Berlin";
        break;
    case 11:
        $city = "Hamburg";
        break;
    case 13:
        $city = "Hamburg";
        break;
    case 12:
        $city = "München";
        break;
    case 14:
        $city = "München";
        break;
    default:
        $city = "";
}


switch ($_GET['id']) {
    case 8:
        $typ = "Videoprojekt";
        break;
    case 10:
        $typ = "Videoevent";
        break;
    case 11:
        $typ = "Videoevent";
        break;
    case 13:
        $typ = "Videoprojekt";
        break;
    case 12:
        $typ = "Videoevent";
        break;
    case 14:
        $typ = "Videoprojekt";
        break;
    default:
        $typ = "";
}



$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'navdraweropen' => $navdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    "surl" => $surl,
    "s_searchcourses" => $ssearchcourses,
    "s_home" => $shome,
    "logourl" => $logourl,
    "compare" => $compare,
    "logourl_footer" => $logourlfooter,
    "footnote" => $footnote,
    "fburl" => $fburl,
    "pinurl" => $pinurl,
    "twurl" => $twurl,
    "gpurl" => $gpurl,
    "address" => $address,
    "emailid" => $emailid,
    "phoneno" => $phoneno,
    "copyright_footer" => $copyrightfooter,
    "infolink" => $infolink,
    "s_info" => $sinfo,
    "s_contact_us" => $scontactus,
    "s_phone" => $sphone,
    "s_email" => $semail,
    "s_get_social" => $sgetsocial,
    "url" => $url,
    "contact" => $contact,
    "footerall" => $footerall,
    "customclass" => $class,
    "block1" => $block1,
    "colclass" => $colclass,
    "logged_in" => $logged_in,
    "username" => $username,
    "logout_url" => $logout_url,
    "admin" => $admin,
    "show_modal" => $show_modal,
    "mailing" => $mailing,
    "mailing_operator_no" => $mailing_operator_no,
    "mailing_operator" => $mailing_operator,
    "saved_students" => $saved_students,
    "students_count" => $students_count,
    "students" => $students,
    "names" => $names,
    "saved_lehrer" => $saved_lehrer,
    "lehrer_count" => $lehrer_count,
    "lehrer" => $lehrer,
    "names_lehrer" => $names_lehrer,
    "greetings" => $greetings,
    "production" => $production,
    "newsletter" => $newsletter,
    "data_modal" => $data_modal,
    "max_section" => $max_section,
    "company_modal" => $company_modal,
    "test" => "test",
    "company" => $company,
    "typ" => $typ,
    "city" => $city,
    "modal_link" => $modal_link,
    "img_modal" => $img_modal
];

$templatecontext['flatnavigation'] = $PAGE->flatnav;
echo $OUTPUT->render_from_template('theme_klass/columns2', $templatecontext);