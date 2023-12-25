<?php

require(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');


$id = optional_param('id', 0, PARAM_INT);
$b = optional_param('b', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('bookings', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('bookings', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('bookings', array('id' => $b), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('bookings', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);


$PAGE->set_url('/mod/bookings/includes/booking.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

//$PAGE->requires->js('https://reisen.ontour.org/theme/klass/javascript/dataTables.js');

echo $OUTPUT->header();

use DB;
$b_id = 0;

global $CFG;
global $USER;


$bookings = getBookings($b_id);

$table = "<table>";
$table .= "<th>B.Nr.</th>";
$table .= "<th>Veranstalter</th>";
$table .= "<th>V.Nr.</th>";
$table .= "<th>Schule</th>";
$table .= "<th></th>";
$table .= "<th>Klasse</th>";

foreach ($bookings as $b){

    $sql = "SELECT * FROM mdl_ext_operators1 WHERE id = '$b->operators_id'";
    $operator = $DB->get_record_sql($sql);


    $table .= "<tr class='$b->id'>";

        $table .= "<td>'.$b->order_id.'</td>";
        $table .= "<td>'.$operator->name.</td>";
        $table .= "<td>'.$b->ext_booking_id.</td>";
        $table .= "<td>'.$b->school.</td>";
        $table .= "<td>'.$b->classname.</td>";



    $table .= "</tr>";

}
$table .= "<table>";


$templatecontext = [

    'bookings' => $bookings,
    'table' => $table,

];


echo $OUTPUT->render_from_template("bookings/operators", $templatecontext);
echo $OUTPUT->footer();

function getBooking($id){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_data3 WHERE order_id = '$id'";
    $booking = $DB->get_record_sql($sql);

    return $booking;

}


function getBookings($id){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_data3 WHERE order_id = '$id' && mailing < '100'";
    $booking = $DB->get_records_sql($sql);

    return $booking;

}



function getHistory($id){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_history3 WHERE b_id = '$id'  && state = 1";
    $history = $DB->get_records_sql($sql);

    return $history;

}


function getPlannend($id, $user){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_history3 WHERE b_id = '$id'  && state = 2 && user_id = $user";
    $planned = $DB->get_records_sql($sql);

    return $planned;

}



function sendMail(){
    $to      = 'kuehn.sebastian@gmail.com';
    $subject = 'Zugangscodes';
    $message = 'Hallo dies ist ein test';
    $headers = 'From: kontakt@ontour.org' . "\r\n" .
        'Reply-To: kontakt@ontour.org' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();

    mail($to, $subject, $message, $headers);
}