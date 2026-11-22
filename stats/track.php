<?php
/**
 * Endpoint de comptage des visites — appelé en AJAX depuis assets/js/common.js.
 *
 * Paramètres GET :
 *   p = page visitée (nom de fichier)
 *   r = provenance de la page (document.referrer), réduite ici à son domaine
 *
 * Enregistre une ligne de visite anonyme (voir lib.php) et renvoie le total de
 * pages vues au format JSON : {"total": 12345}.
 */

declare(strict_types=1);

require __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $pdo = db();

    $device = device_type((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));

    // Les robots ne sont pas comptés.
    if ($device !== 'bot') {
        $page = clean_str((string) ($_GET['p'] ?? ''));
        if ($page === '') {
            $page = '/';
        }
        $referer = referer_host((string) ($_GET['r'] ?? ''), own_host());

        $stmt = $pdo->prepare(
            'INSERT INTO visits (ts, day, page, referer, visitor, device)
             VALUES (:ts, :day, :page, :referer, :visitor, :device)'
        );
        $stmt->execute([
            ':ts'      => time(),
            ':day'     => date('Y-m-d'),
            ':page'    => $page,
            ':referer' => $referer,
            ':visitor' => visitor_hash($pdo),
            ':device'  => $device,
        ]);
    }

    $total = (int) $pdo->query('SELECT COUNT(*) FROM visits')->fetchColumn();

    echo json_encode(['total' => $total]);
} catch (Throwable $e) {
    // Le compteur ne doit jamais perturber l'affichage du site : on échoue en silence.
    http_response_code(200);
    echo json_encode(['total' => null]);
}
