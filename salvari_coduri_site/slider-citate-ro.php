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

/* doar citatele marcate ca publicate */
$res    = $conn->query("SELECT text_ro, autor_ro FROM citate WHERE publicat = 1 ORDER BY data_adaugare DESC LIMIT 5");
$quotes = $res->fetch_all(MYSQLI_ASSOC);


?>
 

<div class="container quote-shell">
  <div class="row">
      <!--  BRANDING  -->
      <div class="col-lg-3 brand-block text-start p-0 p-md-3">
          <span class="init">A</span><span class="tit">dună</span><br>
          <span class="tit" style="font-weight:400;">înțelepciune!</span>
          <div class="sub">Dobândește pricepere!</div>
          <div><a class="citate_ortodoxe" href="https://roarch.org.uk/ro/citate-ortodoxe/">citate ortodoxe »</a></div>
      </div>

      <!--  SLIDER  -->
      <div class="col-lg-8 mt-3 mt-md-0">
          <div id="quoteCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5500">

              <div class="carousel-inner">
                  <?php foreach ($quotes as $idx => $q): ?>
                      <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">
                          <p>„<?= nl2br(htmlspecialchars($q['text_ro'])) ?>”</p>
                          <div class="author"><?= htmlspecialchars($q['autor_ro']) ?></div>
                      </div>
                  <?php endforeach; ?>
              </div>

              <div class="carousel-indicators position-static mt-3">
                  <?php foreach ($quotes as $idx => $_): ?>
                      <button type="button"
                              data-bs-target="#quoteCarousel"
                              data-bs-slide-to="<?= $idx ?>"
                              aria-label="Slide <?= $idx+1 ?>"
                              class="<?= $idx === 0 ? 'active' : '' ?>"></button>
                  <?php endforeach; ?>
              </div>
          </div>
      </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php $conn->close(); 