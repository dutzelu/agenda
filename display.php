<?php
require 'conectaredb.php';

/*────────  PARAMETRU ȚARĂ (=filtru)  ────────*/
$taraId = (isset($_GET['tara']) && ctype_digit($_GET['tara']))
          ? (int)$_GET['tara'] : 0;        // 0 = toate

/*────────  1. LISTA ȚĂRILOR  ────────*/
$tari = $conn->query("
        SELECT id, denumire_ro
        FROM   tari
        ORDER  BY denumire_ro
")->fetch_all(MYSQLI_ASSOC);

/*────────  2. PAROHII + LOCALITATE + TIP + PROTOPOP  ────────*/
$sqlPar = "
    SELECT  p.id,
            l.denumire_en AS localitate,
            p.denumire,
            COALESCE(p.hram_ro,p.hram_en) AS hram,
            p.adresa, p.website, p.email,
            tp.denumire_ro AS tip,        
            pr.protopop_id,
            pr.denumire_ro AS protopopiat_nume 
    FROM    parohii p
    JOIN    localitati     l  ON l.id  = p.localitate_id
    LEFT JOIN tip_parohie  tp ON tp.id = p.tip_parohie_id
    LEFT JOIN protopopiate pr ON pr.id = p.protopopiat_id
    WHERE   (? = 0 OR p.tara_id = ?) AND p.tip_parohie_id NOT IN (5, 6)
    ORDER   BY l.denumire_en, p.denumire";
$stmPar = $conn->prepare($sqlPar);
$stmPar->bind_param('ii', $taraId, $taraId);
$stmPar->execute();
$parohii = $stmPar->get_result()->fetch_all(MYSQLI_ASSOC);
$stmPar->close();

/*────────  3. VOCABULAR POZIȚII CLERICI  ────────*/
$pozitii = [];
$res = $conn->query("SELECT id, denumire_ro FROM pozitie_parohie");
while ($r = $res->fetch_assoc()) $pozitii[$r['id']] = $r['denumire_ro'];

/*────────  4. STATEMENT CLERICI PE PAROHIE  ────────*/
$sqlCl = "
    SELECT  c.id,
            c.nume, c.prenume,
            c.telefon, c.email,
            cp.pozitie_parohie_id,
            ra.denumire_ro AS rang_adm
    FROM    clerici_parohii cp
    JOIN    clerici             c  ON c.id = cp.cleric_id
    LEFT JOIN rang_administrativ ra ON ra.id = c.rang_administrativ_id
    WHERE   cp.parohie_id = ?
      AND  (cp.data_sfarsit IS NULL OR cp.data_sfarsit > CURDATE())
    ORDER BY cp.pozitie_parohie_id";
$stmCl = $conn->prepare($sqlCl);

/*────────  5. HEADER + LAYOUT  ────────*/
include 'header.php';
?>

<body>
<div class="container">
  <div class="row">
    <!-- ------------  SIDEBAR  ------------ -->
    <aside class="col-md-3 mb-4"><?php include 'sidebar.php'; ?></aside>

    <!-- ------------  CONŢINUT PRINCIPAL  ------------ -->
    <main class="col-md-9">

      <h1 class="mb-4">Afișare preoți și parohii</h1>

      <!-- FILTRE ȚĂRI -->
      <div class="mb-3">
        <a href="display.php"
           class="me-1 <?= $taraId==0?'fw-bold text-decoration-underline':''; ?>">
           Toate
        </a>
        <?php foreach ($tari as $t): ?>
          <a href="display.php?tara=<?= $t['id']; ?>"
             class="me-1 <?= $taraId==$t['id']?'fw-bold text-decoration-underline':''; ?>">
             <?= htmlspecialchars($t['denumire_ro']); ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- LISTA PAROHII -->
      <div id="parohii">
      <?php $idx = 1;
      foreach ($parohii as $p):

          /* clerici ai parohiei curente */
          $stmCl->bind_param('i', $p['id']);
          $stmCl->execute();
          $clerici = $stmCl->get_result()->fetch_all(MYSQLI_ASSOC);

          /* localitate (numerotată) */
          echo '<ol'.($idx>1 ? ' start="'.$idx.'"' : '').'><li><strong>'
               .htmlspecialchars($p['localitate'])."</strong></li></ol>\n";

          /* “Parohia:” doar pentru tip ≠ misiune/paraclis/filie/schit */
          $isSpecial = preg_match('/^(misiune|filie|paraclis|schit)/iu', $p['tip'] ?? '');
          echo '<p>'.($isSpecial ? '' : 'Parohia: ')
               . htmlspecialchars($p['denumire']);
          echo "</p>\n";

          /* detalii parohie */
          echo "<ul>\n";
          if ($p['adresa'])  echo '<li>Adresă: '.htmlspecialchars($p['adresa'])."</li>\n";
          if ($p['website']) echo '<li>Website: '.htmlspecialchars($p['website'])."</li>\n";
          if ($p['email'])   echo '<li>Email: '.htmlspecialchars($p['email'])."</li>\n";
          echo "</ul>\n";

          /* clerici */
          foreach ($clerici as $c):
              $functie = $pozitii[$c['pozitie_parohie_id']] ?? 'Cleric';
              $nume    = trim(($c['prenume'] ?? '').' '.($c['nume'] ?? ''));

              $isProtos   = stripos($c['rang_adm'] ?? '', 'protos') !== false;
              $isDiacon   = stripos($functie, 'diacon') !== false;
              $isProtopop = ($p['protopop_id'] && $c['id'] == $p['protopop_id']);

                if ($isProtos) {
                    $linie = 'Protos. '.htmlspecialchars($nume);
                } else {
                    $prefix = $isDiacon ? '' : 'Pr. ';
                    $linie  = htmlspecialchars($functie).': '
                            .$prefix .htmlspecialchars($nume);

                    if ($isProtopop && $p['protopopiat_nume']) {
                        $linie .= ' – protopop al '
                                .htmlspecialchars($p['protopopiat_nume']);
                    }
                }


              echo "<p>" . ucfirst($linie) . "</p>\n";

              if ($c['telefon'] || $c['email']) {
                  echo "<ul>\n";
                  if ($c['telefon'])
                      echo '<li>Telefon: '.htmlspecialchars($c['telefon'])."</li>\n";
                  if ($c['email'])
                      echo '<li>Email: '.htmlspecialchars($c['email'])."</li>\n";
                  echo "</ul>\n";
              }
          endforeach;

          echo "<p>&nbsp;</p>\n";
          $idx++;
      endforeach;
      ?>
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
