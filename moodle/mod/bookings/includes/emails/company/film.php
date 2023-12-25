<?php
require(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/../../lib.php');

global $USER;
global $DB;

$user_id = $_GET['user'];
$b_id =  $_GET['b_id'];

/* get email sender user */
$sql = "SELECT * FROM mdl_user WHERE id = 6163";
$from_user = $DB->get_record_sql($sql);

$sql = "SELECT * FROM mdl_user WHERE id = ".$_GET['user'];
$user = $DB->get_record_sql($sql);

$sql = "SELECT * FROM mdl_booking_data3 WHERE user_id = ".$_GET['user'];
$booking = $DB->get_record_sql($sql);

$app_id = $_GET['user'] + 6;

$sql = "SELECT * FROM mdl_user WHERE id = $app_id";
$app_user = $DB->get_record_sql($sql);
$app_code = $app_user->username;

$sql = "SELECT * FROM mdl_ext_operators1 WHERE id = ".$booking->operators_id;
$op = $DB->get_record_sql($sql);

/* get the all bookings */
$sql = "SELECT * FROM mdl_booking_data3 WHERE order_id = ".$_GET['b_id'];
$bookings = $DB->get_records_sql($sql);

/* generate z-codes table for mail text */
$z_codes_string = get_z_codes($bookings);

$date = new DateTime($booking->arrival);
$kw = $date->format('W');

if($kw != ""){
    $sub_kw = "KW ".$kw;
}

/* create greeting */
if($booking->operators_id == 1){
    $email_subject = "Videoprojekt Berlin - Buchungs-Nr. ".$b_id;
}else{

    if($booking->newsletter == 9){
        $email_subject = "Videoprojekt Berlin - ".$booking->ext_booking_id. " - ".$b_id." ".$sub_kw;
    }else{
        $email_subject = $op->name." Videoprojekt Berlin - Nr. Veranstalter. ".$booking->ext_booking_id." ".$sub_kw;
    }


}


/* get all booking data from wordpress */
$data = getData($b_id);

/* create greeting */
if($booking->operators_id == 1){
    $anrede = "Hallo ".$data[0]['firstname']." ".$data[0]['lastname'];
}else{

    if($booking->newsletter == 9){
        $anrede = "Hallo ".$data[0]['firstname']." ".$data[0]['lastname'];
    }else{
        $anrede = "Liebe Lehrkraft";
    }
}

ob_start();

$sql = "SELECT * FROM mdl_booking_history3 WHERE user_id = $booking->user_id && type = 'mail_fi'";
$mail_mo = $DB->get_record_sql($sql);

if($mail_mo->state == 2){
    include "film_template1.php";
}else{
    include "film_template2.php";
}


$message = ob_get_clean();


set_config('allowedemaildomains', 'ontour.org');
set_config('emailheaders', 'X-Fixed-Header: bar');


if($op->mailing_traveler == 0){

    $emailuser = new stdClass();
    $emailuser->email = $op->mail;
    $emailuser->firstname = $op->name;
    $emailuser->lastname = "";
    $emailuser->maildisplay = true;
    $emailuser->mailformat = 1;
    $emailuser->id = -99;
    $emailuser->firstnamephonetic = "";
    $emailuser->lastnamephonetic = "";
    $emailuser->middlename = "";
    $emailuser->alternatename = "";
    email_to_user($emailuser, $from_user, $email_subject, $message);

}else{
    $emailuser = $user;
    //SPERRE
    //email_to_user($user, $from_user, $email_subject, $message);
}


$email_subject = "Kopie MAIL MANUELL(".$emailuser->email."): ".$email_subject;

if ($booking->operators_id == 1) {
    email_to_user($from_user, $from_user, $email_subject, $message);
} else {
    email_to_user($from_user, $from_user, $email_subject, $message);
}




$sql = "SELECT * FROM mdl_booking_history3 WHERE user_id = $user_id && type = 'mail_re' && state = 2";
$mail_mo = $DB->get_record_sql($sql);


if($mail_mo){

    $data = new stdClass();
    $data->id = $mail_mo->id;
    $data->state = 1;
    $DB->update_record('booking_history3', $data);

}else{

    /* insert new history when mail was sent another time */
    $obj = new stdClass();
    $obj->b_id = $_GET['b_id'];
    $obj->user_id = $user->id;
    $obj->receiver = "";
    $obj->sender = "";
    $obj->type = "mail_re";
    $obj->subject = "Erinnerung geschickt";
    $obj->state = 1;
    $DB->insert_record('booking_history3', $obj);

}

function getData($b_id){

    $servername = "localhost";
    $username = "skuehn22";
    $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
    $dbname = "projektreisenWordpress_1637922561";

    $conn = new mysqli($servername, $username, $password, $dbname);


    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT * FROM wp_woocommerce_order_items WHERE order_id = '$b_id' && order_item_name = 'Videoprojekt'";
    $result = $conn->query($sql);
    $i = 0;
    $tax = 0;
    $line_total = 0;



    foreach ($result as $row){


        $order_item_id = $row["order_item_id"];

        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'anreise'";
        $result = $conn->query($sql);
        $arr = $result->fetch_assoc();

        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'abreise'";
        $result = $conn->query($sql);
        $dep = $result->fetch_assoc();

        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'alter_schuler'";
        $result = $conn->query($sql);
        $age = $result->fetch_assoc();

        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'anzahl_schuler'";
        $result = $conn->query($sql);
        $amount = $result->fetch_assoc();

        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'gruppenname'";
        $result = $conn->query($sql);
        $gruppe = $result->fetch_assoc();


        if (strpos($arr['meta_value'], '-') !== false) {
            $pieces = explode("-", $arr['meta_value']);
            $data[$i]['arr'] = $pieces[2].".".$pieces[1].".".$pieces[0];
        }else{
            $pieces = explode(" ", $arr['meta_value']);
            $m = getMonthNumber($pieces[0]);
            $pieces2 = explode(",", $pieces[1]);

            if(strlen($pieces2[0]) == 1){
                $day = "0".$pieces2[0];
            }else{
                $day = $pieces2[0];
            }

            $data[$i]['arr'] = $day.".".$m.".".$pieces[2];

        }

        if (strpos($dep['meta_value'], '-') !== false) {
            $pieces = explode("-", $dep['meta_value']);
            $data[$i]['dep'] = $pieces[2].".".$pieces[1].".".$pieces[0];
        }else{
            $pieces = explode(" ", $dep['meta_value']);
            $m = getMonthNumber($pieces[0]);
            $pieces2 = explode(",", $pieces[1]);

            if(strlen($pieces2[0]) == 1){
                $day = "0".$pieces2[0];
            }else{
                $day = $pieces2[0];
            }

            $data[$i]['dep'] = $day.".".$m.".".$pieces[2];

        }

        $data[$i]['age'] = $age['meta_value'];
        $data[$i]['amount'] = $amount['meta_value'];
        $data[$i]['gruppe'] = $gruppe['meta_value'];


        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = '_line_tax'";
        $result = $conn->query($sql);
        $tax_tmp = $result->fetch_assoc();

        $tax = $tax + $tax_tmp['meta_value'];


        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = '_line_total'";
        $result = $conn->query($sql);
        $line_tmp = $result->fetch_assoc();

        $data[$i]['item_price'] = number_format((float)$line_tmp['meta_value'], 2, '.', '');
        $line_total = $line_total + $line_tmp['meta_value'];

        $i++;

    }

    $data[0]['tax'] =  number_format((float)$tax, 2, '.', '');
    $data[0]['line_total'] =  number_format((float)$line_total, 2, '.', '');
    $data[0]['total'] = $data[0]['tax'] + $data[0]['line_total'];
    $data[0]['total'] =  number_format((float)$data[0]['total'], 2, '.', '');

    $sql = "SELECT * FROM wp_postmeta WHERE post_id = '$b_id'";
    $result = $conn->query($sql);


    while($row = $result->fetch_assoc()) {

        if($row['meta_key'] == '_billing_first_name'){
            $data[0]['firstname'] = $row['meta_value'];
        }

        if($row['meta_key'] == '_billing_last_name'){
            $data[0]['lastname'] = $row['meta_value'];
        }

        if($row['meta_key'] == '_billing_email'){
            $data[0]['email'] = $row['meta_value'];
        }

        if($row['meta_key'] == '_billing_company'){
            $data[0]['org'] = $row['meta_value'];
        }

        if($row['meta_key'] == '_billing_postcode'){
            $data[0]['zip'] = $row['meta_value'];
        }

        if($row['meta_key'] == '_billing_address_1'){
            $data[0]['addr'] = $row['meta_value'];
        }

        if($row['meta_key'] == '_billing_city'){
            $data[0]['city'] = $row['meta_value'];
        }

        if($row['meta_key'] == '_billing_country'){
            $data[0]['co'] = $row['meta_value'];
        }

    }

    return $data;

}

function get_z_codes($bookings){

    global $DB;
    $z_codes_string = '';

    /* generate z-codes table for mail text */
    foreach ($bookings as $booking){

        $sql = "SELECT * FROM mdl_user WHERE id = ".$booking->user_id;
        $b = $DB->get_record_sql($sql);

        $classname = $booking->classname;

        if($booking->classname == "Klasse 1"){
            $classname = "1";
        }

        if($booking->classname == "Klasse 2"){
            $classname = "2";
        }

        if($booking->classname == "Klasse 3"){
            $classname = "3";
        }

        if($booking->classname == "Klasse 4"){
            $classname = "4";
        }

        $z_codes_string .='<div style="display: flex;"><div style="width: 300px;" width="300">Zugangscodes Kundenbereich Klasse '.$classname.'</div><div><strong>'.$b->username.'</strong></div></div>';

    }

    return $z_codes_string;

}