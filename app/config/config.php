<?php
// Adapte BASE_URL à ton environnement (sans slash final)
// Serveur PHP intégré : http://127.0.0.1:8000
// XAMPP/WAMP        : http://localhost:8080/restaurant-ecommerce/public

define('APP_NAME', 'Kamer Taste');

// Affichage des erreurs (à désactiver en production)
$baseUrl = getenv('BASE_URL') ?: ($_SERVER['BASE_URL'] ?? '');
if ($baseUrl === '') {
    $surVercel = getenv('VERCEL') !== false || isset($_SERVER['VERCEL']);
    $baseUrl = $surVercel
        ? 'https://' . ($_SERVER['HTTP_HOST'] ?? 'kamertaste.vercel.app')
        : 'http://localhost:8080/restaurant-ecommerce/public';
}
define('BASE_URL', rtrim($baseUrl, '/'));
define('APP_DEBUG', true || getenv('VERCEL') || isset($_SERVER['VERCEL']));
ini_set('display_errors',  '1');
ini_set('log_errors', '1');
error_reporting(E_ALL);
define('FRAIS_LIVRAISON', 1000);   // en FCFA
date_default_timezone_set('Africa/Douala');

// Identifiants Fapshi : ne jamais les écrire dans le JavaScript ou les commits.
// Exemple sous Linux : export FAPSHI_API_USER="..." && export FAPSHI_API_KEY="..."
define('FAPSHI_API_BASE', rtrim(getenv('FAPSHI_API_BASE') ?: 'https://sandbox.fapshi.com', '/'));
define('FAPSHI_API_USER', getenv('FAPSHI_API_USER') ?: 'da8d58a6-6576-4ba4-a839-54764e407112');
define('FAPSHI_API_KEY', getenv('FAPSHI_API_KEY') ?: 'FAK_TEST_81c15eed673a97a8004e');
define('FAPSHI_WEBHOOK_SECRET', getenv('FAPSHI_WEBHOOK_SECRET') ?: '');
