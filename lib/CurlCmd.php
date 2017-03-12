<?php
declare(strict_types=1);

trait CurlCmd {
    abstract function hookCmd(string $shell_cmd, callable $callback);
    function curlCmd(string $url, string $pipeline, callable $callback, array $opt = []) {
        $user_agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36';
        $cmd = sprintf(
            "curl -s -L -A %s %s %s %s | %s",
            escapeshellarg($user_agent),
            !empty($opt['data']) ? ("-d " . escapeshellarg($opt['data'])) : '',
            escapeshellarg($url),
            !empty($opt['with_err']) ? '2>&1' : '',
            $pipeline
        );
        weechat_printf('', $cmd);
        $this->hookCmd($cmd, $callback);
    }
}
