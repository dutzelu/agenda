<?php
include 'conectaredb.php';
include 'controllers/edit-parohie-partial.php';

?>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<div class="container ">
  <div class="row gx-4 gy-3">
    <!-- Sidebar -->
    <aside class="col-md-3 mb-4">
      <?php include 'sidebar.php'; ?>
    </aside>

    <!-- Conţinut principal -->
    <main class="col-md-9 m-0">
      <?php
      /* badge colorat pt. tipul parohiei */
      $badgeMap = [1=>'primary',2=>'secondary',3=>'danger',4=>'warning',5=>'success',6=>'info'];
      $tip_id    = (int)$parohie['tip_parohie_id'];
      $tip_label = ucfirst($tipuri[$tip_id] ?? '');
      $bgClass   = 'bg-'.($badgeMap[$tip_id] ?? 'dark');
      echo '<h1 class="badge '.$bgClass.' tip_parohie mb-0">'.$tip_label.'</h1>';
      ?>
      <h2 class="mb-4 mt-0"><?= htmlspecialchars($parohie['denumire']); ?></h2>

      <hr>

      <!-- =======================  CLERICII PAROHIEI  ====================== -->
      <h2 class="h5 mb-3">Clerici care slujesc aici</h2>

      <?php if ($res_clerici->num_rows): ?>
      <div class="table-responsive mb-2">
        <table id="tabel-clerici" class="table table-sm table-striped align-middle">
          <thead class="table-dark">
            <tr>
              <th style="width:45px" class="text-center">⇅</th>
              <th>Nume</th>
              <th>Rang</th>
              <th>Pozitie</th>
              <th class="text-center" style="width:120px">Acțiuni</th>
            </tr>
          </thead>
          <tbody id="clerici-list">
            <?php while ($cl = $res_clerici->fetch_assoc()): ?>
              <tr data-cp-id="<?= $cl['id_asign'] ?>">
                <td class="handle text-center" style="cursor:move">☰</td>
                <td>
                  <a href="edit-cleric.php?id=<?= htmlspecialchars($cl['cleric_id']) ?>">
                    <?= htmlspecialchars($cl['nume'].' '.$cl['prenume']) ?>
                  </a>
                </td>
                <td><?= htmlspecialchars($cl['rang']) ?></td>
                <td><?= htmlspecialchars($cl['pozitie']) ?></td>
                <td class="text-center">
                  <form method="post" class="d-inline" onsubmit="return confirm('Ștergi această asignare?');">
                    <input type="hidden" name="del_cpr" value="<?= $cl['id_asign'] ?>">
                    <button class="btn btn-sm btn-danger"><span class="bi bi-trash"></span> Șterge</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <button id="save-order" class="btn btn-primary">Salvează ordinea</button>
      <div id="order-feedback" class="mt-2"></div>
      <?php else: ?>
        <p class="text-muted">Nu există clerici activați la această parohie.</p>
      <?php endif; ?>

    <!-- ===================  FORM ADĂUGARE CLERIC  =================== -->
    <form method="post" class="row row-cols-lg-auto g-2 align-items-end mb-4 mt-3">
    <div class="col-12">
        <label class="form-label mb-0 small">Adaugă cleric</label>
        <select name="cleric_id" class="form-select" required>
        <option value="">— Selectează —</option>
        <?php foreach ($clerici_all as $id=>$label): ?>
            <option value="<?= $id ?>"><?= htmlspecialchars($label) ?></option>
        <?php endforeach; ?>
        </select>
    </div>

    <div class="col-12">
        <label class="form-label mb-0 small">Poziție</label>
        <select name="pozitie_parohie_id" class="form-select" required>
        <?php foreach ($pozitii as $id=>$den): ?>
            <option value="<?= $id ?>"><?= htmlspecialchars($den) ?></option>
        <?php endforeach; ?>
        </select>
    </div>

    <div class="col-12">
        <button type="submit" name="add_cleric" class="btn btn-success">
        <span class="bi bi-plus-lg"></span> Adaugă
        </button>
    </div>
    </form>

      <?= $feedback ?>

      <!-- =======================  FORM EDIT PAROHIE ======================= -->
            <form method="post" class="row g-3 mt-3">

                <div class="col-12">
                    <label class="form-label">Denumire (RO)</label>
                    <input type="text" name="denumire" class="form-control" required
                           value="<?= htmlspecialchars($parohie['denumire']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Denumire (EN)</label>
                    <input type="text" name="denumire_en" class="form-control" required
                           value="<?= htmlspecialchars($parohie['denumire_en']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Țară</label>
                    <select name="tara_id" class="form-select" required>
                        <?php foreach ($tari as $id=>$den): ?>
                            <option value="<?= $id ?>" <?= $id==$parohie['tara_id']?'selected':'' ?>>
                                <?= htmlspecialchars($den) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Localitate</label>
                    <select name="localitate_id" class="form-select" required>
                        <?php foreach ($localitati as $id=>$den): ?>
                            <option value="<?= $id ?>" <?= $id==$parohie['localitate_id']?'selected':'' ?>>
                                <?= htmlspecialchars($den) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Tip parohie</label>
                    <select name="tip_id" class="form-select" required>
                        <?php foreach ($tipuri as $id=>$den): ?>
                            <option value="<?= $id ?>" <?= $id==$parohie['tip_parohie_id']?'selected':'' ?>>
                                <?= htmlspecialchars($den) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Protopopiat</label>
                    <select name="protopopiat_id" class="form-select">
                        <option value="">— fără —</option>
                        <?php foreach ($protopopiate as $id=>$den): ?>
                            <option value="<?= $id ?>" <?= $id==$parohie['protopopiat_id']?'selected':'' ?>>
                                <?= htmlspecialchars($den) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Parohie mamă</label>
                    <select name="parohie_mama_id" class="form-select">
                        <option value="">— fără —</option>
                        <?php foreach ($parohii_all as $id=>$den): ?>
                            <option value="<?= $id ?>" <?= $id==$parohie['parohie_mama_id']?'selected':'' ?>>
                                <?= htmlspecialchars($den) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Hram (RO)</label>
                    <input type="text" name="hram_ro" class="form-control"
                           value="<?= htmlspecialchars($parohie['hram_ro']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Hram (EN)</label>
                    <input type="text" name="hram_en" class="form-control"
                           value="<?= htmlspecialchars($parohie['hram_en']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Adresă</label>
                    <input type="text" name="adresa" class="form-control"
                           value="<?= htmlspecialchars($parohie['adresa']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Website</label>
                    <input type="text" name="website" class="form-control"
                           value="<?= htmlspecialchars($site) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($parohie['email']) ?>">
                </div>

                
                <div class="col-12">
                    <label class="form-label">Dată Hram (RO)</label>
                    <input type="text" name="data_hram_ro" class="form-control"
                           value="<?= htmlspecialchars($parohie['data_hram_ro']) ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Dată Hram (EN)</label>
                    <input type="text" name="data_hram_en" class="form-control"
                           value="<?= htmlspecialchars($parohie['data_hram_en']) ?>">
                </div>

                <div class="col-12">
                    <button type="submit" name="save_parohie" class="btn btn-primary">Salvează</button>
                    <a href="javascript:history.back()" class="btn btn-secondary">Înapoi</a>
                </div>

            </form>
            <!-- ================================================== -->


    </main>
  </div>
</div>


<script src="assets/js/order-clerici-parohie.js"></script>
<?php include 'footer.php'; ?>
