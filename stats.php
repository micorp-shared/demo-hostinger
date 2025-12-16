<?php
/**
 * Analytics minimaliste
 * - GET ?t=1&p=/path : tracker (appelé depuis le site)
 * - GET / : dashboard
 */

$config = [
    'host' => 'localhost',
    'dbname' => 'u971272190_bdd_micorp',
    'user' => 'u971272190_micorp',
    'password' => '5+o8pTTB+U'
];

function getDB($config) {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
            $config['user'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    return $pdo;
}

// Géolocalisation simple via ip-api.com (gratuit)
function getCountry($ip) {
    if ($ip === '127.0.0.1' || $ip === '::1') return null;
    $ctx = stream_context_create(['http' => ['timeout' => 1]]);
    $data = @file_get_contents("http://ip-api.com/json/{$ip}?fields=countryCode", false, $ctx);
    if ($data) {
        $json = json_decode($data, true);
        return $json['countryCode'] ?? null;
    }
    return null;
}

// Tracker
if (isset($_GET['t'])) {
    header('Content-Type: image/gif');
    header('Cache-Control: no-store');

    // 1x1 transparent GIF
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

    $path = isset($_GET['p']) ? substr($_GET['p'], 0, 255) : '/';
    $referrer = isset($_SERVER['HTTP_REFERER']) ? substr($_SERVER['HTTP_REFERER'], 0, 500) : null;
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $ip = explode(',', $ip)[0];

    // Ne pas tracker les bots évidents
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (preg_match('/bot|crawl|spider|slurp/i', $ua)) exit;

    try {
        $pdo = getDB($config);
        $country = getCountry($ip);

        $stmt = $pdo->prepare('INSERT INTO visits (path, referrer, country) VALUES (?, ?, ?)');
        $stmt->execute([$path, $referrer, $country]);
    } catch (Exception $e) {
        // Silencieux
    }
    exit;
}

// Dashboard
try {
    $pdo = getDB($config);

    // Stats globales
    $total = $pdo->query('SELECT COUNT(*) FROM visits')->fetchColumn();
    $today = $pdo->query("SELECT COUNT(*) FROM visits WHERE created_at >= CURDATE()")->fetchColumn();
    $week = $pdo->query("SELECT COUNT(*) FROM visits WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();

    // Projets cliqués (path qui ne commence pas par /)
    $topProjects = $pdo->query("
        SELECT path, COUNT(*) as clicks
        FROM visits
        WHERE path NOT LIKE '/%' AND path NOT LIKE '#%'
        GROUP BY path
        ORDER BY clicks DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Top pays
    $topCountries = $pdo->query("
        SELECT country, COUNT(*) as views
        FROM visits
        WHERE country IS NOT NULL
        GROUP BY country
        ORDER BY views DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Visites par jour (7 derniers jours) - toujours 7 entrées
    $dailyRaw = $pdo->query("
        SELECT DATE(created_at) as day, COUNT(*) as views
        FROM visits
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(created_at)
    ")->fetchAll(PDO::FETCH_KEY_PAIR);

    $daily = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $daily[] = ['day' => $date, 'views' => $dailyRaw[$date] ?? 0];
    }

} catch (Exception $e) {
    $error = true;
}

// Noms des pays
$countryNames = ['FR'=>'France','US'=>'États-Unis','GB'=>'Royaume-Uni','DE'=>'Allemagne','BE'=>'Belgique','CH'=>'Suisse','CA'=>'Canada','ES'=>'Espagne','IT'=>'Italie','NL'=>'Pays-Bas','PT'=>'Portugal','MA'=>'Maroc','DZ'=>'Algérie','TN'=>'Tunisie','SN'=>'Sénégal','CI'=>"Côte d'Ivoire"];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Funnel+Display:wght@400;500&family=IBM+Plex+Mono:wght@400&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <style>
        :root {
            --black: #080808;
            --black-elevated: #0c0c0c;
            --black-card: #101010;
            --white: #ececec;
            --white-soft: #909090;
            --white-muted: #484848;
            --accent: #00d4ff;
            --accent-glow: rgba(0, 212, 255, 0.15);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'IBM Plex Mono', monospace;
            background: var(--black);
            color: var(--white);
            min-height: 100vh;
            padding: 48px 24px;
            -webkit-font-smoothing: antialiased;
        }

        /* Scanlines subtils */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            background: repeating-linear-gradient(
                0deg, transparent, transparent 2px,
                rgba(0, 0, 0, 0.03) 2px, rgba(0, 0, 0, 0.03) 4px
            );
            z-index: 1000;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        header {
            margin-bottom: 56px;
        }

        h1 {
            font-family: 'Funnel Display', sans-serif;
            font-size: 2rem;
            font-weight: 500;
            letter-spacing: -0.03em;
            margin-bottom: 8px;
        }

        .subtitle {
            font-size: 0.75rem;
            color: var(--white-muted);
            letter-spacing: 0.05em;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1px;
            background: var(--white-muted);
            margin-bottom: 40px;
        }

        .stat {
            background: var(--black-card);
            padding: 32px 24px;
            text-align: center;
        }

        .stat-value {
            font-family: 'Funnel Display', sans-serif;
            font-size: 2.5rem;
            font-weight: 500;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, var(--white) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            font-size: 0.625rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--white-muted);
            margin-top: 8px;
        }

        section {
            margin-bottom: 48px;
        }

        h2 {
            font-family: 'Funnel Display', sans-serif;
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--white-soft);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .list {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            background: var(--black-card);
            font-size: 0.8125rem;
        }

        .list-item-name {
            color: var(--white);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 70%;
        }

        .list-item-value {
            color: var(--accent);
            font-variant-numeric: tabular-nums;
        }

        .chart {
            display: flex;
            align-items: flex-end;
            gap: 4px;
            height: 120px;
            padding: 16px;
            background: var(--black-card);
        }

        .chart-bar {
            flex: 1;
            background: linear-gradient(to top, var(--accent), rgba(0,212,255,0.3));
            min-height: 4px;
            position: relative;
            transition: opacity 0.2s;
        }

        .chart-bar:hover {
            opacity: 0.8;
        }

        .chart-bar::after {
            content: attr(data-label);
            position: absolute;
            bottom: -24px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.5625rem;
            color: var(--white-muted);
            white-space: nowrap;
        }

        .chart-bar::before {
            content: attr(data-value);
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.625rem;
            color: var(--white-soft);
            opacity: 0;
            transition: opacity 0.2s;
        }

        .chart-bar:hover::before {
            opacity: 1;
        }

        .chart-bar.empty-bar {
            background: rgba(255, 255, 255, 0.03);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05);
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
            background: var(--black-card);
            border: 1px dashed rgba(255, 255, 255, 0.08);
        }

        .empty-icon {
            font-family: 'Material Icons Round';
            font-size: 32px;
            color: var(--white-muted);
            opacity: 0.4;
            margin-bottom: 12px;
        }

        .empty-state p {
            font-size: 0.75rem;
            color: var(--white-muted);
            letter-spacing: 0.02em;
        }

        footer {
            margin-top: 64px;
            padding-top: 24px;
            border-top: 1px solid rgba(255,255,255,0.04);
        }

        footer a {
            font-size: 0.75rem;
            color: var(--white-muted);
            text-decoration: none;
            transition: color 0.2s;
        }

        footer a:hover {
            color: var(--accent);
        }

        @media (max-width: 640px) {
            .grid {
                grid-template-columns: 1fr;
            }
            .stat-value {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Analytics</h1>
            <p class="subtitle">Statistiques de visite</p>
        </header>

        <?php if (isset($error)): ?>
            <p class="empty">Erreur de connexion à la base de données.</p>
        <?php else: ?>

        <div class="grid">
            <div class="stat">
                <div class="stat-value"><?= number_format($total) ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?= number_format($week) ?></div>
                <div class="stat-label">7 derniers jours</div>
            </div>
            <div class="stat">
                <div class="stat-value"><?= number_format($today) ?></div>
                <div class="stat-label">Aujourd'hui</div>
            </div>
        </div>

        <section>
            <h2>7 derniers jours</h2>
            <?php $maxViews = max(array_column($daily, 'views')) ?: 1; ?>
            <div class="chart">
                <?php foreach ($daily as $d): ?>
                    <?php
                        $height = ($d['views'] / $maxViews) * 100;
                        $dayLabel = date('d/m', strtotime($d['day']));
                    ?>
                    <div class="chart-bar <?= $d['views'] === 0 ? 'empty-bar' : '' ?>"
                         style="height: <?= max($height, 4) ?>%"
                         data-label="<?= $dayLabel ?>"
                         data-value="<?= $d['views'] ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section>
            <h2>Projets cliqués</h2>
            <?php if (empty($topProjects)): ?>
                <div class="empty-state">
                    <span class="empty-icon">play_arrow</span>
                    <p>En attente des premiers clics</p>
                </div>
            <?php else: ?>
                <div class="list">
                    <?php foreach ($topProjects as $project): ?>
                        <div class="list-item">
                            <span class="list-item-name"><?= htmlspecialchars($project['path']) ?></span>
                            <span class="list-item-value"><?= number_format($project['clicks']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section>
            <h2>Pays</h2>
            <?php if (empty($topCountries)): ?>
                <div class="empty-state">
                    <span class="empty-icon">public</span>
                    <p>En attente des premières visites</p>
                </div>
            <?php else: ?>
                <div class="list">
                    <?php foreach ($topCountries as $c): ?>
                        <div class="list-item">
                            <span class="list-item-name"><?= $countryNames[$c['country']] ?? $c['country'] ?></span>
                            <span class="list-item-value"><?= number_format($c['views']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <?php endif; ?>

        <footer>
            <a href="/">Retour au site</a>
        </footer>
    </div>
</body>
</html>
