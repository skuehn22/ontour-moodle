<?php

// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * CLI cron
 *
 * This script looks through all the module directories for cron.php files
 * and runs them.  These files can contain cleanup functions, email functions
 * or anything that needs to be run on a regular basis.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once($CFG->libdir.'/cronlib.php');
require_once($CFG->libdir.'/pdflib.php');
require_once($CFG->libdir .'/tcpdf/tcpdf_barcodes_2d.php');

    checkBookings_direct();
    checkBookings();
    checkReminder1();
    checkReminder2();
    #createAppOverview();
    #filmProduction();
    #setFilmReady();


//checkReminder3();
    //sendReminder3();



// now get cli options
list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'stop' => false,
        'list' => false,
        'force' => false,
        'enable' => false,
        'disable' => false,
        'disable-wait' => false,
    ], [
        'h' => 'help',
        's' => 'stop',
        'l' => 'list',
        'f' => 'force',
        'e' => 'enable',
        'd' => 'disable',
        'w' => 'disable-wait',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
"Execute periodic cron actions.

Options:
-h, --help               Print out this help
-s, --stop               Notify all other running cron processes to stop after the current task
-l, --list               Show the list of currently running tasks and how long they have been running
-f, --force              Execute task even if cron is disabled
-e, --enable             Enable cron
-d, --disable            Disable cron
-w, --disable-wait=600   Disable cron and wait until all tasks finished or fail after N seconds (optional param)

Example:
\$sudo -u www-data /usr/bin/php admin/cli/cron.php
";

    echo $help;
    die;
}

if ($options['stop']) {
    // By clearing the caches this signals to other running processes
    // to exit after finishing the current task.
    \core\task\manager::clear_static_caches();
    die;
}

if ($options['enable']) {
    set_config('cron_enabled', 1);
    mtrace('Cron has been enabled for the site.');
    exit(0);
}

if ($options['disable']) {
    set_config('cron_enabled', 0);
    \core\task\manager::clear_static_caches();
    mtrace('Cron has been disabled for the site.');
    exit(0);
}

if ($options['list']) {
    $tasks = \core\task\manager::get_running_tasks();
    mtrace('The list of currently running tasks:');
    $format = "%7s %-12s %-9s %-20s %-52s\n";
    printf ($format,
        'PID',
        'HOST',
        'TYPE',
        'TIME',
        'CLASSNAME'
    );
    foreach ($tasks as $task) {
        printf ($format,
            $task->pid,
            substr($task->hostname, 0, 12),
            $task->type,
            format_time(time() - $task->timestarted),
            substr($task->classname, 0, 52)
        );
    }
    exit(0);
}

if ($wait = $options['disable-wait']) {
    $started = time();
    if (true === $wait) {
        // Default waiting time.
        $waitsec = 600;
    } else {
        $waitsec = $wait;
        $wait = true;
    }

    set_config('cron_enabled', 0);
    \core\task\manager::clear_static_caches();
    mtrace('Cron has been disabled for the site.');
    mtrace('Allocating '. format_time($waitsec) . ' for the tasks to finish.');

    $lastcount = 0;
    while ($wait) {
        $tasks = \core\task\manager::get_running_tasks();

        if (count($tasks) == 0) {
            mtrace('');
            mtrace('All scheduled and adhoc tasks finished.');
            exit(0);
        }

        if (time() - $started >= $waitsec) {
            mtrace('');
            mtrace('Wait time ('. format_time($waitsec) . ') elapsed, but ' . count($tasks) . ' task(s) still running.');
            mtrace('Exiting with code 1.');
            exit(1);
        }

        if (count($tasks) !== $lastcount) {
            mtrace('');
            mtrace(count($tasks) . " tasks currently running.", '');
            $lastcount = count($tasks);
        } else {
            mtrace('.', '');
        }

        sleep(1);
    }
}

if (!get_config('core', 'cron_enabled') && !$options['force']) {
    mtrace('Cron is disabled. Use --force to override.');
    exit(1);
}

\core\local\cli\shutdown::script_supports_graceful_exit();



function checkBookings(){
    global $DB;

    $course = 8;
    $sql = "SELECT * FROM {user} WHERE address != ''";
    $users = $DB->get_records_sql($sql);

    if ($users) {
        foreach ($users as $user) { //only return enrolled users.
            if(!empty($user->address)){

                $order_id = $groups_tmp = explode("-" ,$user->phone1);
                $order_id = $order_id[0];

                //new direct bookings have vcodes with them
                if (strpos($user->phone1, '-') !== false) {

                    $pieces = explode("-vc-", $user->phone1);
                    $direct_codes = $pieces[1];
                    $direct_codes = explode(",", $direct_codes);

                    $op_pieces = explode("-", $direct_codes[0]);
                    $operator = $op_pieces[0];
                    $sql = "SELECT mailing FROM mdl_ext_operators1 WHERE short = '$operator'";
                    $mailing = $DB->get_record_sql($sql);

                    $sql = "SELECT id FROM mdl_ext_operators1 WHERE short = '$operator'";
                    $op_id = $DB->get_record_sql($sql);

                }

                $groups_tmp = explode("," ,$user->address);
                $k = 0;
                $z = 0;

                mkdir("/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/".$order_id, 0777);

                foreach ($groups_tmp as $group_tmp){



                    $group_tmp = str_replace(' ', '_', $group_tmp); // Replaces all spaces with hyphens.
                    $remove = array("@", "#", "(", ")", "*", "/", "&");
                    $group_tmp = str_replace($remove, "", $group_tmp);
                    $group_tmp = str_replace(".","_",$group_tmp);

                    if($group_tmp != ""){

                        $users = null;

                        //create groups
                        $obj = new stdClass();
                        $obj->courseid = $course;
                        $obj->name = $order_id."-".$group_tmp;
                        $obj->description = $order_id;
                        $group_id = $DB->insert_record('groups', $obj, true, false);

                        //create v-code user
                        $obj = new stdClass();
                        $obj->auth = "manual";
                        $obj->confirmed = 1;
                        $obj->username =  $direct_codes[$z];
                        $obj->password = password_hash($direct_codes[$z], PASSWORD_DEFAULT);
                        $obj->firstname = $group_tmp;
                        $obj->lastname = " ";
                        $obj->email = $obj->username."@ontour.org";
                        $obj->timecreated = time();
                        $obj->timemodified = time();
                        $obj->institution = "";
                        $obj->mnethostid = 1;
                        $obj->lang = "de";
                        $obj->idnumber = "new";
                        $obj->imagealt = "1";
                        $obj->phone1 = $order_id;
                        $obj->phone2 = $group_tmp;
                        $obj->department = "";
                        $user_v_code = $DB->insert_record('user', $obj, true, false);

                        $servername = "localhost";
                        $username = "skuehn22";
                        $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
                        $dbname = "projektreisenWordpress_1637922561";

                        $conn = new mysqli($servername, $username, $password, $dbname);
                        $sql = "SELECT * FROM wp_postmeta WHERE post_id = '$order_id' && meta_key = 'schulname'";
                        $result = $conn->query($sql);
                        $school = $result->fetch_assoc();

                        $sql = "SELECT * FROM {booking_data3} WHERE order_id = '$order_id'";
                        $booking = $DB->get_record_sql($sql);


                        $obj->classname = $group_tmp;
                        $obj->school = $school['meta_value'];
                        $obj->order_id = $order_id;
                        $obj->operators_id = $op_id->id;
                        $obj->user_id = $user_v_code;
                        $obj->mailing = $mailing->mailing;
                        $obj->newsletter = 1;
                        $DB->insert_record('booking_data3', $obj);

                        //enrol v-code to course
                        $obj = new stdClass();
                        $obj->enrolid = "19";
                        $obj->userid = $user_v_code;
                        $obj->timestart = time();
                        $obj->timecreated = time();
                        $obj->timemodified = time();
                        $obj->modifierid = "2";
                        $obj->timestart = "0";
                        $obj->timeend = "0";
                        $DB->insert_record('user_enrolments', $obj, true, false);

                        //assign v-code  to the created group
                        $obj = new stdClass();
                        $obj->groupid = $group_id;
                        $obj->userid = $user_v_code;
                        $obj->timeadded = time();
                        $DB->insert_record('groups_members', $obj, true, false);
                        //$teacher_id = $userid;


                        //assign teacher to the created group
                        $obj = new stdClass();
                        $obj->groupid = $group_id;
                        $obj->userid = $user->id;
                        $obj->timeadded = time();
                        $group_members_id = $DB->insert_record('groups_members', $obj, true, false);
                        $teacher_id = $user->id;

                        $users_app = [];
                        $pdf_pages = [];

                        //create 5 random users which will be used as students group accounts
                        for ($i = 1; $i <= 6; $i++) {

                            $obj = new stdClass();
                            $obj->auth = "manual";
                            $obj->confirmed = 1;
                            $obj->username = rand(1,999);
                            $obj->username = "g".$i."_".$obj->username."_".$order_id."_".$group_tmp;
                            $obj->password = password_hash($obj->username, PASSWORD_DEFAULT);
                            $obj->firstname = "Gruppe";
                            $obj->lastname = $i."_".$group_tmp;
                            $obj->email = $obj->username."@projektreisen.de";
                            $obj->department = $user->department;
                            $obj->timecreated = time();
                            $obj->timemodified = time();

                            if($i == 6){
                                $obj->institution = "teacher_task";
                            }

                            $obj->mnethostid = 1;
                            $obj->lang = "de";
                            $obj->idnumber = "dev_restriction";
                            $obj->phone2 = "Gruppe_".$i;
                            $obj->department = "student_restriction";
                            $users_app[] = $DB->insert_record('user', $obj, true, false);

                            
                            if($i != 6){
                                
                                //CREATE PDF
                                $pdf_name = $order_id."-Gruppe".$i."-Klasse".$group_tmp."-App-Anmeldedaten-".$user->city.".pdf";

                                if($booking->product == 10){
                                    $content = createPDFCompany($i, $group_tmp, $obj->username,$order_id);
                                }else{
                                    $content = createPDF($i, $group_tmp, $obj->username,$order_id);
                                }

                                $pdf_pages[] = $content;
                            }else{
                                //CREATE PDF
                                $pdf_name = $order_id."-Lehrergruppe-Klasse".$group_tmp."-App-Anmeldedaten-".$user->city.".pdf";

                                if($booking->product == 10){
                                    $content = createPDFLeiter($i, $group_tmp, $obj->username,$order_id);
                                }else{
                                    $content = createPDFTeacher($i, $group_tmp, $obj->username,$order_id);
                                }

                                $pdf_pages[] = $content;
                            }

                            global $CFG;
                            require_once($CFG->libdir.'/pdflib.php');
                            $pdf = new pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                            $pdf->AddPage();
                            $pdf->writeHTMLCell(0, 0, '', '', $content, 0, 0, 0, true, '', true);
                            ob_clean();


                            if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id)) {
                                mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id, 0777, true);
                            }

                            if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp)) {
                                mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp, 0777, true);
                            }

                            $file = $pdf->Output('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp.'/'.$pdf_name, 'F');

                        }

                        $pdf_name = $order_id."-Alle-Accounts".$group_tmp."-App-Anmeldedaten-".$user->city.".pdf";
                        $pdf = new pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                        foreach ($pdf_pages as $page){

                            $pdf->AddPage();
                            $pdf->writeHTMLCell(0, 0, '', '', $page, 0, 0, 0, true, '', true);

                        }
                        ob_clean();
                        $file = $pdf->Output('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp.'/'.$pdf_name, 'F');

                        foreach ($users_app as $user_app){

                            // assign student to group
                            $obj = new stdClass();
                            $obj->groupid = $group_id;
                            $obj->userid = $user_app;
                            $obj->timeadded = time();
                            $group_members_id = $DB->insert_record('groups_members', $obj, true, false);

                            //enrol user to course
                            $obj = new stdClass();
                            $obj->enrolid = "19";
                            $obj->userid = $user_app;
                            $obj->timestart = time();
                            $obj->timecreated = time();
                            $obj->timemodified = time();
                            $group_members_id = $DB->insert_record('user_enrolments', $obj, true, false);

                            //assign student role
                            $obj = new stdClass();
                            $obj->roleid = "5";
                            $obj->contextid = "233";
                            $obj->userid = $user_app;
                            $obj->modifierid = "2";
                            $obj->timemodified = "1647872831";
                            $group_members_id = $DB->insert_record('role_assignments', $obj, true, false);
                        }


                        //assign the assignments to the new users
                        //find context id -> combines system / course cat / course / and its own Id
                        $sql = "SELECT * FROM {context} WHERE instanceid = :id && contextlevel = 50";
                        $context_course = $DB->get_record_sql($sql, array('id'=>$course));
                        $path = "%".$context_course->path."%";

                        $sql = "SELECT * FROM {context} WHERE path LIKE :path && contextlevel = 70";
                        $context_module = $DB->get_records_sql($sql, array('path'=>$path));

                        foreach ($users_app as $user_app){
                            foreach($context_module as $module){
                                // set role for student
                                $ra = new stdClass();
                                $ra->roleid       = "5";
                                $ra->contextid    = $module->id;
                                $ra->userid       = $user_app;
                                $ra->timemodified = time();
                                $ra->id = $DB->insert_record('role_assignments', $ra);
                            }
                        }



                    }


                    $z++;
                }


                setPlannedTask($operator, $order_id, $user);

                $user_update = new stdClass();
                $user_update->id = $user->id;
                $user_update->address = "";
                $DB->update_record('user', $user_update);

            }
        }
    }
}

