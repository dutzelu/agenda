<?php
include 'conectaredb.php';

/* ----------  ȘTERGERE ---------- */
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $idDel = (int)$_GET['delete'];

    $del = $conn->prepare('DELETE FROM citate WHERE id = ?');
    $del->bind_param('i', $idDel);
    $del->execute();
    $del->close();

    /* revenim la listă fără param. delete, păstrând eventualele filtre/paginare */
    $qs = $_GET;
    unset($qs['delete']);
    header('Location: citate.php'.($qs ? '?'.http_build_query($qs) : ''));
    exit;
}


/* ----------  FILTRE  ---------- */
$filtre = [
    'citat'     => trim($_GET['citat']    ?? ''),  // <-- nou
    'categorie' => $_GET['categorie']     ?? '',
    'autor'     => trim($_GET['autor']    ?? ''),
    'publicat'  => $_GET['publicat']      ?? '',
];

$where  = [];
$params = [];
$types  = '';

/* text_ro / text_en conține */
if ($filtre['citat'] !== '') {
    $where[]  = '(text_ro LIKE ? OR text_en LIKE ?)';
    $kw       = '%'.$filtre['citat'].'%';
    $params[] = $kw;
    $params[] = $kw;
    $types   .= 'ss';
}

/* categorie */
if ($filtre['categorie'] !== '') {
    $where[]  = 'categorie = ?';
    $params[] = (int)$filtre['categorie'];
    $types   .= 'i';
}

/* autor */
if ($filtre['autor'] !== '') {
    $where[]  = 'autor_ro LIKE ?';
    $params[] = '%'.$filtre['autor'].'%';
    $types   .= 's';
}

/* publicat */
if ($filtre['publicat'] !== '') {        // '1' sau '0'
    $where[]  = 'publicat = ?';
    $params[] = (int)$filtre['publicat'];
    $types   .= 'i';
}

$whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

/* ----------  PAGINAŢIE  ---------- */
$perPage = 10;
$page    = max((int)($_GET['page'] ?? 1), 1);
$offset  = ($page - 1) * $perPage;

/* nr. total rânduri  */
$stmtCnt = $conn->prepare("SELECT COUNT(*) AS cnt FROM citate $whereSql");
if ($types) $stmtCnt->bind_param($types, ...$params);
$stmtCnt->execute();
$total = (int)$stmtCnt->get_result()->fetch_assoc()['cnt'];
$stmtCnt->close();
$pages = (int)ceil(max($total, 1) / $perPage);

/* citate pentru pagina curentă */
$stmt = $conn->prepare("
    SELECT * FROM citate
    $whereSql
    ORDER BY data_adaugare DESC
    LIMIT ?, ?
");

if ($types) {
    $typesPage  = $types . 'ii';
    $paramsPage = array_merge($params, [$offset, $perPage]);
    $stmt->bind_param($typesPage, ...$paramsPage);
} else {
    $stmt->bind_param('ii', $offset, $perPage);
}
$stmt->execute();
$quotes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include 'header.php';
?> 

<body>
<div class="container">
<div class="row">
<aside class="col-md-3 mb-4"><?php include 'sidebar.php'; ?></aside>

<main class="col-md-9">
    <h1 class="mb-4">Citate</h1>

    <!-- ----------  FORMULAR FILTRE  ---------- -->
    <form class="row g-3 mb-4">
        <!-- Citat conține -->
        <div class="col-md-3">
            <label class="form-label">Citat conține</label>
            <input type="text" name="citat" class="form-control" value="<?= htmlspecialchars($filtre['citat']) ?>">
        </div>

        <!-- Categorie -->
        <div class="col-md-2">
            <label class="form-label">Categorie</label>
            <select name="categorie" class="form-select">
                <option value="">— toate —</option>
                <option value="1" <?= $filtre['categorie']==='1' ? 'selected' : '' ?>>biblic</option>
                <option value="2" <?= $filtre['categorie']==='2' ? 'selected' : '' ?>>patristic</option>
            </select>
        </div>

        <!-- Autor -->
        <div class="col-md-3">
            <label class="form-label">Autor (RO)</label>
            <input type="text" name="autor" class="form-control" value="<?= htmlspecialchars($filtre['autor']) ?>">
        </div>

        <!-- Publicat -->
        <div class="col-md-2">
            <label class="form-label">Publicat</label>
            <select name="publicat" class="form-select">
                <option value="">— ambele —</option>
                <option value="1" <?= $filtre['publicat']==='1' ? 'selected' : '' ?>>Da</option>
                <option value="0" <?= $filtre['publicat']==='0' ? 'selected' : '' ?>>Nu</option>
            </select>
        </div>

        <!-- Butoane -->
        <div class="col-md-2 align-self-end d-grid gap-1">
            <button class="btn btn-primary" type="submit">Filtrează</button>
            <a href="citate.php" class="btn btn-secondary">Resetează</a>
        </div>
    </form>

   <!-- ----------  TABEL  ---------- -->
    <table class="table table-striped align-middle" id="tbl-citate">
        <thead>
        <tr>
            <th>#</th>
            <th>Citat (RO)</th>
            <th>Autor (RO)</th>
            <th>Categorie</th>
            <th>Data</th>
            <th>Publicat</th>
            <th style="width:80px">Acțiuni</th>   <!-- nou -->
        </tr>
        </thead>
        <tbody>
        <?php foreach ($quotes as $q): ?>
            <tr class="row-link" data-href="edit-citat.php?id=<?= $q['id'] ?>">
                <td><?= $q['id'] ?></td>
                <td><?= htmlspecialchars($q['text_ro']) ?></td>
                <td><?= htmlspecialchars($q['autor_ro']) ?></td>
                <td><?= $q['categorie'] == 1 ? 'biblic' : 'patristic' ?></td>
                <td><?= date('d.m.Y', strtotime($q['data_adaugare'])) ?></td>
                <td><?= $q['publicat'] ? 'Da' : 'Nu' ?></td>

                <!-- buton ȘTERGE -->
                <td>
                    <?php
                        $link = 'citate.php?'.http_build_query(array_merge($_GET, ['delete'=>$q['id']]));
                    ?>
                    <a href="<?= $link ?>"
                    class="btn btn-sm btn-danger btn-delete"
                    title="Șterge"
                    onclick="return confirm('Sigur vrei să ștergi citatul #<?= $q['id'] ?>?');">
                        șterge
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>


    <?php if ($pages > 1): ?>
        <nav>
            <ul class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <?php
                    $qs       = $_GET;
                    $qs['page'] = $i;
                    $url      = '?' . http_build_query($qs);
                ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $url ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <a class="btn btn-success" href="adauga-citat.php">Adaugă citat</a>
</main>
</div>
</div>

<!--  ----------  JS: click pe rând  ---------- -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('#tbl-citate tbody tr').forEach(tr => {
        tr.style.cursor = 'pointer';
        tr.addEventListener('click', () => window.location = tr.dataset.href);
    });
});
 
document.addEventListener('DOMContentLoaded', () => {
    /* rând click-to-edit */
    document.querySelectorAll('#tbl-citate tbody tr').forEach(tr => {
        tr.style.cursor = 'pointer';
        tr.addEventListener('click', () => window.location = tr.dataset.href);
    });

    /* butonul de ștergere NU trebuie să trimită la editare */
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', e => e.stopPropagation());
    });
});
</script>

</body>
</html>
