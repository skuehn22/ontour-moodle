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

global $USER;
global $DB;


$servername = "localhost";
$username = "skuehn22";
$password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
$dbname = "ontour_moodle_new";

$school = $_POST['school'];

$conn = new mysqli($servername, $username, $password, $dbname);
$sql = "UPDATE mdl_booking_data3 SET school='$school' WHERE user_id=$USER->id";
$conn->query($sql);

redirect("https://reisen.ontour.org/course/view.php?id=8&section=9");