# 🏗️ PROMPT — Maison 1815 Admin Dashboard (PHP Natif + MySQL + Tailwind CSS)

> **Instructions générales au modèle :**
> Tu es un orchestre de plusieurs agents spécialisés. Chaque agent possède une expertise unique et exclusive. Ils collaborent pour produire un dashboard admin **complet, sécurisé, fonctionnel et production-ready** pour le site **Maison 1815**, une société de production photo/vidéo. Aucun agent ne doit empiéter sur le domaine d'un autre. Chaque agent livre du code **prêt à l'emploi**, commenté et intégrable directement dans le projet. Aucun placeholder, aucun `// TODO`, aucun code incomplet.

---

## 🌐 Contexte du projet

**Maison 1815** est une société de production photo et vidéo haut de gamme. Le site frontend est un projet statique HTML/Tailwind/Vanilla JS avec le design system suivant :

- **Background principal** : `#000000` (noir absolu)
- **Couleur primaire/accent** : `#FF5500` (orange vif)
- **Typographie** : SF Pro Display (polices locales dans `/sf-pro-display/`)
- **Style général** : minimaliste, luxueux, editorial

Le backend à construire est un **dashboard admin en PHP natif (sans framework)**, rendu côté serveur (SSR), avec les fichiers `.php` qui remplacent les `.html` existants pour les pages publiques. Le dashboard admin est placé dans un dossier `/admin/` à la racine du projet.

**Stack technique complète :**
- Serveur : **Apache + MySQL (LAMP)**
- Langage backend : **PHP natif** (pas de Laravel, pas de Symfony)
- CSS dashboard : **Tailwind CSS** (via CDN ou CLI, cohérent avec le projet)
- Rendu : **SSR (Server-Side Rendering)** — le PHP génère le HTML complet
- Authentification : **Sessions PHP**
- Stockage médias : **local sur le serveur** (dossier `/uploads/`)
- Upload vidéo : progression en temps réel avec pourcentage via **XMLHttpRequest + PHP chunked/stream**

---

## 📁 Structure de fichiers à produire

```
/
├── admin/
│   ├── index.php                  # Dashboard home (stats générales)
│   ├── login.php                  # Page de connexion
│   ├── logout.php                 # Déconnexion
│   ├── projects/
│   │   ├── index.php              # Liste tous les projets (vidéo + photo)
│   │   ├── video/
│   │   │   ├── create.php         # Créer un projet vidéo
│   │   │   ├── edit.php           # Éditer un projet vidéo
│   │   │   ├── delete.php         # Supprimer un projet vidéo (hard delete)
│   │   │   └── reorder.php        # Endpoint AJAX pour réordonner
│   │   └── photo/
│   │       ├── create.php         # Créer un projet photo
│   │       ├── edit.php           # Éditer un projet photo
│   │       ├── delete.php         # Supprimer un projet photo (hard delete)
│   │       └── reorder.php        # Endpoint AJAX pour réordonner
│   ├── about/
│   │   └── index.php              # Gestion page About (image + équipe)
│   ├── talents/
│   │   └── index.php              # Gestion des talents
│   ├── upload/
│   │   ├── video.php              # Endpoint upload vidéo avec progression
│   │   ├── image.php              # Endpoint upload image
│   │   └── trim.php               # Endpoint pour sauvegarder timestamps trimmer
│   └── includes/
│       ├── auth.php               # Vérification session (à inclure sur chaque page protégée)
│       ├── db.php                 # Connexion PDO MySQL
│       ├── header.php             # Header HTML du dashboard (nav, sidebar)
│       ├── footer.php             # Footer HTML du dashboard
│       └── helpers.php            # Fonctions utilitaires (slug, sanitize, etc.)
├── api/
│   └── projects.php               # Endpoint JSON public pour le frontend SSR
├── uploads/
│   ├── videos/                    # Vidéos longues uploadées
│   ├── thumbnails/                # Thumbnails (si applicable)
│   ├── photos/                    # Photos des projets photo
│   ├── about/                     # Image de la page About
│   ├── team/                      # Photos de profil équipe
│   └── talents/                   # Photos des talents
├── index.php                      # Remplace index.html (SSR)
├── about.php                      # Remplace about.html (SSR)
├── works.php                      # Page works avec liste projets (SSR)
├── project-video.php              # Page projet vidéo dynamique (SSR)
├── project-photo.php              # Page projet photo dynamique (SSR)
├── talents.php                    # Page talents (SSR)
└── config.php                     # Configuration globale (DB credentials, paths)
```

