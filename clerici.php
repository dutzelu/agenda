<?php
include 'conectaredb.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/* ----------  helper general pt. bind_param  ---------- */
function bindParams(mysqli_stmt $stmt, string $types, array $values): void
{
    if ($types === '') {
        return;
    }
    $params = [];
    $params[] = &$types;
    foreach ($values as $k => $v) {
        $params[] = &$values[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $params);
}

/* ----------  configurare + citirea parametrilor  ---------- */
$per_page    = 20;
$valid_roles = ['all', 'protopop', 'preot', 'diacon', 'monah'];
$role_param  = $_GET['role'] ?? 'all';
$role        = in_array($role_param, $valid_roles, true) ? $role_param : 'all';
$page        = (isset($_GET['page']) && ctype_digit($_GET['page'])) ? (int)$_GET['page'] : 1;
$search      = trim($_GET['q'] ?? '');

/* ----------  ștergere cleric  ---------- */
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];

    // 1) ștergem alocările la parohii (cascadele sunt deja ON DELETE CASCADE,
    //    dar în caz că ții FK-urile fără CASCADE, ștergem manual)
    $stmt = $conn->prepare("DELETE FROM clerici_parohii WHERE cleric_id = ?");
    $stmt->bind_param('i', $del_id);
    $stmt->execute();
    $stmt->close();

    // 2) ștergem clericul
    $stmt = $conn->prepare("DELETE FROM clerici WHERE id = ?");
    $stmt->bind_param('i', $del_id);
    $stmt->execute();
    $stmt->close();

    // redirect ca să evităm re-submit + să păstrăm filtrele curente
    header('Location: ' . urlWith(['page' => $page]));
    exit;
}


$offset = ($page - 1) * $per_page;

/* ----------  filtrul WHERE dinamic  ---------- */
$where  = [];
$values = [];
$types  = '';

switch ($role) {
    case 'protopop':
        $where[] = "ra.denumire_ro = 'protopop'";
        break;
    case 'preot':
        $where[] = "ra.denumire_ro IN ('preot','ieromonah','protos.','arhimandrit')";
        break;
    case 'diacon':
        $where[] = "ra.denumire_ro = 'diacon'";
        break;
    case 'monah':
        $where[] = "ra.denumire_ro LIKE '%monah%'";
        break;
}

if ($search !== '') {
    $where[] = "(c.nume LIKE CONCAT('%', ?, '%')
                 OR c.prenume LIKE CONCAT('%', ?, '%')
                 OR pa.denumire LIKE CONCAT('%', ?, '%'))";
    $types  .= 'sss';
    $values = array_fill(0, 3, $search);
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ----------  număr total rânduri  ---------- */
$sql_count = "SELECT COUNT(DISTINCT c.id) AS total
              FROM clerici c
              JOIN rang_administrativ ra ON ra.id = c.rang_administrativ_id
              LEFT JOIN clerici_parohii cp ON cp.cleric_id = c.id
              LEFT JOIN parohii pa ON pa.id = cp.parohie_id
              $where_sql";

$stmt_cnt = $conn->prepare($sql_count);
bindParams($stmt_cnt, $types, $values);
$stmt_cnt->execute();
$total_rows = ($stmt_cnt->get_result()->fetch_assoc()['total']) ?? 0;
$stmt_cnt->close();

$total_pages = max(1, (int)ceil($total_rows / $per_page));

/* ----------  număr clerici pe rol (pentru filtre)  ---------- */
$sql_role_counts = "SELECT
        COUNT(*) AS all_cnt,
        SUM(ra.denumire_ro = 'protopop') AS protopop_cnt,
        SUM(ra.denumire_ro IN ('preot','ieromonah','protos.','arhimandrit')) AS preot_cnt,
        SUM(ra.denumire_ro = 'diacon') AS diacon_cnt,
        SUM(ra.denumire_ro LIKE '%monah%') AS monah_cnt
    FROM clerici c
    JOIN rang_administrativ ra ON ra.id = c.rang_administrativ_id";
$role_counts = $conn->query($sql_role_counts)->fetch_assoc() ?: [];

