<?php

require(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');

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

if($_SERVER['HTTP_HOST'] == "www.projektreise.sk"){
    $params = array('id' => '4');
}else{
    $params = array('id' => '10');
}

global $DB;

$course = $DB->get_record('course', $params, '*', MUST_EXIST);

$opbid = $_POST['operator_bid'];
$opid = $_POST['operators'];
$course_id =  $_POST['city_product'];

$sql = "SELECT * FROM mdl_booking_data3 WHERE ext_booking_id = '$opbid' && operators_id = '$opid'";
$operator_check_id = $DB->get_record_sql($sql);

if($opid != 1){
    if($operator_check_id){
        header('Location: ../view.php?company=true&id=172&status=error&error="Buchungsnummer Veranstanlter bereits vorhanden"');
        exit();
    }
}




/* get all data about the operator like wbt */
$sql = "SELECT * FROM mdl_ext_operators1 WHERE id = ".$_POST['operators'];
$operator = $DB->get_record_sql($sql);

/* table wp_posts */
/* table wp_postmeta */


$ontour_bid = createWordpressBooking($operator, $_POST['price'][0]);



foreach ($_POST['school_classes'] as $key => $class){

    /* str_replace etc. */
    $school_class_name = prepareClassName($class);

    switch ($school_class_name) {
        case "Zugang_1":
            $code_extension = "1";
            break;
        case "Zugang_2":
            $code_extension = "2";
            break;
        case "Zugang_3":
            $code_extension = "3";
            break;
        case "Zugang_4":
            $code_extension = "4";
            break;
        case "Zugang_5":
            $code_extension = "5";
            break;
        default:
            $code_extension = $school_class_name;
    }



    $code_extension = $school_class_name;

    /* operator 1 is direct booking from website */

    if($_POST['sp_vcode'][$key] == ""){

        if($operator->id == 1){
            $v_code = $operator->short."-".rand(1000 ,9999)."-".$ontour_bid."-".$code_extension;
        }else{
            $v_code = $operator->short."-".$_POST['operator_bid']."-".$ontour_bid."-".$code_extension;
        }

    }else{

        $v_code = $_POST['sp_vcode'][$key];

    }



    createWordpressProductVariantsBooking($v_code, $class ,$ontour_bid, $key, $_POST['price'][0]);

    $users = [];

    $user = createVcodeUser($v_code, $school_class_name, $ontour_bid);


    if($user == "error"){
        header('Location: ../view.php?id=15&status=error&class='.$class);
    }

    $obj = new stdClass();
    $obj->order_id = $ontour_bid;

    if($operator->id != 1){
        $obj->ext_booking_id = $_POST['operator_bid'];
    }

    $school = $_POST['school'];
    $operator_bid = $_POST['operator_bid'];
    $wv = $_POST['note'];
    $wvd = $_POST['reminder'];
    $classname = $_POST['school_classes'][$key];
    $arrival = $_POST['arrival'][$key];
    $departure = $_POST['departure'][$key];


    $obj->operators_id = $operator->id;
    $obj->user_id = $user;
    $obj->mailing = $operator->mailing;
    $obj->ext_booking_id = $operator_bid;
    $obj->school = $school;
    $obj->note = $wv;
    $obj->arrival = $arrival;
    $obj->departure = $departure;
    $obj->note_reminder = $wvd;
    $obj->classname = $classname;


    $sql = "SELECT * FROM {booking_data3} WHERE order_id = '$opbid'";
    $booking = $DB->get_record_sql($sql);


    //TODO change that --> that is the checkbox
    if(isset($_POST['book_for_operator'])){
        $obj->newsletter = 9;
    }

    if($operator->id == 1){
        $obj->newsletter = 1;
    }

    $obj->product = $_POST['city_product'];

    $obj->class_note = $_POST['note_class'][$key];;

    $obj->total = $_POST['price'][$key];

    $DB->insert_record('booking_data3', $obj);

    enroleVcode($user, $_POST['city_product']);
    assignTeacherRole($user, $_POST['city_product']);
    $group_id = createGroup($ontour_bid, $course, $school_class_name);
    assignTeacherToGroup($group_id, $user);

    $teacher_id = $user;
    $pdf_pages = [];

    //create 5 random users which will be used as students group accounts
    for ($i = 1; $i <= 6; $i++) {

        $obj = new stdClass();
        $obj->auth = "manual";
        $obj->confirmed = 1;
        $obj->username = "g".$i."_".rand(1000 ,9999)."_".$ontour_bid;
        $obj->password = password_hash($obj->username, PASSWORD_DEFAULT);

        if($i == 6){
            $obj->firstname = "Leiter";
            $obj->lastname = "_".$class;
        }else{
            $obj->firstname = "Gruppe";
            $obj->lastname = $i."_".$class;
        }


        $obj->email = $obj->username."@ontour.org";
        $obj->department = "";
        $obj->timecreated = time();
        $obj->timemodified = time();

        if($i == 6){
            $obj->institution = "leiter_task";
            $obj->idnumber = time();
            $section = createSection($ontour_bid, $class, $obj->idnumber);
            $obj->middlename = $section;

            $data = new stdClass();
            $data->id = $user;
            $data->middlename = $section;
            $DB->update_record('user', $data);

        }

        $obj->mnethostid = 1;
        $obj->lang = "de";
        //$obj->idnumber = "dev_restriction";
        $obj->phone2 = "Gruppe_".$i;
        $obj->department = "mitarbeiter_restriction";
        $id = $DB->insert_record('user', $obj, true, false);
        $users[] = $id;



        //create Zugangsdaten PDFs
        if($i != 6){
            copyUserToApp($id, $obj, '1');
            $pdf_date = date('d-m-Y');
            $pdf_name = $ontour_bid."-".$class."-Gruppe".$i."-App-Anmeldedaten.pdf";
            $pdf_path = "/finishing/".$pdf_name;
            $content = createPDFCompany($i, $group_tmp, $obj->username,$order_id);

            $pdf_pages[] = $content;
        }else{
            copyUserToApp($id, $obj, '0');
            $pdf_date = date('d-m-Y');
            $pdf_name =$ontour_bid."-".$class."-Lehrergruppe-App-Anmeldedaten.pdf";
            $pdf_path = "/finishing/".$pdf_name;
            $content = createPDFLeiter($i, $group_tmp, $obj->username,$order_id);

            $pdf_pages[] = $content;

            //create task overview  page for the app

        }


        require_once($CFG->libdir.'/pdflib.php');
        $pdf = new pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->AddPage();
        $pdf->writeHTMLCell(0, 0, '', '', $content, 0, 0, 0, true, '', true);
        ob_clean();

        if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid)) {
            mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid, 0777, true);
        }

        if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid.'/'.$class)) {
            mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid.'/'.$school_class_name, 0777, true);
        }

        $file = $pdf->Output('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid.'/'.$school_class_name.'/'.$pdf_name, 'F');

    }


    $pdf_name = $ontour_bid."-Alle-Accounts".$school_class_name."-App-Anmeldedaten.pdf";
    $pdf = new pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    foreach ($pdf_pages as $page){

        $pdf->AddPage();
        $pdf->writeHTMLCell(0, 0, '', '', $page, 0, 0, 0, true, '', true);

    }
    ob_clean();
    $file = $pdf->Output('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/zugangsdaten/'.$ontour_bid.'/'.$school_class_name.'/'.$pdf_name, 'F');



    foreach ($users as $user_student){

        // assign student to group
        $obj = new stdClass();
        $obj->groupid = $group_id;
        $obj->userid = $user_student;
        $obj->timeadded = time();
        $group_members_id = $DB->insert_record('groups_members', $obj, true, false);

        //enrol user to course

        $sql = "SELECT * FROM mdl_enrol WHERE courseid = '$course_id' && enrol = 'manual'";
        $enrol = $DB->get_record_sql($sql);


        $obj = new stdClass();
        $obj->enrolid = $enrol->id;
        $obj->userid = $user_student;
        $obj->timestart = time();
        $obj->timecreated = time();
        $obj->timemodified = time();
        $group_members_id = $DB->insert_record('user_enrolments', $obj, true, false);

        //assign student role

        $sql = "SELECT * FROM mdl_context WHERE instanceid = '$course_id' && contextlevel = '50' && depth = 3";
        $contextid = $DB->get_record_sql($sql);

        $obj = new stdClass();
        $obj->roleid = "5";
        $obj->contextid = $contextid->id;
        $obj->userid = $user_student;
        $obj->modifierid = "2";
        $obj->timemodified = "1647872831";
        $group_members_id = $DB->insert_record('role_assignments', $obj, true, false);
    }

    setPlannedTask($operator, $ontour_bid, $key, $user);


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


