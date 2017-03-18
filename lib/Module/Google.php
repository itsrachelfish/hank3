<?php
declare(strict_types=1);

class Module_Google implements Module {
    use Strings;
    use CurlCmd;
    use HookCmd;
    use Esprintf;

    function help(): array {
        return [
            [ 'g', $this->_('<what>', __CLASS__), $this->_('Do a google search', __CLASS__) ],
        ];
    }

    function init($hank) {
        $hank->registerCommand('g', [ $this, 'doGoogle' ]);
    }

    function doGoogle($c) {
        $term = trim($c->cmd_param);
        $url_args = [
            'site' => '',
            'source' => 'hp',
            'q' => $term,
        ];
        $url = 'https://www.google.com/search?' . http_build_query($url_args);
        $pipeline = $this->esprintf(
            'lynx -stdin -dump | grep -Po %s | grep -Po %s | grep -Pv %s | ' .
            "php -r 'echo urldecode(urldecode(fgets(STDIN)));' | tr ' ' '+'",
            '\d+\.\s+https?://\S+', // grep
            'https?://\S+', // grep
            '(google|w3.org|schema.org)' // grep
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
