<?php
/**
 * Gabarit de configuration du tableau de bord des statistiques.
 *
 * À FAIRE (une seule fois, côté serveur) :
 *   1. Copier ce fichier en « config.php » dans le même dossier.
 *   2. Y placer le hash de votre mot de passe. Pour le générer, exécutez une fois :
 *        php -r "echo password_hash('VOTRE_MOT_DE_PASSE', PASSWORD_DEFAULT), PHP_EOL;"
 *      puis collez le résultat ci-dessous.
 *
 * Le fichier « config.php » n'est volontairement PAS versionné (voir .gitignore),
 * afin de ne pas exposer l'accès au tableau de bord dans le dépôt Git.
 */

return [
    // Hash du mot de passe d'accès à stats.php (NE PAS mettre le mot de passe en clair).
    'password_hash' => '$2y$10$REMPLACEZ_MOI_PAR_UN_VRAI_HASH_BCRYPT_GENERE_AVEC_PASSWORD_HASH',
];
