<?php
require_once VENDOR_DIR . "/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
$dotenv->load();

function getDatabaseConnection()
{
    static $connection = null;

    if ($connection === null) {
        try {
            $connection = new PDO(
                'pgsql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'],
                $_ENV['DB_USER'],
                $_ENV['DB_PWD']
            );
        } catch (PDOException $e) {
            echo "Error de conexión! ";
            print_r($e->getMessage());
            exit();
        }
    }
    return $connection;
}