<?php
include "conectaredb.php";

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirecționare la login dacă utilizatorul nu este logat
    exit;
}

// Setarea numărului de rezultate pe pagină
$results_per_page = 10;

// Determinarea paginii curente
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start_from = ($page-1) * $results_per_page;

// Filtrare după an
$selected_year = isset($_GET['year']) ? $_GET['year'] : 'all';

// Preluare ani unici pentru filtrare
$stmt_years = $conn->prepare("SELECT DISTINCT YEAR(event_start) AS year FROM evenimente ORDER BY year DESC");
$stmt_years->execute();
$result_years = $stmt_years->get_result();
$years = [];
while ($row_year = $result_years->fetch_assoc()) {
    $years[] = $row_year['year'];
}
$stmt_years->close();

// Preluare evenimente cu limitare pentru paginiație și filtrare după an
if ($selected_year == 'all') {
    $stmt = $conn->prepare("SELECT evenimente.*, episcopi.nume_scurt_episcop FROM evenimente LEFT JOIN episcopi ON evenimente.episcop_id = episcopi.id LIMIT ?, ?");
    $stmt->bind_param("ii", $start_from, $results_per_page);
} else {
    $stmt = $conn->prepare("SELECT evenimente.*, episcopi.nume_scurt_episcop FROM evenimente LEFT JOIN episcopi ON evenimente.episcop_id = episcopi.id WHERE YEAR(event_start) = ? LIMIT ?, ?");
    $stmt->bind_param("iii", $selected_year, $start_from, $results_per_page);
}
$stmt->execute();
$result = $stmt->get_result();

// Preluare număr total de evenimente pentru paginiație
if ($selected_year == 'all') {
    $stmt_total = $conn->prepare("SELECT COUNT(id) AS total FROM evenimente");
} else {
    $stmt_total = $conn->prepare("SELECT COUNT(id) AS total FROM evenimente WHERE YEAR(event_start) = ?");
    $stmt_total->bind_param("i", $selected_year);
}
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
<div class="container mt-5 ">
    <div class="row">
        <!-- Bara laterală -->
        <div class="col-md-3 g-5">

            <?php include 'sidebar.php';?>

        </div>

        <!-- Conținut principal -->
        <div class="col-md-9">
            <h2>Evenimente</h2>

            <!-- Filtru după an -->
            <form method="get" action="admin.php" class="mb-4">
                <div class="form-group">
                    <label for="year">Filtrează după an:</label>
                    <select name="year" id="year" class="form-control">
                        <option value="all">Toți anii</option>
                        <?php foreach ($years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo ($year == $selected_year) ? 'selected' : ''; ?>><?php echo $year; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary mt-2">Filtrează</button>
            </form>

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
                        $formatter = new IntlDateFormatter('ro_RO', IntlDateFormatter::LONG, IntlDateFormatter::SHORT);
                        while($row = $result->fetch_assoc()) {
                            echo '<tr class="clickable-row" data-href="edit-eveniment.php?id=' . $row["id"] . '">';
                            echo '<td>' . $row["id"] . '</td>';
                            echo '<td>' . $row["nume_scurt_episcop"] . '</td>';
                            echo '<td>' . $formatter->format(new DateTime($row["event_start"])) . '</td>';
                            echo '<td>' . $formatter->format(new DateTime($row["event_end"])) . '</td>';
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
                        echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="admin.php?page=' . $i . '&year=' . $selected_year . '">' . $i . '</a></li>';
                    }
                    ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
