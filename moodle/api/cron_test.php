<!--
<style>

    body{
        background-color: #000;
        color: #fff;
    }

</style>
-->

<?php
global $CFG;
require(__DIR__.'/../config.php');
require_once('../config.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/pdflib.php');
require_once($CFG->libdir . '/tcpdf/tcpdf_barcodes_2d.php');


class bfpdf extends pdf {
    /**
     * Overriding the footer function in TCPDF.
     */
    public function Footer() {

        $footer_text = '<hr><br><table><tr  style="width: 90%;"> 

                <td style="width: 29%">
                <b>Postanschrift</b><br>
                onTour Media GmbH<br>
                Schönhauser Allee 36 – 39<br>
                10435 Berlin, Kulturbrauerei
                </td>
                
                <td style="width: 34%">
                <b>Bankverbindung</b><br>
                Bank: Ethikbank<br>
                IBAN: DE37 8309 4495 0003 4649 54<br>
                BIC: GENODEF1ETK
                </td>
                
                <td style="width: 25%">
                Scannen Sie den <b>QR-Code</b><br>
                für weitere Informationen
                </td>
                
                <td style="width: 23%">
                <img src="https://ontour.org/wp-content/uploads/qr-code-ontour-startseite.png" style="width: 80px;">
                </td>
                
                
                </tr> 
                </table>';


        $this->SetY(-25);
        $this->SetFont('helvetica', '', 9);
        $this->writeHTML($footer_text, false, false, false, false, '');
    }
}

checkBookings();
//sendCodes();
//calCodeMailDates();
//checkReminder1();
//checkReminder2();
//checkReminder3();
//sendReminder1();
//sendReminder2();
//sendReminder3();
//appOverviewSubmissons();
//transferDates();
//createAppOverview();
//resetSection();
//appOverviewSubmissons();
//sendMotivation();

//getActualBookings();
//deleteOldSections();
//setFilmReady();

//actionOverview();

//copyUserToApp();

//contentSync();

//replacePDFs();

function contentSync(){

    //2341 = 13
    //2584 = 14

    global $DB;

    $sql = 'SELECT * FROM mdl_course_sections WHERE course = 8';
    $content = $DB->get_records_sql($sql);

    foreach ($content as $c){

        //So funktioniert's
        if($c->section == 7 && $c->course == 8){

            $search = "padding-left:6px; min-height:467px;";
            $replace = "padding-left:6px; min-height:467px; display:none";

            $summary = str_replace($search, $replace, $c->summary);


            $data = new stdClass();
            $data->id = 2341;
            $data->summary = $summary;
            $data->name = $c->name;
            $DB->update_record('course_sections', $data);

            $data = new stdClass();
            $data->id = 2584;
            $data->summary = $summary;
            $data->name = $c->name;
            $DB->update_record('course_sections', $data);

        }

        //Vorbereitung der App
        if($c->section == 8 && $c->course == 8){

            $search = "https://reisen.ontour.org/pdfs/Vorbereitung_Klasse_Berlin.pdf";
            $replace = "https://reisen.ontour.org/pdfs/Vorbereitung_Klasse_Hamburg.pdf";

            $summary = str_replace($search, $replace, $c->summary);

            $search = 'class="no-show"';
            $replace = 'class="no-show" style="display:none"';

            $summary = str_replace($search, $replace,$summary);


            $data = new stdClass();
            $data->id = 2342;
            $data->summary = $summary;
            $data->name = $c->name;
            $DB->update_record('course_sections', $data);

            $search = "https://reisen.ontour.org/pdfs/Vorbereitung_Klasse_Hamburg.pdf";
            $replace = "https://reisen.ontour.org/pdfs/Vorbereitung_Klasse_München.pdf";

            $summary = str_replace($search, $replace, $summary);

            $data = new stdClass();
            $data->id = 2585;
            $data->summary = $summary;
            $data->name = $c->name;
            $DB->update_record('course_sections', $data);

        }


        if($c->section == 9 && $c->course == 8){


            $data = new stdClass();
            $data->id = 2343;
            $data->summary = $c->summary;
            $data->name = $c->name;
            $DB->update_record('course_sections', $data);

            $data = new stdClass();
            $data->id = 2586;
            $data->summary = $c->summary;
            $data->name = $c->name;
            $DB->update_record('course_sections', $data);

        }


    }


}

function copyUserToApp($id, $obj, $role){

    $servername = "localhost";
    $username = "skuehn22";
    $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
    $dbname = "webapp";

    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $token = md5(uniqid().rand(1000000, 9999999));

    $sql = "INSERT INTO users (name, email, password, moodle_uid, login_token, role, course) VALUES ('$obj->username', '$obj->email', '$obj->password', '$id', '$token', '$role', 8)";

    ($conn->query($sql));

    return $token;

}


function actionOverview()
{

    global $DB;

    $sql = 'SELECT * FROM mdl_booking_history3 WHERE action_date > "2023-06-28 00:00:00" AND action_date < "2023-07-02 00:00:00" AND state = 2 AND (TYPE != "mail_er3" && TYPE != "mail_re3" && TYPE != "mail_fi" && TYPE != "mail_re2") ';
    $bookings = $DB->get_records_sql($sql);


    echo "<table>";

    echo "<tr>";

    echo "<td style='width: 200px;'><b>Buchungsnummer</b></td>";
    echo "<td style='width: 200px;'><b>Betreff</b></td>";
    echo "<td style='width: 200px;'><b>Actionsdatum</b></td>";
    echo "<td><b>Schulname</b></td>";


    echo "</tr>";

    foreach ($bookings as $booking){

        $register = null;

        $sql = "SELECT * FROM {booking_data3} WHERE state = 0 && order_id = $booking->b_id";
        $check= $DB->get_record_sql($sql);



        if($check){

            if($booking->subject == "Erinnerung 2" || $booking->subject == "Erinnerung 1"){

                $sql = "SELECT * FROM mdl_booking_history3 WHERE TYPE = 'register' && state = 1 && b_id = $booking->b_id";
                $register = $DB->get_record_sql($sql);

                if(!$register){

                    echo "<tr>";

                    echo "<td style='border-bottom: 1px solid #000'>".$booking->b_id."</td>";
                    echo "<td style='border-bottom: 1px solid #000'>".$booking->subject."</td>";
                    // Format the action_date field
                    $formattedDate = date('d.m.Y', strtotime($booking->action_date));
                    echo "<td style='border-bottom: 1px solid #000'>".$formattedDate."</td>";

                    echo "<td style='border-bottom: 1px solid #000'>".$check->school."</td>";
                    echo "<td style='border-bottom: 1px solid #000'>".$check->state."</td>";

                    echo "</tr>";

                }else{
                    echo $register->action_date;
                    echo "<br>";
                }

            }else{

                echo "<tr>";

                echo "<td style='border-bottom: 1px solid #000'>".$booking->b_id."</td>";
                echo "<td style='border-bottom: 1px solid #000'>".$booking->subject."</td>";
                // Format the action_date field
                $formattedDate = date('d.m.Y', strtotime($booking->action_date));
                echo "<td style='border-bottom: 1px solid #000'>".$formattedDate."</td>";

                echo "<td style='border-bottom: 1px solid #000'>".$check->school."</td>";
                echo "<td style='border-bottom: 1px solid #000'>".$check->state."</td>";

                echo "</tr>";

            }



        }

    }

    echo "</table>";

}

function setFilmReady()
{

    global $DB;

    $sql = "SELECT * FROM {booking_data3} WHERE state = 0";
    $bookings = $DB->get_records_sql($sql);

    foreach ($bookings as $booking){

        if($booking->arrival){
            if($booking->arrival != "" && $booking->arrival != "x"){


                $depStr = $booking->departure;
                $dep = new DateTime($depStr);
                $end = $dep->add(new DateInterval('P14D'));
                $now1 = new DateTime();

                if($end < $now1){

                    $sql = "SELECT * FROM mdl_booking_history3 WHERE user_id = $booking->user_id && type = 'film_production'";
                    $mail_ready_movie = $DB->get_record_sql($sql);

                    if($mail_ready_movie && ($mail_ready_movie->state == 1 || $mail_ready_movie->state == 4 || $mail_ready_movie->state == 5 || $mail_ready_movie->state == 6)){


                    }else{

                        if($booking->order_id == 9023){

                        }else{
                            $sql = "SELECT * FROM mdl_booking_history3 WHERE user_id = $booking->user_id && type = 'register' && state = 1";
                            $register = $DB->get_record_sql($sql);

                            if($register){

                                $sql = "SELECT * FROM {groups_members} WHERE userid = ".$booking->user_id;
                                $groupid = $DB->get_record_sql($sql);



                                $sql = "SELECT * FROM {groups_members} WHERE groupid = ".$groupid->groupid;
                                $groups_members = $DB->get_records_sql($sql);




                                $found = false;

                                foreach($groups_members as $member) {

                                    echo $groupid->groupid;

                                    $sql = "SELECT * FROM {assign_submission} WHERE assignment = '49' && userid = ".$member->userid;
                                    $task1 = $DB->get_record_sql($sql);

                                    if($task1){
                                        $found = true;
                                    }

                                }

                                if($found){
                                    $data = new stdClass();
                                    $data->user_id = $booking->user_id;
                                    $data->b_id = $booking->order_id;
                                    $data->state = 4;
                                    $data->subject = "Film automatisch freigegeben";
                                    $data->type = 'film_production';
                                    $DB->insert_record('booking_history3', $data);
                                }else{
                                    $data = new stdClass();
                                    $data->user_id = $booking->user_id;
                                    $data->b_id = $booking->order_id;
                                    $data->state = 6;
                                    $data->subject = "Film-Check: Keine Abgaben";
                                    $data->type = 'film_production';
                                    $DB->insert_record('booking_history3', $data);
                                }



                            }else{
                                $data = new stdClass();
                                $data->user_id = $booking->user_id;
                                $data->b_id = $booking->order_id;
                                $data->state = 5;
                                $data->subject = "Film-Check: Keine Registrierung";
                                $data->type = 'film_production';
                                $DB->insert_record('booking_history3', $data);
                            }
                        }
                    }
                }

            }
        }

    }

}

function getActualBookings()
{

    global $DB;

    $sql = "SELECT * FROM {booking_data3} WHERE state = 0";
    $bookings = $DB->get_records_sql($sql);

    foreach ($bookings as $booking){

        if($booking->arrival){
            if($booking->arrival != "" && $booking->arrival != "x"){
                $arrStr = $booking->arrival;
                $arr = new DateTime($arrStr);

                $depStr = $booking->departure;
                $dep = new DateTime($depStr);

                $now1 = new DateTime();
                $now2 = new DateTime();
                $start = $now1->sub(new DateInterval('P14D'));
                $end = $now2->add(new DateInterval('P14D'));

                /*
                echo $arr->format('Y-m-d');
                echo "<br>";
                echo $dep->format('Y-m-d');
                echo "<br>";
                echo $start->format('Y-m-d');
                echo "<br>";
                echo $end->format('Y-m-d');
                echo "<br>";
                */


                if($arr > $start && $dep < $end){
                    echo $booking->order_id;
                    echo "<br>";
                    echo $booking->arrival;
                    echo "<br>";
                    echo $booking->departure;
                    echo "<br>"; echo "<br>";
                   createAppOverview($booking);
                }

            }
        }

    }

}

function createAppOverview($booking){

    global $DB;

    $sql = "SELECT * FROM mdl_user WHERE phone1 = $booking->order_id";
    $user = $DB->get_record_sql($sql);


        $course = 8;
        $max_section = $DB->get_field_sql('SELECT MAX(section) FROM {course_sections}');
        $max_section = $max_section +1;
        $time = time() + rand();

        // First add section to the end.
        $cw = new stdClass();
        $cw->course   = $course;
        $cw->section  = $max_section;
        $cw->summary  = '<p dir="ltr" style="text-align: left; font-size:14px!important;"><strong>Gruppe 1</strong></p><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 1 Teamname</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important" id="group1_task1"><span style="color: red;">nein</span></div>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 2 Foto</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 3 Film</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 4 Audio</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 5 Safari</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 6 Outtakes</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><p dir="ltr" style="text-align: left; font-size:14px!important;"><strong>Gruppe 2</strong></p><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 1 Teamname</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important" id="group1_task1"><span style="color: red;">nein</span></div>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 2 Foto</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 3 Film</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 4 Audio</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 5 Safari</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 6 Outtakes</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><p dir="ltr" style="text-align: left; font-size:14px!important;"><strong>Gruppe 3</strong></p><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 1 Teamname</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important" id="group1_task1"><span style="color: red;">nein</span></div>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 2 Foto</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 3 Film</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 4 Audio</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 5 Safari</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 6 Outtakes</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><p dir="ltr" style="text-align: left; font-size:14px!important;"><strong>Gruppe 4</strong></p><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 1 Teamname</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important" id="group1_task1"><span style="color: red;">nein</span></div>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 2 Foto</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 3 Film</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 4 Audio</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 5 Safari</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 6 Outtakes</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><p dir="ltr" style="text-align: left; font-size:14px!important;"><strong>Gruppe 5</strong></p><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 1 Teamname</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important" id="group1_task1"><span style="color: red;">nein</span></div>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 2 Foto</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 3 Film</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 4 Audio</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 5 Safari</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 6 Outtakes</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><hr><p dir="ltr" style="text-align: left; font-size:14px!important;"><strong>Lehrkräfte</strong></p><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><p dir="ltr" style="text-align: left; font-size:14px!important;"><strong></strong></p>';
        $cw->summaryformat = FORMAT_HTML;
        $cw->sequence = '';
        $cw->name = 'Übersicht Schüler Abgaben';
        $cw->visible = 1;
        $cw->availability = '{"op":"&","c":[{"type":"profile","sf":"idnumber","op":"isequalto","v":"'.$time.'"}],"showc":[true]}';
        $cw->timemodified = time();
        $cw->id = $DB->insert_record("course_sections", $cw);

        $data = new stdClass();
        $data->id = $user->id;
        $data->middlename = $cw->id;

        $DB->update_record('user', $data);

        $data = new stdClass();
        $data->id = $user->id+6;
        $data->middlename = $cw->id;
        $data->idnumber = $time;
        $DB->update_record('user', $data);






}

