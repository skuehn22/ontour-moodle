<?php

require(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');


$id = optional_param('id', 0, PARAM_INT);
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
$PAGE->set_url('/mod/bookings/includes/booking.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

use DB;
$b_id = 0;

global $CFG;
global $USER;


if(isset($_GET['search_id'])){

    $search_term = $_GET['search_id'];


    $sql = "SELECT * FROM mdl_user WHERE phone1 = '$search_term'";
    $v_codes = $DB->get_records_sql($sql);
    $count = count($v_codes);


    //check search term is ontour booking id
    if($count > 0){
        foreach ($v_codes as $v_code){
            if($v_code->password != ""){
                $v_codes_display .= $v_code->username."<br>";
            }

        }

        $b_id = $search_term;

    }else{

        //check if search term is z-code

        $sql = "SELECT * FROM mdl_user WHERE username = '$search_term'";
        $user = $DB->get_record_sql($sql);


        if($user){



            $sql = "SELECT * FROM mdl_user WHERE phone1 = '$user->phone1'";
            $all_users = $DB->get_records_sql($sql);

            foreach ($all_users as $all_user){
                $v_codes_display .= $all_user->username."<br>";
            }

            $b_id = $user->phone1;

        }else{

            //check if search term is operator booking number
            //$external = $DB->get_record('booking_info', array('ext_booking_id' =>  $_GET['search_id']), 'order_id');

            $sql = "SELECT * FROM mdl_booking_data3 WHERE state IN (0,2,3) && ext_booking_id = '$search_term'";
            $external = $DB->get_record_sql($sql);

            if($external){

                $sql = "SELECT * FROM mdl_user WHERE phone1 = '$external->order_id'";
                $all_users = $DB->get_records_sql($sql);

                //$all_users = $DB->get_records('user', array('phone1' =>  $external->order_id), 'username, phone1');
                foreach ($all_users as $all_user){
                    $v_codes_display .= $all_user->username."<br>";
                }

                $b_id = $all_user->phone1;



            }else{
                $v_codes_display = "Keine Buchung vorhanden";
                $b_id = 0;
            }


        }

    }

    //after booking was found check if active
    $sql = "SELECT * FROM mdl_booking_data3 WHERE state IN (0,2,3) && order_id = '$b_id'";
    $checker = $DB->get_record_sql($sql);

    $state = $checker->state;

    if($state == 0){
        $state = "aktiv";
    }elseif($state==2){
        $state = "storniert";
    }else{
        $state = "abgeschlossen";
    }

    if(!$checker){
        $v_codes_display = "Keine Buchung vorhanden";
        $b_id = 0;
    }


    $sql = "SELECT * FROM mdl_ext_operators1 WHERE id = '$checker->operators_id'";
    $operator = $DB->get_record_sql($sql);

    $ext_b_id = $checker->ext_booking_id;


    $history = getHistory($b_id);



    $history_data = "";

    foreach ($history as $h){

        $history_data .= date('d.m.Y H:i',strtotime($h->action_date)). " Uhr: ".$h->subject."<br>";

    }




    $bookings = getBookings($b_id);
    $planned_data = "";

    foreach ($bookings as $booking){







        $servername = "localhost";
        $username = "skuehn22";
        $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
        $dbname = "projektreisenWordpress_1637922561";

        $conn = new mysqli($servername, $username, $password, $dbname);

        $sql = "SELECT * FROM wp_woocommerce_order_items WHERE order_id = '$b_id' && order_item_name = 'Videoprojekt'";
        $result = $conn->query($sql);


        while($row = $result->fetch_assoc()) {

            $item_id = $row["order_item_id"];

            $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$item_id' && meta_key = 'gruppenname'";
            $result2 = $conn->query($sql);
            $group_name = $result2->fetch_assoc();


            if($group_name['meta_value'] == $booking->classname){
                $right_item = $row;
            }

        }

        $order_item_id = $right_item['order_item_id'];

        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'anreise'";
        $result = $conn->query($sql);
        $arr = $result->fetch_assoc();

        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'abreise'";
        $result = $conn->query($sql);
        $dep = $result->fetch_assoc();

        $dep = explode("-", $dep['meta_value']);
        $dep = $dep['2'].".".$dep['1'].".".$dep['0'];

        $arr = explode("-", $arr['meta_value']);
        $arr = $arr['2'].".".$arr['1'].".".$arr['0'];

        $planned = getPlannend($b_id, $booking->user_id);
        $planned_data .= "<span style='font-weight: 700; font-size: 16px;' class='pt-3 pb-3' style='font-weight: 700'>".$booking->classname."</span> - ".$arr." - ".$dep."<br><br>";

        foreach ($planned as $p){

            $planned_data .= date('d.m.Y H:i',strtotime($p->action_date)). " Uhr: ".$p->subject."<br>";

        }
    }

    if($booking->state == 2){

        $history = getStorno($b_id);

        $found = 0;

        foreach ($history as $h){

            if($found == 0){
                $history_data .= date('d.m.Y H:i',strtotime($h->action_date)). " Uhr: ".$h->subject."<br>";
                $found++;
            }


        }

    }




    $sql = "SELECT * FROM mdl_ext_operators1 WHERE active = 1 ORDER BY sort ASC";
    $operators = $DB->get_records_sql($sql);
    $o_select = "";
    $mailing = "";

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
    }

}


$found = false;
$no_results = false;

if( $b_id != 0){
    $found = true;
}


if($b_id == 0 && isset($_GET['search_id'])){
    $no_results = true;
}



$bookings = getBookings($b_id);
$option = "";

foreach ($bookings as $b){
    $option_classes .= "<option value='".$b->user_id."'>".$b->classname."</option>";
}

$show_class_selector = false;
if(count($bookings)>1){
    $show_class_selector = true;
}

if(isset($_GET['active_tab'])){
    $tab = $_GET['active_tab'];
}else{
    $tab = 0;
}

$servername = "localhost";
$username = "skuehn22";
$password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
$dbname = "projektreisenWordpress_1637922561";


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM wp_postmeta WHERE post_id = '$b_id'";
$result = $conn->query($sql);


while($row = $result->fetch_assoc()) {

     if($row['meta_key'] == '_billing_first_name'){
         $firstname = $row['meta_value'];
     }

    if($row['meta_key'] == '_billing_last_name'){
        $lastname = $row['meta_value'];
    }

    if($row['meta_key'] == '_billing_email'){
        $email = $row['meta_value'];
    }

    if($row['meta_key'] == '_billing_company'){
        $org = $row['meta_value'];
    }

    if($row['meta_key'] == '_billing_postcode'){
        $zip = $row['meta_value'];
    }

    if($row['meta_key'] == '_billing_address_1'){
        $addr = $row['meta_value'];
    }

    if($row['meta_key'] == '_billing_city'){
        $city = $row['meta_value'];
    }

    if($row['meta_key'] == '_billing_country'){
        $co = $row['meta_value'];
    }

}


$error = false;
$error_msg = "";

if(isset($_GET['error'])){
    $error = true;
    $error_msg = $_GET['error'];
}


$success = false;
$success_msg = "";

if(isset($_GET['success'])){
    $success = true;
    $success_msg = $_GET['success'];
}

$mail_case_3 = false;
if($booking->newsletter == 9){
    $mail_case_3 = true;
}

$film_type = false;


if(isset($booking->user_id)){
    $sql = "SELECT * FROM mdl_booking_history3 WHERE user_id = $booking->user_id && type = 'mail_fi'";
    $mail_mo = $DB->get_record_sql($sql);

    if($mail_mo->state == 2){
        $film_type = true;
    }


    if($mail_mo->state > 0){
        $film_btn = true;
    }

}



$templatecontext = [
    'usernames'     => $v_codes_display,
    'count'         =>  $external->ext_booking_id,
    'b_id'          => $b_id,
    'ext_b_id'          => $ext_b_id,
    'o_select' => $o_select,
    'found' => $found,
    'no_results' => $no_results,
    'school' => $booking->school,
    'note' => $booking->note,
    'note_reminder' => $booking->note_reminder,
    'crdate' => date('d.m.Y H:i',strtotime($booking->crdate)). " Uhr",
    'searchterm' => $search_term,
    'history_data' => $history_data,
    'planned_data' => $planned_data,
    'bookings' => $bookings,
    'tab' => $tab,
    'option_classes' => $option_classes,
    'show_class_selector' => $show_class_selector,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'email' => $email,
    'org' => $org,
    'zip' => $zip,
    'addr' => $addr,
    'city' => $city,
    'co' => $co,
    'success' => $success,
    'success_msg' => $success_msg,
    'error' => $error,
    'error_msg' => $error_msg,
    'mail_case_3' => $mail_case_3,
    'film_type' => $film_type,
    'film_btn' => $film_btn,
    'state' => $state,
    'product' => "sdsd",

];


echo $OUTPUT->render_from_template("bookings/report", $templatecontext);
echo $OUTPUT->footer();

function getBooking($id){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_data3 WHERE order_id = '$id'";
    $booking = $DB->get_record_sql($sql);

    return $booking;

}


function getBookings($id){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_data3 WHERE order_id = '$id' && mailing < '100' && (state = 0 || state = 2)";
    $booking = $DB->get_records_sql($sql);

    return $booking;

}



function getHistory($id){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_history3 WHERE b_id = '$id'  && state = 1";
    $history = $DB->get_records_sql($sql);

    return $history;

}


function getPlannend($id, $user){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_history3 WHERE b_id = '$id'  && state = 2 && user_id = $user";
    $planned = $DB->get_records_sql($sql);

    return $planned;

}


function getStorno($id){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_history3 WHERE b_id = '$id'  && state = 2 && type = 'sorno'";
    $history = $DB->get_records_sql($sql);

    return $history;

}



function sendMail(){
    $to      = 'kuehn.sebastian@gmail.com';
    $subject = 'Zugangscodes';
    $message = 'Hallo dies ist ein test';
    $headers = 'From: kontakt@ontour.org' . "\r\n" .
        'Reply-To: kontakt@ontour.org' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();

    mail($to, $subject, $message, $headers);
}