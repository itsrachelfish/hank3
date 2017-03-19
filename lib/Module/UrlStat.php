<?php
declare(strict_types=1);

class Module_UrlStat implements Module {
    use CurlCmd;
    use HookCmd;
    use Esprintf;
    use Attributes;

    function help(): array {
        return [];
    }

    function init($hank) {
        $hank->registerCatchall([ $this, 'maybeCurl' ]);
    }

    function maybeCurl($c) {
        $url_re = '@https?://\S+@';
        $match = [];
        if (!preg_match($url_re, $c->text, $match)) {
            // No URL in text
            return;
        }
        $url = $match[0];
        $file_re = '@(?<!/)/[^/]*?\\.(?!html|htm)[a-z0-9]{3,4}$@';
        if (preg_match($file_re, $url, $m)) {
            // URL looks like a file
            return;
        }
        $piping = $this->esprintf(
            'tr "\n" " " | grep -Po %s | head -n1 | recode -f html..ascii',
            '(?<=<title>).+?(?=</title>)'
        );
        $this->curlCmd($url, $piping, function($res) use ($c) {
            $c->hank->chat($c->server, $c->target, sprintf(
                "[ {$this->_cyan}%s{$this->_reset} ]",
                $res
            ));
        });
    }
}
