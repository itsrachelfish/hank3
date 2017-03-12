<?php
declare(strict_types=1);

class Module_YtStat implements Module {
    use CurlCmd;
    use HookCmd;
    use Attributes;

    private $youtube_api_key = 'AIzaSyAiYfOvXjvhwUFZ1VPn696guJcd2TJ-Lek';

    function help(): array {
        return [];
    }

    function init($hank) {
        $hank->registerCatchall([ $this, 'maybeRespond' ]);
    }

    function maybeRespond($c) {
        $re = '@(youtu.be/|youtube.com/watch.*?v=)([A-Za-z0-9_-]+)@';
        $match = [];
        if (!preg_match($re, $c->text, $match)) {
            return;
        }
        $id = $match[2];
        $url = 'https://www.googleapis.com/youtube/v3/videos?' . http_build_query([
            'key' => $this->youtube_api_key,
            'id' => $id,
            'part' => 'snippet,statistics,id,contentDetails',
            'textFormat' => 'plainText',
        ]);
        $this->curlCmd($url, 'cat', function($res) use ($c) {
            $jres = json_decode($res, true);
            $item = $jres['items'][0] ?? null;
            if (!$item) {
                return;
            }
            $match = [];
            $duration = $item['contentDetails']['duration'];
            if (preg_match_all('/\d+[A-Z]/', $duration, $match)) {
                // https://en.wikipedia.org/wiki/ISO_8601#Durations
                $dur_formatted = strtolower(implode('', $match[0]));
            } else {
                $dur_formatted = $duration;
            }
            $c->hank->chat($c->server, $c->target, sprintf(
                "{$this->_black},{$this->_color_white}You" .
                "{$this->_white},{$this->_color_red}Tube{$this->_reset} " .
                "[{$this->_cyan}%s{$this->_reset}] " .
                "[{$this->_gray}%s{$this->_reset}] " .
                "[{$this->_yellow}%s{$this->_reset}] " .
                "[{$this->_green}%s{$this->_reset}|" .
                "{$this->_lightred}%s{$this->_reset}]",
                $item['snippet']['title'],
                $dur_formatted,
                number_format((float)$item['statistics']['viewCount']),
                number_format((float)$item['statistics']['likeCount']),
                number_format((float)$item['statistics']['dislikeCount'])
            ));
        });
    }
}
