<?php
/* ============================================================
   cleric.php   –   Vizualizează / editează un cleric
   Compatibil PHP 8.2
============================================================ */

include 'conectaredb.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'header.php';

/* ---------- 1. ID din URL ---------- */
$cleric_id = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int)$_GET['id'] : 0;
if ($cleric_id === 0) {
    echo '<div class="container py-5"><h3>ID cleric invalid.</h3></div>';
    include 'footer.php';
    exit;
}

/* ---------- 2. Look-up ranguri pt. dropdown ---------- */
$ranguri   = [];
$res_rang  = $conn->query("SELECT id, denumire_ro FROM rang_administrativ ORDER BY denumire_ro");
while ($r = $res_rang->fetch_assoc()) {
    $ranguri[$r['id']] = $r['denumire_ro'];
}
$res_rang->free();

/* ---------- 3. Dacă s-a trimis formularul, facem UPDATE ---------- */
$feedback = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cleric'])) {
    $nume      = trim($_POST['nume']);
    $prenume   = trim($_POST['prenume']);
    $rang_id   = (int)$_POST['rang_id'];
    $telefon   = trim($_POST['telefon']);
    $email     = trim($_POST['email']);

    $stmt_upd = $conn->prepare(
        "UPDATE clerici SET nume=?, prenume=?, rang_administrativ_id=?, telefon=?, email=? WHERE id=?"
    );
    $stmt_upd->bind_param('ssissi', $nume, $prenume, $rang_id, $telefon, $email, $cleric_id);
    if ($stmt_upd->execute()) {
        $feedback = '<div class="alert alert-success">Date salvate!</div>';
    } else {
        $feedback = '<div class="alert alert-danger">Eroare la salvare: '.$stmt_upd->error.'</div>';
    }
    $stmt_upd->close();
}

/* ---------- 4. Preluăm din nou datele clericului ---------- */
$stmt = $conn->prepare(
    "SELECT c.*, ra.denumire_ro AS rang_ro 
       FROM clerici c
       JOIN rang_administrativ ra ON ra.id = c.rang_administrativ_id
      WHERE c.id = ?"
);
$stmt->bind_param('i', $cleric_id);
$stmt->execute();
$cleric = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cleric) {
    echo '<div class="container py-5"><h3>Cleric inexistent.</h3></div>';
    include 'footer.php';
    exit;
}

/* ---------- 5. Parohii & poziţii ---------- */
$sql_par  = "SELECT cp.id,
                    p.denumire       AS parohie,
                    pp.denumire_ro   AS pozitie,
                    cp.data_start,
                    cp.data_sfarsit
             FROM clerici_parohii cp
             JOIN parohii p          ON p.id  = cp.parohie_id
             JOIN pozitie_parohie pp ON pp.id = cp.pozitie_parohie_id
            WHERE cp.cleric_id = ?
            ORDER BY cp.data_start DESC";

