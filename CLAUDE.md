# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Architecture

Site web statique pour l'association « Oltr'Alpe » (franco-italienne, thématique montagne/randonnée).

**Pas de système de build** — les fichiers sont déployés tels quels par FTP via GitHub Actions.

### Structure
- Pages principales à la racine : `index.html`, `novita.html`, `storia.html`, `iniziative.html`, `immagini.html`, `filmati.html`, `contatti.html`, `associazione.html`, `informazioni.html` — XHTML 1.0 (style Dreamweaver), contenu en italien.
- `pages/` — pages d'événements et de photos archivées (anciennes, ne pas modifier).
- `assets/css/oltralpe.css` — feuille de styles unique du site. Les styles globaux sont sans scope ; les styles page-spécifiques sont scopés via `.page-xxx` (ex. `.page-contatti`, `.page-storia`).
- `assets/js/common.js` — fonctions partagées `buildHeader()` et `buildNav()`, appelées sur chaque page.
- `data/` — données JSON (`news.json`, `filmati.json`, `associazione.json`) consommées côté client.
- `images/` — assets visuels ; `images/shared/` contient les éléments communs (logo, favicon).
- `admin/` — interface Decap CMS accessible à `/admin/`.
- `content/news/` — articles de news en Markdown, créés via le CMS.

### Conventions CSS
- Chaque `<body>` porte une classe `page-xxx` correspondant à la page (ex. `<body class="page-contatti">`).
- Tous les styles sont centralisés dans `assets/css/oltralpe.css`, organisés par sections commentées.
- Les noms de classes hérités du style Dreamweaver (`.Stile10`, `.Stile27`, etc.) sont conservés tels quels dans le HTML.

### CMS (Sveltia CMS)
- Remplaçant de Decap CMS (anciennement Netlify CMS), accessible à `/admin/`.
- Backend : GitHub direct (`repo: lechab/oltralpe`, branche `main`).
- Configuration dans `admin/config.yml`. Quatre collections, toutes stockées en JSON dans `data/` :
  - **news** → `data/news.json` — articles avec type (camminata / evento / varie) et champs conditionnels pour les camminate (date/heure, partenza, lunghezza, dislivello, tempo).
  - **associazione** → `data/associazione.json` — bureau (président, vice, consiglieri, nombre de soci).
  - **galerie** → `data/gallery.json` — albums photo avec couverture et liste de photos.
  - **filmati** → `data/filmati.json` — vidéos YouTube avec année, lieu et thème (camminata / evento / racconti).

### Déploiement
GitHub Actions (`deploy.yml`) déploie par FTP via `SamKirkland/FTP-Deploy-Action@v4.3.4`. Les secrets requis dans le dépôt GitHub sont `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`.

Avant le FTP, une étape CI exécute deux scripts Node :
- `scripts/thumbnails.mjs` (`sharp`) — chaque image de `images/uploads/` (récursif) produit une miniature 400 px dans `images/uploads/thumbs/`. La grille d'albums et la pellicule GLightbox les chargent via `thumbUrl()`, avec repli sur l'original si absente.
- `scripts/credits.mjs` (`exifr`) — lit le crédit auteur (EXIF `Artist`, IPTC `By-line`, XMP `Creator`, `Copyright`) de chaque photo et écrit `data/photo-credits.json`. La lightbox affiche ce crédit par photo, avec repli sur le champ « Réalisation » de l'album. Les valeurs génériques (« Utente », « User »…) sont ignorées.

`images/uploads/thumbs/` et `data/photo-credits.json` ne sont pas versionnés (`.gitignore`) : ils sont régénérés à chaque déploiement.

Pour alimenter les crédits, le crédit auteur doit être présent dans l'EXIF des photos **avant** l'upload CMS (WhatsApp supprime les métadonnées lors d'un transfert WhatsApp, mais pas sur les copies locales). Le script `scripts/crediti-foto.bat` (exclu du déploiement FTP) inscrit l'auteur dans un dossier de photos via `exiftool` — glisser-déposer le dossier dessus, saisir le nom.
