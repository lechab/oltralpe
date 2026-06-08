<?php
/**
 * UTILITAIRE TEMPORAIRE — à supprimer après usage.
 *
 * Aide à générer le hash de mot de passe à coller dans config.php, quand on n'a
 * pas PHP en local. Saisir le mot de passe (envoyé en POST, jamais dans l'URL),
 * copier le hash affiché dans config.php, PUIS SUPPRIMER CE FICHIER du serveur.
 */

declare(strict_types=1);

$hash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pwd']) && $_POST['pwd'] !== '') {
    $hash = password_hash((string) $_POST['pwd'], PASSWORD_DEFAULT);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8" />
<meta name="robots" content="noindex, nofollow" />
<title>Genera hash password</title>
<style>
  body { font-family: Arial, sans-serif; max-width: 640px; margin: 3rem auto; padding: 0 1rem; color: #333; }
  input { padding: .5rem; width: 260px; }
  button { padding: .5rem 1rem; }
  code { display: block; background: #f4f4f0; border: 1px solid #cfcfc4; padding: .8rem; margin-top: 1rem; word-break: break-all; }
  .warn { color: #b00020; font-weight: bold; }
</style>
</head>
<body>
  <h1>Genera hash password</h1>
  <p class="warn">⚠️ Eliminare questo file (genhash.php) dal server dopo l'uso.</p>
  <form method="post">
    <input type="password" name="pwd" placeholder="Password" autofocus />
    <button type="submit">Genera</button>
  </form>
  <?php if ($hash !== ''): ?>
    <p>Copiare in <strong>config.php</strong> (chiave <code style="display:inline;padding:0;border:0;background:none">password_hash</code>):</p>
    <code><?= htmlspecialchars($hash, ENT_QUOTES, 'UTF-8') ?></code>
  <?php endif; ?>
</body>
</html>
