<?php
include "conectaredb.php";

$success = NULL;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $episcop_id = $_POST['episcop_id'];
    $event_start = $_POST['event_start'];
    $event_end = $_POST['event_end'];
    $text_ro = $_POST['text_ro'];
    $text_en = $_POST['text_en'];
    $afiseaza_calendar = isset($_POST['afiseaza_calendar']) ? 1 : 0;
    $publicat = isset($_POST['publicat']) ? 1 : 0;

    // Ensure the datetime values are properly formatted for MySQL
    $event_start = date('Y-m-d H:i:s', strtotime($event_start));
    $event_end = date('Y-m-d H:i:s', strtotime($event_end));

    $sql = "INSERT INTO evenimente (episcop_id, event_start, event_end, text_ro, text_en, afiseaza_calendar, publicat) 
            VALUES ('$episcop_id', '$event_start', '$event_end', '$text_ro', '$text_en', '$afiseaza_calendar', '$publicat')";

    if ($conn->query($sql) === TRUE) {$success = true;}
}

// Preluare date episcopi pentru dropdown
$episcopi_sql = "SELECT id, nume_complet_episcop FROM episcopi";
$episcopi_result = $conn->query($episcopi_sql);

$conn->close();
include "header.php";
?>




<body onload="showMessage()">



<div class="container mt-5">
    <h2>Adaugă eveniment</h2>
    <?php if ($success): ?>
            <div class="alert alert-primary mt-4" id="dispari" role="alert">
                Evenimentul a fost adăugat cu succes
            </div>
    <?php endif; ?>
    <form method="post" action="">
        <div class="mb-3">
            <label for="episcop_id" class="form-label">Episcop</label>
            <select class="form-control" id="episcop_id" name="episcop_id" required>
                <option value="" selected disabled>Selectează un episcop</option>
                <?php
                if ($episcopi_result->num_rows > 0) {
                    while($row = $episcopi_result->fetch_assoc()) {
                        echo '<option value="' . $row["id"] . '">' . $row["nume_complet_episcop"] . '</option>';
                    }
                } else {
                    echo '<option value="" disabled>Nicio intrare găsită</option>';
                }
                ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="event_start" class="form-label">Start Eveniment</label>
            <input type="datetime-local" class="form-control" id="event_start" name="event_start" required>
        </div>
        <div class="mb-3">
            <label for="event_end" class="form-label">Sfârșit Eveniment</label>
            <input type="datetime-local" class="form-control" id="event_end" name="event_end" required>
        </div>
        <div class="mb-3">
            <label for="text_ro" class="form-label">Text în Română</label>
            <textarea class="form-control" id="text_ro" name="text_ro" rows="3" required></textarea>
        </div>
        <div class="mb-3">
            <label for="text_en" class="form-label">Text în Engleză</label>
            <textarea class="form-control" id="text_en" name="text_en" rows="3" required></textarea>
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
        <button type="submit" class="btn btn-primary">Trimite</button>
    </form>
</div>


<?php include 'footer.php'; ?>