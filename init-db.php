<?php
/**
 * Script d'initialisation de la base de données
 * Usage : php init-db.php (CLI) ou accéder via navigateur une seule fois
 */

$config = [
    'host' => 'localhost', // 'srv1340.hstgr.io' pour accès distant
    'dbname' => 'u971272190_bdd_micorp',
    'user' => 'u971272190_micorp',
    'password' => '5+o8pTTB+U'
];

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
        $config['user'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Créer la table visits
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS visits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            path VARCHAR(255) NOT NULL,
            referrer VARCHAR(500) DEFAULT NULL,
            country VARCHAR(2) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created (created_at),
            INDEX idx_path (path)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    echo "Table 'visits' créée avec succès.\n";

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
