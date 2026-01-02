<?php
/**
 * Tracker de visites minimaliste
 * Enregistre l'URL complète (avec UTM, query params, etc.)
 * Échoue silencieusement si la DB n'existe pas
 */

header('Content-Type: image/gif');
header('Cache-Control: no-store');

// 1x1 transparent GIF
echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

$url = isset($_GET['url']) ? substr($_GET['url'], 0, 2000) : '';
if (!$url) exit;

// Ignorer les bots
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (preg_match('/bot|crawl|spider|slurp/i', $ua)) exit;

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=u971272190_bdd_micorp;charset=utf8mb4',
        'u971272190_micorp',
        '5+o8pTTB+U',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $stmt = $pdo->prepare('INSERT INTO visits (url) VALUES (?)');
    $stmt->execute([$url]);
} catch (Exception $e) {
    // Silencieux - le site continue de fonctionner
}