function deleteOldSections(){

    global $DB;

    $sql = "SELECT * FROM mdl_user";
    $users = $DB->get_records_sql($sql);

    foreach ($users as $user){

        if($user->middlename != ""){

            $pieces = explode("_", $user->username);

            if($pieces[2] && is_numeric ($pieces[2] )){
                $sql = "SELECT * FROM {booking_data3} WHERE order_id = $pieces[2]";
                $booking = $DB->get_record_sql($sql);

                if($booking){


                    $depStr = $booking->departure;
                    $dep = new DateTime($depStr);
                    $now = new DateTime();

                    if ($now > $dep) {
                        //The departure date/time has passed.
                        $sql = "SELECT * FROM {course_sections} WHERE id = $user->middlename && course = 8";
                        $section = $DB->get_record_sql($sql);

                        if($section->name == "Übersicht Schüler Abgaben"){
                                $table = 'course_sections'; // replace with the name of your table
                                $conditions = array('id' => $section->id); // specify the ID of the record to delete
                                $DB->delete_records($table, $conditions); // delete the record
                        }
                    }

                    if($booking->school == "TESTBUCHUNG"){
                        $sql = "SELECT * FROM {course_sections} WHERE id = $user->middlename && course = 8";
                        $section = $DB->get_record_sql($sql);
                        if($section->name == "Übersicht Schüler Abgaben"){
                            $table = 'course_sections'; // replace with the name of your table
                            $conditions = array('id' => $section->id); // specify the ID of the record to delete
                            $DB->delete_records($table, $conditions); // delete the record
                        }
                    }

                    if($booking->state == 1){
                        $sql = "SELECT * FROM {course_sections} WHERE id = $user->middlename && course = 8";
                        $section = $DB->get_record_sql($sql);
                        if($section->name == "Übersicht Schüler Abgaben"){
                            $table = 'course_sections'; // replace with the name of your table
                            $conditions = array('id' => $section->id); // specify the ID of the record to delete
                            $DB->delete_records($table, $conditions); // delete the record
                        }
                    }

                    if($booking->school == "sdfsfd"){
                        $sql = "SELECT * FROM {course_sections} WHERE id = $user->middlename && course = 8";
                        $section = $DB->get_record_sql($sql);
                        if($section->name == "Übersicht Schüler Abgaben"){
                            $table = 'course_sections'; // replace with the name of your table
                            $conditions = array('id' => $section->id); // specify the ID of the record to delete
                            $DB->delete_records($table, $conditions); // delete the record
                        }
                    }


                }
            }

        }


    }

}


//sendMotivation();

function sendMotivation(){

    global $DB;

    $datetime = new DateTime('tomorrow');
    $date_check = $datetime->format('Y-m-d H:i:s');

    /* get email sender user --> kontakt@ontour.org */
    $sql = "SELECT * FROM mdl_user WHERE id = 6163";
    $from_user = $DB->get_record_sql($sql);

    $sql = "SELECT * FROM {booking_history3} WHERE type = 'mail_mo' && state = 2 && action_date < '$date_check'";
    $sql = "SELECT * FROM {booking_history3} WHERE type = 'mail_mo' && b_id = 6070";

    $mails = $DB->get_records_sql($sql);

    foreach ($mails as $mail){
echo "test";
        $sql = "SELECT * FROM {booking_history3} WHERE type = 'register' && state = 1 && b_id = $mail->b_id";
        $register = $DB->get_record_sql($sql);

        print_r($register);
        
        if($register &&  $mail->action_date < $date_check){
            echo "test2";
        //if($register){
            $sql = "SELECT * FROM {booking_data3} WHERE order_id = '$mail->b_id' && user_id = '$mail->user_id' && state = 0";
            $bookings = $DB->get_records_sql($sql);

            foreach ($bookings as $booking){

                echo "resr ".$mail->b_id;

                $sql = "SELECT * FROM mdl_user WHERE phone1 = $mail->b_id";
                $durchfuerender = $DB->get_record_sql($sql);
                echo $durchfuerender->email;

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

                //echo $booking->operators_id;

                $sql = "SELECT * FROM mdl_ext_operators1 WHERE id = ".$booking->operators_id;
                $op = $DB->get_record_sql($sql);


                $date = new DateTime($booking->arrival);
                $kw = $date->format('W');



                if($kw != ""){
                    $sub_kw = "KW ".$kw;
                }

                /* create greeting */
                if($booking->operators_id == 1){
                    $email_subject = "Videoprojekt Berlin - Buchungs-Nr. ".$booking->order_id;
                }else{

                    if($booking->newsletter == 9){
                        $email_subject = "Videoprojekt Berlin - ".$booking->ext_booking_id. " - ".$booking->order_id." ".$sub_kw;
                    }else{
                        $email_subject = $op->name." Videoprojekt Berlin vorbereitende Hinweise: - Nr. Veranstalter. ".$booking->ext_booking_id." ".$sub_kw;
                    }


                }
                ob_start();

                include "../mod/bookings/includes/emails/motivation_template.php";


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

                $emailuser = "kontakt@ontour.org";

                if($booking->operators_id == 1){
                    email_to_user($emailuser, $from_user, $email_subject, $message);
                }else{
                    email_to_user($emailuser, $from_user, $email_subject, $message);
                }

                $from_user = "kontakt@ontour.org";

                $email_subject = "Kopie MOTIVATION 1 AUTO (".$emailuser->email."): ".$email_subject;

                if ($booking->operators_id == 1) {
                    email_to_user($from_user, $from_user, $email_subject, $message);
                } else {
                    email_to_user($from_user, $from_user, $email_subject, $message);
                }


                //setMotivationMailState($mail->b_id, "1", $booking->user_id);



                
            }
        }

    }
}

/*
global $DB;
$sql = "SELECT * FROM mdl_user WHERE phone1 <> '' && imagealt = 1";
$users = $DB->get_records_sql($sql);

foreach ($users as $user) {

    $data = new stdClass();
    $data->id = $user->id;
    $data->idnumber = 'new';
    $DB->update_record('user', $data);

}
*/

/* get email sender user --> kontakt@ontour.org */



function checkBookings(){
    global $DB;

    $course = 8;
    $sql = "SELECT * FROM {user} WHERE address != ''";
    $users = $DB->get_records_sql($sql);

    if ($users) {
        foreach ($users as $user) { //only return enrolled users.
            if(!empty($user->address)){

                $order_id = $groups_tmp = explode("-" ,$user->phone1);
                $order_id = $order_id[0];

                //new direct bookings have vcodes with them
                if (strpos($user->phone1, '-') !== false) {

                    $pieces = explode("-vc-", $user->phone1);
                    $direct_codes = $pieces[1];
                    $direct_codes = explode(",", $direct_codes);

                    $op_pieces = explode("-", $direct_codes[0]);
                    $operator = $op_pieces[0];
                    $sql = "SELECT mailing FROM mdl_ext_operators1 WHERE short = '$operator'";
                    $mailing = $DB->get_record_sql($sql);

                    $sql = "SELECT id FROM mdl_ext_operators1 WHERE short = '$operator'";
                    $op_id = $DB->get_record_sql($sql);

                }

                $groups_tmp = explode("," ,$user->address);
                $k = 0;
                $z = 0;

                mkdir("/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/".$order_id, 0777);

                foreach ($groups_tmp as $group_tmp){



                    $group_tmp = str_replace(' ', '_', $group_tmp); // Replaces all spaces with hyphens.
                    $remove = array("@", "#", "(", ")", "*", "/", "&");
                    $group_tmp = str_replace($remove, "", $group_tmp);
                    $group_tmp = str_replace(".","_",$group_tmp);

                    if($group_tmp != ""){

                        $users = null;

                        //create groups
                        $obj = new stdClass();
                        $obj->courseid = $course;
                        $obj->name = $order_id."-".$group_tmp;
                        $obj->description = $order_id;
                        $group_id = $DB->insert_record('groups', $obj, true, false);

                        //create v-code user
                        $obj = new stdClass();
                        $obj->auth = "manual";
                        $obj->confirmed = 1;
                        $obj->username =  $direct_codes[$z];
                        $obj->password = password_hash($direct_codes[$z], PASSWORD_DEFAULT);
                        $obj->firstname = $group_tmp;
                        $obj->lastname = " ";
                        $obj->email = $obj->username."@ontour.org";
                        $obj->timecreated = time();
                        $obj->timemodified = time();
                        $obj->institution = "";
                        $obj->mnethostid = 1;
                        $obj->lang = "de";
                        $obj->idnumber = "new";
                        $obj->imagealt = "1";
                        $obj->phone1 = $order_id;
                        $obj->phone2 = $group_tmp;
                        $obj->department = "";
                        $user_v_code = $DB->insert_record('user', $obj, true, false);

                        $servername = "localhost";
                        $username = "skuehn22";
                        $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
                        $dbname = "projektreisenWordpress_1637922561";

                        $conn = new mysqli($servername, $username, $password, $dbname);
                        $sql = "SELECT * FROM wp_postmeta WHERE post_id = '$order_id' && meta_key = 'schulname'";
                        $result = $conn->query($sql);
                        $school = $result->fetch_assoc();

                        $sql = "SELECT * FROM {booking_data3} WHERE order_id = '$order_id'";
                        $booking = $DB->get_record_sql($sql);


                        $obj->classname = $group_tmp;
                        $obj->school = $school['meta_value'];
                        $obj->order_id = $order_id;
                        $obj->operators_id = $op_id->id;
                        $obj->user_id = $user_v_code;
                        $obj->mailing = $mailing->mailing;
                        $obj->newsletter = 1;
                        $insert_id = $DB->insert_record('booking_data3', $obj);

                        //enrol v-code to course
                        $obj = new stdClass();
                        $obj->enrolid = "19";
                        $obj->userid = $user_v_code;
                        $obj->timestart = time();
                        $obj->timecreated = time();
                        $obj->timemodified = time();
                        $obj->modifierid = "2";
                        $obj->timestart = "0";
                        $obj->timeend = "0";
                        $DB->insert_record('user_enrolments', $obj, true, false);

                        //assign v-code  to the created group
                        $obj = new stdClass();
                        $obj->groupid = $group_id;
                        $obj->userid = $user_v_code;
                        $obj->timeadded = time();
                        $DB->insert_record('groups_members', $obj, true, false);
                        //$teacher_id = $userid;


                        //assign teacher to the created group
                        $obj = new stdClass();
                        $obj->groupid = $group_id;
                        $obj->userid = $user->id;
                        $obj->timeadded = time();
                        $group_members_id = $DB->insert_record('groups_members', $obj, true, false);
                        $teacher_id = $user->id;

                        $users_app = [];
                        $pdf_pages = [];

                        //create 5 random users which will be used as students group accounts
                        for ($i = 1; $i <= 6; $i++) {

                            $obj = new stdClass();
                            $obj->auth = "manual";
                            $obj->confirmed = 1;
                            $obj->username = rand(1,999);
                            $obj->username = "g".$i."_".$obj->username."_".$order_id."_".$group_tmp;
                            $obj->password = password_hash($obj->username, PASSWORD_DEFAULT);
                            $obj->firstname = "Gruppe";
                            $obj->lastname = $i."_".$group_tmp;
                            $obj->email = $obj->username."@projektreisen.de";
                            $obj->department = $user->department;
                            $obj->timecreated = time();
                            $obj->timemodified = time();

                            if($i == 6){
                                $obj->institution = "teacher_task";
                            }

                            $obj->mnethostid = 1;
                            $obj->lang = "de";
                            $obj->idnumber = "dev_restriction";
                            $obj->phone2 = "Gruppe_".$i;
                            $obj->department = "student_restriction";
                            $id = $DB->insert_record('user', $obj, true, false);
                            $users_app[] = $id;


                            if($i != 6){

                                $token = copyUserToApp($id, $obj, '1');

                                // Content of the QR Code
                                $text = "https://app.ontour.org/login/$token";

                                // Create a QRcode object
                                $qrcode = new TCPDF2DBarcode($text, 'QRCODE,H');

                                // Get image data as PNG
                                $imagePngData = $qrcode->getBarcodePngData(14, 14);

                                $qr_name = $token.".png";

                                $ontour_bid = $order_id;
                                $school_class_name = $group_tmp;

                                if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid)) {
                                    mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid, 0777, true);
                                }

                                if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid.'/'.$school_class_name)) {
                                    mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid.'/'.$school_class_name, 0777, true);
                                }


                                // Define the path to save the image
                                $imagePath = '/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid.'/'.$school_class_name.'/'.$qr_name;
                                $path_code = $ontour_bid.'/'.$school_class_name.'/'.$qr_name;

                                // Save the image to a file
                                file_put_contents($imagePath, $imagePngData);

                                $pdf_date = date('d-m-Y');
                                $pdf_name = $ontour_bid."-".$school_class_name."-Gruppe".$i."-App-Anmeldedaten.pdf";
                                $pdf_path = "/finishing/".$pdf_name;
                                $content = createPDF($i, $school_class_name, $obj->username, $ontour_bid, $path_code);
                                $pdf_pages[] = $content;
                            }else{
                                $token = copyUserToApp($id, $obj, '2');

                                // Content of the QR Code
                                $text = "https://app.ontour.org/login/$token";

                                // Create a QRcode object
                                $qrcode = new TCPDF2DBarcode($text, 'QRCODE,H');

                                // Get image data as PNG
                                $imagePngData = $qrcode->getBarcodePngData(14, 14);

                                $qr_name = $token.".png";

                                if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid)) {
                                    mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid, 0777, true);
                                }

                                if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid.'/'.$school_class_name)) {
                                    mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid.'/'.$school_class_name, 0777, true);
                                }


                                // Define the path to save the image
                                $imagePath = '/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid.'/'.$school_class_name.'/'.$qr_name;
                                $path_code = $ontour_bid.'/'.$school_class_name.'/'.$qr_name;

                                // Save the image to a file
                                file_put_contents($imagePath, $imagePngData);

                                $pdf_date = date('d-m-Y');
                                $pdf_name =$ontour_bid."-".$school_class_name."-Lehrergruppe-App-Anmeldedaten.pdf";
                                $pdf_path = "/finishing/".$pdf_name;
                                $content = createPDFTeacher($i, $school_class_name, $obj->username, $ontour_bid, $path_code);

                                $pdf_pages[] = $content;

                                //create task overview  page for the app

                            }



                            global $CFG;
                            require_once($CFG->libdir.'/pdflib.php');
                            $pdf = new pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                            $pdf->AddPage();
                            $pdf->writeHTMLCell(0, 0, '', '', $content, 0, 0, 0, true, '', true);
                            ob_clean();


                            if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id)) {
                                mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id, 0777, true);
                            }

                            if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp)) {
                                mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp, 0777, true);
                            }

                            $file = $pdf->Output('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp.'/'.$pdf_name, 'F');

                        }

                        $pdf_name = $order_id."-Alle-Accounts".$group_tmp."-App-Anmeldedaten-".$user->city.".pdf";
                        $pdf = new pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                        foreach ($pdf_pages as $page){

                            $pdf->AddPage();
                            $pdf->writeHTMLCell(0, 0, '', '', $page, 0, 0, 0, true, '', true);

                        }
                        ob_clean();
                        $file = $pdf->Output('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp.'/'.$pdf_name, 'F');

                        foreach ($users_app as $user_app){

                            // assign student to group
                            $obj = new stdClass();
                            $obj->groupid = $group_id;
                            $obj->userid = $user_app;
                            $obj->timeadded = time();
                            $group_members_id = $DB->insert_record('groups_members', $obj, true, false);

                            //enrol user to course
                            $obj = new stdClass();
                            $obj->enrolid = "19";
                            $obj->userid = $user_app;
                            $obj->timestart = time();
                            $obj->timecreated = time();
                            $obj->timemodified = time();
                            $group_members_id = $DB->insert_record('user_enrolments', $obj, true, false);

                            //assign student role
                            $obj = new stdClass();
                            $obj->roleid = "5";
                            $obj->contextid = "233";
                            $obj->userid = $user_app;
                            $obj->modifierid = "2";
                            $obj->timemodified = "1647872831";
                            $group_members_id = $DB->insert_record('role_assignments', $obj, true, false);
                        }


                        //assign the assignments to the new users
                        //find context id -> combines system / course cat / course / and its own Id
                        $sql = "SELECT * FROM {context} WHERE instanceid = :id && contextlevel = 50";
                        $context_course = $DB->get_record_sql($sql, array('id'=>$course));
                        $path = "%".$context_course->path."%";

                        $sql = "SELECT * FROM {context} WHERE path LIKE :path && contextlevel = 70";
                        $context_module = $DB->get_records_sql($sql, array('path'=>$path));

                        foreach ($users_app as $user_app){
                            foreach($context_module as $module){
                                // set role for student
                                $ra = new stdClass();
                                $ra->roleid       = "5";
                                $ra->contextid    = $module->id;
                                $ra->userid       = $user_app;
                                $ra->timemodified = time();
                                $ra->id = $DB->insert_record('role_assignments', $ra);
                            }
                        }

                    }


                    $z++;
                }


                setPlannedTask($operator, $order_id, $user, $insert_id);

                $user_update = new stdClass();
                $user_update->id = $user->id;
                $user_update->address = "";
                $DB->update_record('user', $user_update);

            }
        }
    }
}

