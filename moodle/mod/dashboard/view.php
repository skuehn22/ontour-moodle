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
 * Prints an instance of mod_dashboard.
 *
 * @package     mod_dashboard
 * @copyright   2023 onTour <info@ontour.org>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$d = optional_param('d', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('dashboard', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('dashboard', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('dashboard', array('id' => $d), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('dashboard', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);


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

$conn = new mysqli($servername, $username, $password, $dbname);

$bookings = getBookings();
$data = [];
$i = 0;

$sql = "SELECT COUNT(DISTINCT school)  FROM mdl_booking_data3  WHERE state = '0'";
$schools = $DB->get_record_sql($sql);





$bundesland = [];
$missing_code = 0;
$missing_arr = [];
$schulen = 0;



foreach ($bookings as $booking){



    if($booking->school != "TESTBUCHUNG"){
        //echo $booking->school;echo " - ".$booking->order_id;echo "<br>";

        $sql = "SELECT * FROM mdl_user WHERE id='$booking->user_id'";
        $user = $DB->get_record_sql($sql);

        $sql = "SELECT * FROM mdl_booking_history3 WHERE user_id='$booking->user_id' && type='register'";
        $history = $DB->get_record_sql($sql);


        if($tmp_booking == $booking->order_id){

        }else{
            $schulen++;
            $sql2 = "SELECT meta_value FROM wp_postmeta WHERE meta_key = '_billing_postcode' && post_id = '$booking->order_id'";
            $result2 = $conn->query($sql2);

            //echo $result2->num_rows; echo "<br>";

            if ($result2->num_rows > 0) {
                while ($row = $result2->fetch_assoc()) {

                    $zipCode = $row['meta_value'];

                    if($zipCode == ""){
                        $missing_code++;
                        $missing_arr[] = $booking->order_id;
                    }else{
                        //  echo $zipCode." - ".$booking->order_id;
                        // echo "<br><bR>";

                        $mapping = [
                            ['min' => 70173, 'max' => 79999, 'bundesland' => 'Baden-Württemberg'],
                            ['min' => 80001, 'max' => 86999, 'bundesland' => 'Bayern'],
                            ['min' => 90001, 'max' => 97999, 'bundesland' => 'Bayern'],
                            ['min' => 10115, 'max' => 14199, 'bundesland' => 'Berlin'],
                            ['min' => 14401, 'max' => 16999, 'bundesland' => 'Brandenburg'],
                            ['min' => 28001, 'max' => 28779, 'bundesland' => 'Bremen'],
                            ['min' => 20001, 'max' => 21149, 'bundesland' => 'Hamburg'],
                            ['min' => 34001, 'max' => 36469, 'bundesland' => 'Hessen'],
                            ['min' => 60001, 'max' => 64859, 'bundesland' => 'Hessen'],
                            ['min' => 20001, 'max' => 29499, 'bundesland' => 'Niedersachsen'],
                            ['min' => 31501, 'max' => 31789, 'bundesland' => 'Niedersachsen'],
                            ['min' => 49001, 'max' => 49999, 'bundesland' => 'Niedersachsen'],
                            ['min' => 17001, 'max' => 17449, 'bundesland' => 'Mecklenburg-Vorpommern'],
                            ['min' => 18001, 'max' => 19417, 'bundesland' => 'Mecklenburg-Vorpommern'],
                            ['min' => 32001, 'max' => 33739, 'bundesland' => 'Nordrhein-Westfalen'],
                            ['min' => 40001, 'max' => 59999, 'bundesland' => 'Nordrhein-Westfalen'],
                            ['min' => 54290, 'max' => 56869, 'bundesland' => 'Rheinland-Pfalz'],
                            ['min' => 65001, 'max' => 67829, 'bundesland' => 'Rheinland-Pfalz'],
                            ['min' => 66111, 'max' => 66589, 'bundesland' => 'Saarland'],
                            ['min' => 01001, 'max' => '09999', 'bundesland' => 'Sachsen'],
                            ['min' => 06001, 'max' => '06999', 'bundesland' => 'Sachsen-Anhalt'],
                            ['min' => 39001, 'max' => 39999, 'bundesland' => 'Sachsen-Anhalt'],
                            ['min' => 20001, 'max' => 25999, 'bundesland' => 'Schleswig-Holstein'],
                            ['min' => 01001, 'max' => '07999', 'bundesland' => 'Thüringen'],
                            ['min' => 99001, 'max' => 99998, 'bundesland' => 'Thüringen'],
                        ];


                        $bundeslandName = getBundesland($zipCode, $mapping);
                        //echo $bundeslandName; echo "<br>";


                        if (isset($bundesland[$bundeslandName])) {
                            //echo "nein";
                            $bundesland[$bundeslandName]++;
                        } else {
                            $bundesland[$bundeslandName] = 1;
                        }

                    }




                }
            }else{
                $missing_code++;
                $missing_arr[] = $booking->order_id;
            }

            $tmp_booking = $booking->order_id;

        }







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

        $order_item_id = $right_item['order_item_id'];

        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'abreise'";
        $result3 = $conn->query($sql);
        $dep = $result3->fetch_assoc();


        if (strpos($dep['meta_value'], '-') !== false) {

            $pieces = explode("-", $dep['meta_value']);
            $dep = $pieces[2]."/".$pieces[1]."/".$pieces[0];

        }else{
            $pieces = explode(" ", $dep['meta_value']);
            $m = getMonthNumber($pieces[0]);
            $pieces2 = explode(",", $pieces[1]);

            if(strlen($pieces2[0]) == 1){
                $day = "0".$pieces2[0];
            }else{
                $day = $pieces2[0];
            }

            $dep = $pieces[2]."-".$m."-".$day;

        }


        //no fucking clue why needed
        if (strpos($dep, '-') !== false) {
            $pieces = explode("-", $dep);
            $dep = $pieces[2]."/".$pieces[1]."/".$pieces[0];
        }



        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'anreise'";
        $result4 = $conn->query($sql);
        $arr = $result4->fetch_assoc();


        if (strpos($arr['meta_value'], '-') !== false) {

            $pieces = explode("-", $arr['meta_value']);
            $arr = $pieces[2]."/".$pieces[1]."/".$pieces[0];

        }else{
            $pieces = explode(" ", $arr['meta_value']);
            $m = getMonthNumber($pieces[0]);
            $pieces2 = explode(",", $pieces[1]);

            if(strlen($pieces2[0]) == 1){
                $day = "0".$pieces2[0];
            }else{
                $day = $pieces2[0];
            }

            $arr = $day."/".$m."/".$pieces[2];

        }


        $sql = "SELECT * FROM wp_woocommerce_order_itemmeta WHERE order_item_id = '$order_item_id' && meta_key = 'alter_schuler'";
        $result4 = $conn->query($sql);
        $age = $result4->fetch_assoc();
        $data[$i]['age'] = $age['meta_value'];


        $data[$i]['id'] = $booking->id;
        $data[$i]['order_id'] = $booking->order_id;
        $data[$i]['school'] = $booking->school;
        //$data[$i]['op'] =  $op_array[$booking->operators_id];
        $data[$i]['op_id'] =  $booking->operators_id;
        $data[$i]['arr'] =  $arr;
        $data[$i]['dep'] = $dep;
        $data[$i]['op_nr'] = $booking->ext_booking_id;
        $data[$i]['code'] = $user->username;
        $data[$i]['user_id'] = $user->id;
        $data[$i]['code_reg'] = $history->crdate;
        $data[$i]['note_reminder'] = $booking->note_reminder;
        $data[$i]['crdate'] = $booking->crdate;

        $i++;
    }


}
//echo "<br>";echo "<br>";echo "<br>";echo "<br>";echo "<br>";echo "<br>";echo "<br>";echo "<br>";echo "<br>";echo "<br>";echo "<br>";echo "<br>";echo "<br>";echo "<br>";echo "<br>";echo "<br>";echo "<br>";echo "<br>";echo "<br>";echo "<br>";echo "<br>";echo "<br>";
print_r($bundesland);
$booking_count = [];

$booking_count['Januar'] = 0;
$booking_count['Februar'] = 0;
$booking_count['März'] = 0;
$booking_count['April'] = 0;
$booking_count['Mai'] = 0;
$booking_count['Juni'] = 0;
$booking_count['Juli'] = 0;
$booking_count['August'] = 0;
$booking_count['September'] = 0;
$booking_count['Oktober'] = 0;
$booking_count['November'] = 0;
$booking_count['Dezember'] = 0;

$gesamt = 0;
$umsatz = 0;
$check_array = array();

$booking_op = [];
$booking_op['direkt'] = 0;
$booking_op['WBT'] = 0;
$booking_op['Alpetours'] = 0;
$booking_op['Jugendtours'] = 0;
$booking_op['Herole'] = 0;
$booking_op['CTS'] = 0;
$booking_op['EVR'] = 0;
$booking_op['WBT'] = 0;
$booking_op['rev_direkt'] = 0;
$booking_op['rev_WBT'] = 0;
$booking_op['rev_Alpetours'] = 0;
$booking_op['rev_Jugendtours'] = 0;
$booking_op['rev_Herole'] = 0;
$booking_op['rev_CTS'] = 0;
$booking_op['rev_EVR'] = 0;
$booking_op['rev_WBT'] = 0;

$booking_op['pro_direkt'] = 0;
$booking_op['pro_WBT'] = 0;
$booking_op['pro_Alpetours'] = 0;
$booking_op['pro_Jugendtours'] = 0;
$booking_op['pro_Herole'] = 0;
$booking_op['pro_CTS'] = 0;
$booking_op['pro_EVR'] = 0;
$booking_op['pro_WBT'] = 0;

$last_month = date("Y-m-d H:i:s",strtotime("-1 month"));
$last_month = explode("-", $last_month);
$last_monthcounter = 0;
$last_revenue = 0;
$pro_gesamt = 0;
$gewinn = 0;

$age['0'] = 0;
$age['1'] = 0;
$age['2'] = 0;
$age['3'] = 0;
$age['4'] = 0;
$age['not'] = 0;


//print_r($data);

foreach ($data as $d){
   

    $lm = false;

    if($tmp == $d['id']){
        continue;
    }


    $crdate = explode("-", $d['crdate']);
    if($last_month[1] == $crdate[1]){
        $last_monthcounter++;
        $lm = true;
    }


    $pieces = explode("/", $d['arr']);

    if($pieces[2] == "2023"){


        switch ($d['op_id']) {
            case "1":
                $umsatz += 201.68;
                $booking_op['rev_direkt'] += 201.68;
                $booking_op['direkt']++;
                if ($lm == true) {
                    $last_revenue += 201.68;
                }
                $pro_gesamt += 0;
                $gewinn +=  201.68;
                break;
            case 2:
                $umsatz += 151.26;
                $booking_op['rev_WBT'] += 151.26;
                $booking_op['WBT']++;
                $booking_op['pro_WBT'] += number_format((float)(151.26 * 25 / 100), 2, '.', '');
                $booking_op['gewinn_WBT'] += number_format((float)(151.26 - (151.26 * 25 / 100)), 2, '.', '');
                $pro_gesamt += 151.26 * 25 / 100;
                $gewinn += 151.26 - (151.26 * 25 / 100);

                if ($lm == true) {
                    $last_revenue += 151.26;
                }
                break;
            case 3:
                $umsatz += 181.51;
                $booking_op['rev_Alpetours'] += 181.51;
                $booking_op['Alpetours']++;
                $booking_op['pro_Alpetours'] += number_format((float)(181.51 * 15 / 100), 2, '.', '');
                $booking_op['gewinn_Alpetours'] += number_format((float)(181.51 - (181.51 * 15 / 100)), 2, '.', '');
                $pro_gesamt += 181.51 * 15 / 100;
                $gewinn += 181.51 - (181.51 * 15 / 100);

                if ($lm == true) {
                    $last_revenue += 181.51;
                }
                break;
            case 4:
                $umsatz += 181.51;
                $booking_op['rev_Jugendtours'] += 181.51;
                $booking_op['Jugendtours']++;
                $booking_op['pro_Jugendtours'] += number_format((float)(181.51 * 15 / 100), 2, '.', '');
                $booking_op['gewinn_Jugendtours'] += number_format((float)(181.51 - (181.51 * 15 / 100)), 2, '.', '');
                $pro_gesamt += 181.51 * 15 / 100;
                $gewinn += 181.51 - (181.51 * 15 / 100);

                if ($lm == true) {
                    $last_revenue += 181.51;
                }
                break;
            case 5:
                $umsatz += 181.51;
                $booking_op['rev_Herole'] += 181.51;
                $booking_op['Herole']++;
                $booking_op['pro_Herole'] += number_format((float)(181.51 * 15 / 100), 2, '.', '');
                $booking_op['gewinn_Herole'] += number_format((float)(181.51 - (181.51 * 15 / 100)), 2, '.', '');
                $pro_gesamt += 181.51 * 15 / 100;
                $gewinn += 181.51 - (181.51 * 15 / 100);

                if ($lm == true) {
                    $last_revenue += 181.51;
                }
                break;
            case 6:
                $umsatz += 181.51;
                $booking_op['rev_CTS'] += 181.51;
                $booking_op['CTS']++;
                $booking_op['pro_CTS'] += number_format((float)(181.51 * 15 / 100), 2, '.', '');
                $booking_op['gewinn_CTS'] += number_format((float)(181.51 - (181.51 * 15 / 100)), 2, '.', '');
                $pro_gesamt += 181.51 * 15 / 100;
                $gewinn += 181.51 - (181.51 * 15 / 100);

                if ($lm == true) {
                    $last_revenue += 181.51;
                }
                break;
            case 7:
                $umsatz += 181.51;
                $booking_op['rev_EVR'] += 181.51;
                $booking_op['EVR']++;
                $booking_op['pro_EVR'] += number_format((float)(181.51 * 15 / 100), 2, '.', '');
                $booking_op['gewinn_EVR'] += number_format((float)(181.51 - (181.51 * 15 / 100)), 2, '.', '');
                $pro_gesamt += 181.51 * 15 / 100;
                $gewinn += 181.51 - (181.51 * 15 / 100);
                break;
            case 8:
                $umsatz += 181.51;
                $booking_op['rev_KS'] += 181.51;
                $booking_op['KS']++;

                $booking_op['pro_KS'] += number_format((float)(181.51 * 15 / 100), 2, '.', '');
                $booking_op['gewinn_KS'] += number_format((float)(181.51 - (181.51 * 15 / 100)), 2, '.', '');
                $pro_gesamt += 181.51 * 15 / 100;
                $gewinn += 181.51 - (181.51 * 15 / 100);
                break;
            case 9:
                $umsatz += 181.51;
                $booking_op['rev_WTB'] += 181.51;
                $booking_op['WTB']++;
                $booking_op['pro_WTB'] += number_format((float)(181.51 * 15 / 100), 2, '.', '');
                $booking_op['gewinn_WTB'] += number_format((float)(181.51 - (181.51 * 15 / 100)), 2, '.', '');
                $pro_gesamt += 181.51 * 15 / 100;
                $gewinn += 181.51 - (181.51 * 15 / 100);

            default:
                $umsatz += 181.51;
                if ($lm == true) {
                    $last_revenue += 181.51;
                }
                break;
        }

        $gesamt++;

        switch ($pieces[1]) {
            case 01:
                $booking_count['Januar']++;
                break;
            case 02:
                $booking_count['Februar']++;
                break;
            case 03:
                $booking_count['März']++;
                break;
            case 04:
                $booking_count['April']++;
                break;
            case 05:
                $booking_count['Mai']++;
                break;
            case 06:
                $booking_count['Juni']++;
                break;
            case 07:
                $booking_count['Juli']++;
                break;
            case "08":
                $booking_count['August']++;
                break;
            case "09":
                $booking_count['September']++;
                break;
            case 10:
                $booking_count['Oktober']++;
                break;
            case 11:
                $booking_count['November']++;
                break;
            case 12:
                $booking_count['Dezember']++;
                break;

        }

    }


    switch (  $d['age']) {

        case "18 und älter":
            $age['alt4']++;
            break;
        case '17 - 18 Jahre':
            $age['alt3']++;
            break;
        case '16 - 17 Jahre':
            $age['alt2']++;
            break;
        case '15 - 16 Jahre':
            $age['alt1']++;
            break;
        case '14 - 15 Jahre':
            $age['alt0']++;
            break;
        default:
            $test = $d['age'];
            $age['not']++;
            break;
    }


    $tmp = $d['id'];

}



$sql = "SELECT * FROM mdl_booking_data3  WHERE state = '2' && school != 'TESTBUCHUNG'";
$stornos = $DB->get_records_sql($sql);
$stornos_gesamt = count($stornos);


$booking_count['JanuarStorno'] = 0;
$booking_count['FebruarStorno'] = 0;
$booking_count['MärzStorno'] = 0;
$booking_count['AprilStorno'] = 0;
$booking_count['MaiStorno'] = 0;
$booking_count['JuniStorno'] = 0;
$booking_count['JuliStorno'] = 0;
$booking_count['AugustStorno'] = 0;
$booking_count['SeptemberStorno'] = 0;
$booking_count['OktoberStorno'] = 0;
$booking_count['NovemberStorno'] = 0;
$booking_count['DezemberStorno'] = 0;

$storno_ext = 0;
$storno_intern = 0;

foreach ($stornos as $storno){

    $pieces = explode("-", $storno->arrival);

    if($storno->operators_id == 1){
        $storno_intern++;
    }else{
        $storno_ext++;
    }

    switch ($pieces[1]) {
        case 01:
            $booking_count['JanuarStorno']++;
            break;
        case 02:
            $booking_count['FebruarStorno']++;
            break;
        case 03:
            $booking_count['MärzStorno']++;
            break;
        case 04:
            $booking_count['AprilStorno']++;
            break;
        case 05:
            $booking_count['MaiStorno']++;
            break;
        case 06:
            $booking_count['JuniStorno']++;
            break;
        case 07:
            $booking_count['JuliStorno']++;
            break;
        case "08":
            $booking_count['AugustStorno']++;
            break;
        case "09":
            $booking_count['SeptemberStorno']++;
            break;
        case 10:
            $booking_count['OktoberStorno']++;
            break;
        case 11:
            $booking_count['NovemberStorno']++;
            break;
        case 12:
            $booking_count['DezemberStorno']++;
            break;

    }

}


$booking_count['JanuarAll'] = $booking_count['JanuarStorno'] +  $booking_count['Januar'];
$booking_count['FebruarAll'] = $booking_count['FebruarStorno'] +  $booking_count['Februar'];
$booking_count['MärzAll'] = $booking_count['MärzStorno'] +  $booking_count['März'];
$booking_count['AprilAll'] = $booking_count['AprilStorno'] +  $booking_count['April'];
$booking_count['MaiAll'] = $booking_count['MaiStorno'] +  $booking_count['Mai'];
$booking_count['JuniAll'] = $booking_count['JuniStorno'] +  $booking_count['Juni'];
$booking_count['JuliAll'] = $booking_count['JuliStorno'] +  $booking_count['Juli'];
$booking_count['AugustAll'] = $booking_count['AugustStorno'] +  $booking_count['August'];
$booking_count['SeptemberAll'] = $booking_count['SeptemberStorno'] +  $booking_count['September'];
$booking_count['OktoberAll'] = $booking_count['OktoberStorno'] +  $booking_count['Oktober'];
$booking_count['NovemberAll'] = $booking_count['NovemberStorno'] +  $booking_count['November'];
$booking_count['DezemberAll'] = $booking_count['DezemberStorno'] +  $booking_count['Dezember'];


$booking_count['JanuarStornoP'] =  number_format((float)$booking_count['JanuarStorno'] * 100 / $booking_count['JanuarAll'], 2, ',', '.');
$booking_count['FebruarStornoP'] =  number_format((float)$booking_count['FebruarStorno'] * 100 / $booking_count['FebruarAll'], 2, ',', '.');
$booking_count['MärzStornoP'] =  number_format((float)$booking_count['MärzStorno'] * 100 / $booking_count['MärzAll'], 2, ',', '.');
$booking_count['AprilStornoP'] =  number_format((float)$booking_count['AprilStorno'] * 100 / $booking_count['AprilAll'], 2, ',', '.');
$booking_count['MaiStornoP'] =  number_format((float)$booking_count['MaiStorno'] * 100 / $booking_count['MaiAll'], 2, ',', '.');
$booking_count['JuniStornoP'] =  number_format((float)$booking_count['JuniStorno'] * 100 / $booking_count['JuniAll'], 2, ',', '.');
$booking_count['JuliStornoP'] =  number_format((float)$booking_count['JuliStorno'] * 100 / $booking_count['JuliAll'], 2, ',', '.');
$booking_count['AugustStornoP'] =  number_format((float)$booking_count['AugustStorno'] * 100 / $booking_count['AugustAll'], 2, ',', '.');
$booking_count['SeptemberStornoP'] =  number_format((float)$booking_count['SeptemberStorno'] * 100 / $booking_count['SeptemberAll'], 2, ',', '.');
$booking_count['OktoberStornoP'] =  number_format((float)$booking_count['OktoberStorno'] * 100 / $booking_count['OktoberAll'], 2, ',', '.');
$booking_count['NovemberStornoP'] =  number_format((float)$booking_count['NovemberStorno'] * 100 / $booking_count['NovemberAll'], 2, ',', '.');

if($booking_count['DezemberAll'] != 0){
    $booking_count['DezemberStornoP'] =  number_format((float)$booking_count['DezemberStorno'] * 100 / $booking_count['DezemberAll'], 2, ',', '.');
}





$booking_op['rev_direkt'] = number_format((float)$booking_op['rev_direkt'], 2, ',', '.');
$booking_op['rev_WBT'] = number_format((float)$booking_op['rev_WBT'], 2, ',', '.');
$booking_op['rev_Alpetours'] = number_format((float)$booking_op['rev_Alpetours'], 2, ',', '.');
$booking_op['rev_Jugendtours'] = number_format((float)$booking_op['rev_Jugendtours'], 2, ',', '.');
$booking_op['rev_Herole'] = number_format((float)$booking_op['rev_Herole'], 2, ',', '.');
$booking_op['rev_CTS'] = number_format((float)$booking_op['rev_CTS'], 2, ',', '.');
$booking_op['rev_EVR']= number_format((float)$booking_op['rev_EVR'], 2, ',', '.');


$ops = $gesamt - $booking_op['direkt'];
$direct = $booking_op['direkt'];

$percent_direct = 100 * $booking_op['direkt'] / $gesamt;
$percent_ops = 100 - $percent_direct;

$progress = 100 * $gesamt / 700;

$storno_percent = $stornos_gesamt * 100 / $gesamt;

$storno_percent = number_format((float)$storno_percent , 2, '.', '');

$bereinigt_ext =  $ops - $storno_ext;
$bereinigt_intern =  $direct - $storno_intern;



$bereinigt_ext_percent = $bereinigt_ext * 100 / $gesamt;
$bereinigt_intern_percent = $bereinigt_intern * 100 / $gesamt;
$bereinigt_ext_percent = number_format((float)$bereinigt_ext_percent , 2, '.', '');
$bereinigt_intern_percent = number_format((float)$bereinigt_intern_percent , 2, '.', '');


$data_bundesland = array();
foreach ($bundesland as $key => $value) {
    $data_bundesland[] = array('key' => $key, 'value' => $value);
}


$schuler = $gesamt*23;


$sql = "SELECT * FROM mdl_feedback_value WHERE value != '' ORDER BY id DESC";
$result = $DB->get_records_sql($sql);

$feedback = [];
$treffer = 0;
foreach ($result as $re){

    if(strlen($re->value) > 6 && $treffer < 11){
        $treffer++;
        $feedback[$treffer] = $re->value;
    }

}

foreach ($feedback as $key => $value) {
    $feedback_arry[] = array('key' => $key, 'value' => $value);
}


$umsatz = 0;

$booking_op['rev_WBT'] = number_format($booking_op['WBT'] * 240 / 1.19, 2, ',', '');
$booking_op['rev_EVR'] = number_format($booking_op['EVR'] * 240 / 1.19, 2, ',', '');
$booking_op['rev_KS'] = number_format($booking_op['KS'] * 240 / 1.19, 2, ',', '');
$booking_op['rev_WTB'] = number_format($booking_op['WTB'] * 240 / 1.19, 2, ',', '');
$booking_op['rev_CTS'] = number_format($booking_op['CTS'] * 240 / 1.19, 2, ',', '');
$booking_op['rev_Herole'] = number_format($booking_op['Herole'] * 240 / 1.19, 2, ',', '');
$booking_op['rev_Jugendtours'] = number_format($booking_op['Jugendtours'] * 240 / 1.19, 2, ',', '');
$booking_op['rev_Alpetours'] = number_format($booking_op['Alpetours'] * 240 / 1.19, 2, ',', '');
$booking_op['rev_direkt'] = number_format($booking_op['direkt'] * 240 / 1.19, 2, ',', '');

foreach ($booking_op as $key => $value) {
    // Exclude 'rev_' keys to avoid summing the calculated revenue values
    if (strpos($key, 'rev_') === 0) {

        $umsatz += $value;
    }
}

$booking_op['gewinn_WBT'] = $booking_op['rev_WBT'] - $booking_op['pro_WBT'];
$booking_op['gewinn_EVR'] = $booking_op['rev_EVR'] - $booking_op['pro_EVR'];
$booking_op['gewinn_KS'] = $booking_op['rev_KS'] - $booking_op['pro_KS'];
$booking_op['gewinn_WTB'] = $booking_op['rev_WTB'] - $booking_op['pro_WTB'];
$booking_op['gewinn_CTS'] = $booking_op['rev_CTS'] - $booking_op['pro_CTS'];
$booking_op['gewinn_Herole'] = $booking_op['rev_Herole'] - $booking_op['pro_Herole'];
$booking_op['gewinn_Jugendtours'] = $booking_op['rev_Jugendtours'] - $booking_op['pro_Jugendtours'];
$booking_op['gewinn_Alpetours'] = $booking_op['rev_Alpetours'] - $booking_op['pro_Alpetours'];
$booking_op['gewinn_direkt'] = $booking_op['rev_direkt'] - $booking_op['pro_direkt'];

$gewinn = 0;

foreach ($booking_op as $key => $value) {
    // Exclude 'rev_' keys to avoid summing the calculated revenue values
    if (strpos($key, 'gewinn_') === 0) {

        $gewinn += $value;
    }
}





$templatecontext = [

    'report' =>  'test',
    'counter' => $booking_count,
    'gesamt' => $gesamt,

    'umsatz' => number_format((float)$umsatz, 2, ',', '.'),
    'booking_op' => $booking_op,
    'ops' => $ops,
    'direct' => $direct,
    'percent_direct' => number_format((float)$percent_direct, 2, ',', '.'),
    'percent_ops' => number_format((float)$percent_ops, 2, ',', '.'),
    'progress' => number_format((float)$progress, 2, '.', ''),
    'last_month' => $last_monthcounter,
    'last_revenue' => number_format((float)$last_revenue, 2, ',', '.'),
    'age' => $age,
    'test' => $test,
    'schools' => $schools,
    'stornos' => $stornos_gesamt,
    'storno_percent' => $storno_percent,
    'storno_intern' =>$storno_intern,
    "storno_ext" => $storno_ext,
    "bereinigt_ext" => $bereinigt_ext,
    "bereinigt_intern" => $bereinigt_intern,
    "bereinigt_ext_percent" => $bereinigt_ext_percent,
    "bereinigt_intern_percent" => $bereinigt_intern_percent,
    "pro_gesamt" =>  number_format((float)$pro_gesamt, 2, ',', '.'),
    "gewinn" => number_format((float)$gewinn, 2, ',', '.'),
    "missing_code" => $missing_code,
    "missing_arr" => $missing_arr,
    "schulen" => $schulen,
    "bundesland" => $data_bundesland,
    "schuler" => $schuler,
    "feedback" => $feedback_arry



];



$PAGE->set_url('/mod/dashboard/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template("dashboard/index", $templatecontext);
echo $OUTPUT->footer();


function getBookings(){
    global $DB;
    $sql = "SELECT * FROM mdl_booking_data3  WHERE state = '0' && school != 'TESTBUCHUNG'";
    $bookings = $DB->get_records_sql($sql);
    return $bookings;
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


// Function to determine the Bundesland based on a given zip code
function getBundesland($zipCode, $mapping) {
    foreach ($mapping as $entry) {
        if ($zipCode >= $entry['min'] && $zipCode <= $entry['max']) {
            return $entry['bundesland'];
        }
    }
    return 'Unknown'; // If no match is found
}