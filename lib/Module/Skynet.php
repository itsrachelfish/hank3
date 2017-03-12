<?php
declare(strict_types=1);

class Module_Skynet implements Module {
    use CurlCmd;
    use HookCmd;
    use Esprintf;

    private $odds_unprovoked = 30;
    private $odds_shouting = 7;
    private $odds_provoked = 5;
    private $odds_ross = 3;
    private $youtube_api_key = 'AIzaSyAiYfOvXjvhwUFZ1VPn696guJcd2TJ-Lek';
    private $textblob_proc = null;
    private $textblob_pipes = [];

    function help(): array {
        return [];
    }

    function init($hank) {
        $hank->registerCatchall([ $this, 'maybeRespond' ]);
        $this->openTextblobProc();
    }

    function openTextblobProc() {
        $cmd = 'python -u ' . dirname(dirname(__DIR__)) . '/bin/noun_phrase.py';
        $spec = [ ['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w'] ];
        $this->textblob_proc = proc_open($cmd, $spec, $this->textblob_pipes);
    }

    function deinit() {
        fclose($this->textblob_pipes[0]);
        fclose($this->textblob_pipes[1]);
        fclose($this->textblob_pipes[2]);
        proc_close($this->textblob_proc);
    }

    function maybeRespond($c) {
        $odds = $this->odds_unprovoked;
        $respond_func = [ $this, 'respondRelevant' ];
        if ($is_shouting =
            strlen($c->text) > 16
            && strtolower($c->text) !== strtoupper($c->text)
            && $c->text == strtoupper($c->text)
        ) {
            $odds = $this->odds_shouting;
        } else if ($is_ross =
            preg_match('/^ross/i', $c->nick)
            || preg_match('/\bross\b/i', $c->text)
        ) {
            $odds = $this->odds_ross;
            $respond_func = [ $this, 'respondFurry' ];
        } else if ($is_provoked =
            preg_match('/\b' . preg_quote($c->my_nick, '/') . '\b/i', $c->text)
        ) {
            $odds = $this->odds_provoked;
            $respond_func = [ $this, 'respondSexy' ];
        }
        if (random_int(1, $odds) === 1) {
            call_user_func($respond_func, $c, function($topic) use ($c) {
                $this->getYoutubeComment($c, $topic);
            });
        }
    }

    function respondRelevant($c, $cb) {
        $r = null; $w = [ $this->textblob_pipes[0] ]; $e = null;
        if (stream_select($r, $w, $e, 0) < 1) {
            return; // write would block
        }
        fwrite($this->textblob_pipes[0], trim($c->text) . "\n");
        $r = [ $this->textblob_pipes[1] ]; $w = null; $e = null;
        if (stream_select($r, $w, $e, 0, 20 * 1000) < 1) {
            return; // read would block
        }
        $line = fgets($this->textblob_pipes[1]);
        $topic = $line ? trim($line) : null;
        if (!empty($topic)) {
            $cb($c, $topic);
        }
    }

    function getYoutubeComment($c, $topic) {
        $url = 'https://www.youtube.com/results?' . http_build_query([
            'search_query' => $topic
        ]);
        $pipeline = sprintf(
            'grep -Po %s | sort | uniq | shuf -n1 | xargs -rn1 -I@ ' .
            'curl -s "https://content.googleapis.com/youtube/v3/' .
            'commentThreads?part=snippet&maxResults=100&videoId=@&textFormat=' .
            'plainText&key=%s" | grep "textDisplay" | ' .
            'cut -d: -f2- | cut -c2- | sed %s | ' .
            "egrep -iv '(\+|#|@|:|vid|record|upload|stream|youtube|thank|watch|download)' | " .
            'shuf -n1 | json_decode -p | paste -sd" " | recode -f html..ascii',
            escapeshellarg('(?<=watch\?v=).{11}'), // grep
            $this->youtube_api_key, // key=
            escapeshellarg('s/,$//') // sed
        );
        $this->curlCmd($url, $pipeline, function($res) use ($c) {
            $c->hank->chat($c->server, $c->target, $res);
        });
    }

    function respondFurry($c, $cb) {
        $cb($c, array_rand([
            'anyfur',
            'furry anthro',
            'furry con',
            'furry convention',
            'furry cuddle',
            'furry equine',
            'furry huggle',
            'furry nuzzle',
            'furry pounce',
            'furry spooge',
            'furry tailwave',
            'furry vulpine',
            'furson',
            'fursona',
            'what is a furry',
            'yerf',
            'yiff',
            'yiffy',
        ]));
    }

    function respondSexy($c, $cb) {
        $cb($c, array_rand([
            'anal sex',
            'butt bang',
            'cunnilingus',
            'dick dong',
            'erotica taboo',
            'fellatio how to',
            'gender bending',
            'hormone treatment',
            'intercourse how to',
            'jizz how to',
            'kinky how to',
            'libido how to',
            'mating humans',
            'nipple tweaking',
            'oral sex how to',
            'perverted sex',
            'queer sex',
            'reproductive organs',
            'sex how to have',
            'transsexual tits',
            'unison orgasm',
            'virgin how to',
            'whoredom',
            'xxx sex',
            'youngster sex',
            'zebra sex',
        ]));
    }
}
