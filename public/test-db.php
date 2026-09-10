<?php
require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/autoload/local.php';
$dbConfig = $config['db'];

try {
    $pdo = new PDO($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password']);
    echo "✅ Connexion réussie à PostgreSQL !";
} catch (PDOException $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage();
}