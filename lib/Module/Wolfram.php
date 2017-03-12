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
        $term = trim($c->cmd_param);
        $url = 'http://api.wolframalpha.com/v2/query?' . http_build_query([
            'appid' => $this->wolfram_api_key,
            'format' => 'plaintext',
            'podindex' => '1,2,3',
            'input' => $term,
        ]);
        $pipeline = $this->esprintf(
            'lynx -stdin -dump -assume_charset=utf-8 -width=1024| ' .
            'php -r %s',
            'echo html_entity_decode(file_get_contents("php://stdin"), ENT_QUOTES | ENT_XML1);' // php
        );
        $this->curlCmd($url, $pipeline, function($res) use ($c, $term) {
            $c->hank->chat($c->server, $c->target, $this->_cyan . $res);
        });
    }
}
