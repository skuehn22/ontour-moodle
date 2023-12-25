<?php


if($_GET['id'] == 8 && !isset($_GET['section'])){
    header("Location: https://reisen.ontour.org/course/view.php?id=8&section=6");
}


//  Display the course home page.

require_once('../config.php');
require_once('lib.php');
require_once($CFG->libdir.'/completionlib.php');

$id          = optional_param('id', 0, PARAM_INT);
$name        = optional_param('name', '', PARAM_TEXT);
$edit        = optional_param('edit', -1, PARAM_BOOL);
$hide        = optional_param('hide', 0, PARAM_INT);
$show        = optional_param('show', 0, PARAM_INT);
$idnumber    = optional_param('idnumber', '', PARAM_RAW);
$sectionid   = optional_param('sectionid', 0, PARAM_INT);
$section     = optional_param('section', 0, PARAM_INT);
$move        = optional_param('move', 0, PARAM_INT);
$marker      = optional_param('marker',-1 , PARAM_INT);
$switchrole  = optional_param('switchrole',-1, PARAM_INT); // Deprecated, use course/switchrole.php instead.
$return      = optional_param('return', 0, PARAM_LOCALURL);


global $PAGE;
//$PAGE->requires->jquery();

$params = array();
if (!empty($name)) {
    $params = array('shortname' => $name);
} else if (!empty($idnumber)) {
    $params = array('idnumber' => $idnumber);
} else if (!empty($id)) {
    $params = array('id' => $id);
}else {
    print_error('unspecifycourseid', 'error');
}

global $DB;
$course = $DB->get_record('course', $params, '*', MUST_EXIST);


//if a teacher changes in the platform
if(isset($_GET['lastname'])){
    $USER->lastname=$_GET['lastname'];
    $USER->firstname=$_GET['firstname'];
    $_SESSION['USER']=$USER;
}

if(isset($_GET['mail'])){
    $USER->email=$_GET['mail'];
    $_SESSION['USER']=$USER;
}

$userid = $USER->id;
$recreation = false;

if(isset($_POST['greetings'])){

    $greetings = $_POST['greetings'];

    $servername = "localhost";
    $username = "skuehn22";
    $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
    $dbname = "ontour_moodle_new";

    $conn = new mysqli($servername, $username, $password, $dbname);
    $sql = "UPDATE mdl_booking_data3 SET greetings='$greetings' WHERE user_id=$USER->id";
    $conn->query($sql);
}

if(isset($_POST['film_pro'])){

    $option = $_POST['film_pro'];

    if($option){

        $sql = "SELECT * FROM mdl_booking_data3 WHERE user_id = '$USER->id'";
        $booking = $DB->get_record_sql($sql);

        $obj = new stdClass();
        $obj->user_id = $USER->id;
        $obj->state = 2;
        $obj->type = "film_production";
        $obj->sender = "";
        $obj->receiver = "";
        $obj->b_id = $booking->order_id;
        $obj->subject = "Film freigegeben";
        $obj->content = "";
        $obj->attachment = "";
        $DB->insert_record('booking_history3', $obj);


    }


}

$urlparams = array('id' => $course->id);

// Sectionid should get priority over section number
if ($sectionid) {
    $section = $DB->get_field('course_sections', 'section', array('id' => $sectionid, 'course' => $course->id), MUST_EXIST);
}
if ($section) {
    $urlparams['section'] = $section;
}

$PAGE->set_url('/course/view.php', $urlparams); // Defined here to avoid notices on errors etc

// Prevent caching of this page to stop confusion when changing page after making AJAX changes
$PAGE->set_cacheable(false);

context_helper::preload_course($course->id);
$context = context_course::instance($course->id, MUST_EXIST);

// Remove any switched roles before checking login
if ($switchrole == 0 && confirm_sesskey()) {
    role_switch($switchrole, $context);
}

require_login($course);

