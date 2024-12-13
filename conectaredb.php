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

// Setarea charset-ului conexiunii la UTF-8 $conn->set_charset("utf8");
$conn->set_charset("utf8");

// Definirea URL-ului de bază
define('BASE_URL', 'https://localhost/agenda/');


?>