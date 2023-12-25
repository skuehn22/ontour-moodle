<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Prints an instance of mod_bookings.
 *
 * @package     mod_bookings
 * @copyright   2022 onTour <info@ontour.org>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$b = optional_param('b', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('bookings', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('bookings', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('bookings', array('id' => $b), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('bookings', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);


global $PAGE;
global $CFG;
$PAGE->requires->js(new moodle_url(($CFG->wwwroot . '/theme/klass/js_custom/jquery-3.5.1.js')), true);
$PAGE->requires->js(new moodle_url(($CFG->wwwroot . '/theme/klass/js_custom/dataTables2.js')), true);
$PAGE->requires->js(new moodle_url(($CFG->wwwroot . '/theme/klass/js_custom/fixedHeader.js')), true);

$PAGE->requires->js(new moodle_url(($CFG->wwwroot . '/theme/klass/js_custom/jquery-3.5.1.js')), true);
$PAGE->requires->js(new moodle_url(($CFG->wwwroot . '/theme/klass/style/jquery-ui/jquery-ui.js?tmp='.time())), true);
$PAGE->requires->js(new moodle_url(($CFG->wwwroot . '/theme/klass/js_custom/moment.js')), true);
$PAGE->requires->js(new moodle_url(($CFG->wwwroot . '/theme/klass/js_custom/dateTime.js')), true);
$PAGE->requires->js(new moodle_url(($CFG->wwwroot . '/mod/bookings/assets/index9.js?tmp='.time())), true);
$PAGE->requires->js(new moodle_url(($CFG->wwwroot . '/theme/klass/js_custom/functions.js?tmp='.time())), true);
$PAGE->requires->css(new moodle_url(($CFG->wwwroot . '/theme/klass/style/datatables.css?tmp='.time())));
$PAGE->requires->css(new moodle_url(($CFG->wwwroot . '/theme/klass/style/fixedHeader.css?tmp='.time())));
$PAGE->requires->css(new moodle_url(($CFG->wwwroot . '/theme/klass/style/jquery-ui/jquery-ui.css?tmp='.time())));
$PAGE->set_url('/mod/bookings/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();


if(isset($_GET['status']) && $_GET['status'] == "success"){
    $b_id = $_GET['b_id'];
    $v_codes_display = "";

    //$v_codes = $DB->get_records('user', array('phone1' =>  $_GET['b_id']), 'username');

    $sql = "SELECT * FROM mdl_user WHERE phone1 = '$b_id'";
    $v_codes = $DB->get_records_sql($sql);


    foreach ($v_codes as $v_code){
        $v_codes_display .= $v_code->username."<br>";
    }

}else{
    $msg = false;
}

if(isset($_POST['search_id'])){

    $b_id = $_POST['search_id'];
    $booking = getBooking($_POST['search_id']);
    $sql = "SELECT * FROM mdl_user WHERE phone1 = $b_id";
    $users = $DB->get_records_sql($sql);

}

$sql = "SELECT * FROM mdl_ext_operators1 WHERE active = 1 ORDER BY sort ASC";
$operators = $DB->get_records_sql($sql);
$o_select = "";
$mailing = "";

$op_array = [];

foreach ($operators as $operator){

    if($booking->operators_id == $operator->id){
        $o_select .="<option value='$operator->id' selected>$operator->name</option>";
    }else{
        $o_select .="<option value='$operator->id'>$operator->name</option>";
    }

    if($operator->mailing == 0){
        $mail = $operator->mail;
    }else{
        $mail = "";
    }

    $mailing .= "<input type='hidden' value='$mail' id='mailing_$operator->id'>";

    $op_array[$operator->id] = $operator->name;
}


if(isset($_GET['mailing'])){

    $bookings = getBookings();
    $data = [];
    $i = 0;

    foreach ($bookings as $booking){

        $sql = "SELECT * FROM mdl_user WHERE id='$booking->user_id'";
        $user = $DB->get_record_sql($sql);


        $sql = "SELECT * FROM mdl_booking_history3 WHERE user_id='$booking->user_id' && type='film_ready && state > 0'";
        $history_mo_ready = $DB->get_record_sql($sql);

        if(!$history_mo_ready){

           $sql = "SELECT * FROM mdl_booking_history3 WHERE user_id='$booking->user_id' && type='film_production' && state > 0";
           $history_production = $DB->get_record_sql($sql);


            if(!$history_production){

                $sql = "SELECT * FROM mdl_booking_history3 WHERE user_id='$booking->user_id' && type='mail_mo' && state=1";
                $history_mo = $DB->get_record_sql($sql);

                if(!$history_mo){

                    $sql = "SELECT * FROM mdl_booking_history3 WHERE user_id='$booking->user_id' && type='register'";
                    $history = $DB->get_record_sql($sql);

                    if(!$history){

                        $sql = "SELECT * FROM mdl_booking_history3 WHERE b_id='$booking->order_id' && (type='er2' || type='mail_re2' || type='mail_er2') && state=1";
                        $history_er2 = $DB->get_record_sql($sql);

                        if(!$history_er2){

                            $sql = "SELECT * FROM mdl_booking_history3 WHERE b_id='$booking->order_id' && (type='er1' || type='mail_re1' || type='mail_er1') && state=1";
                            $history_er1 = $DB->get_record_sql($sql);

                            if(!$history_er1){
                                $sql = "SELECT * FROM mdl_booking_history3 WHERE b_id='$booking->order_id' && type='mail_co' && state=1";
                                $history_codes = $DB->get_record_sql($sql);
                            }
                        }
                    }
                }
            }
        }

        $data[$i]['id'] = $booking->id;
        $data[$i]['order_id'] = $booking->order_id;
        $data[$i]['school'] = $booking->school;
        $data[$i]['op'] =  $op_array[$booking->operators_id];
        $data[$i]['op_id'] =  $booking->operators_id;



        $pieces = explode("-", $booking->arrival);

        if(strlen($pieces[2]) > 2){
            $pieces[2] = substr($pieces[2], 0, -9);

        }

        $arr = $pieces[2]."/".$pieces[1]."/".$pieces[0];


        $data[$i]['arr'] =   $arr;

        $pieces = explode("-", $booking->departure);

        if(strlen($pieces[2]) > 2){
            $pieces[2] = substr($pieces[2], 0, -9);

        }

        $dep = $pieces[2]."/".$pieces[1]."/".$pieces[0];
        $data[$i]['dep'] =  $dep;

        $data[$i]['op_nr'] = $booking->ext_booking_id;
        $data[$i]['code'] = $user->username;
        $data[$i]['user_id'] = $user->id;
        $data[$i]['code_reg'] = $history->crdate;
        $data[$i]['note_reminder'] = $booking->note_reminder;
        $data[$i]['note'] = $booking->note;

        switch ($booking->product) {
            case 10:
                $data[$i]['product'] = "Event Berlin";
                break;
            case 11:
                $data[$i]['product'] = "Event Hamburg";
                break;
            case 12:
                $data[$i]['product'] = "Event München";
                break;
            case 8:
                $data[$i]['product'] = "Projekt Berlin";
                break;
            case 13:
                $data[$i]['product'] = "Projekt Hamburg";
                break;
            case 14:
                $data[$i]['product'] = "Projekt München";
                break;
            default:
                $data[$i]['product'] = "not set";
        }

      

        if( $booking->state == 2){

            $data[$i]['state'] = 'Storniert';
            $color = "red";

        }else{

            $data[$i]['state'] = 'Angelegt';
            $color = "grey";

            if($history_codes){
                $data[$i]['state'] = 'Codes versendet';
                $color = "orange";
            }


            if($history_er1){
                $data[$i]['state'] = "Erinnerung 1";
                $color = "#702963";
            }

            if($history_er2){
                $data[$i]['state'] = 'Erinnerung 2';
                $color = "#BF40BF";
            }


            if( $history->state == 1){

                $crdate = explode("-", $history->crdate);
                $day= explode(" ", $crdate[2]);
                $crdate = $day[0].".".$crdate[1].".".$crdate[0];

                $data[$i]['state'] = 'Registrierung';
                $data[$i]['state_time'] = $crdate."<br>".$day[1]." Uhr";

                $color = "#BDB5D5";

            }

            if($history_mo->state == 1){
                $data[$i]['state'] = 'Motivation versand';
                $color = "#13C184";
                $data[$i]['state_time'] = '';
            }


            if($history_production->state == 1){
                $data[$i]['state'] = 'Film freigegeben';
                $color = "#F781D8";
                $data[$i]['state_time'] = '';
            }


            if($history_production->state == 4){
                $data[$i]['state'] = 'Film Autofreigabe';
                $color = "#FF0000";
                $data[$i]['state_time'] = '';
            }

            if($history_production->state == 5){
               // print_r($history_production);
                //echo $booking->order_id." - ".$booking->user_id."<br>" ;
                $data[$i]['state'] = 'Film k. Registrierung';
                $color = "#FE9A2E";
                $data[$i]['state_time'] = '';
            }

            if($history_production->state == 6){
                $data[$i]['state'] = 'Film k. Abgaben';
                $color = "#61380B";
                $data[$i]['state_time'] = '';
            }

            if( $booking->state == 3){
                $data[$i]['state'] = 'Abgeschlossen';
                $color = "green";
            }

            if($history_mo_ready->state == 1){
                $data[$i]['state'] = 'Film verschickt';
                $color = "#0000FF";
                $data[$i]['state_time'] = $history_mo_ready->subject;
            }

        }

        $data[$i]['color'] = $color;

        $i++;

    }

}


if(isset($_GET['success'])){
    $success = true;
    $success_msg = $_GET['success'];
}

$error = false;
if(isset($_GET['error'])){
    $error = true;
    $error_msg = $_GET['error'];
}

rebuild_course_cache('8', true);
//print_r($bookings);
$templatecontext = [

    'report' =>  $_GET['report'],
    'o_select' => $o_select,
    'mailing' => $mailing,
    'b_id' => $b_id,
    'usernames' => $v_codes_display,
    'search_id' => $_POST['search_id'],
    'order_id' => $booking->order_id,
    'ext_booking_id' => $booking->ext_booking_id,
    'school' => $booking->school,
    'classname' => $booking->classname,
    'users' => $users,
    'bookings' => $data,
    'success' => $success,
    'success_msg' => $success_msg,
    'error' => $error,
    'error_msg' => $error_msg

];


if(isset($_GET['report'])){
    echo $OUTPUT->render_from_template("bookings/report", $templatecontext);
}else{
    if(isset($_GET['mailing'])){
        //booking overview
        echo $OUTPUT->render_from_template("bookings/buchungsubersicht", $templatecontext);
    }else{
        //create bookings
        if(isset($_GET['company'])){
            echo $OUTPUT->render_from_template("bookings/index_company", $templatecontext);
        }else{
            echo $OUTPUT->render_from_template("bookings/index", $templatecontext);
        }

    }
}

echo $OUTPUT->footer();

function getBooking($id){
    global $DB;
    $sql = "SELECT * FROM mdl_booking_data3 WHERE order_id = '$id'";
    $booking = $DB->get_record_sql($sql);
    return $booking;
}

function getBookings(){
    global $DB;
    $sql = "SELECT * FROM mdl_booking_data3  WHERE state = '0' || state = '2' || state = '3'";
    $bookings = $DB->get_records_sql($sql);
    return $bookings;
}

function getMonthNumber($monthStr) {
//e.g, $month='Jan' or 'January' or 'JAN' or 'JANUARY' or 'january' or 'jan'
    $m = ucfirst(strtolower(trim($monthStr)));
    switch ($m) {
        case "Januar":
        case "Jan":
            $m = "01";
            break;
        case "Februar":
        case "Feb":
            $m = "02";
            break;
        case "März":
        case "Mar":
            $m = "03";
            break;
        case "April":
        case "Apr":
            $m = "04";
            break;
        case "Mai":
            $m = "05";
            break;
        case "Juni":
        case "Jun":
            $m = "06";
            break;
        case "July":
        case "Jul":
            $m = "07";
            break;
        case "August":
        case "Aug":
            $m = "08";
            break;
        case "September":
        case "Sep":
            $m = "09";
            break;
        case "Oktober":
        case "Oct":
            $m = "10";
            break;
        case "November":
        case "Nov":
            $m = "11";
            break;
        case "Dezember":
        case "Dec":
            $m = "12";
            break;
        default:
            $m = false;
            break;
    }
    return $m;
}