function setPlannedTask($operator, $ontour_bid, $user, $insert_id){

    global $USER;
    global $DB;

    $servername = "localhost";
    $username = "skuehn22";
    $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
    $dbname = "projektreisenWordpress_1637922561";

    $conn = new mysqli($servername, $username, $password, $dbname);

    $sql = "SELECT * FROM wp_woocommerce_order_items WHERE order_id = '$ontour_bid' && order_item_name = 'Videoprojekt'";
    $result = $conn->query($sql);

    foreach ($result as $row) {


        $order_item_id = $row["order_item_id"];

        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'anreise'";
        $result = $conn->query($sql);
        $arr = $result->fetch_assoc();

        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'abreise'";
        $result = $conn->query($sql);
        $dep = $result->fetch_assoc();

        echo $arr['meta_value'];

        $germanMonths = [
            'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
            'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'
        ];

        $englishMonths = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        $arr['meta_value'] = str_replace($germanMonths, $englishMonths, $arr['meta_value']);
        $dep['meta_value'] = str_replace($germanMonths, $englishMonths, $dep['meta_value']);

        $arrival_date   = new DateTime($arr['meta_value']);
        $arrival_str = $arrival_date->format('Y-m-d H:i:s');

        $departure_date   = new DateTime($dep['meta_value']);
        echo "<br>";
       echo  $departure_str = $departure_date->format('Y-m-d H:i:s');

    }


    $servername = "localhost";
    $username = "skuehn22";
    $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
    $dbname = "ontour_moodle_new";

    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }


    echo "sfhkd:".$user->id;

    $obj = new stdClass();
    $obj->b_id = $ontour_bid;
    $obj->user_id = $user->id;
    $obj->receiver = "";
    $obj->sender = "";
    $obj->type = "mail_co";
    $obj->subject = "Z-Codes";
    $obj->state = 2;

    $now = new DateTime();
    $now->modify('+1 hour');
    $now = $now->format('Y-m-d H:i:s');
    $obj->action_date =  $now;


    $DB->insert_record('booking_history3', $obj);


    echo "<br>";
    echo $ontour_bid;

    $data = new stdClass();
    $data->id = $insert_id;
    $data->arrival = $arrival_str;
    $data->departure = $departure_str;

    $DB->update_record('booking_data3', $data);


    /* Erinnerung 1 */

/*
    $obj = new stdClass();
    $obj->b_id = $ontour_bid;
    $obj->user_id = $user;
    $obj->receiver = "";
    $obj->sender = "";
    $obj->type = "mail_er1";
    $obj->subject = "Erinnerung 1";
    $obj->state = 2;
    $today   = $arrival_date;
    $today->modify('-'.$mailings->erinnerung_1.' day');
    $today->modify('+9 hour');
    $obj->action_date =  $today->format('Y-m-d H:i:s');
    $DB->insert_record('booking_history3', $obj);
*/

    /* Erinnerung 2 */

/*
    $obj = new stdClass();
    $obj->b_id = $ontour_bid;
    $obj->user_id = $user;
    $obj->receiver = "";
    $obj->sender = "";
    $obj->type = "mail_er2";
    $obj->subject = "Erinnerung 2";
    $obj->state = 2;
    $today   = $arrival_date;
    $today->modify('-'.$mailings->erinnerung_2.' day');
    $today->modify('+9 hour');
    $obj->action_date =  $today->format('Y-m-d H:i:s');
    $DB->insert_record('booking_history3', $obj);
*/

    /* Motivation Mail */
/*

    $obj = new stdClass();
    $obj->b_id = $ontour_bid;
    $obj->user_id = $user;
    $obj->receiver = "";
    $obj->sender = "";
    $obj->type = "mail_mo";
    $obj->subject = "Motivation";
    $obj->state = 2;
    $today   = $arrival_date;
    $today->modify('-'.$mailings->motivation.' day');
    $today->modify('+9 hour');
    $obj->action_date =  $today->format('Y-m-d H:i:s');
    $DB->insert_record('booking_history3', $obj);

*/

    /* Finishing Mail */
/*

    $obj = new stdClass();
    $obj->b_id = $ontour_bid;
    $obj->user_id = $user;
    $obj->receiver = "";
    $obj->sender = "";
    $obj->type = "mail_fi";
    $obj->subject = "Film verbessern";
    $obj->state = 0;
    $today   = $departure_date;
    $today->modify('+'.$operator->mailing_4.' day');
    $today->modify('+9 hour');
    $obj->action_date =  $today->format('Y-m-d H:i:s');
    $DB->insert_record('booking_history3', $obj);

*/

}

function checkBookings2(){
    global $DB;

    $course = 8;
    $sql = "SELECT * FROM {user} WHERE address != ''";
    $users = $DB->get_records_sql($sql);

    if ($users) {
        foreach ($users as $user) { //only return enrolled users.
            if(!empty($user->address)){

                $order_id = $groups_tmp = explode("-" ,$user->phone1);
                $order_id = $order_id[0];

                //new direct bookings have vcodes with them
                if (strpos($user->phone1, '-') !== false) {

                    $pieces = explode("-vc-", $user->phone1);
                    $direct_codes = $pieces[1];
                    $direct_codes = explode(",", $direct_codes);

                    $op_pieces = explode("-", $direct_codes[0]);
                    $operator = $op_pieces[0];
                    $sql = "SELECT mailing FROM mdl_ext_operators1 WHERE short = '$operator'";
                    $mailing = $DB->get_record_sql($sql);

                    $sql = "SELECT id FROM mdl_ext_operators1 WHERE short = '$operator'";
                    $op_id = $DB->get_record_sql($sql);

                }

                $groups_tmp = explode("," ,$user->address);
                $k = 0;
                $z = 0;

                mkdir("/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/".$order_id, 0777);



                foreach ($groups_tmp as $group_tmp){



                    $group_tmp = str_replace(' ', '_', $group_tmp); // Replaces all spaces with hyphens.
                    $remove = array("@", "#", "(", ")", "*", "/", "&");
                    $group_tmp = str_replace($remove, "", $group_tmp);
                    $group_tmp = str_replace(".","_",$group_tmp);

                    if($group_tmp != ""){

                        $users = null;

                        //create groups
                        $obj = new stdClass();
                        $obj->courseid = $course;
                        $obj->name = $order_id."-".$group_tmp;
                        $obj->description = $order_id;
                        $group_id = $DB->insert_record('groups', $obj, true, false);

                        //create v-code user
                        $obj = new stdClass();
                        $obj->auth = "manual";
                        $obj->confirmed = 1;
                        $obj->username =  $direct_codes[$z];
                        $obj->password = password_hash($direct_codes[$z], PASSWORD_DEFAULT);
                        $obj->firstname = $group_tmp;
                        $obj->lastname = " ";
                        $obj->email = $obj->username."@ontour.org";
                        $obj->timecreated = time();
                        $obj->timemodified = time();
                        $obj->institution = "";
                        $obj->mnethostid = 1;
                        $obj->lang = "de";
                        $obj->idnumber = "new";
                        $obj->imagealt = "1";
                        $obj->phone1 = $order_id;
                        $obj->phone2 = $group_tmp;
                        $obj->department = "";
                        $user_v_code = $DB->insert_record('user', $obj, true, false);

                        $servername = "localhost";
                        $username = "skuehn22";
                        $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
                        $dbname = "projektreisenWordpress_1637922561";

                        $conn = new mysqli($servername, $username, $password, $dbname);
                        $sql = "SELECT * FROM wp_postmeta WHERE post_id = '$order_id' && meta_key = 'schulname'";
                        $result = $conn->query($sql);
                        $school = $result->fetch_assoc();

                        $sql = "SELECT * FROM {booking_data3} WHERE order_id = '$order_id'";
                        $booking = $DB->get_record_sql($sql);


                        $obj->classname = $group_tmp;
                        $obj->school = $school['meta_value'];
                        $obj->order_id = $order_id;
                        $obj->operators_id = $op_id->id;
                        $obj->user_id = $user_v_code;
                        $obj->mailing = $mailing->mailing;
                        $obj->newsletter = 1;
                        $DB->insert_record('booking_data3', $obj);

                        //enrol v-code to course
                        $obj = new stdClass();
                        $obj->enrolid = "19";
                        $obj->userid = $user_v_code;
                        $obj->timestart = time();
                        $obj->timecreated = time();
                        $obj->timemodified = time();
                        $obj->modifierid = "2";
                        $obj->timestart = "0";
                        $obj->timeend = "0";
                        $DB->insert_record('user_enrolments', $obj, true, false);

                        //assign v-code  to the created group
                        $obj = new stdClass();
                        $obj->groupid = $group_id;
                        $obj->userid = $user_v_code;
                        $obj->timeadded = time();
                        $DB->insert_record('groups_members', $obj, true, false);
                        //$teacher_id = $userid;


                        //assign teacher to the created group
                        $obj = new stdClass();
                        $obj->groupid = $group_id;
                        $obj->userid = $user->id;
                        $obj->timeadded = time();
                        $group_members_id = $DB->insert_record('groups_members', $obj, true, false);
                        $teacher_id = $user->id;

                        $users_app = [];
                        $pdf_pages = [];

                        //create 5 random users which will be used as students group accounts
                        for ($i = 1; $i <= 6; $i++) {

                            $obj = new stdClass();
                            $obj->auth = "manual";
                            $obj->confirmed = 1;
                            $obj->username = rand(1,999);
                            $obj->username = "g".$i."_".$obj->username."_".$order_id."_".$group_tmp;
                            $obj->password = password_hash($obj->username, PASSWORD_DEFAULT);
                            $obj->firstname = "Gruppe";
                            $obj->lastname = $i."_".$group_tmp;
                            $obj->email = $obj->username."@projektreisen.de";
                            $obj->department = $user->department;
                            $obj->timecreated = time();
                            $obj->timemodified = time();

                            if($i == 6){
                                $obj->institution = "teacher_task";
                            }

                            $obj->mnethostid = 1;
                            $obj->lang = "de";
                            $obj->idnumber = "dev_restriction";
                            $obj->phone2 = "Gruppe_".$i;
                            $obj->department = "student_restriction";
                            $users_app[] = $DB->insert_record('user', $obj, true, false);


                            if($i != 6){

                                //CREATE PDF
                                $pdf_name = $order_id."-Gruppe".$i."-Klasse".$group_tmp."-App-Anmeldedaten-".$user->city.".pdf";

                                if($booking->product == 10){
                                    $content = createPDFCompany($i, $group_tmp, $obj->username,$order_id);
                                }else{
                                    $content = createPDF($i, $group_tmp, $obj->username,$order_id);
                                }

                                $pdf_pages[] = $content;
                            }else{
                                //CREATE PDF
                                $pdf_name = $order_id."-Lehrergruppe-Klasse".$group_tmp."-App-Anmeldedaten-".$user->city.".pdf";

                                if($booking->product == 10){
                                    $content = createPDFLeiter($i, $group_tmp, $obj->username,$order_id);
                                }else{
                                    $content = createPDFTeacher($i, $group_tmp, $obj->username,$order_id);
                                }

                                $pdf_pages[] = $content;
                            }

                            global $CFG;
                            require_once($CFG->libdir.'/pdflib.php');
                            $pdf = new pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                            $pdf->AddPage();
                            $pdf->writeHTMLCell(0, 0, '', '', $content, 0, 0, 0, true, '', true);
                            ob_clean();


                            if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id)) {
                                mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id, 0777, true);
                            }

                            if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp)) {
                                mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp, 0777, true);
                            }

                            $file = $pdf->Output('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp.'/'.$pdf_name, 'F');

                        }

                        $pdf_name = $order_id."-Alle-Accounts".$group_tmp."-App-Anmeldedaten-".$user->city.".pdf";
                        $pdf = new pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                        foreach ($pdf_pages as $page){

                            $pdf->AddPage();
                            $pdf->writeHTMLCell(0, 0, '', '', $page, 0, 0, 0, true, '', true);

                        }
                        ob_clean();
                        $file = $pdf->Output('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp.'/'.$pdf_name, 'F');

                        foreach ($users_app as $user_app){

                            // assign student to group
                            $obj = new stdClass();
                            $obj->groupid = $group_id;
                            $obj->userid = $user_app;
                            $obj->timeadded = time();
                            $group_members_id = $DB->insert_record('groups_members', $obj, true, false);

                            //enrol user to course
                            $obj = new stdClass();
                            $obj->enrolid = "19";
                            $obj->userid = $user_app;
                            $obj->timestart = time();
                            $obj->timecreated = time();
                            $obj->timemodified = time();
                            $group_members_id = $DB->insert_record('user_enrolments', $obj, true, false);

                            //assign student role
                            $obj = new stdClass();
                            $obj->roleid = "5";
                            $obj->contextid = "233";
                            $obj->userid = $user_app;
                            $obj->modifierid = "2";
                            $obj->timemodified = "1647872831";
                            $group_members_id = $DB->insert_record('role_assignments', $obj, true, false);
                        }


                        //assign the assignments to the new users
                        //find context id -> combines system / course cat / course / and its own Id
                        $sql = "SELECT * FROM {context} WHERE instanceid = :id && contextlevel = 50";
                        $context_course = $DB->get_record_sql($sql, array('id'=>$course));
                        $path = "%".$context_course->path."%";

                        $sql = "SELECT * FROM {context} WHERE path LIKE :path && contextlevel = 70";
                        $context_module = $DB->get_records_sql($sql, array('path'=>$path));

                        foreach ($users_app as $user_app){
                            foreach($context_module as $module){
                                // set role for student
                                $ra = new stdClass();
                                $ra->roleid       = "5";
                                $ra->contextid    = $module->id;
                                $ra->userid       = $user_app;
                                $ra->timemodified = time();
                                $ra->id = $DB->insert_record('role_assignments', $ra);
                            }
                        }


                    }


                    $z++;
                }




                $user_update = new stdClass();
                $user_update->id = $user->id;
                $user_update->address = "";
                $DB->update_record('user', $user_update);

            }
        }
    }
}


