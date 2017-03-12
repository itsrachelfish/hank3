<?php
declare(strict_types=1);

class Module_Tell implements Module {
    use Strings;
    use Db;
    use Ago;
    use Attributes;

    function help(): array {
        return [
            [ '?tell', $this->_('<who> <what>', __CLASS__), $this->_('Leave a message', __CLASS__) ],
        ];
    }

    function init($hank) {
        if ($this->initDb()) {
            $hank->registerCommand('?tell', [$this, 'queue']);
            $hank->registerCatchall([$this, 'maybeDequeue']);
        }
    }

    function queue($c) {
        if (!$c->is_channel) {
            return;
        }
        $nick_msg = preg_split('/\s+/', $c->cmd_param, 2);
        if (count($nick_msg) !== 2) {
            return;
        }
        $sql = 'insert into tell ' .
            '(server_channel, nick_to, nick_from, msg, ts) ' .
            'values (?, ?, ?, ?, ?)';
        $sql_in = [
            $c->server_channel,
            $nick_msg[0],
            $c->nick,
            $nick_msg[1],
            $c->ts
        ];
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute($sql_in)) {
            $c->hank->chat(
                $c->server, $c->target,
                $this->_("Got it", __CLASS__)
            );
        }
    }

    function maybeDequeue($c) {
        $sql = 'select * from tell ' .
            'where server_channel = ? and nick_to = ?';
        $sql_in = [ $c->server_channel, $c->nick ];
        $stmt = $this->db->prepare($sql);
        $stmt->execute($sql_in);
        $n = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ago = $this->ago((int)$c->ts, (int)$row['ts']);
            $c->hank->chat(
                $c->server, $c->target, sprintf(
                "%s: [{$this->_cyan}%s{$this->_reset}] " .
                "[{$this->_red}%s{$this->_reset}] " .
                "[{$this->_green}%s ago{$this->_reset}]",
                $c->nick,
                $row['msg'],
                $row['nick_from'],
                $ago
            ));
            $n += 1;
        }
        if ($n > 0) {
            $sql = 'delete from tell ' .
                'where server_channel = ? and nick_to = ?';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($sql_in);
        }
    }
}
