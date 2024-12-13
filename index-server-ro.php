<?php
	
// Conectare la baza de date
$servername = "o7jd45625770492.db.45625770.73f.hostedresource.net:3306";
$username = "o7jd45625770492";
$password = "H9VD8,hg";
$dbname = "o7jd45625770492";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conectare eșuată: " . $conn->connect_error);
}

// Setarea charset-ului conexiunii la UTF-8 $conn->set_charset("utf8");
$conn->set_charset("utf8");


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
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>AGENDĂ Episcopului</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>



</body>
</html>

 
 

<?php
// Închidem conexiunea la baza de date după ce am terminat toate interogările
$conn->close();
?>