// Switchrole - sanity check in cost-order...
$reset_user_allowed_editing = false;
if ($switchrole > 0 && confirm_sesskey() &&
    has_capability('moodle/role:switchroles', $context)) {
    // is this role assignable in this context?
    // inquiring minds want to know...
    $aroles = get_switchable_roles($context);
    if (is_array($aroles) && isset($aroles[$switchrole])) {
        role_switch($switchrole, $context);
        // Double check that this role is allowed here
        require_login($course);
    }
    // reset course page state - this prevents some weird problems ;-)
    $USER->activitycopy = false;
    $USER->activitycopycourse = NULL;
    unset($USER->activitycopyname);
    unset($SESSION->modform);
    $USER->editing = 0;
    $reset_user_allowed_editing = true;
}

//If course is hosted on an external server, redirect to corresponding
//url with appropriate authentication attached as parameter
if (file_exists($CFG->dirroot .'/course/externservercourse.php')) {
    include $CFG->dirroot .'/course/externservercourse.php';
    if (function_exists('extern_server_course')) {
        if ($extern_url = extern_server_course($course)) {
            redirect($extern_url);
        }
    }
}


require_once($CFG->dirroot.'/calendar/lib.php');    /// This is after login because it needs $USER

// Must set layout before gettting section info. See MDL-47555.
$PAGE->set_pagelayout('course');

if ($section and $section > 0) {

    // Get section details and check it exists.
    $modinfo = get_fast_modinfo($course);
    $coursesections = $modinfo->get_section_info($section, MUST_EXIST);

    // Check user is allowed to see it.
    if (!$coursesections->uservisible) {
        // Check if coursesection has conditions affecting availability and if
        // so, output availability info.
        if ($coursesections->visible && $coursesections->availableinfo) {
            $sectionname     = get_section_name($course, $coursesections);
            $message = get_string('notavailablecourse', '', $sectionname);
            redirect(course_get_url($course), $message, null, \core\output\notification::NOTIFY_ERROR);
        } else {
            // Note: We actually already know they don't have this capability
            // or uservisible would have been true; this is just to get the
            // correct error message shown.
            require_capability('moodle/course:viewhiddensections', $context);
        }
    }
}

// Fix course format if it is no longer installed
$course->format = course_get_format($course)->get_format();

$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_other_editing_capability('moodle/course:update');
$PAGE->set_other_editing_capability('moodle/course:manageactivities');
$PAGE->set_other_editing_capability('moodle/course:activityvisibility');
if (course_format_uses_sections($course->format)) {
    $PAGE->set_other_editing_capability('moodle/course:sectionvisibility');
    $PAGE->set_other_editing_capability('moodle/course:movesections');
}

// Preload course format renderer before output starts.
// This is a little hacky but necessary since
// format.php is not included until after output starts
if (file_exists($CFG->dirroot.'/course/format/'.$course->format.'/renderer.php')) {
    require_once($CFG->dirroot.'/course/format/'.$course->format.'/renderer.php');
    if (class_exists('format_'.$course->format.'_renderer')) {
        // call get_renderer only if renderer is defined in format plugin
        // otherwise an exception would be thrown
        $PAGE->get_renderer('format_'. $course->format);
    }
}

if ($reset_user_allowed_editing) {
    // ugly hack
    unset($PAGE->_user_allowed_editing);
}

if (!isset($USER->editing)) {
    $USER->editing = 0;
}

if (is_siteadmin()) {
    if ($PAGE->user_allowed_editing()) {
        if (($edit == 1) and confirm_sesskey()) {
            $USER->editing = 1;
            // Redirect to site root if Editing is toggled on frontpage
            if ($course->id == SITEID) {
                redirect($CFG->wwwroot . '/?redirect=0');
            } else if (!empty($return)) {
                redirect($CFG->wwwroot . $return);
            } else {
                $url = new moodle_url($PAGE->url, array('notifyeditingon' => 1));
                redirect($url);
            }
        } else if (($edit == 0) and confirm_sesskey()) {
            $USER->editing = 0;
            if (!empty($USER->activitycopy) && $USER->activitycopycourse == $course->id) {
                $USER->activitycopy = false;
                $USER->activitycopycourse = NULL;
            }
            // Redirect to site root if Editing is toggled on frontpage
            if ($course->id == SITEID) {
                redirect($CFG->wwwroot . '/?redirect=0');
            } else if (!empty($return)) {
                redirect($CFG->wwwroot . $return);
            } else {
                redirect($PAGE->url);
            }
        }

        if (has_capability('moodle/course:sectionvisibility', $context)) {
            if ($hide && confirm_sesskey()) {
                set_section_visible($course->id, $hide, '0');
                redirect($PAGE->url);
            }

            if ($show && confirm_sesskey()) {
                set_section_visible($course->id, $show, '1');
                redirect($PAGE->url);
            }
        }

        if (!empty($section) && !empty($move) &&
            has_capability('moodle/course:movesections', $context) && confirm_sesskey()) {
            $destsection = $section + $move;
            if (move_section_to($course, $section, $destsection)) {
                if ($course->id == SITEID) {
                    redirect($CFG->wwwroot . '/?redirect=0');
                } else {
                    redirect(course_get_url($course));
                }
            } else {
                echo $OUTPUT->notification('An error occurred while moving a section');
            }
        }
    } else {
        $USER->editing = 0;
    }
}

