<?php
// HealthLink — Database connection
// Reads environment variables if set, otherwise falls back to MAMP defaults.
//
// MAMP defaults:
//   Host:     localhost
//   Port:     8889
//   User:     root
//   Password: root
//   DB name:  healthlink
//
// To override, set environment variables:
//   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '8889';
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
                . 'Check that MAMP is running and MySQL is on port <strong>8889</strong>.<br>'
                . 'Default credentials: user <strong>root</strong>, password <strong>root</strong>.<br>'
                . 'To override, set DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS environment variables.'
                . '</div>');
        }
    }
    return $pdo;
}
