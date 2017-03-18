<?php
declare(strict_types=1);

class Module_Youtube implements Module {
    use Strings;
    use CurlCmd;
    use HookCmd;
    use Esprintf;

    function help(): array {
        return [
            [ '?yt', $this->_('<what>', __CLASS__), $this->_('Do a youtube search', __CLASS__) ],
        ];
    }

    function init($hank) {
        $hank->registerCommand('?yt', [ $this, 'doYoutube' ]);
    }

    function doYoutube($c) {
        $term = trim($c->cmd_param);
        $url = 'https://www.youtube.com/results?' . http_build_query([
            'search_query' => $term
        ]);
        $pipeline = $this->esprintf(
            'grep -Po %s | head -n1',
            '(?<=watch\?v=)[^"&<]+' // grep
        );
        $this->curlCmd($url, $pipeline, function($res) use ($c, $term) {
            $c->hank->chat($c->server, $c->target, sprintf(
                $this->_("Result for %s: http://youtu.be/%s", __CLASS__),
                $term,
                $res
            ));
            $c->cmd_func = null;
            $c->text = "http://youtu.be/{$res}";
            $c->hank->process($c);
        });
    }
}
