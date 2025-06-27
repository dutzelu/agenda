<?php
// Conectare la baza de date
$servername = "localhost";
$username = "roarchor_claudiu";
$password = "Parola*0920";
$dbname = "roarchor_wordpress";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conectare eșuată: " . $conn->connect_error);
}

// Setarea charset-ului conexiunii la UTF-8 $conn->set_charset("utf8");
$conn->set_charset("utf8");


/*────────  DETECT COUNTRY FROM URL SLUG  ────────*/
$path     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$slug     = basename(rtrim($path, '/'));  // e.g. "parishes-england"
$slugToId = [
    'parishes-england'           => 1,
    'parishes-northern-ireland'  => 2,
    'parishes-scotland'          => 3,
    'parishes-wales'             => 5,
];
// 0 = all countries (default)
$countryId = $slugToId[$slug] ?? 0;

/* fallback to ?country=* for legacy links */
if ($countryId === 0 && isset($_GET['country']) && ctype_digit($_GET['country'])) {
    $countryId = (int)$_GET['country'];
}

/*────────  1. COUNTRY LIST  ────────*/
$countries = $conn->query("
        SELECT id, denumire_en
        FROM   tari
        ORDER  BY denumire_en
")->fetch_all(MYSQLI_ASSOC);

/*────────  2. PARISHES + LOCALITY + TYPE + DEANERY  ────────*/
$sqlPar = "
    SELECT  p.id,
            l.denumire_en AS locality,
            p.denumire_en AS name,
            COALESCE(p.hram_en, p.hram_ro) AS patron,
            p.data_hram_en,
            p.adresa,
            p.website,
            p.email,
            tp.denumire_en AS type,
            pr.protopop_id,
            pr.denumire_en AS deanery_name
    FROM    parohii p
    JOIN    localitati     l  ON l.id  = p.localitate_id
    LEFT JOIN tip_parohie  tp ON tp.id = p.tip_parohie_id
    LEFT JOIN protopopiate pr ON pr.id = p.protopopiat_id
    WHERE   (? = 0 OR p.tara_id = ? OR (? = 1 AND p.tara_id = 4))
      AND   p.tip_parohie_id NOT IN (5, 6)
    ORDER   BY l.denumire_en, p.denumire_en";
$stmPar = $conn->prepare($sqlPar);
$stmPar->bind_param('iii', $countryId, $countryId, $countryId);
$stmPar->execute();
$parishes = $stmPar->get_result()->fetch_all(MYSQLI_ASSOC);
$stmPar->close();

/*────────  NATURAL (HUMAN-FRIENDLY) SORT BY LOCALITY, THEN NAME  ────────*/
usort($parishes, function ($a, $b) {
    // ex. “London 2” se va poziționa înainte de “London 10”
    $cmp = strnatcasecmp($a['locality'], $b['locality']);
    return $cmp !== 0 ? $cmp : strnatcasecmp($a['name'], $b['name']);
});

/*────────  3. CLERGY POSITIONS VOCAB  ────────*/
$positions = [];
$res = $conn->query("SELECT id, denumire_en FROM pozitie_parohie");
while ($r = $res->fetch_assoc()) $positions[$r['id']] = $r['denumire_en'];

/*────────  4. PREPARE CLERGY STATEMENT  ────────*/
$sqlCl = "
  SELECT  c.id,
          c.nume      AS surname,
          c.prenume   AS firstname,
          c.telefon   AS phone,
          c.email,
          cp.pozitie_parohie_id,
          ra.denumire_en AS adm_rank,
          cp.sort_order
  FROM    clerici_parohii cp
  JOIN    clerici c  ON c.id = cp.cleric_id
  LEFT JOIN rang_administrativ ra ON ra.id = c.rang_administrativ_id
  WHERE   cp.parohie_id = ?
    AND   (cp.data_sfarsit IS NULL OR cp.data_sfarsit > CURDATE())
  ORDER   BY
          (cp.sort_order IS NULL),
          cp.sort_order ASC,
          cp.pozitie_parohie_id,
          surname,
          firstname;";
$stmCl = $conn->prepare($sqlCl);

?>

<div class="container">

      <!-- PARISH LIST -->
      <div id="parohii">
      <?php $idx = 1;
      foreach ($parishes as $p):
          /* clergy for the current parish */
          $stmCl->bind_param('i', $p['id']);
          $stmCl->execute();
          $clergy = $stmCl->get_result()->fetch_all(MYSQLI_ASSOC);

          /* locality (numbered list) */
          echo '<ol'.($idx>1 ? ' start="'.$idx.'"' : '').'><li><strong>'
               .htmlspecialchars($p['locality'])."</strong></li></ol>\n";

          /* "Parish:" only for standard parishes (exclude mission/chapel/outpost/skete) */
          $isSpecial = preg_match('/^(mission|chapel|outpost|skete)/iu', $p['type'] ?? '');
          echo '<p>'.($isSpecial ? '' : 'Parish: ')
               . htmlspecialchars($p['name']);
               if($p['data_hram_en'] != NULL) {
                  echo ' (' . $p['data_hram_en'] . ')';
               }
          echo "</p>\n";

          /* parish details */
          echo "<ul>\n";
          if ($p['adresa'])
              echo '<li>Address: '.htmlspecialchars($p['adresa'])."</li>\n";
          if ($p['website'])
              echo '<li>Website: <a href="'.htmlspecialchars($p['website']).'">'
                   .htmlspecialchars($p['website'])."</a></li>\n";
          if ($p['email'])
              echo '<li>Email: '.htmlspecialchars($p['email'])."</li>\n";
          echo "</ul>\n";

          /* clergy */
          foreach ($clergy as $cl): 
            /* --- Raw data --- */
            $positionRaw = $positions[$cl['pozitie_parohie_id']] ?? '';
            $rankRaw     = $cl['adm_rank'] ?? '';
            $firstName   = $cl['firstname'];
            $surname     = $cl['surname'];
            $phone       = $cl['phone']  ?? '';
            $email       = $cl['email']  ?? '';

            /* --- Title Case --- */
            $position = mb_convert_case($positionRaw, MB_CASE_TITLE, 'UTF-8');
            $rank     = mb_convert_case($rankRaw,    MB_CASE_TITLE, 'UTF-8');

            /* --- Main line --- */
            $line = '';

            // 1. Dean (Protopop)
            if ($rank === 'Dean' || $rank === 'Protopop') {
                $line = $position . ': Fr. ' . $surname . ' ' . $firstName . ' – Dean';
            }
            // 2. Deacon
            elseif ($position === 'Deacon') {
                $line = 'Deacon: ' . $firstName . ' ' . $surname;
            }
            // 3. Parish Priest / Assistant Priest
            elseif ($rank === 'Priest' || $rank === '') {
                $line = $position . ': Fr. ' . $firstName . ' ' . $surname;
            }
            // 4. Hieromonk / Protos.
            elseif ($rank === 'Hieromonk' || strpos($rank, 'Protos') === 0) {
                $line = $position . ': ' . $rank . ' ' . $firstName . ' (' . $surname . ')';
            }
            // 5. Hierodeacon
            elseif ($rank === 'Hierodeacon') {
                $line = 'Hierodeacon ' . $firstName . ' (' . $surname . ')';
            }
            // 6. Abbot
            elseif (in_array($position, ['Stareț', 'Abbot'])) {
                $line = $position . ': ' . $rank . ' ' . $firstName . ' (' . $surname . ')';
            }
            // Fallback
            else {
                $line = $position . ': ' . $firstName . ' ' . $surname;
            }

            /* --- Output --- */
            echo '<p>' . htmlspecialchars($line) . '</p>';

            if ($phone || $email) {
                echo '<ul class="mb-2">';
                if ($phone) {
                    echo '<li>Phone: ' . htmlspecialchars($phone) . '</li>';
                }
                if ($email) {
                    echo '<li>Email: <a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a></li>';
                }
                echo '</ul>';
            }
        endforeach;

          echo "<p>&nbsp;</p>\n";
          $idx++;
      endforeach;
      ?>
      </div>

</div>


<?php
$stmCl->close();
$conn->close();