function createWordpressBooking($operator, $price){

    if($_SERVER['HTTP_HOST'] == "www.projektreise.sk"){
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "ontour_wordpress";
    }else{
        $servername = "localhost";
        $username = "skuehn22";
        $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
        $dbname = "projektreisenWordpress_1637922561";
    }


    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }


    $totalprice = $price * count($_POST['school_classes']);
    $tax = $totalprice * 0.19;

    $date = date('Y-m-d H:i:s');

    $sql = "INSERT INTO wp_posts (post_author, post_date, post_date_gmt, post_title, post_status, comment_status, post_name, post_type, post_content, post_excerpt, to_ping, pinged, post_parent, menu_order,post_content_filtered)
        VALUES ('1', '$date', '$date', 'Order Moodle Backend', 'wc-processing', 'closed', 'Order Moodle Backend', 'shop_order', '', '', '', '', '0', '0', '')";

    if ($conn->query($sql) === TRUE) {

        $b_id = $conn->insert_id;

        $sql = "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_order_version', '6.9.4');";

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_order_total', '$totalprice');";

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_order_tax', '$tax');";

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_order_shipping_tax', '');";

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_order_shipping', '0');";

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_cart_discount_tax', '0');";

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_cart_discount', '0');";

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_order_currency', '0');";

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_cart_discount', '0');";

        $mail_billing = $_POST['email'];

        if(isset($_POST['test-booking'])){
            $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_billing_email', 'testbuchung@ontour.org');";
        }else{
            $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_billing_email', '$mail_billing');";
        }

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_billing_country', 'Deutschland');";


        $city = $_POST['city'];

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_billing_city', '$city');";

        $street = $_POST['street'];

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_billing_address_1', '$street');";

        $plz = $_POST['plz'];

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_billing_postcode', '$plz');";


        $school = $_POST['school'];
        $org = $_POST['org'];

        if(isset($_POST['test-booking'])){
            if($_POST['school'] != ""){
                $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_billing_company', 'Testbuchung - $org');";
            }else{
                $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_billing_company', 'Testbuchung');";
            }
        }else{
            if($_POST['school'] != ""){
                $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_billing_company', '$org');";
            }else{
                $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_billing_company', 'moodle backend');";
            }
        }




        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];

        if(isset($_POST['test-booking'])){

            if($_POST['firstname'] != ""){
                $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ('$conn->insert_id', '_billing_last_name', '$lastname'.);";
            }else{
                $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ('$conn->insert_id', '_billing_last_name', 'TESTBUCHUNG');";
            }
            $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ('$conn->insert_id', '_billing_first_name', '$firstname');";

        }else{
            if($_POST['firstname'] != ""){
                $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ('$conn->insert_id', '_billing_last_name', '$lastname');";
                $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ('$conn->insert_id', '_billing_first_name', '$firstname');";
            }else{
                $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ('$conn->insert_id', '_billing_last_name', '$operator->name');";
                $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ('$conn->insert_id', '_billing_first_name', 'Veranstalter');";
            }



            //$sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value) VALUES ('$conn->insert_id', '_billing_first_name', 'Veranstalter');";
        }




        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_cart_hash', 'moodle backend');";

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_created_via', 'moodle backend');";

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_customer_user_agent', 'moodle backend');";

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_customer_ip_address', '79.218.142.248');";

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_customer_user_agent', 'moodle backend');";

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_payment_method_title', 'moodle backend');";

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_payment_method', 'moodle backend');";

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_customer_user', '');";

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', '_order_key', 'wc_order_xxx');";

        $sql .= "INSERT INTO wp_postmeta (post_id, meta_key, meta_value)
        VALUES ('$conn->insert_id', 'product_options', 'a:0:{}');";

        $conn->multi_query($sql);

        $conn->close();

        return $b_id;

    }

    return false;

}

