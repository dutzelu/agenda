<?php
include "conectaredb.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nume_complet_episcop = $_POST['nume_complet_episcop'];
    $nume_scurt_episcop = $_POST['nume_scurt_episcop'];
    $nume_episcopie = $_POST['nume_episcopie'];
    $nume_mitropolie = $_POST['nume_mitropolie'];

    $sql = "INSERT INTO episcopi (nume_complet_episcop, nume_scurt_episcop, nume_episcopie, nume_mitropolie) 
            VALUES ('$nume_complet_episcop', '$nume_scurt_episcop', '$nume_episcopie', '$nume_mitropolie')";

    if ($conn->query($sql) === TRUE) {
        echo "Înregistrare adăugată cu succes!";
    } else {
        echo "Eroare: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
include "header.php";
?>

<body>
<div class="container mt-5">
    <h2>Adăugare episcop</h2>
    <form method="post" action="">
        <div class="mb-3">
            <label for="nume_complet_episcop" class="form-label">Nume complet episcop</label>
            <input type="text" class="form-control" id="nume_complet_episcop" name="nume_complet_episcop" required>
        </div>
        <div class="mb-3">
            <label for="nume_scurt_episcop" class="form-label">Nume scurt episcop</label>
            <input type="text" class="form-control" id="nume_scurt_episcop" name="nume_scurt_episcop" required>
        </div>
        <div class="mb-3">
            <label for="nume_episcopie" class="form-label">Nume episcopie</label>
            <input type="text" class="form-control" id="nume_episcopie" name="nume_episcopie" required>
        </div>
        <div class="mb-3">
            <label for="nume_mitropolie" class="form-label">Nume mitropolie</label>
            <input type="text" class="form-control" id="nume_mitropolie" name="nume_mitropolie" required>
        </div>
        <button type="submit" class="btn btn-primary">Trimite</button>
    </form>
</div>

<?php include 'footer.php'; ?>
