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


$PAGE->set_url('/mod/bookings/includes/data.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

use DB;

if(isset($_GET['search_id'])){

    $v_codes = $DB->get_records('user', array('phone1' =>  $_GET['search_id']), 'username');
    $count = count($v_codes);

    //check search term is ontour booking id
    if($count > 0){
        foreach ($v_codes as $v_code){
            $v_codes_display .= $v_code->username."<br>";
        }

        $b_id = $_GET['search_id'];

    }else{

        //check if search term is z-code
        $user = $DB->get_record('user', array('username' =>  $_GET['search_id']), 'username, phone1');

        if($user){

            $all_users = $DB->get_records('user', array('phone1' =>  $user->phone1), 'username');

            foreach ($all_users as $all_user){
                $v_codes_display .= $all_user->username."<br>";
            }

            $b_id = $user->phone1;

        }else{

            //check if search term is operator booking number
            $external = $DB->get_record('booking_info', array('ext_booking_id' =>  $_GET['search_id']), 'order_id');

            if($external){

                $all_users = $DB->get_records('user', array('phone1' =>  $external->order_id), 'username, phone1');
                foreach ($all_users as $all_user){
                    $v_codes_display .= $all_user->username."<br>";
                }

                $b_id = $all_user->phone1;


            }else{
                $v_codes_display = "Keine Buchung vorhanden";
            }

        }



    }

    $booking = getBooking($b_id);
    $sql = "SELECT * FROM mdl_ext_operators WHERE active = 1 ORDER BY sort ASC";
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


$templatecontext = [
    'usernames'     => $v_codes_display,
    'count'         => $count,
    'b_id'          => $b_id,
    'o_select' => $o_select,
];


echo $OUTPUT->render_from_template("bookings/report", $templatecontext);
echo $OUTPUT->footer();



function getBooking($id){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_data3 WHERE order_id = '$id'";
    $booking = $DB->get_record_sql($sql);


    return $booking;

}