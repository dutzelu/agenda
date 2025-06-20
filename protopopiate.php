<?php
/* ============================================================
   protopopiate.php  –  listare protopopiate + protopopi
   Compatibil PHP 8.2 • Bootstrap 5 • DataTables
============================================================ */

include 'conectaredb.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/* ---------- interogăm protopopiatele împreună cu țara și protopopul titular ---------- */
$sql = "SELECT pr.id,
               pr.denumire_en,
               pr.denumire_ro,
               t.denumire_ro   AS tara,
               CONCAT(p.nume, ' ', p.prenume) AS protopop
        FROM   protopopiate pr
        LEFT  JOIN tari   t ON t.id = pr.tara_id
        LEFT  JOIN clerici p ON p.id = pr.protopop_id
        ORDER BY t.denumire_ro, pr.denumire_en";
$rez = $conn->query($sql);

?>
<?php include 'header.php'; ?>
<body>
<div class="container ">
    <div class="row gx-4">
        <aside class="col-md-3 mb-4"><?php include 'sidebar.php'; ?></aside>

        <main class="col-md-9">
            <div class="d-flex justify-content-between align-items-center flex-wrap flex-md-nowrap pt-3 mb-3">
                <h1 class="h2 mb-0">Protopopiate</h1>
                <a href="add-protopopiat.php" class="btn btn-primary">Adaugă protopopiat</a>
            </div>

            <?php if (isset($_GET['msg'])): ?>
                <div id="flash-msg" class="alert alert-success">
                    <?= htmlspecialchars($_GET['msg']) ?>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table id="tProtopopiate" class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Denumire EN</th>
                            <th>Denumire RO</th>
                            <th>Țara</th>
                            <th>Protopop</th>
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
                            <td><?= $row['protopop'] ? htmlspecialchars($row['protopop']) : '—' ?></td>
                            <td class="text-end">
                                <a href="edit-protopopiat.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary">Editează</a>
                                <a href="delete-protopopiat.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Sigur ștergeți protopopiatul?');">Șterge</a>
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

<!-- DataTables CDN (Bootstrap 5 integration) -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
// inițializăm DataTable + mesaj flash
$(function () {
    $('#tProtopopiate').DataTable({
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/ro.json' },
        order: [[3, 'asc'], [1, 'asc']]
    });
    setTimeout(() => $('#flash-msg').fadeOut(), 2000);
});
</script>
</body>
</html>
