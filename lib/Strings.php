<?php
declare(strict_types=1);

trait Strings {
    static $strings = [];
    function loadStrings(string $path) {
        if (!file_exists($path)) {
            throw new RuntimeException("Strings path '{$path}' does not exist");
        }
        require $path;
        Strings::$strings = $strings;
    }
    function _(string $str, string $namespace): string {
        $key = "{$namespace}:{$str}";
        return Strings::$strings[$key] ?? $str;
    }
}
