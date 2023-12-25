<?php

require(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');

global $DB;

$bookingID = $_POST['b_uid_webapp'] ;

$groups = [];
$classes = [];

if(isset($_POST['b_uid_webapp'] )){


    try {

        $sql = "SELECT *
				FROM {user}
				WHERE phone1 = ".$bookingID;

        $bookings = $DB->get_records_sql($sql);

        if($bookings){

            foreach($bookings as $booking){
                $sql = "SELECT *
					FROM {groups_members}
					WHERE userid = ".$booking->id;

                $groups_members = $DB->get_records_sql($sql);

                foreach($groups_members as $member){

                    $sql = "SELECT *
					FROM {groups}
					WHERE id = ".$member->groupid;

                    $groups[$member->groupid] = $DB->get_record_sql($sql);

                }

                //$select = "<p><strong>".$booking->city."</strong><br>".$booking->firstname." ".$booking->lastname."<br>".$booking->email."</p>";


                if(count($groups)>1){
                    $select = "<label>Klassen:</label><select class='form-control class-filter' id='class-filter'><option></option>";
                }else{
                    $select = "<select class='form-control class-filter' id='class-filter'>";
                }
                $select .= "<option value=''></option>";
                foreach($groups as $group){

                    $select .= "<option value='".$group->id."'>".$group->name."</option>";

                    //$classes[$group->id] = $group->name;
                }
                $select .= "</select>";

            }




            echo $select;

        }else{

            echo "Nicht vorhanden";

        }



    }catch (Exception $e) {
        return $e;
    }



}



