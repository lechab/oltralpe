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