---

## 🗄️ Schéma de base de données (MySQL)

Générer les fichiers SQL complets (`schema.sql`) avec les tables suivantes :

### Table `users`
```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,   -- bcrypt hash
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```
> Tout utilisateur présent dans cette table est automatiquement considéré comme administrateur. Pas de système de rôles.

### Table `video_projects`
```sql
CREATE TABLE video_projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(255) NOT NULL UNIQUE,
  client VARCHAR(255) NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  director VARCHAR(255),
  video_path VARCHAR(500),           -- chemin relatif vers la vidéo longue
  clip_start FLOAT DEFAULT 0,        -- timestamp start du clip 10s (en secondes)
  clip_end FLOAT DEFAULT 10,         -- timestamp end du clip 10s (en secondes)
  sort_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Table `video_project_teams`
```sql
CREATE TABLE video_project_teams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  FOREIGN KEY (project_id) REFERENCES video_projects(id) ON DELETE CASCADE
);
```

### Table `photo_projects`
```sql
CREATE TABLE photo_projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(255) NOT NULL UNIQUE,
  client VARCHAR(255) NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  director VARCHAR(255),
  cover_photo VARCHAR(500),          -- photo de couverture (affichée sur /works)
  sort_order INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Table `photo_project_images`
```sql
CREATE TABLE photo_project_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  image_path VARCHAR(500),
  sort_order INT DEFAULT 0,
  FOREIGN KEY (project_id) REFERENCES photo_projects(id) ON DELETE CASCADE
);
```

### Table `photo_project_teams`
```sql
CREATE TABLE photo_project_teams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  first_name VARCHAR(100),
  last_name VARCHAR(100),
  FOREIGN KEY (project_id) REFERENCES photo_projects(id) ON DELETE CASCADE
);
```

### Table `team_members` (page About)
```sql
CREATE TABLE team_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  role_fr VARCHAR(255),
  role_de VARCHAR(255),
  role_en VARCHAR(255),
  email VARCHAR(255),
  photo VARCHAR(500),
  sort_order INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Table `about_page`
```sql
CREATE TABLE about_page (
  id INT AUTO_INCREMENT PRIMARY KEY,
  image_path VARCHAR(500)
);
-- Insérer une ligne par défaut : INSERT INTO about_page (id) VALUES (1);
```

### Table `talents`
```sql
CREATE TABLE talents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  photo VARCHAR(500),
  is_active TINYINT(1) DEFAULT 1,
  sort_order INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

## 👥 AGENTS SPÉCIALISÉS

---

### 🔴 AGENT 1 — Spécialiste Base de données & Architecture PHP

**Mission :** Poser les fondations solides du projet. Tout le reste dépend de ce que cet agent produit.

**Livrables :**

1. **`schema.sql`** — Fichier SQL complet avec toutes les tables définies ci-dessus, avec commentaires, index sur `slug`, `is_active`, `sort_order`, et contraintes de clés étrangères.