function setPlannedTask($operator, $ontour_bid, $user){

    global $USER;
    global $DB;

    $servername = "localhost";
    $username = "skuehn22";
    $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
    $dbname = "projektreisenWordpress_1637922561";

    $conn = new mysqli($servername, $username, $password, $dbname);

    $sql = "SELECT * FROM wp_woocommerce_order_items WHERE order_id = '$ontour_bid' && order_item_name = 'Videoprojekt'";
    $result = $conn->query($sql);

    foreach ($result as $row) {


        $order_item_id = $row["order_item_id"];

        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'anreise'";
        $result = $conn->query($sql);
        $arr = $result->fetch_assoc();

        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'abreise'";
        $result = $conn->query($sql);
        $dep = $result->fetch_assoc();

        echo $arr['meta_value'];

        $arrival_date   = new DateTime($arr['meta_value']);
        $arrival_date = $arrival_date->format('Y-m-d H:i:s');
        $arrival_date = new DateTime($arrival_date);

        $departure_date   = new DateTime($dep['meta_value']);
        $departure_date = $departure_date->format('Y-m-d H:i:s');
        $departure_date = new DateTime($departure_date);


    }

    $data = new stdClass();
    $data->id = $ontour_bid;
    $data->arrival = $arr['meta_value'];
    $data->departure = $dep['meta_value'];

    $DB->update_record('booking_data3', $data);

    $sql = "SELECT * FROM mdl_booking_mails WHERE short_code = ".$operator->id;
    $mailings = $DB->get_record_sql($sql);


    $sql = "SELECT DISTINCT b_id FROM {booking_history3} WHERE type = 'mail_co' && state = 2 && b_id = '$ontour_bid'";
    $checker = $DB->get_record_sql($sql);

    if(!$checker){

        /* Z-Code Mail */
        $obj = new stdClass();
        $obj->b_id = $ontour_bid;
        $obj->user_id = $user;
        $obj->receiver = "";
        $obj->sender = "";
        $obj->type = "mail_co";
        $obj->subject = "Z-Codes";
        $obj->state = 2;

        if($operator->id != 1){
            $today   = $arrival_date;
            $today->modify('-'.$mailings->codes.' day');
            $today->modify('+9 hour');
            $today = $today->format('Y-m-d H:i:s');

            $now = new DateTime();
            $now->modify('+1 hour');
            $now = $now->format('Y-m-d H:i:s');

            if($today < $now){
                $obj->action_date = $now;
            }else{
                $obj->action_date = $today;
            }


        }else{
            $today = new DateTime();
            $today->modify('+1 hour');
            $obj->action_date =  $today->format('Y-m-d H:i:s');
        }

        $DB->insert_record('booking_history3', $obj);

    }



    /* Erinnerung 1 */


    $obj = new stdClass();
    $obj->b_id = $ontour_bid;
    $obj->user_id = $user;
    $obj->receiver = "";
    $obj->sender = "";
    $obj->type = "mail_er1";
    $obj->subject = "Erinnerung 1";
    $obj->state = 2;
    $today   = $arrival_date;
    $today->modify('-'.$mailings->erinnerung_1.' day');
    $today->modify('+9 hour');
    $obj->action_date =  $today->format('Y-m-d H:i:s');
    $DB->insert_record('booking_history3', $obj);


    /* Erinnerung 2 */


    $obj = new stdClass();
    $obj->b_id = $ontour_bid;
    $obj->user_id = $user;
    $obj->receiver = "";
    $obj->sender = "";
    $obj->type = "mail_er2";
    $obj->subject = "Erinnerung 2";
    $obj->state = 2;
    $today   = $arrival_date;
    $today->modify('-'.$mailings->erinnerung_2.' day');
    $today->modify('+9 hour');
    $obj->action_date =  $today->format('Y-m-d H:i:s');
    $DB->insert_record('booking_history3', $obj);


    /* Motivation Mail */


    $obj = new stdClass();
    $obj->b_id = $ontour_bid;
    $obj->user_id = $user;
    $obj->receiver = "";
    $obj->sender = "";
    $obj->type = "mail_mo";
    $obj->subject = "Motivation";
    $obj->state = 2;
    $today   = $arrival_date;
    $today->modify('-'.$mailings->motivation.' day');
    $today->modify('+9 hour');
    $obj->action_date =  $today->format('Y-m-d H:i:s');
    $DB->insert_record('booking_history3', $obj);



    /* Finishing Mail */


    $obj = new stdClass();
    $obj->b_id = $ontour_bid;
    $obj->user_id = $user;
    $obj->receiver = "";
    $obj->sender = "";
    $obj->type = "mail_fi";
    $obj->subject = "Film verbessern";
    $obj->state = 0;
    $today   = $departure_date;
    $today->modify('+'.$operator->mailing_4.' day');
    $today->modify('+9 hour');
    $obj->action_date =  $today->format('Y-m-d H:i:s');
    $DB->insert_record('booking_history3', $obj);



}

