<?php
/**
 * Tableau de bord privé des statistiques de visite — protégé par mot de passe.
 *
 * Authentification par session PHP (formulaire + password_verify contre le hash
 * défini dans config.php). Aucune dépendance externe : le graphique est en CSS pur.
 */

declare(strict_types=1);

require __DIR__ . '/lib.php';

session_start();

$configFile = __DIR__ . '/config.php';
$config = is_file($configFile) ? require $configFile : null;
$hash = is_array($config) ? ($config['password_hash'] ?? '') : '';

$loginError = '';

// --- Déconnexion ---------------------------------------------------------
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// --- Traitement du login -------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($hash !== '' && password_verify((string) $_POST['password'], $hash)) {
        session_regenerate_id(true);
        $_SESSION['auth'] = true;
    } else {
        $loginError = 'Password errata.';
    }
}

$authenticated = !empty($_SESSION['auth']);

// --- Écran de connexion --------------------------------------------------
if (!$authenticated) {
    ?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="robots" content="noindex, nofollow" />
<title>Statistiche Oltr'Alpe — Accesso</title>
<style>
  body { font-family: Arial, sans-serif; background: #f4f4f0; color: #333; display: flex; min-height: 100vh; align-items: center; justify-content: center; margin: 0; }
  .box { background: #fff; border: 1px solid #cfcfc4; border-radius: 8px; padding: 2rem; width: 320px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
  h1 { font-size: 1.2rem; color: #5C743D; margin-top: 0; }
  input[type=password] { width: 100%; padding: .6rem; box-sizing: border-box; border: 1px solid #cfcfc4; border-radius: 4px; margin: .5rem 0 1rem; }
  button { width: 100%; padding: .6rem; background: #5C743D; color: #fff; border: 0; border-radius: 4px; cursor: pointer; font-size: 1rem; }
  button:hover { background: #4a5f30; }
  .err { color: #b00020; font-size: .9rem; margin-bottom: .5rem; }
  .warn { color: #9a6700; font-size: .85rem; background: #fff8e1; padding: .5rem; border-radius: 4px; }
</style>
</head>
<body>
  <form class="box" method="post">
    <h1>Statistiche Oltr'Alpe</h1>
    <?php if ($loginError !== ''): ?><p class="err"><?= htmlspecialchars($loginError) ?></p><?php endif; ?>
    <?php if ($hash === ''): ?><p class="warn">config.php mancante: copiare config.example.php e impostare la password.</p><?php endif; ?>
    <label for="password">Password</label>
    <input type="password" id="password" name="password" autofocus />
    <button type="submit">Accedi</button>
  </form>
</body>
</html>
    <?php
    exit;
}

// --- Données (utilisateur authentifié) -----------------------------------
$pdo = db();

$totalViews   = (int) $pdo->query('SELECT COUNT(*) FROM visits')->fetchColumn();
$uniqueTotal  = (int) $pdo->query('SELECT COUNT(DISTINCT visitor) FROM visits')->fetchColumn();
$daysTracked  = (int) $pdo->query('SELECT COUNT(DISTINCT day) FROM visits')->fetchColumn();

// Visites + uniques par jour, 30 derniers jours.
$perDay = $pdo->query(
    "SELECT day,
            COUNT(*)              AS views,
            COUNT(DISTINCT visitor) AS uniques
     FROM visits
     WHERE day >= date('now', '-29 days')
     GROUP BY day
     ORDER BY day ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$maxViews = 0;
foreach ($perDay as $row) {
    $maxViews = max($maxViews, (int) $row['views']);
}

// Top pages.
$topPages = $pdo->query(
    'SELECT page, COUNT(*) AS views
     FROM visits
     GROUP BY page
     ORDER BY views DESC
     LIMIT 15'
)->fetchAll(PDO::FETCH_ASSOC);

// Top référents (hors visites directes).
$topReferers = $pdo->query(
    "SELECT referer, COUNT(*) AS views
     FROM visits
     WHERE referer <> ''
     GROUP BY referer
     ORDER BY views DESC
     LIMIT 15"
)->fetchAll(PDO::FETCH_ASSOC);

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="robots" content="noindex, nofollow" />
<title>Statistiche Oltr'Alpe</title>
<style>
  :root { --verde: #5C743D; --verde-chiaro: #99CC66; }
  * { box-sizing: border-box; }
  body { font-family: Arial, sans-serif; background: #f4f4f0; color: #333; margin: 0; padding: 1.5rem; }
  header { display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: .5rem; }
  h1 { color: var(--verde); font-size: 1.4rem; margin: 0 0 1rem; }
  a.logout { color: var(--verde); font-size: .9rem; }
  .cards { display: flex; flex-wrap: wrap; gap: 1rem; margin: 1rem 0 2rem; }
  .card { background: #fff; border: 1px solid #cfcfc4; border-radius: 8px; padding: 1rem 1.5rem; min-width: 140px; flex: 1; }
  .card .num { font-size: 2rem; font-weight: bold; color: var(--verde); }
  .card .lbl { font-size: .85rem; color: #666; text-transform: uppercase; letter-spacing: .03em; }
  section { background: #fff; border: 1px solid #cfcfc4; border-radius: 8px; padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; }
  section h2 { font-size: 1.05rem; color: var(--verde); margin: 0 0 1rem; }
  .chart { display: flex; align-items: flex-end; gap: 4px; height: 180px; border-bottom: 1px solid #ddd; }
  .chart .bar { flex: 1; background: var(--verde-chiaro); border-radius: 2px 2px 0 0; position: relative; min-height: 1px; }
  .chart .bar:hover { background: var(--verde); }
  .chart .bar span { position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); font-size: .7rem; color: #333; white-space: nowrap; opacity: 0; transition: opacity .15s; background: #fff; border: 1px solid #ddd; padding: 1px 4px; border-radius: 3px; }
  .chart .bar:hover span { opacity: 1; }
  .chart-axis { display: flex; gap: 4px; margin-top: 4px; }
  .chart-axis div { flex: 1; text-align: center; font-size: .65rem; color: #999; overflow: hidden; }
  table { width: 100%; border-collapse: collapse; }
  th, td { text-align: left; padding: .4rem .5rem; border-bottom: 1px solid #eee; font-size: .9rem; }
  th { color: #666; font-weight: normal; text-transform: uppercase; font-size: .75rem; letter-spacing: .03em; }
  td.n { text-align: right; font-variant-numeric: tabular-nums; }
  td.url { word-break: break-all; }
  .empty { color: #999; font-style: italic; }
</style>
</head>
<body>
  <header>
    <h1>Statistiche di visita — Oltr'Alpe</h1>
    <a class="logout" href="?logout=1">Esci</a>
  </header>

  <div class="cards">
    <div class="card"><div class="num"><?= number_format($totalViews, 0, ',', '.') ?></div><div class="lbl">Pagine viste</div></div>
    <div class="card"><div class="num"><?= number_format($uniqueTotal, 0, ',', '.') ?></div><div class="lbl">Visitatori unici</div></div>
    <div class="card"><div class="num"><?= number_format($daysTracked, 0, ',', '.') ?></div><div class="lbl">Giorni registrati</div></div>
  </div>

  <section>
    <h2>Visite — ultimi 30 giorni</h2>
    <?php if (empty($perDay)): ?>
      <p class="empty">Nessun dato ancora.</p>
    <?php else: ?>
      <div class="chart">
        <?php foreach ($perDay as $row):
            $views = (int) $row['views'];
            $pct = $maxViews > 0 ? round($views / $maxViews * 100) : 0; ?>
          <div class="bar" style="height: <?= $pct ?>%">
            <span><?= h($row['day']) ?> — <?= $views ?> viste / <?= (int) $row['uniques'] ?> unici</span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="chart-axis">
        <?php foreach ($perDay as $row): ?>
          <div><?= h(substr((string) $row['day'], 5)) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section>
    <h2>Pagine più viste</h2>
    <?php if (empty($topPages)): ?>
      <p class="empty">Nessun dato ancora.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Pagina</th><th class="n">Viste</th></tr></thead>
        <tbody>
          <?php foreach ($topPages as $row): ?>
            <tr><td class="url"><?= h($row['page']) ?></td><td class="n"><?= number_format((int) $row['views'], 0, ',', '.') ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <section>
    <h2>Provenienza (referrer)</h2>
    <?php if (empty($topReferers)): ?>
      <p class="empty">Nessun referrer registrato (visite dirette).</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Origine</th><th class="n">Viste</th></tr></thead>
        <tbody>
          <?php foreach ($topReferers as $row): ?>
            <tr><td class="url"><?= h($row['referer']) ?></td><td class="n"><?= number_format((int) $row['views'], 0, ',', '.') ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
</body>
</html>