$SESSION->fromdiscussion = $PAGE->url->out(false);


if ($course->id == SITEID) {
    // This course is not a real course.
    redirect($CFG->wwwroot .'/');
}

// Determine whether the user has permission to download course content.
$candownloadcourse = \core\content::can_export_context($context, $USER);

// We are currently keeping the button here from 1.x to help new teachers figure out
// what to do, even though the link also appears in the course admin block.  It also
// means you can back out of a situation where you removed the admin block. :)
if (is_siteadmin())
{
    if ($PAGE->user_allowed_editing()) {
        $buttons = $OUTPUT->edit_button($PAGE->url);
        $PAGE->set_button($buttons);
    } else if ($candownloadcourse) {
        // Show the download course content button if user has permission to access it.
        // Only showing this if user doesn't have edit rights, since those who do will access it via the actions menu.
        $buttonattr = \core_course\output\content_export_link::get_attributes($context);
        $button = new single_button($buttonattr->url, $buttonattr->displaystring, 'post', false, $buttonattr->elementattributes);
        $PAGE->set_button($OUTPUT->render($button));
    }

}

// If viewing a section, make the title more specific
if ($section and $section > 0 and course_format_uses_sections($course->format)) {
    $sectionname = get_string('sectionname', "format_$course->format");
    $sectiontitle = get_section_name($course, $section);
    $PAGE->set_title(get_string('coursesectiontitle', 'moodle', array('course' => $course->fullname, 'sectiontitle' => $sectiontitle, 'sectionname' => $sectionname)));
} else {
    $PAGE->set_title(get_string('coursetitle', 'moodle', array('course' => $course->fullname)));
}

$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

if ($USER->editing == 1) {

    // MDL-65321 The backup libraries are quite heavy, only require the bare minimum.
    require_once($CFG->dirroot . '/backup/util/helper/async_helper.class.php');

    if (async_helper::is_async_pending($id, 'course', 'backup')) {
        echo $OUTPUT->notification(get_string('pendingasyncedit', 'backup'), 'warning');
    }
}




if( (empty($_GET['group'] || $_GET['group_pre'] ==1) && $_GET['section'] ==2)){

    $userid = $USER->id;
    $show_modal = false;
    $count_groups= 0;

    $sql = "SELECT groupid FROM {groups_members} WHERE userid = :id";
    $groups = $DB->get_records_sql($sql, array('id'=>$userid));


    if (isset($_GET['group'])) {
        $SESSION->group = $_GET['group'];
    }

    //text string
    $sql = "SELECT name FROM {groups} WHERE id = :id";
    $name = $DB->get_record_sql($sql, array('id' => $SESSION->group));
    $js = "$('.class_name').text('" . $name->name . "')";

    $SESSION->group = $PAGE->url->out(false);

    //text string
    $sql = "SELECT * FROM {post} WHERE groupid = :id";
    $post = $DB->get_record_sql($sql, array('id' => $SESSION->group));

    //build modal if more than one group
    $sql = "SELECT groupid FROM {groups_members} WHERE userid = :id";
    $groups = $DB->get_records_sql($sql, array('id' => $userid));

}


/* SET MENU DISPLAY TRUE OR FALSE */
if ($USER->username != "check") {
    $js .= '$(".nav-tabs").hide();';
}

if ($USER->username == "aufgaben#_2911" || $USER->idnumber == "new" || $USER->username == "%&f~rnapvd=x)}:s" || $USER->username ==  "julia_bauer574#") {
    $js .= '$(".nav-tabs").show();';
}


