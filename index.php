<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/admin/includes/db.php';

// Load active video projects ordered by sort_order
$videoProjects = $pdo->query(
    "SELECT * FROM video_projects WHERE is_active=1 ORDER BY sort_order ASC, created_at DESC"
)->fetchAll();
$count = count($videoProjects);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Maison 1815 — Production photo &amp; vidéo">
  <title>MAISON 1815</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://pro.boxicons.com/fonts/3.0.8/basic/regular/400/boxicons.min.css?sig=44d0ffd8c35c5df4a4e5d8637b495a3c5e592d2a684120dd5cac3b87db79632b">
  <link rel="stylesheet" href="https://cdn.boxicons.com/3.0.8/fonts/brands/boxicons-brands.min.css">
</head>
<body>

  <header id="navbar" class="navbar navbar--nav-only navbar--over-hero" role="banner">
    <nav aria-label="Navigation principale">
      <ul class="navbar__links">
        <li><a href="index.php" class="nav-link" data-scramble data-en="WORKS" data-fr="TRAVAUX" aria-current="page">WORKS<span class="nav-link__line" aria-hidden="true"></span></a></li>
        <li><a href="about.php" class="nav-link" data-scramble data-en="ABOUT" data-fr="À PROPOS">ABOUT<span class="nav-link__line" aria-hidden="true"></span></a></li>
        <li><a href="onset.html" class="nav-link" data-scramble data-en="ON SET" data-fr="EN PLATEAU">ON SET<span class="nav-link__line" aria-hidden="true"></span></a></li>
        <li><a href="talents.php" class="nav-link" data-scramble data-en="TALENTS" data-fr="TALENTS">TALENTS<span class="nav-link__line" aria-hidden="true"></span></a></li>
      </ul>
    </nav>
    <button class="navbar__burger" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="navbar-mobile">
      <i class="bx bx-menu" aria-hidden="true"></i>
    </button>
  </header>

  <div id="navbar-mobile" class="navbar__mobile" role="dialog" aria-modal="true" aria-label="Menu mobile">
    <nav>
      <a href="index.php"   class="nav-link" data-scramble data-en="WORKS"   data-fr="TRAVAUX">WORKS</a>
      <a href="about.php"   class="nav-link" data-scramble data-en="ABOUT"   data-fr="À PROPOS">ABOUT</a>
      <a href="onset.html"  class="nav-link" data-scramble data-en="ON SET"  data-fr="EN PLATEAU">ON SET</a>
      <a href="talents.php" class="nav-link" data-scramble data-en="TALENTS" data-fr="TALENTS">TALENTS</a>
    </nav>
  </div>

  <section class="hero" aria-label="Hero">
    <video class="hero__video" autoplay muted loop playsinline>
      <source src="assets/media/video_old.mp4" type="video/mp4">
    </video>
    <div class="hero__split">
      <div class="hero__logo-block">
        <h1 class="hero__logo-img-wrap">
          <img src="assets/media/maison-1815-logo.png" alt="Maison 1815" class="hero__logo-img" draggable="false">
        </h1>
      </div>
      <div class="hero__desc">
        <span class="hero__desc-eyebrow">Est. 2026 — Valais/Wallis</span>
        <p class="hero__desc-text">
          Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod
          tempor incididunt ut labore et dolore magna aliqua.
        </p>
      </div>
    </div>
    <div class="hero__scroll" aria-hidden="true">
      <span class="hero__scroll-label">Ein Zuhause fur deine Ideen.</span>
      <span class="hero__scroll-line"></span>
    </div>
  </section>

  <main>
    <section class="projects" aria-label="Projets">
      <div class="projects__header">
        <span class="projects__label" data-scramble data-en="Selected projects" data-fr="Projets sélectionnés">Projets sélectionnés</span>
        <span class="projects__count"><?= str_pad($count, 2, '0', STR_PAD_LEFT) ?></span>
      </div>
      <div class="projects__grid">
<?php foreach ($videoProjects as $p): ?>
        <a href="works/video/<?= htmlspecialchars($p['slug'],ENT_QUOTES,'UTF-8') ?>" class="card-link">
        <article class="card"
                 aria-label="<?= htmlspecialchars($p['client'].' — '.$p['title'],ENT_QUOTES,'UTF-8') ?>"
                 data-clip-start="<?= (float)$p['clip_start'] ?>"
                 data-clip-end="<?= (float)$p['clip_end'] ?>">
          <div class="card__media">
            <img class="card__img" src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" alt="<?= htmlspecialchars($p['title'],ENT_QUOTES,'UTF-8') ?>" loading="lazy" draggable="false">
            <?php if (!empty($p['video_path'])): ?>
            <video class="card__video" loop muted playsinline preload="none"
                   data-src="<?= htmlspecialchars(BASE_URL.'/uploads/videos/'.$p['video_path'],ENT_QUOTES,'UTF-8') ?>">
            </video>
            <?php endif; ?>
          </div>
          <div class="card__info">
            <div class="card__field">
              <span class="card__field-label">Client</span>
              <span class="card__field-value"><?= htmlspecialchars($p['client'],ENT_QUOTES,'UTF-8') ?></span>
            </div>
            <div class="card__field">
              <span class="card__field-label">Projet</span>
              <span class="card__field-value"><?= htmlspecialchars($p['title'],ENT_QUOTES,'UTF-8') ?></span>
            </div>
            <?php if (!empty($p['director'])): ?>
            <div class="card__field">
              <span class="card__field-label">Régisseur</span>
              <span class="card__field-value"><?= htmlspecialchars($p['director'],ENT_QUOTES,'UTF-8') ?></span>
            </div>
            <?php endif; ?>
          </div>
        </article>
        </a>
<?php endforeach; ?>
      </div>
    </section>

    <section class="clients" aria-label="Clients">
      <div class="clients__header">
        <span class="clients__label" data-scramble data-en="Our customers" data-fr="Nos clients">Nos clients</span>
      </div>
      <div class="ticker" role="marquee" aria-label="Défilement des clients">
        <div class="ticker__track">
          <ul class="ticker__list" aria-hidden="false">
            <?php foreach ($videoProjects as $p): ?>
            <li class="ticker__item"><span class="ticker__name"><?= htmlspecialchars($p['client'],ENT_QUOTES,'UTF-8') ?></span></li>
            <?php endforeach; ?>
          </ul>
          <ul class="ticker__list" aria-hidden="true">
            <?php foreach ($videoProjects as $p): ?>
            <li class="ticker__item"><span class="ticker__name"><?= htmlspecialchars($p['client'],ENT_QUOTES,'UTF-8') ?></span></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </section>
  </main>

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

  <script>
  // Clip-based hover playback for video cards
  document.querySelectorAll('.card[data-clip-start]').forEach(function(card) {
    var video = card.querySelector('.card__video');
    if (!video) return;
    var start = parseFloat(card.dataset.clipStart) || 0;
    var end   = parseFloat(card.dataset.clipEnd)   || 10;
    // Lazy-load video src on first hover
    card.addEventListener('mouseenter', function() {
      if (video.dataset.src && !video.src) {
        video.src = video.dataset.src;
        video.load();
      }
      video.currentTime = start;
      video.play().catch(function(){});
    });
    card.addEventListener('mouseleave', function() {
      video.pause();
      video.currentTime = start;
    });
    video.addEventListener('timeupdate', function() {
      if (video.currentTime >= end) {
        video.currentTime = start;
      }
    });
  });
  </script>

</body>
</html>