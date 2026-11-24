<?php
define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');

require APP . '/config/config.php';
require APP . '/helpers/functions.php';

spl_autoload_register(function (string $class) {
    foreach (['core', 'controllers', 'controllers/admin', 'models'] as $dir) {
        $file = APP . "/$dir/$class.php";
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

// Sur Vercel (serverless), les fichiers de session ne sont pas partagés entre les requêtes :
// on range donc les sessions dans la base de données.
$surVercel = getenv('VERCEL') !== false || isset($_SERVER['VERCEL']);
if ($surVercel || getenv('SESSION_DRIVER') === 'db') {
    session_set_save_handler(new DbSessionHandler(), true);
}
ini_set('session.gc_maxlifetime', '86400');   // 24 h

// Cookie de session plus sûr (inaccessible au JavaScript, envoyé en HTTPS si disponible)
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// En-têtes de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Erreur imprévue : le visiteur voit une page propre, le détail va dans les logs
set_exception_handler(function (Throwable $e) {
    error_log(get_class($e) . ' : ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ')');

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code(500);
    }

    // En local (APP_DEBUG = true) on affiche le détail de l'erreur
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo '<pre style="padding:20px;white-space:pre-wrap">' . htmlspecialchars((string) $e) . '</pre>';
        return;
    }

    $accueil = htmlspecialchars(defined('BASE_URL') ? BASE_URL : '/');
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>Erreur</title></head>'
       . '<body style="font-family:Arial,sans-serif;text-align:center;padding:60px 20px">'
       . '<h1>Oups, une erreur est survenue</h1>'
       . '<p>Le problème a été enregistré. Veuillez réessayer dans un instant.</p>'
       . '<p><a href="' . $accueil . '">Retour à l\'accueil</a></p>'
       . '</body></html>';
});

ob_start();
(new Router())->dispatch();
ob_end_flush();
