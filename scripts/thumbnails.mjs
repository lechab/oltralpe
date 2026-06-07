// Génère les miniatures des photos uploadées via le CMS.
// Lancé dans GitHub Actions avant le déploiement FTP — voir .github/workflows/deploy.yml.
// Source : images/uploads/ (récursif), formats .jpg/.jpeg/.png/.webp
// Sortie : images/uploads/thumbs/<même sous-chemin> (largeur max THUMB_WIDTH, recompressé).
// La structure de sous-dossiers est reproduite à l'identique sous thumbs/.
// Le dossier thumbs/ n'est pas versionné : il est régénéré à chaque déploiement.

import { readdir, mkdir } from 'node:fs/promises';
import path from 'node:path';
import sharp from 'sharp';

const SRC_DIR = 'images/uploads';
const THUMB_DIR = path.join(SRC_DIR, 'thumbs');
const THUMB_WIDTH = 400;
const EXTS = new Set(['.jpg', '.jpeg', '.png', '.webp']);

let created = 0;
let failed = 0;

async function walk(dir) {
  const entries = await readdir(dir, { withFileTypes: true });
  for (const entry of entries) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      if (path.resolve(full) === path.resolve(THUMB_DIR)) continue; // ne pas traiter thumbs/
      await walk(full);
      continue;
    }
    if (!entry.isFile()) continue;
    const ext = path.extname(entry.name).toLowerCase();
    if (!EXTS.has(ext)) continue;

    const rel = path.relative(SRC_DIR, full);          // ex. galleria/2026/photo.jpg
    const out = path.join(THUMB_DIR, rel);
    await mkdir(path.dirname(out), { recursive: true });

    try {
      let pipe = sharp(full)
        .rotate()                                       // respecte l'orientation EXIF
        .resize({ width: THUMB_WIDTH, withoutEnlargement: true });

      if (ext === '.png') pipe = pipe.png({ quality: 80, compressionLevel: 9 });
      else if (ext === '.webp') pipe = pipe.webp({ quality: 75 });
      else pipe = pipe.jpeg({ quality: 72, mozjpeg: true });

      await pipe.toFile(out);
      created++;
    } catch (err) {
      console.error('Miniature ignorée pour', rel, '-', err.message);
      failed++;
    }
  }
}

await walk(SRC_DIR);
console.log(`Miniatures : ${created} générées, ${failed} échec(s).`);
