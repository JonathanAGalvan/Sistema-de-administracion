<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'srv737.hstgr.io';
$db   = 'u102381755_sistemaInve';
$user = 'u102381755_sistema';
$pass = 'Os5aVGSRL+';
$charset = 'utf8';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // Conexión silenciosa para no romper respuestas JSON
} catch (\PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}
?>