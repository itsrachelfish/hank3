<?php
declare(strict_types=1);

class Module_Twitter implements Module {
    use Strings;
    use CurlCmd;
    use HookCmd;
    use Esprintf;

    function help(): array {
        return [
            [ 'tw', $this->_('<what>', __CLASS__), $this->_("Return top twitter search result", __CLASS__) ],
            [ 'tr', $this->_('<what>', __CLASS__), $this->_("Return random twitter search result", __CLASS__) ],
        ];
    }

    function init($hank) {
        $hank->registerCommand('tw', function($c) { $this->findTweet($c, 'head'); });
        $hank->registerCommand('tr', function($c) { $this->findTweet($c, 'shuf'); });
    }

    function findTweet($c, $shuf_or_head) {
        $term = trim($c->cmd_param);
        $url = 'https://twitter.com/search?' . http_build_query([
            'f' => 'realtime',
            'q' => $term,
        ]);
        $pipeline = $this->esprintf(
            'grep -Po %s | sed -e %s | grep -Pv %s | ' .
            "recode -f html..ascii | {$shuf_or_head} -n1 ",
            '(?<=data-aria-label-part="0">).*?(?=</p>)', // grep
            's/<[^>]*>//g', // sed
            '^\s*$' // grep
        );
        $this->curlCmd($url, $pipeline, function($res) use ($c, $term) {
            $c->hank->chat($c->server, $c->target, $res);
        });
    }
}
