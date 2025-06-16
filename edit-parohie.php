<?php
/* ============================================================
   edit-parohie.php  –  Vizualizare / editare parohie
   Compatibil PHP 8.2  •  Bootstrap 5
============================================================ */

include 'conectaredb.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/* ===================  ȘTERGE ASIGNARE  =================== */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['del_cpr'])) {
    $asign_id = (int)$_POST['del_cpr'];

    /* ștergem doar dacă asignarea aparține acestei parohii */
    $stmt_del = $conn->prepare(
        "DELETE FROM clerici_parohii
          WHERE id = ? AND parohie_id = ?"
    );
    $stmt_del->bind_param('ii', $asign_id, $parohie_id);
    $stmt_del->execute();
    $stmt_del->close();

    header("Location: edit-parohie.php?id=$parohie_id&del=1");
    exit;
}

if (isset($_GET['del'])) {
    echo '<div class="alert alert-success" id="dispari">Asignarea a fost ștearsă.</div>';
}


/* ---------- 1. ID parohie din URL ---------- */
$parohie_id = (isset($_GET['id']) && ctype_digit($_GET['id'])) ? (int)$_GET['id'] : 0;
if ($parohie_id === 0) {
    echo '<div class="container py-5"><h3>ID parohie invalid.</h3></div>';
    include 'footer.php';
    exit;
}

/* ---------- 2. Funcție simplă pentru lookup ---------- */
function fetchLookup(mysqli $conn, string $table, string $idCol, string $nameCol): array
{
    $out = [];
    $res = $conn->query("SELECT $idCol, $nameCol FROM $table ORDER BY $nameCol");
    while ($r = $res->fetch_assoc()) $out[$r[$idCol]] = $r[$nameCol];
    $res->free();
    return $out;
}

/* ---------- 3. Lookup-uri ---------- */
$tari         = fetchLookup($conn, 'tari',          'id', 'denumire_ro');
$tipuri       = fetchLookup($conn, 'tip_parohie',   'id', 'denumire_ro');
$protopopiate = fetchLookup($conn, 'protopopiate',  'id', 'denumire_ro');
$localitati   = fetchLookup($conn, 'localitati',    'id', 'denumire_en');  // simplificare

// Parohii cu țară și localitate pentru dropdown parohie-mamă
$parohii_all = [];
$sql_par_mama = "
    SELECT p.id, p.denumire, l.denumire_en AS localitate, t.denumire_ro AS tara
    FROM parohii p
    JOIN localitati l ON p.localitate_id = l.id
    JOIN tari t ON p.tara_id = t.id
    ORDER BY t.denumire_ro ASC, p.denumire ASC
";
$res_pm = $conn->query($sql_par_mama);
while ($row = $res_pm->fetch_assoc()) {
    $parohii_all[$row['id']] = '['.$row['tara'].'] '.$row['denumire'].' ('.$row['localitate'].')';
}
$res_pm->free();


/* ---------- 4. Salvare (dacă POST) ---------- */
$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_parohie'])) {

    /* a) Citire & sanitaizare */
    $denumire       = trim($_POST['denumire']);
    $tara_id        = (int)$_POST['tara_id'];
    $localitate_id  = (int)$_POST['localitate_id'];
    $tip_id         = (int)$_POST['tip_id'];
    $protopopiat_id = $_POST['protopopiat_id'] !== '' ? (int)$_POST['protopopiat_id'] : null;
    $parohie_mama_id= $_POST['parohie_mama_id']  !== '' ? (int)$_POST['parohie_mama_id'] : null;
    $hram_ro        = trim($_POST['hram_ro']);
    $hram_en        = trim($_POST['hram_en']);
    $adresa         = trim($_POST['adresa']);
    $website        = trim($_POST['website']);
    $email          = trim($_POST['email']);

    /* dacă website-ul nu are schemă, adaugă https:// (poţi schimba pe http://) */
    if ($website !== '' && !preg_match('#^https?://#i', $website)) {
        $website = 'https://' . $website;
    }

    /* b) Update */
    $sql = "UPDATE parohii SET
                denumire = ?, tara_id = ?, localitate_id = ?,
                tip_parohie_id = ?, protopopiat_id = ?,
                parohie_mama_id = ?, hram_ro = ?, hram_en = ?,
                adresa = ?, website = ?, email = ?
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    /* tipuri: s  i  i  i  i  i  s  s  s  s  s  i */
    $stmt->bind_param(
        'siiiiisssssi',
        $denumire,
        $tara_id,
        $localitate_id,
        $tip_id,
        $protopopiat_id,
        $parohie_mama_id,
        $hram_ro,
        $hram_en,
        $adresa,
        $website,
        $email,
        $parohie_id
    );

    if ($stmt->execute()) {
        $feedback = '<div class="alert alert-success" id="dispari">Salvat cu succes!</div>';
    } else {
        $feedback = '<div class="alert alert-danger">Eroare la salvare: '.
                     htmlspecialchars($stmt->error).'</div>';
    }
    $stmt->close();
}

