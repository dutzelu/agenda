<?php
include 'conectaredb.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: citate.php'); exit; }

/* ----------  OBŢINE CITATUL  ---------- */
$stmt = $conn->prepare('SELECT * FROM citate WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$citat = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$citat) { header('Location: citate.php'); exit; }

/* ----------  SALVEAZĂ  ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text_ro   = trim($_POST['text_ro']   ?? '');
    $text_en   = trim($_POST['text_en']   ?? '');
    $autor_ro  = trim($_POST['autor_ro']  ?? '');
    $autor_en  = trim($_POST['autor_en']  ?? '');
    $categorie = intval($_POST['categorie'] ?? 1);
    $publicat  = isset($_POST['publicat']) ? 1 : 0;

    if ($text_ro === '') {
        $eroare = 'Citatul în română este obligatoriu.';
    } else {
        $stmt = $conn->prepare('
            UPDATE citate
            SET text_ro=?, text_en=?, autor_ro=?, autor_en=?, categorie=?, publicat=?
            WHERE id = ?
        ');
        $stmt->bind_param('ssssiii', $text_ro, $text_en, $autor_ro, $autor_en, $categorie, $publicat, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: citate.php');
        exit;
    }
}

include 'header.php';
?>

<body>
<div class="container">
<div class="row">
<aside class="col-md-3 mb-4"><?php include 'sidebar.php'; ?></aside>

<main class="col-md-9">
    <h1 class="mb-4">Editează citatul #<?= $id ?></h1>

    <?php if (isset($eroare)): ?>
        <div class="alert alert-danger"><?= $eroare ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label class="form-label">Text (RO)*</label>
            <textarea name="text_ro" class="form-control" rows="3" required><?= htmlspecialchars($_POST['text_ro'] ?? $citat['text_ro']) ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Text (EN)</label>
            <textarea name="text_en" class="form-control" rows="3"><?= htmlspecialchars($_POST['text_en'] ?? $citat['text_en']) ?></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Autor (RO)</label>
            <input type="text" name="autor_ro" class="form-control" value="<?= htmlspecialchars($_POST['autor_ro'] ?? $citat['autor_ro']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Autor (EN)</label>
            <input type="text" name="autor_en" class="form-control" value="<?= htmlspecialchars($_POST['autor_en'] ?? $citat['autor_en']) ?>">
        </div>
        <div class="mb-3">
            <label class="form-label">Categorie</label>
            <select name="categorie" class="form-select">
                <option value="1" <?= (($citat['categorie'] ?? 1) == 1) ? 'selected':'' ?>>biblic</option>
                <option value="2" <?= (($citat['categorie'] ?? 1) == 2) ? 'selected':'' ?>>patristic</option>
            </select>
        </div>
        <div class="form-check mb-3">
            <input type="checkbox" name="publicat" id="publicat" class="form-check-input" <?= ($citat['publicat'] ?? 0) ? 'checked':'' ?>>
            <label class="form-check-label" for="publicat">Publicat</label>
        </div>

        <button class="btn btn-success">Salvează</button>
        <a class="btn btn-secondary" href="citate.php">Înapoi</a>
    </form>
</main>
</div>
</div>
</body>
</html>
