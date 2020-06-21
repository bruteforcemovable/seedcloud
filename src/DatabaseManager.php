<?php
namespace SeedCloud;

class DatabaseManager
{
    protected static $connectionHandle;

    public static function GetHandle()
    {
        if (self::$connectionHandle) {
            return self::$connectionHandle;
        }

        $connection = new \PDO(
            "mysql:dbname=" . ConfigManager::GetConfiguration('database.database') . ";host=" . ConfigManager::GetConfiguration('database.host'),
            ConfigManager::GetConfiguration('database.username'),
            ConfigManager::GetConfiguration('database.password'));

        self::$connectionHandle = $connection;
        return $connection;
    }
}