// TAB 1 JS



if ($USER->username == "check" || $USER->idnumber == "new") {


    $js .= '
						$(".classic-pencil").click(function(event) {
							var id = event.target.id;
							id = id.substring(2);
							//alert( id );
							
							$(".a_"+id).hide();
							$("#e_"+id).hide();
							$("#"+id).show();
							$(".s_"+id).show();
							$(".c_"+id).show();
						});
						
						
						$(".mail-pencil").click(function(event) {
							var id = event.target.id;
							id = id.substring(2);
						
							
							$(".mail").hide();
							$("#mail_new").show();
							
							$("#e_"+id).hide();
							$("#"+id).show();
							$(".s_"+id).show();
							$(".c_"+id).show();
							$(".mail_change").hide();
						});
						
						$(".name-pencil").click(function(event) {
							var id = event.target.id;
							id = id.substring(2);
						
							//alert( id );
							$(".name").hide();
							$("#firstname_new").show();
							$("#lastname_new").show();
							
							$("#e_"+id).hide();
							$("#"+id).show();
							$(".s_"+id).show();
							$(".c_"+id).show();
							$(".name_change").hide();
						
						});
						
                        $(".classname-pencil").click(function(event) {
                            var id = event.target.id;
                            id = id.substring(2);
                        
                            
                            $(".classname").hide();
                            $("#classname_new").show();
                            
                            $("#e_"+id).hide();
                            $("#"+id).show();
                            $(".s_"+id).show();
                            $(".c_"+id).show();
                            $(".classname_change").hide();
						});
						
                      $(".school-pencil").click(function(event) {
                            var id = event.target.id;
                            id = id.substring(2);
                        
                            
                            $(".school").hide();
                            $("#school_new").show();
                            
                            $("#e_"+id).hide();
                            $("#"+id).show();
                            $(".s_"+id).show();
                            $(".c_"+id).show();
                            $(".school_change").hide();
						});
						
						
						$(".fa-times").click(function(event) {
							var id = event.target.id;
							id = id.substring(2);
							//alert( id );
							
								$(".mail").show();
							$("#mail_new").hide();
							
							$(".a_"+id).show();
							$("#e_"+id).show();
							$("#"+id).hide();
							$(".s_"+id).hide();
							$(".c_"+id).hide();
							$(".mail_change").show();
							
						});
						
						$(".fa-times-name").click(function(event) {
							var id = event.target.id;
							id = id.substring(2);
							//alert( id );
							
								$(".name").show();
							$("#firstname_new").hide();
							$("#lastname_new").hide();
							
							$(".a_"+id).show();
							$("#e_"+id).show();
							$("#"+id).hide();
							$(".s_"+id).hide();
							$(".c_"+id).hide();
							
							$(".name_change").show();
						});
						
						
						$(".c_classname").click(function(event) {
							var id = event.target.id;
							id = id.substring(2);
						
							$(".classname").show();
							$("#classname_new").hide();
						
							
							$(".a_"+id).show();
							$("#e_"+id).show();
							$("#"+id).hide();
							$(".s_"+id).hide();
							$(".c_"+id).hide();
							
							$(".classname_change").show();
							$("#classname").show();
						});
						
                        $(".c_school").click(function(event) {
                            var id = event.target.id;
                            id = id.substring(2);
                        
                            $(".school").show();
                            $("#school_new").hide();
                        
                            
                            $(".a_"+id).show();
                            $("#e_"+id).show();
                            $("#"+id).hide();
                            $(".s_"+id).hide();
                            $(".c_"+id).hide();
                            
                            $(".school_change").show();
                            $("#schoolname").show();
						});
					
						$(".fa-floppy-o").click(function(event) {
							var id = event.target.id;
							id = id.substring(2);
							$( "#form_"+id ).submit();
						
						});
						
						$(".mail-floppy").click(function(event) {
							var id = event.target.id;
							id = id.substring(2);
							$( "#form_mail" ).submit();
						
						});
						
						$(".name-floppy").click(function(event) {
							var id = event.target.id;
							id = id.substring(2);
							$( "#form_name" ).submit();
						
						});
						
                        $(".classname-floppy").click(function(event) {
                   
                            var id = event.target.id;
                            id = id.substring(2);
                            $( "#classname" ).submit();
                       
						});
						
						   $(".school-floppy").click(function(event) {
                   
                            var id = event.target.id;
                            id = id.substring(2);
                            $( "#school_form" ).submit();
                       
						});
						
						$( ".class_info" ).hide();
						$( ".change_class" ).hide();
					';




    chmod('../filemanager/zugangsdaten/'.$USER->phone1, 0777);


    if($USER->phone2 != ""){
        chmod('../filemanager/zugangsdaten/'.$USER->phone1.'/'.$USER->phone2, 0777);  // octal; correct value of mode
    }


    if($USER->phone1 == "5029") {

        if($USER->username == "wbt-4756-5029-HH21C"){
            $arrFiles = scandir('../filemanager/zugangsdaten/'.$USER->phone1.'/gruppe1');
        }else{
            $arrFiles = scandir('../filemanager/zugangsdaten/'.$USER->phone1.'/gruppe2');
        }

    }else{

        if($USER->phone2 != ""){
            $arrFiles = scandir('../filemanager/zugangsdaten/'.$USER->phone1.'/'.$USER->phone2);
        }else{
            $arrFiles = scandir('../filemanager/zugangsdaten/'.$USER->phone1);
        }
    }


    sort($arrFiles);


    if(count($arrFiles) > 8){
        //echo count($arrFiles);
        foreach ($arrFiles as $key => $file){

            if($key >1){

                $pieces = explode("-", $file);

                if($pieces[1] == "Alle"){

                    if($USER->phone2 != ""){
                        $js .= '$("#download-all").attr("href", "/filemanager/zugangsdaten/'.$USER->phone1.'/'.$USER->phone2.'/'.$file.'");';
                    }else{
                        $js .= '$("#download-all").attr("href", "/filemanager/zugangsdaten/'.$USER->phone1.'/'.$file.'");';
                    }


                }else{
                    $key_tmp = $key - 3;

                    if($USER->phone2 != ""){
                        $js .= '$("#link'.$key_tmp.'").attr("href", "/filemanager/zugangsdaten/'.$USER->phone1.'/'.$USER->phone2.'/'.$file.'");';
                    }else{
                        $js .= '$("#link'.$key_tmp.'").attr("href", "/filemanager/zugangsdaten/'.$USER->phone1.'/'.$file.'");';
                    }


                }
            }

        }
    }else{

        $js .= '$( "#download-all" ).hide();';

        foreach ($arrFiles as $key => $file){
            //echo $file."-".$key."<br>";

           //cho $file;
            //echo "<br>";

            $pieces = explode("-", $file);


            if($pieces[1] != "Gruppe1"){

            }

            if($pieces[1] != "Alle"){
                if($key >1){
                    $pieces = explode("-", $file);
                    $key_tmp = $key - 2;

                    if($USER->phone1 == "5029") {
                        if($USER->username == "wbt-4756-5029-HH21C"){
                            $js .= '$("#link'.$key_tmp.'").attr("href", "/filemanager/zugangsdaten/'.$USER->phone1.'/gruppe1/'.$file.'");';
                        }else{
                            $js .= '$("#link'.$key_tmp.'").attr("href", "/filemanager/zugangsdaten/'.$USER->phone1.'/gruppe2/'.$file.'");';
                        }

                    }else{


                        if($USER->phone2 != ""){
                            $js .= '$("#link'.$key_tmp.'").attr("href", "/filemanager/zugangsdaten/'.$USER->phone1.'/'.$USER->phone2.'/'.$file.'");';
                        }else{
                            $js .= '$("#link'.$key_tmp.'").attr("href", "/filemanager/zugangsdaten/'.$USER->phone1.'/'.$file.'");';
                        }


                    }

                }

            }


        }
    }




} else {
    $js .= '$( ".class_info" ).hide();';
}

