<?php
include 'conectaredb.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text_ro   = trim($_POST['text_ro']   ?? '');
    $text_en   = trim($_POST['text_en']   ?? '');
    $autor_ro  = trim($_POST['autor_ro']  ?? '');
    $autor_en  = trim($_POST['autor_en']  ?? '');
    $publicat  = isset($_POST['publicat'])  ? 1 : 0;
    $categorie = intval($_POST['categorie'] ?? 1);   // ← preia selectul (default 1)

    if ($text_ro === '') {
        $eroare = 'Citatul în română este obligatoriu.';
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO citate (text_ro, text_en, autor_ro, autor_en, publicat, categorie)
             VALUES (?,?,?,?,?,?)'
        );
        $stmt->execute([$text_ro, $text_en, $autor_ro, $autor_en, $publicat, $categorie]);
        header('Location: citate.php');
        exit;
    }
}

$publicat = isset($_POST['publicat']) ? 1 : 1;
include 'header.php';
?> 

<body>
<div class="container">
    <div class="row">
        <!-- ------------  SIDEBAR  ------------ -->
        <aside class="col-md-3 mb-4">
            <?php include 'sidebar.php'; ?>
        </aside>

        <!-- ------------  CONŢINUT PRINCIPAL  ------------ -->
        <main class="col-md-9">
            <h1 class="mb-4">Citate</h1>

    <?php if (isset($eroare)): ?>
        <div class="alert alert-danger"><?= $eroare ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label class="form-label">Text (RO)*</label>
            <textarea name="text_ro" class="form-control" rows="3" required><?= htmlspecialchars($_POST['text_ro'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Text (EN)</label>
            <textarea name="text_en" class="form-control" rows="3"><?= htmlspecialchars($_POST['text_en'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Autor (RO)</label>
            <input type="text" name="autor_ro" class="form-control" value="<?= htmlspecialchars($_POST['autor_ro'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Autor (EN)</label>
            <input type="text" name="autor_en" class="form-control" value="<?= htmlspecialchars($_POST['autor_en'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Categorie</label>
            <select name="categorie" class="form-select">
                <option value="1" <?= (($_POST['categorie'] ?? '') == 1) ? 'selected' : '' ?>>biblic</option>
                <option value="2" <?= (($_POST['categorie'] ?? '') == 2) ? 'selected' : '' ?>>patristic</option>
            </select>
        </div>

        <div class="form-check mb-3">
            <input  type="checkbox"
                    name="publicat"
                    id="publicat"
                    class="form-check-input"
                    <?= $publicat ? 'checked' : '' ?> >
            <label class="form-check-label" for="publicat">Publicat</label>
        </div>

        <button type="submit" class="btn btn-success">Salvează</button>
        <a href="citate.php" class="btn btn-secondary">Lista citate</a>
    </form>
</body>
</html>
