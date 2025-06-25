<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/* ───────────────────────────── 1.  ID din URL ─────────────────────────── */
$parohie_id = (isset($_GET['id']) && ctype_digit($_GET['id'])) ? (int)$_GET['id'] : 0;
if ($parohie_id === 0) {
    echo '<div class="container py-5"><h3>ID parohie invalid.</h3></div>';
    include 'footer.php';
    exit;
}

/* ─────────────────────────── 2.  Şterge asignare ───────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['del_cpr'])) {
    $asign_id = (int)$_POST['del_cpr'];
    $stmt_del = $conn->prepare(
        'DELETE FROM clerici_parohii WHERE id = ? AND parohie_id = ?'
    );
    $stmt_del->bind_param('ii', $asign_id, $parohie_id);
    $stmt_del->execute();
    $stmt_del->close();

    header("Location: edit-parohie.php?id=$parohie_id&del=1");
    exit;
}
if (isset($_GET['del'])) {
    $feedback = '<div class="alert alert-success" id="dispari">Asignarea a fost ștearsă.</div>';
} else {
    $feedback = '';
}


/* ─────────────────────────── 2b.  Adaugă asignare ────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cleric'])) {
    $cleric_id   = (int)$_POST['cleric_id'];
    $pozitie_id  = (int)$_POST['pozitie_parohie_id'];

    // împiedicăm duplicarea aceleiași asignări active
    $stmt_chk = $conn->prepare(
        'SELECT 1 FROM clerici_parohii
         WHERE cleric_id = ? AND parohie_id = ? AND data_sfarsit IS NULL LIMIT 1'
    );
    $stmt_chk->bind_param('ii', $cleric_id, $parohie_id);
    $stmt_chk->execute();
    $stmt_chk->store_result();

    if (!$stmt_chk->num_rows) {
        $stmt_ins = $conn->prepare(
            'INSERT INTO clerici_parohii
               (cleric_id, parohie_id, pozitie_parohie_id, data_start)
             VALUES (?,?,?,CURDATE())'
        );
        $stmt_ins->bind_param('iii', $cleric_id, $parohie_id, $pozitie_id);
        $stmt_ins->execute();
        $stmt_ins->close();
        header("Location: edit-parohie.php?id=$parohie_id&add=1");
        exit;
    }
    $stmt_chk->close();
}
if (isset($_GET['add'])) {
    $feedback = '<div class="alert alert-success" id="dispari">Clericul a fost adăugat.</div>';
}



/* ────────────────── 3.  Funcţie utilitară lookup simplu ────────────────── */
function fetchLookup(mysqli $conn, string $table, string $idCol, string $nameCol): array
{
    $out = [];
    $res = $conn->query("SELECT $idCol, $nameCol FROM $table ORDER BY $nameCol");
    while ($r = $res->fetch_assoc()) $out[$r[$idCol]] = $r[$nameCol];
    $res->free();
    return $out;
}

/* ─────────────────────────── 4.  Lookup‑uri rapide ─────────────────────── */
$tari         = fetchLookup($conn, 'tari',          'id', 'denumire_ro');
$tipuri       = fetchLookup($conn, 'tip_parohie',   'id', 'denumire_ro');
$protopopiate = fetchLookup($conn, 'protopopiate',  'id', 'denumire_ro');
$localitati   = fetchLookup($conn, 'localitati',    'id', 'denumire_en');

/* ───────────────────── Parohii pentru dropdown parohie‑mamă ─────────────── */

/* ───────────────── Clerici & poziții pentru formularul de asignare ───────────────── */
$pozitii = fetchLookup($conn, 'pozitie_parohie', 'id', 'denumire_ro');

$clerici_all = [];
$sql_all_clerici = "
    SELECT c.id, c.nume, c.prenume, COALESCE(ra.denumire_ro,'-') AS rang
    FROM   clerici c
    LEFT  JOIN rang_administrativ ra ON ra.id = c.rang_administrativ_id
    ORDER  BY ra.id, c.nume, c.prenume";
$res_all = $conn->query($sql_all_clerici);
while ($row = $res_all->fetch_assoc()) {
    $clerici_all[$row['id']] = '['.$row['rang'].'] '.$row['nume'].' '.$row['prenume'];
}
$res_all->free();

