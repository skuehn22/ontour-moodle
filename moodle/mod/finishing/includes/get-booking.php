<?php

require(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');

global $DB;

$bookingID = $_POST['b_uid'];

//echo $_POST;

if(isset($_POST['b_uid'] )){


    try {

        $sql = "SELECT *
				FROM {user}
				WHERE phone1 = ".$bookingID;

        $booking = $DB->get_record_sql($sql);

        $sql = "SELECT *
				FROM {booking_data3}
				WHERE order_id = ".$bookingID;
        

        if($booking){

            $info = "<h3 style='font-size: 20px; font-weight: 700'>Buchungsinformationen</h3><p>".$booking->city."<br>".$booking->firstname." ".$booking->lastname."<br>".$booking->email."</p>";
            echo $info;

            $servername = "localhost";
            $username = "skuehn22";
            $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
            $db = "projektreisenWordpress_1637922561";

            // Create connection
            $conn = new mysqli($servername, $username, $password, $db);

            // Check connection
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            //echo "<br>Connected successfully";

            $sql = "SELECT * FROM wp_wc_order_coupon_lookup WHERE order_id = ".$bookingID;
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {

                while($row = $result->fetch_assoc()) {

                    //echo $row["coupon_id"];

                    $sql2 = "SELECT * FROM wp_posts WHERE ID = ".$row["coupon_id"];
                    $result2 = $conn->query($sql2);

                    while($row2 = $result2->fetch_assoc()) {
                        echo "Genutzer Gutschein: ".$row2['post_title'] ;
                    }


                }
            }


        }else{

            echo "Nicht vorhanden";

        }

    }catch (Exception $e) {
        return $e;
    }



}



