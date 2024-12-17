<?php
include 'conectaredb.php';

// Interogarea SQL
$sql = "SELECT id, luna, zi, sfinti, icoana FROM calendar_date_fixe ORDER BY luna, zi";
$result = $conn->query($sql);

// Verificare rezultate
if ($result->num_rows > 0) {
    // Afișare rezultate într-un tabel HTML
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr>
            <th>ID</th>
            <th>Luna</th>
            <th>Zi</th>
            <th>Sfinti</th>
            <th>Icoana</th>
          </tr>";

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['luna'] . "</td>";
        echo "<td>" . $row['zi'] . "</td>";
        echo "<td>" . $row['sfinti'] . "</td>";
        echo "<td><img src='" . $row['icoana'] . "' alt='Icoana' width='50' height='50'></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Nu există date disponibile.";
}

// Închidere conexiune
$conn->close();
?>
