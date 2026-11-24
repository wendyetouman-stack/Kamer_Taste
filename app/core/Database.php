<?php
class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $c = require APP . '/config/database.php';
            $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset={$c['charset']}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            // Base distante (cloud) qui exige une connexion chiffrée
            if (!empty($c['ssl'])) {
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                if (!empty($c['ssl_ca'])) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = $c['ssl_ca'];
                }
            }

            self::$instance = new PDO($dsn, $c['user'], $c['pass'], $options);
        }
        return self::$instance;
    }
}
