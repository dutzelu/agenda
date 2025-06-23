<?php
include 'conectaredb.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/* ---------- helper ------------------------------------------------ */
function clean(string $s): string
{
    return trim($s);
}

/* ---------- validăm ID ------------------------------------------- */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: protopopiate.php');
    exit;
}

/* ---------- liste dropdown --------------------------------------- */
/* ţări */
$countries = [];
$res = $conn->query("SELECT id, denumire_ro FROM tari ORDER BY denumire_ro");
while ($row = $res->fetch_assoc()) {
    $countries[$row['id']] = $row['denumire_ro'];
}
$res->free();

/* clerici (protopopi) */
$protopopi = [];
$sqlProt = "SELECT c.id,
                   CONCAT(c.nume, ' ', c.prenume) AS nume_complet
            FROM clerici c
            JOIN rang_administrativ r ON r.id = c.rang_administrativ_id
            WHERE r.denumire_ro = 'protopop'
            ORDER BY c.nume, c.prenume";
$res = $conn->query($sqlProt);
while ($row = $res->fetch_assoc()) {
    $protopopi[$row['id']] = $row['nume_complet'];
}
$res->free();

/* ---------- citim înregistrarea ---------------------------------- */
$stmtInfo = $conn->prepare("SELECT denumire_ro, denumire_en, tara_id, protopop_id
                            FROM protopopiate
                            WHERE id = ?");
$stmtInfo->bind_param('i', $id);
$stmtInfo->execute();
$info = $stmtInfo->get_result()->fetch_assoc();
$stmtInfo->close();

if (!$info) {
    echo '<h1 class="text-center text-danger mt-5">Protopopiatul nu există.</h1>';
    exit;
}

/* ---------- inițializăm valori formular -------------------------- */
$val_ro  = $info['denumire_ro'];
$val_en  = $info['denumire_en'];
$val_tara = $info['tara_id'];
$val_prot = $info['protopop_id'];

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $val_ro   = clean($_POST['denumire_ro'] ?? '');
    $val_en   = clean($_POST['denumire_en'] ?? '');
    $val_tara = (int)($_POST['tara_id'] ?? 0);
    $val_prot = ($_POST['protopop_id'] === '' ? null : (int)$_POST['protopop_id']);

    if ($val_ro === '' || mb_strlen($val_ro) < 3)   $errors[] = 'Denumirea (RO) este obligatorie.';
    if ($val_en === '' || mb_strlen($val_en) < 3)   $errors[] = 'Denumirea (EN) este obligatorie.';
    if (!$val_tara || !isset($countries[$val_tara])) $errors[] = 'Selectează o țară validă.';
    if ($val_prot && !isset($protopopi[$val_prot]))  $errors[] = 'Protopop invalid.';

    if (!$errors) {
        $stmtUpd = $conn->prepare("UPDATE protopopiate
                                   SET denumire_ro=?, denumire_en=?, tara_id=?, protopop_id=?
                                   WHERE id=?");
        $stmtUpd->bind_param('ssiii', $val_ro, $val_en, $val_tara, $val_prot, $id);
        if ($stmtUpd->execute()) {
            $success = true;
        } else {
            $errors[] = 'Eroare la salvare în baza de date: ' . $conn->error;
        }
        $stmtUpd->close();
    }
}

include 'header.php';
?>
<body>
<div class="container ">
    <div class="row gx-4">
        <aside class="col-md-3 mb-4"><?php include 'sidebar.php'; ?></aside>

        <main class="col-md-9">
    <h1 class="mb-4">Editează protopopiat</h1>

    <?php if ($success): ?>
        <div class="alert alert-success">
            Modificările au fost salvate cu succes!
            <a href="protopopiate.php" class="alert-link">Înapoi la listă</a>.
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="needs-validation" novalidate>
        <div class="mb-3">
            <label class="form-label" for="denumire_ro">Denumire (RO) *</label>
            <input type="text" id="denumire_ro" name="denumire_ro"
                   class="form-control" required
                   value="<?= htmlspecialchars($val_ro) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label" for="denumire_en">Denumire (EN) *</label>
            <input type="text" id="denumire_en" name="denumire_en"
                   class="form-control" required
                   value="<?= htmlspecialchars($val_en) ?>">
        </div>

        <div class="mb-3">
            <label class="form-label" for="tara_id">Țară *</label>
            <select id="tara_id" name="tara_id" class="form-select" required>
                <option value="">Alege ţara…</option>
                <?php foreach ($countries as $cid=>$name): ?>
                    <option value="<?= $cid ?>" <?= ($val_tara==$cid)?'selected':''; ?>>
                        <?= htmlspecialchars($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label class="form-label" for="protopop_id">Protopop (opțional)</label>
            <select id="protopop_id" name="protopop_id" class="form-select">
                <option value="">— Fără —</option>
                <?php foreach ($protopopi as $pid=>$name): ?>
                    <option value="<?= $pid ?>" <?= ($val_prot==$pid)?'selected':''; ?>>
                        <?= htmlspecialchars($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Salvează</button>
        <a href="protopopiate.php" class="btn btn-secondary">Înapoi</a>
    </form>
</div>

<!-- validare bootstrap -->
<script>
(() => {
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(f => {
        f.addEventListener('submit', e => {
            if (!f.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            f.classList.add('was-validated');
        }, false);
    });
})();
</script>
<?php include 'footer.php'; ?>