function createWordpressProductVariantsBooking($v_code, $class_name, $ontour_bid, $key, $price){

    if($_SERVER['HTTP_HOST'] == "www.projektreise.sk"){
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "ontour_wordpress";
    }else{
        $servername = "localhost";
        $username = "skuehn22";
        $password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
        $dbname = "projektreisenWordpress_1637922561";
    }

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $totalprice = $price * count($_POST['school_classes']);
    $tax = $totalprice * 0.19;

    $sql = "INSERT INTO wp_woocommerce_order_items (order_item_name, order_item_type, order_id )
        VALUES ('DE-MWST. 19 % DE-1', 'tax', '$ontour_bid')";

    $conn->query($sql);

    $sql = "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', 'rate_id', '68');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', 'label', 'MwSt. 19 % DE');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', 'compound', '');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', 'tax_amount', '38.319328');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', 'shipping_tax_amount', '0');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', 'rate_percent', '19');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', 'Group Enrollment', 'no');";

    $conn->multi_query($sql);


    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    $sql = "INSERT INTO wp_woocommerce_order_items (order_item_name, order_item_type, order_id ) VALUES ('Videoevent', 'line_item', '$ontour_bid')";

    $conn->query($sql);

    $sql = "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_product_id', '9037');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_variation_id', '0');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_qty', '1');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_tax_class', '');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_line_subtotal', '$totalprice');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_line_subtotal_tax', '$tax');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_line_total', '$totalprice');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_line_tax', '$tax');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_line_tax_data', '');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_unit', '');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_unit_base', '');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_unit_product', '');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_item_desc', '');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_defect_description', '');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_delivery_time', '');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_deposit_amount_per_unit', '0');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_deposit_net_amount_per_unit', '0');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_deposit_quantity', '1');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_deposit_amount', '0');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_deposit_net_amount', '0');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_deposit_packaging_type', '');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', 'V-Code', '$v_code');";

    $arrival = $_POST['arrival'][$key];

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', 'anreise', '$arrival');";

    $departure = $_POST['departure'][$key];

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', 'abreise', '$departure');";

    $students = $_POST['students'][$key];

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', 'anzahl_schuler', '$students');";

    $age = $_POST['age'][$key];

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', 'alter_schuler', '$age');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', 'gruppenname', '$class_name');";

    $wv = $_POST['note'];

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', 'Wiedervorlage', '$wv');";

    $wvd = $_POST['reminder'];

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', 'Wiedervorlage Datum', '$wvd');";

    $op = $_POST['operators'];

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', 'Veranstalter', '$op');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_alg_wc_pif_global', 'a:0:{}');";

    $sql .= "INSERT INTO wp_woocommerce_order_itemmeta  (order_item_id, meta_key, meta_value )
        VALUES ('$conn->insert_id', '_alg_wc_pif_local', 'a:0:{}');";

    $conn->multi_query($sql);






    $conn->close();

}

