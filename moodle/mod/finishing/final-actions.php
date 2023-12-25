<?php

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

global $DB, $CFG;

$course = $_POST['course'];
$user = $_POST['user'];
$group = $_POST['school_class'];
$user_data = $DB->get_record('user', array('id' => $user));

/**
 * Creates the final invoice pdf
 */

//get the amount of students which took part in the course
$sql = "SELECT id, name FROM {finishing_students}  WHERE fk_user = $user && fk_course = $course && status = 0";
$students = $DB->get_records_sql($sql);
$price_per_student = 6;


$pdf_date = date('d-m-Y');
$pdf_name = "Rechnung-".$pdf_date."-".preg_replace('/\s+/', '_', $user_data->department).".pdf";
$pdf_path = "/finishing/".$pdf_name;

$in = getInvoiceNumber($DB);
$total = $price_per_student * count($students);

$content = createPDF($in, $user_data, $total, count($students));

require_once($CFG->libdir.'/pdflib.php');
$pdf = new pdf(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->AddPage();
$pdf->writeHTMLCell(0, 0, '', '', $content, 0, 0, 0, true, '', true);
$file = $pdf->Output($CFG->dataroot . '/finishing/'.$pdf_name, 'F');


/**
 * All accounts will be disabled
 */

// get all group members except the teacher
$sql = "SELECT * FROM {groups_members} WHERE groupid = $group && userid <> $user";
$students = $DB->get_records_sql($sql);

//deactivate each account
foreach($students as $key => $student){
    $sql = "SELECT id FROM {user} WHERE id = $student->userid";
    $student = $DB->get_record_sql($sql);
    $student->deleted = 1;
    $DB->update_record('user', $student);
}


/**
 * Send final e-mail to teacher with pdf invoice attached
 */

$supportuser = core_user::get_support_user();

$data = new stdClass();
$data->firstname = $user_data->firstname." ".$user_data->lastname;
$data->count_student = count($students);

$subject = get_string('mail_subject', 'mod_finishing', format_string('Projektreise'));
$message = new \core\message\message();
$message->fullmessage       = 'message body';
$messagehtml = text_to_html(get_string('mail_content', 'mod_finishing', $data), false, false, true);

email_to_user($user_data, $supportuser, $subject, $message, $messagehtml, $pdf_path, $pdf_name);


saveInvoice($DB, $pdf_name, $course, $user);


/**
 *  Gets the next invoice number
 */

function getInvoiceNumber($DB){
    $sql = "SELECT id FROM {finishing_invoices}  WHERE state = 1";
    $invoices = $DB->get_record_sql($sql);
    return count($invoices)+1;
}

/**
 * Saves all relevant invoice data after creation
 */

function saveInvoice($DB, $name, $course, $user){

    $obj = new stdClass();
    $obj->number = $name;
    $obj->fk_course = $course;
    $obj->fk_user = $user;
    $obj->state = 0;
    $obj->timecreated = time();
    $obj->timemodified = time();

    $test = "";

    $DB->insert_record('finishing_invoices', $obj, true, false);

    return true;

}

/**
 * Build the actual content of the pdf invoice
 */

function createPDF($in, $user_data, $total, $quantity){

    $html = '
	<style>
	table, tr, td {
	padding: 15px;
	}
	</style>
	<table style="background-color: #222222; color: #fff">
	<tbody>
	<tr>
	<td><h1>Rechnung<strong> #'.$in.'</strong></h1></td>
	<td align="right"><img src="logo.png" height="60px"/><br/>

	Luma Blended Learning GmbH<br/>
	Schönhauser Allee 36 <br/>
	10435 Berlin
	<br/>
	<strong>+00-1234567890</strong> | <strong>abc@xyz</strong>
	</td>
	
	</tr>
	</tbody>
	</table>
	';
    $html .= '
	<table>
	<tbody>
	<tr>
	<td>Rechnungsempfänger<br/>
	<strong>'.$user_data->department.'</strong>
	<br/>
	'.$user_data->address.'
	</td>
	<td align="right">
	<strong>Gesamtbetrag: '.number_format($total, 2, ',', ' ').' EUR</strong><br/>
	Steuernummer: ABCDEFGHIJ12345<br/>
	Rechnungsdatum: '.date('d-m-Y').'
	</td>
	</tr>
	</tbody>
	</table>
	';
    $html .= '
	<table>
	<thead>
	<tr style="font-weight:bold;">
	<th>Reise</th>
	<th>Preis</th>
	<th>Menge</th>
	<th>Summe</th>
	</tr>
	</thead>
	<tbody>
		<tr>
		<td style="border-bottom: 1px solid #222">DDR Klassenfahrt</td>
		<td style="border-bottom: 1px solid #222">6 €/Person</td>
		<td style="border-bottom: 1px solid #222">'.$quantity.'</td>
		<td style="border-bottom: 1px solid #222">'.number_format($total, 2, ',', ' ').' EUR</td>
		</tr>
	';

    $html .='
	<tr align="right">
	<td colspan="4"><strong>Gesamtsumme: '.number_format($total, 2, ',', ' ').' EUR</strong></td>
	</tr>
	<tr>
	<td colspan="4">
	<h2>Vielen Dank, dass Sie mit uns gereist sind.</h2><br/>

	</td>
	</tr>
	</tbody>
	</table>
	';

    return $html;

}


/*
 *  No usage at the moment
 */

function internal_message($user){

    global $DB, $CFG;

    $user1 = $DB->get_record('user', array('id' => $user));
    $user2 = $DB->get_record('user', array('id' => "7"));

    // Extra content for all types of messages.
    $message = new \core\message\message();
    $message->courseid          = 1;
    $message->component         = 'moodle';
    $message->name              = 'instantmessage';
    $message->userfrom          = $user2;
    $message->userto            = $user1;
    $message->subject           = 'message subject 1';
    $message->fullmessage       = 'message body';
    $message->fullmessageformat = FORMAT_MARKDOWN;
    $message->fullmessagehtml   = '<p>message body</p>';
    $message->smallmessage      = 'small message';
    $message->notification      = '0';
    $content = array('*' => array('header' => ' test ', 'footer' => ' test '));
    $message->set_additional_content('email', $content);

    #$sink = $this->redirectEmails();
    $messageid = message_send($message);
    $test = "2";
    #$emails = $sink->get_messages();
}