/* ---------- 5. Reîncarcă datele parohiei ---------- */
$stmt = $conn->prepare(
    "SELECT * FROM parohii WHERE id = ?"
);
$stmt->bind_param('i', $parohie_id);
$stmt->execute();
$parohie = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$parohie) {
    echo '<div class="container py-5"><h3>Parohie inexistentă.</h3></div>';
    include 'footer.php';
    exit;
}

/* pregăteşte website-ul pt. afișare (prefix după caz) */

/* ---------- 6. Clericii care slujesc în această parohie ---------- */
$sql_clerici = "
    SELECT  c.id,
            c.nume,
            c.prenume,
            ra.denumire_ro AS rang,
            pp.denumire_ro AS pozitie
      FROM clerici_parohii cp
      JOIN clerici          c  ON c.id  = cp.cleric_id
      JOIN rang_administrativ ra ON ra.id = c.rang_administrativ_id
      JOIN pozitie_parohie  pp ON pp.id = cp.pozitie_parohie_id
     WHERE cp.parohie_id = ?
       AND (cp.data_sfarsit IS NULL OR cp.data_sfarsit >= CURDATE())
     ORDER BY pp.id, c.nume, c.prenume";

$stmt_cl = $conn->prepare($sql_clerici);
$stmt_cl->bind_param('i', $parohie_id);
$stmt_cl->execute();
$res_clerici = $stmt_cl->get_result();
$stmt_cl->close();

 

$site = $parohie['website'];
if ($site && !preg_match('#^https?://#i', $site)) $site = 'https://' . $site;

$sql_clerici = "
SELECT  cp.id            AS id_asign,   -- ★ aici
        c.id             AS cleric_id,  -- util dacă vrei link spre cleric
        c.nume,
        c.prenume,
        ra.denumire_ro   AS rang,
        pp.denumire_ro   AS pozitie
    FROM clerici_parohii cp
    JOIN clerici            c  ON c.id  = cp.cleric_id
    JOIN rang_administrativ ra ON ra.id = c.rang_administrativ_id
    JOIN pozitie_parohie    pp ON pp.id = cp.pozitie_parohie_id
    WHERE cp.parohie_id = ?
    AND (cp.data_sfarsit IS NULL OR cp.data_sfarsit >= CURDATE())
    ORDER BY pp.id, c.nume, c.prenume";


include 'header.php';
?>
 
