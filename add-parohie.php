<?php
/* ============================================================
   add-parohie.php  –  Adaugă o parohie                      */
include 'conectaredb.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/* ---------- funcție utilitară pentru lookup ---------- */
function fetchLookup(mysqli $conn, string $table, string $idCol, string $nameCol): array {
    $out = [];
    $res = $conn->query("SELECT $idCol, $nameCol FROM $table ORDER BY $nameCol");
    while ($r = $res->fetch_assoc()) $out[$r[$idCol]] = $r[$nameCol];
    $res->free();
    return $out;
}

/* ---------- liste pentru dropdown-uri ---------- */
$tari         = fetchLookup($conn, 'tari',         'id', 'denumire_ro');
$localitati   = fetchLookup($conn, 'localitati',   'id', 'denumire_en');
$tipuri       = fetchLookup($conn, 'tip_parohie',  'id', 'denumire_ro');
$protopopiate = fetchLookup($conn, 'protopopiate', 'id', 'denumire_ro');
$parohii_all  = fetchLookup($conn, 'parohii',      'id', 'denumire');
$parohii_all  = fetchLookup($conn, 'parohii',      'id', 'denumire_en');

/* ---------- inițializare valori ---------- */
$values = [
    'tara_id'        => '',
    'localitate_id'  => '',
    'denumire'       => '',
    'denumire_en'    => '',
    'hram_ro'        => '',
    'hram_en'        => '',
    'tip_id'         => '',
    'protopopiat_id' => '',
    'parohie_mama_id'=> '',
    'adresa'         => '',
    'website'        => '',
    'email'          => ''
];
$feedback = '';

/* ---------- procesare submit ---------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_parohie'])) {

    foreach ($values as $k => $v) $values[$k] = trim($_POST[$k] ?? '');

    /* prefix website cu schemă */
    if ($values['website'] && !preg_match('#^https?://#i', $values['website'])) {
        $values['website'] = 'https://' . $values['website'];
    }

   /* ------------ INSERARE ------------ */

/* 1. pregătește variabile “curate” (NU expresii ternare inline) */
$prot_id   = $values['protopopiat_id'] !== '' ? (int)$values['protopopiat_id']  : null;
$par_mama  = $values['parohie_mama_id'] !== '' ? (int)$values['parohie_mama_id'] : null;

/* 2. pregătește statement-ul */
$sql = "INSERT INTO parohii
          (tara_id, localitate_id, denumire, denumire_en, hram_ro, hram_en,
           tip_parohie_id, protopopiat_id, parohie_mama_id,
           adresa, website, email)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";

$stmt = $conn->prepare($sql);

/* 3. leagă parametrii – TOȚI sunt variabile, nu expresii */
$stmt->bind_param(
    'iissssiiisss',
    $values['tara_id'],         // i
    $values['localitate_id'],   // i
    $values['denumire'],        // s
    $values['denumire_en'],     // s
    $values['hram_ro'],         // s
    $values['hram_en'],         // s
    $values['tip_id'],          // i
    $prot_id,                   // i  (poate fi NULL)
    $par_mama,                  // i  (poate fi NULL)
    $values['adresa'],          // s
    $values['website'],         // s
    $values['email']            // s
);

if ($stmt->execute()) {
    $feedback = '<div class="alert alert-success" id="dispari">Parohia a fost adăugată!</div>';
    foreach ($values as $k=>$v) $values[$k]='';   // goliţi formularul
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
        <!-- sidebar -->
        <aside class="col-md-3 mb-4"><?php include 'sidebar.php'; ?></aside>

        <!-- content -->
        <main class="col-md-9">
            <h1 class="mb-4">Adaugă parohie</h1>
            <?= $feedback ?>

            <form method="post" class="row g-3">

                <div class="col-12">
                    <label class="form-label">Țară</label>
                    <select name="tara_id" class="form-select" required>
                        <option value="" disabled selected>— alege țara —</option>
                        <?php foreach ($tari as $id=>$den): ?>
                            <option value="<?= $id ?>" <?= $id==$values['tara_id']?'selected':'' ?>>
                                <?= htmlspecialchars($den) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Localitate</label>
                    <select name="localitate_id" class="form-select" required>
                        <option value="" disabled selected>— alege localitatea —</option>
                        <?php foreach ($localitati as $id=>$den): ?>
                            <option value="<?= $id ?>" <?= $id==$values['localitate_id']?'selected':'' ?>>
                                <?= htmlspecialchars($den) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Denumire (RO)</label>
                    <input type="text" name="denumire" class="form-control" required
                           value="<?= htmlspecialchars($values['denumire']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Denumire (EN)</label>
                    <input type="text" name="denumire_en" class="form-control" required
                           value="<?= htmlspecialchars($values['denumire_en']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Hram (RO)</label>
                    <input type="text" name="hram_ro" class="form-control"
                           value="<?= htmlspecialchars($values['hram_ro']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Hram (EN)</label>
                    <input type="text" name="hram_en" class="form-control"
                           value="<?= htmlspecialchars($values['hram_en']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Tip parohie</label>
                    <select name="tip_id" class="form-select" required>
                        <option value="" disabled selected>— alege tipul —</option>
                        <?php foreach ($tipuri as $id=>$den): ?>
                            <option value="<?= $id ?>" <?= $id==$values['tip_id']?'selected':'' ?>>
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
                            <option value="<?= $id ?>" <?= $id==$values['protopopiat_id']?'selected':'' ?>>
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
                            <option value="<?= $id ?>" <?= $id==$values['parohie_mama_id']?'selected':'' ?>>
                                <?= htmlspecialchars($den) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Adresă</label>
                    <input type="text" name="adresa" class="form-control"
                           value="<?= htmlspecialchars($values['adresa']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Website</label>
                    <input type="text" name="website" class="form-control"
                           value="<?= htmlspecialchars($values['website']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($values['email']) ?>">
                </div>

                <div class="col-12">
                    <button class="btn btn-primary" type="submit" name="add_parohie">Adaugă</button>
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
