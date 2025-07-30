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

// Număr de citate pe pagină
$perPage = 20;

// Pagina curentă
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Număr total de citate
$totalSql = "SELECT COUNT(*) FROM citate WHERE publicat = 1";
$totalRes = $conn->query($totalSql);
$totalRows = $totalRes->fetch_row()[0];
$totalPages = ceil($totalRows / $perPage);

// Interogare citate (cele mai recente primele)
$sql = "SELECT text_en, autor_en FROM citate 
        WHERE publicat = 1 
        ORDER BY data_adaugare DESC 
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $offset, $perPage);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ro">
<head>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  
</head>
<body class="bg-light">

<div class="container">

  <?php while ($row = $result->fetch_assoc()): ?>
    <div class="citat">
      <p>"<?= nl2br(htmlspecialchars($row['text_en'])) ?>"
      <span class="autor">(<?= htmlspecialchars($row['autor_en']) ?>)</span></p>
    </div>
    <hr>
  <?php endwhile; ?>

  <!-- PAGINAȚIE -->
  <nav>
    <ul class="pagination justify-content-center">
      <?php if ($page > 1): ?>
        <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>">&laquo; Înapoi</a></li>
      <?php endif; ?>

      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>

      <?php if ($page < $totalPages): ?>
        <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>">Înainte &raquo;</a></li>
      <?php endif; ?>
    </ul>
  </nav>

</div>
</body>
</html>
