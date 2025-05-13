<?php
namespace Database;

use PDO;
use PDOException;
use cUtils\cUtils;

class Database {

    static $DB_HOST = cUtils::config('DB_HOST');
    static $DB_NAME = cUtils::config('DB_NAME');
    static $DB_USER = cUtils::config('DB_USER');
    static $DB_PASS = cUtils::config('DB_PASS');

    static $connection;

    public static function getConnection() {
        self::$connection = null;
        try {
            self::$connection = new PDO("mysql:host=" . self::$DB_HOST . ";dbname=" . self::$DB_NAME, self::$DB_USER, self::$DB_PASS);
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return self::$connection;
    }
}