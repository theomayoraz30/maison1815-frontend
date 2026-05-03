<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/admin/includes/db.php';

$talents = $pdo->query("SELECT * FROM talents WHERE is_active=1 ORDER BY sort_order ASC, created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Maison 1815 — Talents, équipes de tournage">
  <title>TALENTS — MAISON 1815</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://pro.boxicons.com/fonts/3.0.8/basic/regular/400/boxicons.min.css?sig=44d0ffd8c35c5df4a4e5d8637b495a3c5e592d2a684120dd5cac3b87db79632b">
  <link rel="stylesheet" href="https://cdn.boxicons.com/3.0.8/fonts/brands/boxicons-brands.min.css">
</head>
<body>
  <header id="navbar" class="navbar navbar--nav-only" role="banner">
    <nav aria-label="Navigation principale">
      <ul class="navbar__links">
        <li><a href="index.php"   class="nav-link" data-scramble data-en="WORKS"   data-fr="TRAVAUX">WORKS<span class="nav-link__line" aria-hidden="true"></span></a></li>
        <li><a href="about.php"   class="nav-link" data-scramble data-en="ABOUT"   data-fr="À PROPOS">ABOUT<span class="nav-link__line" aria-hidden="true"></span></a></li>
        <li><a href="onset.html"  class="nav-link" data-scramble data-en="ON SET"  data-fr="EN PLATEAU">ON SET<span class="nav-link__line" aria-hidden="true"></span></a></li>
        <li><a href="talents.php" class="nav-link" data-scramble data-en="TALENTS" data-fr="TALENTS" aria-current="page">TALENTS<span class="nav-link__line" aria-hidden="true"></span></a></li>
      </ul>
    </nav>
    <button class="navbar__burger" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="navbar-mobile">
      <i class="bx bx-menu" aria-hidden="true"></i>
    </button>
  </header>
  <div id="navbar-mobile" class="navbar__mobile" role="dialog" aria-modal="true" aria-label="Menu mobile">
    <nav>
      <a href="index.php"   class="nav-link" data-scramble data-en="PROJECTS" data-fr="PROJETS">PROJECTS</a>
      <a href="about.php"   class="nav-link" data-scramble data-en="ABOUT"    data-fr="À PROPOS">ABOUT</a>
      <a href="onset.html"  class="nav-link" data-scramble data-en="ON SET"   data-fr="SUR PLATEAU">ON SET</a>
      <a href="talents.php" class="nav-link" data-scramble data-en="TALENTS"  data-fr="TALENTS">TALENTS</a>
    </nav>
  </div>

  <main>
    <section class="talents-section" aria-label="Talents">
      <div class="talents-header"></div>
      <ul class="talent__list">
<?php foreach ($talents as $t):
  $photo = !empty($t['photo']) ? BASE_URL.'/uploads/'.$t['photo'] : 'https://placehold.co/400x500/0a0a0a/1e1e1e?text=+';
?>
        <li class="talent__item" data-asset="<?= htmlspecialchars($photo,ENT_QUOTES,'UTF-8') ?>" data-type="image">
          <span class="talent__name"><?= htmlspecialchars($t['first_name'].' '.$t['last_name'],ENT_QUOTES,'UTF-8') ?></span>
        </li>
<?php endforeach; ?>
      </ul>
    </section>
  </main>

  <div class="talent__hover-asset" aria-hidden="true"></div>

  <footer class="footer" role="contentinfo">
    <span class="footer__brand">Copyright &copy; <a target="_blank" href="https://mdevelopment.ch">MDevelopment</a> 2026</span>
    <div class="footer__socials">
      <a href="#" class="social-icon" aria-label="Instagram" target="_blank" rel="noopener"><i class="bxl bx-instagram" aria-hidden="true"></i></a>
      <a href="#" class="social-icon" aria-label="LinkedIn"  target="_blank" rel="noopener"><i class="bxl bx-linkedin-square" aria-hidden="true"></i></a>
    </div>
  </footer>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script type="module" src="assets/js/main.js"></script>
</body>
</html>