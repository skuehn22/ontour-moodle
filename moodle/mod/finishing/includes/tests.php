<?php

require(__DIR__.'/../../../config.php');
require_once(__DIR__.'/../lib.php');

$servername = "localhost";
$username = "skuehn22";
$password = "a-:qnwYISnkV2cAB6rW.T8~o%yL^bI9#";
$db = "projektreisenWordpress_1637922561";

// Create connection
$conn = new mysqli($servername, $username, $password, $db);

// Check connection
if ($conn->connect_error) {
die("Connection failed: " . $conn->connect_error);
}
echo "<br>Connected successfully";

$sql = "SELECT * FROM wp_comments WHERE comment_ID = 1 ";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<br> id: " . $row["comment_author"];
    }
}