function checkBookingsCompany(){
    global $DB;

    $course = 10;
    $sql = "SELECT * FROM {user} WHERE address != ''";
    $users = $DB->get_records_sql($sql);

    if ($users) {
        foreach ($users as $user) { //only return enrolled users.
            if(!empty($user->address)){

                $order_id = $groups_tmp = explode("-" ,$user->phone1);
                $order_id = $order_id[0];

                //new direct bookings have vcodes with them
                if (strpos($user->phone1, '-') !== false) {

                    $pieces = explode("-vc-", $user->phone1);
                    $direct_codes = $pieces[1];
                    $direct_codes = explode(",", $direct_codes);

                    $op_pieces = explode("-", $direct_codes[0]);
                    $operator = $op_pieces[0];
                    $sql = "SELECT mailing FROM mdl_ext_operators1 WHERE short = '$operator'";
                    $mailing = $DB->get_record_sql($sql);

                    $sql = "SELECT id FROM mdl_ext_operators1 WHERE short = '$operator'";
                    $op_id = $DB->get_record_sql($sql);

                }

                $groups_tmp = explode("," ,$user->address);
                $k = 0;
                $z = 0;

                mkdir("/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/".$order_id, 0777);

                foreach ($groups_tmp as $group_tmp){



                    $group_tmp = str_replace(' ', '_', $group_tmp); // Replaces all spaces with hyphens.
                    $remove = array("@", "#", "(", ")", "*", "/", "&");
                    $group_tmp = str_replace($remove, "", $group_tmp);
                    $group_tmp = str_replace(".","_",$group_tmp);

                    if($group_tmp != ""){

                        $users = null;

                        //create groups
                        $obj = new stdClass();
                        $obj->courseid = $course;
                        $obj->name = $order_id."-".$group_tmp;
                        $obj->description = $order_id;
                        $group_id = $DB->insert_record('groups', $obj, true, false);

                        //create v-code user
                        $obj = new stdClass();
                        $obj->auth = "manual";
                        $obj->confirmed = 1;
                        $obj->username =  $direct_codes[$z];
                        $obj->password = password_hash($direct_codes[$z], PASSWORD_DEFAULT);
                        $obj->firstname = $group_tmp;
                        $obj->lastname = " ";
                        $obj->email = $obj->username."@ontour.org";
                        $obj->timecreated = time();
                        $obj->timemodified = time();
                        $obj->institution = "";
                        $obj->mnethostid = 1;
                        $obj->lang = "de";
                        $obj->idnumber = "new";
                        $obj->imagealt = "1";
                        $obj->phone1 = $order_id;
                        $obj->phone2 = $group_tmp;
                        $obj->department = "";
                        $user_v_code = $DB->insert_record('user', $obj, true, false);

                        $servername = "localhost";
                        $username = "skuehn22";
                        $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
                        $dbname = "projektreisenWordpress_1637922561";

                        $conn = new mysqli($servername, $username, $password, $dbname);
                        $sql = "SELECT * FROM wp_postmeta WHERE post_id = '$order_id' && meta_key = 'schulname'";
                        $result = $conn->query($sql);
                        $school = $result->fetch_assoc();


                        $obj->classname = $group_tmp;
                        $obj->school = $school['meta_value'];
                        $obj->order_id = $order_id;
                        $obj->operators_id = $op_id->id;
                        $obj->user_id = $user_v_code;
                        $obj->mailing = $mailing->mailing;
                        $obj->newsletter = 1;
                        $DB->insert_record('booking_data3', $obj);

                        //enrol v-code to course
                        $obj = new stdClass();
                        $obj->enrolid = "19";
                        $obj->userid = $user_v_code;
                        $obj->timestart = time();
                        $obj->timecreated = time();
                        $obj->timemodified = time();
                        $obj->modifierid = "2";
                        $obj->timestart = "0";
                        $obj->timeend = "0";
                        $DB->insert_record('user_enrolments', $obj, true, false);

                        //assign v-code  to the created group
                        $obj = new stdClass();
                        $obj->groupid = $group_id;
                        $obj->userid = $user_v_code;
                        $obj->timeadded = time();
                        $DB->insert_record('groups_members', $obj, true, false);
                        //$teacher_id = $userid;


                        //assign teacher to the created group
                        $obj = new stdClass();
                        $obj->groupid = $group_id;
                        $obj->userid = $user->id;
                        $obj->timeadded = time();
                        $group_members_id = $DB->insert_record('groups_members', $obj, true, false);
                        $teacher_id = $user->id;

                        $users_app = [];
                        $pdf_pages = [];

                        //create 5 random users which will be used as students group accounts
                        for ($i = 1; $i <= 6; $i++) {

                            $obj = new stdClass();
                            $obj->auth = "manual";
                            $obj->confirmed = 1;
                            $obj->username = rand(1,999);
                            $obj->username = "g".$i."_".$obj->username."_".$order_id."_".$group_tmp;
                            $obj->password = password_hash($obj->username, PASSWORD_DEFAULT);
                            $obj->firstname = "Gruppe";
                            $obj->lastname = $i."_".$group_tmp;
                            $obj->email = $obj->username."@projektreisen.de";
                            $obj->department = $user->department;
                            $obj->timecreated = time();
                            $obj->timemodified = time();

                            if($i == 6){
                                $obj->institution = "teacher_task";
                            }

                            $obj->mnethostid = 1;
                            $obj->lang = "de";
                            $obj->idnumber = "dev_restriction";
                            $obj->phone2 = "Gruppe_".$i;
                            $obj->department = "student_restriction";
                            $users_app[] = $DB->insert_record('user', $obj, true, false);

                            //CREATE PDF
                            $pdf_name = $order_id."-Gruppe".$i."-Klasse".$group_tmp."-App-Anmeldedaten-".$user->city.".pdf";
                            $content = createPDFCompany($i, $group_tmp, $obj->username,$order_id);
                            $pdf_pages[] = $content;

                            global $CFG;
                            require_once($CFG->libdir.'/pdflib.php');
                            $pdf = new pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                            $pdf->AddPage();
                            $pdf->writeHTMLCell(0, 0, '', '', $content, 0, 0, 0, true, '', true);
                            ob_clean();


                            if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id)) {
                                mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id, 0777, true);
                            }

                            if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp)) {
                                mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp, 0777, true);
                            }





                            $file = $pdf->Output('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp.'/'.$pdf_name, 'F');

                        }

                        $pdf_name = $order_id."-Alle-Accounts".$group_tmp."-App-Anmeldedaten-".$user->city.".pdf";
                        $pdf = new pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                        foreach ($pdf_pages as $page){

                            $pdf->AddPage();
                            $pdf->writeHTMLCell(0, 0, '', '', $page, 0, 0, 0, true, '', true);

                        }
                        ob_clean();
                        $file = $pdf->Output('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp.'/'.$pdf_name, 'F');

                        foreach ($users_app as $user_app){

                            // assign student to group
                            $obj = new stdClass();
                            $obj->groupid = $group_id;
                            $obj->userid = $user_app;
                            $obj->timeadded = time();
                            $group_members_id = $DB->insert_record('groups_members', $obj, true, false);

                            //enrol user to course
                            $obj = new stdClass();
                            $obj->enrolid = "19";
                            $obj->userid = $user_app;
                            $obj->timestart = time();
                            $obj->timecreated = time();
                            $obj->timemodified = time();
                            $group_members_id = $DB->insert_record('user_enrolments', $obj, true, false);

                            //assign student role
                            $obj = new stdClass();
                            $obj->roleid = "5";
                            $obj->contextid = "233";
                            $obj->userid = $user_app;
                            $obj->modifierid = "2";
                            $obj->timemodified = "1647872831";
                            $group_members_id = $DB->insert_record('role_assignments', $obj, true, false);
                        }


                        //assign the assignments to the new users
                        //find context id -> combines system / course cat / course / and its own Id
                        $sql = "SELECT * FROM {context} WHERE instanceid = :id && contextlevel = 50";
                        $context_course = $DB->get_record_sql($sql, array('id'=>$course));
                        $path = "%".$context_course->path."%";

                        $sql = "SELECT * FROM {context} WHERE path LIKE :path && contextlevel = 70";
                        $context_module = $DB->get_records_sql($sql, array('path'=>$path));

                        foreach ($users_app as $user_app){
                            foreach($context_module as $module){
                                // set role for student
                                $ra = new stdClass();
                                $ra->roleid       = "5";
                                $ra->contextid    = $module->id;
                                $ra->userid       = $user_app;
                                $ra->timemodified = time();
                                $ra->id = $DB->insert_record('role_assignments', $ra);
                            }
                        }


                    }


                    $z++;
                }


                $user_update = new stdClass();
                $user_update->id = $user->id;
                $user_update->address = "";
                $DB->update_record('user', $user_update);

            }
        }
    }
}

function setFilmReady()
{

    global $DB;

    $sql = "SELECT * FROM {booking_data3} WHERE state = 0";
    $bookings = $DB->get_records_sql($sql);

    foreach ($bookings as $booking){

        if($booking->arrival){
            if($booking->arrival != "" && $booking->arrival != "x"){


                $depStr = $booking->departure;
                $dep = new DateTime($depStr);
                $end = $dep->add(new DateInterval('P14D'));
                $now1 = new DateTime();

                if($end < $now1){

                    $sql = "SELECT * FROM mdl_booking_history3 WHERE user_id = $booking->user_id && type = 'film_production'";
                    $mail_ready_movie = $DB->get_record_sql($sql);

                    if($mail_ready_movie && ($mail_ready_movie->state == 1 || $mail_ready_movie->state == 4 || $mail_ready_movie->state == 5 || $mail_ready_movie->state == 6)){


                    }else{

                        if($booking->order_id == 9023){

                        }else{
                            $sql = "SELECT * FROM mdl_booking_history3 WHERE user_id = $booking->user_id && type = 'register' && state = 1";
                            $register = $DB->get_record_sql($sql);

                            if($register){

                                $sql = "SELECT * FROM {groups_members} WHERE userid = ".$booking->user_id;
                                $groupid = $DB->get_record_sql($sql);



                                $sql = "SELECT * FROM {groups_members} WHERE groupid = ".$groupid->groupid;
                                $groups_members = $DB->get_records_sql($sql);




                                $found = false;

                                foreach($groups_members as $member) {

                                    echo $groupid->groupid;

                                    $sql = "SELECT * FROM {assign_submission} WHERE assignment = '49' && userid = ".$member->userid;
                                    $task1 = $DB->get_record_sql($sql);

                                    if($task1){
                                        $found = true;
                                    }

                                }

                                /*

                                if($found){
                                    $data = new stdClass();
                                    $data->user_id = $booking->user_id;
                                    $data->b_id = $booking->order_id;
                                    $data->state = 4;
                                    $data->subject = "Film automatisch freigegeben";
                                    $data->type = 'film_production';
                                    $DB->insert_record('booking_history3', $data);
                                }else{
                                    $data = new stdClass();
                                    $data->user_id = $booking->user_id;
                                    $data->b_id = $booking->order_id;
                                    $data->state = 6;
                                    $data->subject = "Film-Check: Keine Abgaben";
                                    $data->type = 'film_production';
                                    $DB->insert_record('booking_history3', $data);
                                }

                                */



                            }else{

                                /*

                                $data = new stdClass();
                                $data->user_id = $booking->user_id;
                                $data->b_id = $booking->order_id;
                                $data->state = 5;
                                $data->subject = "Film-Check: Keine Registrierung";
                                $data->type = 'film_production';
                                $DB->insert_record('booking_history3', $data);

                                */
                            }
                        }
                    }
                }

            }
        }

    }

}

function fillBookingTable($oid, $operator, $user_id, $mailing){
    $obj = new stdClass();
    $obj->order_id = $oid;
    $obj->operators_id = $operator;
    $obj->user_id = $user_id;
    $obj->mailing = $mailing->mailing;
    $DB->insert_record('bookings', $obj);
}



