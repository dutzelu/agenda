<?php
include "conectaredb.php";

// Setarea localizării în limba română
setlocale(LC_TIME, 'ro_RO.UTF-8');

// Determinarea săptămânii curente (de duminică până sâmbătă)
$week_start = isset($_GET['week_start']) ? $_GET['week_start'] : date('Y-m-d', strtotime('last Sunday'));
$week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));

// Determinarea lunii curente
$month_start = isset($_GET['month_start']) ? $_GET['month_start'] : date('Y-m-01');
$month_end = date('Y-m-t', strtotime($month_start));

// Preluare evenimente pentru săptămâna curentă
$stmt_week = $conn->prepare("SELECT * FROM evenimente WHERE event_start BETWEEN ? AND ?");
$stmt_week->bind_param("ss", $week_start, $week_end);
$stmt_week->execute();
$result_week = $stmt_week->get_result();

// Preluare evenimente pentru luna curentă
$stmt_month = $conn->prepare("SELECT * FROM evenimente WHERE event_start BETWEEN ? AND ?");
$stmt_month->bind_param("ss", $month_start, $month_end);
$stmt_month->execute();
$result_month = $stmt_month->get_result();

include "header.php";
?>

<body>
<div class="container mt-5">
    <?php
    $view = isset($_GET['view']) ? $_GET['view'] : 'week';
    $current_date_param = $view == 'month' ? 'month_start' : 'week_start';
    $current_date = $view == 'month' ? $month_start : $week_start;
    $prev_date = $view == 'month' ? date('Y-m-01', strtotime($current_date . ' -1 month')) : date('Y-m-d', strtotime($current_date . ' -7 days'));
    $next_date = $view == 'month' ? date('Y-m-01', strtotime($current_date . ' +1 month')) : date('Y-m-d', strtotime($current_date . ' +7 days'));
    $today_date = $view == 'month' ? date('Y-m-01') : date('Y-m-d', strtotime('last Sunday'));
    $week_display = strftime('%d %B', strtotime($week_start)) . ' - ' . strftime('%d %B %Y', strtotime($week_end));
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="?view=<?php echo $view; ?>&<?php echo $current_date_param; ?>=<?php echo $prev_date; ?>" class="btn btn-primary"><</a>
        <a href="?view=<?php echo $view; ?>&<?php echo $current_date_param; ?>=<?php echo $next_date; ?>" class="btn btn-primary">></a>
        <span><?php echo $week_display; ?></span>
        <a href="?view=<?php echo $view; ?>&<?php echo $current_date_param; ?>=<?php echo $today_date; ?>" class="btn btn-primary">Astăzi</a>
        <a href="?view=week&week_start=<?php echo $week_start; ?>" class="btn <?php echo $view == 'week' ? 'btn-secondary active' : 'btn-secondary'; ?>">Săptămână</a>
        <a href="?view=month&month_start=<?php echo $month_start; ?>" class="btn <?php echo $view == 'month' ? 'btn-secondary active' : 'btn-secondary'; ?>">Lună</a>
    </div>

    <?php if ($view == 'week'): ?>
       
        <div>
            <?php
            // Array de date din săptămâna curentă
            $days_of_week = [];
            $current_date = strtotime($week_start);
            while ($current_date <= strtotime($week_end)) {
                $days_of_week[] = date('Y-m-d', $current_date);
                $current_date = strtotime("+1 day", $current_date);
            }

            $days_names = ['Duminică', 'Luni', 'Marți', 'Miercuri', 'Joi', 'Vineri', 'Sâmbătă'];

            foreach ($days_of_week as $index => $date) {
                echo "<h3>" . strftime('%A, %d %b. %Y', strtotime($date)) . "</h3>";

                $day_start = $date . " 00:00:00";
                $day_end = $date . " 23:59:59";
                $stmt_day = $conn->prepare("SELECT * FROM evenimente WHERE event_start BETWEEN ? AND ?");
                $stmt_day->bind_param("ss", $day_start, $day_end);
                $stmt_day->execute();
                $result_day = $stmt_day->get_result();

                if ($result_day->num_rows > 0) {
                    while($row = $result_day->fetch_assoc()) {
                        echo '<p>' . date('H:i', strtotime($row["event_start"])) . ' - ' . date('H:i', strtotime($row["event_end"])) . ' ' . $row["text_ro"] . '</p>';
                    }
                } else {
                    echo '<p>Nicio activitate.</p>';
                }
                $stmt_day->close();
            }
            ?>
        </div>
    <?php endif; ?>

    <?php if ($view == 'month'): ?>
        <h2>Evenimente din luna <?php echo strftime('%B %Y', strtotime($month_start)); ?></h2>
        <div>
            <?php
            // Grupare evenimente pe zile
            $events_by_day = [];
            if ($result_month->num_rows > 0) {
                while ($row = $result_month->fetch_assoc()) {
                    $day = date('Y-m-d', strtotime($row["event_start"]));
                    if (!isset($events_by_day[$day])) {
                        $events_by_day[$day] = [];
                    }
                    $events_by_day[$day][] = $row;
                }
            }

            foreach ($events_by_day as $day => $events) {
                echo "<h3>" . strftime('%d %B %Y | %A', strtotime($day)) . "</h3>";
                foreach ($events as $event) {
                    echo '<p>' . date('H:i', strtotime($event["event_start"])) . ' - ' . date('H:i', strtotime($event["event_end"])) . ' - ' . $event["text_ro"] . '</p>';
                }
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
