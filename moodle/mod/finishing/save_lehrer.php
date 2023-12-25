<?php

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

global $DB;
global $USER;
$course = 8;
$user = $USER->id;


if(isset($_POST['new_name_lehrer'])){

    $DB->delete_records('finishing_lehrer', ['fk_user' => $USER->id]);

    foreach($_POST['new_name_lehrer'] as $key => $name){

        // $DB->execute("DELETE FROM {finishing_students} WHERE fk_user = $user  && name = '$name' && fk_course = $course && status = 0");
        //$sql = "SELECT id, name FROM {finishing_students} WHERE fk_user = $user  && name = '$name' && fk_course = $course && status = 0";
        //$student = $DB->get_record_sql($sql);



        try {

            $sql = "SELECT id, name FROM {finishing_lehrer} WHERE fk_user = $user  && name = '$name' && fk_course = $course && status = 0";

            $student = $DB->get_record_sql($sql);

            if(!$student){
                if($name != ""){
                    $obj = new stdClass();
                    $obj->fk_user = $user;
                    $obj->fk_course = $course;
                    $obj->name = $name;
                    $obj->status = 0;
                    $group_id = $DB->insert_record('finishing_lehrer', $obj, true, false);
                }
            }
        }catch (Exception $e) {
            return $e;
        }

    }
}

//get the amount of students which took part in the course
$sql = "SELECT id, name FROM {finishing_lehrer}  WHERE fk_user = $user && fk_course = $course && status = 0";
$students = $DB->get_records_sql($sql);
echo count($students);

