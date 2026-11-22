# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Architecture

Site web statique pour l'association « Oltr'Alpe » (franco-italienne, thématique montagne/randonnée).

**Pas de système de build** — les fichiers sont déployés tels quels par FTP via GitHub Actions.

### Structure
- Pages principales à la racine : `index.html`, `novita.html`, `storia.html`, `iniziative.html`, `immagini.html`, `filmati.html`, `contatti.html`, `associazione.html`, `informazioni.html` — XHTML 1.0 (style Dreamweaver), contenu en italien.
- `pages/` — pages d'événements et de photos archivées (anciennes, ne pas modifier).
- `assets/css/oltralpe.css` — feuille de styles unique du site. Les styles globaux sont sans scope ; les styles page-spécifiques sont scopés via `.page-xxx` (ex. `.page-contatti`, `.page-storia`).
- `assets/js/common.js` — fonctions partagées `buildHeader()` et `buildNav()`, appelées sur chaque page, plus `formatDateIso()` et `initChipsScroll()` (rangées de filtres défilantes avec flèches sur mobile, Galleria et Filmati).
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

### Palette des badges
Couleurs communes à Novità, l'accueil, la Galleria et les Filmati : camminata/uscita vert `#D5EDB3`/`#336633`, evento/culturale orangé `#FFE8D6`/`#993300`, varie beige `#F2EBD9`/`#6B5B2E`, assemblea gris, via_presepi rouge `#F6DADA`/`#8B1A1A`, racconti bleu ardoise `#DCE6F2`/`#2C4A6E`. Classes `.news-type-badge.xxx` (Novità, accueil) et `.event-badge.xxx` (Galleria, Filmati). Tout nouveau thème ajouté dans `admin/config.yml` doit aussi être ajouté aux libellés (`LABELS` dans `galerie.html`, `THEMES` dans `filmati.html`) et recevoir une couleur.

### Déploiement
GitHub Actions (`deploy.yml`) déploie par FTP via `SamKirkland/FTP-Deploy-Action@v4.3.4`. Les secrets requis dans le dépôt GitHub sont `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`.

Avant le FTP, une étape CI exécute deux scripts Node :
- `scripts/thumbnails.mjs` (`sharp`) — chaque image de `images/uploads/` (récursif) produit une miniature 400 px dans `images/uploads/thumbs/`. La grille d'albums et la pellicule GLightbox les chargent via `thumbUrl()`, avec repli sur l'original si absente.
- `scripts/credits.mjs` (`exifr`) — lit le crédit auteur (EXIF `Artist`, IPTC `By-line`, XMP `Creator`, `Copyright`) de chaque photo et écrit `data/photo-credits.json`. La lightbox affiche ce crédit par photo, avec repli sur le champ « Réalisation » de l'album. Les valeurs génériques (« Utente », « User »…) sont ignorées.

`images/uploads/thumbs/` et `data/photo-credits.json` ne sont pas versionnés (`.gitignore`) : ils sont régénérés à chaque déploiement.

Pour alimenter les crédits, le crédit auteur doit être présent dans l'EXIF des photos **avant** l'upload CMS (WhatsApp supprime les métadonnées lors d'un transfert WhatsApp, mais pas sur les copies locales). Le script `scripts/crediti-foto.bat` (exclu du déploiement FTP) inscrit l'auteur dans un dossier de photos via `exiftool` — glisser-déposer le dossier dessus, saisir le nom.
