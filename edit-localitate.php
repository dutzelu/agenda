<?php
include 'conectaredb.php';

//  ─────────────────────  Helper: fetchLookup rapid  ───────────────────────
if (!function_exists('fetchLookup')) {
    /**
     * Returnează un array id => etichetă dintr-un tabel
     */
    function fetchLookup(mysqli $conn, string $table, string $key, string $label): array
    {
        $out = [];
        $stmt = $conn->prepare("SELECT `$key`, `$label` FROM `$table` ORDER BY `$label`");
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $out[$row[$key]] = $row[$label];
        }
        return $out;
    }
}

//  ─────────────────────  Inițializări  ────────────────────────────────
$localitate_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_new        = ($localitate_id === 0);
$feedback      = '';

// obținem lista de țări pt. dropdown
$tarile = fetchLookup($conn, 'tari', 'id', 'denumire_ro');

// variabile model
$den_ro  = '';
$den_en  = '';
$tara_id = key($tarile); // default prima țară

//  ─────────────────────  Citire existentă (dacă edităm)  ────────────────
if (!$is_new) {
    $stmt = $conn->prepare('SELECT denumire_ro, denumire_en, tara_id FROM localitati WHERE id = ?');
    $stmt->bind_param('i', $localitate_id);
    $stmt->execute();
    $stmt->bind_result($den_ro, $den_en, $tara_id);
    if (!$stmt->fetch()) {
        // id inexistent
        header('Location: localitati.php?err=notfound');
        exit;
    }
    $stmt->close();
}

//  ─────────────────────  Salvare (insert/update)  ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_localitate'])) {
    $den_ro  = trim($_POST['denumire_ro']);
    $den_en  = trim($_POST['denumire_en']);
    $tara_id = (int)$_POST['tara_id'];

    if ($is_new) {
        $stmt = $conn->prepare('INSERT INTO localitati (denumire_ro, denumire_en, tara_id) VALUES (?,?,?)');
        $stmt->bind_param('ssi', $den_ro, $den_en, $tara_id);
        $stmt->execute();
        $new_id = $stmt->insert_id;
        $stmt->close();
        header("Location: edit-localitate.php?id=$new_id&add=1");
        exit;
    } else {
        $stmt = $conn->prepare('UPDATE localitati SET denumire_ro = ?, denumire_en = ?, tara_id = ? WHERE id = ?');
        $stmt->bind_param('ssii', $den_ro, $den_en, $tara_id, $localitate_id);
        $stmt->execute();
        $stmt->close();
        $feedback = '<div class="alert alert-success" id="dispari">Modificările au fost salvate.</div>';
    }
}

//  ─────────────────────  Ștergere  ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_localitate']) && !$is_new) {
    $stmt = $conn->prepare('DELETE FROM localitati WHERE id = ?');
    $stmt->bind_param('i', $localitate_id);
    $stmt->execute();
    $stmt->close();
    header('Location: localitati.php?del=1');
    exit;
}

if (isset($_GET['add'])) {
    $feedback = '<div class="alert alert-success" id="dispari">Localitatea a fost adăugată.</div>';
}

include __DIR__ . '/header.php';
?>



<body>
<div class="container ">
    <div class="row gx-4">
        <aside class="col-md-3 mb-4"><?php include 'sidebar.php'; ?></aside>

        <main class="col-md-9">
  <h1 class="mb-3">
    <?= $is_new ? 'Adaugă localitate' : 'Editează localitatea' ?>
  </h1>

  <?= $feedback ?>

  <form method="post" class="row g-3">
    <div class="col-12">
      <label class="form-label">Denumire (română)</label>
      <input type="text" name="denumire_ro" class="form-control" value="<?= htmlspecialchars($den_ro) ?>" required>
    </div>
    <div class="col-12">
      <label class="form-label">Denumire (engleză)</label>
      <input type="text" name="denumire_en" class="form-control" value="<?= htmlspecialchars($den_en) ?>">
    </div>
    <div class="col-12">
      <label class="form-label">Țara</label>
      <select name="tara_id" class="form-select select2" style="width:100%" required>
        <?php foreach ($tarile as $id => $den): ?>
          <option value="<?= $id ?>" <?= $id == $tara_id ? 'selected' : '' ?>><?= htmlspecialchars($den) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-12 mt-2">
      <button type="submit" name="save_localitate" class="btn btn-primary">Salvează
      </button>
      <?php if (!$is_new): ?>
        <button type="submit" name="delete_localitate" class="btn btn-danger float-end"
                onclick="return confirm('Ștergi definitiv această localitate?');">
          <span class="bi bi-trash"></span>
          Șterge
        </button>
      <?php endif; ?>
    </div>
  </form>
</div>

<!--  ───────────────────────── Assets & JS  ───────────────────────── -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link  rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // ascunde flash-ul după 2s
  setTimeout(() => {
    const el = document.getElementById('dispari');
    if (el) el.style.display = 'none';
  }, 2000);

  // pornește Select2
  $(function () {
    $('.select2').select2({
      allowClear: true,
      width: 'resolve'
    });
  });
</script>

<?php include __DIR__ . '/footer.php'; ?>