function fillBookingTable($oid, $operator, $user_id, $mailing){


    $obj = new stdClass();
    $obj->order_id = $oid;
    $obj->operators_id = $operator;
    $obj->user_id = $user_id;
    $obj->mailing = $mailing->mailing;
    $DB->insert_record('bookings', $obj);


}

function createPDFnew($group, $class, $username, $order, $imagePath){

    $html = '
	<style>
	ul {
  list-style: none;
}

li::before {
  content: "• ";
  color: red;
}
	table, tr, td {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11px;
       
	}
	
	p{
	 border-radius: 6px;
	 -webkit-border-top-right-radius: 4px;
  -webkit-border-bottom-right-radius: 4px;
  -webkit-border-bottom-left-radius:6px;
  -webkit-border-top-left-radius: 6px;
  -moz-border-radius-topright: 4px;
  -moz-border-radius-bottomright: 4px;
  -moz-border-radius-bottomleft: 6px;
  -moz-border-radius-topleft: 6px;
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
  border-bottom-left-radius:6px;
  border-top-left-radius:6px;
	}
	
	</style>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff;">
	<tbody>
	<tr>
	<td><img src="/videoproject.png" style="width: 130px;"></td>
	<td align="right">
		
	</td>
	
	</tr>
	</tbody>
	</table>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff; margin-top: -25px;">
	<tbody>
	<tr>
	<td></td>
	<td align="right">
			<strong>Buchungsnummer: '.$order.'</strong>
	</td>
	
	</tr>
	<tr>
	<td colspan="2" style="text-align: center">
	    <h1>Login-Daten / Web-APP</h1>
	    <p style="font-size: 16px;">'.$class.' Gruppe '.$group.'</p>
    </td>
    </tr>
	</tbody>
	</table>
	';


    $html .= '
    <table style="width: 560px;">
    <tbody>
    <tr>
	    <td style="font-size: 16px; width:45%;">
	       <h1>1) Web-App aufrufen</h1>
	    </td>
        <td align="right">
	
        </td>
	</tr>
	 <tr>
	    <td style="font-size: 16px; width:45%;">
	       <span style="font-size: 12px">QR-Code für <b>Web-App</b></span><br>
	    </td>
        <td>
	
        </td>
	</tr>
    <tr>
        <td style="font-size: 16px; width:45%; vertical-align: top;">
            <img src="/filemanager/zugangsdaten/webapp.png" style="width: 150px;"><br>
        </td>
        <td style="vertical-align: top;">
            <table>
                <tr>
                    <td colspan="2">
                        <p><b>ODER</b></p>
                        <p><a href="app.ontour.org" style="color: #009BD5; font-weight: 700"><b>app.ontour.org</b></a> in den Browser eingeben.</p>
                        <p><i>Hinweis: Für die Nutzung ist kein Download im App- oder Playstore notwendig. Die Web-App läuft über jeden Browser.</i></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

	<tr>
        <td colspan="2"><hr></td>
    </tr>
    <tr>
	    <td style="font-size: 16px; width:45%;">
	       <h1>2) Einloggen</h1>
	    </td>
        <td align="right">
	
        </td>
	</tr>
	 <tr>
	    <td style="font-size: 16px; width:45%;">
	       <span style="font-size: 12px">QR-Code für <b>Gruppe '.$group.'</b></span><br>
	    </td>
        <td>
	
        </td>
	</tr>
	<tr>
	    <td style="font-size: 16px; width:45%;">
	        <img src ="/filemanager/zugangsdaten/'.$imagePath.'" style="width: 150px;"><br>
	    </td>
        <td>
               <table>
                   <tr>
                        <td>
                            <p><b>ODER</b></p>
                            <p><strong>Zugangscode</strong></p>
                          
                        </td>
                        <td>
                              <p> </p>
                              <p>'.$username.'</p>
                          
                        </td>
                   </tr>
                 
               </table>
        </td>
	</tr>
	<tr>
	    <td colspan="2" align="right" style="text-align: right;">
	        <br><br><img src="/filemanager/zugangsdaten/logo.png" style="width: 100px;">
        </td>
    </tr>
	</tbody>
	</table>
	';


    return $html;

}

function createPDFTeachernew($group, $class, $username, $order, $imagePath){

    $html = '
	<style>
	ul {
  list-style: none;
}

li::before {
  content: "• ";
  color: red;
}
	table, tr, td {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11px;
       
	}
	
	p{
	 border-radius: 6px;
	 -webkit-border-top-right-radius: 4px;
  -webkit-border-bottom-right-radius: 4px;
  -webkit-border-bottom-left-radius:6px;
  -webkit-border-top-left-radius: 6px;
  -moz-border-radius-topright: 4px;
  -moz-border-radius-bottomright: 4px;
  -moz-border-radius-bottomleft: 6px;
  -moz-border-radius-topleft: 6px;
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
  border-bottom-left-radius:6px;
  border-top-left-radius:6px;
	}
	
	</style>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff;">
	<tbody>
	<tr>
	<td><img src="/videoproject.png" style="width: 130px;"></td>
	<td align="right">
		
	</td>
	
	</tr>
	</tbody>
	</table>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff; margin-top: -25px;">
	<tbody>
	<tr>
	<td></td>
	<td align="right">
			<strong>Buchungsnummer: '.$order.'</strong>
	</td>
	
	</tr>
	<tr>
	<td colspan="2" style="text-align: center">
	    <h1>Login-Daten / Web-APP</h1>
	    <p style="font-size: 16px;">Lehrkräfte</p>
    </td>
    </tr>
	</tbody>
	</table>
	';


    $html .= '
    <table style="width: 560px;">
    <tbody>
    <tr>
	    <td style="font-size: 16px; width:45%;">
	       <h1>1) Web-App aufrufen</h1>
	    </td>
        <td align="right">
	
        </td>
	</tr>
	 <tr>
	    <td style="font-size: 16px; width:45%;">
	       <span style="font-size: 12px">QR-Code für <b>Web-App</b></span><br>
	    </td>
        <td>
	
        </td>
	</tr>
    <tr>
        <td style="font-size: 16px; width:45%; vertical-align: top;">
            <img src="/filemanager/zugangsdaten/webapp.png" style="width: 150px;"><br>
        </td>
        <td style="vertical-align: top;">
            <table>
                <tr>
                    <td colspan="2">
                        <p><b>ODER</b></p>
                        <p><a href="app.ontour.org" style="color: #009BD5; font-weight: 700"><b>app.ontour.org</b></a> in den Browser eingeben.</p>
                        <p><i>Hinweis: Für die Nutzung ist kein Download im App- oder Playstore notwendig. Die Web-App läuft über jeden Browser.</i></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

	<tr>
        <td colspan="2"><hr></td>
    </tr>
    <tr>
	    <td style="font-size: 16px; width:45%;">
	       <h1>2) Einloggen</h1>
	    </td>
        <td align="right">
	
        </td>
	</tr>
	 <tr>
	    <td style="font-size: 16px; width:45%;">
	       <span style="font-size: 12px">QR-Code für die <b>Lehrkräfte</b></span><br>
	    </td>
        <td>
	
        </td>
	</tr>
	<tr>
	    <td style="font-size: 16px; width:45%;">
	        <img src ="/filemanager/zugangsdaten/'.$imagePath.'" style="width: 150px;"><br>
	    </td>
        <td>
               <table>
                   <tr>
                        <td>
                            <p><b>ODER</b></p>
                            <p><strong>Zugangscode</strong></p>
                          
                        </td>
                        <td>
                              <p> </p>
                              <p>'.$username.'</p>
                          
                        </td>
                   </tr>
                 
                 
               </table>
        </td>
	</tr>
	<tr>
	    <td colspan="2" align="right" style="text-align: right;">
	        <br><br><img src="/filemanager/zugangsdaten/logo.png" style="width: 100px;">
        </td>
    </tr>
	</tbody>
	</table>
	';


    return $html;

}


function createPDF($group, $class, $username, $order, $imagePath){

    $html = '
	<style>
	ul {
  list-style: none;
}

li::before {
  content: "• ";
  color: red;
}
	table, tr, td {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11px;
       
	}
	
	p{
	 border-radius: 6px;
	 -webkit-border-top-right-radius: 4px;
  -webkit-border-bottom-right-radius: 4px;
  -webkit-border-bottom-left-radius:6px;
  -webkit-border-top-left-radius: 6px;
  -moz-border-radius-topright: 4px;
  -moz-border-radius-bottomright: 4px;
  -moz-border-radius-bottomleft: 6px;
  -moz-border-radius-topleft: 6px;
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
  border-bottom-left-radius:6px;
  border-top-left-radius:6px;
	}
	
	</style>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff;">
	<tbody>
	<tr>
	<td><img src="/videoproject.png" style="width: 130px;"></td>
	<td align="right">
		
	</td>
	
	</tr>
	</tbody>
	</table>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff; margin-top: -25px;">
	<tbody>
	<tr>
	<td></td>
	<td align="right">
			<strong>Buchungsnummer: '.$order.'</strong>
	</td>
	
	</tr>
	<tr>
	<td colspan="2" style="text-align: center">
	    <h1>Login-Daten / Web-APP</h1>
	    <p style="font-size: 16px;">'.$class.' Gruppe '.$group.'</p>
    </td>
    </tr>
	</tbody>
	</table>
	';


    $html .= '
    <table style="width: 560px;">
    <tbody>
    <tr>
	    <td style="font-size: 16px; width:45%;">
	       <h1>1) Web-App aufrufen</h1>
	    </td>
        <td align="right">
	
        </td>
	</tr>
	 <tr>
	    <td style="font-size: 16px; width:45%;">
	       <span style="font-size: 12px">QR-Code für <b>Web-App</b></span><br>
	    </td>
        <td>
	
        </td>
	</tr>
    <tr>
        <td style="font-size: 16px; width:45%; vertical-align: top;">
            <img src="/filemanager/zugangsdaten/webapp.png" style="width: 150px;"><br>
        </td>
        <td style="vertical-align: top;">
            <table>
                <tr>
                    <td colspan="2">
                        <p><b>ODER</b></p>
                        <p><a href="app.ontour.org" style="color: #009BD5; font-weight: 700"><b>app.ontour.org</b></a> in den Browser eingeben.</p>
                        <p><i>Hinweis: Für die Nutzung ist kein Download im App- oder Playstore notwendig. Die Web-App läuft über jeden Browser.</i></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

	<tr>
        <td colspan="2"><hr></td>
    </tr>
    <tr>
	    <td style="font-size: 16px; width:45%;">
	       <h1>2) Einloggen</h1>
	    </td>
        <td align="right">
	
        </td>
	</tr>
	 <tr>
	    <td style="font-size: 16px; width:45%;">
	       <span style="font-size: 12px">QR-Code für <b>Gruppe '.$group.'</b></span><br>
	    </td>
        <td>
	
        </td>
	</tr>
	<tr>
	    <td style="font-size: 16px; width:45%;">
	        <img src ="/filemanager/zugangsdaten/'.$imagePath.'" style="width: 150px;"><br>
	    </td>
        <td>
               <table>
                   <tr>
                        <td>
                            <p><b>ODER</b></p>
                            <p><strong>Benutzername</strong></p>
                       
                        </td>
                        <td>
                              <p> </p>
                              <p>'.$username.'</p>
                         
                        </td>
                   </tr>
                 
                   
               </table>
        </td>
	</tr>
	<tr>
	    <td colspan="2" align="right" style="text-align: right;">
	        <br><br><img src="/filemanager/zugangsdaten/logo.png" style="width: 100px;">
        </td>
    </tr>
	</tbody>
	</table>
	';


    return $html;

}
function createPDFTeacher($group, $class, $username, $order, $imagePath){

    $html = '
	<style>
	ul {
  list-style: none;
}

li::before {
  content: "• ";
  color: red;
}
	table, tr, td {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11px;
       
	}
	
	p{
	 border-radius: 6px;
	 -webkit-border-top-right-radius: 4px;
  -webkit-border-bottom-right-radius: 4px;
  -webkit-border-bottom-left-radius:6px;
  -webkit-border-top-left-radius: 6px;
  -moz-border-radius-topright: 4px;
  -moz-border-radius-bottomright: 4px;
  -moz-border-radius-bottomleft: 6px;
  -moz-border-radius-topleft: 6px;
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
  border-bottom-left-radius:6px;
  border-top-left-radius:6px;
	}
	
	</style>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff;">
	<tbody>
	<tr>
	<td><img src="/videoproject.png" style="width: 130px;"></td>
	<td align="right">
		
	</td>
	
	</tr>
	</tbody>
	</table>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff; margin-top: -25px;">
	<tbody>
	<tr>
	<td></td>
	<td align="right">
			<strong>Buchungsnummer: '.$order.'</strong>
	</td>
	
	</tr>
	<tr>
	<td colspan="2" style="text-align: center">
	    <h1>Login-Daten / Web-APP</h1>
	    <p style="font-size: 16px;">Lehrkräfte</p>
    </td>
    </tr>
	</tbody>
	</table>
	';


    $html .= '
    <table style="width: 560px;">
    <tbody>
    <tr>
	    <td style="font-size: 16px; width:45%;">
	       <h1>1) Web-App aufrufen</h1>
	    </td>
        <td align="right">
	
        </td>
	</tr>
	 <tr>
	    <td style="font-size: 16px; width:45%;">
	       <span style="font-size: 12px">QR-Code für <b>Web-App</b></span><br>
	    </td>
        <td>
	
        </td>
	</tr>
    <tr>
        <td style="font-size: 16px; width:45%; vertical-align: top;">
            <img src="/filemanager/zugangsdaten/webapp.png" style="width: 150px;"><br>
        </td>
        <td style="vertical-align: top;">
            <table>
                <tr>
                    <td colspan="2">
                        <p><b>ODER</b></p>
                        <p><a href="app.ontour.org" style="color: #009BD5; font-weight: 700"><b>app.ontour.org</b></a> in den Browser eingeben.</p>
                        <p><i>Hinweis: Für die Nutzung ist kein Download im App- oder Playstore notwendig. Die Web-App läuft über jeden Browser.</i></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

	<tr>
        <td colspan="2"><hr></td>
    </tr>
    <tr>
	    <td style="font-size: 16px; width:45%;">
	       <h1>2) Einloggen</h1>
	    </td>
        <td align="right">
	
        </td>
	</tr>
	 <tr>
	    <td style="font-size: 16px; width:45%;">
	       <span style="font-size: 12px">QR-Code für die <b>Lehrkräfte</b></span><br>
	    </td>
        <td>
	
        </td>
	</tr>
	<tr>
	    <td style="font-size: 16px; width:45%;">
	        <img src ="/filemanager/zugangsdaten/'.$imagePath.'" style="width: 150px;"><br>
	    </td>
        <td>
               <table>
                   <tr>
                        <td>
                            <p><b>ODER</b></p>
                            <p><strong>Benutzername</strong></p>
                         
                        </td>
                        <td>
                              <p> </p>
                              <p>'.$username.'</p>
                         
                        </td>
                   </tr>
                 
                   
               </table>
        </td>
	</tr>
	<tr>
	    <td colspan="2" align="right" style="text-align: right;">
	        <br><br><img src="/filemanager/zugangsdaten/logo.png" style="width: 100px;">
        </td>
    </tr>
	</tbody>
	</table>
	';


    return $html;

}