$js .= '
        $( ".close" ).click(function() {
            $( ".class_info" ).hide();
        });';




if($_GET['group_tmp']){
    $js .= ' $(".btn-back-info").attr("href", "https://reisen.ontour.org/course/view.php?id=8&group='.$_GET['group'].'")';
}



$eventdata = new \core\message\message();
$eventdata->courseid          = "8";
$eventdata->component         = 'moodle';
$eventdata->name              = 'instantmessage';
$eventdata->userfrom          = get_admin();
$eventdata->userto            = get_admin();
$eventdata->subject           = "Zugangscodes";
$eventdata->fullmessage       = $pdf_name;
$eventdata->fullmessageformat = FORMAT_PLAIN;
$eventdata->fullmessagehtml   = '';
$eventdata->smallmessage      = '';
$eventdata->attachment      = $pdf;
//$test = message_send($eventdata);


$PAGE->requires->js_init_code($js);


// Course wrapper start.
echo html_writer::start_tag('div', array('class'=>'course-content'));

// make sure that section 0 exists (this function will create one if it is missing)
course_create_sections_if_missing($course, 0);

// get information about course modules and existing module types
// format.php in course formats may rely on presence of these variables
$modinfo = get_fast_modinfo($course);
$modnames = get_module_types_names();
$modnamesplural = get_module_types_names(true);
$modnamesused = $modinfo->get_used_module_names();
$mods = $modinfo->get_cms();
$sections = $modinfo->get_section_info_all();

