<?php
// Adapte BASE_URL à ton environnement (sans slash final)
// Serveur PHP intégré : http://127.0.0.1:8000
// XAMPP/WAMP        : http://localhost:8080/restaurant-ecommerce/public
define('BASE_URL', getenv('APP_BASE_URL') ?: 'http://127.0.0.1:8000');
define('APP_NAME', 'Kamer Taste');

// Affichage des erreurs (à désactiver en production)
define('APP_DEBUG', getenv('APP_DEBUG') !== 'false');
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
define('FRAIS_LIVRAISON', 1000);   // en FCFA
date_default_timezone_set('Africa/Douala');

// Identifiants Fapshi : ne jamais les écrire dans le JavaScript ou les commits.
// Exemple sous Linux : export FAPSHI_API_USER="..." && export FAPSHI_API_KEY="..."
define('FAPSHI_API_BASE', rtrim(getenv('FAPSHI_API_BASE') ?: 'https://sandbox.fapshi.com', '/'));
define('FAPSHI_API_USER', getenv('FAPSHI_API_USER') ?: '');
define('FAPSHI_API_KEY', getenv('FAPSHI_API_KEY') ?: '');
define('FAPSHI_WEBHOOK_SECRET', getenv('FAPSHI_WEBHOOK_SECRET') ?: '');