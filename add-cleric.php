<?php
/* ============================================================
   add-cleric.php  –  adăugare cleric (preot / diacon / etc.)
   Compatibil PHP 8.2 • Bootstrap 5
============================================================ */

include 'conectaredb.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}


/* ---------- ranguri pentru dropdown ---------- */
$ranguri = [];
$res_r = $conn->query("SELECT id, denumire_ro FROM rang_administrativ ORDER BY denumire_ro");
while ($r = $res_r->fetch_assoc()) $ranguri[$r['id']] = $r['denumire_ro'];
$res_r->free();

/* ---------- inițializare formular ---------- */
$values = [
    'nume'     => '',
    'prenume'  => '',
    'rang_id'  => '',
    'telefon'  => '',
    'email'    => '',
    'email2'   => ''
];
$feedback = '';

/* ---------- procesare submit ---------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_cleric'])) {

    foreach ($values as $k=>$v) {
        $values[$k] = trim($_POST[$k] ?? '');
    }

    $sql = "INSERT INTO clerici
              (nume, prenume, rang_administrativ_id, telefon, email, email2)
            VALUES (?,?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        'ssisss',
        $values['nume'],
        $values['prenume'],
        $values['rang_id'],
        $values['telefon'],
        $values['email'],
        $values['email2']
    );

    if ($stmt->execute()) {
        $feedback = '<div class="alert alert-success" id="dispari">Clericul a fost adăugat!</div>';
        foreach ($values as $k=>$v) $values[$k] = '';      // goli formularul
    } else {
        $feedback = '<div class="alert alert-danger">Eroare: '.$stmt->error.'</div>';
    }
    $stmt->close();
}

include 'header.php';
?>
 
<body>
<div class="container ">
    <div class="row gx-4">
        <aside class="col-md-3 mb-4">
            <?php include 'sidebar.php'; ?>
        </aside>

        <main class="col-md-9">
            <h1 class="mb-4">Adaugă cleric</h1>
            <?= $feedback ?>

            <form method="post" class="row g-3">

                <div class="col-12">
                    <label class="form-label">Nume</label>
                    <input type="text" name="nume" class="form-control" required
                           value="<?= htmlspecialchars($values['nume']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Prenume</label>
                    <input type="text" name="prenume" class="form-control" required
                           value="<?= htmlspecialchars($values['prenume']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Rang</label>
                    <select name="rang_id" class="form-select" required>
                        <option value="" disabled selected>— alege rang —</option>
                        <?php foreach ($ranguri as $id=>$den): ?>
                            <option value="<?= $id ?>" <?= $id==$values['rang_id']?'selected':'' ?>>
                                <?= htmlspecialchars($den) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Telefon</label>
                    <input type="text" name="telefon" class="form-control"
                           value="<?= htmlspecialchars($values['telefon']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Email principal</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($values['email']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Email secundar</label>
                    <input type="email" name="email2" class="form-control"
                           value="<?= htmlspecialchars($values['email2']) ?>">
                </div>

                <div class="col-12">
                    <button class="btn btn-primary" type="submit" name="add_cleric">Adaugă</button>
                    <a href="javascript:history.back()" class="btn btn-secondary">Renunță</a>
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
