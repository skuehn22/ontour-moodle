<?php
require(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/../../lib.php');

global $DB;
global $CFG;

$b_id = $_GET['booking_id'];
$classname = $_GET['school_classes'];
$old_classname = $_GET['old_class_name'];
$user_id = $_GET['user_id'];
$age = $_GET['age'];
$students = $_GET['students'];
$arrival = $_GET['arrival'];
$departure = $_GET['departure'];
$class_note = $_GET['note_teacher'];
$movie = $_GET['movie'];
$user_id_class = $_GET['class_selector_data'];
$movie = $_GET['movie'];
$movie_ready = $_GET['film_sent'];


$sql = "SELECT * FROM mdl_booking_history3 WHERE user_id = $user_id_class && type = 'film_production'";
$mail_mo = $DB->get_record_sql($sql);

if($mail_mo){
    $data = new stdClass();
    $data->id = $mail_mo->id;
    if( $_GET['movie']){
        $data->state = $_GET['movie'];
    }else{
        $data->state = 0;
    }
    $data->type = 'film_production';
    $DB->update_record('booking_history3', $data);
}else{
    $data = new stdClass();
    $data->user_id = $user_id_class;
    $data->b_id = $b_id;

    if( $_GET['movie']){
        $data->state = $_GET['movie'];
    }else{
        $data->state = 0;
    }

    $data->subject = "Film verbessern";
    $data->type = 'film_production';
    $DB->insert_record('booking_history3', $data);
}

$sql = "SELECT * FROM mdl_booking_history3 WHERE user_id = $user_id_class && type = 'film_ready'";
$mail_ready_movie = $DB->get_record_sql($sql);

if($mail_ready_movie){
    $data = new stdClass();
    $data->id = $mail_ready_movie->id;

    if($movie_ready){
        $data->state = 1;
    }else{
        $data->state = 0;
    }

    $data->type = 'film_ready';
    $data->subject = $movie_ready;
    $DB->update_record('booking_history3', $data);

}else{
    $data = new stdClass();
    $data->user_id = $user_id_class;
    $data->b_id = $b_id;
    if($movie_ready){
        $data->state = 1;
    }else{
        $data->state = 0;
    }
    $data->subject = $movie_ready;
    $data->type = 'film_ready';
    $DB->insert_record('booking_history3', $data);
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

$sql = "SELECT * FROM wp_woocommerce_order_items WHERE order_id = '$b_id' && order_item_name = 'Videoprojekt'";
$result = $conn->query($sql);


while($row = $result->fetch_assoc()) {

    $item_id = $row["order_item_id"];

    $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$item_id' && meta_key = 'gruppenname'";
    $result2 = $conn->query($sql);
    $group_name = $result2->fetch_assoc();


    if($group_name['meta_value'] == $old_classname){
        $right_item = $row;
    }

}

//$order_item = $result->fetch_assoc();
$order_item_id = $right_item['order_item_id'];

$sql = "UPDATE wp_woocommerce_order_itemmeta SET meta_value='$classname' WHERE meta_key='gruppenname' && order_item_id = '$order_item_id'";
$conn->query($sql);

$sql = "UPDATE wp_woocommerce_order_itemmeta SET meta_value='$arrival' WHERE meta_key='anreise' && order_item_id = '$order_item_id'";
$conn->query($sql);

$sql = "UPDATE wp_woocommerce_order_itemmeta SET meta_value='$departure' WHERE meta_key='abreise' && order_item_id = '$order_item_id'";
$conn->query($sql);

$sql = "UPDATE wp_woocommerce_order_itemmeta SET meta_value='$age' WHERE meta_key='alter_schuler' && order_item_id = '$order_item_id'";
$conn->query($sql);

$sql = "UPDATE wp_woocommerce_order_itemmeta SET meta_value='$students' WHERE meta_key='anzahl_schuler' && order_item_id = '$order_item_id'";
$conn->query($sql);


$conn->close();



$servername = "localhost";
$username = "skuehn22";
$password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
$dbname = "ontour_moodle_new";

$conn = new mysqli($servername, $username, $password, $dbname);
$sql = "UPDATE mdl_booking_data3 SET classname='$classname' WHERE user_id=$user_id";
$conn->query($sql);

$conn = new mysqli($servername, $username, $password, $dbname);
$sql = "UPDATE mdl_booking_data3 SET class_note='$class_note' WHERE user_id=$user_id";
$conn->query($sql);


$conn = new mysqli($servername, $username, $password, $dbname);
$sql = "UPDATE mdl_booking_data3 SET arrival='$arrival' WHERE user_id=$user_id";
$conn->query($sql);

$conn = new mysqli($servername, $username, $password, $dbname);
$sql = "UPDATE mdl_booking_data3 SET departure='$departure' WHERE user_id=$user_id";
$conn->query($sql);



$conn->close();

header("Location: ../booking.php?search_id=".$b_id."&id=172&active_tab=1");

$msg = "Klasse aktualisiert";
header("Location: ../booking.php?search_id=".$b_id."&id=172&active_tab=1&success=".$msg."&test=".$mail_mo->user_id);