function createPDF($group, $class, $username, $order, $imagePath){

    $html = '
	<style>
	ul {
  list-style: none;
}

li::before {
  content: "• ";
  color: red;
}
	table, tr, td {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11px;
       
	}
	
	p{
	 border-radius: 6px;
	 -webkit-border-top-right-radius: 4px;
  -webkit-border-bottom-right-radius: 4px;
  -webkit-border-bottom-left-radius:6px;
  -webkit-border-top-left-radius: 6px;
  -moz-border-radius-topright: 4px;
  -moz-border-radius-bottomright: 4px;
  -moz-border-radius-bottomleft: 6px;
  -moz-border-radius-topleft: 6px;
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
  border-bottom-left-radius:6px;
  border-top-left-radius:6px;
	}
	
	</style>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff;">
	<tbody>
	<tr>
	<td><img src="/videoproject.png" style="width: 130px;"></td>
	<td align="right">
		
	</td>
	
	</tr>
	</tbody>
	</table>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff; margin-top: -25px;">
	<tbody>
	<tr>
	<td></td>
	<td align="right">
			<strong>Buchungsnummer: '.$order.'</strong>
	</td>
	
	</tr>
	<tr>
	<td colspan="2" style="text-align: center">
	    <h1>Login-Daten / Web-APP</h1>
	    <p style="font-size: 16px;">'.$class.' Gruppe '.$group.'</p>
    </td>
    </tr>
	</tbody>
	</table>
	';


    $html .= '
    <table style="width: 560px;">
    <tbody>
    <tr>
	    <td style="font-size: 16px; width:45%;">
	       <h1>1) Web-App aufrufen</h1>
	    </td>
        <td align="right">
	
        </td>
	</tr>
	 <tr>
	    <td style="font-size: 16px; width:45%;">
	       <span style="font-size: 12px">QR-Code für <b>Web-App</b></span><br>
	    </td>
        <td>
	
        </td>
	</tr>
    <tr>
        <td style="font-size: 16px; width:45%; vertical-align: top;">
            <img src="/filemanager/zugangsdaten/webapp.png" style="width: 150px;"><br>
        </td>
        <td style="vertical-align: top;">
            <table>
                <tr>
                    <td colspan="2">
                        <p><b>ODER</b></p>
                        <p><a href="app.ontour.org" style="color: #009BD5; font-weight: 700"><b>app.ontour.org</b></a> in den Browser eingeben.</p>
                        <p><i>Hinweis: Für die Nutzung ist kein Download im App- oder Playstore notwendig. Die Web-App läuft über jeden Browser.</i></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

	<tr>
        <td colspan="2"><hr></td>
    </tr>
    <tr>
	    <td style="font-size: 16px; width:45%;">
	       <h1>2) Einloggen</h1>
	    </td>
        <td align="right">
	
        </td>
	</tr>
	 <tr>
	    <td style="font-size: 16px; width:45%;">
	       <span style="font-size: 12px">QR-Code für <b>Gruppe '.$group.'</b></span><br>
	    </td>
        <td>
	
        </td>
	</tr>
	<tr>
	    <td style="font-size: 16px; width:45%;">
	        <img src ="/filemanager/zugangsdaten/'.$imagePath.'" style="width: 150px;"><br>
	    </td>
        <td>
               <table>
                   <tr>
                        <td>
                            <p><b>ODER</b></p>
                            <p><strong>Benutzername</strong></p>
                       
                        </td>
                        <td>
                              <p> </p>
                              <p>'.$username.'</p>
                         
                        </td>
                   </tr>
                 
                   
               </table>
        </td>
	</tr>
	<tr>
	    <td colspan="2" align="right" style="text-align: right;">
	        <br><br><img src="/filemanager/zugangsdaten/logo.png" style="width: 100px;">
        </td>
    </tr>
	</tbody>
	</table>
	';


    return $html;

}
function createPDFTeacher($group, $class, $username, $order, $imagePath){

    $html = '
	<style>
	ul {
  list-style: none;
}

li::before {
  content: "• ";
  color: red;
}
	table, tr, td {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11px;
       
	}
	
	p{
	 border-radius: 6px;
	 -webkit-border-top-right-radius: 4px;
  -webkit-border-bottom-right-radius: 4px;
  -webkit-border-bottom-left-radius:6px;
  -webkit-border-top-left-radius: 6px;
  -moz-border-radius-topright: 4px;
  -moz-border-radius-bottomright: 4px;
  -moz-border-radius-bottomleft: 6px;
  -moz-border-radius-topleft: 6px;
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
  border-bottom-left-radius:6px;
  border-top-left-radius:6px;
	}
	
	</style>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff;">
	<tbody>
	<tr>
	<td><img src="/videoproject.png" style="width: 130px;"></td>
	<td align="right">
		
	</td>
	
	</tr>
	</tbody>
	</table>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff; margin-top: -25px;">
	<tbody>
	<tr>
	<td></td>
	<td align="right">
			<strong>Buchungsnummer: '.$order.'</strong>
	</td>
	
	</tr>
	<tr>
	<td colspan="2" style="text-align: center">
	    <h1>Login-Daten / Web-APP</h1>
	    <p style="font-size: 16px;">Lehrkräfte</p>
    </td>
    </tr>
	</tbody>
	</table>
	';


    $html .= '
    <table style="width: 560px;">
    <tbody>
    <tr>
	    <td style="font-size: 16px; width:45%;">
	       <h1>1) Web-App aufrufen</h1>
	    </td>
        <td align="right">
	
        </td>
	</tr>
	 <tr>
	    <td style="font-size: 16px; width:45%;">
	       <span style="font-size: 12px">QR-Code für <b>Web-App</b></span><br>
	    </td>
        <td>
	
        </td>
	</tr>
    <tr>
        <td style="font-size: 16px; width:45%; vertical-align: top;">
            <img src="/filemanager/zugangsdaten/webapp.png" style="width: 150px;"><br>
        </td>
        <td style="vertical-align: top;">
            <table>
                <tr>
                    <td colspan="2">
                        <p><b>ODER</b></p>
                        <p><a href="app.ontour.org" style="color: #009BD5; font-weight: 700"><b>app.ontour.org</b></a> in den Browser eingeben.</p>
                        <p><i>Hinweis: Für die Nutzung ist kein Download im App- oder Playstore notwendig. Die Web-App läuft über jeden Browser.</i></p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

	<tr>
        <td colspan="2"><hr></td>
    </tr>
    <tr>
	    <td style="font-size: 16px; width:45%;">
	       <h1>2) Einloggen</h1>
	    </td>
        <td align="right">
	
        </td>
	</tr>
	 <tr>
	    <td style="font-size: 16px; width:45%;">
	       <span style="font-size: 12px">QR-Code für die <b>Lehrkräfte</b></span><br>
	    </td>
        <td>
	
        </td>
	</tr>
	<tr>
	    <td style="font-size: 16px; width:45%;">
	        <img src ="/filemanager/zugangsdaten/'.$imagePath.'" style="width: 150px;"><br>
	    </td>
        <td>
               <table>
                   <tr>
                        <td>
                            <p><b>ODER</b></p>
                            <p><strong>Benutzername</strong></p>
                         
                        </td>
                        <td>
                              <p> </p>
                              <p>'.$username.'</p>
                         
                        </td>
                   </tr>
                 
                   
               </table>
        </td>
	</tr>
	<tr>
	    <td colspan="2" align="right" style="text-align: right;">
	        <br><br><img src="/filemanager/zugangsdaten/logo.png" style="width: 100px;">
        </td>
    </tr>
	</tbody>
	</table>
	';


    return $html;

}

function createPDFCompany($group, $class, $username, $order){

    $html = '
	<style>
	ul {
  list-style: none;
}

li::before {
  content: "• ";
  color: red;
}
	table, tr, td {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11px;
       
	}
	
	p{
	 border-radius: 6px;
	 -webkit-border-top-right-radius: 4px;
  -webkit-border-bottom-right-radius: 4px;
  -webkit-border-bottom-left-radius:6px;
  -webkit-border-top-left-radius: 6px;
  -moz-border-radius-topright: 4px;
  -moz-border-radius-bottomright: 4px;
  -moz-border-radius-bottomleft: 6px;
  -moz-border-radius-topleft: 6px;
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
  border-bottom-left-radius:6px;
  border-top-left-radius:6px;
	}
	
	</style>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff;">
	<tbody>
	<tr>
	<td><img src="https://ontour.org/wp-content/uploads/2021/12/Logo-onTour-1.png" style="width: 100px;"></td>
	<td align="right">
		
	</td>
	
	</tr>
	</tbody>
	</table>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff; margin-top: -25px;">
	<tbody>
	<tr>
	<td></td>
	<td align="right">
			<strong>Buchungsnummer: '.$order.'</strong>
	</td>
	
	</tr>
	</tbody>
	</table>
	';
    $html .= '
	<table style="width: 1200px;">
	<tbody>
	<tr>
	    <td style="font-size: 16px; width:45%;">
	        <br><br><br><strong>Gruppe '.$group.' Team: </strong><span style="color: #49AEDB">'.$class.'</span><br/>
	    </td>
        <td align="right">
	
        </td>
	</tr>
	<tr><td><strong><u>Login für die Teilnehmer*innen</u></strong>
	    <br><br>
	    <table>
             <tr><td style="width: 100px;"><strong>Benutzername:</strong> </td>
                <td> '.$username.'</td>
            </tr>
              <tr> <td><strong>Passwort:</strong> </td>
                <td> '.$username.'</td>
            </tr>
        </table>
	    
	    <br>
	  
	    <p>Benutzername und Passwort sind identisch</p>
	</td>
	<td></td>
    </tr>
    
    <tr>
    <td style="font-size: 16px;"><br><br><strong>App herunterladen</strong></td>
    <td></td>
    </tr>
    <tr>
         <td style="width: 45%">
         ';

    /*
        <p style="border: 1px solid #49AEDB; border-radius: 6px; ">
            <br><br>
                   <span style="color: #49AEDB; padding-top: 15px;"><br>  Achtung:</span><br>
        Aktuell ist die App für das Videoprojekt Berlin leider <strong>nur mit iPhones nutzbar.</strong>
                    <br>  Bitte wendet Euch an Eure Lehrkraft, falls in Eurer Gruppe niemand ein I-Phone hat.

            </p>
    */

    $html .= '
         
          
            <ul style="line-height: 25px; margin-top: -25px;">
                <li>Gehen Sie in den Appstore und gebebn Sie mit I-Phones "onTour Reisen" und mit anderen Handytypen "onTour" ein. Sie müssen etwas scrollen.</li>
                <li>Nach dem Download die App öffnen und die Logindaten eingeben. </li>
            </ul>
            <br>
            <img src="https://reisen.ontour.org/downloads/app_store_1.png" style="width: 220px;">
            <br><br><br><br>
            <strong><u>Bitte beachten Sie</u></strong>
             <ul style="line-height: 25px; margin-top: -30px;">
                <li style="color: red;"><span style="color: #000;">Mindestens eine Person pro Gruppe muss die App in Berlin auf dem Handy heruntergeladen haben.</span></li>
                <li>Sie  brauchen vor Ort für die App etwas Datenvolumen und natürlich einen geladenen Akku.</li>
                <li>Bitte schließen Sie und öffnen Sie die App vor der Nutzung in Berlin noch einmal, damit Sie die aktuelle Version haben. </li>
            </ul>
        </td>
		<td></td>
    </tr>
	</tbody>
	</table>
	';


    return $html;

}


