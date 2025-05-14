<?php
namespace Database;

use PDO;
use PDOException;
use cUtils\cUtils;

class Database {

    private static $DB_HOST;
    private static $DB_NAME;
    private static $DB_USER;
    private static $DB_PASS;

    private static $connection;

    private static function init() {
        if (self::$DB_HOST === null) {
            self::$DB_HOST = cUtils::config('DB_HOST');
            self::$DB_NAME = cUtils::config('DB_NAME');
            self::$DB_USER = cUtils::config('DB_USER');
            self::$DB_PASS = cUtils::config('DB_PASS');
        }
    }

    public static function getConnection() {
        self::init(); // Ensure config is loaded
        if (self::$connection === null) {
            try {
                self::$connection = new PDO(
                    "mysql:host=" . self::$DB_HOST . ";dbname=" . self::$DB_NAME,
                    self::$DB_USER,
                    self::$DB_PASS
                );
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $exception) {
                echo "Connection error: " . $exception->getMessage();
            }
        }
        return self::$connection;
    }
}
