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

$vercel = getenv('VERCEL') !== false || isset($_SERVER['VERCEL']);
$alt = static fn(string $cle, string $defaut): string => $vercel ? $env($cle, $defaut) : $defaut;

return [
    'host'    => $env('DB_HOST', $alt('HOST', 'localhost')),
    'port'    => $env('DB_PORT', $alt('PORT', '3306')),
    'name'    => $env('DB_NAME', $alt('DATABASE', 'restaurant_db')),
    'user'    => $env('DB_USER', $alt('USERNAME', 'root')),
    'pass'    => $env('DB_PASS', $alt('PASSWORD', '')),
    'charset' => 'utf8mb4',
    'ssl'     => $env('DB_SSL', '0') === '1',
    'ssl_ca'  => $env('DB_SSL_CA', ''),
];