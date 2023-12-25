<?php

echo "test";

$to_email = 'kuehn.sebastian@gmail.com';
$subject = 'Buchungsbestätigung';
$message = 'This mail is sent using the PHP mail function';
$headers = 'From: info@ontour.org';
mail($to_email,$subject,$message,$headers);
?>