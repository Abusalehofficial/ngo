<?php
// Secure PDO configuration
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        $host = 'localhost';
        $dbname = 'infozfd8_ngo';
        $username = 'infozfd8_ngo';
        $password = 'infozfd8_ngo';
        $charset = 'utf8mb4';
        
        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Real prepared statements
        ];
        
        try {
            $pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('Database connection error. Please contact support.');
        }
    }
    
    return $pdo;
}