function createVcodeUser($v_code, $class, $b_id){

    global $DB;


    $sql = "SELECT * FROM mdl_user WHERE username = '$v_code'";
    $check = $DB->get_record_sql($sql);

    if(empty($check)){
        //create user
        $obj = new stdClass();
        $obj->auth = "manual";
        $obj->confirmed = 1;
        $obj->username =  $v_code;
        $obj->password = password_hash($v_code, PASSWORD_DEFAULT);

        if($_POST['firstname'] != ""){
            $obj->firstname = $_POST['firstname'];
        }else{
            $obj->firstname = $class;
        }

        if($_POST['lastname'] != ""){
            $obj->lastname = $_POST['lastname'];
        }else{
            $obj->lastname = " ";
        }

        if($_POST['email'] != ""){
            $obj->email = $_POST['email'];
        }else{
            //$obj->email = "no_mail@ontour.org";
            $obj->email = time()."@no_ontour.org";
        }

        $obj->timecreated = time();
        $obj->timemodified = time();
        $obj->institution = "";
        $obj->mnethostid = 1;
        $obj->lang = "de";
        $obj->idnumber = "new";
        $obj->imagealt = "1";
        $obj->phone1 = $b_id;
        $obj->phone2 = $class;
        $obj->department = "";
        $user = $DB->insert_record('user', $obj, true, false);

        copyUserToApp($user, $obj, '2');

        return $user;
    }else{
        return $user = "error";
    }



}

function enroleVcode($user, $course_id){

    global $DB;


    $sql = "SELECT * FROM mdl_enrol WHERE courseid = '$course_id' && enrol = 'manual'";
    $enrol = $DB->get_record_sql($sql);

    //enrol user to course
    $obj = new stdClass();
    $obj->enrolid = $enrol->id;
    $obj->userid = $user;
    $obj->timestart = time();
    $obj->timecreated = time();
    $obj->timemodified = time();
    $obj->modifierid = "2";
    $obj->timestart = "0";
    $obj->timeend = "0";

    return $DB->insert_record('user_enrolments', $obj, true, false);

    return $group_members_id;
}

