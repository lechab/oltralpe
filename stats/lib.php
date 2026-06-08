<?php
/**
 * Fonctions communes du compteur de visiteurs Oltr'Alpe.
 *
 * Stockage : SQLite (PDO). La base est créée automatiquement au premier appel.
 * Respect de la vie privée : aucune IP n'est stockée en clair, aucun cookie posé.
 */

declare(strict_types=1);

/** Chemin du fichier base de données (hors accès HTTP grâce à data/.htaccess). */
function db_path(): string
{
    return __DIR__ . '/data/visite.sqlite';
}

/**
 * Ouvre la base SQLite et crée le schéma si nécessaire.
 * Renvoie une instance PDO partagée.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dir = dirname(db_path());
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $pdo = new PDO('sqlite:' . db_path());
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS visits (
            id      INTEGER PRIMARY KEY AUTOINCREMENT,
            ts      INTEGER NOT NULL,
            day     TEXT    NOT NULL,
            page    TEXT    NOT NULL,
            referer TEXT    NOT NULL DEFAULT \'\',
            visitor TEXT    NOT NULL
        )'
    );
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_visits_day ON visits(day)');

    return $pdo;
}

/**
 * Identifiant anonyme du visiteur pour la journée en cours.
 * sha256(IP + User-Agent + sel quotidien) : non réversible, change chaque jour,
 * permet de compter les visiteurs uniques par jour sans identifier personne.
 */
function visitor_hash(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $saltDuJour = date('Y-m-d') . '|oltralpe';
    return hash('sha256', $ip . '|' . $ua . '|' . $saltDuJour);
}

/** Nettoie une chaîne pour stockage : longueur bornée, sans caractères de contrôle. */
function clean_str(string $value, int $max = 255): string
{
    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max);
    }
    return substr($value, 0, $max);
}
