<?php

use tool_brickfield\accessibility;

require(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/../../lib.php');

global $USER;
global $DB;
global $CFG;

$user_id = $_GET['user'];
$b_id = $_GET['b_id'];

/* get email sender user --> kontakt@ontour.org */
$sql = "SELECT * FROM mdl_user WHERE id = 6163";
$from_user = $DB->get_record_sql($sql);

/* get the all bookings */
$sql = "SELECT * FROM mdl_booking_data3 WHERE state = 0 && order_id = ".$_GET['b_id'];
$bookings = $DB->get_records_sql($sql);

/* generate z-codes table for mail text */
$z_codes_string = get_z_codes($bookings);

/* get all booking data from wordpress */
$data = getData($b_id);



foreach ($bookings as $booking){
   $tmp = $booking->operators_id;
}

/* create greeting */
if($booking->operators_id == 1){
    $anrede = "Hallo ".$data[0]['firstname']." ".$data[0]['lastname'];
}else{

    if($booking->newsletter == 9){
        $anrede = "Hallo ".$data[0]['firstname']." ".$data[0]['lastname'];
    }else{
        $anrede = "Liebe Lehrkraft";
    }
}

$sql = "SELECT * FROM mdl_ext_operators1 WHERE id = ".$booking->operators_id;
$op = $DB->get_record_sql($sql);


if($booking->operators_id == 1){

    $invoice_number = getInvoiceNumber($DB) + 1100;

    $pdf_date = date('d-m-Y');
    $pdf_name = "Rechnung-".$invoice_number.".pdf";

    $total = 480;

    $content = createPDF($invoice_number, $data, $total, $b_id, $booking);

    if (!file_exists('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/invoices/'.$b_id)) {
        mkdir('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/invoices/'.$b_id, 0777, true);
    }


    require_once($CFG->libdir.'/pdflib.php');

    class bfpdf extends pdf {
        /**
         * Overriding the footer function in TCPDF.
         */
        public function Footer() {

            $footer_text = '<hr><br><table><tr  style="width: 90%;"> 

            <td style="width: 29%">
            <b>Postanschrift</b><br>
            onTour Media GmbH<br>
            Schönhauser Allee 36 – 39<br>
            10435 Berlin, Kulturbrauerei
            </td>
            
            <td style="width: 34%">
            <b>Bankverbindung</b><br>
            Bank: Ethikbank<br>
            IBAN: DE37 8309 4495 0003 4649 54<br>
            BIC: GENODEF1ETK
            </td>
            
            <td style="width: 25%">
            Scannen Sie den <b>QR-Code</b><br>
            für weitere Informationen
            </td>
            
            <td style="width: 23%">
            <img src="https://ontour.org/wp-content/uploads/qr-code-ontour-startseite.png" style="width: 80px;">
            </td>
            
            
            </tr> 
            </table>';


            $this->SetY(-25);
            $this->SetFont('helvetica', '', 9);
            $this->writeHTML($footer_text, false, false, false, false, '');
        }
    }


    $pdf = new bfpdf('P','mm','A4',true,'utf-8');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->AddPage();
    $pdf->writeHTMLCell(0, 0, '', '', $content, 0, 0, 0, true, '', true);
    $pdf->setFooterMargin(10);

    $file = $pdf->Output('/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/invoices/'.$b_id.'/'.$pdf_name, 'F');


    $path_to_file =  '/home/runcloud/webapps/app-moodle-ontour/moodle/filemanager/invoices/'.$b_id.'/'.$pdf_name;

    saveInvoice($DB, $b_id, "8", $booking->user_id);

}


$date = new DateTime($booking->arrival);
$kw = $date->format('W');

if($kw != ""){
    $sub_kw = "KW ".$kw;
}

/* create greeting */
if($booking->operators_id == 1 || $booking->operators_id == 9){
    $email_subject = "ZCODES Videoprojekt Berlin - Buchungs-Nr. ".$b_id." ".$sub_kw;
}else{

    if($booking->newsletter == 9){
        $email_subject = "ZCODES Videoprojekt Berlin - ".$booking->ext_booking_id. " - ".$b_id." ".$sub_kw;
    }else{
        $email_subject = $op->name." ZCODES  Videoprojekt Berlin - Nr. Veranstalter. ".$booking->ext_booking_id." ".$sub_kw;
    }


}


ob_start();

if($booking->newsletter == 9){
    include "codes_template_no_style.php";
}else{
    include "codes_template.php";
}


$message = ob_get_clean();


set_config('allowedemaildomains', 'ontour.org');
set_config('emailheaders', 'X-Fixed-Header: bar');


if($op->mailing_traveler == 0){

    $emailuser = new stdClass();
    $emailuser->email = $op->mail;
    $emailuser->firstname = $op->name;
    $emailuser->lastname = "";

}else{
    
    $emailuser = new stdClass();
    $emailuser->email = $data[0]['email'];
    $emailuser->firstname = $data[0]['firstname'];
    $emailuser->lastname = $data[0]['lastname'];

}

$emailuser->maildisplay = true;
$emailuser->mailformat = 1;
$emailuser->id = -99;
$emailuser->firstnamephonetic = "";
$emailuser->lastnamephonetic = "";
$emailuser->middlename = "";
$emailuser->alternatename = "";


if($booking->operators_id == 1){
    email_to_user($emailuser, $from_user, $email_subject, $message, '', $path_to_file, $pdf_name);
}else{
    email_to_user($emailuser, $from_user, $email_subject, $message);
}


$email_subject = "Kopie MAIL MANUELL(".$emailuser->email."): ".$email_subject;

if ($booking->operators_id == 1) {
    email_to_user($from_user, $from_user, $email_subject, $message, '', $path_to_file, $pdf_name);
} else {
    email_to_user($from_user, $from_user, $email_subject, $message);
}

$sql = "SELECT * FROM mdl_booking_data3 WHERE state = 0 && order_id = ".$_GET['b_id'];
$bookings = $DB->get_records_sql($sql);

foreach ($bookings as $booking){


    $sql = "SELECT * FROM mdl_booking_history3 WHERE user_id = '$booking->user_id' && type = 'mail_co' && state = 2";
    $mail_mo = $DB->get_record_sql($sql);

  
    if($mail_mo){
        //echo $mail_mo->id;
        $data = new stdClass();
        $data->id = $mail_mo->id;
        $data->state = 1;
        $DB->update_record('booking_history3', $data);

    }else{

        echo "hier";
        /*
           $obj = new stdClass();
           $obj->b_id = $_GET['b_id'];
           $obj->user_id = $user->id;
           $obj->receiver = "";
           $obj->sender = "";
           $obj->type = "mail_co";
           $obj->subject = "Codes geschickt";
           $obj->state = 1;
           $DB->insert_record('booking_history3', $obj);
        */
    }

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

function getInvoiceNumber($DB){
    $sql = "SELECT * FROM {finishing_invoices}  WHERE state = 1";
    $invoices = $DB->get_records_sql($sql);
    return count($invoices)+1;
}

function saveInvoice($DB, $name, $course, $user){

    $obj = new stdClass();
    $obj->number = $name;
    $obj->fk_course = $course;
    $obj->fk_user = $user;
    $obj->state = 1;


    $DB->insert_record('finishing_invoices', $obj, true, false);

    return true;

}


function createPDF($in, $data, $total, $b_id, $booking){

    $html = '
	<style>
	table, tr, td {
	padding: 15px;
	}
	</style>
	<table style="background-color: #fff; color: #000">
	<tbody>
	<tr>
	    <td colspan="2" style="text-align: right;"><img src="https://reisen.ontour.org/pix/theme/onTour.png"></td>
    </tr>
    <tr>
	   <td colspan="2">onTour Media GmbH | Schönhauser Allee 36 – 39 | 10435 Berlin</td>
    </tr>
	<tr>
	<td>'.$booking->school.'<br>'.$data[0]['firstname'].' '.$data[0]['lastname'].'<br>'.$data[0]['addr'].'<br>'.$data[0]['zip'].' '.$data[0]['city'].'
	
    </td>
	<td>

	    <table style="padding: 0px!important;">
	    <tr style="padding: 0px!important;">
	        <td style="padding: 0px!important;">Rechnungsdatum: </td>
	        <td style="padding: 0px!important; text-align: right;">'.date('d.m.y').'</td>
	    </tr> 
	    <tr style="padding: 0px!important;">   
	        <td style="padding: 0px!important;">Rechnungsnummer: </td>
	        <td style="padding: 0px!important; text-align: right;">'.$in.'</td>
	    </tr>
	    <tr style="padding: 0px!important;">    
	        <td style="padding: 0px!important;">Bestellnummer:</td>
	        <td style="padding: 0px!important; text-align: right;"> '.$b_id.'</td>
	    </tr>
	    <tr style="padding: 0px!important;">
	        <td style="padding: 0px!important;">Zahlungsart: </td>
	        <td style="padding: 0px!important; text-align: right;">Rechnung</td>
        </tr>
        </table>
	
	</td>
	
	</tr>
	</tbody>
	</table>
	';
    $html .= '
	<table style="padding: 5px;  padding-left: 15px;">
	<tbody>
	<tr>
	<td colspan="2">
	    <span style="font-weight: 700; font-size: 18px;">RECHNUNG</span>
	</td>
	</tr>
	</tbody>
	</table>
	';
    $html .= '
	<table>
	<tbody>
	<tr>
	<td colspan="2">
	    <p>
	      Wir freuen uns, dass Sie das Videoprojekt gebucht haben.<br>
          Folgende Leistung berechnen wir Ihnen.
        </p>
	</td>
	</tr>
	</tbody>
	</table>
	';
    $html .= '
	<table style="padding: 5px; padding-left: 15px;">
	<thead>
	<tr>
	<th style="font-weight:bold; border-bottom: 1px solid #000; border-top: 1px solid #000;padding-top: 10px;padding-bottom: 10px;">Bezeichnung</th>
	<th  style="font-weight:bold; border-bottom: 1px solid #000; border-top: 1px solid #000; text-align: right;">Gesamt</th>
	</tr>
	</thead>
	<tbody>';

         $i = 0;
	     foreach ($data as $d){
             $html .='
                <tr>
                    <td><strong>Videoprojekt Berlin</strong></td>
                    <td style="text-align: right;padding-top: 10px;padding-bottom: 10px!important;">'.$data[$i]['item_price'].' €</td>
                </tr>
                <tr>
                    <td>Leistungszeitraum</td>
                    <td style="text-align: right;">'.$data[$i]['arr'].' '.$data[$i]['dep'].'</td>
                </tr>
                 <tr>
                    <td>Durchführung und Erinnerungsfilm</td>
                    <td style="text-align: right;"></td>
                </tr>
                 <tr>
                    <td>Klassenname: '.$data[$i]['gruppe'].'</td>
                    <td style="text-align: right;"></td>
                </tr>
                ';

             $i++;
         }



    $html .='
        <tr style="text-align: right;">
            <td>
         
            </td>
            <td>
              <table style="padding: 0px!important;">
                    <tr style="padding: 0px!important;">
                        <td style="padding: 0px!important;"><strong>Summe Netto</strong></td>
                        <td style="padding: 0px!important; text-align: right;">'.$data[0]['line_total'].'</td>
                    </tr> 
                    <tr style="padding: 0px!important;">   
                        <td style="padding: 0px!important;"><strong>19 % MwSt.</strong></td>
                        <td style="padding: 0px!important; text-align: right;">'.$data[0]['tax'].'</td>
                    </tr>
                    <tr style="padding: 0px!important;">    
                        <td style="padding: 0px!important;border-bottom: 1px solid #000; border-top: 1px solid #000;"><strong>Rechnungsbetrag</strong></td>
                        <td style="padding: 0px!important; text-align: right;border-bottom: 1px solid #000; border-top: 1px solid #000;"><strong> '.$data[0]['total'].'</strong></td>
                    </tr>
                
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="2"><br><br>Bitte überweisen Sie den Rechnungsbetrag umgehend, mit der Angabe Ihrer RechnungsNr., auf das unten aufgeführte Konto.<br><br>Mit freundlichen Grüßen und bis zum nächsten Mal<br><img src="https://ontour.org/wp-content/uploads/Unterschrit-Matthias-Enter-onTour.png" style="width: 140px;"><br>Matthias Enter
            </td>
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