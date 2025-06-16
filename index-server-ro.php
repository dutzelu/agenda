<?php
	
// Conectare la baza de date
$servername = "localhost";
$username = "roarchor_claudiu";
$password = "Parola*0920";
$dbname = "roarchor_wordpress";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conectare eșuată: " . $conn->connect_error);
}

// Setarea charset-ului conexiunii la UTF-8 $conn->set_charset("utf8");
$conn->set_charset("utf8");


// Array de zile ale săptămânii și luni în limba română
$zile_saptamana = ['Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă', 'Duminică'];
$luni_an = ['Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'];

// Determinarea săptămânii curente (de luni până duminică)
$week_start = isset($_GET['week_start']) ? $_GET['week_start'] : date('Y-m-d', strtotime('monday this week'));
// Extindem intervalul până la sfârșitul zilei de duminică
$week_end = date('Y-m-d 23:59:59', strtotime($week_start . ' +6 days'));

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
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>AGENDĂ Episcopului</title>
    <style>

    .agenda ul {
    
        margin:0;
        padding:10px 18px;
        border:1px solid #CCC;
        border-right:1px solid #CCC;
        border-bottom:none;
        list-style:none;
    }
        
    .d-flex span {
        font-weight: bold;
        margin: 0 10px;
    }
    .d-flex a {
        margin: 0 5px;
    }

    .clickable-row {
            cursor: pointer;
        }



    .navig_agenda a.btn-primary, a.btn-secondary.active {
       background:#c20000;
       border:none;
    }

    .zi_sapt { 
        color: #a94c50; 
        font-size: 18px; 
        background: rgba(208, 208, 208, 0.3); 
        padding: 4px 15px; 
        font-weight: bold; 
        border:1px solid #CCC;
        border-bottom:none;
        margin:0;
        text-transform:capitalize;
        }

    .continut-agenda {
        border-bottom:1px solid #CCC;
    }

    .agenda ul li {margin:5px 0;}

    .agenda .titlu_eveniment {
        margin-left:25px;
    }
    .afis-sapt-luna {
        font-weight:normal!important;
        font-size:22px;
        color:#000;
    }

    a.btn-secondary.active , a.btn-secondary.active   {
        background: #c20000!important;
    }

    .btn-secondary, .btn-secondary:hover {
        background-color: #fa6060;
        color: white;
    }

    a.btn-secondary:nth-child(2)  {
        margin-left: -6px!important;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        border: none;
    }

    a.btn-secondary:nth-child(1){
        border: none;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }



    a.btn-primary:nth-child(2){
        margin-left: -6px;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
        border-left: 1px solid #fa6060;
    }

    a.btn-primary:nth-child(1){
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .navig_agenda .col-md-6 {text-align:center!important;}
    .navig_agenda div.col-md-3:nth-child(3) {text-align:right;}

    .row {
     --bs-gutter-x: 0!important;

}

    /* Mobil */

    @media only screen and (max-width: 600px) {

        .navig_agenda div.col-md-3:nth-child(1), .navig_agenda .col-md-6, .navig_agenda div.col-md-3:nth-child(3) {text-align:center!important;}

        
    }
  
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var rows = document.querySelectorAll(".clickable-row");
        rows.forEach(function(row) {
            row.addEventListener("click", function() {
                window.location.href = row.dataset.href;
            });
        });
    });
</script>

</head>