<body>
<div class="container m-5 py-4">
    <div class="row gx-4 gy-3">
        <!-- Sidebar -->
        <aside class="col-md-3 mb-4">
            <?php include 'sidebar.php'; ?>
        </aside>

        <!-- Conţinut -->
        <main class="col-md-9 m-0">
            <?php

            /* ------------ afișăm badge-ul colorat ------------ */
       /* ------------ mapăm tip-id → culoare Bootstrap ------------ */
        $badgeMap = [
            1 => 'primary',      // catedrala arhiepiscopală
            2 => 'secondary',    // paraclis arhiepiscopal
            3 => 'danger',       // parohie
            4 => 'warning',      // misiune
            5 => 'success',      // mănăstire
            6 => 'info',         // schit
        ];

        /* ------------ afișăm badge-ul colorat ------------ */
        $tip_id    = (int)$parohie['tip_parohie_id'];
        $tip_label = ucfirst($tipuri[$tip_id] ?? '');      // textul vizibil
        $bgClass   = 'bg-'.($badgeMap[$tip_id] ?? 'dark'); // culoare; fallback „dark”

        echo '<h1 class="tip_parohie mb-0 badge '.$bgClass.'">'.$tip_label.'</h1>';

            ?>
            <h2 class="mb-4 mt-0">
                <?= htmlspecialchars($parohie['denumire']);?>
            </h2>

            <hr>

            <!-- =======================  CLERICII PAROHIEI  ====================== -->
            <h2 class="h5 mb-3">Clerici care slujesc aici</h2>

            <?php if ($res_clerici->num_rows): ?>
            <div class="table-responsive mb-4">
                <table class="table table-sm table-striped align-middle">
                    
                <thead class="table-dark">
                    <tr>
                        <th>Nume</th>
                        <th>Rang</th>
                        <th>Pozitie</th>
                        <th class="text-center">Acțiuni</th>
                    </tr>
                </thead>
                
                <tbody>
                <?php if ($res_clerici->num_rows): ?>
                    <?php while ($cl = $res_clerici->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <a href="cleric.php?id=<?= $cl['id'] ?>">
                                    <?= htmlspecialchars($cl['nume'].' '.$cl['prenume']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($cl['rang']) ?></td>
                            <td><?= htmlspecialchars($cl['pozitie']) ?></td>

                            <!-- Buton Șterge -->
                            <td class="text-center">
                                <form method="post" class="d-inline"
                                    onsubmit="return confirm('Ștergi această asignare?');">
                                    <input type="hidden" name="del_cpr"
       value="<?= $cl['id_asign'] ?>">
                                    <button class="btn btn-sm btn-danger">
                                        Șterge
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center">Nu există clerici.</td></tr>
                <?php endif; ?>
                </tbody>

                </table>
            </div>
            <?php else: ?>
                <p class="text-muted">Nu există clerici activați la această parohie.</p>
            <?php endif; ?>
            <!-- ================================================================ -->



            <?= $feedback ?>

            <!-- ================================================== -->
            <form method="post" class="row g-3">

                <div class="col-12">
                    <label class="form-label">Denumire</label>
                    <input type="text" name="denumire" class="form-control" required
                           value="<?= htmlspecialchars($parohie['denumire']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Țară</label>
                    <select name="tara_id" class="form-select" required>
                        <?php foreach ($tari as $id=>$den): ?>
                            <option value="<?= $id ?>" <?= $id==$parohie['tara_id']?'selected':'' ?>>
                                <?= htmlspecialchars($den) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Localitate</label>
                    <select name="localitate_id" class="form-select" required>
                        <?php foreach ($localitati as $id=>$den): ?>
                            <option value="<?= $id ?>" <?= $id==$parohie['localitate_id']?'selected':'' ?>>
                                <?= htmlspecialchars($den) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Tip parohie</label>
                    <select name="tip_id" class="form-select" required>
                        <?php foreach ($tipuri as $id=>$den): ?>
                            <option value="<?= $id ?>" <?= $id==$parohie['tip_parohie_id']?'selected':'' ?>>
                                <?= htmlspecialchars($den) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Protopopiat</label>
                    <select name="protopopiat_id" class="form-select">
                        <option value="">— fără —</option>
                        <?php foreach ($protopopiate as $id=>$den): ?>
                            <option value="<?= $id ?>" <?= $id==$parohie['protopopiat_id']?'selected':'' ?>>
                                <?= htmlspecialchars($den) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Parohie mamă</label>
                    <select name="parohie_mama_id" class="form-select">
                        <option value="">— fără —</option>
                        <?php foreach ($parohii_all as $id=>$den): ?>
                            <option value="<?= $id ?>" <?= $id==$parohie['parohie_mama_id']?'selected':'' ?>>
                                <?= htmlspecialchars($den) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Hram (RO)</label>
                    <input type="text" name="hram_ro" class="form-control"
                           value="<?= htmlspecialchars($parohie['hram_ro']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Hram (EN)</label>
                    <input type="text" name="hram_en" class="form-control"
                           value="<?= htmlspecialchars($parohie['hram_en']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Adresă</label>
                    <input type="text" name="adresa" class="form-control"
                           value="<?= htmlspecialchars($parohie['adresa']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Website</label>
                    <input type="text" name="website" class="form-control"
                           value="<?= htmlspecialchars($site) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($parohie['email']) ?>">
                </div>

                <div class="col-12">
                    <button type="submit" name="save_parohie" class="btn btn-primary">Salvează</button>
                    <a href="javascript:history.back()" class="btn btn-secondary">Înapoi</a>
                </div>
            </form>
            <!-- ================================================== -->

        </main>
    </div>
</div>

<script>
    /* mesajul succes (id=dispari) dispare după 2 sec. – dacă îl ai */
    setTimeout(() => { const el=document.getElementById('dispari'); if(el) el.style.display='none'; }, 2000);
</script>
    <?php include 'footer.php'; ?>

