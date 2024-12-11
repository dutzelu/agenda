<?php
include "conectaredb.php";

// Preluare episcopi
$stmt_episcopi = $conn->prepare("SELECT id, nume_scurt_episcop FROM episcopi");
$stmt_episcopi->execute();
$result_episcopi = $stmt_episcopi->get_result();
$episcopi = [];
while ($row = $result_episcopi->fetch_assoc()) {
    $episcopi[] = $row;
}
$stmt_episcopi->close();

// Preluare date eveniment
if (isset($_GET['id']) || isset($_POST['id'])) {
    $id = isset($_GET['id']) ? $_GET['id'] : $_POST['id'];

    // Actualizare date eveniment
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $episcop_id = $_POST['episcop_id'];
        $event_start = $_POST['event_start'];
        $event_end = $_POST['event_end'];
        $text_ro = $_POST['text_ro'];
        $text_en = $_POST['text_en'];
        $afiseaza_calendar = isset($_POST['afiseaza_calendar']) ? 1 : 0;
        $publicat = isset($_POST['publicat']) ? 1 : 0;

        $stmt = $conn->prepare("UPDATE evenimente SET episcop_id = ?, event_start = ?, event_end = ?, text_ro = ?, text_en = ?, afiseaza_calendar = ?, publicat = ? WHERE id = ?");
        $stmt->bind_param("issssiii", $episcop_id, $event_start, $event_end, $text_ro, $text_en, $afiseaza_calendar, $publicat, $id);

        if ($stmt->execute()) {
            $message = "Eveniment actualizat cu succes!";
        } else {
            $message = "Eroare: " . $stmt->error;
        }
        $stmt->close();
    }

    // Preluare date actualizate
    $stmt = $conn->prepare("SELECT * FROM evenimente WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $eveniment = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    include 'header.php';
}
?>
<body>
<div class="container mt-5">
    <h2>Editare Eveniment</h2>
    <?php if (isset($message)): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if (isset($eveniment)): ?>
        <form method="post" action="">
            <input type="hidden" name="id" value="<?php echo $eveniment['id']; ?>">
            <div class="mb-3">
                <label for="episcop_id" class="form-label">Episcop</label>
                <select class="form-control" id="episcop_id" name="episcop_id" required>
                    <?php foreach ($episcopi as $episcop): ?>
                        <option value="<?php echo $episcop['id']; ?>" <?php echo $episcop['id'] == $eveniment['episcop_id'] ? 'selected' : ''; ?>>
                            <?php echo $episcop['nume_scurt_episcop']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="event_start" class="form-label">Start Eveniment</label>
                <input type="datetime-local" class="form-control" id="event_start" name="event_start" value="<?php echo date('Y-m-d\TH:i', strtotime($eveniment['event_start'])); ?>" required>
            </div>
            <div class="mb-3">
                <label for="event_end" class="form-label">Sfârșit Eveniment</label>
                <input type="datetime-local" class="form-control" id="event_end" name="event_end" value="<?php echo date('Y-m-d\TH:i', strtotime($eveniment['event_end'])); ?>" required>
            </div>
            <div class="mb-3">
                <label for="text_ro" class="form-label">Text în Română</label>
                <textarea class="form-control" id="text_ro" name="text_ro" rows="3" required><?php echo $eveniment['text_ro']; ?></textarea>
            </div>
            <div class="mb-3">
                <label for="text_en" class="form-label">Text în Engleză</label>
                <textarea class="form-control" id="text_en" name="text_en" rows="3" required><?php echo $eveniment['text_en']; ?></textarea>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="afiseaza_calendar" name="afiseaza_calendar" <?php echo $eveniment['afiseaza_calendar'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="afiseaza_calendar">
                    Afișează Calendar
                </label>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="publicat" name="publicat" <?php echo $eveniment['publicat'] ? 'checked' : ''; ?>>
                <label class="form-check-label" for="publicat">
                    Publicat
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Actualizează</button>
            <a href="admin.php" class="btn btn-secondary">Înapoi</a>
        </form>
    <?php else: ?>
        <p>Evenimentul nu a fost găsit.</p>
    <?php endif; ?>
</div>
<?php include 'footer.php';?>