function createPDFLeiter($group, $class, $username, $order){

    $html = '
	<style>
	ul {
  list-style: none;
}

li::before {
  content: "• ";
  color: red;
}
	table, tr, td {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11px;
       
	}
	
	p{
	 border-radius: 6px;
	 -webkit-border-top-right-radius: 4px;
  -webkit-border-bottom-right-radius: 4px;
  -webkit-border-bottom-left-radius:6px;
  -webkit-border-top-left-radius: 6px;
  -moz-border-radius-topright: 4px;
  -moz-border-radius-bottomright: 4px;
  -moz-border-radius-bottomleft: 6px;
  -moz-border-radius-topleft: 6px;
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
  border-bottom-left-radius:6px;
  border-top-left-radius:6px;
	}
	
	</style>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff;">
	<tbody>
	<tr>
	<td><img src="https://ontour.org/wp-content/uploads/2021/12/Logo-onTour-1.png" style="width: 100px;"></td>
	<td align="right">

	</td>
	
	</tr>
	</tbody>
	</table>
	<table style="background-color: #fff; color: #000; border: 2px solid #fff; margin-top: -25px;">
	<tbody>
	<tr>
	<td></td>
	<td align="right">
			<strong>Buchungsnummer: '.$order.'</strong>
	</td>
	
	</tr>
	</tbody>
	</table>
	';
    $html .= '
	<table style="width: 1200px;">
	<tbody>
	<tr>
	    <td style="font-size: 16px;">
	        <br><br><br><strong>Gruppe Spielleiter*innen: </strong><span style="color: #49AEDB">'.$class.'</span><br/>
	    </td>
        <td align="right">
        </td>
	</tr>
	<tr><td><strong><u>Login für Spielleiter*innen</u></strong>
	    <br><br>
	    <table>
            <tr><td style="width: 100px;"><strong>Benutzername:</strong> </td>
                <td> '.$username.'</td>
            </tr>
              <tr> <td><strong>Passwort:</strong> </td>
                <td> '.$username.'</td>
            </tr>
        </table>
	    
	    <br>
	  
	    <p>Benutzername und Passwort sind identisch</p>
	</td>
	<td></td>
    </tr>
    
    <tr>
    <td style="font-size: 16px;"><br><br><strong>App herunterladen</strong></td>
    <td></td>
    </tr>
    <tr>
        <td style="width: 45%">
        ';


    /*
        <p style="border: 1px solid #49AEDB; border-radius: 6px;">
        <br><br>
               <span style="color: #49AEDB; padding-top: 15px;"><br>  Achtung:</span><br>
                Aktuell ist die App für das Videoprojekt Berlin leider <strong>nur mit iPhones nutzbar.</strong>
                <br>  Sollte eine Gruppe kein iPhone haben, schreiben Sie uns bitte bis spätestens 3 Tage vor Durchführung <br>  in Berlin eine E-Mail: <a href="mailto:info@ontour.org">info@ontour.org</a> Wir schicken Ihnen dann eine Alternativvariante zu.

        </p>

    */

    $html .= '
            <ul style="line-height: 17px; margin-top: -25px;">
                <li>Gehen Sie in den Appstore und geben mit I-Phones "onTour Reisen" und mit anderen Handytypen "onTour" ein. Sie müssen etwas scrollen.</li>
                <li>Nach dem Download die App öffnen und die <strong>Logindaten</strong> eingeben. </li>
            </ul>
            <br>
            <img src="https://reisen.ontour.org/downloads/app_store_1.png" style="width: 220px;">
            <br><br><br><br>
            <strong><u>Bitte achten Sie als Lehrkraft darauf</u></strong>
             <ul style="line-height: 17px; margin-top: -30px;">
                <li style="color: red;"><span style="color: #000;">Mindestens ein/e Schüler*in pro Gruppe muss die App in Berlin auf dem Handy heruntergeladen haben.</span></li>
                <li>Es wird etwas Datenvolumen und ein geladener Akku vor Ort benötigt.</li>
                <li>Die App muss vor Durchführung geschlossen und wieder geöffnet werden, damit die aktuelle Version genutzt wird.</li>
                <li>In Berlin wird das Videoprojekt Gruppenweise eigenständig durchgeführt. Die Gruppenbildung und Wahl des Drehortes erfolgen unter Ihrer Anleitung. </li>
            </ul>
        </td>
        <td></td>
    </tr>
	</tbody>
	</table>
	';


    return $html;

}
function getMonthNumber($monthStr) {
//e.g, $month='Jan' or 'January' or 'JAN' or 'JANUARY' or 'january' or 'jan'
    $m = ucfirst(strtolower(trim($monthStr)));
    switch ($m) {
        case "Januar":
        case "Jan":
            $m = "01";
            break;
        case "Februar":
        case "Feb":
            $m = "02";
            break;
        case "März":
        case "Mar":
            $m = "03";
            break;
        case "April":
        case "Apr":
            $m = "04";
            break;
        case "Mai":
            $m = "05";
            break;
        case "Juni":
        case "Jun":
            $m = "06";
            break;
        case "July":
        case "Jul":
            $m = "07";
            break;
        case "August":
        case "Aug":
            $m = "08";
            break;
        case "September":
        case "Sep":
            $m = "09";
            break;
        case "Oktober":
        case "Oct":
            $m = "10";
            break;
        case "November":
        case "Nov":
            $m = "11";
            break;
        case "Dezember":
        case "Dec":
            $m = "12";
            break;
        default:
            $m = false;
            break;
    }
    return $m;
}

function getData($b_id){

    $servername = "localhost";
    $username = "skuehn22";
    $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
    $dbname = "projektreisenWordpress_1637922561";

    $conn = new mysqli($servername, $username, $password, $dbname);


    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT * FROM wp_woocommerce_order_items WHERE order_id = '$b_id' && order_item_name = 'Videoprojekt'";
    $result = $conn->query($sql);
    $i = 0;
    $tax = 0;
    $line_total = 0;



    foreach ($result as $row){


        $order_item_id = $row["order_item_id"];

        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'anreise'";
        $result = $conn->query($sql);
        $arr = $result->fetch_assoc();

        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'abreise'";
        $result = $conn->query($sql);
        $dep = $result->fetch_assoc();

        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'alter_schuler'";
        $result = $conn->query($sql);
        $age = $result->fetch_assoc();

        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'anzahl_schuler'";
        $result = $conn->query($sql);
        $amount = $result->fetch_assoc();

        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'gruppenname'";
        $result = $conn->query($sql);
        $gruppe = $result->fetch_assoc();


        if (strpos($arr['meta_value'], '-') !== false) {
            $pieces = explode("-", $arr['meta_value']);
            $data[$i]['arr'] = $pieces[2].".".$pieces[1].".".$pieces[0];
        }else{
            $pieces = explode(" ", $arr['meta_value']);
            $m = getMonthNumber($pieces[0]);
            $pieces2 = explode(",", $pieces[1]);

            if(strlen($pieces2[0]) == 1){
                $day = "0".$pieces2[0];
            }else{
                $day = $pieces2[0];
            }

            $data[$i]['arr'] = $day.".".$m.".".$pieces[2];

        }

        if (strpos($dep['meta_value'], '-') !== false) {
            $pieces = explode("-", $dep['meta_value']);
            $data[$i]['dep'] = $pieces[2].".".$pieces[1].".".$pieces[0];
        }else{
            $pieces = explode(" ", $dep['meta_value']);
            $m = getMonthNumber($pieces[0]);
            $pieces2 = explode(",", $pieces[1]);

            if(strlen($pieces2[0]) == 1){
                $day = "0".$pieces2[0];
            }else{
                $day = $pieces2[0];
            }

            $data[$i]['dep'] = $day.".".$m.".".$pieces[2];

        }

        $data[$i]['age'] = $age['meta_value'];
        $data[$i]['amount'] = $amount['meta_value'];
        $data[$i]['gruppe'] = $gruppe['meta_value'];


        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = '_line_tax'";
        $result = $conn->query($sql);
        $tax_tmp = $result->fetch_assoc();

        $tax = $tax + $tax_tmp['meta_value'];


        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = '_line_total'";
        $result = $conn->query($sql);
        $line_tmp = $result->fetch_assoc();

        $data[$i]['item_price'] = number_format((float)$line_tmp['meta_value'], 2, '.', '');
        $line_total = $line_total + $line_tmp['meta_value'];

        $i++;

    }

    $data[0]['tax'] =  number_format((float)$tax, 2, '.', '');
    $data[0]['line_total'] =  number_format((float)$line_total, 2, '.', '');
    $data[0]['total'] = $data[0]['tax'] + $data[0]['line_total'];
    $data[0]['total'] =  number_format((float)$data[0]['total'], 2, '.', '');

    $sql = "SELECT * FROM wp_postmeta WHERE post_id = '$b_id'";
    $result = $conn->query($sql);


    while($row = $result->fetch_assoc()) {

        if($row['meta_key'] == '_billing_first_name'){
            $data[0]['firstname'] = $row['meta_value'];
        }

        if($row['meta_key'] == '_billing_last_name'){
            $data[0]['lastname'] = $row['meta_value'];
        }

        if($row['meta_key'] == '_billing_email'){
            $data[0]['email'] = $row['meta_value'];
        }

        if($row['meta_key'] == '_billing_company'){
            $data[0]['org'] = $row['meta_value'];
        }

        if($row['meta_key'] == '_billing_postcode'){
            $data[0]['zip'] = $row['meta_value'];
        }

        if($row['meta_key'] == '_billing_address_1'){
            $data[0]['addr'] = $row['meta_value'];
        }

        if($row['meta_key'] == '_billing_city'){
            $data[0]['city'] = $row['meta_value'];
        }

        if($row['meta_key'] == '_billing_country'){
            $data[0]['co'] = $row['meta_value'];
        }

    }

    return $data;

}

