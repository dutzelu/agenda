<?php
include 'conectaredb.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
}


/* ---------- ştergere directă dacă există ?del=id ---------- */
if (isset($_GET['del']) && ctype_digit($_GET['del'])) {
    $delId   = (int)$_GET['del'];

    $stmtDel = $conn->prepare("DELETE FROM localitati WHERE id = ?");
    $stmtDel->bind_param('i', $delId);

    if ($stmtDel->execute() && $stmtDel->affected_rows) {
        $msg = rawurlencode('Localitatea a fost ștearsă cu succes.');
    } else {
        // cel mai frecvent motiv de eşec este o parohie care referenţiază localitatea
        $msg = rawurlencode('Localitatea nu poate fi ștearsă (există parohii asociate).');
    }
    $stmtDel->close();

    header("Location: localitati.php?msg={$msg}");
    exit;
}

/* ---------- interogăm localitățile cu numele țării ---------- */
$sql = "SELECT l.id,
               l.denumire_en,
               l.denumire_ro,
               t.denumire_ro   AS tara
        FROM   localitati l
        LEFT JOIN tari t ON t.id = l.tara_id
        ORDER BY t.denumire_ro, l.denumire_en";
$rez = $conn->query($sql);

include 'header.php'; ?>

<body>
<div class="container ">
    <div class="row gx-4">
        <aside class="col-md-3 mb-4"><?php include 'sidebar.php'; ?></aside>

        <main class="col-md-9">
            <div class="d-flex justify-content-between align-items-center flex-wrap flex-md-nowrap pt-3 mb-3">
                <h1 class="h2 mb-0">Localități</h1>
                <a href="add-localitate.php" class="btn btn-primary">Adaugă localitate</a>
            </div>

            <?php if (isset($_GET['msg'])): ?>
                <div id="flash-msg" class="alert alert-success">
                    <?= htmlspecialchars($_GET['msg']) ?>
                </div>
            <?php endif; ?>


            <div class="table-responsive">
                <table id="tLocalitati" class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Denumire EN</th>
                            <th>Denumire RO</th>
                            <th>Țara</th>
                            <th class="text-end">Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1; while ($row = $rez->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['denumire_en']) ?></td>
                            <td><?= htmlspecialchars($row['denumire_ro']) ?></td>
                            <td><?= htmlspecialchars($row['tara']) ?></td>
                            <td class="text-end">
                                <a href="edit-localitate.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Editează"><i class="bi bi-pencil"></i></a>
                                <a href="localitati.php?del=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" title="Șterge" onclick="return confirm('Sigur doriți să ștergeți localitatea?');"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>

<?php include 'footer.php'; ?>

<!-- DataTables CDN (Bootstrap 5 integration) -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
// inițializăm DataTable + mesaj flash
$(function () {
    $('#tLocalitati').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/ro.json' },
        order: [[3, 'asc'], [1, 'asc']]
    });
    setTimeout(() => $('#flash-msg').fadeOut(), 2000);
});
</script>
</body>
</html>
