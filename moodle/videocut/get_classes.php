<?php

global $CFG;
require(__DIR__.'/../config.php');
require_once('../config.php');

global $DB;

$bookingID = $_POST['b_uid'];
$groups = [];
$classes = [];

if(isset($_POST['b_uid'] )){


    try {

        $sql = "SELECT *
				FROM {booking_data3}
				WHERE order_id = ".$bookingID;

        $bookings = $DB->get_records_sql($sql);

        if($bookings){

            $select = "<label>Klassen:</label><select class='form-control class-filter selects' id='class-filter' name='class-filter'><option></option>";

            foreach($bookings as $booking){
                $select .= "<option value='".$booking->user_id."'>".$booking->classname."</option>";
            }

            $select .= "</select>";
            echo $select;


        }else{

            echo "Nicht vorhanden";

        }



    }catch (Exception $e) {
        return $e;
    }



}





