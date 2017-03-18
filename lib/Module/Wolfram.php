<?php
declare(strict_types=1);

class Module_Wolfram implements Module {
    use Strings;
    use CurlCmd;
    use HookCmd;
    use Esprintf;
    use Attributes;

    private $wolfram_api_key = 'VTRKXL-A9P5Y769P5';

    function help(): array {
        return [
            [ '?wa', $this->_('<what>', __CLASS__), $this->_('Do a wolfram search', __CLASS__) ],
        ];
    }

    function init($hank) {
        $hank->registerCommand('?wa', [ $this, 'doWolfram' ]);
    }

    function doWolfram($c) {
        $nlines = 2;
        $parts = preg_split('/\s+/', $c->cmd_param, 2);
        if (preg_match('/-\d+/', $parts[0])) {
            $nlines = (int)substr($parts[0], 1);
            $parts = array_slice($parts, 1);
        }
        $term = trim(implode(' ', $parts));
        $url = 'http://api.wolframalpha.com/v2/query?' . http_build_query([
            'appid' => $this->wolfram_api_key,
            'format' => 'plaintext',
            'podindex' => '1,2,3',
            'input' => $term,
        ]);
        $pipeline = $this->esprintf(
            'lynx -stdin -dump -assume_charset=utf-8 -width=1024 | ' .
            'php -r %s | head -n %s',
            'echo html_entity_decode(file_get_contents("php://stdin"), ENT_QUOTES | ENT_XML1);', // php
            (string)$nlines
        );
        $this->curlCmd($url, $pipeline, function($res) use ($c) {
            $lines = array_values(array_filter(explode("\n", $res)));
            if (empty($lines[1])) {
                return;
            }
            $c->hank->chat($c->server, $c->target, trim(sprintf(
                "[%sWolfram%s] %s%s%s = %s%s%s\n%s",
                $this->_lightred,
                $this->_reset,
                $this->_cyan,
                $lines[0] ?? '',
                $this->_reset,
                $this->_lightblue,
                $lines[1] ?? '',
                $this->_reset,
                implode("\n", array_slice($lines, 2))
            )));
        });
    }
}
