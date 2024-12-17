<?php
include "conectaredb.php";
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); 
    exit;
}

$success = NULL;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Preluăm datele generale
    $episcop_id = $_POST['episcop_id'];
    $afiseaza_calendar = isset($_POST['afiseaza_calendar']) ? 1 : 0;
    $publicat = isset($_POST['publicat']) ? 1 : 0;
    $selected_dates = isset($_POST['selected_dates']) && is_array($_POST['selected_dates']) ? $_POST['selected_dates'] : [];

    if (isset($_POST['events']) && is_array($_POST['events'])) {
        $inserts_ok = true;
        
        foreach ($_POST['events'] as $event_data) {
            // Extragem câmpurile pentru evenimentul curent
            $original_event_start = $event_data['event_start'];
            $original_event_end = $event_data['event_end'];
            $text_ro = $event_data['text_ro'];
            $text_en = $event_data['text_en'];

            // Conversie la format MySQL
            $event_start_dt = strtotime($original_event_start);
            $event_end_dt = strtotime($original_event_end);

            // Inserăm întâi evenimentul cu data originală
            $event_start = date('Y-m-d H:i:s', $event_start_dt);
            $event_end = date('Y-m-d H:i:s', $event_end_dt);

            $sql = "INSERT INTO evenimente (episcop_id, event_start, event_end, text_ro, text_en, afiseaza_calendar, publicat) 
                    VALUES ('$episcop_id', '$event_start', '$event_end', '$text_ro', '$text_en', '$afiseaza_calendar', '$publicat')";

            if ($conn->query($sql) !== TRUE) {
                $inserts_ok = false;
                break;
            }

            // Dacă au fost selectate zile suplimentare și există un singur eveniment
            // (Presupunem că dacă utilizatorul a adăugat mai multe evenimente, ascundem această opțiune
            //  și practic nu există selected_dates de aplicat la multiple evenimente)
            if (!empty($selected_dates) && count($_POST['events']) == 1) {
                $start_time = date('H:i:s', $event_start_dt);
                $end_time = date('H:i:s', $event_end_dt);
                
                foreach ($selected_dates as $selected_day) {
                    // Construim evenimentul pentru acea zi suplimentară
                    $new_event_start = $selected_day . ' ' . $start_time;
                    $new_event_end = $selected_day . ' ' . $end_time;

                    $new_event_start = date('Y-m-d H:i:s', strtotime($new_event_start));
                    $new_event_end = date('Y-m-d H:i:s', strtotime($new_event_end));

                    $sql = "INSERT INTO evenimente (episcop_id, event_start, event_end, text_ro, text_en, afiseaza_calendar, publicat) 
                            VALUES ('$episcop_id', '$new_event_start', '$new_event_end', '$text_ro', '$text_en', '$afiseaza_calendar', '$publicat')";

                    if ($conn->query($sql) !== TRUE) {
                        $inserts_ok = false;
                        break 2; // Iesim din ambele loop-uri
                    }
                }
            }
        }

        if ($inserts_ok) {
            $success = true;
        }
    }
}

// Preluare date episcopi pentru dropdown
$episcopi_sql = "SELECT id, nume_complet_episcop FROM episcopi";
$episcopi_result = $conn->query($episcopi_sql);

$conn->close();
include "header.php";
?>