function get_z_codes($bookings){

    global $DB;
    $z_codes_string = '';

    /* generate z-codes table for mail text */
    foreach ($bookings as $booking){

        $sql = "SELECT * FROM mdl_user WHERE id = ".$booking->user_id;
        $b = $DB->get_record_sql($sql);

        $classname = $booking->classname;

        if($booking->classname == "Klasse 1"){
            $classname = "1";
        }

        if($booking->classname == "Klasse 2"){
            $classname = "2";
        }

        if($booking->classname == "Klasse 3"){
            $classname = "3";
        }

        if($booking->classname == "Klasse 4"){
            $classname = "4";
        }

        $z_codes_string .='<div style="display: flex;"><div style="width: 300px;" width="300">Zugangscodes Kundenbereich Klasse '.$classname.'</div><div><strong>'.$b->username.'</strong></div></div>';

    }

    return $z_codes_string;

}

function checkReminder2(){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_data3 WHERE state = 0 && reg_date IS NULL";
    $bookings = $DB->get_records_sql($sql);

    foreach ($bookings as $booking){

        $sql = "SELECT * FROM mdl_booking_mails WHERE short_code = ".$booking->operators_id;
        $mailings = $DB->get_record_sql($sql);

        $sql = "SELECT DISTINCT b_id FROM {booking_history3} WHERE type = 'mail_er1' && state = 2 && b_id = '$booking->order_id'";
        $checker = $DB->get_record_sql($sql);

        if(!$checker){
            $sql = "SELECT DISTINCT b_id FROM {booking_history3} WHERE type = 'mail_er1' && state = 1 && b_id = '$booking->order_id'";
            $checker = $DB->get_record_sql($sql);
        }

        if(!$checker){

            $servername = "localhost";
            $username = "skuehn22";
            $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
            $dbname = "projektreisenWordpress_1637922561";

            $conn = new mysqli($servername, $username, $password, $dbname);


            $sql = "SELECT * FROM wp_woocommerce_order_items WHERE order_id = '$booking->order_id' && order_item_name = 'Videoprojekt'";
            $result = $conn->query($sql);


            while($row = $result->fetch_assoc()) {

                $item_id = $row["order_item_id"];

                $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$item_id' && meta_key = 'gruppenname'";
                $result2 = $conn->query($sql);
                $group_name = $result2->fetch_assoc();

                if($group_name['meta_value'] == $booking->classname){
                    $right_item = $row;
                }

            }

            //$order_item = $result->fetch_assoc();
            $order_item_id = $right_item['order_item_id'];

            $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'anreise'";
            $result = $conn->query($sql);
            $arr = $result->fetch_assoc();



            if(strpos($arr['meta_value'], "-") !== false){

                $arrival_date   = new DateTime($arr['meta_value']);
                $arrival_date = $arrival_date->format('Y-m-d H:i:s');
                $arrival_date = new DateTime($arrival_date);

                $now = new DateTime();
                $now->modify('+1 hour');


                $days_till_arrival = $now->diff($arrival_date)->format("%r%a");


                //echo $booking->order_id."-".$arr['meta_value']."-".$days_till_arrival."<br>";

                if($days_till_arrival > 17){

                    /* Reminder 1 Mail */
                    $obj = new stdClass();
                    $obj->b_id = $booking->order_id;
                    $obj->user_id = $booking->user_id;
                    $obj->receiver = "";
                    $obj->sender = "";
                    $obj->type = "mail_er2";
                    $obj->subject = "Erinnerung 2";
                    $obj->state = 2;

                    $arrival_date->modify('-'.$mailings->erinnerung_2.' day');
                    $arrival_date->modify('+9 hour');
                    $arrival_date = $arrival_date->format('Y-m-d H:i:s');
                    $obj->action_date = $arrival_date;
                    $DB->insert_record('booking_history3', $obj);

                }

            }

        }


    }

}

function checkReminder1(){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_data3 WHERE state = 0 && reg_date IS NULL";
    $bookings = $DB->get_records_sql($sql);

    foreach ($bookings as $booking){

        $sql = "SELECT * FROM mdl_booking_mails WHERE short_code = ".$booking->operators_id;
        $mailings = $DB->get_record_sql($sql);

        $sql = "SELECT DISTINCT b_id FROM {booking_history3} WHERE type = 'mail_er1' && state = 2 && b_id = '$booking->order_id'";
        $checker = $DB->get_record_sql($sql);

        if(!$checker){
            $sql = "SELECT DISTINCT b_id FROM {booking_history3} WHERE type = 'mail_er1' && state = 1 && b_id = '$booking->order_id'";
            $checker = $DB->get_record_sql($sql);
        }

        if(!$checker){

            $servername = "localhost";
            $username = "skuehn22";
            $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
            $dbname = "projektreisenWordpress_1637922561";

            $conn = new mysqli($servername, $username, $password, $dbname);


            $sql = "SELECT * FROM wp_woocommerce_order_items WHERE order_id = '$booking->order_id' && order_item_name = 'Videoprojekt'";
            $result = $conn->query($sql);


            while($row = $result->fetch_assoc()) {

                $item_id = $row["order_item_id"];

                $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$item_id' && meta_key = 'gruppenname'";
                $result2 = $conn->query($sql);
                $group_name = $result2->fetch_assoc();

                if($group_name['meta_value'] == $booking->classname){
                    $right_item = $row;
                }

            }

            //$order_item = $result->fetch_assoc();
            $order_item_id = $right_item['order_item_id'];

            $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'anreise'";
            $result = $conn->query($sql);
            $arr = $result->fetch_assoc();



            if(strpos($arr['meta_value'], "-") !== false){

                $arrival_date   = new DateTime($arr['meta_value']);
                $arrival_date = $arrival_date->format('Y-m-d H:i:s');
                $arrival_date = new DateTime($arrival_date);

                $now = new DateTime();
                $now->modify('+1 hour');


                $days_till_arrival = $now->diff($arrival_date)->format("%r%a");


                //echo $booking->order_id."-".$arr['meta_value']."-".$days_till_arrival."<br>";

                if($days_till_arrival > 28){

                    /* Reminder 1 Mail */
                    $obj = new stdClass();
                    $obj->b_id = $booking->order_id;
                    $obj->user_id = $booking->user_id;
                    $obj->receiver = "";
                    $obj->sender = "";
                    $obj->type = "mail_er1";
                    $obj->subject = "Erinnerung 1";
                    $obj->state = 2;

                    $arrival_date->modify('-'.$mailings->erinnerung_1.' day');
                    $arrival_date->modify('+9 hour');
                    $arrival_date = $arrival_date->format('Y-m-d H:i:s');
                    $obj->action_date = $arrival_date;
                    $DB->insert_record('booking_history3', $obj);

                }

            }

        }


    }

}
function checkReminder3(){

    global $DB;

    $sql = "SELECT * FROM mdl_booking_data3 WHERE state = 0 && reg_date IS NULL";
    $bookings = $DB->get_records_sql($sql);

    foreach ($bookings as $booking){

        $sql = "SELECT * FROM mdl_booking_mails WHERE short_code = ".$booking->operators_id;
        $mailings = $DB->get_record_sql($sql);

        $sql = "SELECT DISTINCT b_id FROM {booking_history3} WHERE type = 'mail_re3' && state = 2 && b_id = '$booking->order_id'";
        $checker = $DB->get_record_sql($sql);

        if(!$checker){
            $sql = "SELECT DISTINCT b_id FROM {booking_history3} WHERE type = 'mail_re3' && state = 1 && b_id = '$booking->order_id'";
            $checker = $DB->get_record_sql($sql);
        }

        if(!$checker){

            $servername = "localhost";
            $username = "skuehn22";
            $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
            $dbname = "projektreisenWordpress_1637922561";

            $conn = new mysqli($servername, $username, $password, $dbname);


            $sql = "SELECT * FROM wp_woocommerce_order_items WHERE order_id = '$booking->order_id' && order_item_name = 'Videoprojekt'";
            $result = $conn->query($sql);


            while($row = $result->fetch_assoc()) {

                $item_id = $row["order_item_id"];

                $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$item_id' && meta_key = 'gruppenname'";
                $result2 = $conn->query($sql);
                $group_name = $result2->fetch_assoc();

                if($group_name['meta_value'] == $booking->classname){
                    $right_item = $row;
                }

            }

            //$order_item = $result->fetch_assoc();
            $order_item_id = $right_item['order_item_id'];

            $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'anreise'";
            $result = $conn->query($sql);
            $arr = $result->fetch_assoc();



            if(strpos($arr['meta_value'], "-") !== false){

                $arrival_date   = new DateTime($arr['meta_value']);
                $arrival_date = $arrival_date->format('Y-m-d H:i:s');
                $arrival_date = new DateTime($arrival_date);

                $now = new DateTime();
                $now->modify('+1 hour');


                $days_till_arrival = $now->diff($arrival_date)->format("%r%a");


                //echo $booking->order_id."-".$arr['meta_value']."-".$days_till_arrival."<br>";

                if($days_till_arrival > 14){

                    /* Reminder 1 Mail */
                    $obj = new stdClass();
                    $obj->b_id = $booking->order_id;
                    $obj->user_id = $booking->user_id;
                    $obj->receiver = "";
                    $obj->sender = "";
                    $obj->type = "mail_re3";
                    $obj->subject = "Reminder 3";
                    $obj->state = 2;

                    $arrival_date->modify('- 14 day');
                    $arrival_date->modify('+9 hour');
                    $arrival_date = $arrival_date->format('Y-m-d H:i:s');
                    $obj->action_date = $arrival_date;
                    $DB->insert_record('booking_history3', $obj);

                }

            }

        }


    }

}

