<?php
// Lit d'abord les variables d'environnement (Vercel), sinon les valeurs locales de XAMPP.
// Aucun mot de passe n'est écrit ici : ce fichier peut être publié sur GitHub.
$env = static function (string $cle, string $defaut = ''): string {
    $v = getenv($cle);
    if ($v === false) {
        $v = $_SERVER[$cle] ?? $_ENV[$cle] ?? false;
    }
    return $v === false ? $defaut : (string) $v;
};

return [
    'host'    => $env('DB_HOST', 'localhost'),
    'port'    => $env('DB_PORT', '3306'),
    'name'    => $env('DB_NAME', 'restaurant_db'),
    'user'    => $env('DB_USER', 'root'),
    'pass'    => $env('DB_PASS', ''),
    'charset' => 'utf8mb4',
    'ssl'     => $env('DB_SSL', '0') === '1',
    'ssl_ca'  => $env('DB_SSL_CA', ''),
];
