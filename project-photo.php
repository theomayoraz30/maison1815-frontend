<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/admin/includes/db.php';

$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($_GET['slug'] ?? '')));
if ($slug === '') { http_response_code(404); ?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>404 — MAISON 1815</title><link rel="stylesheet" href="/assets/css/style.css"></head>
<body><main style="display:flex;align-items:center;justify-content:center;min-height:100vh;"><div style="text-align:center;"><h1 style="font-size:48px;color:#fff;margin:0;">404</h1><p style="color:#888;">Projet introuvable.</p><a href="/index.php" style="color:#FF5500;">← Retour</a></div></main></body></html>
<?php exit; }

$stmt = $pdo->prepare("SELECT * FROM photo_projects WHERE slug=? AND is_active=1");
$stmt->execute([$slug]);
$project = $stmt->fetch();
if (!$project) { http_response_code(404); ?>
<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>404 — MAISON 1815</title><link rel="stylesheet" href="/assets/css/style.css"></head>
<body><main style="display:flex;align-items:center;justify-content:center;min-height:100vh;"><div style="text-align:center;"><h1 style="font-size:48px;color:#fff;margin:0;">404</h1><p style="color:#888;">Projet introuvable.</p><a href="/index.php" style="color:#FF5500;">← Retour</a></div></main></body></html>
<?php exit; }

$imagesStmt = $pdo->prepare("SELECT * FROM photo_project_images WHERE project_id=? ORDER BY sort_order ASC");
$imagesStmt->execute([$project['id']]);
$images = $imagesStmt->fetchAll();

$teamsStmt = $pdo->prepare("SELECT * FROM photo_project_teams WHERE project_id=?");
$teamsStmt->execute([$project['id']]);
$team = $teamsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= htmlspecialchars($project['client'].' — '.$project['title'],ENT_QUOTES,'UTF-8') ?> — Maison 1815">
  <title><?= htmlspecialchars(strtoupper($project['client']).' — '.$project['title'],ENT_QUOTES,'UTF-8') ?> — MAISON 1815</title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="https://pro.boxicons.com/fonts/3.0.8/basic/regular/400/boxicons.min.css?sig=44d0ffd8c35c5df4a4e5d8637b495a3c5e592d2a684120dd5cac3b87db79632b">
  <link rel="stylesheet" href="https://cdn.boxicons.com/3.0.8/fonts/brands/boxicons-brands.min.css">
</head>
<body>
  <header id="navbar" class="navbar navbar--nav-only navbar--over-hero" role="banner">
    <nav aria-label="Navigation principale">
      <ul class="navbar__links">
        <li><a href="/index.php"   class="nav-link" data-scramble data-en="WORKS"   data-fr="TRAVAUX">WORKS<span class="nav-link__line" aria-hidden="true"></span></a></li>
        <li><a href="/about.php"   class="nav-link" data-scramble data-en="ABOUT"   data-fr="À PROPOS">ABOUT<span class="nav-link__line" aria-hidden="true"></span></a></li>
        <li><a href="/onset.html"  class="nav-link" data-scramble data-en="ON SET"  data-fr="EN PLATEAU">ON SET<span class="nav-link__line" aria-hidden="true"></span></a></li>
        <li><a href="/talents.php" class="nav-link" data-scramble data-en="TALENTS" data-fr="TALENTS">TALENTS<span class="nav-link__line" aria-hidden="true"></span></a></li>
      </ul>
    </nav>
    <button class="navbar__burger" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="navbar-mobile">
      <i class="bx bx-menu" aria-hidden="true"></i>
    </button>
  </header>
  <div id="navbar-mobile" class="navbar__mobile" role="dialog" aria-modal="true" aria-label="Menu mobile">
    <nav>
      <a href="/index.php"   class="nav-link" data-scramble data-en="WORKS"   data-fr="TRAVAUX">WORKS</a>
      <a href="/about.php"   class="nav-link" data-scramble data-en="ABOUT"   data-fr="À PROPOS">ABOUT</a>
      <a href="/onset.html"  class="nav-link" data-scramble data-en="ON SET"  data-fr="EN PLATEAU">ON SET</a>
      <a href="/talents.php" class="nav-link" data-scramble data-en="TALENTS" data-fr="TALENTS">TALENTS</a>
    </nav>
  </div>

  <main>
    <?php if (!empty($project['cover_photo'])): ?>
    <div class="project-photo-hero">
      <img src="<?= htmlspecialchars(BASE_URL.$project['cover_photo'],ENT_QUOTES,'UTF-8') ?>"
           alt="<?= htmlspecialchars($project['title'],ENT_QUOTES,'UTF-8') ?>" loading="eager" draggable="false">
    </div>
    <?php endif; ?>

    <?php if (!empty($team)): ?>
    <section class="project-team" aria-label="Équipe">
      <ul class="accordion">
        <li class="accordion__item reveal">
          <button class="accordion__trigger accordion__trigger--team" aria-expanded="false">
            <span class="accordion__title">TEAM</span>
          </button>
          <div class="accordion__body" role="region">
            <ul class="team-list">
              <?php foreach ($team as $m): ?>
              <li class="team-list__item">
                <span class="team-list__name"><?= htmlspecialchars($m['first_name'].' '.$m['last_name'],ENT_QUOTES,'UTF-8') ?></span>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </li>
      </ul>
    </section>
    <?php endif; ?>

    <section class="project-info reveal" aria-label="Informations du projet">
      <h1 class="project-info__title"><?= htmlspecialchars($project['client'].' — '.$project['title'],ENT_QUOTES,'UTF-8') ?></h1>
      <?php if (!empty($project['description'])): ?>
      <p class="project-info__desc"><?= htmlspecialchars($project['description'],ENT_QUOTES,'UTF-8') ?></p>
      <?php endif; ?>
    </section>

    <?php if (!empty($images)): ?>
    <section class="project-gallery" aria-label="Galerie">
      <div class="project-gallery__grid">
        <?php foreach ($images as $img): ?>
        <figure class="reveal">
          <img src="<?= htmlspecialchars(BASE_URL.$img['image_path'],ENT_QUOTES,'UTF-8') ?>"
               alt="" loading="lazy" draggable="false">
        </figure>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
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
  <script type="module" src="/assets/js/main.js"></script>
  <script type="module">
    document.addEventListener('DOMContentLoaded', () => {
      if (typeof ScrollTrigger === 'undefined') return;
      document.querySelectorAll('.reveal').forEach((el, i) => {
        gsap.fromTo(el, { opacity:0, y:20 }, { opacity:1, y:0, duration:0.8, ease:'power3.out',
          scrollTrigger: { trigger:el, start:'top 88%', toggleActions:'play none none none' }, delay: i*0.04 });
      });
    });
  </script>
</body>
</html>