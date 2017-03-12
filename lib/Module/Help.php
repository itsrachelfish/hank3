<?php
declare(strict_types=1);

class Module_Help implements Module {
    use Strings;
    use Attributes;

    function help(): array {
        return [
            [ '?help', '',                           $this->_('Show help for everything', __CLASS__) ],
            [ '?help', $this->_('<cmd>', __CLASS__), $this->_('Show cmd help', __CLASS__) ],
        ];
    }

    function init($hank) {
        $hank->registerCommand('?help', function($c) {
            list($help_list, $help_map) = $this->getHelpEntries($c->hank);
            $cmd_name = trim($c->cmd_param);
            if (empty($cmd_name)) {
                if ($c->target !== $c->nick) {
                    $c->hank->chat($c->server, $c->target, sprintf(
                        $this->_("%s: Check PM", __CLASS__),
                        $c->nick
                    ));
                }
                $c->hank->chat(
                    $c->server, $c->nick,
                    $this->getAllHelp($help_list)
                );
            } else if (isset($help_map[$cmd_name])) {
                $c->hank->chat(
                    $c->server, $c->target,
                    $this->getHelp($help_map[$cmd_name])
                );
            } else {
                $c->hank->chat(
                    $c->server, $c->target,
                    $this->_("Command not found", __CLASS__)
                );
            }
        });
    }

    function getHelpEntries($hank): array {
        static $help_list = [];
        static $help_map = [];
        if (empty($help_list)) {
            foreach ($hank->getModules() as $module) {
                $help_tuples = $module->help();
                foreach ($help_tuples as $help_tuple) {
                    $help_map[$help_tuple[0]] = $help_tuple;
                }
                $help_list = array_merge($help_list, $help_tuples);
            }
            ksort($help_map);
            usort($help_list, function($a, $b) { return strcmp($a[0], $b[0]); });
        }
        return [ $help_list, $help_map ];
    }

    function getAllHelp(array $help_list): string {
        $maxw_func_gen = function(int $col) {
            return function($maxw, $help_tuple) use ($col) {
                return max(strlen($help_tuple[$col]), $maxw);
            };
        };
        $maxw_cmd_name = array_reduce($help_list, $maxw_func_gen(0));
        $maxw_cmd_args = array_reduce($help_list, $maxw_func_gen(1));
        $fmt = ">   {$this->_lightgreen}%{$maxw_cmd_name}s{$this->_reset}" .
            "  {$this->_green}%-{$maxw_cmd_args}s{$this->_reset}" .
            "  %s";
        $help = $this->_("Help", __CLASS__) . ":\n";
        foreach ($help_list as $help_tuple) {
            $help .= sprintf($fmt, $help_tuple[0], $help_tuple[1], $help_tuple[2]) . "\n";
        }
        return rtrim($help);
    }

    function getHelp(array $help_tuple): string {
        return "{$this->_lightgreen}{$help_tuple[0]}{$this->_reset}" .
            "  {$this->_green}{$help_tuple[1]}{$this->_reset}" .
            "  {$help_tuple[2]}";
    }
}
