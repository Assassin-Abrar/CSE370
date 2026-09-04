<?php
// PDO connection singleton
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host = 'localhost';
        $dbname = 'bracu_cms';
        $user = 'root';
        $pass = '';
        try {
            $pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed. Make sure MySQL is running and the "bracu_cms" database is imported. (' . $e->getMessage() . ')', 0, $e);
        }
    }
    return $pdo;
}
