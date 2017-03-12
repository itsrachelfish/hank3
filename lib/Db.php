<?php
declare(strict_types=1);

trait Db {
    private $db;
    function initDb() {
        $db_path = dirname(__DIR__) . '/hank.db';
        if (!file_exists($db_path)) {
            shell_exec("sqlite3 {$db_path} < {$db_path}.sql");
        }
        $this->db = new PDO("sqlite:{$db_path}");
        return $this->db;
    }
}
