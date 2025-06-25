<?php
/* ============================================================
   edit-parohie.php  –  Vizualizare / editare parohie
   Compatibil PHP 8.2  •  Bootstrap 5
   Versiune îmbunătăţită:  ➜  drag‑and‑drop & sort_order  ➜  coloană „Acţiuni” (Şterge)
   25‑Jun‑2025
============================================================ */

include 'conectaredb.php';

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
    $adresa        = trim($_POST['adresa']);
    $website       = trim($_POST['website']);
    if ($website !== '' && !preg_match('#^https?://#i', $website)) $website = 'https://'.$website;
    $email         = trim($_POST['email']);

    $sql_up = "UPDATE parohii SET
                denumire = ?, denumire_en = ?, tara_id = ?, localitate_id = ?,
                tip_parohie_id = ?, protopopiat_id = ?, parohie_mama_id = ?,
                hram_ro = ?, hram_en = ?, adresa = ?, website = ?, email = ?
               WHERE id = ?";
    $stmt = $conn->prepare($sql_up);
    $stmt->bind_param('ssiiiiisssssi', $denumire, $denumire_en, $tara_id, $localitate_id,
        $tip_id, $protopopiat_id, $parohie_mama_id, $hram_ro, $hram_en,
        $adresa, $website, $email, $parohie_id);
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
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<div class="container ">
  <div class="row gx-4 gy-3">
    <!-- Sidebar -->
    <aside class="col-md-3 mb-4">
      <?php include 'sidebar.php'; ?>
    </aside>

    <!-- Conţinut principal -->
    <main class="col-md-9 m-0">
      <?php
      /* badge colorat pt. tipul parohiei */
      $badgeMap = [1=>'primary',2=>'secondary',3=>'danger',4=>'warning',5=>'success',6=>'info'];
      $tip_id    = (int)$parohie['tip_parohie_id'];
      $tip_label = ucfirst($tipuri[$tip_id] ?? '');
      $bgClass   = 'bg-'.($badgeMap[$tip_id] ?? 'dark');
      echo '<h1 class="badge '.$bgClass.' tip_parohie mb-0">'.$tip_label.'</h1>';
      ?>
      <h2 class="mb-4 mt-0"><?= htmlspecialchars($parohie['denumire']); ?></h2>

      <hr>

      <!-- =======================  CLERICII PAROHIEI  ====================== -->
      <h2 class="h5 mb-3">Clerici care slujesc aici</h2>

      <?php if ($res_clerici->num_rows): ?>
      <div class="table-responsive mb-4">
        <table id="tabel-clerici" class="table table-sm table-striped align-middle">
          <thead class="table-dark">
            <tr>
              <th style="width:45px" class="text-center">⇅</th>
              <th>Nume</th>
              <th>Rang</th>
              <th>Pozitie</th>
              <th class="text-center" style="width:120px">Acțiuni</th>
            </tr>
          </thead>
          <tbody id="clerici-list">
            <?php while ($cl = $res_clerici->fetch_assoc()): ?>
              <tr data-cp-id="<?= $cl['id_asign'] ?>">
                <td class="handle text-center" style="cursor:move">☰</td>
                <td>
                  <a href="edit-cleric.php?id=<?= htmlspecialchars($cl['cleric_id']) ?>">
                    <?= htmlspecialchars($cl['nume'].' '.$cl['prenume']) ?>
                  </a>
                </td>
                <td><?= htmlspecialchars($cl['rang']) ?></td>
                <td><?= htmlspecialchars($cl['pozitie']) ?></td>
                <td class="text-center">
                  <form method="post" class="d-inline" onsubmit="return confirm('Ștergi această asignare?');">
                    <input type="hidden" name="del_cpr" value="<?= $cl['id_asign'] ?>">
                    <button class="btn btn-sm btn-danger"><span class="bi bi-trash"></span> Șterge</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <button id="save-order" class="btn btn-primary">Salvează ordinea</button>
      <div id="order-feedback" class="mt-2"></div>
      <?php else: ?>
        <p class="text-muted">Nu există clerici activați la această parohie.</p>
      <?php endif; ?>

    <!-- ===================  FORM ADĂUGARE CLERIC  =================== -->
    <form method="post" class="row row-cols-lg-auto g-2 align-items-end mb-4 mt-3">
    <div class="col-12">
        <label class="form-label mb-0 small">Adaugă cleric</label>
        <select name="cleric_id" class="form-select" required>
        <option value="">— Selectează —</option>
        <?php foreach ($clerici_all as $id=>$label): ?>
            <option value="<?= $id ?>"><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
        </select>
    </div>

    <div class="col-12">
        <label class="form-label mb-0 small">Poziție</label>
        <select name="pozitie_parohie_id" class="form-select" required>
        <?php foreach ($pozitii as $id=>$den): ?>
            <option value="<?= $id ?>"><?= htmlspecialchars($den) ?></option>
        <?php endforeach; ?>
        </select>
    </div>

    <div class="col-12">
        <button type="submit" name="add_cleric" class="btn btn-success">
        <span class="bi bi-plus-lg"></span> Adaugă
        </button>
    </div>
    </form>




      <?= $feedback ?>

      <!-- =======================  FORM EDIT PAROHIE ======================= -->
            <form method="post" class="row g-3 mt-3">

                <div class="col-12">
                    <label class="form-label">Denumire (RO)</label>
                    <input type="text" name="denumire" class="form-control" required
                           value="<?= htmlspecialchars($parohie['denumire']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Denumire (EN)</label>
                    <input type="text" name="denumire_en" class="form-control" required
                           value="<?= htmlspecialchars($parohie['denumire_en']) ?>">
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
$(function(){
  /* drag-and-drop */
  $('#clerici-list').sortable({handle:'.handle', placeholder:'ui-state-highlight'});

  /* buton salvare */
  $('#save-order').on('click',function(){
    const ord=[];
    $('#clerici-list tr').each(function(i){ord.push({id:$(this).data('cp-id'),sort:i+1});});
    $.post('update-order.php',{order:ord},function(r){
      if(r.success){
        $('#order-feedback').html('<div class="alert alert-success">Ordinea a fost salvată!</div>');
        /* mesaj auto‑hide */
        setTimeout(()=>{$('#order-feedback .alert').fadeOut(500,function(){$(this).remove();});},2500);
      } else {
        $('#order-feedback').html('<div class="alert alert-danger">Eroare: '+r.error+'</div>');
        setTimeout(()=>{$('#order-feedback .alert').fadeOut(500,function(){$(this).remove();});},4000);
      }
    },'json');
  });

  /* auto-close pentru alerte cu id="dispari" */
  setTimeout(()=>{const el=document.getElementById('dispari');if(el)$(el).fadeOut(500);},2500);
});
</script>
<?php include 'footer.php'; ?>
