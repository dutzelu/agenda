<?php
include "conectaredb.php";
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirecționare la login dacă utilizatorul nu este logat
    exit;
}

// Array de zile ale săptămânii și luni în limba română
$zile_saptamana = ['Duminică', 'Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă'];
$luni_an = ['Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'];

// Determinarea săptămânii curente (de duminică până sâmbătă)
$week_start = isset($_GET['week_start']) ? $_GET['week_start'] : date('Y-m-d', strtotime('last Sunday'));
$week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));

// Determinarea lunii curente
$month_start = isset($_GET['month_start']) ? $_GET['month_start'] : date('Y-m-01');
$month_end = date('Y-m-t', strtotime($month_start));

// Preluare evenimente pentru săptămâna curentă
$stmt_week = $conn->prepare("SELECT * FROM evenimente WHERE event_start BETWEEN ? AND ? ORDER BY event_start");
$stmt_week->bind_param("ss", $week_start, $week_end);
$stmt_week->execute();
$result_week = $stmt_week->get_result();

// Preluare evenimente pentru luna curentă
$stmt_month = $conn->prepare("SELECT * FROM evenimente WHERE event_start BETWEEN ? AND ? ORDER BY event_start");
$stmt_month->bind_param("ss", $month_start, $month_end);
$stmt_month->execute();
$result_month = $stmt_month->get_result();

include "header.php";
?>

<body>
<div class="container mt-5 agenda">
    <div class="row">
        <!-- Bara laterală -->
        <div class="col-md-3 g-5">
            <?php include 'sidebar.php';?>
        </div>

        <!-- Conținut principal -->
        <div class="col-md-9">
    <?php
    $view = isset($_GET['view']) ? $_GET['view'] : 'week';
    $current_date_param = $view == 'month' ? 'month_start' : 'week_start';
    $current_date = $view == 'month' ? $month_start : $week_start;
    $prev_date = $view == 'month' ? date('Y-m-01', strtotime($current_date . ' -1 month')) : date('Y-m-d', strtotime($current_date . ' -7 days'));
    $next_date = $view == 'month' ? date('Y-m-01', strtotime($current_date . ' +1 month')) : date('Y-m-d', strtotime($current_date . ' +7 days'));
    $today_date = $view == 'month' ? date('Y-m-01') : date('Y-m-d', strtotime('last Sunday'));

    // Setarea vizualizării și a datei pentru butonul "Astăzi"
    if ($view == 'month') {
        $today_view = 'week';
        $today_date_param = 'week_start';
        $today_date_value = date('Y-m-d', strtotime('last Sunday'));
    } else {
        $today_view = $view;
        $today_date_param = $current_date_param;
        $today_date_value = $today_date;
    }

    // Afișare titlu în funcție de vizualizare
    if ($view == 'month') {
        $luna = $luni_an[(int)date('m', strtotime($month_start)) - 1];
        $an = date('Y', strtotime($month_start));
        $display_title = "$luna $an";
    } else {
       // Obținem luna și anul pentru start și end
        $luna_start = $luni_an[(int)date('m', strtotime($week_start)) - 1];
        $luna_end = $luni_an[(int)date('m', strtotime($week_end)) - 1];
        $an = date('Y', strtotime($week_end));

        if (date('m', strtotime($week_start)) == date('m', strtotime($week_end))) {
            // Dacă este aceeași lună, afișăm doar numele lunii o dată
            $week_start_formatted = ltrim(date('d', strtotime($week_start)), '0'); // Fără 0 în față
            $week_end_formatted = ltrim(date('d', strtotime($week_end)), '0');     // Fără 0 în față
            $display_title = "$week_start_formatted-$week_end_formatted $luna_start $an";
        } else {
            // Dacă sunt luni diferite, afișăm ambele luni
            $week_start_formatted = ltrim(date('d', strtotime($week_start)), '0') . ' ' . $luna_start;
            $week_end_formatted = ltrim(date('d', strtotime($week_end)), '0') . ' ' . $luna_end;
            $display_title = "$week_start_formatted - $week_end_formatted $an";
        }

    }
    ?>

    <div class="row mb-4 navig_agenda">
        <div class="col-md-3">
            <a href="?view=<?php echo $view; ?>&<?php echo $current_date_param; ?>=<?php echo $prev_date; ?>" class="btn btn-primary"><</a>
            <a href="?view=<?php echo $view; ?>&<?php echo $current_date_param; ?>=<?php echo $next_date; ?>" class="btn btn-primary">></a>
            <a href="?view=<?php echo $today_view; ?>&<?php echo $today_date_param; ?>=<?php echo $today_date_value; ?>" class="btn btn-primary">Astăzi</a>
        </div>
        <div class="col-md-6">
            <span class="afis-sapt-luna"><?php echo $display_title; ?></span>
        </div>
        <div class="col-md-3">
            <a href="?view=week&week_start=<?php echo $week_start; ?>" class="btn <?php echo $view == 'week' ? 'btn-secondary active' : 'btn-secondary'; ?>">Săptămână</a>
            <a href="?view=month&month_start=<?php echo $month_start; ?>" class="btn <?php echo $view == 'month' ? 'btn-secondary active' : 'btn-secondary'; ?>">Lună</a>
        </div>
    </div>

    <?php if ($view == 'week'): ?>
        <div class="continut-agenda">
            <?php
            // Array de date din săptămâna curentă cu evenimente
            $days_with_events = [];
            while ($row = $result_week->fetch_assoc()) {
                $day = date('Y-m-d', strtotime($row["event_start"]));
                if (!isset($days_with_events[$day])) {
                    $days_with_events[$day] = [];
                }
                $days_with_events[$day][] = $row;
            }

            foreach ($days_with_events as $date => $events) {
                $zi = $zile_saptamana[date('w', strtotime($date))];
                $data_formata = date('d', strtotime($date)) . ' ' . $luni_an[(int)date('m', strtotime($date)) - 1] . ' ' . date('Y', strtotime($date));
                echo "<p class='zi_sapt'>$zi, $data_formata</p>";
                echo "<ul>";
                foreach ($events as $event) {
                    echo '<li>' . date('H:i', strtotime($event["event_start"])) . ' - ' . date('H:i', strtotime($event["event_end"])) . ' ' . '<span class="titlu_eveniment">' . $event["text_ro"] . '</span></li>';
                }
                echo "</ul>";
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if ($view == 'month'): ?>
        <div class="continut-agenda">
            <?php
            // Grupare evenimente pe zile
            $events_by_day = [];
            while ($row = $result_month->fetch_assoc()) {
                $day = date('Y-m-d', strtotime($row["event_start"]));
                if (!isset($events_by_day[$day])) {
                    $events_by_day[$day] = [];
                }
                $events_by_day[$day][] = $row;
            }

            foreach ($events_by_day as $day => $events) {
                $zi = $zile_saptamana[date('w', strtotime($day))];
                $data_formata = date('d', strtotime($day)) . ' ' . $luni_an[(int)date('m', strtotime($day)) - 1];
                echo "<p class='zi_sapt'>$data_formata | $zi</p>";
                echo '<ul>';
                foreach ($events as $event) {
                    echo '<li>' . date('H:i', strtotime($event["event_start"])) . ' - ' . date('H:i', strtotime($event["event_end"])) . ' - ' . $event["text_ro"] . '</li>';
                }
                echo '</ul>';
            }
            
            if (empty($events_by_day)) {
                echo '<p>Nu s-au găsit evenimente pentru această lună.</p>';
            }
            ?>
        </div>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>

<?php
// Închidem conexiunea la baza de date după ce am terminat toate interogările
$conn->close();
?>
