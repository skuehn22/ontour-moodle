<?php

global $CFG;
global $DB;
require(__DIR__.'/../config.php');
require_once('../config.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/pdflib.php');
require '../vendor/autoload.php';
use transloadit\Transloadit;



if(isset($_GET['class-filter']) && $_GET['class-filter'] != ''){


    echo $u_id = $_GET['class-filter'];
    $sql = "SELECT * FROM {booking_data3} WHERE user_id = '$u_id'";
    $booking = $DB->get_record_sql($sql);
    $school = $booking->school;
    $classname = $booking->classname;
    $year = substr($booking->arrival, 0, 4);
    $present = $_GET['quality'];
    $sql = "SELECT * FROM {ext_operators1} WHERE id = '$booking->operators_id'";
    $operator = $DB->get_record_sql($sql);
    echo "<br>Veranstalter: ".$operator->name."<br>";


    $video_task2 = "https://ontour.org/videos/Intro_Sequenz_02.mp4";

    echo "Schule: <span style=''>$school</span><br>";
    echo "Klasse:<span style=''> $classname</span><br>";

    switch ($_GET['task']) {
        case 1:
            $task2 = prepareTask2($u_id);
            break;
        case 5:
            $task2 = prepareTask2($u_id);
            break;
        case 6:
            $task2 = prepareTask6($u_id);
            break;
    }







 

}else{
    echo 'nicht erreicht';
}


function prepareTask2($u_id){

    global $DB;

    $task = [];
    $gruppen_task2 = 0;
    echo "<br>";

    //TASK 2
    for ($i = 1; $i <= 6; $i++) {

        $next = $u_id+$i;
        $sql = "SELECT * FROM {user} WHERE id = '$next'";
        $user = $DB->get_record_sql($sql);



        $sql = "SELECT * FROM {assign_submission} WHERE userid = '$user->id' && assignment = 54 && status = 'submitted' && latest = 1";
        $assign_submission_task2 = $DB->get_record_sql($sql);

        $sql = "SELECT * FROM {assign_submission} WHERE userid = '$user->id' && assignment = 49 && status = 'submitted' && latest = 1";
        $assign_submission_task1 = $DB->get_record_sql($sql);

        if($i ==6 ){



            $sql = "SELECT * FROM {assign_submission} WHERE userid = '$user->id' && assignment = 61 && status = 'submitted' && latest = 1";
            $assign_submission_task2_teacher = $DB->get_record_sql($sql);



        }





        if($assign_submission_task2){

            if($assign_submission_task1){

                $sql = "SELECT * FROM {assignsubmission_onlinetext} WHERE submission = '$assign_submission_task1->id' && assignment = 49";
                $assign_submission_onlinetext = $DB->get_record_sql($sql);


                echo "<div class='row'><div class='col-2'>".$user->phone2."</div><div class='col-4'>".$assign_submission_onlinetext->onlinetext."</div></div>";

            }

            $sql = "SELECT * FROM {files} WHERE userid = '$user->id' && component = 'assignsubmission_file' && filearea = 'submission_files' && filesize > 0 && filename != '.' && contextid = 1575 && itemid = $assign_submission_task2->id";
            $file = $DB->get_record_sql($sql);

            $task[$i] = "https://reisen.ontour.org/pluginfile.php/2966/assignsubmission_file/submission_files/2003/cdv_photo_1671021754.mov?forcedownload=1&contextid=1575&itemid=$assign_submission_task2->id&file=$file->filename";
            echo "<div class='row'><div class='col-8'><img src='$task[$i]' style='max-width: 500px; max-height: 400px; padding-left:100px;' id='imgTask2_$i'> </div><div class='col-4'><input type='button' value='Rotate' id='rotate$i' class='rotater btn btn-secondary'></div></div>";
            echo "<br><br>";




        }else{
            $task[$i] = "";
        }


        if($assign_submission_task2_teacher){



            $sql = "SELECT * FROM {files} WHERE userid = '$user->id' && mimetype = 'image/jpeg' && component = 'assignsubmission_file' && filearea = 'submission_files' && filesize > 0 && filename != '.' && contextid = 2522 && itemid = $assign_submission_task2_teacher->id";
            $file = $DB->get_record_sql($sql);

           

            echo "<div class='row'><div class='col-8'>Lehrer 2<br>  <br>  </div><div class='col-4'></div></div>";
            echo "<br><br>";
            $task[$i] = "https://reisen.ontour.org/pluginfile.php/2966/assignsubmission_file/submission_files/2003/cdv_photo_1671021754.mov?forcedownload=1&contextid=2522&itemid=$assign_submission_task2_teacher->id&file=$file->filename";
            echo "<div class='row'><div class='col-8'><img src='$task[$i]' style='max-width: 500px; max-height: 400px; padding-left:100px;' id='imgTask2_$i'> </div><div class='col-4'><input type='button' value='Rotate' id='rotate$i' class='rotater btn btn-secondary'></div></div>";
            echo "<br><br>";






            //$task[$i] = "https://reisen.ontour.org/pluginfile.php/2966/assignsubmission_file/submission_files/2003/cdv_photo_1671021754.mov?forcedownload=1&contextid=1575&itemid=$assign_submission_task2_teacher->id&file=$file->filename";


            $gruppen_task2++;

        }

        if($assign_submission_task2 && $assign_submission_task1){
            $gruppen_task2++;
        }



    }
    $task[7] = $gruppen_task2;

    echo "<br> Produzierbare Gruppen: ".$task[7]."<br><br>";


    return $task;
}