/* ----------  extragerea rândurilor curente  ---------- */
$sql_data = "SELECT c.id,
                    c.nume,
                    c.prenume,
                    ra.denumire_ro AS rang,
                    COALESCE(MAX(pp.denumire_ro), '–') AS pozitie,
                    GROUP_CONCAT(
                        DISTINCT CONCAT(
                            '[', tp.denumire_ro, '] ',
                            pa.denumire,
                            ' (<span style=\"color:#c00;\">',
                            COALESCE(lo.denumire_ro, lo.denumire_en),
                            '</span>)'
                        )
                        ORDER BY pa.denumire
                        SEPARATOR '||'
                    ) AS parohii
             FROM clerici c
             JOIN rang_administrativ ra ON ra.id = c.rang_administrativ_id
             LEFT JOIN clerici_parohii cp ON cp.cleric_id = c.id
             LEFT JOIN parohii pa ON pa.id = cp.parohie_id
             LEFT JOIN tip_parohie tp ON tp.id = pa.tip_parohie_id
             LEFT JOIN localitati lo ON lo.id = pa.localitate_id
             LEFT JOIN pozitie_parohie pp ON pp.id = cp.pozitie_parohie_id
             $where_sql
             GROUP BY c.id
             ORDER BY c.nume, c.prenume
             LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql_data);
$types_data  = $types . 'ii';
$values_data = array_merge($values, [$per_page, $offset]);
bindParams($stmt, $types_data, $values_data);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

/* ----------  util: construieste URL cu parametri la zi  ---------- */
function urlWith(array $extra = []): string
{
    global $role, $search;
    $base = [
        'page' => 1,
        'role' => $role,
        'q'    => $search
    ];
    return 'clerici.php?' . http_build_query(array_merge($base, $extra));
}

/* -------------------------------------------------------------------------
 | Export CSV pentru CLERICI (UTF‑8 + BOM)
 |--------------------------------------------------------------------------*/
