<?php
include "conectaredb.php";

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirecționare la login dacă utilizatorul nu este logat
    exit;
}

// Array pentru zilele și lunile în limba română
$zile_saptamana = ['Duminică', 'Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă'];
$luni_an = ['Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'];

// Setarea numărului de rezultate pe pagină
$results_per_page = 10;

// Determinarea paginii curente
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$start_from = ($page - 1) * $results_per_page;

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

// Preluare număr total de evenimente pentru paginare
if ($selected_year == 'all') {
    $stmt_total = $conn->prepare("SELECT COUNT(id) AS total FROM evenimente");
} else {
    $stmt_total = $conn->prepare("SELECT COUNT(id) AS total FROM evenimente WHERE YEAR(event_start) = ?");
    $stmt_total->bind_param("i", $selected_year);
}
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$row_total = $result_total->fetch_assoc();
$total_pages = $row_total ? ceil($row_total["total"] / $results_per_page) : 1;
$stmt_total->close();

// Preluare evenimente cu limitare și sortare după event_start
if ($selected_year == 'all') {
    $stmt = $conn->prepare("SELECT evenimente.*, episcopi.nume_scurt_episcop 
                            FROM evenimente 
                            LEFT JOIN episcopi ON evenimente.episcop_id = episcopi.id 
                            ORDER BY event_start DESC 
                            LIMIT ?, ?");
    $stmt->bind_param("ii", $start_from, $results_per_page);
} else {
    $stmt = $conn->prepare("SELECT evenimente.*, episcopi.nume_scurt_episcop 
                            FROM evenimente 
                            LEFT JOIN episcopi ON evenimente.episcop_id = episcopi.id 
                            WHERE YEAR(event_start) = ? 
                            ORDER BY event_start DESC 
                            LIMIT ?, ?");
    $stmt->bind_param("iii", $selected_year, $start_from, $results_per_page);
}
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

$conn->close();
include 'header.php';
?>

<body>
<div class="container">
    <div class="row">
        <!-- ------------  SIDEBAR  ------------ -->
        <aside class="col-md-3 mb-4">
            <?php include 'sidebar.php'; ?>
        </aside>

        <!-- ------------  CONŢINUT PRINCIPAL  ------------ -->
        <main class="col-md-9">
            <h2>Evenimente</h2>

            <!-- Filtru după an -->
            <form method="get" action="admin.php" class="mb-4">
                <div class="form-group">
                    <label for="year">Filtrează după an:</label>
                    <select name="year" id="year" class="form-control">
                        <option value="all">Toți anii</option>
                        <?php foreach ($years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo ($year == $selected_year) ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary mt-2">Filtrează</button>
            </form>

            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Start</th>
                        <th>Sfârșit</th>
                        <th>Română</th>
                        <th>Engleză</th>
                        <th>Calendar</th>
                        <th>Publicat</th>
                        <th>Acțiuni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $start_date = strtotime($row["event_start"]);
                            $end_date = strtotime($row["event_end"]);
                            $start_formatted = $zile_saptamana[date('w', $start_date)] . ', ' . date('d', $start_date) . ' ' . $luni_an[date('n', $start_date) - 1] . ' ' . date('Y', $start_date);
                            $end_formatted = $zile_saptamana[date('w', $end_date)] . ', ' . date('d', $end_date) . ' ' . $luni_an[date('n', $end_date) - 1] . ' ' . date('Y', $end_date);

                            echo '<tr onclick="window.location.href=\'edit-eveniment.php?id=' . $row["id"] . '\'" style="cursor:pointer;">';
                            echo '<td>' . $start_formatted . '</td>';
                            echo '<td>' . $end_formatted . '</td>';
                            echo '<td>' . htmlspecialchars($row["text_ro"]) . '</td>';
                            echo '<td>' . htmlspecialchars($row["text_en"]) . '</td>';
                            echo '<td>' . ($row["afiseaza_calendar"] ? 'Da' : 'Nu') . '</td>';
                            echo '<td>' . ($row["publicat"] ? 'Da' : 'Nu') . '</td>';
                            echo '<td>
                            <a href="del.php?id=' . $row["id"] . '" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm(\'Ești sigur că vrei să ștergi acest eveniment?\');">
                               Șterge
                            </a>
                          </td>';
                    
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="8">Nu s-au găsit evenimente.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>

            <!-- Paginare -->
            <nav aria-label="Navigation" class="mt-5 mb-5">
                <ul class="pagination mb-0">

                    <?php
                    /* Config */
                    $max_links   = 10;                          // câte numere vrei să apară
                    $half_window = floor($max_links / 2);

                    /* Calculează fereastra de pagini */
                    $start = max(1, $page - $half_window);
                    $end   = min($total_pages, $start + $max_links - 1);
                    // Dacă suntem la începutul listei şi nu avem 10 numere, deplasăm fereastra la dreapta
                    if (($end - $start + 1) < $max_links) {
                        $start = max(1, $end - $max_links + 1);
                    }

                    /* Helper pentru linkuri */
                    function pageUrl($p, $year) {
                        return "admin.php?page={$p}&year={$year}";
                    }
                    ?>

                    <!-- Prima / Înapoi -->
                    <li class="page-item <?php echo ($page == 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo pageUrl(1, $selected_year); ?>" aria-label="First">
                            &laquo;<!-- « -->
                        </a>
                    </li>
                    <li class="page-item <?php echo ($page == 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo pageUrl(max(1, $page - 1), $selected_year); ?>" aria-label="Previous">
                            &lsaquo;<!-- ‹ -->
                        </a>
                    </li>

                    <!-- Numere dinamic -->
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo pageUrl($i, $selected_year); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <!-- Înainte / Ultima -->
                    <li class="page-item <?php echo ($page == $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo pageUrl(min($total_pages, $page + 1), $selected_year); ?>" aria-label="Next">
                            &rsaquo;<!-- › -->
                        </a>
                    </li>
                    <li class="page-item <?php echo ($page == $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="<?php echo pageUrl($total_pages, $selected_year); ?>" aria-label="Last">
                            &raquo;<!-- » -->
                        </a>
                    </li>

                </ul>
</nav>

        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
