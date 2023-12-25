<?php

define('CLI_SCRIPT', true);

require(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once($CFG->libdir.'/cronlib.php');
require_once($CFG->libdir.'/pdflib.php');

#appOverviewSubmissons();

function appOverviewSubmissons(){

    global $DB;

    $start = date('Y-m-d', strtotime('-7 days'));
    $end = date('Y-m-d', strtotime('+7 days'));

    $sql = "SELECT * FROM {booking_data3} WHERE state = 0";
    $bookings = $DB->get_records_sql($sql);


    foreach ($bookings as $booking) {

        if ($booking->arrival > $start && $booking->arrival < $end) {

            $sql = "SELECT * FROM {user} WHERE phone1 = " . $booking->order_id;
            $z_account = $DB->get_record_sql($sql);

            $section_id = $z_account->middlename;

            $sql = "SELECT * FROM {groups_members} WHERE userid = " . $z_account->id;
            $group = $DB->get_record_sql($sql);
            $group_id = $group->groupid;

            $html = "";

            $sql = "SELECT * FROM {groups_members} WHERE groupid = " . $group_id;

            $groups_members = $DB->get_records_sql($sql);
            $i = 0;
            $k = 0;

            $count = count($groups_members) - 1;

            foreach ($groups_members as $member) {

                if ($k > 0) {

                    $html .= '<p dir="ltr" style="text-align: left; font-size:14px!important;"><strong>Gruppe ' . $k . '</strong></p>';
                }


                $k++;

                $sql = "SELECT * FROM {user} WHERE id = " . $member->userid;

                $student_account = $DB->get_record_sql($sql);


                //bigger 0 because 0 is the z-code user
                if ($i != $count && $i > 0) {
                    if ($student_account->institution == '') {
                        $teamname = "<span style='color: red;'>nein</span>";
                        $task6_files = 0;
                        $task5_files = 0;
                        $task4_files = 0;
                        $task3_files = 0;
                        $task2_files = 0;

                        //get task 1 data
                        $sql = "SELECT *
				FROM {assign_submission}
				WHERE assignment = '49' && userid = " . $member->userid;

                        $task1 = $DB->get_record_sql($sql);

                        //get online text task 1
                        if ($task1->status == "submitted") {
                            $sql = "SELECT *
				FROM {assignsubmission_onlinetext}
				WHERE assignment = '49' && submission = " . $task1->id;
                            $teamname_record = $DB->get_record_sql($sql);
                            $teamname = strip_tags($teamname_record->onlinetext);
                        }

                        //get task 2 data team foto
                        $sql = "SELECT *
				FROM {assign_submission}
				WHERE assignment = '54' && userid = " . $member->userid;

                        $task2 = $DB->get_record_sql($sql);

                        //get task 3 data team foto
                        $sql = "SELECT *
				FROM {assign_submission}
				WHERE assignment = '62' && userid = " . $member->userid;

                        $task3 = $DB->get_record_sql($sql);

                        //get task 4 data team foto
                        $sql = "SELECT *
				FROM {assign_submission}
				WHERE assignment = '52' && userid = " . $member->userid;

                        $task4 = $DB->get_record_sql($sql);


                        //get task 5 data team foto
                        $sql = "SELECT *
				FROM {assign_submission}
				WHERE assignment = '63' && userid = " . $member->userid;

                        $task5 = $DB->get_record_sql($sql);

                        if ($task5->status == "submitted") {
                            $sql = "SELECT *
				FROM {assignsubmission_file}
				WHERE assignment = '63' && submission = " . $task5->id;

                            $task5_record = $DB->get_record_sql($sql);

                            $task5_files = $task5_record->numfiles;

                        }

                        //get task 6 data team foto
                        $sql = "SELECT *
				FROM {assign_submission}
				WHERE assignment = '64' && userid = " . $member->userid;

                        $task6 = $DB->get_record_sql($sql);

                        if ($task6->status == "submitted") {
                            $sql = "SELECT *
				FROM {assignsubmission_file}
				WHERE assignment = '64' && submission = " . $task6->id;
                            $task6_record = $DB->get_record_sql($sql);
                            $task6_files = $task6_record->numfiles;
                        }

                        $sql = "SELECT *
				FROM {files}
				WHERE filesize > 0 && contextid = 2968 && userid = " . $member->userid;

                        $files = $DB->get_records_sql($sql);


                        $sql = "SELECT *
				FROM {files}
				WHERE filesize > 0 && contextid = 2967 && userid = " . $member->userid;

                        $files_5 = $DB->get_records_sql($sql);

                        $sql = "SELECT *
				FROM {files}
				WHERE filesize > 0 && contextid = 1573 && userid = " . $member->userid;

                        $files_4 = $DB->get_records_sql($sql);

                        $sql = "SELECT *
				FROM {files}
				WHERE filesize > 0 && contextid = 2966 && userid = " . $member->userid;

                        $files_3 = $DB->get_records_sql($sql);

                        $sql = "SELECT *
				FROM {files}
				WHERE filesize > 0 && contextid = 1575 && userid = " . $member->userid;

                        $files_2 = $DB->get_records_sql($sql);


                        $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 1 Teamname</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important" id="group1_task1">' . $teamname . '</div>
                         </div>';

                        if ($task5->status == "submitted") {

                            if ($task5_files == 3) {

                                $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 5 Safari</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important">ja(' . $task5_files . ' von 3)</div><br>
                         </div>';


                            } else {

                                $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 2 Kiezerkundung</div>
                            <div style="color: orange;  display: inline-block; font-size:14px!important">ja(' . $task5_files . ' von 3)</div><br>
                         </div>';


                            }


                        } else {
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 2 Kiezerkundung</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div>';

                        }


                        if ($task2->status == "submitted") {
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 3 Teamplakat</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important">ja</div><br>
                         </div>';
                        } else {
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 3 Teamplakat</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div>';
                        }





                        if ($task4->status == "submitted") {
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 4 Audio</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important">ja</div><br>
                         </div>';
                        } else {
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 4 Audio</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div>';
                        }


                        if ($task3->status == "submitted") {
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 5 Video</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important">ja</div><br>
                         </div>';
                        } else {
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 5 Video</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div>';
                        }


                        if ($task6->status == "submitted") {

                            if ($task5_files == 3) {

                                $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 6 Outtakes</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important">ja(' . $task6_files . ' von 6)</div><br>
                         </div>';


                            } else {

                                $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 6 Outtakes</div>
                            <div style="color: orange;  display: inline-block; font-size:14px!important">ja(' . $task6_files . ' von 6)</div><br>
                         </div>';


                            }


                        } else {
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe 6 Outtakes</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div>';

                        }

                        $i++;

                    }

                    if ($i == 6) {


                        $html .= '<hr><p dir="ltr" style="text-align: left; font-size:14px!important;"><strong>Lehrkräfte</strong></p>';

                        $member->userid = $member->userid + 1;

                        //get task 2 data team foto
                        $sql = "SELECT *
				FROM {assign_submission}
				WHERE assignment = '61' && userid = " . $member->userid;

                        $task_teacher = $DB->get_record_sql($sql);


                        $sql = "SELECT *
				FROM {files}
				WHERE filesize > 0 && contextid = 2522 && userid = " . $member->userid;

                        $files_lehrer = $DB->get_records_sql($sql);

                        if ($task_teacher->status == "submitted") {
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe</div>
                            <div style="color: green;  display: inline-block; font-size:14px!important">ja</div><br>
                         </div>';
                        } else {
                            $html .= '<div>
                            <div style="width:50%!important;  display: inline-block; text-align: left; font-size:14px!important;">Aufgabe</div>
                            <div style="color: red;  display: inline-block; font-size:14px!important">nein</div><br>
                         </div>';
                        }


                    }

                } else {
                    $i++;
                }

            }

            $html = str_replace("Gruppe 6", "", $html);

            echo $html;

            if (isset($section_id)) {
                $sql = "SELECT * FROM {course_sections} WHERE id = '$section_id'";
                $section = $DB->get_record_sql($sql);

                $section_update = new stdClass();
                $section_update->id = $section->id;
                $section_update->summary = $html;
                $DB->update_record('course_sections', $section_update);
                rebuild_course_cache('8', true);
            }

        }

    }

}

cron_run();