<body>
<div class="agenda">
    <div class="row">
    <?php
            $view = isset($_GET['view']) ? $_GET['view'] : 'week';
            $current_date_param = $view == 'month' ? 'month_start' : 'week_start';
            $current_date = $view == 'month' ? $month_start : $week_start;
            $prev_date = $view == 'month' ? date('Y-m-01', strtotime($current_date . ' -1 month')) : date('Y-m-d', strtotime($current_date . ' -7 days'));
            $next_date = $view == 'month' ? date('Y-m-01', strtotime($current_date . ' +1 month')) : date('Y-m-d', strtotime($current_date . ' +7 days'));
            $today_date = $view == 'month' ? date('Y-m-01') : date('Y-m-d', strtotime('monday this week'));

            // Afișare titlu, luând în considerare cazurile în care săptămâna traversează două luni/ani
            if ($view == 'month') {
                $display_title = $luni_an[(int)date('m', strtotime($month_start)) - 1] . ' ' . date('Y', strtotime($month_start));
            } else {
                // Extragem informațiile necesare pentru titlul săptămânii
                $start_day = date('j', strtotime($week_start));
                $end_day_timestamp = strtotime($week_end);
                $end_day = date('j', $end_day_timestamp);

                $start_month_index = (int)date('m', strtotime($week_start)) - 1;
                $end_month_index = (int)date('m', $end_day_timestamp) - 1;

                $start_year = date('Y', strtotime($week_start));
                $end_year = date('Y', $end_day_timestamp);

                if ($start_month_index == $end_month_index && $start_year == $end_year) {
                    // Aceeași lună și același an
                    $display_title = "$start_day-$end_day {$luni_an[$start_month_index]} $end_year";
                } else {
                    // Luni sau ani diferiți
                    $display_title = "$start_day {$luni_an[$start_month_index]} $start_year - $end_day {$luni_an[$end_month_index]} $end_year";
                }
            }
            ?>

            <div class="row mb-4 navig_agenda">
                <div class="col-md-3">
                    <a href="?view=<?php echo $view; ?>&<?php echo $current_date_param; ?>=<?php echo $prev_date; ?>" class="btn btn-primary"><</a>
                    <a href="?view=<?php echo $view; ?>&<?php echo $current_date_param; ?>=<?php echo $next_date; ?>" class="btn btn-primary">></a>
                    <a href="?view=week&week_start=<?php echo date('Y-m-d', strtotime('monday this week')); ?>" class="btn btn-primary">Astăzi</a>
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
                    // Construim toate zilele săptămânii curente (Luni - Duminică)
                    $days_of_week = [];
                    for ($i = 0; $i < 7; $i++) {
                        $days_of_week[] = date('Y-m-d', strtotime($week_start . " +$i days"));
                    }

                    // Grupăm evenimentele pe zile
                    $days_with_events = [];
                    while ($row = $result_week->fetch_assoc()) {
                        $event_day = date('Y-m-d', strtotime($row["event_start"]));
                        if (!isset($days_with_events[$event_day])) {
                            $days_with_events[$event_day] = [];
                        }
                        $days_with_events[$event_day][] = $row;
                    }

                    // Parcurgem fiecare zi a săptămânii și afișăm doar zilele cu evenimente
                    foreach ($days_of_week as $date) {
                        if (isset($days_with_events[$date])) {
                            $index_zi = date('N', strtotime($date)) - 1;
                            $zi = $zile_saptamana[$index_zi];
                            $data_formata = date('j', strtotime($date)) . ' ' . $luni_an[(int)date('m', strtotime($date)) - 1];

                            echo "<p class='zi_sapt'>$data_formata | $zi</p>";
                            echo "<ul>";
                            foreach ($days_with_events[$date] as $event) {
                                echo '<li>' . date('H:i', strtotime($event["event_start"])) . ' - ' . date('H:i', strtotime($event["event_end"])) . ' - ';
                                echo htmlspecialchars($event["text_ro"]) . '</li>';
                            }
                            echo "</ul>";
                        }
                    }
                    ?>
                </div>
            <?php elseif ($view == 'month'): ?>
                <div class="continut-agenda">
                    <?php
                    $events_by_day = [];
                    while ($row = $result_month->fetch_assoc()) {
                        $day = date('Y-m-d', strtotime($row["event_start"]));
                        $events_by_day[$day][] = $row;
                    }

                    foreach ($events_by_day as $date => $events) {
                        $zi = $zile_saptamana[date('N', strtotime($date)) - 1];
                        $data_formata = date('j', strtotime($date)) . ' ' . $luni_an[(int)date('m', strtotime($date)) - 1];
                        echo "<p class='zi_sapt'>{$data_formata} | $zi</p>";
                        echo "<ul>";
                        foreach ($events as $event) {
                            echo '<li>' . date('H:i', strtotime($event["event_start"])) . ' - ' . date('H:i', strtotime($event["event_end"])) . ' - ' . htmlspecialchars($event["text_ro"]) . '</li>';
                        }
                        echo "</ul>";
                    }
                    ?>
                </div>
            <?php endif; ?>
 
 

<?php
$conn->close();
?>
