<?php
declare(strict_types=1);

class Module_Image implements Module {
    use Strings;
    use CurlCmd;
    use HookCmd;
    use Esprintf;

    function help(): array {
        return [
            [ 'im',   $this->_('<what>', __CLASS__), $this->_("Return top image result", __CLASS__) ],
            [ 'ir',   $this->_('<what>', __CLASS__), $this->_("Return random image result", __CLASS__) ],
            [ 'gif',  $this->_('<what>', __CLASS__), $this->_("Return top gif result", __CLASS__) ],
            [ 'gifr', $this->_('<what>', __CLASS__), $this->_("Return random gif result", __CLASS__) ],
            [ 'tu',   $this->_('<what>', __CLASS__), $this->_("Return top tumblr image result", __CLASS__) ],
            [ 'tur',  $this->_('<what>', __CLASS__), $this->_("Return random tumblr image result", __CLASS__) ],
        ];
    }

    function init($hank) {
        $hank->registerCommand('im', function($c) {
            $this->findImage($c, 'head', '', [ 'site' => '', 'source' => 'hp' ]);
        });
        $hank->registerCommand('ir', function($c) {
            $this->findImage($c, 'shuf', '', [ 'site' => '', 'source' => 'hp' ]);
        });
        $hank->registerCommand('gif', function($c) {
            $this->findImage($c, 'head', '', [ 'tbs' => 'itp:animated' ]);
        });
        $hank->registerCommand('gifr', function($c) {
            $this->findImage($c, 'shuf', '', [ 'tbs' => 'itp:animated' ]);
        });
        $hank->registerCommand('tu', function($c) {
            $this->findImage($c, 'head', 'site:tumblr.com ', [ 'site' => '', 'source' => 'hp' ]);
        });
        $hank->registerCommand('tur', function($c) {
            $this->findImage($c, 'shuf', 'site:tumblr.com ', [ 'site' => '', 'source' => 'hp' ]);
        });
    }

    function findImage($c, $shuf_or_head, $prefix, $extra_args) {
        $term = trim($c->cmd_param);
        $url_args = array_merge([
            'tbm' => 'isch',
            'q' => $prefix . $term,
        ], $extra_args);
        $url = 'https://www.google.com/search?' . http_build_query($url_args);
        $pipeline = $this->esprintf(
            "grep -Po %s | grep -v photobucket | {$shuf_or_head} -n1 | " .
            "php -r 'echo @json_decode(fgets(STDIN));' | tr ' ' '+'",
            '(?<="ou":)".+?"(?=,"ow")' // grep
        );
        $this->curlCmd($url, $pipeline, function($res) use ($c, $term) {
            $c->hank->chat($c->server, $c->target, sprintf(
                $this->_("Result for %s: %s", __CLASS__),
                $term,
                $res
            ));
        });
    }
}