function createAppOverview(){

    global $DB;

    $sql = "SELECT * FROM mdl_user WHERE phone1 <> '' && (middlename IS NULL || middlename = '')";
    $users = $DB->get_records_sql($sql);

    foreach ($users as $user) {
        $course = 8;
        $max_section = $DB->get_field_sql('SELECT MAX(section) FROM {course_sections}');
        $max_section = $max_section + 1;
        $time = time() + rand();

        // First add section to the end.
        $cw = new stdClass();
        $cw->course = $course;
        $cw->section = $max_section;
        $cw->summary = '<p dir="ltr" style="text-align: left; font-size:14px!important;"><strong>Gruppe 1</strong></p><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 1 Teamname</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important" id="group1_task1"><span style="color: red;">nein</span></div>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 2 Foto</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 3 Film</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 4 Audio</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 5 Safari</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 6 Outtakes</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><p dir="ltr" style="text-align: left; font-size:14px!important;"><strong>Gruppe 2</strong></p><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 1 Teamname</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important" id="group1_task1"><span style="color: red;">nein</span></div>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 2 Foto</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 3 Film</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 4 Audio</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 5 Safari</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 6 Outtakes</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><p dir="ltr" style="text-align: left; font-size:14px!important;"><strong>Gruppe 3</strong></p><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 1 Teamname</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important" id="group1_task1"><span style="color: red;">nein</span></div>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 2 Foto</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 3 Film</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 4 Audio</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 5 Safari</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 6 Outtakes</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><p dir="ltr" style="text-align: left; font-size:14px!important;"><strong>Gruppe 4</strong></p><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 1 Teamname</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important" id="group1_task1"><span style="color: red;">nein</span></div>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 2 Foto</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 3 Film</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 4 Audio</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 5 Safari</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 6 Outtakes</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><p dir="ltr" style="text-align: left; font-size:14px!important;"><strong>Gruppe 5</strong></p><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 1 Teamname</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important" id="group1_task1"><span style="color: red;">nein</span></div>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 2 Foto</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 3 Film</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 4 Audio</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 5 Safari</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 6 Outtakes</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><hr><p dir="ltr" style="text-align: left; font-size:14px!important;"><strong>Lehrkräfte</strong></p><div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div><p dir="ltr" style="text-align: left; font-size:14px!important;"><strong></strong></p>';
        $cw->summaryformat = FORMAT_HTML;
        $cw->sequence = '';
        $cw->name = 'Übersicht Schüler Abgaben';
        $cw->visible = 1;
        $cw->availability = '{"op":"&","c":[{"type":"profile","sf":"idnumber","op":"isequalto","v":"' . $time . '"}],"showc":[true]}';
        $cw->timemodified = time();
        $cw->id = $DB->insert_record("course_sections", $cw);

        $data = new stdClass();
        $data->id = $user->id;
        $data->middlename = $cw->id;

        $DB->update_record('user', $data);

        $data = new stdClass();
        $data->id = $user->id + 6;
        $data->middlename = $cw->id;
        $data->idnumber = $time;
        $DB->update_record('user', $data);
    }

    }
    function filmProduction(){

        global $DB;

        $sql = "SELECT * FROM mdl_booking_history3 WHERE state= 2 && type='film_production'";
        $history_productions = $DB->get_records_sql($sql);

        foreach ($history_productions as $history_production){

            $sql = "SELECT * FROM mdl_user WHERE id = 6163";
            $from_user = $DB->get_record_sql($sql);

            $email_subject = "Film wurde freigegeben - ".$history_production->b_id;
            $message = "Der Film wurde soeben freigegeben.";

            email_to_user($from_user, $from_user, $email_subject, $message);

            $data = new stdClass();
            $data->id = $history_production->id;
            $data->state = 1;
            $DB->update_record('booking_history3', $data);

        



    }


}


function checkBookings_direct(){
    global $DB;

    $course = 8;
    $sql = "SELECT * FROM {user} WHERE address != ''";
    $users = $DB->get_records_sql($sql);

    if ($users) {
        foreach ($users as $user) { //only return enrolled users.
            if(!empty($user->address)){

                $order_id = $groups_tmp = explode("-" ,$user->phone1);
                $order_id = $order_id[0];

                //new direct bookings have vcodes with them
                if (strpos($user->phone1, '-') !== false) {

                    $pieces = explode("-vc-", $user->phone1);
                    $direct_codes = $pieces[1];
                    $direct_codes = explode(",", $direct_codes);

                    $op_pieces = explode("-", $direct_codes[0]);
                    $operator = $op_pieces[0];
                    $sql = "SELECT mailing FROM mdl_ext_operators1 WHERE short = '$operator'";
                    $mailing = $DB->get_record_sql($sql);

                    $sql = "SELECT id FROM mdl_ext_operators1 WHERE short = '$operator'";
                    $op_id = $DB->get_record_sql($sql);

                }

                $groups_tmp = explode("," ,$user->address);
                $k = 0;
                $z = 0;

                mkdir("/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/".$order_id, 0777);

                foreach ($groups_tmp as $group_tmp){



                    $group_tmp = str_replace(' ', '_', $group_tmp); // Replaces all spaces with hyphens.
                    $remove = array("@", "#", "(", ")", "*", "/", "&");
                    $group_tmp = str_replace($remove, "", $group_tmp);
                    $group_tmp = str_replace(".","_",$group_tmp);

                    if($group_tmp != ""){

                        $users = null;

                        //create groups
                        $obj = new stdClass();
                        $obj->courseid = $course;
                        $obj->name = $order_id."-".$group_tmp;
                        $obj->description = $order_id;
                        $group_id = $DB->insert_record('groups', $obj, true, false);

                        //create v-code user
                        $obj = new stdClass();
                        $obj->auth = "manual";
                        $obj->confirmed = 1;
                        $obj->username =  $direct_codes[$z];
                        $obj->password = password_hash($direct_codes[$z], PASSWORD_DEFAULT);
                        $obj->firstname = $group_tmp;
                        $obj->lastname = " ";
                        $obj->email = $obj->username."@ontour.org";
                        $obj->timecreated = time();
                        $obj->timemodified = time();
                        $obj->institution = "";
                        $obj->mnethostid = 1;
                        $obj->lang = "de";
                        $obj->idnumber = "new";
                        $obj->imagealt = "1";
                        $obj->phone1 = $order_id;
                        $obj->phone2 = $group_tmp;
                        $obj->department = "";
                        $user_v_code = $DB->insert_record('user', $obj, true, false);

                        $servername = "localhost";
                        $username = "skuehn22";
                        $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
                        $dbname = "projektreisenWordpress_1637922561";

                        $conn = new mysqli($servername, $username, $password, $dbname);
                        $sql = "SELECT * FROM wp_postmeta WHERE post_id = '$order_id' && meta_key = 'schulname'";
                        $result = $conn->query($sql);
                        $school = $result->fetch_assoc();

                        $sql = "SELECT * FROM {booking_data3} WHERE order_id = '$order_id'";
                        $booking = $DB->get_record_sql($sql);


                        $obj->classname = $group_tmp;
                        $obj->school = $school['meta_value'];
                        $obj->order_id = $order_id;
                        $obj->operators_id = $op_id->id;
                        $obj->user_id = $user_v_code;
                        $obj->mailing = $mailing->mailing;
                        $obj->newsletter = 1;
                        $insert_id = $DB->insert_record('booking_data3', $obj);

                        //enrol v-code to course
                        $obj = new stdClass();
                        $obj->enrolid = "19";
                        $obj->userid = $user_v_code;
                        $obj->timestart = time();
                        $obj->timecreated = time();
                        $obj->timemodified = time();
                        $obj->modifierid = "2";
                        $obj->timestart = "0";
                        $obj->timeend = "0";
                        $DB->insert_record('user_enrolments', $obj, true, false);

                        //assign v-code  to the created group
                        $obj = new stdClass();
                        $obj->groupid = $group_id;
                        $obj->userid = $user_v_code;
                        $obj->timeadded = time();
                        $DB->insert_record('groups_members', $obj, true, false);
                        //$teacher_id = $userid;


                        //assign teacher to the created group
                        $obj = new stdClass();
                        $obj->groupid = $group_id;
                        $obj->userid = $user->id;
                        $obj->timeadded = time();
                        $group_members_id = $DB->insert_record('groups_members', $obj, true, false);
                        $teacher_id = $user->id;

                        $users_app = [];
                        $pdf_pages = [];

                        //create 5 random users which will be used as students group accounts
                        for ($i = 1; $i <= 6; $i++) {

                            $obj = new stdClass();
                            $obj->auth = "manual";
                            $obj->confirmed = 1;
                            $obj->username = rand(1,999);
                            $obj->username = "g".$i."_".$obj->username."_".$order_id."_".$group_tmp;
                            $obj->password = password_hash($obj->username, PASSWORD_DEFAULT);
                            $obj->firstname = "Gruppe";
                            $obj->lastname = $i."_".$group_tmp;
                            $obj->email = $obj->username."@projektreisen.de";
                            $obj->department = $user->department;
                            $obj->timecreated = time();
                            $obj->timemodified = time();

                            if($i == 6){
                                $obj->institution = "teacher_task";
                            }

                            $obj->mnethostid = 1;
                            $obj->lang = "de";
                            $obj->idnumber = "dev_restriction";
                            $obj->phone2 = "Gruppe_".$i;
                            $obj->department = "student_restriction";
                            $id = $DB->insert_record('user', $obj, true, false);
                            $users_app[] = $id;


                            if($i != 6){

                                //$token = copyUserToApp($id, $obj, '1');
                                $token = copyUserToApp($id, $obj, '1');

                                // Content of the QR Code
                                $text = "https://app.ontour.org/login/$token";

                                // Create a QRcode object
                                $qrcode = new TCPDF2DBarcode($text, 'QRCODE,H');

                                // Get image data as PNG
                                $imagePngData = $qrcode->getBarcodePngData(14, 14);

                                $qr_name = $token.".png";

                                $ontour_bid = $order_id;
                                $school_class_name = $group_tmp;

                                if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid)) {
                                    mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid, 0777, true);
                                }

                                if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid.'/'.$school_class_name)) {
                                    mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid.'/'.$school_class_name, 0777, true);
                                }


                                // Define the path to save the image
                                $imagePath = '/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid.'/'.$school_class_name.'/'.$qr_name;
                                $path_code = $ontour_bid.'/'.$school_class_name.'/'.$qr_name;

                                // Save the image to a file
                                file_put_contents($imagePath, $imagePngData);

                                $pdf_date = date('d-m-Y');
                                $pdf_name = $ontour_bid."-".$school_class_name."-Gruppe".$i."-App-Anmeldedaten.pdf";
                                $pdf_path = "/finishing/".$pdf_name;
                                $content = createPDF($i, $school_class_name, $obj->username, $ontour_bid, $path_code);
                                $pdf_pages[] = $content;
                            }else{
                                $token = copyUserToApp($id, $obj, '2');

                                // Content of the QR Code
                                $text = "https://app.ontour.org/login/$token";

                                // Create a QRcode object
                                $qrcode = new TCPDF2DBarcode($text, 'QRCODE,H');

                                // Get image data as PNG
                                $imagePngData = $qrcode->getBarcodePngData(14, 14);

                                $qr_name = $token.".png";

                                if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid)) {
                                    mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid, 0777, true);
                                }

                                if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid.'/'.$school_class_name)) {
                                    mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid.'/'.$school_class_name, 0777, true);
                                }


                                // Define the path to save the image
                                $imagePath = '/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid.'/'.$school_class_name.'/'.$qr_name;
                                $path_code = $ontour_bid.'/'.$school_class_name.'/'.$qr_name;

                                // Save the image to a file
                                file_put_contents($imagePath, $imagePngData);

                                $pdf_date = date('d-m-Y');
                                $pdf_name =$ontour_bid."-".$school_class_name."-Lehrergruppe-App-Anmeldedaten.pdf";
                                $pdf_path = "/finishing/".$pdf_name;
                                $content = createPDFTeacher($i, $school_class_name, $obj->username, $ontour_bid, $path_code);

                                $pdf_pages[] = $content;

                                //create task overview  page for the app

                            }



                            global $CFG;
                            require_once($CFG->libdir.'/pdflib.php');
                            $pdf = new pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                            $pdf->AddPage();
                            $pdf->writeHTMLCell(0, 0, '', '', $content, 0, 0, 0, true, '', true);
                            ob_clean();


                            if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id)) {
                                mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id, 0777, true);
                            }

                            if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp)) {
                                mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp, 0777, true);
                            }

                            $file = $pdf->Output('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp.'/'.$pdf_name, 'F');

                        }

                        $pdf_name = $order_id."-Alle-Accounts".$group_tmp."-App-Anmeldedaten-".$user->city.".pdf";
                        $pdf = new pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
                        foreach ($pdf_pages as $page){

                            $pdf->AddPage();
                            $pdf->writeHTMLCell(0, 0, '', '', $page, 0, 0, 0, true, '', true);

                        }
                        ob_clean();
                        $file = $pdf->Output('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$order_id.'/'.$group_tmp.'/'.$pdf_name, 'F');

                        foreach ($users_app as $user_app){

                            // assign student to group
                            $obj = new stdClass();
                            $obj->groupid = $group_id;
                            $obj->userid = $user_app;
                            $obj->timeadded = time();
                            $group_members_id = $DB->insert_record('groups_members', $obj, true, false);

                            //enrol user to course
                            $obj = new stdClass();
                            $obj->enrolid = "19";
                            $obj->userid = $user_app;
                            $obj->timestart = time();
                            $obj->timecreated = time();
                            $obj->timemodified = time();
                            $group_members_id = $DB->insert_record('user_enrolments', $obj, true, false);

                            //assign student role
                            $obj = new stdClass();
                            $obj->roleid = "5";
                            $obj->contextid = "233";
                            $obj->userid = $user_app;
                            $obj->modifierid = "2";
                            $obj->timemodified = "1647872831";
                            $group_members_id = $DB->insert_record('role_assignments', $obj, true, false);
                        }


                        //assign the assignments to the new users
                        //find context id -> combines system / course cat / course / and its own Id
                        $sql = "SELECT * FROM {context} WHERE instanceid = :id && contextlevel = 50";
                        $context_course = $DB->get_record_sql($sql, array('id'=>$course));
                        $path = "%".$context_course->path."%";

                        $sql = "SELECT * FROM {context} WHERE path LIKE :path && contextlevel = 70";
                        $context_module = $DB->get_records_sql($sql, array('path'=>$path));

                        foreach ($users_app as $user_app){
                            foreach($context_module as $module){
                                // set role for student
                                $ra = new stdClass();
                                $ra->roleid       = "5";
                                $ra->contextid    = $module->id;
                                $ra->userid       = $user_app;
                                $ra->timemodified = time();
                                $ra->id = $DB->insert_record('role_assignments', $ra);
                            }
                        }

                    }


                    $z++;
                }


                setPlannedTask_direct($operator, $order_id, $user, $insert_id);

                $user_update = new stdClass();
                $user_update->id = $user->id;
                $user_update->address = "";
                $DB->update_record('user', $user_update);

            }
        }
    }
}

