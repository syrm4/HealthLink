<?php
// HealthLink — Database connection + base path
//
// MAMP macOS defaults:  port 8889, user root, password root
// MAMP Windows defaults: port 3306, user root, password root
//
// BASE_PATH: set this to the subfolder HealthLink lives in.
// e.g. if served from localhost:8888/HealthLink/ set to '/HealthLink'
// e.g. if served from localhost/ (webroot) set to ''

define('BASE_PATH', '/HealthLink');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '8889';   // MAMP macOS default
        $name = getenv('DB_NAME') ?: 'healthlink';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: 'root';
        try {
            $pdo = new PDO(
                "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:20px;color:red;">'
                . '<strong>Database connection failed.</strong><br><br>'
                . htmlspecialchars($e->getMessage()) . '<br><br>'
                . '<strong>MAMP macOS:</strong> port 8889, user root, password root<br>'
                . '<strong>MAMP Windows:</strong> port 3306, user root, password root<br><br>'
                . 'Override with environment variables: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS'
                . '</div>');
        }
    }
    return $pdo;
}
