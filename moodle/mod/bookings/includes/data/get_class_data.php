<?php

require(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/../../lib.php');

$b_id = $_GET['b_id'];
$classname = $_GET['classname'];

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

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT * FROM wp_woocommerce_order_items WHERE order_id = '$b_id' && order_item_name = 'Videoprojekt'";
$result = $conn->query($sql);


while($row = $result->fetch_assoc()) {

    $item_id = $row["order_item_id"];

    $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$item_id' && meta_key = 'gruppenname'";
    $result2 = $conn->query($sql);
    $group_name = $result2->fetch_assoc();

    if($group_name['meta_value'] == $classname){
        $right_item = $row;
    }

}

//$order_item = $result->fetch_assoc();
$order_item_id = $right_item['order_item_id'];

$sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'anreise'";
$result = $conn->query($sql);
$arr = $result->fetch_assoc();

if (strpos($arr['meta_value'], '-') !== false) {
    $arr = $arr['meta_value'];
}else{
    $pieces = explode(" ", $arr['meta_value']);
    $m = getMonthNumber($pieces[0]);
    $pieces2 = explode(",", $pieces[1]);

    if(strlen($pieces2[0]) == 1){
        $day = "0".$pieces2[0];
    }else{
        $day = $pieces2[0];
    }

    $arr = $pieces[2]."-".$m."-".$day;

}

$sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'abreise'";
$result = $conn->query($sql);
$dep = $result->fetch_assoc();

if (strpos($dep['meta_value'], '-') !== false) {
    $dep = $dep['meta_value'];
}else{
    $pieces = explode(" ", $dep['meta_value']);
    $m = getMonthNumber($pieces[0]);
    $pieces2 = explode(",", $pieces[1]);

    if(strlen($pieces2[0]) == 1){
        $day = "0".$pieces2[0];
    }else{
        $day = $pieces2[0];
    }

    $dep = $pieces[2]."-".$m."-".$day;

}

$sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'alter_schuler'";
$result = $conn->query($sql);
$age = $result->fetch_assoc();

$sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'anzahl_schuler'";
$result = $conn->query($sql);
$amount = $result->fetch_assoc();

global $DB;
$sql = "SELECT * FROM mdl_booking_data3 WHERE classname='$classname' && order_id = '$b_id'";
$booking = $DB->get_record_sql($sql);

$sql = "SELECT * FROM mdl_user WHERE id='$booking->user_id'";
$user = $DB->get_record_sql($sql);


$sql = "SELECT * FROM mdl_booking_history3 WHERE user_id = $booking->user_id && type = 'film_production'";
$mail_mo = $DB->get_record_sql($sql);


$msg['movie']  = $mail_mo->state;


$sql = "SELECT * FROM mdl_booking_history3 WHERE user_id = $booking->user_id && type = 'film_ready'";
$mail_ready = $DB->get_record_sql($sql);
if($mail_ready){
    $msg['movie_ready']  = $mail_ready->subject;
}


$data = $_POST['name'];

$msg['dep'] = $dep;
$msg['arr'] = $arr;
$msg['age'] = $age['meta_value'];
$msg['amount'] = $amount['meta_value'];
$msg['classname_film'] = $booking->classname_teacher;
$msg['zcode'] = $user->username;
$msg['teacher'] = $user->firstname." ". $user->lastname;
$msg['teacher_email'] = $user->email;
$msg['total'] = $booking->total;
$msg['note_teacher'] = $booking->class_note;


echo json_encode($msg);


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