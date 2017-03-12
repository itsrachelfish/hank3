<?php
declare(strict_types=1);

trait Esprintf {
    function esprintf(string $fmt, string ...$args) {
        return vsprintf($fmt, array_map('escapeshellarg', $args));
    }
}
