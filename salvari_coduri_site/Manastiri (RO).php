<?php
// Database connection
$servername = "localhost";
$username   = "roarchor_claudiu";
$password   = "Parola*0920";
$dbname     = "roarchor_wordpress";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8");

/*────────  ALL MONASTERIES & SKETES (types 5 and 6)  ────────*/
$sqlPar = "
    SELECT  p.id,
            l.denumire_ro AS locality,
            p.denumire    AS name,
            p.hram_ro AS patron,
            p.data_hram_ro,
            p.adresa      AS address,
            p.website,
            p.email,
            tp.denumire_ro AS type,
            pr.protopop_id,
            pr.denumire_ro AS deanery_name
    FROM    parohii p
    JOIN    localitati     l  ON l.id  = p.localitate_id
    LEFT JOIN tip_parohie  tp ON tp.id = p.tip_parohie_id
    LEFT JOIN protopopiate pr ON pr.id = p.protopopiat_id
    WHERE   p.tip_parohie_id IN (5, 6)
    ORDER   BY l.denumire_ro, p.denumire";

$parishes = $conn->query($sqlPar)->fetch_all(MYSQLI_ASSOC);

/*────────  CLERGY POSITION VOCAB (EN)  ────────*/
$positions = [];
$res = $conn->query("SELECT id, denumire_ro FROM pozitie_parohie");
while ($r = $res->fetch_assoc()) $positions[$r['id']] = $r['denumire_ro'];

/*────────  CLERGY BY PARISH STATEMENT  ────────*/
$sqlCl = "
  SELECT  c.id,
          c.nume       AS surname,
          c.prenume    AS firstname,
          c.telefon    AS phone,
          c.email,
          cp.pozitie_parohie_id,
          ra.denumire_ro AS adm_rank,
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
          firstname";
$stmCl = $conn->prepare($sqlCl);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monasteries & Sketes</title>
    <link rel="stylesheet" href="/wp-content/themes/twentyseventeen/assets/css/bootstrap.min.css" />
    <style>
        .title {font-weight: 600; font-size: 1.2rem;}
    </style>
</head>
<body>
<div class="container my-4">
    <div id="manastiri">
    <?php $idx = 1;
    foreach ($parishes as $p):
        /* clergy for current monastic community */
        $stmCl->bind_param('i', $p['id']);
        $stmCl->execute();
        $clergy = $stmCl->get_result()->fetch_all(MYSQLI_ASSOC);

        /* monastery title */
        echo '<ol'.($idx>1 ? ' start="'.$idx.'"' : '').'><li>'
             .htmlspecialchars($p['name'])."</li></ol>\n";

        /* Patron (Feast day) */
        if ($p['patron']) {
            echo '<p><strong>Hram:</strong> '.htmlspecialchars($p['patron']);
            if ($p['data_hram_ro']) {
                echo ' (' . $p['data_hram_ro'] . ')';
            }
            echo '</p>';
        }

        /* details */
        echo "<ul>\n";
        if ($p['address'])
            echo '<li>Adresă: '.htmlspecialchars($p['address'])."</li>\n";
        if ($p['website'])
            echo '<li>Website: <a href="'.htmlspecialchars($p['website']).'">'
                 .htmlspecialchars($p['website'])."</a></li>\n";
        if ($p['email'])
            echo '<li>Email: <a href="mailto:'.htmlspecialchars($p['email']).'">'
                 .htmlspecialchars($p['email'])."</a></li>\n";
        echo "</ul>\n";

        /* clergy */
        foreach ($clergy as $cl):
            $positionRaw = $positions[$cl['pozitie_parohie_id']] ?? '';
            $rankRaw     = $cl['adm_rank'] ?? '';
            $surname     = $cl['surname'];
            $firstName   = $cl['firstname'];
            $phone       = $cl['phone']  ?? '';
            $email       = $cl['email']  ?? '';

            /* --- Title Case --- */
            $position = mb_convert_case($positionRaw, MB_CASE_TITLE, 'UTF-8');
            $rank     = mb_convert_case($rankRaw,    MB_CASE_TITLE, 'UTF-8');

            /* --- Main line --- */
            $line = '';

            // 1. Dean (Protopop)
            if ($rank === 'Protopop') {
                $line = $position . ': PFr. ' . $surname . ' ' . $firstName . ' – Protopop';
            }
            // 2. Deacon
            elseif ($position === 'Diacon') {
                $line = 'Diacon: ' . $firstName . ' ' . $surname;
            }
            // 3. Parish Priest / Assistant Priest
            elseif ($rank === 'Preot' || $rank === '') {
                $line = $position . ': Pr. ' . $firstName . ' ' . $surname;
            }
            // 4. Hieromonk / Protos.
            elseif ($rank === 'Ieromonah' || strpos($rank, 'Protos') === 0) {
                $line = $position . ': ' . $rank . ' ' . $firstName . ' (' . $surname . ')';
            }
            // 5. Hierodeacon
            elseif ($rank === 'Ierodiacon') {
                $line = 'Ierodiacon ' . $firstName . ' (' . $surname . ')';
            }
            // 6. Abbot
            elseif (in_array($position, ['Stareț', 'Egumen'])) {
                $line = $position . ': ' . $rank . ' ' . $firstName . ' (' . $surname . ')';
            }
            // Fallback
            else {
                $line = $position . ': ' . $firstName . ' ' . $surname;
            }

            /* --- Output --- */
            echo '<p><strong>' . htmlspecialchars($line) . '</strong></p>';

            if ($phone || $email) {
                echo '<ul class="mb-2">';
                if ($phone) {
                    echo '<li>Telefon: '.htmlspecialchars($phone).'</li>';
                }
                if ($email) {
                    echo '<li>Email: <a href="mailto:'.htmlspecialchars($email).'">'.htmlspecialchars($email).'</a></li>';
                }
                echo '</ul>';
            }
        endforeach;

        echo "<p>&nbsp;</p>\n";
        $idx++;
    endforeach; ?>
   
</div>
</body>
<?php
$stmCl->close();
$conn->close();
