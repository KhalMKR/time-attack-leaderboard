<?php
$servername = "localhost";
$username = "Haziq";
$password = "Haz1q100%"; 
$dbname = "time_attack";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>