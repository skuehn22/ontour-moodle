<?php

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

global $DB;
global $USER;

$student = $_POST['id'];
$user = $USER->id;
$course = 8;

$sql = "SELECT *
FROM {finishing_lehrer}
WHERE id = $student";

$student = $DB->get_record_sql($sql);

if($student){
    $student->status = 1;
    $DB->update_record('finishing_lehrer', $student);
}

//get the amount of students which took part in the course
$sql = "SELECT id, name FROM {finishing_lehrer}  WHERE fk_user = $user && fk_course = $course && status = 0";
$students = $DB->get_records_sql($sql);
echo count($students);




