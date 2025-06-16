<?php
/*  ============================================================
    edit-cleric.php – fișa de editare a unui cleric
    PHP 8.2 • Bootstrap 5
    ============================================================ */

include 'conectaredb.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/* ---------- 1. ID din URL ---------- */
$cleric_id = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$cleric_id) { die('<h3>Cleric inexistent.</h3>'); }

/* ---------- 2. RANGURI (dropdown) ---------- */
$ranguri = [];
$res = $conn->query("SELECT id, denumire_ro FROM rang_administrativ ORDER BY id");
while ($row = $res->fetch_assoc()) $ranguri[$row['id']] = $row['denumire_ro'];
$res->free();

/* ---------- 3. SALVARE DATE CLERIC ---------- */
$feedback = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_c'])) {
    $stmt = $conn->prepare(
        "UPDATE clerici SET nume=?, prenume=?, rang_administrativ_id=?, telefon=?, email=?, email2=?
         WHERE id=?"
    );
    $stmt->bind_param(
        'ssisssi',
        $_POST['nume'],
        $_POST['prenume'],
        $_POST['rang_id'],
        $_POST['telefon'],
        $_POST['email'],
        $_POST['email2'],
        $cleric_id
    );
    $stmt->execute();
    $stmt->close();
    header("Location: edit-cleric.php?id=$cleric_id&ok=1");
    exit;
}

/* ---------- 4. DATE CLERIC ---------- */
$stmt = $conn->prepare(
    "SELECT c.*, ra.denumire_ro AS rang_ro
       FROM clerici c
       JOIN rang_administrativ ra ON ra.id = c.rang_administrativ_id
      WHERE c.id=?"
);
$stmt->bind_param('i', $cleric_id);
$stmt->execute();
$cleric = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$cleric) { die('<h3>Cleric inexistent.</h3>'); }

/* ---------- 5. LISTĂ PAROHII ACTIVE ---------- */
$sql = "SELECT cp.id, p.id AS parohie_id, p.denumire,
               l.denumire_en AS localitate, t.denumire_ro AS tara,
               pp.denumire_ro AS pozitie,
               cp.data_start, cp.data_sfarsit
        FROM clerici_parohii cp
        JOIN parohii p            ON p.id = cp.parohie_id
        JOIN localitati l         ON l.id = p.localitate_id
        JOIN tari t               ON t.id = p.tara_id
        JOIN pozitie_parohie pp   ON pp.id = cp.pozitie_parohie_id
       WHERE cp.cleric_id = ?
       ORDER BY t.denumire_ro, p.denumire";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $cleric_id);
$stmt->execute();
$res_par = $stmt->get_result();

/* ---------- 6. LOOK-UP pentru FORMULAR ASIGNARE ---------- */
function lookup(mysqli $c, string $tbl, string $n) {
    $out=[]; $r=$c->query("SELECT id,$n FROM $tbl ORDER BY $n");
    while($row=$r->fetch_assoc()) $out[$row['id']]=$row[$n];
    $r->free(); return $out;
}
$parohii = lookup($conn,'parohii','denumire');
$pozitii = lookup($conn,'pozitie_parohie','denumire_ro');

/* ---------- 7. ADĂUGARE ASIGNARE ---------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_cpr'])) {
    $parohie_id   = (int)$_POST['parohie_id'];
    $pozitie_id   = (int)$_POST['pozitie_id'];
    $data_start   = $_POST['data_start'] ?: null;
    $data_sfarsit = $_POST['data_sfarsit'] ?: null;

    /* verificăm duplicatul */
    $chk = $conn->prepare(
        "SELECT 1 FROM clerici_parohii
          WHERE cleric_id=? AND parohie_id=? AND pozitie_parohie_id=?
            AND ( (data_start IS NULL AND ? IS NULL) OR data_start=? )
          LIMIT 1"
    );
    $chk->bind_param('iiiss', $cleric_id,$parohie_id,$pozitie_id,$data_start,$data_start);
    $chk->execute(); $chk->store_result();

    if (!$chk->num_rows) {
        $ins = $conn->prepare(
            "INSERT INTO clerici_parohii
                 (cleric_id,parohie_id,pozitie_parohie_id,data_start,data_sfarsit)
             VALUES (?,?,?,?,?)"
        );
        $ins->bind_param('iiiss', $cleric_id,$parohie_id,$pozitie_id,$data_start,$data_sfarsit);
        $ins->execute(); $ins->close();
    }
    $chk->close();
    header("Location: cleric.php?id=$cleric_id");
    exit;
}

