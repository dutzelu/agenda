<?php
include 'conectaredb.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/* ---------- obținem țările pentru dropdown ---------- */
$tari = [];
$sqlTari = "SELECT id, denumire_ro FROM tari ORDER BY denumire_ro";
if ($rez = $conn->query($sqlTari)) {
    while ($row = $rez->fetch_assoc()) {
        $tari[] = $row;
    }
}

/* ---------- valori inițiale ---------- */
$values = [
    'denumire_ro' => '',
    'denumire_en' => '',
    'tara_id'     => ''
];

$feedback = '';

/* ---------- procesare submit ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_localitate'])) {

    foreach ($values as $k => $v) {
        $values[$k] = trim($_POST[$k] ?? '');
    }

    // validare simplă
    if ($values['denumire_en'] === '' || $values['tara_id'] === '') {
        $feedback = '<div id="dispari" class="alert alert-danger">Completați câmpurile obligatorii!</div>';
    } else {

        // ——— mysqli_stmt::bind_param necesită variabile reale, NU expresii ———
        $denumire_en = $values['denumire_en'];
        $denumire_ro = $values['denumire_ro'] === '' ? null : $values['denumire_ro'];
        $tara_id     = (int)$values['tara_id'];

        $stmt = $conn->prepare("INSERT INTO localitati (denumire_en, denumire_ro, tara_id) VALUES (?,?,?)");
        $stmt->bind_param("ssi", $denumire_en, $denumire_ro, $tara_id);

        if ($stmt->execute()) {
            $feedback = '<div id="dispari" class="alert alert-success">Localitate adăugată cu succes!</div>';
            // resetăm valorile după succes
            foreach ($values as $k => $v) $values[$k] = '';
        } else {
            $feedback = '<div id="dispari" class="alert alert-danger">Eroare: ' . $stmt->error . '</div>';
        }

        $stmt->close();
    }
}

include 'header.php'; 

?>

<body>
<div class="container ">
    <div class="row gx-4">
        <!-- sidebar -->
        <aside class="col-md-3 mb-4"><?php include 'sidebar.php'; ?></aside>

        <!-- content -->
        <main class="col-md-9">
            <h1 class="mb-4">Adaugă localitate</h1>
            <?= $feedback ?>

            <form method="post" class="row g-3">

                <div class="col-12 col-md-6">
                    <label class="form-label">Denumire (română)</label>
                    <input type="text" name="denumire_ro" class="form-control"
                           value="<?= htmlspecialchars($values['denumire_ro']) ?>">
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">Denumire (engleză) <span class="text-danger">*</span></label>
                    <input type="text" name="denumire_en" class="form-control" required
                           value="<?= htmlspecialchars($values['denumire_en']) ?>">
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">Țara <span class="text-danger">*</span></label>
                    <select name="tara_id" class="form-select" required>
                        <option value="">— alege țara —</option>
                        <?php foreach ($tari as $t): ?>
                            <option value="<?= $t['id'] ?>" <?= $values['tara_id']==$t['id']?'selected':'' ?> >
                                <?= htmlspecialchars($t['denumire_ro']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary" type="submit" name="add_localitate">Adaugă localitate</button>
                </div>

            </form>
        </main>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
// ascundem mesajele după 2 secunde
setTimeout(()=>{ const el=document.getElementById('dispari'); if(el){ el.style.display='none'; } },2000);
</script>
</body>
</html>