// CAUTION, hacky fundamental variable defintion to follow!
// Note that because of the way course fromats are constructed though
// inclusion we pass parameters around this way..
$displaysection = $section;

// Include the actual course format.
require($CFG->dirroot .'/course/format/'. $course->format .'/format.php');
// Content wrapper end.

echo html_writer::end_tag('div');

// Trigger course viewed event.
// We don't trust $context here. Course format inclusion above executes in the global space. We can't assume
// anything after that point.
course_view(context_course::instance($course->id), $section);

// Include course AJAX
include_course_ajax($course, $modnamesused);

// If available, include the JS to prepare the download course content modal.
if ($candownloadcourse) {
    $PAGE->requires->js_call_amd('core_course/downloadcontent', 'init');
}

// Load the view JS module if completion tracking is enabled for this course.
$completion = new completion_info($course);
if ($completion->is_enabled()) {
    $PAGE->requires->js_call_amd('core_course/view', 'init');
}


echo html_writer::start_tag('div', array('id'=>'myModal1', 'class'=>'modal-students'));
echo html_writer::start_tag('div', array('class'=>'modal-content-students'));
echo html_writer::start_span('hello') . '<h3>Herzlich Willkommen!</h3> <br> Mit welcher Klassen möchten Sie reisen?<br><br>' . html_writer::end_span();
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo $OUTPUT->footer();



function createPDF($group, $class, $username, $order){

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
	        <br><br><br><strong>Gruppe '.$group.' Klasse: </strong><span style="color: #49AEDB">'.$class.'</span><br/>
	    </td>
        <td align="right">
	
        </td>
	</tr>
	<tr><td><strong><u>Login für die Schüler*innen</u></strong>
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
                <li>Geht in den Appstore und gebt mit I-Phones "onTour Reisen" und mit anderen Handytypen "onTour" ein. Ihr müsst etwas scrollen.</li>
                <li>Nach dem Download die App öffnen und die Logindaten eingeben. </li>
            </ul>
            <br>
            <img src="https://reisen.ontour.org/downloads/app_store_1.png" style="width: 220px;">
            <br><br><br><br>
            <strong><u>Bitte beachten</u></strong>
             <ul style="line-height: 25px; margin-top: -30px;">
                <li style="color: red;"><span style="color: #000;">Mindestens eine Person pro Gruppe muss die App in Berlin auf dem Handy heruntergeladen haben.</span></li>
                <li>Ihr braucht vor Ort für die App etwas Datenvolumen und natürlich einen geladenen Akku.</li>
                <li>Bitte schließt und öffnet die App vor der Nutzung in Berlin noch einmal, damit ihr die aktuelle Version habt. </li>
            </ul>
        </td>
		<td></td>
    </tr>
	</tbody>
	</table>
	';


    return $html;

}

function createPDFTeacher($group, $class, $username, $order){

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
	        <br><br><br><strong>Gruppe Lehrkräfte - Klasse: </strong><span style="color: #49AEDB">'.$class.'</span><br/>
	    </td>
        <td align="right">
        </td>
	</tr>
	<tr><td><strong><u>Login für Lehrer*innen</u></strong>
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

