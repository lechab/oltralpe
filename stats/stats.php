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

// Visites humaines uniquement : les robots (device = 'bot') ont leur propre section.
$pdo->exec("CREATE TEMP VIEW human AS SELECT * FROM visits WHERE device <> 'bot'");

$totalViews   = (int) $pdo->query('SELECT COUNT(*) FROM human')->fetchColumn();
// Les identifiants changent chaque jour : on additionne les visiteurs uniques de chaque jour.
$uniqueTotal  = (int) $pdo->query(
    'SELECT COALESCE(SUM(n), 0) FROM (SELECT COUNT(DISTINCT visitor) AS n FROM human GROUP BY day)'
)->fetchColumn();
$daysTracked  = (int) $pdo->query('SELECT COUNT(DISTINCT day) FROM human')->fetchColumn();

// Visites + uniques par jour, 30 derniers jours.
$perDay = $pdo->query(
    "SELECT day,
            COUNT(*)              AS views,
            COUNT(DISTINCT visitor) AS uniques
     FROM human
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
     FROM human
     GROUP BY page
     ORDER BY views DESC
     LIMIT 15'
)->fetchAll(PDO::FETCH_ASSOC);

// Appareils : 30 derniers jours et depuis le début (les visites antérieures au
// suivi des appareils ont device = '' et apparaissent comme « non rilevato »).
$deviceRows = $pdo->query(
    "SELECT device,
            SUM(CASE WHEN day >= date('now', '-29 days') THEN 1 ELSE 0 END) AS v30,
            COUNT(*) AS vall
     FROM human
     GROUP BY device"
)->fetchAll(PDO::FETCH_ASSOC);

$DEVICE_LABELS = ['smartphone' => 'Smartphone', 'tablet' => 'Tablet', 'computer' => 'Computer', 'altro' => 'Altro', '' => 'Non rilevato'];
$devices = [];
foreach ($DEVICE_LABELS as $key => $label) {
    $devices[$key] = ['label' => $label, 'v30' => 0, 'vall' => 0];
}
foreach ($deviceRows as $row) {
    $key = array_key_exists($row['device'], $devices) ? $row['device'] : 'altro';
    $devices[$key]['v30']  += (int) $row['v30'];
    $devices[$key]['vall'] += (int) $row['vall'];
}
$devices = array_filter($devices, function ($d) { return $d['vall'] > 0; });
$sum30  = array_sum(array_column($devices, 'v30'));
$sumAll = array_sum(array_column($devices, 'vall'));
$known30 = $sum30 - (isset($devices['']) ? $devices['']['v30'] : 0);
$mobile30 = (isset($devices['smartphone']) ? $devices['smartphone']['v30'] : 0)
          + (isset($devices['tablet']) ? $devices['tablet']['v30'] : 0);
$mobilePct = $known30 > 0 ? round($mobile30 / $known30 * 100) : null;

// Provenance : domaine des sites d'où arrivent les visiteurs (navigation interne et
// visites directes exclues), regroupé par service connu.
function source_label(string $host): string
{
    $map = [
        '/(^|\.)google\./'                    => 'Google',
        '/(^|\.)bing\.com$/'                  => 'Bing',
        '/(^|\.)duckduckgo\.com$/'            => 'DuckDuckGo',
        '/(^|\.)yahoo\./'                     => 'Yahoo',
        '/(^|\.)ecosia\.org$/'                => 'Ecosia',
        '/(^|\.)facebook\.com$|^fb\.me$/'     => 'Facebook',
        '/(^|\.)instagram\.com$/'             => 'Instagram',
        '/(^|\.)whatsapp\.(com|net)$/'        => 'WhatsApp',
        '/(^|\.)(youtube\.com|youtu\.be)$/'  => 'YouTube',
        '/^t\.co$|(^|\.)(twitter|x)\.com$/'  => 'X (Twitter)',
        '/(^|\.)wikiloc\.com$/'               => 'Wikiloc',
        '/^com\.google\.android\.gm$/'       => 'Gmail (app)',
        '/^com\.google\.android\./'          => 'App Google (Android)',
    ];
    foreach ($map as $re => $label) {
        if (preg_match($re, $host)) {
            return $label;
        }
    }
    return $host;
}

$refRows = $pdo->query(
    "SELECT referer,
            SUM(CASE WHEN day >= date('now', '-29 days') THEN 1 ELSE 0 END) AS v30,
            COUNT(*) AS vall
     FROM human
     WHERE referer <> ''
     GROUP BY referer"
)->fetchAll(PDO::FETCH_ASSOC);

$sources = [];
foreach ($refRows as $row) {
    $label = source_label((string) $row['referer']);
    if (!isset($sources[$label])) {
        $sources[$label] = ['v30' => 0, 'vall' => 0];
    }
    $sources[$label]['v30']  += (int) $row['v30'];
    $sources[$label]['vall'] += (int) $row['vall'];
}
uasort($sources, function ($a, $b) { return [$b['v30'], $b['vall']] <=> [$a['v30'], $a['vall']]; });
$sources = array_slice($sources, 0, 20, true);

