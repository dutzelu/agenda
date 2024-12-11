<?php

// Conectare la baza de date
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "agenda";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conectare eșuată: " . $conn->connect_error);
}


?>