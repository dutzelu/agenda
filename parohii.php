<?php

include 'conectaredb.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}


/* ----------  helper bind_param (array de referinţe) ---------- */
function bindParams(mysqli_stmt $stmt, string $types, array $values): void
{
    if ($types === '') return;
    $refs = [&$types];
    foreach ($values as $k => $v) $refs[] = &$values[$k];
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

/* ----------  config + input ---------- */
$per_page = 20;
$valid_types = ['all','parohie','misiune','filie','manastire','catedrala arhiepiscopala', 'paraclis arhiepiscopal', 'schit'];

$page   = (isset($_GET['page']) && ctype_digit($_GET['page'])) ? (int)$_GET['page'] : 1;
$search = trim($_GET['q'] ?? '');
$type_param = $_GET['type'] ?? 'all';
$type   = in_array($type_param, $valid_types, true) ? $type_param : 'all';

$offset = ($page - 1) * $per_page;

/* ----------  WHERE dinamic ---------- */
$where  = [];
$values = [];
$types  = '';

switch ($type) {
    case 'parohie':
        $where[] = "tp.denumire_ro = 'parohie'";
        break;
    case 'misiune':
        $where[] = "tp.denumire_ro = 'misiune'";
        break;
    case 'filie':
        $where[] = "tp.denumire_ro = 'filie'";
        break;
    case 'manastire':
        $where[] = "tp.denumire_ro = 'mănăstire'";
        break;
     case 'catedrala arhiepiscopala':
        $where[] = "tp.denumire_ro = 'catedrala arhiepiscopala'";
        break;
     case 'paraclis arhiepiscopal':
        $where[] = "tp.denumire_ro = 'paraclis arhiepiscopal'";
        break;
    case 'schit':
        $where[] = "tp.denumire_ro = 'schit'";
        break;
}

if ($search !== '') {
    $where[] = "(p.denumire LIKE CONCAT('%', ?, '%')
                 OR l.denumire_ro LIKE CONCAT('%', ?, '%')
                 OR l.denumire_en LIKE CONCAT('%', ?, '%'))";
    $types  .= 'sss';
    $values = array_fill(0,3,$search);
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ----------  total rows ---------- */
$sql_count = "SELECT COUNT(DISTINCT p.id) AS total
              FROM parohii p
              JOIN tip_parohie tp   ON tp.id = p.tip_parohie_id
              JOIN localitati l     ON l.id  = p.localitate_id
              $where_sql";

$stmt_cnt = $conn->prepare($sql_count);
bindParams($stmt_cnt, $types, $values);
$stmt_cnt->execute();
$total_rows = ($stmt_cnt->get_result()->fetch_assoc()['total']) ?? 0;
$stmt_cnt->close();

$total_pages = max(1, (int)ceil($total_rows / $per_page));

    /* ----------  număr parohii pe tip (pentru filtre) ---------- */
    $sql_type_counts = "SELECT
            COUNT(*) AS all_cnt,
            SUM(tp.denumire_ro = 'parohie') AS parohie_cnt,
            SUM(tp.denumire_ro = 'misiune') AS misiune_cnt,
            SUM(tp.denumire_ro = 'filie')   AS filie_cnt,
            SUM(tp.denumire_ro = 'mănăstire') AS manastire_cnt,
            SUM(tp.denumire_ro = 'catedrala arhiepiscopala') AS catedrala_cnt,
            SUM(tp.denumire_ro = 'paraclis arhiepiscopal') AS paraclis_cnt,
            SUM(tp.denumire_ro = 'schit')  AS schit_cnt
        FROM parohii p
        JOIN tip_parohie tp ON tp.id = p.tip_parohie_id";
    $type_counts = $conn->query($sql_type_counts)->fetch_assoc() ?: [];

/* ----------  select data ---------- */
$sql_data = "SELECT p.id,
                    p.denumire,
                    COALESCE(l.denumire_ro, l.denumire_en) AS localitate,
                    t.denumire_ro AS tara,
                    tp.denumire_ro AS tip,
                    pr.denumire_ro AS protopopiat,
                    p.website
             FROM parohii p
             JOIN localitati l       ON l.id  = p.localitate_id
             JOIN tari t             ON t.id  = p.tara_id
             JOIN tip_parohie tp     ON tp.id = p.tip_parohie_id
             LEFT JOIN protopopiate pr ON pr.id = p.protopopiat_id
             $where_sql
             ORDER BY p.denumire
             LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql_data);
$types_data = $types . 'ii';
$values_data = array_merge($values, [$per_page, $offset]);
bindParams($stmt, $types_data, $values_data);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

/* ----------  util pt. URL ---------- */
function urlWith(array $extra = []): string
{
    global $type, $search;
    $base = [
        'page' => 1,
        'type' => $type,
        'q'    => $search
    ];
    return 'parohii.php?' . http_build_query(array_merge($base, $extra));
}


