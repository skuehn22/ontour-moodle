<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Prints an instance of mod_finishing.
 *
 * @package     mod_finishing
 * @copyright   2021 skuehn22 <kuehn.sebastian@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$f = optional_param('f', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('finishing', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('finishing', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($f) {
    $moduleinstance = $DB->get_record('finishing', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('finishing', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', 'mod_finishing'));
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

#$event = \mod_finishing\event\course_module_viewed::create(array(
#    'objectid' => $moduleinstance->id,
#    'context' => $modulecontext
#));

#$event->add_record_snapshot('course', $course);
#$event->add_record_snapshot('finishing', $moduleinstance);
#$event->trigger();#

$userid = $USER->id;
$courseid =  $course->id;

// Database
$sql = "SELECT id, name
FROM {finishing_students}
WHERE fk_user = $userid && fk_course = $courseid && status = 0";

$students = $DB->get_records_sql($sql);

//get all groups (student classes) the teacher is assigned
$sql = "SELECT u.id, u.name
FROM {groups_members} g
JOIN {groups} u ON u.id = g.groupid
WHERE g.userid = $userid";

$school_classes = $DB->get_records_sql($sql);


$obj = new stdClass();
$obj->userid = (int)$userid;
$obj->courseid = (int)$courseid;
$obj->students = array_values($students);
$obj->school_classes = array_values($school_classes);

//get the amount of students which took part in the course
$sql = "SELECT id, name FROM {finishing_students}  WHERE fk_user = $userid && fk_course = $courseid && status = 0";
$students = $DB->get_records_sql($sql);
$students_count = count($students);

if($students_count > 0){
    $obj->saved_students =  true;
}else{
    $obj->saved_students =  false;
}

$obj->students_count = (int)$students_count;



$PAGE->set_url('/mod/finishing/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

echo "<div style='text-align: center;'>";

#echo "<h3 style='color: #565E6B!important; font-size: 28px!important; padding-top: 25px;'><strong>Gruppen Accounts ".$school_data['school']->data." ".$school_data['class']->data. " ".$school_data['year']->data."</strong></h3>";
echo "<h3 style='color: #565E6B!important; font-size: 28px!important; padding-top: 25px;'><strong>Reise abschließen</strong> ";
echo "<p class='pt-3'>Wenn Sie die Reise durchgeführt haben, dann können Sie hier die Reise abschließen. </p>";
echo "</div>";


echo $OUTPUT->render_from_template('finishing/user_list', $obj);
echo $OUTPUT->render_from_template('finishing/deactivate', $obj);


echo $OUTPUT->footer();
