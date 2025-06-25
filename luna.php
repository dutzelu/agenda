<?php
// Conectare la baza de date
include "conectaredb.php";

// Array cu zilele săptămânii în limba română
$zile_saptamana = ['Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă', 'Duminică'];
$luni = [
    '01' => 'Ianuarie', '02' => 'Februarie', '03' => 'Martie', '04' => 'Aprilie',
    '05' => 'Mai', '06' => 'Iunie', '07' => 'Iulie', '08' => 'August',
    '09' => 'Septembrie', '10' => 'Octombrie', '11' => 'Noiembrie', '12' => 'Decembrie'
];

// Determinăm luna și anul curent sau selectate prin GET
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Ajustăm luna și anul pentru butoanele de navigare
$prev_date = strtotime("$selected_year-$selected_month-01 -1 month");
$next_date = strtotime("$selected_year-$selected_month-01 +1 month");
$prev_month = date('m', $prev_date);
$prev_year = date('Y', $prev_date);
$next_month = date('m', $next_date);
$next_year = date('Y', $next_date);

$month_start = "$selected_year-$selected_month-01";
$month_end = date('Y-m-t', strtotime($month_start));
$first_day_of_month = date('N', strtotime($month_start));
$total_days_in_month = date('t', strtotime($month_start));

// Preluare evenimente pentru luna curentă
$stmt = $conn->prepare("SELECT * FROM evenimente WHERE event_start BETWEEN ? AND ? ORDER BY event_start");
$stmt->bind_param("ss", $month_start, $month_end);
$stmt->execute();
$result = $stmt->get_result();

// Organizare evenimente pe zile
$evenimente_luna = [];
while ($row = $result->fetch_assoc()) {
    $ziua = date('j', strtotime($row['event_start']));
    $evenimente_luna[$ziua][] = $row['text_ro'];
}
include "header.php";
?>

<body>
<div class="container">
    <div class="row">
        <!-- ------------  SIDEBAR  ------------ -->
        <aside class="col-md-3 mb-4">
            <?php include 'sidebar.php'; ?>
        </aside>

        <!-- ------------  CONŢINUT PRINCIPAL  ------------ -->
        <main class="col-md-9 navig_agenda">
            
            <!-- Formular pentru selectarea lunii și anului -->
            <form method="GET" class="form-inline justify-content-center mb-3 d-flex align-items-center">
                <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-primary"><</a>
                <div class="form-group mx-2">
                    <select name="month" class="form-control">
                        <?php foreach ($luni as $key => $luna): ?>
                            <option value="<?php echo $key; ?>" <?php echo $selected_month == $key ? 'selected' : ''; ?>>
                                <?php echo $luna; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mx-2">
                    <select name="year" class="form-control">
                        <?php for ($i = 2020; $i <= date('Y') + 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $selected_year == $i ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary mx-2">Schimbă</button>
                <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-primary">></a>
            </form>

            <!-- Calendar -->
            <div class="card">
                <div class="card-header text-center">
                    <?php echo $luni[$selected_month] . " " . $selected_year; ?>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered text-center mb-0">
                        <thead class="thead-dark">
                            <tr>
                                <?php foreach ($zile_saptamana as $zi): ?>
                                    <th><?php echo $zi; ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $day = 1;
                            $current_day = date('j');
                            $current_month = date('m');
                            $current_year = date('Y');

                            for ($row = 0; $row < 6; $row++): ?>
                                <tr>
                                    <?php for ($col = 1; $col <= 7; $col++): ?>
                                        <td class="<?php echo ($selected_year == $current_year && $selected_month == $current_month && $day == $current_day) ? 'bg-primary text-white' : ''; ?>">
                                            <?php
                                            if (($row === 0 && $col < $first_day_of_month) || $day > $total_days_in_month) {
                                                echo "";
                                            } else {
                                                echo "<div class='day-number'>$day</div>";
                                                if (isset($evenimente_luna[$day])) {
                                                    $event_index = 0;
                                                    foreach ($evenimente_luna[$day] as $eveniment) {
                                                        $color_class = $event_index % 2 === 0 ? 'event-even' : 'event-odd';
                                                        echo "<div class='event $color_class'>$eveniment</div>";
                                                        $event_index++;
                                                    }
                                                }
                                                $day++;
                                            }
                                            ?>
                                        </td>
                                    <?php endfor; ?>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>