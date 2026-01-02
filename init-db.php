<?php
/**
 * Crée la table visits
 * Usage: php init-db.php
 */

$config = [
    'host' => 'localhost',
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

    $pdo->exec("DROP TABLE IF EXISTS visits");
    $pdo->exec("
        CREATE TABLE visits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            url VARCHAR(2000) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    echo "Table 'visits' créée.\n";

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
