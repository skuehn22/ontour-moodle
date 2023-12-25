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
 * Prints an instance of mod_setup.
 *
 * @package     mod_setup
 * @copyright   2021 skuehn22 <kuehn.sebastian@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

global $USER;



// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$s = optional_param('s', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('setup', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('setup', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($s) {
    $moduleinstance = $DB->get_record('setup', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('setup', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', 'mod_setup'));
}

require_login($course, true, $cm);


$userid = $USER->id;

$sql = "SELECT groupid FROM {groups_members} WHERE userid = :id";
$group = $DB->get_record_sql($sql, array('id'=>$userid));


//check if the logged in teacher is already member of group
if(!$group)
{
    //if not create a new group and use the school name as groupname
    $obj = new stdClass();
    $obj->courseid = $course->id;
    $obj->name = $USER->department;
    $group_id = $DB->insert_record('groups', $obj, true, false);

    //assign teacher to the created group
    $obj = new stdClass();
    $obj->groupid = $group_id;
    $obj->userid = $userid;
    $obj->timeadded = time();
    $group_members_id = $DB->insert_record('groups_members', $obj, true, false);

    //create 5 random users which will be used as students group accounts
    for ($i = 1; $i <= 5; $i++) {

        $obj = new stdClass();
        $obj->auth = "manual";
        $obj->confirmed = 1;
        $obj->username = time() - rand(1,999);
        $obj->password = password_hash($obj->username, PASSWORD_DEFAULT);
        $obj->firstname = "Gruppe";
        $obj->lastname = $i;
        $obj->email = $obj->username."@projektreisen.de";
        $obj->department = "students";
        $obj->timecreated = time();
        $obj->timemodified = time();
        $obj->mnethostid = 1;
        $users[] = $DB->insert_record('user', $obj, true, false);
    }


    foreach ($users as $user){

        // assign student to group
        $obj = new stdClass();
        $obj->groupid = $group_id;
        $obj->userid = $user;
        $obj->timeadded = time();
        $group_members_id = $DB->insert_record('groups_members', $obj, true, false);

        //enrol user to course
        $obj = new stdClass();
        $obj->enrolid = "21";
        $obj->userid = $user;
        $obj->timestart = time();
        $obj->timecreated = time();
        $obj->timemodified = time();
        $group_members_id = $DB->insert_record('user_enrolments', $obj, true, false);
    }

    //assign the assignments to the new users
    //find context id -> combines system / course cat / course / and its own Id
    $sql = "SELECT * FROM {context} WHERE instanceid = :id && contextlevel = 50";
    $context_course = $DB->get_record_sql($sql, array('id'=>$course->id));
    $path = "%".$context_course->path."%";

    $sql = "SELECT * FROM {context} WHERE path LIKE :path && contextlevel = 70";
    $context_module = $DB->get_records_sql($sql, array('path'=>$path));

  foreach ($users as $user){
    foreach($context_module as $module){
        // set role for student
        $ra = new stdClass();
        $ra->roleid       = "5";
        $ra->contextid    = $module->id;
        $ra->userid       = $user;
        $ra->timemodified = time();
        $ra->id = $DB->insert_record('role_assignments', $ra);
    }
	}



}



if(isset($group->groupid)){
    $group_id = $group->groupid;
}

$sql = "SELECT u.username
FROM {groups_members} g
JOIN {user} u ON u.id = g.userid
WHERE g.groupid = $group_id && u.id <> $userid";

$users = $DB->get_records_sql($sql);

$modulecontext = context_module::instance($cm->id);

$PAGE->set_url('/mod/setup/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

$school_data = getSchool($userid);


echo $OUTPUT->header();

echo "<div style='text-align: left;'>";

echo '<br><br><p>
    <a href="https://reisen.ontour.org/course/view.php?id=8" class="btn btn-ontour">Zurück zu "Das Videoprojekt"</a>
</p>
';

#echo "<h3 style='color: #565E6B!important; font-size: 28px!important; padding-top: 25px;'><strong>Gruppen Accounts ".$school_data['school']->data." ".$school_data['class']->data. " ".$school_data['year']->data."</strong></h3>";
echo "<h2 style='padding-top: 25px;'><strong>Zugänge für die Gruppen</strong> </h2>";

echo "</div>";

echo "<p style='color: #565E6B!important; font-size: 18px!important;'><strong>Gruppenaccounts </strong></p>";


$i = 1;

echo "<div class='row'>";
echo "<div class='col-md-5'  style='border: solid #ccc 1px; border-radius: 6px; background-color: #fff; margin-left: 15px;'>";

        echo "<div class='row'>";

            echo "<div class='offset-md-6 col-md-6 pb-3 pt-5'>";
            echo "<strong>Benutzername <br> & Passwort</strong>";
            echo "</div>";
            echo "</div>";

            foreach ($users as $key => $user){
                echo "<div class='row'>";

                    echo "<div class='col-md-6 pb-3 pl-5'>";

                            echo "<strong>Gruppe ".$i.": </strong><br>";
                            $i++;

                    echo "</div>";

                    echo "<div class='col-md-6'>";

                            echo $user->username;

                    echo "</div>";

                echo "</div>";
            }





echo "</div>";


echo "</div>";





//echo $OUTPUT->render_from_template('setup/user_data', [$users_names]);
//echo "<br>".$id;


echo $OUTPUT->footer($userid);


function getSchool($userid){

    global $DB;

    $data = [];

    $sql = "SELECT data
    FROM {user_info_data} 
    WHERE userid = $userid && fieldid = 1";

    $data['school'] = $DB->get_record_sql($sql);

    $sql = "SELECT data
    FROM {user_info_data} 
    WHERE userid = $userid && fieldid = 2";

    $data['class'] = $DB->get_record_sql($sql);

    $sql = "SELECT data
    FROM {user_info_data} 
    WHERE userid = $userid && fieldid = 3";

    $data['year'] = $DB->get_record_sql($sql);

    return $data;

}
