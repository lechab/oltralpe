<?php
/**
 * Endpoint de comptage des visites — appelé en AJAX depuis assets/js/common.js.
 *
 * Enregistre une ligne de visite (anonyme) et renvoie le total de pages vues
 * au format JSON : {"total": 12345}.
 */

declare(strict_types=1);

require __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $pdo = db();

    $page = clean_str((string) ($_GET['p'] ?? ''));
    if ($page === '') {
        $page = '/';
    }
    $referer = clean_str((string) ($_SERVER['HTTP_REFERER'] ?? ''), 512);

    $stmt = $pdo->prepare(
        'INSERT INTO visits (ts, day, page, referer, visitor)
         VALUES (:ts, :day, :page, :referer, :visitor)'
    );
    $stmt->execute([
        ':ts'      => time(),
        ':day'     => date('Y-m-d'),
        ':page'    => $page,
        ':referer' => $referer,
        ':visitor' => visitor_hash(),
    ]);

    $total = (int) $pdo->query('SELECT COUNT(*) FROM visits')->fetchColumn();

    echo json_encode(['total' => $total]);
} catch (Throwable $e) {
    // Le compteur ne doit jamais perturber l'affichage du site : on échoue en silence.
    http_response_code(200);
    echo json_encode(['total' => null]);
}
