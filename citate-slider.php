<?php
include 'conectaredb.php';

/* doar citatele marcate ca publicate */
$res    = $conn->query("SELECT text_ro, autor_ro FROM citate WHERE publicat = 1 ORDER BY data_adaugare DESC LIMIT 5");
$quotes = $res->fetch_all(MYSQLI_ASSOC);

include 'header.php';
?>
<!--  =====  STILURI  =====  -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    .quote-shell       { padding:3rem 0; }
    .brand-block       { font-family:'Playfair Display', serif; color:#3e3e3e; }
    .brand-block .init { font-size:60px; line-height:0.8; color:#b63c2b; display:inline-block; }
    .brand-block .tit  { font-size:38px; font-weight:600; }
    .brand-block .sub  { font-size:20px; font-style:italic; color:#777;
                         border-top:1px solid #ccc; padding-top:.5rem; margin-top:.5rem; }
    .carousel-item p   { font-size:26px; line-height:1.45; margin-bottom:1rem; }
    .carousel-item .author { font-size:20px; color:#b63c2b; text-align:left; font-weight:600; }
    .carousel-indicators   { justify-content:flex-start; }
    .carousel-indicators [data-bs-target] {
                        width: 18px;
                        height: 0px;
                        border-radius: 50%;
                        background: #bbb;
                        }
    .carousel-indicators .active { background:#b63c2b; }
</style>

<div class="container quote-shell">
  <div class="row g-5">
      <!--  BRANDING  -->
      <div class="col-lg-3 brand-block text-center text-lg-start ">
          <span class="init">A</span><span class="tit">dună</span><br>
          <span class="tit" style="font-weight:400;">înțelepciune!</span>
          <div class="sub">Dobândește pricepere!</div>
      </div>

      <!--  SLIDER  -->
      <div class="col-lg-9">
          <div id="quoteCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5500">

              <div class="carousel-inner">
                  <?php foreach ($quotes as $idx => $q): ?>
                      <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">
                          <p><?= nl2br(htmlspecialchars($q['text_ro'])) ?></p>
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
<?php $conn->close(); ?>
