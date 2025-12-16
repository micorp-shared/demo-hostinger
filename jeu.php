<?php
/**
 * Jeu interactif - Portrait en tiles
 * Chaque visiteur peut inverser une tile (cooldown 2s)
 * Les changements sont visibles par tous en temps réel
 */

// Configuration
$config = [
    'host' => 'localhost', // localhost sur Hostinger
    'dbname' => 'u971272190_bdd_micorp',
    'user' => 'u971272190_micorp',
    'password' => '5+o8pTTB+U',
    'grid_size' => 8,
    'cooldown_ms' => 2000
];

// Connexion PDO
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

// API : récupérer l'état de toutes les tiles
if (isset($_GET['api']) && $_GET['api'] === 'state') {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache');
    try {
        $pdo = getDB($config);
        $stmt = $pdo->query('SELECT id, inverted FROM tiles ORDER BY id');
        $tiles = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tiles[(int)$row['id']] = (int)$row['inverted'];
        }
        echo json_encode(['ok' => true, 'tiles' => $tiles]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'db_error']);
    }
    exit;
}

// API : toggle une tile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache');

    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($input['id']) ? (int)$input['id'] : -1;
    $maxId = $config['grid_size'] * $config['grid_size'] - 1;

    if ($id < 0 || $id > $maxId) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'invalid_id']);
        exit;
    }

    try {
        $pdo = getDB($config);
        $stmt = $pdo->prepare('UPDATE tiles SET inverted = 1 - inverted WHERE id = ?');
        $stmt->execute([$id]);

        $stmt = $pdo->prepare('SELECT inverted FROM tiles WHERE id = ?');
        $stmt->execute([$id]);
        $newState = (int)$stmt->fetchColumn();

        echo json_encode(['ok' => true, 'id' => $id, 'inverted' => $newState]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'db_error']);
    }
    exit;
}

