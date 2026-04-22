# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Architecture

Site web statique pour l'association « Oltr'Alpe » (franco-italienne, thématique montagne/randonnée).

**Pas de système de build** — les fichiers sont déployés tels quels par FTP via GitHub Actions.

### Structure
- `index.html` / `iniziative.html` — pages statiques en XHTML 1.0 (style Dreamweaver), contenu en italien.
- `admin/` — interface Decap CMS (anciennement Netlify CMS) accessible à `/admin/` pour gérer le contenu.
- `content/news/` — articles de news en Markdown, créés via le CMS.
- `images/uploads/` — images uploadées via le CMS (chemin configuré dans `admin/config.yml`).

### CMS (Decap CMS)
- Backend : `git-gateway` (nécessite Netlify Identity ou un service compatible).
- Collection « news » : champs `title`, `date`, `image`, `body` (markdown).
- La `site_url` dans `admin/config.yml` est actuellement un placeholder (`https://netlify.app`) — à remplacer par l'URL réelle du site.

### Déploiement
GitHub Actions (`deploy.yml`) déploie par FTP via `SamKirkland/FTP-Deploy-Action@v4.3.4`. Les secrets requis dans le dépôt GitHub sont `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`.