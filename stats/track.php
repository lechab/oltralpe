<?php
/**
 * Endpoint de comptage des visites — appelé en AJAX depuis assets/js/common.js.
 *
 * Paramètres GET :
 *   p = page visitée (nom de fichier)
 *   r = provenance de la page (document.referrer), réduite ici à son domaine
 *   w = 1 si navigator.webdriver est vrai (navigateur piloté par un programme)
 *
 * Enregistre une ligne de visite anonyme (voir lib.php). Les robots sont enregistrés
 * à part (device = 'bot', colonne bot = leur nom) et exclus du total.
 * Renvoie le total des pages vues par des humains : {"total": 12345}.
 */

declare(strict_types=1);

require __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $pdo = db();

    $ua  = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    $bot = bot_name($ua, ($_GET['w'] ?? '') === '1');

    $page = clean_str((string) ($_GET['p'] ?? ''));
    if ($page === '') {
        $page = '/';
    }

    $stmt = $pdo->prepare(
        'INSERT INTO visits (ts, day, page, referer, visitor, device, bot)
         VALUES (:ts, :day, :page, :referer, :visitor, :device, :bot)'
    );
    $stmt->execute([
        ':ts'      => time(),
        ':day'     => date('Y-m-d'),
        ':page'    => $page,
        ':referer' => $bot === '' ? referer_host((string) ($_GET['r'] ?? ''), own_host()) : '',
        ':visitor' => $bot === '' ? visitor_hash($pdo) : '',
        ':device'  => $bot === '' ? device_type($ua) : 'bot',
        ':bot'     => $bot,
    ]);

    $total = (int) $pdo->query("SELECT COUNT(*) FROM visits WHERE device <> 'bot'")->fetchColumn();

    echo json_encode(['total' => $total]);
} catch (Throwable $e) {
    // Le compteur ne doit jamais perturber l'affichage du site : on échoue en silence.
    http_response_code(200);
    echo json_encode(['total' => null]);
}