function createPDFCompany($group, $class, $username, $order){

    $html = '
	<style>
	ul {
  list-style: none;
}

li::before {
  content: "• ";
  color: red;
}
	table, tr, td {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11px;
       
	}
	
	p{
	 border-radius: 6px;
	 -webkit-border-top-right-radius: 4px;
  -webkit-border-bottom-right-radius: 4px;
  -webkit-border-bottom-left-radius:6px;
  -webkit-border-top-left-radius: 6px;
  -moz-border-radius-topright: 4px;
  -moz-border-radius-bottomright: 4px;
  -moz-border-radius-bottomleft: 6px;
  -moz-border-radius-topleft: 6px;
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
  border-bottom-left-radius:6px;
  border-top-left-radius:6px;
	}
	
	</style>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff;">
	<tbody>
	<tr>
	<td><img src="https://ontour.org/wp-content/uploads/2021/12/Logo-onTour-1.png" style="width: 100px;"></td>
	<td align="right">
		
	</td>
	
	</tr>
	</tbody>
	</table>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff; margin-top: -25px;">
	<tbody>
	<tr>
	<td></td>
	<td align="right">
			<strong>Buchungsnummer: '.$order.'</strong>
	</td>
	
	</tr>
	</tbody>
	</table>
	';
    $html .= '
	<table style="width: 1200px;">
	<tbody>
	<tr>
	    <td style="font-size: 16px; width:45%;">
	        <br><br><br><strong>Gruppe '.$group.' Team: </strong><span style="color: #49AEDB">'.$class.'</span><br/>
	    </td>
        <td align="right">
	
        </td>
	</tr>
	<tr><td><strong><u>Login für die Teilnehmer*innen</u></strong>
	    <br><br>
	    <table>
             <tr><td style="width: 100px;"><strong>Benutzername:</strong> </td>
                <td> '.$username.'</td>
            </tr>
              <tr> <td><strong>Passwort:</strong> </td>
                <td> '.$username.'</td>
            </tr>
        </table>
	    
	    <br>
	  
	    <p>Benutzername und Passwort sind identisch</p>
	</td>
	<td></td>
    </tr>
    
    <tr>
    <td style="font-size: 16px;"><br><br><strong>App herunterladen</strong></td>
    <td></td>
    </tr>
    <tr>
         <td style="width: 45%">
         ';

    /*
        <p style="border: 1px solid #49AEDB; border-radius: 6px; ">
            <br><br>
                   <span style="color: #49AEDB; padding-top: 15px;"><br>  Achtung:</span><br>
        Aktuell ist die App für das Videoprojekt Berlin leider <strong>nur mit iPhones nutzbar.</strong>
                    <br>  Bitte wendet Euch an Eure Lehrkraft, falls in Eurer Gruppe niemand ein I-Phone hat.

            </p>
    */

    $html .= '
         
          
            <ul style="line-height: 25px; margin-top: -25px;">
                <li>Gehen Sie in den Appstore und gebebn Sie mit I-Phones "onTour Reisen" und mit anderen Handytypen "onTour" ein. Sie müssen etwas scrollen.</li>
                <li>Nach dem Download die App öffnen und die Logindaten eingeben. </li>
            </ul>
            <br>
            <img src="https://reisen.ontour.org/downloads/app_store_1.png" style="width: 220px;">
            <br><br><br><br>
            <strong><u>Bitte beachten Sie</u></strong>
             <ul style="line-height: 25px; margin-top: -30px;">
                <li style="color: red;"><span style="color: #000;">Mindestens eine Person pro Gruppe muss die App in Berlin auf dem Handy heruntergeladen haben.</span></li>
                <li>Sie  brauchen vor Ort für die App etwas Datenvolumen und natürlich einen geladenen Akku.</li>
                <li>Bitte schließen Sie und öffnen Sie die App vor der Nutzung in Berlin noch einmal, damit Sie die aktuelle Version haben. </li>
            </ul>
        </td>
		<td></td>
    </tr>
	</tbody>
	</table>
	';


    return $html;

}


function createPDFLeiter($group, $class, $username, $order){

    $html = '
	<style>
	ul {
  list-style: none;
}

li::before {
  content: "• ";
  color: red;
}
	table, tr, td {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11px;
       
	}
	
	p{
	 border-radius: 6px;
	 -webkit-border-top-right-radius: 4px;
  -webkit-border-bottom-right-radius: 4px;
  -webkit-border-bottom-left-radius:6px;
  -webkit-border-top-left-radius: 6px;
  -moz-border-radius-topright: 4px;
  -moz-border-radius-bottomright: 4px;
  -moz-border-radius-bottomleft: 6px;
  -moz-border-radius-topleft: 6px;
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
  border-bottom-left-radius:6px;
  border-top-left-radius:6px;
	}
	
	</style>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff;">
	<tbody>
	<tr>
	<td><img src="https://ontour.org/wp-content/uploads/2021/12/Logo-onTour-1.png" style="width: 100px;"></td>
	<td align="right">

	</td>
	
	</tr>
	</tbody>
	</table>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff; margin-top: -25px;">
	<tbody>
	<tr>
	<td></td>
	<td align="right">
			<strong>Buchungsnummer: '.$order.'</strong>
	</td>
	
	</tr>
	</tbody>
	</table>
	';
    $html .= '
	<table style="width: 1200px;">
	<tbody>
	<tr>
	    <td style="font-size: 16px;">
	        <br><br><br><strong>Gruppe Spielleiter*innen: </strong><span style="color: #49AEDB">'.$class.'</span><br/>
	    </td>
        <td align="right">
        </td>
	</tr>
	<tr><td><strong><u>Login für Spielleiter*innen</u></strong>
	    <br><br>
	    <table>
            <tr><td style="width: 100px;"><strong>Benutzername:</strong> </td>
                <td> '.$username.'</td>
            </tr>
              <tr> <td><strong>Passwort:</strong> </td>
                <td> '.$username.'</td>
            </tr>
        </table>
	    
	    <br>
	  
	    <p>Benutzername und Passwort sind identisch</p>
	</td>
	<td></td>
    </tr>
    
    <tr>
    <td style="font-size: 16px;"><br><br><strong>App herunterladen</strong></td>
    <td></td>
    </tr>
    <tr>
        <td style="width: 45%">
        ';


    /*
        <p style="border: 1px solid #49AEDB; border-radius: 6px;">
        <br><br>
               <span style="color: #49AEDB; padding-top: 15px;"><br>  Achtung:</span><br>
                Aktuell ist die App für das Videoprojekt Berlin leider <strong>nur mit iPhones nutzbar.</strong>
                <br>  Sollte eine Gruppe kein iPhone haben, schreiben Sie uns bitte bis spätestens 3 Tage vor Durchführung <br>  in Berlin eine E-Mail: <a href="mailto:info@ontour.org">info@ontour.org</a> Wir schicken Ihnen dann eine Alternativvariante zu.

        </p>

    */

    $html .= '
            <ul style="line-height: 17px; margin-top: -25px;">
                <li>Gehen Sie in den Appstore und geben mit I-Phones "onTour Reisen" und mit anderen Handytypen "onTour" ein. Sie müssen etwas scrollen.</li>
                <li>Nach dem Download die App öffnen und die <strong>Logindaten</strong> eingeben. </li>
            </ul>
            <br>
            <img src="https://reisen.ontour.org/downloads/app_store_1.png" style="width: 220px;">
            <br><br><br><br>
            <strong><u>Bitte achten Sie als Lehrkraft darauf</u></strong>
             <ul style="line-height: 17px; margin-top: -30px;">
                <li style="color: red;"><span style="color: #000;">Mindestens ein/e Schüler*in pro Gruppe muss die App in Berlin auf dem Handy heruntergeladen haben.</span></li>
                <li>Es wird etwas Datenvolumen und ein geladener Akku vor Ort benötigt.</li>
                <li>Die App muss vor Durchführung geschlossen und wieder geöffnet werden, damit die aktuelle Version genutzt wird.</li>
                <li>In Berlin wird das Videoprojekt Gruppenweise eigenständig durchgeführt. Die Gruppenbildung und Wahl des Drehortes erfolgen unter Ihrer Anleitung. </li>
            </ul>
        </td>
        <td></td>
    </tr>
	</tbody>
	</table>
	';


    return $html;

}

