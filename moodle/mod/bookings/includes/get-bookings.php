<?php

require(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');

global $DB;


$sql = "SELECT *
				FROM {user}
                WHERE imagealt = 1
				";

$users = $DB->get_records_sql($sql);

    echo '  <table class="table">
                                    <thead class="thead-dark">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">V-Code</th>
                                        <th scope="col">Veranstalter</th>
                                        <th scope="col">Anreise</th>
                                        <th scope="col">Abreise</th>
                                        <th scope="col">Bemerkung</th>
                                        <th scope="col">Datum Wiedervorlage</th>
                                    </tr>
                                    </thead>
                                    <tbody>';


    foreach ($users as $user){

        echo '<tr><td>'.$user->id.'</td> <td>'.$user->username.'</td><td></td><td></td><td></td><td></td><td></td></tr>';


    }

    echo'
                                      
                                   
                                    </tbody>
                                </table>
';