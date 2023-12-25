<?php

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

global $DB;

$student = $_POST['id'];
$user = $_POST['user'];
$course = $_POST['course'];

$sql = "SELECT *
FROM {finishing_students}
WHERE id = $student";

$student = $DB->get_record_sql($sql);

if($student){
    $student->status = 1;
    $DB->update_record('finishing_students', $student);
}

//get the amount of students which took part in the course
$sql = "SELECT id, name FROM {finishing_students}  WHERE fk_user = $user && fk_course = $course && status = 0";
$students = $DB->get_records_sql($sql);
echo count($students);




