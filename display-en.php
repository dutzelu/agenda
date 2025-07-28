<?php
require 'conectaredb.php';

/*──── country filter ────*/
$countryId = (isset($_GET['tara']) && ctype_digit($_GET['tara']))
           ? (int)$_GET['tara'] : 0;     // 0 = all

/*──── 1. country list ────*/
$countries = $conn->query("
  SELECT id, denumire_en
  FROM   tari
  ORDER  BY denumire_en
")->fetch_all(MYSQLI_ASSOC);

/*──── 2. parishes with new EN name ────*/
$sqlPar = "
  SELECT  p.id,
          l.denumire_en                 AS locality,
          p.denumire_en                 AS en_name,      -- NEW
          COALESCE(p.hram_en,p.hram_ro) AS patron,
          p.data_hram_en, p.adresa, p.website, p.email,
          tp.denumire_en                AS type_en,
          pr.protopop_id,
          pr.denumire_en                AS deanery_en
  FROM    parohii p
  JOIN    localitati     l  ON l.id  = p.localitate_id
  LEFT JOIN tip_parohie  tp ON tp.id = p.tip_parohie_id
  LEFT JOIN protopopiate pr ON pr.id = p.protopopiat_id
  WHERE   (? = 0 OR p.tara_id = ?) AND p.tip_parohie_id NOT IN (5, 6)
  ORDER   BY l.denumire_en,
            COALESCE(p.denumire_en, p.hram_en, p.hram_ro)";
$stmPar = $conn->prepare($sqlPar);
$stmPar->bind_param('ii', $countryId, $countryId);
$stmPar->execute();
$parishes = $stmPar->get_result()->fetch_all(MYSQLI_ASSOC);
$stmPar->close();

/*──── 3. positions (EN) ────*/
$positions = [];
$resPos = $conn->query("SELECT id, denumire_en FROM pozitie_parohie");
while ($r = $resPos->fetch_assoc()) $positions[$r['id']] = $r['denumire_en'];

/*──── 4. clergy statement ────*/
$sqlCl = "
  SELECT
      c.id,
      c.nume,
      c.prenume,
      c.telefon,
      c.email,
      cp.pozitie_parohie_id,
      ra.denumire_en AS adm_rank_en,
      cp.sort_order    
  FROM    clerici_parohii cp
  JOIN    clerici              c  ON c.id = cp.cleric_id
  LEFT JOIN rang_administrativ ra ON ra.id = c.rang_administrativ_id
  WHERE   cp.parohie_id = ?
    AND  (cp.data_sfarsit IS NULL OR cp.data_sfarsit > CURDATE())
  ORDER BY
          (cp.sort_order IS NULL),
          cp.sort_order ASC, 
          cp.pozitie_parohie_id,
          c.nume,
          c.prenume;
";
$stmCl = $conn->prepare($sqlCl);

/*──── 5. HTML skeleton ────*/
include 'header.php';
?>
<body>
<div class="container">
  <div class="row">
    <aside class="col-md-3 mb-4"><?php include 'sidebar.php'; ?></aside>

    <main class="col-md-9">
      <h1 class="mb-4">Parishes &amp; Clergy</h1>

      <!-- country filters -->
      <div class="mb-3">
        <a href="display-en.php"
           class="me-1 <?= $countryId==0?'fw-bold text-decoration-underline':''; ?>">
           All
        </a>
        <?php foreach ($countries as $c): ?>
          <a href="display-en.php?tara=<?= $c['id']; ?>"
             class="me-1 <?= $countryId==$c['id']?'fw-bold text-decoration-underline':''; ?>">
             <?= htmlspecialchars($c['denumire_en']); ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- parish list -->
      <div id="parohii">
      <?php $idx = 1;
      foreach ($parishes as $p):

          /* clergy for current parish */
          $stmCl->bind_param('i', $p['id']);
          $stmCl->execute();
          $clergy = $stmCl->get_result()->fetch_all(MYSQLI_ASSOC);

          /* heading: locality */
          echo '<ol'.($idx>1?' start="'.$idx.'"':'').'><li><strong>'
               .htmlspecialchars($p['locality'])."</strong></li></ol>\n";

          /* choose EN name if available, else patron */
          $title = $p['en_name'] ?: $p['patron'];

          /* omit “Parish:” for Mission / Chapel / Filial / Skete */
          $special = preg_match('/^(mission|chapel|filial|skete)/i', $p['type_en'] ?? '');
          echo '<p>'.($special?'':'Parish: ')
               . htmlspecialchars($title);
               
                   if($p['data_hram_en'] != NULL) {
                  echo ' (' . $p['data_hram_en'] . ')';
               }      
          echo "</p>\n";



          /* contact details */
          echo "<ul>\n";
          if ($p['adresa'])  echo '<li>Address: '.htmlspecialchars($p['adresa'])."</li>\n";
          if ($p['website']) echo '<li>Website: '.htmlspecialchars($p['website'])."</li>\n";
          if ($p['email'])   echo '<li>Email: '.htmlspecialchars($p['email'])."</li>\n";
          echo "</ul>\n";

          /* clergy loop */
 foreach ($clergy as $cl): 
            /* --- Raw data --- */
            $positionRaw = $positions[$cl['pozitie_parohie_id']] ?? '';
            $rankRaw     = $cl['adm_rank_en'] ?? '';
            $firstName   = $cl['prenume'];
            $surname     = $cl['nume'];
            $phone       = $cl['telefon']  ?? '';
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
      endforeach; ?>
      </div>

    </main>
  </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
<?php
$stmCl->close();
$conn->close();
?>
