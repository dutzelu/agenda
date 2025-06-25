<?php

// Conectare la baza de date
$servername = "46.102.249.158";
$username = "roarchor_claudiu";
$password = "Parola*0920";
$dbname = "roarchor_wordpress";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conectare eșuată: " . $conn->connect_error);
}

// Setarea charset-ului conexiunii la UTF-8 $conn->set_charset("utf8");
$conn->set_charset("utf8");

// Definirea URL-ului de bază
define("BASE_URL", "http://localhost/agenda/");
define("ROOT_PATH", $_SERVER["DOCUMENT_ROOT"] . "/agenda");



?>