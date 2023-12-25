<?php
global $CFG;
require(__DIR__.'/../config.php');
require_once('../config.php');

// Check if the form is submitted
if (isset($_POST['submit'])) {
    // Get the user-entered dates
    $von = $_POST['von'];
    $bis = $_POST['bis'];
    $type =  $_POST['type'];
} else {
    // Default values for von and bis (today and today + 7 days)
    $von = isset($_POST['von']) ? $_POST['von'] : date('Y-m-d');
    $bis = isset($_POST['bis']) ? $_POST['bis'] : date('Y-m-d', strtotime('+7 days'));
}

actionOverview($von, $bis, $type);

function actionOverview($von, $bis, $type)
{
    global $DB;

    if($type == 'all'){

        $sql = 'SELECT * FROM mdl_booking_history3 WHERE action_date > :von AND action_date < :bis AND state = 2 AND (TYPE != "mail_er3" && TYPE != "mail_re3" && TYPE != "mail_fi" && TYPE != "mail_re2") ORDER BY TYPE ASC, action_date ASC';

        $parameters = [
            'von' => $von,
            'bis' => $bis
        ];

    }else{

        $sql = 'SELECT DISTINCT b_id, subject, user_id, action_date 
        FROM mdl_booking_history3 
        WHERE action_date > :von AND action_date < :bis AND state = 2 AND TYPE = :type 
        ORDER BY TYPE ASC, action_date ASC';

        $parameters = [
            'von' => $von,
            'bis' => $bis,
            'type' => $type
        ];

    }

    $bookings = $DB->get_records_sql($sql, $parameters);



    echo "<style>
            form {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
            }

            form label {
                font-weight: bold;
                margin-right: 10px;
            }

            form input[type='date'] {
                padding: 5px;
            }

            form input[type='submit'] {
                padding: 5px 15px;
                background-color: #007bff;
                color: #fff;
                border: none;
                cursor: pointer;
            }

            table {
                border-collapse: collapse;
                width: 100%;
            }

            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }

            th {
                background-color: #f2f2f2;
            }
            
             /* Custom select style */
            .custom-select {
                display: inline-block;
                position: relative;
                font-family: Arial, sans-serif;
                font-size: 14px;
                color: #333;
                border: 1px solid #ccc;
                border-radius: 4px;
                padding: 6px 24px 6px 10px;
                background-color: #fff;
                cursor: pointer;
                appearance: none;
                -webkit-appearance: none;
                -moz-appearance: none;
                margin-left: 5px;
            }
        
            .custom-select::after {
                content: '';
                position: absolute;
                top: 50%;
                right: 10px;
                margin-top: -3px;
                border-width: 6px 6px 0 6px;
                border-style: solid;
                border-color: #333 transparent;
                pointer-events: none;
            }
        </style>";
    echo "<h1>Geplanter Mailsversand</h1><br>";
    echo "<form method='post'>";

    echo "<label for='von'>Von:</label> <input type='date' name='von' value='$von' > ";
    echo "<label for='bis' style='padding-left: 7px;'>Bis:</label> <input type='date' name='bis' value='$bis'> ";
    echo "<input type='submit' name='submit' value='Submit' style='margin-left: 7px;'>";
    echo "<select name='type' id='type' class='custom-select'><option value='all'>alle</option><option value='mail_er1'>Erinnerung 1</option><option value='mail_er2'>Erinnerung 2</option></select>";
    echo "</form>";

    echo "<table>";
    echo "<tr>";
    echo "<th style='width: 200px;'>Buchungsnummer</th>";
    echo "<th style='width: 200px;'>Betreff</th>";
    echo "<th style='width: 200px;'>Actionsdatum</th>";
    echo "<th>Schulname</th>";
    echo "<th>Empfänger</th>";
    echo "<th style='text-align: right;'>Status der Buchung</th>";
    echo "</tr>";

    foreach ($bookings as $booking) {
        $register = null;
        $sql = "SELECT * FROM {booking_data3} WHERE state = 0 && order_id = $booking->b_id";
        $check = $DB->get_record_sql($sql);


        try {
            if (isset($booking->user_id) && !is_array($booking->user_id)) {
                    $sql = "SELECT * FROM {user} WHERE id = $booking->user_id";
                    $user = $DB->get_record_sql($sql);

            }
        } catch (Exception $e) {
            // Handle the exception here if needed, or you can simply log it for debugging purposes.
            // For example, you can use error_log($e->getMessage()) to log the error message.
        }

        if($booking->type == "mail_mo" && strpos($user->email, "g6_") !== false){
            $sql = "SELECT * FROM {user} WHERE id = $booking->user_id-6";
            $user = $DB->get_record_sql($sql);
        }


        if ($check) {
            if ($booking->subject == "Erinnerung 2" || $booking->subject == "Erinnerung 1") {
                $sql = "SELECT * FROM mdl_booking_history3 WHERE TYPE = 'register' && state = 1 && b_id = $booking->b_id";
                $register = $DB->get_record_sql($sql);

                if (!$register) {
                    echo "<tr>";
                    echo "<td>".$booking->b_id."</td>";
                    echo "<td>".$booking->subject."</td>";
                    // Format the action_date field
                    $formattedDate = date('d.m.Y', strtotime($booking->action_date));
                    echo "<td>".$formattedDate."</td>";
                    echo "<td>".$check->school."</td>";
                    echo "<td>".$user->email."</td>";
                    echo "<td style='text-align: right;'>aktiv</td>";
                    echo "</tr>";
                } else {
                    //echo $register->action_date;
                    // echo "<br>";
                }
            } else {
                echo "<tr>";
                echo "<td>".$booking->b_id."</td>";
                echo "<td>".$booking->subject."</td>";
                // Format the action_date field
                $formattedDate = date('d.m.Y', strtotime($booking->action_date));
                echo "<td>".$formattedDate."</td>";
                echo "<td>".$check->school."</td>";
                echo "<td>".$user->email."</td>";
                echo "<td style='text-align: right;'>aktiv</td>";
                echo "</tr>";
            }
        }
    }
    echo "</table>";
}
