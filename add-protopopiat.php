<?php
/*  add_protopopiat.php ---------------------------------------------
    Formular + procesare pentru adăugare protopopiat                 */
/* ------------------------------------------------------------------*/

include 'conectaredb.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/* --------  funcție helper: trim & sanitize ----------------------- */
function clean(string $s): string
{
    return trim($s);
}

/* --------  preluăm listele pentru dropdown ----------------------- */
/* ţări */
$countries = [];
$res = $conn->query("SELECT id, denumire_ro FROM tari ORDER BY denumire_ro");
while ($row = $res->fetch_assoc()) {
    $countries[$row['id']] = $row['denumire_ro'];
}
$res->free();

/* clerici cu rang protopop */
$protopopi = [];
$sql_prot = "SELECT c.id,
                    CONCAT(c.nume, ' ', c.prenume) AS nume_complet
             FROM clerici c
             JOIN rang_administrativ r ON r.id = c.rang_administrativ_id
             WHERE r.denumire_ro = 'protopop'
             ORDER BY c.nume, c.prenume";
$res = $conn->query($sql_prot);
while ($row = $res->fetch_assoc()) {
    $protopopi[$row['id']] = $row['nume_complet'];
}
$res->free();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $den_ro  = clean($_POST['denumire_ro'] ?? '');
    $den_en  = clean($_POST['denumire_en'] ?? '');
    $tara_id = (int)($_POST['tara_id'] ?? 0);
    $prot_id = ($_POST['protopop_id'] === '' ? null : (int)$_POST['protopop_id']);

    if ($den_ro === '' || mb_strlen($den_ro) < 3)   $errors[] = 'Denumirea (RO) este obligatorie.';
    if ($den_en === '' || mb_strlen($den_en) < 3)   $errors[] = 'Denumirea (EN) este obligatorie.';
    if (!$tara_id || !isset($countries[$tara_id]))  $errors[] = 'Selectează o țară.';
    if ($prot_id && !isset($protopopi[$prot_id]))   $errors[] = 'Protopop invalid.';

    if (!$errors) {
        $stmt = $conn->prepare(
            "INSERT INTO protopopiate (denumire_ro, denumire_en, tara_id, protopop_id)
             VALUES (?,?,?,?)"
        );
        $stmt->bind_param('ssii', $den_ro, $den_en, $tara_id, $prot_id);
        if ($stmt->execute()) {
            $success = true;
            $new_id = $stmt->insert_id;
        } else {
            $errors[] = 'Eroare la salvare în baza de date: ' . $conn->error;
        }
        $stmt->close();
    }
}
include 'header.php';
?>
<body>
<div class="container ">
    <div class="row gx-4">
        <aside class="col-md-3 mb-4"><?php include 'sidebar.php'; ?></aside>

        <main class="col-md-9">
            <h1 class="mb-4">Adaugă protopopiat</h1>

    <?php if ($success): ?>
        <div class="alert alert-success">
            Protopopiat adăugat cu succes!
            <a href="edit-protopopiat.php?id=<?php echo $new_id; ?>" class="alert-link">Editează</a> sau
            <a href="protopopiate.php" class="alert-link">înapoi la listă</a>.
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="needs-validation" novalidate>
        <div class="mb-3">
            <label class="form-label" for="denumire_ro">Denumire (RO) *</label>
            <input type="text" id="denumire_ro" name="denumire_ro"
                   class="form-control" required
                   value="<?php echo isset($_POST['denumire_ro']) ? htmlspecialchars($_POST['denumire_ro']) : ''; ?>">
        </div>

        <div class="mb-3">
            <label class="form-label" for="denumire_en">Denumire (EN) *</label>
            <input type="text" id="denumire_en" name="denumire_en"
                   class="form-control" required
                   value="<?php echo isset($_POST['denumire_en']) ? htmlspecialchars($_POST['denumire_en']) : ''; ?>">
        </div>

        <div class="mb-3">
            <label class="form-label" for="tara_id">Țară *</label>
            <select id="tara_id" name="tara_id" class="form-select" required>
                <option value="">Alege ţara…</option>
                <?php foreach ($countries as $cid=>$name): ?>
                    <option value="<?php echo $cid; ?>"
                        <?php echo (isset($_POST['tara_id']) && $_POST['tara_id']==$cid)?'selected':''; ?>>
                        <?php echo htmlspecialchars($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label class="form-label" for="protopop_id">Protopop (opțional)</label>
            <select id="protopop_id" name="protopop_id" class="form-select">
                <option value="">— Fără —</option>
                <?php foreach ($protopopi as $pid=>$name): ?>
                    <option value="<?php echo $pid; ?>"
                        <?php echo (isset($_POST['protopop_id']) && $_POST['protopop_id']==$pid)?'selected':''; ?>>
                        <?php echo htmlspecialchars($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Salvează</button>
        <a href="protopopiate.php" class="btn btn-secondary">Renunță</a>
    </form>
</div>

<!-- validare bootstrap -->
<script>
(function () {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>
<?php include 'footer.php'; ?>
