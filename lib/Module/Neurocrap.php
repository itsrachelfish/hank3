<?php
declare(strict_types=1);

class Module_Neurocrap implements Module {
    use Strings;
    use Attributes;

    function help(): array {
        return [
            [ '>dunno',   '',                            $this->_('Print a face', __CLASS__) ],
            [ '>downy',   '',                            $this->_('Print another face', __CLASS__) ],
            [ '>intense', $this->_('<what>', __CLASS__), $this->_('Intense a thing', __CLASS__) ],
        ];
    }

    function __construct() {
        $this->crap = [
            '>dunno' => function($c) {
                return $this->_lightgreen . ([
                    "\xc2\xaf\x5c_\x28\xe3\x82\xb7\x29_\x2f\xc2\xaf",
                    "\xc2\xaf\x5c\x28\xc2\xba_o\x29\x2f\xc2\xaf",
                    "\xe2\x80\xbe\x5c\x28\xe3\x83\x84\x29\x2f\xe2\x80\xbe",
                ][random_int(0, 2)]) . $this->_reset;
            },
            '>downy' => function($c) {
                return $this->_lightgreen .
                    ".\x27{$this->_underline}\x2f{$this->_underline}\x29" .
                    $this->_reset;
            },
            '>intense' => function($c) {
                return sprintf(
                    "{$this->_bold}[%s intensifies]{$this->_reset}",
                    trim($c->cmd_param)
                );
            },
        ];
    }

    function init($hank) {
        foreach ($this->crap as $cmd => $func) {
            $hank->registerCommand($cmd, function($c) use ($func) {
                $reply = $func($c);
                $c->hank->chat($c->server, $c->target, $reply);
            });
        }
    }
}