function sendCodes(){

    global $DB;

    $datetime = new DateTime('tomorrow');
    $date_check = $datetime->format('Y-m-d H:i:s');

    /* get email sender user --> kontakt@ontour.org */
    $sql = "SELECT * FROM mdl_user WHERE id = 6163";
    $from_user = $DB->get_record_sql($sql);

    $sql = "SELECT * FROM {booking_history3} WHERE type = 'mail_co' && state = 2 && action_date < '$date_check'";
    $mails = $DB->get_records_sql($sql);

    foreach ($mails as $mail){

        $is_there_an_active_class = 1;

        $sql = "SELECT * FROM {booking_data3} WHERE order_id = '$mail->b_id'";
        $bookings = $DB->get_records_sql($sql);

        foreach ($bookings as $booking){

            if($booking->state == 0){
                $is_there_an_active_class = 0;
            }

        }

        if($is_there_an_active_class == 0) {

            $sql = "SELECT * FROM mdl_user WHERE phone1 = '$mail->b_id'";
            $buchender = $DB->get_record_sql($sql);


            /* generate z-codes table for mail text */
            $z_codes_string = get_z_codes($bookings);

            /* get all booking data from wordpress */
            $data = getData($mail->b_id);

            //check if there is an email address for "Buchender"
            if($data[0]['email'] != ""){

                foreach ($bookings as $booking) {
                    $tmp = $booking->operators_id;
                }

                /* create greeting */
                if ($booking->operators_id == 1) {
                    $anrede = "Hallo " . $data[0]['firstname'] . " " . $data[0]['lastname'];
                } else {

                    if ($booking->newsletter == 9) {
                        $anrede = "Hallo " . $data[0]['firstname'] . " " . $data[0]['lastname'];
                    } else {
                        $anrede = "Liebe Lehrkraft";
                    }
                }

                //echo $booking->operators_id;

                $sql = "SELECT * FROM mdl_ext_operators1 WHERE id = " . $booking->operators_id;
                $op = $DB->get_record_sql($sql);


                $invoice_number = getInvoiceNumber($DB) + 1100;

                $pdf_date = date('d-m-Y');
                $pdf_name = "Rechnung-" . $invoice_number . ".pdf";

                $total = 480;

                $content = createInvoicePDF($invoice_number, $data, $total, $mail->b_id, $booking);

                if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/invoices/' . $mail->b_id)) {
                    mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/invoices/' . $mail->b_id, 0777, true);
                }


                $pdf = new bfpdf('P', 'mm', 'A4', true, 'utf-8');
                $pdf->SetFont('helvetica', '', 9);
                $pdf->AddPage();
                $pdf->writeHTMLCell(0, 0, '', '', $content, 0, 0, 0, true, '', true);
                $pdf->setFooterMargin(10);

                $file = $pdf->Output('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/invoices/' . $mail->b_id . '/' . $pdf_name, 'F');
                $path_to_file = '/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/invoices/' . $mail->b_id . '/' . $pdf_name;

                saveInvoice($DB, $mail->b_id, "8", $booking->user_id);

                /* create greeting */
                if ($booking->operators_id == 1) {
                    $email_subject = "Videoprojekt Berlin - Buchungs-Nr. " . $mail->b_id;
                } else {

                    if ($booking->newsletter == 9) {
                        $email_subject = "Videoprojekt Berlin - " . $booking->ext_booking_id . " - " . $mail->b_id;
                    } else {
                        $email_subject = $op->name . " Videoprojekt Berlin - Nr. Veranstalter. " . $booking->ext_booking_id;
                    }


                }


                ob_start();

                //if the buchender = veranstalter // that check box when you create a booking
                //send a mail without style to the veranstalter
                if ($booking->newsletter == 9) {
                    include "../../mod/bookings/includes/emails/codes_template_no_style.php";
                } else {
                    include "../../mod/bookings/includes/emails/codes_template.php";
                }

                $message = ob_get_clean();


                set_config('allowedemaildomains', 'ontour.org');
                set_config('emailheaders', 'X-Fixed-Header: bar');


                //if the buchender = veranstalter // that check box when you create a booking
                if ($op->mailing_traveler != 0 || $booking->newsletter == 9) {

                    $emailuser = new stdClass();
                    $emailuser->email = $data[0]['email'];
                    $emailuser->firstname = $data[0]['firstname'];
                    $emailuser->lastname = $data[0]['lastname'];

                } else {

                    $emailuser = new stdClass();
                    $emailuser->email = $op->mail;
                    $emailuser->firstname = $op->name;
                    $emailuser->lastname = "";

                }

                $emailuser->maildisplay = true;
                $emailuser->mailformat = 1;
                $emailuser->id = -99;
                $emailuser->firstnamephonetic = "";
                $emailuser->lastnamephonetic = "";
                $emailuser->middlename = "";
                $emailuser->alternatename = "";


                if ($booking->operators_id == 1) {
                    email_to_user($emailuser, $from_user, $email_subject, $message, '', $path_to_file, $pdf_name, '', 'kontakt@ontour.org');
                } else {
                    email_to_user($emailuser, $from_user, $email_subject, $message,'', '','','','kontakt@ontour.org');
                }


                $email_subject = "Kopie (".$emailuser->email."): ".$email_subject;

                if ($booking->operators_id == 1) {
                    email_to_user($from_user, $from_user, $email_subject, $message, '', $path_to_file, $pdf_name);
                } else {
                    email_to_user($from_user, $from_user, $email_subject, $message);
                }


                setCodeMailState($mail->b_id, "1", $booking->user_id);

            }
        }
    }

}

function getInvoiceNumber($DB){
    $sql = "SELECT * FROM {finishing_invoices}  WHERE state = 1";
    $invoices = $DB->get_records_sql($sql);
    return count($invoices)+1;
}

function saveInvoice($DB, $name, $course, $user){

    $obj = new stdClass();
    $obj->number = $name;
    $obj->fk_course = $course;
    $obj->fk_user = $user;
    $obj->state = 1;


    $DB->insert_record('finishing_invoices', $obj, true, false);

    return true;

}

function createInvoicePDF($in, $data, $total, $b_id, $booking){

    $html = '
	<style>
	table, tr, td {
	padding: 15px;
	}
	</style>
	<table style="background-color: #fff; color: #000">
	<tbody>
	<tr>
	    <td colspan="2" style="text-align: right;"><img src="https://reisen.ontour.org/pix/theme/onTour.png"></td>
    </tr>
    <tr>
	   <td colspan="2">onTour Media GmbH | Schönhauser Allee 36 – 39 | 10435 Berlin</td>
    </tr>
	<tr>
	<td>'.$booking->school.'<br>'.$data[0]['firstname'].' '.$data[0]['lastname'].'<br>'.$data[0]['addr'].'<br>'.$data[0]['zip'].' '.$data[0]['city'].'
	
    </td>
	<td>

	    <table style="padding: 0px!important;">
	    <tr style="padding: 0px!important;">
	        <td style="padding: 0px!important;">Rechnungsdatum: </td>
	        <td style="padding: 0px!important; text-align: right;">'.date('d.m.y').'</td>
	    </tr> 
	    <tr style="padding: 0px!important;">   
	        <td style="padding: 0px!important;">Rechnungsnummer: </td>
	        <td style="padding: 0px!important; text-align: right;">'.$in.'</td>
	    </tr>
	    <tr style="padding: 0px!important;">    
	        <td style="padding: 0px!important;">Bestellnummer:</td>
	        <td style="padding: 0px!important; text-align: right;"> '.$b_id.'</td>
	    </tr>
	    <tr style="padding: 0px!important;">
	        <td style="padding: 0px!important;">Zahlungsart: </td>
	        <td style="padding: 0px!important; text-align: right;">Rechnung</td>
        </tr>
        </table>
	
	</td>
	
	</tr>
	</tbody>
	</table>
	';
    $html .= '
	<table style="padding: 5px;  padding-left: 15px;">
	<tbody>
	<tr>
	<td colspan="2">
	    <span style="font-weight: 700; font-size: 18px;">RECHNUNG</span>
	</td>
	</tr>
	</tbody>
	</table>
	';
    $html .= '
	<table>
	<tbody>
	<tr>
	<td colspan="2">
	    <p>
	      Wir freuen uns, dass Sie das Videoprojekt gebucht haben.<br>
          Folgende Leistung berechnen wir Ihnen.
        </p>
	</td>
	</tr>
	</tbody>
	</table>
	';
    $html .= '
	<table style="padding: 5px; padding-left: 15px;">
	<thead>
	<tr>
	<th style="font-weight:bold; border-bottom: 1px solid #000; border-top: 1px solid #000;padding-top: 10px;padding-bottom: 10px;">Bezeichnung</th>
	<th  style="font-weight:bold; border-bottom: 1px solid #000; border-top: 1px solid #000; text-align: right;">Gesamt</th>
	</tr>
	</thead>
	<tbody>';

    $i = 0;
    foreach ($data as $d){
        $html .='
                <tr>
                    <td><strong>Videoprojekt Berlin</strong></td>
                    <td style="text-align: right;padding-top: 10px;padding-bottom: 10px!important;">'.$data[$i]['item_price'].' €</td>
                </tr>
                <tr>
                    <td>Leistungszeitraum</td>
                    <td style="text-align: right;">'.$data[$i]['arr'].' '.$data[$i]['dep'].'</td>
                </tr>
                 <tr>
                    <td>Durchführung und Erinnerungsfilm</td>
                    <td style="text-align: right;"></td>
                </tr>
                 <tr>
                    <td>Klassenname: '.$data[$i]['gruppe'].'</td>
                    <td style="text-align: right;"></td>
                </tr>
                ';

        $i++;
    }



    $html .='
        <tr style="text-align: right;">
            <td>
         
            </td>
            <td>
              <table style="padding: 0px!important;">
                    <tr style="padding: 0px!important;">
                        <td style="padding: 0px!important;"><strong>Summe Netto</strong></td>
                        <td style="padding: 0px!important; text-align: right;">'.$data[0]['line_total'].'</td>
                    </tr> 
                    <tr style="padding: 0px!important;">   
                        <td style="padding: 0px!important;"><strong>19 % MwSt.</strong></td>
                        <td style="padding: 0px!important; text-align: right;">'.$data[0]['tax'].'</td>
                    </tr>
                    <tr style="padding: 0px!important;">    
                        <td style="padding: 0px!important;border-bottom: 1px solid #000; border-top: 1px solid #000;"><strong>Rechnungsbetrag</strong></td>
                        <td style="padding: 0px!important; text-align: right;border-bottom: 1px solid #000; border-top: 1px solid #000;"><strong> '.$data[0]['total'].'</strong></td>
                    </tr>
                
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2"><br><br>Bitte überweisen Sie den Rechnungsbetrag umgehend, mit der Angabe Ihrer RechnungsNr., auf das unten aufgeführte Konto.<br><br>Mit freundlichen Grüßen und bis zum nächsten Mal<br><img src="https://ontour.org/wp-content/uploads/Unterschrit-Matthias-Enter-onTour.png" style="width: 140px;"><br>Matthias Enter
            </td>
        </tr>
	</tbody>
	</table>
	';

    return $html;

}

