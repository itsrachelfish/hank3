<?php
declare(strict_types=1);

spl_autoload_register(function(string $class) {
    $class_path = str_replace('_', '/', $class);
    require dirname(__DIR__) . '/lib/' . $class_path . '.php';
});

set_exception_handler(function(Throwable $e) {
    // Print to main weechat buffer if available, otherwise stderr
    $msg = "Uncaught {$e}\n";
    if (function_exists('weechat_printf')) {
        weechat_printf('', $msg);
    } else {
        fwrite(STDERR, $msg);
    }
});

set_error_handler(function($errno, $errmsg, $file, $line): bool {
    // Throw an ErrorException
    throw new ErrorException($errmsg, 0, $errno, $file, $line);
});
