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
    $booking_data->state = "3";
    $DB->update_record('booking_data3', $booking_data);

  


}

$_GET['overview_trigger'] = 1;

if(isset($_GET['overview_trigger']) && $_GET['overview_trigger'] == 1){
    $msg = "Klasse/Buchung ".$b_id." abgeschlossen";
    header("Location: ../../view.php?id=172&mailing=true&success=".$msg);
}else{
    $msg = "Klasse gelöscht";
    header("Location: ../booking.php?search_id=".$b_id."&id=172&active_tab=1&success=".$msg);
}


