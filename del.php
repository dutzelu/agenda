<?php
include "conectaredb.php";

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) { // Folosim GET pentru a prelua ID-ul
    $event_id = intval($_GET['id']);

    // Ștergerea evenimentului din baza de date
    $stmt = $conn->prepare("DELETE FROM evenimente WHERE id = ?");
    $stmt->bind_param("i", $event_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Evenimentul a fost șters cu succes.";
    } else {
        $_SESSION['error'] = "A apărut o problemă la ștergerea evenimentului.";
    }

    $stmt->close();
} else {
    $_SESSION['error'] = "ID-ul evenimentului nu este valid.";
}

// Redirecționare înapoi la pagina principală
header("Location: admin.php");
exit;
?>
