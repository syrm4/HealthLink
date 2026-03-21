<?php
// HealthLink — Database connection
// Configured for MAMP on macOS (default port 8889, user root, password root)
//
// To override any value, set the corresponding environment variable:
//   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS

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
                . 'Expected: <strong>MAMP macOS</strong> — port 8889, user root, password root<br><br>'
                . 'Check that MAMP is running and Apache + MySQL are both green.<br>'
                . 'Override with environment variables: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS'
                . '</div>');
        }
    }
    return $pdo;
}