if (($_GET['export'] ?? '') === 'csv' && basename($_SERVER['PHP_SELF']) === 'clerici.php') {

    // Majorăm limita pentru GROUP_CONCAT, dacă este necesar
    $conn->query('SET SESSION group_concat_max_len = 65535');

    $sqlExport = "
        SELECT  c.id,
                c.nume,
                c.prenume,
                ra.denumire_ro                                                                    AS rang,
                COALESCE(
                    GROUP_CONCAT(DISTINCT CONCAT('• ', p.denumire) ORDER BY p.denumire SEPARATOR 0x0A),
                    ''
                )                                                                                AS parohii
        FROM clerici                c
        JOIN rang_administrativ     ra ON ra.id  = c.rang_administrativ_id
        LEFT JOIN clerici_parohii   cp ON cp.cleric_id = c.id AND cp.data_sfarsit IS NULL
        LEFT JOIN parohii           p  ON p.id  = cp.parohie_id
        $where_sql
        GROUP BY c.id, c.nume, c.prenume, ra.denumire_ro
        ORDER BY c.nume, c.prenume
    ";

    $stmt = $conn->prepare($sqlExport);
    bindParams($stmt, $types, $values);
    $stmt->execute();
    $exportRes = $stmt->get_result();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=clerici_' . date('Ymd_His') . '.csv');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF‑8 BOM pentru Excel

    fputcsv($out, ['ID', 'Nume', 'Prenume', 'Rang', 'Parohii']);

    while ($r = $exportRes->fetch_assoc()) {
        fputcsv($out, [
            $r['id'],
            $r['nume'],
            $r['prenume'],
            $r['rang'],
            $r['parohii']
        ]);
    }
    exit;
}
/* ------------------------------------------------------------------------- */

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
            <h1 class="mb-4">Clerici</h1>

            <!-- butoane roluri -->
            <div class="mb-3">
                <?php
                $labels = [
                    'all'      => 'Toți',
                    'protopop' => 'Protopopi',
                    'preot'    => 'Preoți',
                    'diacon'   => 'Diaconi',
                    'monah'    => 'Monahi'
                ];
                $count_map = [
                    'all'      => $role_counts['all_cnt']      ?? 0,
                    'protopop' => $role_counts['protopop_cnt'] ?? 0,
                    'preot'    => $role_counts['preot_cnt']    ?? 0,
                    'diacon'   => $role_counts['diacon_cnt']   ?? 0,
                    'monah'    => $role_counts['monah_cnt']    ?? 0
                ];
                foreach ($labels as $key => $label): ?>
                    <a class="me-1 mb-1" href="<?php echo urlWith(['role'=>$key,'page'=>1]); ?>">
                        <?php echo $label . ' (' . ($count_map[$key] ?? 0) . ')'; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- căutare -->
            <form class="row row-cols-lg-auto g-3 align-items-center mb-4" method="get" action="clerici.php">
                <input type="hidden" name="role" value="<?php echo htmlspecialchars($role); ?>">
                <div class="col-12">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>"
                           class="form-control" placeholder="Caută nume / parohie…">
                </div>
                <div class="col-12">
                    <button class="btn btn-secondary" type="submit">Caută</button>
                    <?php if ($search !== ''): ?>
                        <a class="btn btn-link" href="<?php echo urlWith(['q'=>'','page'=>1]); ?>">Șterge filtru</a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- tabel -->
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table">
                        <tr>
                            <th>#</th>
                            <th>Rang</th>
                            <th>Nume</th>
                            <th>Prenume</th>
                            <th>Parohii</th>
                            <th>Pozitie</th>
                            <th class="text-center">Acțiuni</th>   <!-- NOU -->
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result->num_rows): $i = $offset + 1; ?>
                        <?php while ($row = $result->fetch_assoc()):
                            $parohiiHtml = '—';
                            if (!empty($row['parohii'])) {
                                $pArr = explode('||', $row['parohii']);
                                $items = array_map(fn($p) => '<li>'.$p.'</li>', $pArr);
                                $parohiiHtml = '<ul class="mb-0 ps-3">'.implode('', $items).'</ul>';
                            }
                        ?>
                            <tr class="clickable-row" data-href="edit-cleric.php?id=<?php echo $row['id']; ?>">
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['rang']); ?></td>
                                <td><?php echo htmlspecialchars($row['nume']); ?></td>
                                <td><?php echo htmlspecialchars($row['prenume']); ?></td>
                                <td><?php echo $parohiiHtml; ?></td>
                                <td><?php echo htmlspecialchars($row['pozitie']); ?></td>
                                <td class="text-nowrap text-center">
                                    <a href="clerici.php?<?php echo http_build_query(array_merge($_GET, ['delete'=>$row['id']])); ?>"
                                    class="btn btn-sm btn-outline-danger" 
                                    title="Șterge"
                                    onclick="return confirm('Ești sigur că vrei să ștergi acest cleric?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">Niciun cleric găsit.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- paginaţie -->
            <?php if ($total_pages > 1):
                $max_links = 10;
                $half = (int)floor($max_links / 2);
                $start = max(1, $page - $half);
                $end = min($total_pages, $start + $max_links - 1);
                if (($end - $start + 1) < $max_links) {
                    $start = max(1, $end - $max_links + 1);
                }
            ?>
            <nav aria-label="Paginare" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page==1)?'disabled':''; ?>">
                        <a class="page-link" href="<?php echo urlWith(['page'=>1]); ?>">&laquo;</a>
                    </li>
                    <li class="page-item <?php echo ($page==1)?'disabled':''; ?>">
                        <a class="page-link" href="<?php echo urlWith(['page'=>max(1,$page-1)]); ?>">&lsaquo;</a>
                    </li>
                    <?php for ($p=$start; $p<=$end; $p++): ?>
                        <li class="page-item <?php echo ($p==$page)?'active':''; ?>">
                            <a class="page-link" href="<?php echo urlWith(['page'=>$p]); ?>"><?php echo $p; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page==$total_pages)?'disabled':''; ?>">
                        <a class="page-link" href="<?php echo urlWith(['page'=>min($total_pages,$page+1)]); ?>">&rsaquo;</a>
                    </li>
                    <li class="page-item <?php echo ($page==$total_pages)?'disabled':''; ?>">
                        <a class="page-link" href="<?php echo urlWith(['page'=>$total_pages]); ?>">&raquo;</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
            <!--  Buton Export CSV pentru CLERICI (în zona filtre, înainte de tabel)  -->
            <a href="clerici.php?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>"
            class="btn btn-outline-primary mb-3">
                <i class="bi bi-filetype-csv"></i> Export CSV
            </a>

        </main>
    </div>
</div>

<?php include 'footer.php'; ?>