function setPlannedTask_direct($operator, $ontour_bid, $user, $insert_id){

    global $USER;
    global $DB;

    $servername = "localhost";
    $username = "skuehn22";
    $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
    $dbname = "projektreisenWordpress_1637922561";

    $conn = new mysqli($servername, $username, $password, $dbname);

    $sql = "SELECT * FROM wp_woocommerce_order_items WHERE order_id = '$ontour_bid' && order_item_name = 'Videoprojekt'";
    $result = $conn->query($sql);

    foreach ($result as $row) {


        $order_item_id = $row["order_item_id"];

        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'anreise'";
        $result = $conn->query($sql);
        $arr = $result->fetch_assoc();

        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'abreise'";
        $result = $conn->query($sql);
        $dep = $result->fetch_assoc();

        echo $arr['meta_value'];

        $germanMonths = [
            'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
            'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'
        ];

        $englishMonths = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];

        $arr['meta_value'] = str_replace($germanMonths, $englishMonths, $arr['meta_value']);
        $dep['meta_value'] = str_replace($germanMonths, $englishMonths, $dep['meta_value']);

        $arrival_date   = new DateTime($arr['meta_value']);
        $arrival_str = $arrival_date->format('Y-m-d H:i:s');

        $departure_date   = new DateTime($dep['meta_value']);
        echo "<br>";
        echo  $departure_str = $departure_date->format('Y-m-d H:i:s');

    }


    $servername = "localhost";
    $username = "skuehn22";
    $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
    $dbname = "ontour_moodle_new";

    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }


    echo "sfhkd:".$user->id;

    $obj = new stdClass();
    $obj->b_id = $ontour_bid;
    $obj->user_id = $user->id;
    $obj->receiver = "";
    $obj->sender = "";
    $obj->type = "mail_co";
    $obj->subject = "Z-Codes";
    $obj->state = 2;

    $now = new DateTime();
    $now->modify('+1 hour');
    $now = $now->format('Y-m-d H:i:s');
    $obj->action_date =  $now;


    $DB->insert_record('booking_history3', $obj);


    echo "<br>";
    echo $ontour_bid;

    $data = new stdClass();
    $data->id = $insert_id;
    $data->arrival = $arrival_str;
    $data->departure = $departure_str;

    $DB->update_record('booking_data3', $data);


    /* Erinnerung 1 */

    /*
        $obj = new stdClass();
        $obj->b_id = $ontour_bid;
        $obj->user_id = $user;
        $obj->receiver = "";
        $obj->sender = "";
        $obj->type = "mail_er1";
        $obj->subject = "Erinnerung 1";
        $obj->state = 2;
        $today   = $arrival_date;
        $today->modify('-'.$mailings->erinnerung_1.' day');
        $today->modify('+9 hour');
        $obj->action_date =  $today->format('Y-m-d H:i:s');
        $DB->insert_record('booking_history3', $obj);
    */

    /* Erinnerung 2 */

    /*
        $obj = new stdClass();
        $obj->b_id = $ontour_bid;
        $obj->user_id = $user;
        $obj->receiver = "";
        $obj->sender = "";
        $obj->type = "mail_er2";
        $obj->subject = "Erinnerung 2";
        $obj->state = 2;
        $today   = $arrival_date;
        $today->modify('-'.$mailings->erinnerung_2.' day');
        $today->modify('+9 hour');
        $obj->action_date =  $today->format('Y-m-d H:i:s');
        $DB->insert_record('booking_history3', $obj);
    */

    /* Motivation Mail */
    /*

        $obj = new stdClass();
        $obj->b_id = $ontour_bid;
        $obj->user_id = $user;
        $obj->receiver = "";
        $obj->sender = "";
        $obj->type = "mail_mo";
        $obj->subject = "Motivation";
        $obj->state = 2;
        $today   = $arrival_date;
        $today->modify('-'.$mailings->motivation.' day');
        $today->modify('+9 hour');
        $obj->action_date =  $today->format('Y-m-d H:i:s');
        $DB->insert_record('booking_history3', $obj);

    */

    /* Finishing Mail */
    /*

        $obj = new stdClass();
        $obj->b_id = $ontour_bid;
        $obj->user_id = $user;
        $obj->receiver = "";
        $obj->sender = "";
        $obj->type = "mail_fi";
        $obj->subject = "Film verbessern";
        $obj->state = 0;
        $today   = $departure_date;
        $today->modify('+'.$operator->mailing_4.' day');
        $today->modify('+9 hour');
        $obj->action_date =  $today->format('Y-m-d H:i:s');
        $DB->insert_record('booking_history3', $obj);

    */

}
function copyUserToApp($id, $obj, $role){

    $servername = "localhost";
    $username = "skuehn22";
    $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
    $dbname = "webapp";

    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $token = md5(uniqid().rand(1000000, 9999999));

    $sql = "INSERT INTO users (name, email, password, moodle_uid, login_token, role, course) VALUES ('$obj->username', '$obj->email', '$obj->password', '$id', '$token', '$role', 8)";

    ($conn->query($sql));

    return $token;

}

cron_run();
