<?php

require('../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/cohort/edit_form.php');

$id        = optional_param('id', 0, PARAM_INT);
$contextid = optional_param('contextid', 0, PARAM_INT);
$delete    = optional_param('delete', 0, PARAM_BOOL);
$show      = optional_param('show', 0, PARAM_BOOL);
$hide      = optional_param('hide', 0, PARAM_BOOL);
$confirm   = optional_param('confirm', 0, PARAM_BOOL);
$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

$current_url = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

$id = $_POST['user_id'];

$sql = "SELECT * FROM {user} WHERE id = $id";
$group = $DB->get_record_sql($sql, array('id'=>$id));
$group->firstname =  $_POST['firstname'];
$group->lastname =  $_POST['lastname'];

$DB->update_record('user', $group);


if(isset($_POST['company'])){
    redirect("https://reisen.ontour.org/course/view.php?id=10&section=3&mail=".$_POST['mail']);
}else{
    redirect("https://reisen.ontour.org/course/view.php?id=8&section=6&mail=".$_POST['mail']);
}