function prepareTask6($u_id){

    global $DB;

    $task = [];
    $gruppen_task2 = 0;
    echo "<br>";
    $k = 0;
    //TASK 2
    for ($i = 1; $i <= 6; $i++) {

        $next = $u_id+$i;
        $sql = "SELECT * FROM {user} WHERE id = '$next'";
        $user = $DB->get_record_sql($sql);



        $sql = "SELECT * FROM {assign_submission} WHERE userid = '$user->id' && assignment = 64 && status = 'submitted' && latest = 1";
        $assign_submission_task6s = $DB->get_records_sql($sql);




        if($assign_submission_task6s){


            foreach ($assign_submission_task6s as $assign_submission_task6){

                $contextid = 2968;

                echo $user->phone2;

                $sql = "SELECT * FROM {files} WHERE userid = '$user->id' && component = 'assignsubmission_file' && filearea = 'submission_files' && filesize > 0 && filename != '.' && contextid = $contextid && itemid = $assign_submission_task6->id";
                $files = $DB->get_records_sql($sql);


               // echo '<li data-target="#carouselExampleIndicators" data-slide-to="'.$j.'" class="active"></li>';



                foreach ($files as $file){


                    if($file->mimetype == "video/quicktime" || $file->mimetype == "video/mp4"){




                        echo ' <div class="row"><div class="col-8" style="  vertical-align: top;"> ';

                        echo $task[$i] = '    
    
                          <video width="520" height="440" controls style="padding-left: 100px;" id="imgTask2_'.$k.'">
                          <source src="https://reisen.ontour.org/pluginfile.php/2966/assignsubmission_file/submission_files/2003/cdv_photo_1671021754.mov?forcedownload=1&contextid='.$contextid.'&itemid='.$assign_submission_task6->id.'&file='.$file->filename.'" type="video/mp4">
                          <source src="https://reisen.ontour.org/pluginfile.php/2966/assignsubmission_file/submission_files/2003/cdv_photo_1671021754.mov?forcedownload=1&contextid='.$contextid.'&itemid='.$assign_submission_task6->id.'&file='.$file->filename.'" type="video/ogg">    
                          </video>';

                        echo "</div>
                              <div class='col-2'><input type='button' value='Rotate' id='rotate$k' class='btn btn-secondary rotater'></div>
                              <div class='col-2'><input type='checkbox' value='exclude' id='exclude$k' class='btn btn-secondary excluder'> Exclude</div>";
                        echo "</div><br><br>";

                    }else{

                        $task[$i] = "https://reisen.ontour.org/pluginfile.php/2966/assignsubmission_file/submission_files/2003/cdv_photo_1671021754.mov?forcedownload=1&contextid=$contextid&itemid=$assign_submission_task6->id&file=$file->filename";
                        echo "<div class='row'><div class='col-8'><img src='$task[$i]' style='max-width: 500px; max-height: 400px; padding-left:100px;' id='imgTask2_$k'> </div><div class='col-4'><input type='button' value='Rotate' id='rotate$k' class='btn btn-secondary rotater'></div></div>";
                        echo "<br><br>";
                    }

                    $k++;
                }


            }



        }else{
            $task[$i] = "";
        }


    }

    $sql = "SELECT *
				FROM {finishing_students}
				WHERE fk_user = ".$u_id;

    $studentNames = $DB->get_records_sql($sql);

    if($studentNames){

        echo "<br><br><strong>Schülernamen</strong><br>";

        foreach ($studentNames as $studentName){

            echo $studentName->name."<br>" ;

        }
    }


    $sql = "SELECT *
				FROM {finishing_lehrer}
				WHERE fk_user = ".$u_id;

    $studentNames = $DB->get_records_sql($sql);

    if($studentNames){

        echo "<br><br><strong>Lehrernamen</strong><br>";

        foreach ($studentNames as $studentName){

            echo $studentName->name."<br>" ;

        }
    }

    return $task;
}