// Robots : visites automatiques, comptées à part.
$botTotal = (int) $pdo->query("SELECT COUNT(*) FROM visits WHERE device = 'bot'")->fetchColumn();
$botRows = $pdo->query(
    "SELECT bot,
            SUM(CASE WHEN day >= date('now', '-29 days') THEN 1 ELSE 0 END) AS v30,
            COUNT(*) AS vall,
            MAX(day) AS last
     FROM visits
     WHERE device = 'bot'
     GROUP BY bot
     ORDER BY v30 DESC, vall DESC"
)->fetchAll(PDO::FETCH_ASSOC);

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function pct(int $n, int $total): string
{
    return $total > 0 ? round($n / $total * 100) . '%' : '–';
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
  .note { color: #888; font-size: .8rem; margin: .75rem 0 0; }
  .meter { display: inline-block; width: 120px; height: 8px; background: #eee; border-radius: 4px; vertical-align: middle; margin-left: .5rem; overflow: hidden; }
  .meter i { display: block; height: 100%; background: var(--verde-chiaro); }
  @media (max-width: 600px) { .meter { display: none; } body { padding: 1rem; } }
</style>
</head>
<body>
  <header>
    <h1>Statistiche di visita — Oltr'Alpe</h1>
    <a class="logout" href="?logout=1">Esci</a>
  </header>

  <div class="cards">
    <div class="card"><div class="num"><?= number_format($totalViews, 0, ',', '.') ?></div><div class="lbl">Pagine viste</div></div>
    <div class="card"><div class="num"><?= number_format($uniqueTotal, 0, ',', '.') ?></div><div class="lbl">Visitatori unici (somma giornaliera)</div></div>
    <div class="card"><div class="num"><?= $mobilePct === null ? '–' : $mobilePct . '%' ?></div><div class="lbl">Da smartphone o tablet (30 giorni)</div></div>
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
    <h2>Dispositivi</h2>
    <?php if (empty($devices)): ?>
      <p class="empty">Nessun dato ancora.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Dispositivo</th><th class="n">Ultimi 30 giorni</th><th class="n">Da sempre</th></tr></thead>
        <tbody>
          <?php foreach ($devices as $d): ?>
            <tr>
              <td><?= h($d['label']) ?></td>
              <td class="n"><?= number_format($d['v30'], 0, ',', '.') ?> (<?= pct($d['v30'], $sum30) ?>)<span class="meter"><i style="width: <?= $sum30 > 0 ? round($d['v30'] / $sum30 * 100) : 0 ?>%"></i></span></td>
              <td class="n"><?= number_format($d['vall'], 0, ',', '.') ?> (<?= pct($d['vall'], $sumAll) ?>)</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p class="note">Pagine viste per tipo di dispositivo. «Non rilevato» = visite registrate prima dell'introduzione di questo dato. Gli iPad recenti si presentano come computer Mac.</p>
    <?php endif; ?>
  </section>

  <section>
    <h2>Provenienza</h2>
    <?php if (empty($sources)): ?>
      <p class="empty">Nessun arrivo da altri siti registrato.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Origine</th><th class="n">Ultimi 30 giorni</th><th class="n">Da sempre</th></tr></thead>
        <tbody>
          <?php foreach ($sources as $label => $d): ?>
            <tr>
              <td class="url"><?= h((string) $label) ?></td>
              <td class="n"><?= number_format($d['v30'], 0, ',', '.') ?></td>
              <td class="n"><?= number_format($d['vall'], 0, ',', '.') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    <p class="note">Siti da cui arrivano i visitatori (solo il nome del sito, mai l'indirizzo completo). Non sono contate le visite dirette, la navigazione interna al sito e i link aperti da app che non trasmettono la provenienza (spesso WhatsApp ed e-mail).</p>
  </section>

  <section>
    <h2>Robot (esclusi dalle statistiche)</h2>
    <?php if (empty($botRows)): ?>
      <p class="empty">Nessun robot registrato.</p>
    <?php else: ?>
      <table>
        <thead><tr><th>Robot</th><th class="n">Ultimi 30 giorni</th><th class="n">Da sempre</th><th class="n">Ultima visita</th></tr></thead>
        <tbody>
          <?php foreach ($botRows as $row): ?>
            <tr>
              <td><?= h($row['bot'] !== '' ? (string) $row['bot'] : 'Robot') ?></td>
              <td class="n"><?= number_format((int) $row['v30'], 0, ',', '.') ?></td>
              <td class="n"><?= number_format((int) $row['vall'], 0, ',', '.') ?></td>
              <td class="n"><?= h((string) $row['last']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    <p class="note">Pagine viste da programmi automatici (motori di ricerca, anteprime dei link, strumenti di analisi, intelligenze artificiali). Sono contati solo i robot che eseguono il JavaScript della pagina: quelli che leggono solo il testo non arrivano al contatore. Le visite registrate prima di questa separazione restano tra quelle umane.</p>
  </section>

  <p class="note">Statistiche anonime: nessun indirizzo IP, nessun cookie. Il visitatore è identificato con una chiave casuale che cambia ogni giorno e viene distrutta il giorno dopo.</p>
</body>
</html>
