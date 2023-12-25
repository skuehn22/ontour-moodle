<?php

require(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/../../lib.php');

global $DB;

$user = $_GET['user_delete_id'];
$b_id = $_GET['bid_delete_id'];

$user_data = new stdClass();
$user_data->id = $user;
$user_data->password = "";
$user_data->username = time();
$DB->update_record('user', $user_data);

$sql = "SELECT * FROM mdl_user WHERE id = '$user'";
$user_set = $DB->get_record_sql($sql);


$sql = "SELECT * FROM mdl_booking_data3 WHERE user_id = '$user'";
$user_set2 = $DB->get_record_sql($sql);
echo "test";
echo $user_set2->id;

$booking_data = new stdClass();
$booking_data->id = $user_set2->id;
$booking_data->state = "1";
$DB->update_record('booking_data3', $booking_data);



if(isset($_GET['overview_trigger']) && $_GET['overview_trigger'] == 1){
    $msg = "Klasse/Buchung ".$b_id." gelöscht";
    header("Location: ../../view.php?id=172&mailing=true&success=".$msg);
}else{
    $msg = "Klasse gelöscht";
    header("Location: ../booking.php?search_id=".$b_id."&id=172&active_tab=1&success=".$msg);
}


