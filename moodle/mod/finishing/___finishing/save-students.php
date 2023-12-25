<?php


$test = "";


foreach($_POST['name'] as $name){

    $obj = new stdClass();
    $obj->fk_user = $_POST['userid'];
    $obj->fk_course = $_POST['courseid'];
    $obj->name = $name;
    $obj->status = 0;
    $group_id = $DB->insert_record('finishing_students', $obj, true, false);

}
