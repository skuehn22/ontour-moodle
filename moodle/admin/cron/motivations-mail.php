<?php

define('CLI_SCRIPT', true);

require(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once($CFG->libdir.'/cronlib.php');
require_once($CFG->libdir.'/pdflib.php');



#sendMotivation();


function sendMotivation(){

    global $DB;

    $datetime = new DateTime('tomorrow');
    $date_check = $datetime->format('Y-m-d H:i:s');

    /* get email sender user --> kontakt@ontour.org */
    $sql = "SELECT * FROM mdl_user WHERE id = 6163";
    $from_user = $DB->get_record_sql($sql);

    $sql = "SELECT * FROM {booking_history3} WHERE type = 'mail_mo' && state = 2 && action_date < '$date_check'";



    $mails = $DB->get_records_sql($sql);

    foreach ($mails as $mail){

        $sql = "SELECT * FROM {booking_history3} WHERE type = 'register' && state = 1 && b_id = $mail->b_id";
        $register = $DB->get_record_sql($sql);

        if($register && $mail->action_date < $date_check){

            $sql = "SELECT * FROM {booking_data3} WHERE order_id = '$mail->b_id' && user_id = '$mail->user_id' && state = 0";
            $bookings = $DB->get_records_sql($sql);

            foreach ($bookings as $booking){

                $sql = "SELECT * FROM mdl_ext_operators1 WHERE id = ".$booking->operators_id;
                $op = $DB->get_record_sql($sql);

                if($op->mailing_traveler == 1){
                    $sql = "SELECT * FROM mdl_user WHERE phone1 = '$mail->b_id'";
                    $durchfuerender = $DB->get_record_sql($sql);


                    if($booking->type == "mail_mo" && strpos($durchfuerender->email, "g6_") !== false){
                        $sql = "SELECT * FROM {user} WHERE id = $booking->user_id-6";
                        $durchfuerender = $DB->get_record_sql($sql);
                    }

                    /* create greeting */
                    if($booking->operators_id == 1){
                        $anrede = "Hallo ".$booking->firstname." ".$booking->lastname;
                    }else{

                        if($booking->newsletter == 9){
                            $anrede = "Hallo ".$booking->firstname." ".$booking->lastname;
                        }else{
                            $anrede = "Liebe Lehrkraft";
                        }
                    }

                    $anrede = "Liebe Lehrkraft";


                    //echo $booking->operators_id;
                    $date = new DateTime($booking->arrival);
                    $kw = $date->format('W');

                    if($kw != ""){
                        $sub_kw = "KW ".$kw;
                    }

                    /* create greeting */
                    if($booking->operators_id == 1){
                        $email_subject = "Hinweis Videoprojekt Berlin - Buchungs-Nr. ".$booking->order_id;
                    }else{

                        if($booking->newsletter == 9){
                            $email_subject = "Hinweis Videoprojekt Berlin - ".$booking->ext_booking_id. " - ".$booking->order_id." ".$sub_kw;
                        }else{
                            $email_subject = "Hinweis Videoprojekt Berlin - ".$op->name." Nr.".$booking->ext_booking_id." ".$sub_kw;
                        }
                    }
                    ob_start();

                    include "../../mod/bookings/includes/emails/motivation_template.php";


                    $message = ob_get_clean();

                    set_config('allowedemaildomains', 'ontour.org');
                    set_config('emailheaders', 'X-Fixed-Header: bar');

                    if($op->mailing_traveler == 0){

                        $emailuser = new stdClass();
                        $emailuser->email = $op->mail;
                        $emailuser->firstname = $op->name;
                        $emailuser->lastname = "";

                    }else{
                        $emailuser = new stdClass();
                        $emailuser->email = $durchfuerender->email;
                        $emailuser->firstname = $booking->firstname;
                        $emailuser->lastname = $booking->lastname;

                    }

                    $emailuser->maildisplay = true;
                    $emailuser->mailformat = 1;
                    $emailuser->id = -99;
                    $emailuser->firstnamephonetic = "";
                    $emailuser->lastnamephonetic = "";
                    $emailuser->middlename = "";
                    $emailuser->alternatename = "";


                    if($booking->operators_id == 1){
                        email_to_user($emailuser, $from_user, $email_subject, $message);
                    }else{
                        email_to_user($emailuser, $from_user, $email_subject, $message);
                    }


                    $email_subject = "Kopie MOTIVATION 1 AUTO (".$emailuser->email."): ".$email_subject;

                    if ($booking->operators_id == 1) {
                        email_to_user($from_user, $from_user, $email_subject, $message);
                    } else {
                        email_to_user($from_user, $from_user, $email_subject, $message);
                    }

                    setMotivationMailState($mail->b_id, "1", $booking->user_id);

                }
            }
        }

    }
}

function setMotivationMailState($b_id, $state, $user_id){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_history3 WHERE b_id = '$b_id' && type = 'mail_mo' && state = 2";
    $mail_mo = $DB->get_record_sql($sql);

    $my_date = date("Y-m-d H:i:s");

    $data = new stdClass();
    $data->id = $mail_mo->id;
    $data->state = $state;
    $data->action_date = $my_date;
    $DB->update_record('booking_history3', $data);


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

        if($booking->classname == "Klasse 5"){
            $classname = "5";
        }

        $z_codes_string .='<div style="display: flex;"><div style="width: 300px;" width="300">Zugangscodes Kundenbereich Klasse '.$classname.'</div><div><strong>'.$b->username.'</strong></div></div>';

    }

    return $z_codes_string;

}

function setReminder1MailState($b_id, $state, $user_id){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_history3 WHERE b_id = '$b_id' && type = 'mail_er1' && state = 2";
    $mail_mo = $DB->get_record_sql($sql);


    $data = new stdClass();
    $data->id = $mail_mo->id;
    $data->state = $state;
    $DB->update_record('booking_history3', $data);

}

function setReminder2MailState($b_id, $state, $user_id){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_history3 WHERE b_id = '$b_id' && type = 'mail_er2' && state = 2";
    $mail_mo = $DB->get_record_sql($sql);


    $data = new stdClass();
    $data->id = $mail_mo->id;
    $data->state = $state;
    $DB->update_record('booking_history3', $data);


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

cron_run();
