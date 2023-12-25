<?php
require(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');

global $DB;


$b_id = $_GET['b_id'];

if(isset($_GET['action']) && $_GET['action'] == "delete"){
    $delete = "-1";
}

if(isset($_GET['date'])){

    $user_id = $_GET['class_selector']+6;
    $type = $_GET['type_selector'];

    if($delete == "-1"){

        $sql = "SELECT * FROM mdl_booking_history3 WHERE user_id = $user_id && type = '$type' && state = 2";
        $mail = $DB->get_record_sql($sql);

        if($mail){
            $data = new stdClass();
            $data->id = $mail->id;
            $data->state =  "3";
            $DB->update_record('booking_history3', $data);

        }



    }else{
        $sql = "SELECT * FROM mdl_booking_history3 WHERE user_id = $user_id && type = '$type' && state = 2";
        $mail = $DB->get_record_sql($sql);

        if($mail){
            $data = new stdClass();
            $data->id = $mail->id;
            $today   = new DateTime($_GET['date']);
            $today->modify('+9 hour');
            $data->action_date =  $today->format('Y-m-d H:i:s');
            $DB->update_record('booking_history3', $data);
        }else{

            /* get all data about the operator like wbt */
            $sql = "SELECT * FROM mdl_booking_data3 WHERE order_id = ".$b_id;
            $booking = $DB->get_record_sql($sql);

            /* get all data about the operator like wbt */
            $sql = "SELECT * FROM mdl_ext_operators1 WHERE id = ".$booking->operators_id;
            $operator = $DB->get_record_sql($sql);


            switch ($type) {
                case "mail_co":
                    $subject = "Z-Codes";
                    break;
                case "mail_re":
                    $subject = "Erinnerung";
                    break;
                case "mail_mo":
                    $subject = "Motivation";
                    break;
                case "mail_fi":
                    $subject = "Film verbessern";
                    break;
            }


            $obj = new stdClass();
            $obj->b_id = $b_id;
            $obj->user_id = $user_id;
            $obj->receiver = "";
            $obj->sender = "";
            $obj->type = "$type";
            $obj->subject = $subject;
            $obj->state = 2;
            $today   = new DateTime($_GET['date']);
            $today->modify('+9 hour');
            $obj->action_date =  $today->format('Y-m-d H:i:s');
            $DB->insert_record('booking_history3', $obj);

        }

    }



    header("Location: booking.php?search_id=".$b_id."&id=172&active_tab=2");



}else{


        $user_id = $_GET['user'];
        $type = $_GET['type'];

        $sql = "SELECT * FROM mdl_booking_history3 WHERE user_id = $user_id && type = '$type' && state = 1";
        $mail = $DB->get_record_sql($sql);

        $pieces = explode(" ",  $mail->action_date);
        echo $pieces[0];




}

