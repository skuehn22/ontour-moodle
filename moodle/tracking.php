<?php

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
echo "<p>Connected successfully </p>>";
echo $_GET['bid'];

$sql = "INSERT INTO film_tracking (id) VALUES ('".$_GET['bid']."')";
$result = $conn->query($sql);


?>