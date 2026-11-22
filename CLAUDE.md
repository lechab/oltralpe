# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Architecture

Site web statique pour l'association « Oltr'Alpe » (franco-italienne, thématique montagne/randonnée).

**Pas de système de build** — les fichiers sont déployés tels quels par FTP via GitHub Actions.

### Structure
- Pages principales à la racine : `index.html`, `novita.html`, `storia.html`, `iniziative.html`, `immagini.html`, `filmati.html`, `contatti.html`, `associazione.html`, `informazioni.html`, `iscrizione.html`, `galerie.html`, `privacy.html` — XHTML 1.0 (style Dreamweaver), contenu en italien.
- `pages/` — pages d'événements et de photos archivées (anciennes, ne pas modifier).
- `assets/css/oltralpe.css` — feuille de styles unique du site. Les styles globaux sont sans scope ; les styles page-spécifiques sont scopés via `.page-xxx` (ex. `.page-contatti`, `.page-storia`).
- `assets/vendor/` — bibliothèques tierces hébergées localement (pas de CDN côté public, pour ne pas transmettre l'IP des visiteurs) : `glightbox/` (GLightbox 3.3.1, lightbox de la galerie). Pour une mise à jour, retélécharger les fichiers `dist/` de la version voulue et mettre à jour `README.txt`. Seul l'admin (`/admin/`) charge encore Sveltia CMS depuis unpkg.
- `assets/js/common.js` — chargé par chaque page :
  - appelées explicitement par les pages : `buildHeader()`, `buildNav()` ;
  - utilitaires : `formatDateIso()`, `initChipsScroll()` (rangées de filtres défilantes avec flèches sur mobile, Galleria et Filmati) ;
  - lancées automatiquement au chargement : `buildEmailLinks()` (adresses e-mail masquées via `data-email-user` / `data-email-domain`), `buildFooter()` (identification + lien privacy), `trackVisit()` (compteur de visites).
- `data/` — données JSON consommées côté client : `news.json` (Novità + 3 dernières sur l'accueil), `gallery.json`, `filmati.json`, `associazione.json`, et `photo-credits.json` (généré en CI, non versionné).
- `images/` — assets visuels ; `images/shared/` contient les éléments communs (logo, favicon) ; `images/uploads/` reçoit les médias du CMS.
- `docs/` — PDF téléchargeables, dont le formulaire d'adhésion `domanda_di_ammissione_a_socio.pdf` (lié depuis `iscrizione.html`).
- `admin/` — interface Sveltia CMS accessible à `/admin/` (voir plus bas).
- `stats/` — compteur de visites PHP + SQLite et son tableau de bord (voir « Privacy et statistiques »).

### Conventions CSS
- Chaque `<body>` porte une classe `page-xxx` correspondant à la page (ex. `<body class="page-contatti">`).
- Tous les styles sont centralisés dans `assets/css/oltralpe.css`, organisés par sections commentées.
- Les noms de classes hérités du style Dreamweaver (`.Stile10`, `.Stile27`, etc.) sont conservés tels quels dans le HTML.

### CMS (Sveltia CMS)
- Remplaçant de Decap CMS (anciennement Netlify CMS), accessible à `/admin/`.
- Backend : GitHub direct (`repo: lechab/oltralpe`, branche `main`).
- Configuration dans `admin/config.yml`. Quatre collections, toutes stockées en JSON dans `data/` :
  - **news** → `data/news.json` — articles avec type (camminata / evento / varie), date de l'appuntamento (`data_camminata`, tous types, vide par défaut), case `annullato`, et champs conditionnels pour les camminate (partenza, lunghezza, dislivello, tempo).
  - **associazione** → `data/associazione.json` — bureau (président, vice, consiglieri, nombre de soci).
  - **galerie** → `data/gallery.json` — albums photo avec type (uscita / culturale / assemblea / via_presepi / varie), couverture et liste de photos.
  - Les listes albums et vidéos ont `add_to_top: true` : un nouvel élément s'ajoute en haut, et les JSON sont rangés du plus récent au plus ancien. Sur le site, Galleria et Filmati trient eux-mêmes (plus récents par défaut, bouton « Ordine » pour inverser) et affichent tous les thèmes connus dans les filtres, même sans contenu.
  - **filmati** → `data/filmati.json` — vidéos YouTube avec année, lieu et thème (camminata / evento / racconti / via_presepi).

### Novità : agenda et export .ics
Tout est côté client dans `novita.html` (styles `.agenda`, `.cal-*`, `.ics-link` dans `oltralpe.css`).
- **Source** : le champ `data_camminata` (`"YYYY-MM-DD HH:mm"`) de `data/news.json`. Malgré son nom historique, il sert pour tous les types : un article n'apparaît dans l'agenda que s'il est rempli. Le laisser **vide** pour les vidéos, comptes rendus et affiches. Ne pas renommer le champ (données existantes).
- **« Prossimi appuntamenti »** : les 5 prochains rendez-vous (à partir d'aujourd'hui), annulés inclus mais barrés.
- **Calendrier mensuel** : jours colorés selon le type, barrés si tous annulés, pastille si plusieurs rendez-vous. Il s'ouvre sur le mois du prochain rendez-vous (sinon le dernier mois avec un rendez-vous). Un clic sur un jour liste ses rendez-vous, avec un lien vers l'article (ancres `#art-N`, N = index dans la liste triée `ARTICLES`).
- **« Aggiungi al calendario »** : `buildIcs()` génère un fichier `.ics` (RFC 5545) dans le navigateur au clic, téléchargé via un Blob. Il contient le fuseau `Europe/Rome` (VTIMEZONE incluse), une durée par type (`ICS_DURATION` : 3 h, 2 h pour varie), `partenza` en LOCATION, le corps en DESCRIPTION et un rappel la veille. Le lien n'est affiché que pour les rendez-vous à venir et non annulés.

### Privacy et statistiques
- `privacy.html` : informativa RGPD en italien. `buildFooter()` (`common.js`) ajoute sous `.footer-bar` de chaque page l'identification de l'association (Oltr'Alpe OdV, siège, C.F.) et le lien vers cette page. Toute modification de la collecte de données (statistiques, nouveau service tiers) doit y être reportée.
- Aucun cookie sans action de l'utilisateur : les vidéos YouTube passent par `youtube-nocookie.com` (Novità : miniature puis lecteur au clic ; Filmati : lecteur au clic ; `pages/` : iframes nocookie).
- Compteur de visites `stats/` (PHP + SQLite, `stats/data/visite.sqlite`, non versionné) : `track.php` est appelé par `trackVisit()` avec `p` (page), `r` (`document.referrer`) et `w` (`navigator.webdriver`). Les données sont anonymes : visiteur = HMAC(IP + User-Agent) avec une clé aléatoire du jour (table `daily_key`, clés des jours passés supprimées), appareil stocké en catégorie (`smartphone` / `tablet` / `computer`), provenance réduite au domaine (navigation interne = vide). Migrations par `PRAGMA user_version` dans `lib.php` (v1 : anonymisation, v2 : colonne `bot`).
- Robots : `bot_name()` (`lib.php`) reconnaît les robots par User-Agent (Googlebot, Bing, IA, Lighthouse, headless, scripts…) ou par `w=1`. Ils sont enregistrés avec `device = 'bot'` et leur nom dans `bot`, sans visiteur ni provenance. Ils sont exclus du compteur public « Visite » et de toutes les statistiques du tableau de bord (vue temporaire `human` dans `stats.php`), qui les liste dans une section « Robot » à part. Seuls les robots qui exécutent le JavaScript atteignent le compteur. Les visites antérieures à la v2 restent comptées comme humaines. Tableau de bord protégé par mot de passe : `stats/stats.php` (hash dans `stats/config.php`, non versionné).

### Palette des badges
Couleurs communes à Novità, l'accueil, la Galleria et les Filmati : camminata/uscita vert `#D5EDB3`/`#336633`, evento/culturale orangé `#FFE8D6`/`#993300`, varie beige `#F2EBD9`/`#6B5B2E`, assemblea gris, via_presepi rouge `#F6DADA`/`#8B1A1A`, racconti bleu ardoise `#DCE6F2`/`#2C4A6E`. Classes `.news-type-badge.xxx` (Novità, accueil) et `.event-badge.xxx` (Galleria, Filmati). Tout nouveau thème ajouté dans `admin/config.yml` doit aussi être ajouté aux libellés (`LABELS` dans `galerie.html`, `THEMES` dans `filmati.html`) et recevoir une couleur.

### Déploiement
GitHub Actions (`deploy.yml`) déploie par FTP vers l'hébergement Aruba via `SamKirkland/FTP-Deploy-Action@v4.3.4`, à chaque push sur `main` (y compris les commits du CMS). Les secrets requis dans le dépôt GitHub sont `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`. Exclus du FTP : `.git*`, `.claude/`, `content/`, `scripts/`. Un fichier supprimé du dépôt est aussi supprimé du serveur au déploiement suivant (constaté avec `stats/genhash.php`).

Suivre un déploiement : `gh run list --branch main --limit 1` puis `gh run watch <id> --exit-status`. Une fois terminé, vérifier en ligne avec `curl` (ex. `curl -s -o /dev/null -w "%{http_code}" https://www.oltralpe.it/<chemin>`). Ne pas appeler `stats/track.php` pour tester : chaque appel est enregistré dans les statistiques.

**Serveur : Windows / IIS 10 avec PHP 8.4** (pas Apache). Conséquences :
- les fichiers `.htaccess` (racine, `admin/`, `stats/data/`) sont **ignorés** ; la configuration passe par `web.config` (racine : types MIME `.yml`/`.json` ; `stats/web.config` : accès HTTP interdit à `stats/data/` et aux `.sqlite`) ;
- toute protection d'un dossier ou d'un fichier doit être écrite en `web.config` (`requestFiltering`, accepté par Aruba), puis vérifiée en ligne avec `curl` : un fichier interdit renvoie 404, un dossier masqué par `hiddenSegments` renvoie 403 ou 404 (vérifié pour `stats/data/`) ;
- PHP 8.4 : tester le code PHP avec l'image Docker `php:8.4-cli` ; `stats/lib.php` force le fuseau `Europe/Rome`.

Pas d'environnement de test : pour vérifier une page, servir le dépôt en local (`python -m http.server`). Le compteur PHP n'y répond pas, c'est normal ; il se teste avec l'image Docker `php:8.4-cli`.

Avant le FTP, une étape CI exécute deux scripts Node :
- `scripts/thumbnails.mjs` (`sharp`) — chaque image de `images/uploads/` (récursif) produit une miniature 400 px dans `images/uploads/thumbs/`. La grille d'albums et la pellicule GLightbox les chargent via `thumbUrl()`, avec repli sur l'original si absente.
- `scripts/credits.mjs` (`exifr`) — lit le crédit auteur (EXIF `Artist`, IPTC `By-line`, XMP `Creator`, `Copyright`) de chaque photo et écrit `data/photo-credits.json`. La lightbox affiche ce crédit par photo, avec repli sur le champ « Réalisation » de l'album. Les valeurs génériques (« Utente », « User »…) sont ignorées.

`images/uploads/thumbs/` et `data/photo-credits.json` ne sont pas versionnés (`.gitignore`) : ils sont régénérés à chaque déploiement.

Pour alimenter les crédits, le crédit auteur doit être présent dans l'EXIF des photos **avant** l'upload CMS (WhatsApp supprime les métadonnées lors d'un transfert WhatsApp, mais pas sur les copies locales). Le script `scripts/crediti-foto.bat` (exclu du déploiement FTP) inscrit l'auteur dans un dossier de photos via `exiftool` — glisser-déposer le dossier dessus, saisir le nom.