2. **`config.php`** (racine) :
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'maison1815');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_PATH', __DIR__);
define('BASE_URL', 'http://localhost:8080');
define('UPLOAD_PATH', BASE_PATH . '/uploads/');
define('MAX_VIDEO_SIZE', 2 * 1024 * 1024 * 1024); // 2GB
define('ALLOWED_VIDEO_FORMATS', ['mp4', 'mov', 'avi', 'mkv', 'webm', 'wmv', 'm4v', 'flv']);
define('ALLOWED_IMAGE_FORMATS', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
```

3. **`admin/includes/db.php`** — Connexion PDO avec gestion d'erreur, charset UTF-8, mode ERRMODE_EXCEPTION.

4. **`admin/includes/helpers.php`** — Fonctions :
   - `generate_slug(string $title): string` — Génère un slug URL-safe depuis un titre (accents, espaces, majuscules gérés). Vérifie unicité en BDD et ajoute `-2`, `-3` si nécessaire.
   - `sanitize_input(string $input): string` — Nettoie les entrées utilisateur.
   - `redirect(string $url): void` — Redirect HTTP propre.
   - `delete_file(string $path): bool` — Supprime un fichier du système si il existe.
   - `format_filesize(int $bytes): string` — Formate une taille de fichier (KB/MB/GB).
   - `csrf_token(): string` — Génère et stocke un token CSRF en session.
   - `csrf_verify(string $token): bool` — Vérifie le token CSRF.

---

### 🔴 AGENT 2 — Spécialiste Sécurité & Authentification

**Mission :** Implémenter un système d'authentification PHP robuste et sécurisé. Zéro faille.

**Contexte de sécurité :**
- Authentification par **sessions PHP** (pas JWT)
- Mots de passe hashés avec **`password_hash()` / `password_verify()`** (bcrypt)
- Protection **CSRF** sur tous les formulaires POST
- Protection contre les **injections SQL** via PDO prepared statements exclusivement
- Protection contre le **brute force** : bloquer après 5 tentatives échouées pendant 15 minutes (stocké en session ou table)
- En-têtes HTTP de sécurité : `X-Frame-Options`, `X-Content-Type-Options`, `Content-Security-Policy`
- Toutes les pages admin vérifient la session via `admin/includes/auth.php`

**Livrables :**

1. **`admin/includes/auth.php`** :
```php
<?php
// À inclure en PREMIER sur chaque page admin protégée
// Vérifie la session, redirige vers login.php si non connecté
// Renouvelle l'ID de session périodiquement (session_regenerate_id)
```

2. **`admin/login.php`** — Page de connexion complète :
   - Formulaire : champ `username` + champ `password`
   - Validation côté serveur (champs non vides, username existe, password correct)
   - Limitation brute force (5 tentatives → blocage 15min)
   - Token CSRF dans le formulaire
   - Messages d'erreur génériques (ne pas révéler si c'est le username ou le password qui est faux)
   - Redirection vers `/admin/index.php` si déjà connecté
   - Design : pleine page, centré, fond noir `#000`, logo/nom "Maison 1815" en blanc, champs stylisés Tailwind avec accent orange `#FF5500`, bouton submit orange

3. **`admin/logout.php`** :
   - Détruit la session complètement (`session_destroy()`, unset cookie)
   - Redirige vers `login.php`
   - Protégé par token CSRF (formulaire POST, pas GET)

4. **`.htaccess`** (dossier `/admin/`) :
   - Bloquer l'accès direct aux fichiers `includes/`
   - Forcer HTTPS en production (avec commentaire pour activer/désactiver)
   - Désactiver le listing des répertoires

5. **`.htaccess`** (dossier `/uploads/`) :
   - Empêcher l'exécution de PHP dans ce dossier
   - Autoriser uniquement les fichiers médias (images, vidéos)

---

### 🔴 AGENT 3 — Spécialiste UI/UX Design du Dashboard

**Mission :** Concevoir l'interface visuelle du dashboard. Code HTML/Tailwind production-ready pour chaque layout.

**Design System à respecter STRICTEMENT :**
- **Background** : `#000000` ou `#0a0a0a` (noir)
- **Sidebar** : `#111111`
- **Cards/Panels** : `#1a1a1a` avec border `#2a2a2a`
- **Accent/Primary** : `#FF5500` (orange)
- **Texte principal** : `#ffffff`
- **Texte secondaire** : `#888888`
- **Success** : `#22c55e`
- **Danger** : `#ef4444`
- **Warning** : `#f59e0b`
- **Typographie** : SF Pro Display (polices locales, même chemin que le front : `/sf-pro-display/`)
- **Dashboard entièrement en anglais**

**Livrables :**

1. **`admin/includes/header.php`** — Layout principal du dashboard :
   - Sidebar fixe à gauche (largeur 260px)
   - Logo "Maison 1815" en haut de la sidebar en blanc
   - Navigation sidebar avec icônes SVG inline :
     - Dashboard (home icon)
     - Video Projects
     - Photo Projects
     - About Page
     - Talents
   - Indicateur de page active (accent orange sur l'élément actif)
   - Username de l'admin connecté en bas de sidebar
   - Lien Logout (POST CSRF, pas GET)
   - Zone de contenu principale à droite avec padding
   - Breadcrumb en haut de la zone de contenu

2. **`admin/includes/footer.php`** — Fermeture des balises HTML.

3. **Composants UI réutilisables** (définis en PHP includes ou classes Tailwind documentées) :
   - **Bouton primary** : fond `#FF5500`, texte blanc, hover légèrement plus sombre
   - **Bouton secondary** : bordure `#2a2a2a`, texte blanc, hover fond `#1a1a1a`
   - **Bouton danger** : fond `#ef4444`, texte blanc
   - **Input text** : fond `#111111`, bordure `#2a2a2a`, texte blanc, focus bordure `#FF5500`
   - **Textarea** : même style que input
   - **Select** : même style, flèche personnalisée
   - **Toggle switch** (actif/inactif) : orange si actif, gris si inactif
   - **Badge** : "Active" (vert), "Inactive" (gris), "Video" (orange), "Photo" (bleu)
   - **Cards de stats** sur la home : fond `#1a1a1a`, nombre large en blanc, label en gris
   - **Table listing** : fond `#1a1a1a`, header `#111111`, lignes alternées très légèrement, hover sur ligne
   - **Alert/Flash messages** : succès vert, erreur rouge, warning jaune — en haut de page, auto-dismiss après 4s en JS

4. **`admin/index.php`** (home dashboard) :
   - 4 stats cards : "Video Projects", "Photo Projects", "Talents", "Team Members"
   - Tableau des 5 derniers projets ajoutés (vidéo + photo mélangés, triés par date)
   - Design épuré, typographie large

---

### 🔴 AGENT 4 — Spécialiste Upload & Gestion des Médias

**Mission :** Implémenter tout ce qui concerne les uploads de fichiers. Performance, fiabilité, sécurité des fichiers.

**Spécifications techniques critiques :**

**Upload vidéo avec progression en temps réel :**
- Utiliser **XMLHttpRequest** (pas fetch) pour pouvoir tracker `xhr.upload.onprogress`
- Afficher une barre de progression animée avec le pourcentage exact
- Afficher la vitesse d'upload (MB/s) et le temps restant estimé
- Côté PHP : configurer `upload_max_filesize = 2G`, `post_max_size = 2G`, `max_execution_time = 600` (via `.htaccess` ou `ini_set()`)
- Formats vidéo acceptés : `mp4`, `mov`, `avi`, `mkv`, `webm`, `wmv`, `m4v`, `flv`
- Taille max : 2GB
- Nommage du fichier : `[timestamp]_[slug-du-projet].[ext]` pour éviter les collisions
- Stocker dans `/uploads/videos/`

**Upload images :**
- Formats acceptés : `jpg`, `jpeg`, `png`, `webp`, `gif`
- Taille max images : 10MB
- Validation MIME type côté serveur (pas seulement l'extension)
- Redimensionnement optionnel via GD si image > 3000px de large
- Nommage : `[timestamp]_[nom-sanitisé].[ext]`

**Suppression de fichiers (hard delete) :**
- Lors de la suppression d'un projet, supprimer TOUS les fichiers associés :
  - Pour un projet vidéo : la vidéo longue (`video_path`)
  - Pour un projet photo : la photo de couverture + toutes les images de la galerie
  - Pour un talent : sa photo
  - Pour un membre de l'équipe : sa photo de profil

**Livrables :**

1. **`admin/upload/video.php`** — Endpoint POST pour upload vidéo :
   - Validation : format, taille, MIME type
   - Déplacement vers `/uploads/videos/`
   - Retourne JSON : `{"success": true, "path": "/uploads/videos/xxx.mp4", "filename": "xxx.mp4"}`
   - En cas d'erreur : `{"success": false, "error": "message"}`

2. **`admin/upload/image.php`** — Endpoint POST pour upload image :
   - Validation stricte
   - Retourne JSON avec chemin du fichier

3. **JavaScript côté client** (`admin/assets/js/upload.js`) :
```javascript
// Classe UploadManager avec :
// - initVideoUpload(inputElement, progressBarElement, onSuccess)
// - initImageUpload(inputElement, previewElement, onSuccess)
// - Barre de progression avec animation CSS
// - Affichage : "45% — 2.3 MB/s — ~12s remaining"
// - État "Uploading...", "Processing...", "Done ✓", "Error ✗"
```

4. **`admin/upload/trim.php`** — Endpoint POST pour sauvegarder les timestamps du trimmer :
   - Reçoit : `project_id`, `clip_start`, `clip_end`, `type` (video)
   - Valide que `clip_end - clip_start <= 15` secondes (tolérance de 5s)
   - Met à jour `video_projects.clip_start` et `video_projects.clip_end`
   - Retourne JSON succès/erreur

---

### 🔴 AGENT 5 — Spécialiste Trimmer Vidéo

**Mission :** Construire le composant trimmer vidéo intégré dans le dashboard admin. UX pro, précis, fonctionnel.

**Comportement attendu :**
- S'affiche dans `admin/projects/video/create.php` et `edit.php` APRÈS que la vidéo ait été uploadée
- Permet de sélectionner un segment de **10 secondes maximum** (tolérance jusqu'à 15s)
- Les timestamps sélectionnés (`clip_start`, `clip_end`) sont stockés en BDD
- Sur la page publique `index.php`, au hover sur la card d'un projet vidéo, la vidéo se lit uniquement entre `clip_start` et `clip_end` (géré en JS vanilla)

**Fonctionnalités du trimmer :**

1. **Lecteur vidéo** intégré avec la vidéo uploadée
2. **Timeline visuelle** : barre horizontale représentant la durée totale de la vidéo
3. **Handles draggables** : poignée gauche (start) et poignée droite (end), oranges (`#FF5500`)
4. **Zone sélectionnée** : surlignage orange semi-transparent entre les deux handles
5. **Affichage temps** : `00:00.000 → 00:10.000 (10.0s)` mis à jour en temps réel
6. **Bouton Play** : joue uniquement la sélection en boucle pour prévisualiser
7. **Inputs numériques** : champs `Start` et `End` en secondes, éditables manuellement
8. **Frame stepping** : boutons `◀` et `▶` pour avancer/reculer image par image (1/30s)
9. **Snap** : le handle End se verrouille automatiquement à Start + 10s si on dépasse
10. **Waveform audio** (optionnel mais souhaité) : représentation visuelle de l'audio via Web Audio API

**Livrables :**

1. **`admin/assets/js/trimmer.js`** — Classe JS complète `VideoTrimmer` :
   - Constructeur : `new VideoTrimmer(videoElement, containerElement, options)`
   - Méthode `getTimestamps()` : retourne `{start: float, end: float}`
   - Méthode `setTimestamps(start, end)` : initialise les handles (pour le mode édition)
   - Méthode `onSave(callback)` : callback appelé avec les timestamps
   - Drag & drop natif (pas de librairie externe)

2. **Template HTML du trimmer** (intégré dans `create.php` / `edit.php`) :
```html
<!-- Inséré après l'upload vidéo réussi -->
<div id="trimmer-container" class="hidden mt-8">
  <h3 class="text-white font-semibold mb-4">Select 10-second clip for homepage preview</h3>
  <video id="trim-video" class="w-full rounded" controls></video>
  <div id="trim-timeline" class="relative mt-4 h-12 bg-[#1a1a1a] rounded cursor-pointer">
    <!-- Handles et zone sélectionnée générés par JS -->
  </div>
  <div class="flex justify-between mt-2 text-sm text-[#888888]">
    <span id="trim-time-display">00:00.000 → 00:10.000 (10.0s)</span>
    <button id="trim-preview-btn" class="text-[#FF5500]">▶ Preview clip</button>
  </div>
  <input type="hidden" name="clip_start" id="clip_start_input">
  <input type="hidden" name="clip_end" id="clip_end_input">
</div>
```

---

### 🔴 AGENT 6 — Spécialiste CRUD Projets Vidéo

**Mission :** Implémenter le CRUD complet des projets vidéo avec toutes leurs relations.

**Règles métier :**
- Slug auto-généré depuis le titre, affiché en lecture seule dans le formulaire (jamais modifiable manuellement)
- `sort_order` détermine l'ordre d'affichage sur le site public
- Un projet peut être actif (`is_active = 1`) ou inactif (`is_active = 0`) — seuls les actifs apparaissent sur le site public
- Teams : liste dynamique de membres (prénom + nom), ajout/suppression dynamique en JS sans rechargement de page
- Hard delete : supprime le projet, ses teams (CASCADE FK), et le fichier vidéo sur le disque

**Livrables :**

1. **`admin/projects/video/index.php`** (liste des projets vidéo) :
   - Tableau avec colonnes : Thumbnail/Aperçu, Title, Client, Director, Status (badge Active/Inactive), Order, Actions
   - Actions par ligne : Edit (icône crayon), Toggle Active/Inactive (toggle switch), Delete (icône poubelle rouge + confirmation modal)
   - Section "Drag & Drop Reorder" : liste réorganisable, bouton "Save Order" qui fait un appel AJAX vers `reorder.php`
   - Bouton "Add New Video Project" en haut à droite (orange)

2. **`admin/projects/video/create.php`** :
   - Champ : **Client** (text input)
   - Champ : **Project Title** (text input) → génère le slug en live via JS et l'affiche en dessous en gris : `Slug: nike-campaign-2025`
   - Champ : **Description** (textarea)
   - Champ : **Director** (text input)
   - Section **Team Members** : liste dynamique avec bouton "+ Add Member" qui ajoute une ligne (First Name + Last Name) et bouton "✕" pour retirer
   - Section **Upload Video** : zone de drop ou bouton, avec barre de progression XHR (Agent 4)
   - Section **Video Trimmer** : apparaît après upload réussi (Agent 5)
   - Champ **Status** : toggle Active/Inactive (default: Active)
   - Bouton **Save Project** (orange) + bouton **Cancel** (secondary)
   - Validation côté serveur : titre, client, slug unique obligatoires

3. **`admin/projects/video/edit.php`** :
   - Identique à `create.php` mais pré-rempli avec les données existantes
   - La vidéo actuelle est affichée avec son nom de fichier + option de remplacement
   - Le trimmer est initialisé avec les timestamps existants (`setTimestamps(clip_start, clip_end)`)
   - Slug affiché en lecture seule (ne change pas si on modifie le titre d'un projet existant)

4. **`admin/projects/video/delete.php`** (POST uniquement) :
   - Vérifie CSRF token
   - Récupère le projet, supprime le fichier vidéo du disque
   - Supprime l'entrée BDD (les teams se suppriment en CASCADE)
   - Redirige vers la liste avec message flash "Project deleted successfully"

5. **`admin/projects/video/reorder.php`** (AJAX POST) :
   - Reçoit tableau JSON d'IDs dans le nouvel ordre
   - Met à jour `sort_order` pour chaque projet
   - Retourne JSON `{"success": true}`

---

### 🔴 AGENT 7 — Spécialiste CRUD Projets Photo

**Mission :** Identique à l'Agent 6 mais pour les projets photo. Gestion de la galerie multi-images.

**Spécificités projets photo :**
- Photo de couverture : 1 seule image, affichée sur la page `/works` dans la card du projet
- Galerie : N images affichées uniquement sur la page `/works/photo/slug-du-projet`
- Upload multiple images pour la galerie (sélection multiple dans l'input file)
- Réordonnancement des images de la galerie (drag & drop dans le formulaire)
- Hard delete : supprime la cover photo + toutes les images de la galerie du disque

**Livrables :**

1. **`admin/projects/photo/index.php`** — Même structure que la liste vidéo, avec aperçu de la cover photo

2. **`admin/projects/photo/create.php`** :
   - Champs identiques aux projets vidéo (Client, Title, Description, Director, Team)
   - Section **Cover Photo** : upload image unique avec prévisualisation instantanée
   - Section **Gallery Photos** : upload multiple, prévisualisation des thumbnails, réordonnancement drag & drop avant soumission
   - Status toggle

3. **`admin/projects/photo/edit.php`** :
   - Pré-rempli avec données existantes
   - Cover photo actuelle affichée + option de remplacement
   - Galerie existante avec thumbnails réordonnables + bouton "✕" sur chaque pour supprimer (AJAX)
   - Possibilité d'ajouter de nouvelles images à la galerie existante

4. **`admin/projects/photo/delete.php`** — Hard delete, supprime tous les fichiers du projet

5. **`admin/projects/photo/reorder.php`** — Réordonner les projets photo

---

### 🔴 AGENT 8 — Spécialiste Page About & Gestion Équipe

**Mission :** Gestion de la page About : image principale + gestion des membres de l'équipe avec support i18n des rôles.

**Règles métier :**
- Un seul enregistrement dans `about_page` (id = 1), toujours mis à jour (jamais recréé)
- Les membres de l'équipe peuvent être réordonnés (drag & drop)
- Le champ "Role" doit être saisi en 3 langues : FR, DE, EN (3 inputs séparés)
- Sur le site public, la langue est détectée (via paramètre URL `?lang=fr` ou header HTTP Accept-Language) et le bon rôle est affiché

**Livrables :**

1. **`admin/about/index.php`** :

   **Section "About Page Image" :**
   - Affiche l'image actuelle (si elle existe)
   - Upload nouvelle image avec prévisualisation
   - Bouton "Save Image"

   **Section "Team Members" :**
   - Liste des membres existants (cards) avec prévisualisation photo de profil, nom, rôle EN
   - Bouton "+ Add Team Member" ouvre un formulaire inline ou modal
   - Chaque membre : Photo de profil, First Name, Last Name, Role FR + Role DE + Role EN (3 inputs), Email, boutons Edit/Delete
   - Réordonnancement drag & drop
   - Bouton "Save Order"

2. **Endpoint AJAX** (dans `about/index.php` ou fichier séparé) pour :
   - Ajouter un membre (POST)
   - Modifier un membre (POST)
   - Supprimer un membre + son fichier photo (POST)
   - Réordonner (POST)

---

### 🔴 AGENT 9 — Spécialiste Page Talents

**Mission :** Gestion de la page Talents avec statut actif/inactif.

**Règles métier :**
- Un talent a : Prénom, Nom, Photo, Statut (actif/inactif)
- Sur le site public `talents.php`, seuls les talents actifs sont affichés
- Réordonnancement possible

**Livrables :**

1. **`admin/talents/index.php`** :
   - Tableau/grille des talents avec photo de profil (miniature), Nom Prénom, badge Active/Inactive, actions
   - Bouton "+ Add Talent"
   - Formulaire d'ajout : First Name, Last Name, Photo upload avec prévisualisation, toggle Active
   - Edit inline (ou page dédiée) : même champs, photo actuelle affichée
   - Delete : supprime le talent + son fichier photo du disque, confirmation modal
   - Toggle rapide actif/inactif directement depuis la liste (AJAX)

---

### 🔴 AGENT 10 — Spécialiste Pages Publiques SSR (PHP)

**Mission :** Convertir les pages `.html` statiques en pages `.php` dynamiques qui lisent la base de données.

**Règles :**
- Chaque page `.php` doit reproduire EXACTEMENT le même HTML/CSS/JS que le `.html` original
- Seule la partie dynamique (liste de projets, données, etc.) est générée par PHP
- Tous les assets (CSS, JS, fonts) continuent à fonctionner avec les mêmes chemins relatifs
- Seuls les projets avec `is_active = 1` sont affichés
- Les projets sont triés par `sort_order ASC`

**Livrables :**

1. **`index.php`** (page d'accueil) :
   - Charge les projets vidéo actifs depuis la BDD (`is_active = 1`, `ORDER BY sort_order ASC`)
   - Pour chaque projet vidéo, génère la card avec :
     - L'élément `<video>` avec `src` vers la vidéo longue
     - Les attributs `data-clip-start` et `data-clip-end` sur la card
     - Pas de thumbnail photo — c'est la vidéo elle-même
   - Le JS existant (`cards.js`) est adapté pour :
     - Au **mouseenter** sur une card vidéo : `video.currentTime = data-clip-start`, `video.play()`
     - Au **mouseleave** : `video.pause()`, `video.currentTime = data-clip-start`
     - Pendant la lecture : si `video.currentTime >= data-clip-end`, `video.currentTime = data-clip-start` (loop sur le clip)

2. **`works.php`** (page works — liste projets) :
   - Charge projets vidéo + photo actifs
   - Pour chaque projet vidéo : card avec la vidéo (hover play comportement identique à index.php)
   - Pour chaque projet photo : card avec la cover photo
   - Liens vers `/works/video/[slug]` et `/works/photo/[slug]`

3. **`project-video.php`** :
   - Récupère le slug depuis l'URL (`$_GET['slug']` ou via `.htaccess` rewrite)
   - Charge le projet + ses teams depuis la BDD
   - Affiche la vidéo complète, les infos du projet, l'équipe
   - 404 si projet inexistant ou inactif

4. **`project-photo.php`** :
   - Charge le projet photo + cover + toutes les images de la galerie
   - Affiche la galerie complète
   - 404 si inexistant ou inactif

5. **`about.php`** :
   - Charge l'image de la page About
   - Charge les membres de l'équipe triés par `sort_order`
   - Détection de langue : `$lang = $_GET['lang'] ?? 'fr'` (défaut FR)
   - Affiche `role_fr`, `role_de` ou `role_en` selon la langue active

6. **`talents.php`** :
   - Charge uniquement les talents actifs (`is_active = 1`, `ORDER BY sort_order ASC`)
   - Affiche nom, prénom, photo

7. **`.htaccess`** (racine) :
   - Rewrite rules pour URLs propres :
     - `/works/video/[slug]` → `project-video.php?slug=[slug]`
     - `/works/photo/[slug]` → `project-photo.php?slug=[slug]`
   - Redirection `.html` → `.php` pour ne pas casser les bookmarks existants

---

## 📋 Instructions de livraison pour chaque agent

Chaque agent doit :

1. **Livrer du code complet**, pas de pseudocode ni de `// implement here`
2. **Commenter le code** en anglais, commentaires utiles (pas évidents)
3. **Gérer les erreurs** : try/catch PDO, validation inputs, messages utilisateur
4. **Utiliser PDO prepared statements** exclusivement (zéro interpolation de variable dans les requêtes SQL)
5. **Protéger chaque action POST** avec un token CSRF (via `csrf_token()` et `csrf_verify()`)
6. **Flash messages** : après chaque action (create/edit/delete), stocker le message en session et l'afficher en haut de la page suivante, puis le supprimer
7. **Mobile responsive** : le dashboard doit être utilisable sur tablette (sidebar collapse en mode hamburger)
8. **Pas de dépendance externe** autre que Tailwind CSS (via CDN) et les fonts SF Pro Display locales

---

## ✅ Ordre d'exécution recommandé

```
1. Agent 1  → DB + config + helpers (tout dépend de ça)
2. Agent 2  → Auth + sécurité (bloque toutes les pages sans ça)
3. Agent 3  → UI/UX layout + composants (nécessaire pour tous les CRUDs)
4. Agent 4  → Upload système (nécessaire pour Agent 6, 7, 8, 9)
5. Agent 5  → Trimmer vidéo (nécessaire pour Agent 6)
6. Agent 6  → CRUD Projets Vidéo
7. Agent 7  → CRUD Projets Photo
8. Agent 8  → About + Équipe
9. Agent 9  → Talents
10. Agent 10 → Pages publiques SSR
```

---

## 🚫 Contraintes absolues

- **Zéro framework PHP** (pas Symfony, pas Laravel, pas Slim)
- **Zéro requête SQL avec interpolation de variable** — PDO prepared statements uniquement
- **Zéro fichier incomplet** — tout le code est fonctionnel et intégrable tel quel
- **Zéro design générique** — respecter strictement le design system Maison 1815 (noir/orange/SF Pro Display)
- **Zéro placeholder** — pas de `lorem ipsum`, pas de données fictives hardcodées dans le code de production
- **Les fichiers supprimés depuis l'admin doivent être supprimés du disque** (hard delete physique)
- **La langue du dashboard est l'anglais** (labels, boutons, messages) — le contenu géré (titres, descriptions des projets) est en FR/autre selon l'admin