$parohii_all = [];
$sql_par_mama = "
    SELECT p.id, p.denumire, l.denumire_en AS localitate, t.denumire_ro AS tara
    FROM parohii p
    JOIN localitati l ON p.localitate_id = l.id
    JOIN tari t ON p.tara_id = t.id
    ORDER BY t.denumire_ro, p.denumire
";
$res_pm = $conn->query($sql_par_mama);
while ($row = $res_pm->fetch_assoc()) {
    $parohii_all[$row['id']] = '['.$row['tara'].'] '.$row['denumire'].' ('.$row['localitate'].')';
}
$res_pm->free();

/* ───────────────────────────── 5.  Update parohie ───────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_parohie'])) {
    $denumire      = trim($_POST['denumire']);
    $denumire_en   = trim($_POST['denumire_en']);
    $tara_id       = (int)$_POST['tara_id'];
    $localitate_id = (int)$_POST['localitate_id'];
    $tip_id        = (int)$_POST['tip_id'];
    $protopopiat_id= $_POST['protopopiat_id'] !== '' ? (int)$_POST['protopopiat_id'] : null;
    $parohie_mama_id= $_POST['parohie_mama_id'] !== '' ? (int)$_POST['parohie_mama_id'] : null;
    $hram_ro       = trim($_POST['hram_ro']);
    $hram_en       = trim($_POST['hram_en']);
    $data_hram_ro       = trim($_POST['data_hram_ro']);
    $data_hram_en       = trim($_POST['data_hram_en']);
    $adresa        = trim($_POST['adresa']);
    $website       = trim($_POST['website']);
    if ($website !== '' && !preg_match('#^https?://#i', $website)) $website = 'https://'.$website;
    $email         = trim($_POST['email']);

    $sql_up = "UPDATE parohii SET
                denumire = ?, denumire_en = ?, tara_id = ?, localitate_id = ?,
                tip_parohie_id = ?, protopopiat_id = ?, parohie_mama_id = ?,
                hram_ro = ?, hram_en = ?, adresa = ?, website = ?, email = ?, data_hram_ro=?, data_hram_en=?
               WHERE id = ?";
    $stmt = $conn->prepare($sql_up);
    $stmt->bind_param('ssiiiiisssssssi', $denumire, $denumire_en, $tara_id, $localitate_id,
        $tip_id, $protopopiat_id, $parohie_mama_id, $hram_ro, $hram_en,
        $adresa, $website, $email, $data_hram_ro, $data_hram_en, $parohie_id);
    if ($stmt->execute()) {
        $feedback = '<div class="alert alert-success" id="dispari">Salvat cu succes!</div>';
    } else {
        $feedback = '<div class="alert alert-danger">Eroare: '.htmlspecialchars($stmt->error).'</div>';
    }
    $stmt->close();
}

/* ───────────────────────── 6.  Reîncarcă parohia ────────────────────────── */
$stmt = $conn->prepare('SELECT * FROM parohii WHERE id = ?');
$stmt->bind_param('i', $parohie_id);
$stmt->execute();
$parohie = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$parohie) {
    echo '<div class="container py-5"><h3>Parohie inexistentă.</h3></div>';
    include 'footer.php';
    exit;
}
$site = $parohie['website'];
if ($site && !preg_match('#^https?://#i', $site)) $site = 'https://'.$site;

/* ───────────────────── 7.  Clerici ordonaţi după sort_order ─────────────── */
$sql_clerici = "
  SELECT  cp.id          AS id_asign,
          c.id           AS cleric_id,
          c.nume,
          c.prenume,
          ra.denumire_ro AS rang,
          pp.denumire_ro AS pozitie,
          cp.sort_order
  FROM    clerici_parohii cp
  JOIN    clerici             c  ON c.id = cp.cleric_id
  LEFT JOIN rang_administrativ ra ON ra.id = c.rang_administrativ_id
  JOIN    pozitie_parohie     pp ON pp.id = cp.pozitie_parohie_id
  WHERE   cp.parohie_id = ?
    AND  (cp.data_sfarsit IS NULL OR cp.data_sfarsit >= CURDATE())
  ORDER BY (cp.sort_order IS NULL), cp.sort_order, pp.id, c.nume, c.prenume";
$stmt_cl = $conn->prepare($sql_clerici);
$stmt_cl->bind_param('i', $parohie_id);
$stmt_cl->execute();
$res_clerici = $stmt_cl->get_result();
$stmt_cl->close();

/* ───────────────────────────── 8.  UI / FRONTEND ────────────────────────── */
include 'header.php';
?>