<body>
<div class="container mt-5 mb-5">
    <div class="row">
        <!-- Bara laterală -->
        <div class="col-md-3 g-5">
            <?php include 'sidebar.php';?>
        </div>
    
        <!-- Conținut principal -->
        <div class="col-md-9">
            <h2>Adaugă eveniment</h2>
            <?php if ($success): ?>
                <div class="alert alert-primary mt-4" id="dispari" role="alert">
                    Evenimentele au fost adăugate cu succes
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="mb-3">
                    <label for="episcop_id" class="form-label">Episcop</label>
                    <select class="form-control" id="episcop_id" name="episcop_id" required>
                        <option value="" disabled <?php echo !isset($_POST['episcop_id']) ? 'selected' : ''; ?>>Selectează un episcop</option>
                        <?php
                        $selected_episcop_id = isset($_POST['episcop_id']) ? $_POST['episcop_id'] : 1;

                        if ($episcopi_result->num_rows > 0) {
                            while($row = $episcopi_result->fetch_assoc()) {
                                $selected = ($row["id"] == $selected_episcop_id) ? ' selected' : '';
                                echo '<option value="' . $row["id"] . '"' . $selected . '>' . $row["nume_complet_episcop"] . '</option>';
                            }
                        } else {
                            echo '<option value="" disabled>Nicio intrare găsită</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="afiseaza_calendar" name="afiseaza_calendar">
                    <label class="form-check-label" for="afiseaza_calendar">
                        Afișează Calendar
                    </label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="publicat" name="publicat" checked>
                    <label class="form-check-label" for="publicat">
                        Publicat
                    </label>
                </div>
                
                <!-- Container pentru evenimente -->
                <div id="events-container">
                    <div class="event-group mb-4 border p-3">
                        <h5>Eveniment 1</h5>
                        <div class="mb-3">
                            <label class="form-label">Start Eveniment</label>
                            <input type="datetime-local" class="form-control" name="events[0][event_start]" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sfârșit Eveniment</label>
                            <input type="datetime-local" class="form-control" name="events[0][event_end]" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Text în Română</label>
                            <textarea class="form-control" name="events[0][text_ro]" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Text în Engleză</label>
                            <textarea class="form-control" name="events[0][text_en]" rows="3" required></textarea>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-success mb-3" id="add-event-btn">Adaugă alt eveniment</button>
                
                <hr>

                <!-- Zile suplimentare - se afișează doar dacă este un singur eveniment -->
                <div id="extra-days-container">
                    <?php
                    $days = ['Luni','Marți','Miercuri','Joi','Vineri','Sâmbătă','Duminică'];
                    $months = ['ianuarie','februarie','martie','aprilie','mai','iunie','iulie','august','septembrie','octombrie','noiembrie','decembrie'];

                    $today = date('Y-m-d');
                    ?>
                    
                    <label class="form-label fw-bold text-primary">Selectează zile suplimentare pentru eveniment:</label><br>
                    <?php
                    echo "<ul class='zile-suplimentare'>";
                    // Începem de la ziua de mâine
                    for ($i = 1; $i <= 14; $i++) {
                        $day = date('Y-m-d', strtotime("$today +$i day"));
                        $timestamp = strtotime($day);

                        $weekdayIndex = date('N', $timestamp) - 1; 
                        $dayName = $days[$weekdayIndex]; 

                        $dayNumber = date('j', $timestamp); 
                        $monthIndex = date('n', $timestamp) - 1; 
                        $monthName = $months[$monthIndex];
                        $year = date('Y', $timestamp);

                        $date_str = $dayName . ", " . $dayNumber . " " . $monthName . " " . $year;

                        echo "<li><input type='checkbox' name='selected_dates[]' value='$day'> $date_str</li>";
                    }
                    echo "</ul>";
                    ?>
                    <p class="mt-3">Pentru fiecare zi bifată, se vor crea evenimente cu aceleași ore și texte ca cele completate mai sus.</p>
                </div>
                
                <br><br>
                <button type="submit" class="btn btn-primary">Trimite</button>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<!-- JavaScript pentru a clona grupurile de evenimente și a ascunde zona cu zile suplimentare -->
<script>
document.getElementById('add-event-btn').addEventListener('click', function() {
    var container = document.getElementById('events-container');
    var eventCount = container.querySelectorAll('.event-group').length;
    var newIndex = eventCount; 
    var firstEvent = container.querySelector('.event-group:first-child');
    var newEvent = firstEvent.cloneNode(true);

    newEvent.querySelector('h5').textContent = "Eveniment " + (newIndex + 1);
    newEvent.querySelectorAll('input, textarea').forEach(function(input) {
        var name = input.getAttribute('name');
        if (name) {
            var newName = name.replace(/\[\d+\]/, '['+newIndex+']');
            input.setAttribute('name', newName);
        }
        input.value = '';
        if (input.tagName.toLowerCase() === 'textarea') {
            input.textContent = '';
        }
    });

    container.appendChild(newEvent);

    // Ascundem zona cu zile suplimentare, deoarece acum avem multiple evenimente
    var extraDays = document.getElementById('extra-days-container');
    if (extraDays) {
        extraDays.style.display = 'none';
    }
});
</script>

</body>
</html>
