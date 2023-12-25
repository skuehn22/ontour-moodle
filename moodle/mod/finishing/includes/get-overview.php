<?php

require(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');

global $DB;

$bookingID = $_POST['b_uid'];
$groups = [];
$classes = [];

if(isset($_GET['group'] )) {
    $_POST['group'] = $_GET['group'];
}

if(isset($_POST['group'] )){

    $sql = "SELECT *
					FROM {groups_members}
					WHERE groupid = ".$_POST['group'];

    $groups_members = $DB->get_records_sql($sql);
    $i = 0;

    echo '<div class="row pb-1"  style="border-bottom: 3px solid #000">';
        //echo '<div class="col-md-1"><strong>ID</strong></div>';
        echo '<div class="col-md-2"><strong>Gruppe</strong></div>';
        echo '<div class="col-md-2" style="text-align: center;"><strong>Aufgabe1 <br> Teamname</strong></div>';
        echo '<div class="col-md-1" style="text-align: center;"><strong>Aufgabe2 <br> Kiezerkundung</strong></div>';
        echo '<div class="col-md-1" style="text-align: center;"><strong>Aufgabe3 <br> Teamplakat</strong></div>';
        echo '<div class="col-md-1" style="text-align: center;"><strong>Aufgabe4 <br> Audio</strong></div>';
        echo '<div class="col-md-2" style="text-align: center;"><strong>Aufgabe5 <br> Video</strong></div>';
        echo '<div class="col-md-2" style="text-align: center;"><strong>Aufgabe6 <br> Outtakes</strong></div>';
    echo '</div>';


    $count = count($groups_members)-1;

    foreach($groups_members as $member){


        $sql = "SELECT *
				FROM {user}
				WHERE id = ".$member->userid;

        $student_account = $DB->get_record_sql($sql);

        if($student_account->phone2 == "Gruppe_1"){
            $g1 = $member->userid;
        }


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

                    $teamname = $teamname_record->onlinetext;

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



                echo '<div class="row pb-1 pt-2" style="border-bottom: 1px solid #000">';
                echo '<div class="col-md-2">';
                //echo $student_account->firstname." ".$student_account->lastname." (".$student_account->username.")<br>";
                echo $student_account->firstname." ".$student_account->lastname."<br>";
                echo '</div>';

                echo '<div class="col-md-2"  style="text-align: center;">';
                echo $teamname;
                echo '</div>';
                echo '<div class="col-md-1"  style="text-align: center;">';

                if($task5->status == "submitted"){

                    if($task5_files == 3){
                        echo '<span style="color: green;">ja ('.$task5_files.' von 3)</span>';
                    }else{
                        echo '<span style="color: orange;">ja ('.$task5_files.' von 3)</span>';
                    }

                    $j = 1;
                    echo '<br>';
                    foreach ($files_5 as $file){

                        echo '<span style="color: green;"><a href="https://reisen.ontour.org/pluginfile.php/2967/assignsubmission_file/submission_files/'.$file->itemid.'/'.$file->source.'?forcedownload=1">Datei '.$i.'</a></span><br>';
                        $j++;

                    }

                }else{
                    echo '<span style="color: red;">nein</span>';
                }

                echo '</div>';
                echo '<div class="col-md-1"  style="text-align: center;">';

                if($task2->status == "submitted"){
                    echo '<span style="color: green;">ja</span>';

                    $j = 1;
                    echo '<br>';
                    foreach ($files_2 as $file){

                        echo '<span style="color: green;"><a href="https://reisen.ontour.org/pluginfile.php/1575/assignsubmission_file/submission_files/'.$file->itemid.'/'.$file->source.'?forcedownload=1">Datei '.$i.'</a></span><br>';
                        $j++;

                    }

                }else{
                    echo '<span style="color: red;">nein</span>';
                }

                echo '</div>';
                echo '<div class="col-md-1" style="text-align: center;">';

                if($task4->status == "submitted"){
                    echo '<span style="color: green;">ja</span>';

                    $j = 1;
                    echo '<br>';
                    foreach ($files_4 as $file){

                        echo '<span style="color: green;"><a href="https://reisen.ontour.org/pluginfile.php/1573/assignsubmission_file/submission_files/'.$file->itemid.'/'.$file->source.'?forcedownload=1">Datei '.$i.'</a></span><br>';
                        $j++;

                    }

                }else{
                    echo '<span style="color: red;">nein</span>';
                }

                echo '</div>';
                echo '<div class="col-md-2" style="text-align: center;">';

                 if($task3->status == "submitted"){
                     echo '<span style="color: green;">ja</span>';

                     $j = 1;
                     echo '<br>';
                     foreach ($files_3 as $file){

                         echo '<span style="color: green;"><a href="https://reisen.ontour.org/pluginfile.php/2966/assignsubmission_file/submission_files/'.$file->itemid.'/'.$file->source.'?forcedownload=1">Datei '.$i.'</a></span><br>';
                         $j++;

                     }

                 }else{
                     echo '<span style="color: red;">nein</span>';
                 }





                echo '</div>';
                echo '<div class="col-md-2" style="text-align: center;">';

                if($task6->status == "submitted"){

                    echo '<span style="color: green;">ja('.$task6_files.')</span>';

                    $j = 1;
                    echo '<br>';
                    foreach ($files as $file){

                        echo '<span style="color: green;"><a href="https://reisen.ontour.org/pluginfile.php/2968/assignsubmission_file/submission_files/'.$file->itemid.'/'.$file->source.'?forcedownload=1" class="mediafile">Datei '.$i.'</a></span><br>';
                        $j++;

                    }


                }else{
                    echo '<span style="color: red;">nein</span>';
                }

                echo '</div>';
                echo '</div>';

            }


            $i++;

        }else{
            if($i == 0){
                $i++;
            }else{
                //get task 2 data team foto
                $sql = "SELECT *
				FROM {assign_submission}
				WHERE assignment = '61' && userid = ".$member->userid;

                $task_teacher = $DB->get_record_sql($sql);


                $sql = "SELECT *
				FROM {files}
				WHERE filesize > 0 && contextid = 2522 && userid = ".$member->userid;

                $files_lehrer = $DB->get_records_sql($sql);

                echo '<div class="row pt-2" style="border-top: 2px solid #000;">';

                echo '<div class="col-md-2">Lehreraufgabe: ';

                if($task_teacher->status == "submitted"){
                    echo '<span style="color: green;">ja</span>';
                    $j = 1;
                    echo '<br>';
                    foreach ($files_lehrer as $file){

                        echo '<span style="color: green;"><a href="https://reisen.ontour.org/pluginfile.php/2522/assignsubmission_file/submission_files/'.$file->itemid.'/'.$file->source.'?forcedownload=1">Datei '.$i.'</a></span><br>';
                        $j++;

                    }

                }else{
                    echo '<span style="color: red;">nein</span>';
                }

                echo '</div>';
                echo '<div class="col-md-2">';



                echo '</div>';

                echo '</div>';
            }
        }

    }

    /*echo "<a href='#' class='btn btn-primary download' id='download'>Alle Dateien herrunterladen</a>";*/

    /*
    echo "<br><br>";
    echo "Debug-Info:";
    echo "Gruppe: ".$_POST['group']."<br><br>";
    */


    $find = $g1 -1;
    $sql = "SELECT * FROM {user} WHERE id = ".$find;


     $zCodeUser = $DB->get_record_sql($sql);

    //echo $zCodeUser->id;

    $sql = "SELECT *
				FROM {booking_data3}
				WHERE user_id = ".$zCodeUser->id;


 

    $bookingData = $DB->get_record_sql($sql);

    if($bookingData->classname_teacher != null){
        echo "<br><br><strong>Klassenname (Lehrer):</strong> ".$bookingData->classname_teacher."<br>";
    }else{
        echo "<br><br><strong>Klassenname (Lehrer):</strong> bisher nicht gewählt<br>";
    }



    echo "<br><strong>Buchungsnummer:</strong> ".$bookingData->order_id;
    echo "<br><strong>Veranstalternummer:</strong> ".$bookingData->ext_booking_id;
    echo "<br><strong>Schule:</strong> ".$bookingData->school;
    echo "<br><strong>Klassenname (intern):</strong> ".$bookingData->classname."<br>";
    echo "<br><strong>Botschaft Lehrer:</strong> ".$bookingData->greetings."<br><br>";


    $sql = "SELECT *
				FROM {finishing_students}
				WHERE fk_user = ".$bookingData->user_id;

    $studentNames = $DB->get_records_sql($sql);

    if($studentNames){

        echo "<strong>Schülernamen</strong><br>";

        foreach ($studentNames as $studentName){

            echo $studentName->name."<br>" ;

        }
    }



    $sql = "SELECT *
				FROM {finishing_lehrer}
				WHERE fk_user = ".$bookingData->user_id;

    $studentNames = $DB->get_records_sql($sql);

    if($studentNames){
        echo "<br><strong>Lehrer</strong><br>";

        foreach ($studentNames as $studentName){

            echo $studentName->name."<br>" ;

        }
    }






    //$url=$CFG->wwwroot/pluginfile.php/$contextid/$component/$filearea/$itemid/$course_name;

    $fs = get_file_storage();

// Prepare file record object
    $fileinfo = array(
        'component' => 'assignsubmission_file',     // usually = table name
        'filearea' => 'submission_files',     // usually = table name
        'itemid' => 707,               // usually = ID of row in table
        'contextid' => '2968', // ID of context
        'filepath' => '/',           // any path beginning and ending in /
        'filename' => 'cdv_photo_1657712978_20220713134936.jpg'); // any filename

// Get file
    $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'],
        $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);

// Read contents
    if ($file) {
        //echo "da";

        $zip = new ZipArchive();
        $zip->open("archive.zip", ZipArchive::CREATE);
        $zip->addFile( "/pluginfile.php/2968/assignsubmission_file/submission_files/707/cdv_photo_1657712978_20220713134936.jpg");
        //$zip->addEmptyDir("folder");
        $zip->close();
        //print_r ($file);

        $contents = $file->get_content();
        //echo $file->get_content();
    } else {
       // echo "nix";
        // file doesn't exist - do something
    }


}else{

    echo "nein";

}