function assignTeacherRole($user, $course_id){

    global $DB;

    $sql = "SELECT * FROM mdl_context WHERE instanceid = '$course_id' && contextlevel = '50' && depth = 3";
    $contextid = $DB->get_record_sql($sql);

    //assign non editing teacher role
    $obj = new stdClass();
    $obj->roleid = "4";
    $obj->contextid = $contextid->id;
    $obj->userid = $user;
    $obj->modifierid = "2";
    $obj->timemodified = time();

    return $DB->insert_record('role_assignments', $obj, true, false);

}

function createGroup($b_id, $course, $class){

    global $DB;

    //create group
    $obj = new stdClass();
    $obj->courseid = $course->id;
    $obj->name = $b_id."-".$class;
    $obj->description = $b_id;

    return $DB->insert_record('groups', $obj, true, false);

}

function assignTeacherToGroup($group_id, $user){

    global $DB;

    //assign teacher to the created group
    $obj = new stdClass();
    $obj->groupid = $group_id;
    $obj->userid = $user;
    $obj->timeadded = time();

    return $DB->insert_record('groups_members', $obj, true, false);

}

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

function prepareClassName($class){

    $school_class_name = str_replace(' ', '_', $class); // Replaces all spaces with hyphens.
    $remove = array("@", "#", "(", ")", "*", "/", "&");
    $school_class_name = str_replace($remove, "", $school_class_name);
    $school_class_name = str_replace(".","_",$school_class_name);

    return $school_class_name;
}


function setPlannedTask($operator, $ontour_bid, $key, $user){

    global $USER;
    global $DB;


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
            $today   = new DateTime($_POST['arrival'][$key]);
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
    $today   = new DateTime($_POST['arrival'][$key]);
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
    $today   = new DateTime($_POST['arrival'][$key]);
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
    $today   = new DateTime($_POST['arrival'][$key]);
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
    $today   = new DateTime($_POST['departure'][$key]);
    $today->modify('+'.$operator->mailing_4.' day');
    $today->modify('+9 hour');
    $obj->action_date =  $today->format('Y-m-d H:i:s');
    $DB->insert_record('booking_history3', $obj);



}

function createSection($ontour_bid,$class, $user){


    global $DB;
    $course = 8;
    $max_section = $DB->get_field_sql('SELECT MAX(section) FROM {course_sections}');
    $max_section = $max_section +1;

    // First add section to the end.
    $cw = new stdClass();
    $cw->course   = $course;
    $cw->section  = $max_section;
    $cw->summary  = '<p dir="ltr" style="text-align: left; font-size:14px!important;"><strong>Gruppe 1</strong></p><div>
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
    $cw->availability = '{"op":"&","c":[{"type":"profile","sf":"idnumber","op":"isequalto","v":"'.$user.'"}],"showc":[true]}';
    $cw->timemodified = time();
    $cw->id = $DB->insert_record("course_sections", $cw);
    rebuild_course_cache('8', true);

    return $cw->id;

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
            <strong><u>Bitte achten Sie als Spielleiter*in darauf</u></strong>
             <ul style="line-height: 17px; margin-top: -30px;">
                <li style="color: red;"><span style="color: #000;">Mindestens eine Person pro Gruppe muss die App auf dem Handy heruntergeladen haben.</span></li>
                <li>Es wird etwas Datenvolumen und ein geladener Akku vor Ort benötigt.</li>
                <li>Die App muss vor Durchführung geschlossen und wieder geöffnet werden, damit die aktuelle Version genutzt wird.</li>
                <li>In Berlin wird das Videoevent Gruppenweise eigenständig durchgeführt. Die Gruppenbildung und
Wahl des Drehortes erfolgen unter Ihrer Anleitung. </li>
            </ul>
        </td>
        <td></td>
    </tr>
	</tbody>
	</table>
	';


    return $html;

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

    $sql = "INSERT INTO users (name, email, password, moodle_uid, login_token, role, course) VALUES ( '$obj->username' ,  '$obj->email' ,  '$obj->password' ,  '$id', '$token', '$role', 9)";

    ($conn->query($sql));

}


if($_SERVER['HTTP_HOST'] == "www.projektreise.sk"){
    header('Location: ../view.php?company=true&id=15&status=success&b_id='.$ontour_bid);
}else{
    header('Location: ../view.php?company=true&id=172&status=success&b_id='.$ontour_bid);
}