include 'header.php';
?>

<body>
<div class="container py-4">
    <div class="row gx-4">
        <aside class="col-md-3 mb-4"><?php include 'sidebar.php'; ?></aside>

        <main class="col-md-9">

            <?php if (isset($_GET['ok'])): ?>
                <div class="alert alert-success" id="dispari">Salvat!</div>
            <?php endif; ?>

            <h1 class="mb-4">
                <?= htmlspecialchars($cleric['nume'].' '.$cleric['prenume']) ?>
            </h1>

            <!-- FORM CLERIC -->
            <form method="post" class="row g-3 mb-5">
                <input type="hidden" name="save_c" value="1">

                <div class="col-md-4">
                    <label class="form-label">Nume</label>
                    <input name="nume" class="form-control" required
                           value="<?= htmlspecialchars($cleric['nume']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Prenume</label>
                    <input name="prenume" class="form-control" required
                           value="<?= htmlspecialchars($cleric['prenume']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Rang</label>
                    <select name="rang_id" class="form-select" required>
                        <?php foreach ($ranguri as $id=>$den): ?>
                            <option value="<?= $id ?>" <?= $id==$cleric['rang_administrativ_id']?'selected':'' ?>>
                                <?= htmlspecialchars($den) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Telefon</label>
                    <input name="telefon" class="form-control"
                           value="<?= htmlspecialchars($cleric['telefon']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email principal</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($cleric['email']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email secundar</label>
                    <input type="email" name="email2" class="form-control"
                           value="<?= htmlspecialchars($cleric['email2']) ?>">
                </div>

                <div class="col-12 text-end">
                    <button class="btn btn-primary">Salvează</button>
                </div>
            </form>

            <!-- LISTĂ PAROHII -->
            <h2 class="h5 mb-3">Parohii / Misiuni</h2>
            <div class="table-responsive">
                <table class="table table-striped table-sm align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Parohie</th><th>Pozitie</th><th>Start</th><th>Sfârșit</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($res_par->num_rows): ?>
                        <?php while ($row = $res_par->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <a href="edit-parohie.php?id=<?= $row['parohie_id'] ?>">
                                        [<?= $row['tara'] ?>] <?= htmlspecialchars($row['denumire']) ?>
                                        (<?= $row['localitate'] ?>)
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($row['pozitie']) ?></td>
                                <td><?= $row['data_start'] ?: '—' ?></td>
                                <td><?= $row['data_sfarsit']?: '—' ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">Nicio înregistrare.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- FORM ASIGNARE -->
            <h3 class="h5 mt-5 mb-3">Adaugă clericul la o parohie</h3>
            <form method="post" class="row g-3">
                <input type="hidden" name="add_cpr" value="1">

                <div class="col-md-5">
                    <label class="form-label">Parohie</label>
                    <select name="parohie_id" class="form-select" required>
                        <option value="" disabled selected>— selectează —</option>
                        <?php foreach ($parohii as $pid=>$den): ?>
                            <option value="<?= $pid ?>"><?= htmlspecialchars($den) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Poziție</label>
                    <select name="pozitie_id" class="form-select" required>
                        <?php foreach ($pozitii as $poz_id=>$poz_den): ?>
                            <option value="<?= $poz_id ?>"><?= htmlspecialchars($poz_den) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Start</label>
                    <input type="date" name="data_start" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sfârșit</label>
                    <input type="date" name="data_sfarsit" class="form-control">
                </div>

                <div class="col-12">
                    <button class="btn btn-secondary">Adaugă</button>
                </div>
            </form>
        </main>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
setTimeout(()=>{const el=document.getElementById('dispari');if(el)el.style.display='none';},2000);
</script>
</body>
</html>
