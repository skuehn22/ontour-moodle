<?php
require(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/../../lib.php');

global $DB;
global $CFG;

$b_id = $_GET['booking_id_data'];
$note_teacher = "";
$sql = "SELECT * FROM mdl_booking_data3 WHERE order_id = '$b_id' && mailing < '100'";
$bookings = $DB->get_records_sql($sql);

foreach ($bookings as $booking){

    $data = new stdClass();
    $data->id = $booking->id;
    $data->school = $_GET['school'];
    $data->note = $_GET['note'];
    $data->note_reminder = $_GET['reminder'];
    $data->operators_id = $_GET['operators'];
   

    if($_GET['book_for_operator']){
        $data->newsletter = 9;
    }else{
        $data->newsletter = 0;
    }

    $DB->update_record('booking_data3', $data);

}


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


$firstname = $_GET['firstname'];
$lastname = $_GET['lastname'];
$city = $_GET['city'];
$country = $_GET['country'];
$zip = $_GET['plz'];
$street = $_GET['street'];
$org = $_GET['org'];
$email = $_GET['email'];


$sql = "UPDATE wp_postmeta SET meta_value='$firstname' WHERE meta_key='_billing_first_name' && post_id = '$b_id'";
$conn->query($sql);

$sql = "UPDATE wp_postmeta SET meta_value='$lastname' WHERE meta_key='_billing_last_name' && post_id = '$b_id'";
$conn->query($sql);

$sql = "UPDATE wp_postmeta SET meta_value='$city' WHERE meta_key='_billing_city' && post_id = '$b_id'";
$conn->query($sql);

$sql = "UPDATE wp_postmeta SET meta_value='$country' WHERE meta_key='_billing_country' && post_id = '$b_id'";
$conn->query($sql);

$sql = "UPDATE wp_postmeta SET meta_value='$zip' WHERE meta_key='_billing_postcode' && post_id = '$b_id'";
$conn->query($sql);

$sql = "UPDATE wp_postmeta SET meta_value='$street' WHERE meta_key='_billing_address_1' && post_id = '$b_id'";
$conn->query($sql);

$sql = "UPDATE wp_postmeta SET meta_value='$org' WHERE meta_key='_billing_company' && post_id = '$b_id'";
$conn->query($sql);

$sql = "UPDATE wp_postmeta SET meta_value='$email' WHERE meta_key='_billing_email' && post_id = '$b_id'";
$conn->query($sql);


$msg = "Daten aktualisiert";
header("Location: ../booking.php?search_id=".$b_id."&id=172&active_tab=2&success=".$msg);