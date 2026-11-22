<?php
/**
 * Fonctions communes du compteur de visiteurs Oltr'Alpe.
 *
 * Stockage : SQLite (PDO). La base est créée automatiquement au premier appel.
 *
 * Anonymat :
 *  - aucune IP ni User-Agent n'est stocké ;
 *  - le visiteur est un HMAC(IP + User-Agent) avec une clé ALÉATOIRE du jour, détruite
 *    le lendemain : une fois la journée passée, il devient impossible de relier une ligne
 *    à une IP ou de suivre quelqu'un d'un jour à l'autre ;
 *  - l'appareil n'est gardé que sous forme de catégorie (smartphone / tablet / computer) ;
 *  - la provenance n'est gardée que sous forme de domaine (ex. « google.com »), jamais l'URL ;
 *  - aucun cookie.
 */

declare(strict_types=1);

/** Version du schéma (PRAGMA user_version). */
const STATS_SCHEMA_VERSION = 1;

/** Chemin du fichier base de données (hors accès HTTP grâce à data/.htaccess). */
function db_path(): string
{
    return __DIR__ . '/data/visite.sqlite';
}

/**
 * Ouvre la base SQLite, crée le schéma et applique les migrations si nécessaire.
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
    $pdo->exec('PRAGMA busy_timeout = 5000');
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
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS daily_key (
            day TEXT PRIMARY KEY,
            k   TEXT NOT NULL
        )'
    );

    if ((int) $pdo->query('PRAGMA user_version')->fetchColumn() < STATS_SCHEMA_VERSION) {
        migrate($pdo);
    }

    return $pdo;
}

/**
 * Migration v0 -> v1 (une seule fois, sous verrou) :
 *  - ajoute la colonne « device » (vide pour les anciennes lignes = non rilevato) ;
 *  - remplace les anciens identifiants visiteur (sel prévisible) par des jetons aléatoires,
 *    distincts par jour, ce qui garde le comptage des visiteurs uniques par jour ;
 *  - réduit les anciennes provenances à leur domaine et efface les provenances internes.
 */
function migrate(PDO $pdo): void
{
    $pdo->exec('BEGIN IMMEDIATE');
    try {
        if ((int) $pdo->query('PRAGMA user_version')->fetchColumn() >= STATS_SCHEMA_VERSION) {
            $pdo->exec('COMMIT');
            return;
        }

        $cols = array_column($pdo->query('PRAGMA table_info(visits)')->fetchAll(PDO::FETCH_ASSOC), 'name');
        if (!in_array('device', $cols, true)) {
            $pdo->exec('ALTER TABLE visits ADD COLUMN device TEXT NOT NULL DEFAULT \'\'');
        }

        $pairs = $pdo->query('SELECT DISTINCT day, visitor FROM visits')->fetchAll(PDO::FETCH_NUM);
        $upd = $pdo->prepare('UPDATE visits SET visitor = :new WHERE day = :day AND visitor = :old');
        foreach ($pairs as [$day, $old]) {
            $upd->execute([':new' => bin2hex(random_bytes(8)), ':day' => $day, ':old' => $old]);
        }

        $own = own_host();
        $refs = $pdo->query("SELECT DISTINCT referer FROM visits WHERE referer <> ''")->fetchAll(PDO::FETCH_COLUMN);
        $updRef = $pdo->prepare('UPDATE visits SET referer = :new WHERE referer = :old');
        foreach ($refs as $old) {
            $updRef->execute([':new' => referer_host((string) $old, $own), ':old' => $old]);
        }

        $pdo->exec('PRAGMA user_version = ' . STATS_SCHEMA_VERSION);
        $pdo->exec('COMMIT');
    } catch (Throwable $e) {
        $pdo->exec('ROLLBACK');
        throw $e;
    }
}

/**
 * Clé aléatoire du jour. Les clés des jours précédents sont supprimées :
 * les identifiants déjà enregistrés ne peuvent plus être recalculés.
 */
function daily_key(PDO $pdo): string
{
    $today = date('Y-m-d');
    $stmt = $pdo->prepare('SELECT k FROM daily_key WHERE day = :d');
    $stmt->execute([':d' => $today]);
    $key = $stmt->fetchColumn();
    if (is_string($key) && $key !== '') {
        return $key;
    }

    $pdo->prepare('INSERT OR IGNORE INTO daily_key (day, k) VALUES (:d, :k)')
        ->execute([':d' => $today, ':k' => bin2hex(random_bytes(32))]);
    $pdo->prepare('DELETE FROM daily_key WHERE day <> :d')->execute([':d' => $today]);

    $stmt->execute([':d' => $today]);
    return (string) $stmt->fetchColumn();
}

/** Identifiant anonyme du visiteur pour la journée en cours (16 caractères hex). */
function visitor_hash(PDO $pdo): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return substr(hash_hmac('sha256', $ip . '|' . $ua, daily_key($pdo)), 0, 16);
}

/** Catégorie d'appareil déduite du User-Agent (le User-Agent lui-même n'est pas stocké). */
function device_type(string $ua): string
{
    if ($ua === '') {
        return 'altro';
    }
    if (preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit/i', $ua)) {
        return 'bot';
    }
    if (preg_match('/iPad|Tablet|PlayBook|Silk|Kindle/i', $ua)
        || (stripos($ua, 'Android') !== false && stripos($ua, 'Mobile') === false)) {
        return 'tablet';
    }
    if (preg_match('/Mobi|iPhone|iPod|Android|Windows Phone/i', $ua)) {
        return 'smartphone';
    }
    return 'computer';
}

/** Domaine du site lui-même, sans « www. ». */
function own_host(): string
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'www.oltralpe.it'));
    $host = preg_replace('/:\d+$/', '', $host) ?? $host;
    return preg_replace('/^www\./', '', $host) ?? $host;
}

/**
 * Réduit une provenance à son domaine (sans « www. »).
 * Renvoie '' pour une visite directe ou une navigation interne au site.
 */
function referer_host(string $url, string $ownHost): string
{
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    $host = parse_url($url, PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
        return '';
    }
    $host = strtolower($host);
    $host = preg_replace('/^www\./', '', $host) ?? $host;
    if ($host === $ownHost || $host === 'localhost' || $host === '127.0.0.1') {
        return '';
    }
    return clean_str($host, 100);
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