function setCodeMailState($b_id, $state, $user_id){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_history3 WHERE b_id = '$b_id' && type = 'mail_co' && state = 2";
    $mail_mo = $DB->get_record_sql($sql);

    $my_date = date("Y-m-d H:i:s");

    $data = new stdClass();
    $data->id = $mail_mo->id;
    $data->state = $state;
    $data->action_date = $my_date;
    $DB->update_record('booking_history3', $data);


    /*
    if($mail_mo){

        $data = new stdClass();
        $data->id = $mail_mo->id;
        $data->state = $state;
        $DB->update_record('booking_history3', $data);

    }else{

        $obj = new stdClass();
        $obj->b_id = $b_id;
        $obj->user_id = $user_id;
        $obj->receiver = "";
        $obj->sender = "";
        $obj->type = "mail_co";
        $obj->subject = "Codes geschickt";
        $obj->state = $state;
        $DB->insert_record('booking_history3', $obj);

    }
    */


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

function checkReminder2(){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_data3 WHERE state = 0 && reg_date IS NULL";
    $bookings = $DB->get_records_sql($sql);

    foreach ($bookings as $booking){

        $sql = "SELECT * FROM mdl_booking_mails WHERE short_code = ".$booking->operators_id;
        $mailings = $DB->get_record_sql($sql);

        $sql = "SELECT DISTINCT b_id FROM {booking_history3} WHERE type = 'mail_re2' && state = 2 && b_id = '$booking->order_id'";
        $checker = $DB->get_record_sql($sql);

        if(!$checker){
            $sql = "SELECT DISTINCT b_id FROM {booking_history3} WHERE type = 'mail_re2' && state = 1 && b_id = '$booking->order_id'";
            $checker = $DB->get_record_sql($sql);
        }

        if(!$checker){

            $servername = "localhost";
            $username = "skuehn22";
            $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
            $dbname = "projektreisenWordpress_1637922561";

            $conn = new mysqli($servername, $username, $password, $dbname);


            $sql = "SELECT * FROM wp_woocommerce_order_items WHERE order_id = '$booking->order_id' && order_item_name = 'Videoprojekt'";
            $result = $conn->query($sql);


            while($row = $result->fetch_assoc()) {

                $item_id = $row["order_item_id"];

                $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$item_id' && meta_key = 'gruppenname'";
                $result2 = $conn->query($sql);
                $group_name = $result2->fetch_assoc();

                if($group_name['meta_value'] == $booking->classname){
                    $right_item = $row;
                }

            }

            //$order_item = $result->fetch_assoc();
            $order_item_id = $right_item['order_item_id'];

            $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'anreise'";
            $result = $conn->query($sql);
            $arr = $result->fetch_assoc();



            if(strpos($arr['meta_value'], "-") !== false){

                $arrival_date   = new DateTime($arr['meta_value']);
                $arrival_date = $arrival_date->format('Y-m-d H:i:s');
                $arrival_date = new DateTime($arrival_date);

                $now = new DateTime();
                $now->modify('+1 hour');


                $days_till_arrival = $now->diff($arrival_date)->format("%r%a");


                //echo $booking->order_id."-".$arr['meta_value']."-".$days_till_arrival."<br>";

                if($days_till_arrival > 17){

                    /* Reminder 1 Mail */
                    $obj = new stdClass();
                    $obj->b_id = $booking->order_id;
                    $obj->user_id = $booking->user_id;
                    $obj->receiver = "";
                    $obj->sender = "";
                    $obj->type = "mail_re2";
                    $obj->subject = "Reminder 2";
                    $obj->state = 2;

                    $arrival_date->modify('-'.$mailings->erinnerung_2.' day');
                    $arrival_date->modify('+9 hour');
                    $arrival_date = $arrival_date->format('Y-m-d H:i:s');
                    $obj->action_date = $arrival_date;
                    $DB->insert_record('booking_history3', $obj);

                }

            }

        }


    }

}

function checkReminder1(){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_data3 WHERE state = 0 && reg_date IS NULL";
    $bookings = $DB->get_records_sql($sql);

    foreach ($bookings as $booking){

        $sql = "SELECT * FROM mdl_booking_mails WHERE short_code = ".$booking->operators_id;
        $mailings = $DB->get_record_sql($sql);

        $sql = "SELECT DISTINCT b_id FROM {booking_history3} WHERE type = 'mail_re1' && state = 2 && b_id = '$booking->order_id'";
        $checker = $DB->get_record_sql($sql);

        if(!$checker){
            $sql = "SELECT DISTINCT b_id FROM {booking_history3} WHERE type = 'mail_re1' && state = 1 && b_id = '$booking->order_id'";
            $checker = $DB->get_record_sql($sql);
        }

        if(!$checker){

            $servername = "localhost";
            $username = "skuehn22";
            $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
            $dbname = "projektreisenWordpress_1637922561";

            $conn = new mysqli($servername, $username, $password, $dbname);


            $sql = "SELECT * FROM wp_woocommerce_order_items WHERE order_id = '$booking->order_id' && order_item_name = 'Videoprojekt'";
            $result = $conn->query($sql);


            while($row = $result->fetch_assoc()) {

                $item_id = $row["order_item_id"];

                $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$item_id' && meta_key = 'gruppenname'";
                $result2 = $conn->query($sql);
                $group_name = $result2->fetch_assoc();

                if($group_name['meta_value'] == $booking->classname){
                    $right_item = $row;
                }

            }

            //$order_item = $result->fetch_assoc();
            $order_item_id = $right_item['order_item_id'];

            $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'anreise'";
            $result = $conn->query($sql);
            $arr = $result->fetch_assoc();



            if(strpos($arr['meta_value'], "-") !== false){

                $arrival_date   = new DateTime($arr['meta_value']);
                $arrival_date = $arrival_date->format('Y-m-d H:i:s');
                $arrival_date = new DateTime($arrival_date);

                $now = new DateTime();
                $now->modify('+1 hour');


                $days_till_arrival = $now->diff($arrival_date)->format("%r%a");


                //echo $booking->order_id."-".$arr['meta_value']."-".$days_till_arrival."<br>";

                if($days_till_arrival > 28){

                    /* Reminder 1 Mail */
                    $obj = new stdClass();
                    $obj->b_id = $booking->order_id;
                    $obj->user_id = $booking->user_id;
                    $obj->receiver = "";
                    $obj->sender = "";
                    $obj->type = "mail_re1";
                    $obj->subject = "Reminder 1";
                    $obj->state = 2;

                    $arrival_date->modify('-'.$mailings->erinnerung_1.' day');
                    $arrival_date->modify('+9 hour');
                    $arrival_date = $arrival_date->format('Y-m-d H:i:s');
                    $obj->action_date = $arrival_date;
                    $DB->insert_record('booking_history3', $obj);

                }

            }

        }


    }

}
function checkReminder3(){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_data3 WHERE state = 0 && reg_date IS NULL";
    $bookings = $DB->get_records_sql($sql);

    foreach ($bookings as $booking){

        $sql = "SELECT * FROM mdl_booking_mails WHERE short_code = ".$booking->operators_id;
        $mailings = $DB->get_record_sql($sql);

        $sql = "SELECT DISTINCT b_id FROM {booking_history3} WHERE type = 'mail_re3' && state = 2 && b_id = '$booking->order_id'";
        $checker = $DB->get_record_sql($sql);

        if(!$checker){
            $sql = "SELECT DISTINCT b_id FROM {booking_history3} WHERE type = 'mail_re3' && state = 1 && b_id = '$booking->order_id'";
            $checker = $DB->get_record_sql($sql);
        }

        if(!$checker){

            $servername = "localhost";
            $username = "skuehn22";
            $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
            $dbname = "projektreisenWordpress_1637922561";

            $conn = new mysqli($servername, $username, $password, $dbname);


            $sql = "SELECT * FROM wp_woocommerce_order_items WHERE order_id = '$booking->order_id' && order_item_name = 'Videoprojekt'";
            $result = $conn->query($sql);


            while($row = $result->fetch_assoc()) {

                $item_id = $row["order_item_id"];

                $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$item_id' && meta_key = 'gruppenname'";
                $result2 = $conn->query($sql);
                $group_name = $result2->fetch_assoc();

                if($group_name['meta_value'] == $booking->classname){
                    $right_item = $row;
                }

            }

            //$order_item = $result->fetch_assoc();
            $order_item_id = $right_item['order_item_id'];

            $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'anreise'";
            $result = $conn->query($sql);
            $arr = $result->fetch_assoc();



            if(strpos($arr['meta_value'], "-") !== false){

                $arrival_date   = new DateTime($arr['meta_value']);
                $arrival_date = $arrival_date->format('Y-m-d H:i:s');
                $arrival_date = new DateTime($arrival_date);

                $now = new DateTime();
                $now->modify('+1 hour');


                $days_till_arrival = $now->diff($arrival_date)->format("%r%a");


                //echo $booking->order_id."-".$arr['meta_value']."-".$days_till_arrival."<br>";

                if($days_till_arrival > 14){

                    /* Reminder 1 Mail */
                    $obj = new stdClass();
                    $obj->b_id = $booking->order_id;
                    $obj->user_id = $booking->user_id;
                    $obj->receiver = "";
                    $obj->sender = "";
                    $obj->type = "mail_re3";
                    $obj->subject = "Reminder 3";
                    $obj->state = 2;

                    $arrival_date->modify('- 14 day');
                    $arrival_date->modify('+9 hour');
                    $arrival_date = $arrival_date->format('Y-m-d H:i:s');
                    $obj->action_date = $arrival_date;
                    $DB->insert_record('booking_history3', $obj);

                }

            }

        }


    }

}

function sendReminder1(){

    global $DB;

    $datetime = new DateTime('tomorrow');
    $datetime->add(new DateInterval('P11D'));
    $date_check = $datetime->format('Y-m-d H:i:s');

    /* get email sender user --> kontakt@ontour.org */
    $sql = "SELECT * FROM mdl_user WHERE id = 6163";
    $from_user = $DB->get_record_sql($sql);

    $sql = "SELECT * FROM {booking_history3} WHERE (type = 'mail_er1' || type = 'mail_re1') && state = 2 && action_date < '$date_check'";
    $mails = $DB->get_records_sql($sql);

    foreach ($mails as $mail){

        if($mail->action_date < $date_check){

            //echo "test1";

            $is_there_an_active_class = 1;
            //echo $mail->b_id;
            $sql = "SELECT * FROM {booking_data3} WHERE order_id = '$mail->b_id'";
            $bookings = $DB->get_records_sql($sql);

            foreach ($bookings as $booking){
                //echo $booking->state;
                if($booking->state == 0){
                    $is_there_an_active_class = 0;
                }

            }

            if($is_there_an_active_class == 0){

                echo "Morgen: ".$date_check;
                echo "<br>";
                echo "Geplant: ".$mail->action_date;
                echo "<br>";
                echo $mail->b_id;
                echo "<br>";
                echo "<br>";

                $sql = "SELECT * FROM mdl_user WHERE phone1 = '$mail->b_id'";
                $buchender = $DB->get_record_sql($sql);

                /* generate z-codes table for mail text */
                $z_codes_string = get_z_codes($bookings);

                /* get all booking data from wordpress */
                $data = getData($mail->b_id);

                foreach ($bookings as $booking){
                    $tmp = $booking->operators_id;
                }

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

                $sql = "SELECT * FROM mdl_ext_operators1 WHERE id = ".$booking->operators_id;
                $op = $DB->get_record_sql($sql);


                $date = new DateTime($booking->arrival);
                $kw = $date->format('W');

                if($kw != ""){
                    $sub_kw = "KW ".$kw;
                }

                /* create greeting */
                if($booking->operators_id == 1){
                    $email_subject = "Erinnerung Videoprojekt Berlin - Buchungs-Nr. ".$mail->b_id;
                }else{
                    if($booking->newsletter == 9){
                        $email_subject = "Erinnerung Videoprojekt Berlin - ".$booking->ext_booking_id. " - ".$mail->b_id." ".$sub_kw;
                    }else{
                        $email_subject = $op->name." Erinnerung Videoprojekt Berlin - Nr. Veranstalter. ".$booking->ext_booking_id." ".$sub_kw;
                    }
                }

                ob_start();

                if($booking->newsletter == 9){
                    include "../mod/bookings/includes/emails/reminder1_template_no_style.php";
                }else{
                    include "../mod/bookings/includes/emails/reminder1_template.php";
                }

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
                    $emailuser->email = $buchender->email;
                    $emailuser->firstname = $data[0]['firstname'];
                    $emailuser->lastname = $data[0]['lastname'];

                }

                $emailuser->maildisplay = true;
                $emailuser->mailformat = 1;
                $emailuser->id = -99;
                $emailuser->firstnamephonetic = "";
                $emailuser->lastnamephonetic = "";
                $emailuser->middlename = "";
                $emailuser->alternatename = "";


                if($booking->operators_id == 1){
                  //  email_to_user($emailuser, $from_user, $email_subject, $message);
                }else{
                  //  email_to_user($emailuser, $from_user, $email_subject, $message);
                }

                $email_subject = "Kopie REMINDER 1 AUTO (".$emailuser->email."): ".$email_subject;

                if ($booking->operators_id == 1) {
                 //   email_to_user($from_user, $from_user, $email_subject, $message);
                } else {
                  //  email_to_user($from_user, $from_user, $email_subject, $message);
                }


                //setReminder1MailState($mail->b_id, "1", $booking->user_id);


            }


        }

    }

}

function sendReminder2(){

    global $DB;

    $datetime = new DateTime('tomorrow');
    $date_check = $datetime->format('Y-m-d H:i:s');

    /* get email sender user --> kontakt@ontour.org */
    $sql = "SELECT * FROM mdl_user WHERE id = 6163";
    $from_user = $DB->get_record_sql($sql);

    $sql = "SELECT * FROM {booking_history3} WHERE (type = 'mail_er2' || type = 'mail_re2') && state = 2 && action_date < '$date_check'";
    $mails = $DB->get_records_sql($sql);

    foreach ($mails as $mail) {

            $is_there_an_active_class = 1;

            $sql = "SELECT * FROM {booking_data3} WHERE order_id = '$mail->b_id'";
            $bookings = $DB->get_records_sql($sql);

            foreach ($bookings as $booking) {

                if ($booking->state == 0) {
                    $is_there_an_active_class = 0;
                }

            }

            if ($is_there_an_active_class == 0) {

                echo $mail->b_id;
                echo "<br>";


                $sql = "SELECT * FROM mdl_user WHERE phone1 = '$mail->b_id'";
                $buchender = $DB->get_record_sql($sql);


                /* generate z-codes table for mail text */
                $z_codes_string = get_z_codes($bookings);

                /* get all booking data from wordpress */
                $data = getData($mail->b_id);

                foreach ($bookings as $booking) {
                    $tmp = $booking->operators_id;
                }

                /* create greeting */
                if ($booking->operators_id == 1) {
                    $anrede = "Hallo " . $data[0]['firstname'] . " " . $data[0]['lastname'];
                } else {

                    if ($booking->newsletter == 9) {
                        $anrede = "Hallo " . $data[0]['firstname'] . " " . $data[0]['lastname'];
                    } else {
                        $anrede = "Liebe Lehrkraft";
                    }
                }

                //echo $booking->operators_id;

                $sql = "SELECT * FROM mdl_ext_operators1 WHERE id = " . $booking->operators_id;
                $op = $DB->get_record_sql($sql);

                $date = new DateTime($booking->arrival);
                $kw = $date->format('W');

                if($kw != ""){
                    $sub_kw = "KW ".$kw;
                }
                /* create greeting */
                if ($booking->operators_id == 1) {
                    $email_subject = "Erinnerung Videoprojekt Berlin - Buchungs-Nr. " . $mail->b_id;
                } else {

                    if ($booking->newsletter == 9) {
                        $email_subject = "Erinnerung Videoprojekt Berlin - " . $booking->ext_booking_id . " - " . $mail->b_id." ".$sub_kw;
                    } else {
                        $email_subject = $op->name . "Erinnerung Videoprojekt Berlin - Nr. Veranstalter. " . $booking->ext_booking_id." ".$sub_kw;
                    }


                }


                ob_start();

                if ($booking->newsletter == 9) {
                    include "../mod/bookings/includes/emails/reminder2_template_no_style.php";
                } else {
                    include "../mod/bookings/includes/emails/reminder2_template.php";
                }

                $message = ob_get_clean();


                set_config('allowedemaildomains', 'ontour.org');
                set_config('emailheaders', 'X-Fixed-Header: bar');


                if ($op->mailing_traveler == 0) {

                    $emailuser = new stdClass();
                    $emailuser->email = $op->mail;
                    $emailuser->firstname = $op->name;
                    $emailuser->lastname = "";

                } else {
                    $emailuser = new stdClass();
                    $emailuser->email = $data[0]['email'];
                    $emailuser->firstname = $data[0]['firstname'];
                    $emailuser->lastname = $data[0]['lastname'];

                }

                $emailuser->maildisplay = true;
                $emailuser->mailformat = 1;
                $emailuser->id = -99;
                $emailuser->firstnamephonetic = "";
                $emailuser->lastnamephonetic = "";
                $emailuser->middlename = "";
                $emailuser->alternatename = "";
                echo "<br><br>";
                print_r($emailuser);
                echo "<br><br>";

                if ($booking->operators_id == 1) {
                   email_to_user($emailuser, $from_user, $email_subject, $message);
                } else {
                    email_to_user($emailuser, $from_user, $email_subject, $message);
                }

                $email_subject = "#".$mail->b_id." - Erinnerung 2 automatisch an ".$emailuser->email." versandt.";

                echo "<br><br>";
                print_r($from_user);
                echo "<br><br>";
                if ($booking->operators_id == 1) {
                   email_to_user($from_user, $from_user, $email_subject, $message);
                } else {
                   email_to_user($from_user, $from_user, $email_subject, $message);
                }

                setReminder2MailState($mail->b_id, "1", $booking->user_id);

            }
    }

}

function sendReminder3(){

    global $DB;

    $datetime = new DateTime('tomorrow');
    $date_check = $datetime->format('Y-m-d H:i:s');

    /* get email sender user --> kontakt@ontour.org */
    $sql = "SELECT * FROM mdl_user WHERE id = 6163";
    $from_user = $DB->get_record_sql($sql);

    $sql = "SELECT * FROM {booking_history3} WHERE type = 'mail_re2' && state = 2 && action_date < '$date_check'";
    $mails = $DB->get_records_sql($sql);

    foreach ($mails as $mail){


        $sql = "SELECT * FROM mdl_user WHERE phone1 = '$mail->b_id'";
        $buchender = $DB->get_record_sql($sql);

        $sql = "SELECT * FROM {booking_data3} WHERE order_id = '$mail->b_id'";
        $bookings = $DB->get_records_sql($sql);

        /* generate z-codes table for mail text */
        $z_codes_string = get_z_codes($bookings);

        /* get all booking data from wordpress */
        $data = getData($mail->b_id);

        foreach ($bookings as $booking){
            $tmp = $booking->operators_id;
        }

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

        //echo $booking->operators_id;

        $sql = "SELECT * FROM mdl_ext_operators1 WHERE id = ".$booking->operators_id;
        $op = $DB->get_record_sql($sql);



        /* create greeting */
        if($booking->operators_id == 1){
            $email_subject = "Erinnerung Videoprojekt Berlin - Buchungs-Nr. ".$mail->b_id;
        }else{

            if($booking->newsletter == 9){
                $email_subject = "Erinnerung Videoprojekt Berlin - ".$booking->ext_booking_id. " - ".$mail->b_id;
            }else{
                $email_subject = $op->name."Erinnerung Videoprojekt Berlin - Nr. Veranstalter. ".$booking->ext_booking_id;
            }


        }


        ob_start();

        if($booking->newsletter == 9){
            include "../../mod/bookings/includes/emails/reminder3_template_no_style.php";
        }else{
            include "../../mod/bookings/includes/emails/reminder3_template.php";
        }

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
            $emailuser->email = $data[0]['email'];
            $emailuser->firstname = $data[0]['firstname'];
            $emailuser->lastname = $data[0]['lastname'];

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


        setReminder3MailState($mail->b_id, "1", $booking->user_id);


    }

}

