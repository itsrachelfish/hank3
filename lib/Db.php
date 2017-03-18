<?php
declare(strict_types=1);

trait Db {
    static $_db;
    function initDb() {
        if (!Db::$_db) {
            $db_path = dirname(__DIR__) . '/hank.db';
            if (!file_exists($db_path)) {
                shell_exec("sqlite3 {$db_path} < {$db_path}.sql");
            }
            Db::$_db = new PDO("sqlite:{$db_path}");
        }
        return Db::$_db;
    }
}
