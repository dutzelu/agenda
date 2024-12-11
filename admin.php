<?php
include "conectaredb.php";

// Setarea numărului de rezultate pe pagină
$results_per_page = 10;

// Determinarea paginii curente
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start_from = ($page-1) * $results_per_page;

// Preluare evenimente cu limitare pentru paginiație
$stmt = $conn->prepare("SELECT evenimente.*, episcopi.nume_scurt_episcop FROM evenimente LEFT JOIN episcopi ON evenimente.episcop_id = episcopi.id LIMIT ?, ?");
$stmt->bind_param("ii", $start_from, $results_per_page);
$stmt->execute();
$result = $stmt->get_result();

// Preluare număr total de evenimente
$stmt_total = $conn->prepare("SELECT COUNT(id) AS total FROM evenimente");
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$row_total = $result_total->fetch_assoc();
$total_pages = ceil($row_total["total"] / $results_per_page);

$stmt->close();
$stmt_total->close();
$conn->close();
include 'header.php';
?>
 
<body>
<div class="container mt-5">
    <h2>Lista Evenimente</h2>
    <table class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Episcop</th>
                <th>Start Eveniment</th>
                <th>Sfârșit Eveniment</th>
                <th>Text în Română</th>
                <th>Text în Engleză</th>
                <th>Afișează Calendar</th>
                <th>Publicat</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo '<tr class="clickable-row" data-href="edit-eveniment.php?id=' . $row["id"] . '">';
                    echo '<td>' . $row["id"] . '</td>';
                    echo '<td>' . $row["nume_scurt_episcop"] . '</td>';
                    echo '<td>' . strftime('%A, %d %b. %Y ora %H:%M', strtotime($row["event_start"])) . '</td>';
                    echo '<td>' . strftime('%A, %d %b. %Y ora %H:%M', strtotime($row["event_end"])) . '</td>';
                    echo '<td>' . $row["text_ro"] . '</td>';
                    echo '<td>' . $row["text_en"] . '</td>';
                    echo '<td>' . ($row["afiseaza_calendar"] ? 'Da' : 'Nu') . '</td>';
                    echo '<td>' . ($row["publicat"] ? 'Da' : 'Nu') . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="8">Nu s-au găsit evenimente.</td></tr>';
            }
            ?>
        </tbody>
    </table>

    <nav>
        <ul class="pagination">
            <?php
            for ($i = 1; $i <= $total_pages; $i++) {
                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="admin.php?page=' . $i . '">' . $i . '</a></li>';
            }
            ?>
        </ul>
    </nav>
</div>
<?php include 'footer.php';?>