function setReminder1MailState($b_id, $state, $user_id){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_history3 WHERE b_id = '$b_id' && (type = 'mail_er1' || type = 'mail_re1') && state = 2";
    $mail_mo = $DB->get_record_sql($sql);

print_r($mail_mo);
    $data = new stdClass();
    $data->id = $mail_mo->id;
    $data->state = $state;
    $DB->update_record('booking_history3', $data);

}

function setReminder2MailState($b_id, $state, $user_id){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_history3 WHERE b_id = '$b_id' && (type = 'mail_er2' || type = 'mail_re2') && state = 2";
    $mail_mo = $DB->get_record_sql($sql);


    $data = new stdClass();
    $data->id = $mail_mo->id;
    $data->state = $state;
    $DB->update_record('booking_history3', $data);


}

function setReminder3MailState($b_id, $state, $user_id){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_history3 WHERE b_id = '$b_id' && type = 'mail_re3' && state = 2";
    $mail_mo = $DB->get_record_sql($sql);


    $data = new stdClass();
    $data->id = $mail_mo->id;
    $data->state = $state;
    $DB->update_record('booking_history3', $data);


}
function appOverviewSubmissons(){

    global $DB;

    //get all ongoing bookings
    echo $currentDateTime = date('Y-m-d H:i:s');
    echo "<br>";
    echo $start = date('Y-m-d', strtotime('-7 days'));
    echo "<br>";
    echo $end = date('Y-m-d', strtotime('+7 days'));
    echo "<br>";

    $sql = "SELECT * FROM {booking_data3} WHERE state = 0";
    $bookings = $DB->get_records_sql($sql);

    foreach ($bookings as $booking) {
        if ($booking->arrival > $start && $booking->arrival < $end) {
            echo $booking->order_id."<br>";
        }

    }

    foreach ($bookings as $booking){

        if($booking->arrival > $start && $booking->arrival < $end){
            
            $sql = "SELECT * FROM {user} WHERE phone1 = ".$booking->order_id;
            $z_account = $DB->get_record_sql($sql);

            echo $section_id = $z_account->middlename;



            $sql = "SELECT * FROM {groups_members} WHERE userid = ".$z_account->id;
            $group = $DB->get_record_sql($sql);
            $group_id = $group->groupid;

            $html = "";

            $sql = "SELECT * FROM {groups_members} WHERE groupid = ".$group_id;

            $groups_members = $DB->get_records_sql($sql);
            $i = 0;
            $k = 0;

            $count = count($groups_members)-1;

            foreach($groups_members as $member){

                if($k >0){

                    $html .= '<p dir="ltr" style="text-align: left; font-size:14px!important;"><strong>Gruppe '.$k.'</strong></p>';
                }


                $k++;

                $sql = "SELECT * FROM {user} WHERE id = ".$member->userid;

                $student_account = $DB->get_record_sql($sql);



                //bigger 0 because 0 is the z-code user
                if($i != $count && $i >0){
                    if($student_account->institution == ''){
                        $teamname = "<span style='color: red;'>nein</span>";
                        $task6_files = 0;
                        $task5_files = 0;
                        $task4_files = 0;
                        $task3_files = 0;
                        $task2_files = 0;

                        //get task 1 data
                        $sql = "SELECT *
				FROM {assign_submission}
				WHERE assignment = '49' && userid = ".$member->userid;

                        $task1 = $DB->get_record_sql($sql);

                        //get online text task 1
                        if($task1->status == "submitted"){
                            $sql = "SELECT *
				FROM {assignsubmission_onlinetext}
				WHERE assignment = '49' && submission = ".$task1->id;
                            $teamname_record = $DB->get_record_sql($sql);
                            $teamname = strip_tags($teamname_record->onlinetext);
                        }

                        //get task 2 data team foto
                        $sql = "SELECT *
				FROM {assign_submission}
				WHERE assignment = '54' && userid = ".$member->userid;

                        $task2 = $DB->get_record_sql($sql);

                        //get task 3 data team foto
                        $sql = "SELECT *
				FROM {assign_submission}
				WHERE assignment = '62' && userid = ".$member->userid;

                        $task3 = $DB->get_record_sql($sql);

                        //get task 4 data team foto
                        $sql = "SELECT *
				FROM {assign_submission}
				WHERE assignment = '52' && userid = ".$member->userid;

                        $task4 = $DB->get_record_sql($sql);


                        //get task 5 data team foto
                        $sql = "SELECT *
				FROM {assign_submission}
				WHERE assignment = '63' && userid = ".$member->userid;

                        $task5 = $DB->get_record_sql($sql);

                        if($task5->status == "submitted"){
                            $sql = "SELECT *
				FROM {assignsubmission_file}
				WHERE assignment = '63' && submission = ".$task5->id;

                            $task5_record = $DB->get_record_sql($sql);

                            $task5_files = $task5_record->numfiles;

                        }

                        //get task 6 data team foto
                        $sql = "SELECT *
				FROM {assign_submission}
				WHERE assignment = '64' && userid = ".$member->userid;

                        $task6 = $DB->get_record_sql($sql);

                        if($task6->status == "submitted"){
                            $sql = "SELECT *
				FROM {assignsubmission_file}
				WHERE assignment = '64' && submission = ".$task6->id;
                            $task6_record = $DB->get_record_sql($sql);
                            $task6_files = $task6_record->numfiles;
                        }

                        $sql = "SELECT *
				FROM {files}
				WHERE filesize > 0 && contextid = 2968 && userid = ".$member->userid;

                        $files = $DB->get_records_sql($sql);


                        $sql = "SELECT *
				FROM {files}
				WHERE filesize > 0 && contextid = 2967 && userid = ".$member->userid;

                        $files_5 = $DB->get_records_sql($sql);

                        $sql = "SELECT *
				FROM {files}
				WHERE filesize > 0 && contextid = 1573 && userid = ".$member->userid;

                        $files_4 = $DB->get_records_sql($sql);

                        $sql = "SELECT *
				FROM {files}
				WHERE filesize > 0 && contextid = 2966 && userid = ".$member->userid;

                        $files_3 = $DB->get_records_sql($sql);

                        $sql = "SELECT *
				FROM {files}
				WHERE filesize > 0 && contextid = 1575 && userid = ".$member->userid;

                        $files_2 = $DB->get_records_sql($sql);


                        $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 1 Teamname</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important" id="group1_task1">'.$teamname.'</div>
                         </div>';


                        if($task2->status == "submitted"){
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 2 Foto</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important">ja</div><br>
                         </div>';
                        }else{
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 2 Foto</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div>';
                        }


                        if($task3->status == "submitted"){
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 3 Film</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important">ja</div><br>
                         </div>';
                        }else{
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 3 Film</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div>';
                        }





                        if($task4->status == "submitted"){
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 4 Audio</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important">ja</div><br>
                         </div>';
                        }else{
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 4 Audio</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div>';
                        }



                        if($task5->status == "submitted"){

                            if($task5_files == 3){

                                $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 5 Safari</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important">ja('.$task5_files.' von 3)</div><br>
                         </div>';


                            }else{

                                $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 5 Safari</div>
                            <div style="color: orange;  display: inline-block; font-size:14px!important">ja('.$task5_files.' von 3)</div><br>
                         </div>';


                            }


                        }else{
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 5 Safari</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div>';

                        }



                        if($task6->status == "submitted"){

                            if($task5_files == 3){

                                $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 6 Outtakes</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important">ja('.$task6_files.' von 6)</div><br>
                         </div>';


                            }else{

                                $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 6 Outtakes</div>
                            <div style="color: orange;  display: inline-block; font-size:14px!important">ja('.$task6_files.' von 6)</div><br>
                         </div>';


                            }


                        }else{
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 6 Outtakes</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div>';

                        }

                        $i++;

                    }

                    if($i == 6){


                        $html .= '<hr><p dir="ltr" style="text-align: left; font-size:14px!important;"><strong>Lehrkräfte</strong></p>';

                        $member->userid = $member->userid+1;

                        //get task 2 data team foto
                        $sql = "SELECT *
				FROM {assign_submission}
				WHERE assignment = '61' && userid = ".$member->userid;

                        $task_teacher = $DB->get_record_sql($sql);


                        $sql = "SELECT *
				FROM {files}
				WHERE filesize > 0 && contextid = 2522 && userid = ".$member->userid;

                        $files_lehrer = $DB->get_records_sql($sql);

                        if($task_teacher->status == "submitted"){
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important">ja</div><br>
                         </div>';
                        }else{
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div>';
                        }


                    }

                }else{
                    $i++;
                }

            }

            $html = str_replace("Gruppe 6","",$html);

            echo $html;

            if(isset($section_id)){
                $sql = "SELECT * FROM {course_sections} WHERE id = '$section_id'";
                $section = $DB->get_record_sql($sql);

                $section_update = new stdClass();
                $section_update->id = $section->id;
                $section_update->summary = $html;
                $DB->update_record('course_sections', $section_update);
                rebuild_course_cache('8', true);
            }
        }
    }
}

function transferDates(){

    global $DB;

    $sql = "SELECT * FROM {booking_data3} WHERE arrival IS NULL ";
    $bookings = $DB->get_records_sql($sql);

    foreach ($bookings as $booking) {



        $servername = "localhost";
        $username = "skuehn22";
        $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
        $dbname = "projektreisenWordpress_1637922561";

        $conn = new mysqli($servername, $username, $password, $dbname);


        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $sql = "SELECT * FROM wp_woocommerce_order_items WHERE order_id = '$booking->order_id' && order_item_name = 'Videoprojekt'";
        $result = $conn->query($sql);


        foreach ($result as $row) {




            $order_item_id = $row["order_item_id"];

            $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'anreise'";
            $result = $conn->query($sql);
            $arr = $result->fetch_assoc();

            $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'abreise'";
            $result = $conn->query($sql);
            $dep = $result->fetch_assoc();

            echo $arr['meta_value'];

            $arrival_date   = new DateTime($arr['meta_value']);
            $arrival_date = $arrival_date->format('Y-m-d H:i:s');
            $arrival_date = new DateTime($arrival_date);

            $departure_date   = new DateTime($dep['meta_value']);
            $departure_date = $departure_date->format('Y-m-d H:i:s');
            $departure_date = new DateTime($departure_date);


        }


        $data = new stdClass();
        $data->id = $booking->id;
        $data->arrival = $arr['meta_value'];
        $data->departure = $dep['meta_value'];

        $DB->update_record('booking_data3', $data);

    }

}

function resetSection(){

    global $DB;

    $sql = "SELECT * FROM mdl_user WHERE imagealt = '1'";
    $users = $DB->get_records_sql($sql);

    foreach ($users as $user){

        $data = new stdClass();
        $data->id = $user->id;
        $data->middlename = '';
        $DB->update_record('user', $data);

    }


}


function replacePDFs(){


    global $DB;

    $sql = "SELECT * FROM mdl_booking_data3 WHERE arrival > DATE_SUB(CURDATE(), INTERVAL 2 DAY)";

    $bookings = $DB->get_records_sql($sql);

    foreach ($bookings as $booking){
        // Replace spaces with underscores in the classname
        $formattedClassname = str_replace(' ', '_', $booking->classname);

        $folderPath = "/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/{$booking->order_id}/{$formattedClassname}";

        if (file_exists($folderPath)) {
            echo "Folder exists for order: {$booking->order_id} with classname: {$booking->classname}";
        } else {
            echo "Folder does NOT exist for order: {$booking->order_id} with classname: {$booking->classname}";
        }

        echo "<br>";

        // Query to get users with a username that contains the order id and lastname that contains the classname
        $sql = "SELECT * FROM mdl_user WHERE username LIKE ? AND lastname LIKE ?";
        $users = $DB->get_records_sql($sql, array('%' . $booking->order_id . '%', '%' . $booking->classname . '%'));

        foreach ($users as $user) {
            echo "Found user: {$user->username} with order id: {$booking->order_id} in the username and classname: {$booking->classname} in the lastname.<br>";

            $servername = "localhost";
            $username = "skuehn22";
            $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
            $dbname = "webapp";

            $conn = new mysqli($servername, $username, $password, $dbname);

            // Check connection
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }


            // Check if the user already exists in the webapp users table
            $checkSQL = "SELECT * FROM users WHERE moodle_uid = '" . $user->id . "'";
            $result = $conn->query($checkSQL);

            if ($result && $result->num_rows > 0) {
                // Fetch the row
                $row = $result->fetch_assoc();

                if( $booking->order_id == 11173){
                    $token = $row['login_token'];

                    $text = "https://app.ontour.org/login/$token";


                    // Create a QRcode object
                    $qrcode = new TCPDF2DBarcode($text, 'QRCODE,H');

                    // Get image data as PNG
                    $imagePngData = $qrcode->getBarcodePngData(14, 14);

                    $qr_name = $token.".png";

                    // Define the path to save the image
                    $imagePath = '/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$booking->order_id.'/'.$formattedClassname.'/'.$qr_name;
                    $path_code = $booking->order_id.'/'.$formattedClassname.'/'.$qr_name;

                    // Save the image to a file
                    file_put_contents($imagePath, $imagePngData);

                    $i = substr($user->username, 1, 1);  // Extract the second character from $user->username

                    if($i == 6){
                        $pdf_name = $booking->order_id."-".$booking->classname."-Lehrergruppe-App-Anmeldedaten.pdf";
                        $content = createPDFTeachernew($i, $booking->classname, $user->username, $booking->order_id, $path_code);
                        $pdf_pages[] = $content;
                    }else{
                        $pdf_name = $booking->order_id."-".$booking->classname."-Gruppe".$i."-App-Anmeldedaten.pdf";
                        $content = createPDFnew($i, $booking->classname, $user->username, $booking->order_id, $path_code);
                        $pdf_pages[] = $content;
                    }

                    $pdf = new pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                    $pdf->AddPage();
                    $pdf->writeHTMLCell(0, 0, '', '', $content, 0, 0, 0, true, '', true);
                    ob_clean();


                    $file = $pdf->Output('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$booking->order_id.'/'.$formattedClassname.'/'.$pdf_name, 'F');
                    unlink($imagePath);

                }

                echo $row['login_token'];
                echo "<br>";
            } else {
                echo "No users found with moodle_uid: " . $user->id;
                echo "<br>";
            }



            $conn->close();
        }

        echo "<br><br>";

    }
}




