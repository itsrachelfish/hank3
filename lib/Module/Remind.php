<?php
declare(strict_types=1);

class Module_Remind implements Module {
    use Strings;
    use Ago;

    private $max_dur = 86400 * 7;

    function help(): array {
        return [
            [ '?remind',  $this->_('<when> <what>', __CLASS__), $this->_('Send a reminder', __CLASS__) ],
            [ '?remindp', $this->_('<when> <what>', __CLASS__), $this->_('Send a reminder, private', __CLASS__) ],
        ];
    }

    function init($hank) {
        $hank->registerCommand('?remind',  function($c) { $this->doRemind($c, $private=false); });
        $hank->registerCommand('?remindp', function($c) { $this->doRemind($c, $private=true); });
    }

    function doRemind($c, bool $private) {
        $parts = preg_split('/\s+/', $c->cmd_param, 2);
        if (count($parts) < 2) {
            return;
        }
        if (preg_match('/^\d+$/', $parts[0])) {
            $parts[0] = "{$parts[0]}h";
        }
        $dur = $this->fromAgo($parts[0]);
        if ($dur < 0) {
            if ($ts_then = strtotime($parts[0], $c->ts)) {
                $dur = $ts_then - $c->ts;
            }
        }
        $c->hank->chat($c->server, $c->target, sprintf(
            "K, in %s" . ($private ? ', private' : ''),
            $this->ago($c->ts + $dur, $c->ts)
        ));
        $c->hank->chat(
            $c->server,
            $private ? $c->nick : $c->target,
            "{$c->nick}: {$parts[1]}",
            $dur
        );
    }
}
