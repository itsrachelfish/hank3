<?php
declare(strict_types=1);

trait HookCmd {
    function hookCmd(string $shell_cmd, callable $callback) {
        $timeout_s = 5;
        $cmd = 'bash -c ' . escapeshellarg($shell_cmd);
        weechat_hook_process($cmd, $timeout_s * 1000, function ($ig1, $ig2, $ig3, $res, $ig5) use ($callback) {
            $trimmed_res = trim($res);
            $retval = strlen($trimmed_res) !== 0
                ? call_user_func($callback, $trimmed_res)
                : null;
            return $retval === null ? WEECHAT_RC_OK : $retval;
        }, '');
    }
}