// Page HTML
$gridSize = $config['grid_size'];
$cooldownMs = $config['cooldown_ms'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Jeu - Micode</title>
    <meta name="theme-color" content="#080808">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --black: #080808;
            --white: #ececec;
            --white-soft: #909090;
            --accent: #00d4ff;
            --accent-glow: rgba(0, 212, 255, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'IBM Plex Mono', monospace;
            background: var(--black);
            color: var(--white);
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            width: 100%;
            max-width: 480px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 24px;
        }

        h1 {
            font-size: 0.625rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            color: var(--white-soft);
        }

        .grid-wrapper {
            position: relative;
            width: 100%;
            aspect-ratio: 3 / 4;
            background: #111;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(<?= $gridSize ?>, 1fr);
            grid-template-rows: repeat(<?= $gridSize ?>, 1fr);
            width: 100%;
            height: 100%;
            gap: 1px;
            background: rgba(255, 255, 255, 0.03);
        }

        .tile {
            position: relative;
            background-image: url('michael_BW.jpg');
            background-size: <?= $gridSize * 100 ?>% <?= $gridSize * 100 ?>%;
            cursor: pointer;
            transition: filter 0.3s ease;
            overflow: hidden;
        }

        .tile::after {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--accent);
            opacity: 0;
            transition: opacity 0.15s ease;
            pointer-events: none;
        }

        .tile:hover::after {
            opacity: 0.1;
        }

        .tile.inverted {
            filter: invert(1);
        }

        .tile.inverted::after {
            background: #000;
        }

        /* Cooldown overlay */
        .cooldown-active .tile {
            pointer-events: none;
        }

        .cooldown-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 2px;
            background: var(--accent);
            box-shadow: 0 0 10px var(--accent-glow);
            width: 0;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .cooldown-active .cooldown-bar {
            opacity: 1;
            animation: cooldown <?= $cooldownMs ?>ms linear forwards;
        }

        @keyframes cooldown {
            from { width: 100%; }
            to { width: 0%; }
        }

        /* Flash effect on toggle */
        .tile.flash::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--accent);
            animation: flash 0.3s ease-out forwards;
            pointer-events: none;
            z-index: 2;
        }

        @keyframes flash {
            0% { opacity: 0.5; }
            100% { opacity: 0; }
        }

        .info {
            font-size: 0.6875rem;
            color: var(--white-soft);
            text-align: center;
            line-height: 1.6;
        }

        .info a {
            color: var(--accent);
            text-decoration: none;
        }

        .info a:hover {
            text-decoration: underline;
        }

        /* Status indicator */
        .status {
            position: fixed;
            top: 16px;
            right: 16px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #333;
            transition: background 0.3s ease;
        }

        .status.connected {
            background: #22c55e;
            box-shadow: 0 0 8px rgba(34, 197, 94, 0.5);
        }

        .status.syncing {
            background: var(--accent);
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
    <div class="status" id="status"></div>

    <div class="container">
        <h1>Inverser le portrait</h1>

        <div class="grid-wrapper">
            <div class="grid" id="grid"></div>
            <div class="cooldown-bar" id="cooldownBar"></div>
        </div>

        <p class="info">
            Cliquez sur une case pour l'inverser.<br>
            Visible par tous les visiteurs.<br>
            <a href="/">Retour</a>
        </p>
    </div>

    <script>
        const GRID_SIZE = <?= $gridSize ?>;
        const COOLDOWN_MS = <?= $cooldownMs ?>;
        const POLL_INTERVAL = 1500;

        const grid = document.getElementById('grid');
        const cooldownBar = document.getElementById('cooldownBar');
        const status = document.getElementById('status');
        const gridWrapper = document.querySelector('.grid-wrapper');

        let tiles = [];
        let localState = {};
        let cooldownActive = false;
        let pollTimer = null;

        // Créer la grille
        function createGrid() {
            for (let i = 0; i < GRID_SIZE * GRID_SIZE; i++) {
                const row = Math.floor(i / GRID_SIZE);
                const col = i % GRID_SIZE;

                const tile = document.createElement('div');
                tile.className = 'tile';
                tile.dataset.id = i;

                // Position du background pour afficher la bonne portion
                const bgX = (col / (GRID_SIZE - 1)) * 100;
                const bgY = (row / (GRID_SIZE - 1)) * 100;
                tile.style.backgroundPosition = `${bgX}% ${bgY}%`;

                tile.addEventListener('click', () => toggleTile(i));

                grid.appendChild(tile);
                tiles.push(tile);
            }
        }

        // Mettre à jour l'affichage
        function updateDisplay(state) {
            for (const [id, inverted] of Object.entries(state)) {
                const tile = tiles[id];
                if (tile) {
                    const wasInverted = tile.classList.contains('inverted');
                    const isInverted = inverted === 1;

                    if (wasInverted !== isInverted) {
                        tile.classList.toggle('inverted', isInverted);
                        // Flash si changement externe
                        if (localState[id] !== undefined && localState[id] !== inverted) {
                            tile.classList.add('flash');
                            setTimeout(() => tile.classList.remove('flash'), 300);
                        }
                    }
                }
                localState[id] = inverted;
            }
        }

        // Récupérer l'état du serveur
        async function fetchState() {
            try {
                status.className = 'status syncing';
                const res = await fetch('?api=state');
                const data = await res.json();
                if (data.ok) {
                    updateDisplay(data.tiles);
                    status.className = 'status connected';
                }
            } catch (e) {
                status.className = 'status';
            }
        }

        // Toggle une tile
        async function toggleTile(id) {
            if (cooldownActive) return;

            // Optimistic update
            const tile = tiles[id];
            tile.classList.toggle('inverted');
            tile.classList.add('flash');
            setTimeout(() => tile.classList.remove('flash'), 300);

            // Activer le cooldown
            cooldownActive = true;
            gridWrapper.classList.add('cooldown-active');

            setTimeout(() => {
                cooldownActive = false;
                gridWrapper.classList.remove('cooldown-active');
            }, COOLDOWN_MS);

            // Envoyer au serveur
            try {
                const res = await fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const data = await res.json();
                if (data.ok) {
                    localState[id] = data.inverted;
                }
            } catch (e) {
                // Revert on error
                tile.classList.toggle('inverted');
            }
        }

        // Démarrer le polling
        function startPolling() {
            fetchState();
            pollTimer = setInterval(fetchState, POLL_INTERVAL);
        }

        // Pause polling quand l'onglet n'est pas visible
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearInterval(pollTimer);
            } else {
                startPolling();
            }
        });

        // Init
        createGrid();
        startPolling();
    </script>
</body>
</html>