$stmt_par = $conn->prepare($sql_par);
$stmt_par->bind_param('i', $cleric_id);
$stmt_par->execute();
$res_par  = $stmt_par->get_result();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <title>Editare cleric</title>
</head>
<body>
<div class="container-fluid py-4">
    <div class="row">
        <!-- Sidebar -->
        <aside class="col-md-3 mb-4">
            <?php include 'sidebar.php'; ?>
        </aside>

        <main class="col-md-9">
            <h1 class="mb-4">Editare cleric: <?php echo htmlspecialchars($cleric['nume'].' '.$cleric['prenume']); ?></h1>

            <?php echo $feedback; ?>

            <!-- Formular editare -->
            <form method="post" class="row g-3 mb-5">
                <div class="col-md-6">
                    <label class="form-label">Nume</label>
                    <input type="text" name="nume" class="form-control" required
                           value="<?php echo htmlspecialchars($cleric['nume']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Prenume</label>
                    <input type="text" name="prenume" class="form-control" required
                           value="<?php echo htmlspecialchars($cleric['prenume']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Rang</label>
                    <select name="rang_id" class="form-select" required>
                        <?php foreach ($ranguri as $rid=>$den): ?>
                            <option value="<?php echo $rid; ?>"
                                    <?php if ($rid == $cleric['rang_administrativ_id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($den); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Telefon</label>
                    <input type="text" name="telefon" class="form-control"
                           value="<?php echo htmlspecialchars($cleric['telefon']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?php echo htmlspecialchars($cleric['email']); ?>">
                </div>

                <div class="col-12">
                    <button type="submit" name="save_cleric" class="btn btn-primary">Salvează</button>
                    <a href="javascript:history.back()" class="btn btn-secondary">Înapoi</a>
                </div>
            </form>

            <!-- Parohii asociate -->
            <h2 class="mb-3">Parohii / Misiuni</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Parohie</th>
                            <th>Pozitie</th>
                            <th>Început</th>
                            <th>Sfârșit</th>
                            <!-- <th>Acțiuni</th>  // opțional: delete / edit -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res_par->num_rows): ?>
                            <?php while ($row = $res_par->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['parohie']); ?></td>
                                    <td><?php echo htmlspecialchars($row['pozitie']); ?></td>
                                    <td><?php echo $row['data_start'] ?: '—'; ?></td>
                                    <td><?php echo $row['data_sfarsit'] ?: '—'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">Nicio înregistrare.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php
            /* --------------  formular ADD assignment (opţional) -------------- */
            // listă parohii
            $parohii = [];
            $res_p = $conn->query("SELECT id, denumire FROM parohii ORDER BY denumire");
            while ($p = $res_p->fetch_assoc()) $parohii[$p['id']] = $p['denumire'];
            $res_p->free();

            // listă poziţii
            $pozitii = [];
            $res_po = $conn->query("SELECT id, denumire_ro FROM pozitie_parohie ORDER BY id");
            while ($po = $res_po->fetch_assoc()) $pozitii[$po['id']] = $po['denumire_ro'];
            $res_po->free();
            ?>

            <h3 class="mt-5 mb-3">Adaugă asignare</h3>
            <form method="post" class="row g-3">
                <input type="hidden" name="cleric_id" value="<?php echo $cleric_id; ?>">
                <div class="col-md-5">
                    <label class="form-label">Parohie</label>
                    <select name="parohie_id" class="form-select" required>
                        <option value="" selected disabled>— Selectează —</option>
                        <?php foreach ($parohii as $pid=>$den): ?>
                            <option value="<?php echo $pid; ?>"><?php echo htmlspecialchars($den); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Poziție</label>
                    <select name="pozitie_id" class="form-select" required>
                        <?php foreach ($pozitii as $poz_id=>$poz_den): ?>
                            <option value="<?php echo $poz_id; ?>"><?php echo htmlspecialchars($poz_den); ?></option>
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
                    <button type="submit" name="add_cpr" class="btn btn-secondary">Adaugă</button>
                </div>
            </form>
            <?php
            /* procesează adăugarea */
            if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_cpr'])) {
                $parohie_id   = (int)$_POST['parohie_id'];
                $pozitie_id   = (int)$_POST['pozitie_id'];
                $data_start   = $_POST['data_start'] ?: null;
                $data_sfarsit = $_POST['data_sfarsit'] ?: null;

                $stmt_ins = $conn->prepare(
                    "INSERT INTO clerici_parohii (cleric_id, parohie_id, pozitie_parohie_id, data_start, data_sfarsit)
                     VALUES (?,?,?,?,?)"
                );
                $stmt_ins->bind_param('iii ss', $cleric_id, $parohie_id, $pozitie_id, $data_start, $data_sfarsit);
                if ($stmt_ins->execute()) {
                    echo '<div class="alert alert-success mt-3">Asignare adăugată! <a href="?id='.$cleric_id.'">Reîncarcă</a></div>';
                } else {
                    echo '<div class="alert alert-danger mt-3">Eroare la adăugare: '.$stmt_ins->error.'</div>';
                }
                $stmt_ins->close();
            }
            ?>

        </main>
    </div>
</div>

<?php include 'footer.php'; ?>

