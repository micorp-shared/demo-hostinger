<?php
/**
 * Script d'initialisation de la base de données
 * Usage :
 *   php init-db.php        → Crée la table
 *   php init-db.php demo   → Crée la table + données de démo
 *   ?demo=1 via navigateur → Idem
 */

$config = [
    'host' => 'localhost', // 'srv1340.hstgr.io' pour accès distant
    'dbname' => 'u971272190_bdd_micorp',
    'user' => 'u971272190_micorp',
    'password' => '5+o8pTTB+U'
];

$withDemo = isset($_GET['demo']) || (isset($argv[1]) && $argv[1] === 'demo');

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Reset et créer la table visits
    $pdo->exec("DROP TABLE IF EXISTS visits");
    $pdo->exec("
        CREATE TABLE visits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            path VARCHAR(255) NOT NULL,
            referrer VARCHAR(500) DEFAULT NULL,
            country VARCHAR(2) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created (created_at),
            INDEX idx_path (path)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    echo "Table 'visits' créée.\n";

    // Données de démo
    if ($withDemo) {
        $projects = [
            "J'ai infiltré une pyramide de Ponzi",
            "Comment blanchir de l'argent ?",
            "Téléphones à 6000\$",
            "24h du crime organisé",
            "L'infiltration impossible",
            "Le complot le plus grave de la tech française"
        ];
        $countries = ['FR','FR','FR','FR','FR','BE','BE','CH','CA','CA','MA','SN','US','DE'];

        $stmt = $pdo->prepare("INSERT INTO visits (path, country, created_at) VALUES (?, ?, ?)");

        // Générer des visites sur 7 jours
        for ($day = 6; $day >= 0; $day--) {
            $date = date('Y-m-d', strtotime("-$day days"));
            $visitCount = rand(8, 25) + (6 - $day) * 3; // Plus récent = plus de visites

            for ($i = 0; $i < $visitCount; $i++) {
                $hour = rand(8, 23);
                $min = rand(0, 59);
                $timestamp = "$date $hour:$min:00";
                $country = $countries[array_rand($countries)];

                // 80% visites de page, 20% clics projets
                if (rand(1, 100) <= 80) {
                    $path = '/';
                } else {
                    $path = $projects[array_rand($projects)];
                }

                $stmt->execute([$path, $country, $timestamp]);
            }
        }

        $total = $pdo->query("SELECT COUNT(*) FROM visits")->fetchColumn();
        echo "Données de démo insérées ($total visites).\n";
    }

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
