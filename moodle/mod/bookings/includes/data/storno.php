<?php

require(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/../../lib.php');

global $DB;

$b_id = $_GET['b_id'];

$sql = "SELECT * FROM mdl_booking_data3 WHERE order_id = '$b_id'";
$bookings = $DB->get_records_sql($sql);

foreach ($bookings as $booking){

    $booking_data = new stdClass();
    $booking_data->id = $booking->id;
    $booking_data->state = "2";
    $DB->update_record('booking_data3', $booking_data);

    $sql = "SELECT * FROM mdl_user WHERE id = '$booking->user_id'";
    $user_set = $DB->get_record_sql($sql);

    $user_data = new stdClass();
    $user_data->id = $user_set->id;
    $user_data->username = $user_set->username."_";
    $DB->update_record('user', $user_data);

    $obj = new stdClass();
    $obj->b_id = $booking->order_id;
    $obj->user_id = $booking->user_id;
    $obj->receiver = "";
    $obj->sender = "";
    $obj->type = "sorno";
    $obj->subject = "Stornierung";
    $obj->state = 2;
    $today   = new DateTime();
    $obj->action_date =  date("Y-m-d");
    $DB->insert_record('booking_history3', $obj);



}

$_GET['overview_trigger'] = 1;

if(isset($_GET['overview_trigger']) && $_GET['overview_trigger'] == 1){
    $msg = "Klasse/Buchung ".$b_id." storniert";
    header("Location: ../../view.php?id=172&mailing=true&success=".$msg);
}else{
    $msg = "Klasse gelöscht";
    header("Location: ../booking.php?search_id=".$b_id."&id=172&active_tab=1&success=".$msg);
}