/* --------------  ștergere parohie -------------- */
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];

    // 1. Ștergem clericii alocați la parohie (dacă nu ai ON DELETE CASCADE)
    $stmt = $conn->prepare("DELETE FROM clerici_parohii WHERE parohie_id = ?");
    $stmt->bind_param('i', $del_id);
    $stmt->execute();
    $stmt->close();

    // 2. Ștergem însăși parohia
    $stmt = $conn->prepare("DELETE FROM parohii WHERE id = ?");
    $stmt->bind_param('i', $del_id);
    $stmt->execute();
    $stmt->close();

    // 3. Redirect ca să evităm re-submit și să păstrăm eventualele filtre
    header('Location: ' . urlWith([]));   // sau urlWith(['search'=>$search]) etc.
    exit;
}


include "header.php";
?>
 
<body>
<div class="container ">
    <div class="row">
        <!-- -------------  SIDEBAR 25 % ------------- -->
        <aside class="col-md-3 mb-4">
            <?php include 'sidebar.php'; ?>
        </aside>

        <!-- -------------  CONŢINUT PRINCIPAL ------------- -->
        <main class="col-md-9">

            <h1 class="mb-4">Parohii</h1>

            
<!-- filtre rapide pe tip -->
<div class="mb-3">
<?php
$labels = [
    'all'       => 'Toate',
    'parohie'   => 'Parohii',
    'misiune'   => 'Misiuni',
    'filie'     => 'Filii',
    'manastire' => 'Mănăstiri',
    'catedrala arhiepiscopala' => 'Catedrale',
    'paraclis arhiepiscopal' => 'Paraclise',
    'schit'     => 'Schituri'
];
$count_map = [
    'all'       => $type_counts['all_cnt']       ?? 0,
    'parohie'   => $type_counts['parohie_cnt']   ?? 0,
    'misiune'   => $type_counts['misiune_cnt']   ?? 0,
    'filie'     => $type_counts['filie_cnt']     ?? 0,
    'manastire' => $type_counts['manastire_cnt'] ?? 0,
    'catedrala arhiepiscopala' => $type_counts['catedrala_cnt'] ?? 0,
    'paraclis arhiepiscopal' => $type_counts['paraclis_cnt'] ?? 0,
    'schit'     => $type_counts['schit_cnt']     ?? 0
];
foreach ($labels as $key=>$label):
?>
    <a class="me-1 mb-1"
       href="<?php echo urlWith(['type'=>$key,'page'=>1]); ?>">
       <?php echo $label . ' (' . ($count_map[$key] ?? 0) . ')'; ?>
    </a>
<?php endforeach; ?>
</div>


            <!-- căutare -->
            <form class="row row-cols-lg-auto g-3 align-items-center mb-4" method="get" action="parohii.php">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                <div class="col-12">
                    <input class="form-control" type="text" name="q"
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Caută parohie / localitate…">
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
                            <th>Nume</th>
                            <th>Adresa</th>
                            <th>Protopopiat</th>
                            <th class="text-center">Acțiuni</th>  <!-- NOU -->
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result->num_rows): $i=$offset+1; ?>
                        <?php while ($row=$result->fetch_assoc()): ?>
                                <tr class="clickable-row"
                                        data-href="edit-parohie.php?id=<?php echo $row['id']; ?>">
                                <td><?php echo $i++; ?></td>
                                <td><?php echo htmlspecialchars($row['tip']); ?></td>
                                <td><?php echo htmlspecialchars($row['denumire']); ?></td>
                                <td><?php echo htmlspecialchars($row['localitate']); ?></td>
                                <td><?php echo htmlspecialchars($row['tara']); ?></td>
                                <td><?php echo htmlspecialchars($row['protopopiat'] ?: '—'); ?></td>
                                <td>
                                  <?php if ($row['website']): ?>
                                     <a href="<?php echo htmlspecialchars($row['website']); ?>" target="_blank">Link</a>
                                  <?php else: echo '—'; endif; ?>
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="parohii.php?<?php echo http_build_query(array_merge($_GET, ['delete'=>$row['id']])); ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    title="Șterge"
                                    onclick="return confirm('Ești sigur că vrei să ștergi această parohie?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">Nu s-au găsit parohii.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- paginaţie -->
            <?php if ($total_pages > 1):
                $max_links = 10; $half = (int)floor($max_links/2);
                $start = max(1, $page-$half);
                $end   = min($total_pages, $start + $max_links - 1);
                if (($end-$start+1) < $max_links) $start = max(1, $end-$max_links+1);
            ?>
            <nav aria-label="Paginare" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page==1)?'disabled':''; ?>">
                        <a class="page-link" href="<?php echo urlWith(['page'=>1]); ?>">&laquo;</a>
                    </li>
                    <li class="page-item <?php echo ($page==1)?'disabled':''; ?>">
                        <a class="page-link" href="<?php echo urlWith(['page'=>max(1,$page-1)]); ?>">&lsaquo;</a>
                    </li>
                    <?php for($p=$start;$p<=$end;$p++): ?>
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

        </main>
    </div>
</div>

<?php include 'footer.php'; ?>
 
