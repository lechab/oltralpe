// Extrait le crédit auteur (EXIF/IPTC/XMP) de chaque photo et génère data/photo-credits.json.
// Lancé dans GitHub Actions avant le déploiement FTP — voir .github/workflows/deploy.yml.
// La galerie (galerie.html) affiche ce crédit dans la légende GLightbox, avec repli sur
// le champ « Réalisation » de l'album si une photo n'a pas de crédit propre.
// Le fichier généré n'est pas versionné : il est recréé à chaque déploiement.

import { readdir, writeFile } from 'node:fs/promises';
import path from 'node:path';
import exifr from 'exifr';

const SRC_DIR = 'images/uploads';
const THUMB_DIR = path.join(SRC_DIR, 'thumbs');
const OUT_FILE = 'data/photo-credits.json';
const EXTS = new Set(['.jpg', '.jpeg', '.png', '.webp', '.tif', '.tiff']);

// Champs porteurs d'un nom d'auteur, par ordre de préférence.
const NAME_FIELDS = ['Artist', 'creator', 'Creator', 'Byline', 'by-line', 'XPAuthor'];
const COPYRIGHT_FIELDS = ['Copyright', 'rights', 'CopyrightNotice'];

// Valeurs génériques à ignorer (noms d'utilisateur Windows par défaut, etc.).
const GENERIC = new Set(['utente', 'user', 'utilisateur', 'owner', 'administrator',
                         'admin', 'windows user', 'picasa', 'unknown']);

function clean(value) {
  if (value == null) return '';
  if (Array.isArray(value)) value = value.filter(Boolean).join(', ');
  var s = String(value).trim();
  if (!s || GENERIC.has(s.toLowerCase())) return '';
  return s;
}

function pickCredit(meta) {
  for (const f of NAME_FIELDS) {
    const v = clean(meta[f]);
    if (v) return v;
  }
  for (const f of COPYRIGHT_FIELDS) {
    const v = clean(meta[f]);
    if (v) return v.replace(/^(©|\(c\)|copyright)\s*/i, '').trim() || v;
  }
  return '';
}

const credits = {};
let scanned = 0;

async function walk(dir) {
  const entries = await readdir(dir, { withFileTypes: true });
  for (const entry of entries) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      if (path.resolve(full) === path.resolve(THUMB_DIR)) continue;
      await walk(full);
      continue;
    }
    if (!entry.isFile()) continue;
    if (!EXTS.has(path.extname(entry.name).toLowerCase())) continue;

    scanned++;
    let meta;
    try {
      meta = await exifr.parse(full, { tiff: true, iptc: true, xmp: true });
    } catch { meta = null; }
    if (!meta) continue;

    const credit = pickCredit(meta);
    if (!credit) continue;

    const rel = path.relative(SRC_DIR, full).split(path.sep).join('/');
    credits['/' + path.posix.join('images/uploads', rel)] = credit;
  }
}

await walk(SRC_DIR);
await writeFile(OUT_FILE, JSON.stringify(credits, null, 2) + '\n', 'utf8');
console.log(`Crédits : ${Object.keys(credits).length}/${scanned} photo(s) avec auteur -> ${OUT_FILE}`);
