<?php
declare(strict_types=1);

class Module_Weather implements Module {
    use Db;
    use Strings;
    use CurlCmd;
    use HookCmd;
    use Attributes;

    private $db;
    private $weather_api_key = 'a446951688d7a1a155e17d3956005951';

    function help(): array {
        return [
            [ 'weather',  $this->_('<where>', __CLASS__), $this->_('Current weather', __CLASS__) ],
            [ 'forecast', $this->_('<where>', __CLASS__), $this->_('Forecast', __CLASS__) ],
        ];
    }

    function init($hank) {
        if ($db = $this->initDb()) {
            $this->db = $db;
            $hank->registerCommand('weather',  function($c) { $this->doWeather($c, 'weather'); });
            $hank->registerCommand('forecast', function($c) { $this->doWeather($c, 'forecast/daily'); });
        }
    }

    function doWeather($c, string $method) {
        $opts = $this->getOptsAndRemember($c);
        $opts['APPID'] = $this->weather_api_key;
        $url = "http://api.openweathermap.org/data/2.5/{$method}?" . http_build_query($opts);
        $this->curlCmd($url, 'cat', function($res) use ($c, $opts, $method) {
            $res = @json_decode($res, true);
            if (!$res || (empty($res['city']) && empty($res['name']))) {
                return;
            }
            $rows = [];
            if ($method === 'weather') {
                $rows[] = [
                    'ts' => $res['dt'] ?? 0,
                    'name' => $res['name'] ?? '?',
                    'temp_min' => $res['main']['temp_min'] ?? '?',
                    'temp_max' => $res['main']['temp_max'] ?? '?',
                    'humidity' => $res['main']['humidity'] ?? '?',
                    'wind' => $res['wind']['speed'] ?? '?',
                    'desc' => $res['weather'][0]['description'] ?? '?',
                ];
            } else if ($method === 'forecast/daily') {
                foreach ($res['list'] as $day) {
                    $rows[] = [
                        'ts' => $day['dt'] ?? 0,
                        'name' => $res['city']['name'] ?? '?',
                        'temp_min' => $day['temp']['min'] ?? '?',
                        'temp_max' => $day['temp']['max'] ?? '?',
                        'humidity' => $day['humidity'] ?? '?',
                        'wind' => $day['speed'] ?? '?',
                        'desc' => $day['weather'][0]['description'] ?? '?',
                    ];
                }
            }
            $temp_unit = "\xc2\xb0" . ($opts['units'] === 'imperial' ? 'F'   : 'C');
            $wind_unit =              ($opts['units'] === 'imperial' ? 'mph' : 'm/s');
            if (count($rows) === 1) {
                $row = current($rows);
                $c->hank->chat($c->server, $c->target, sprintf(
                    "%s: [{$this->_cyan}%s{$this->_reset}] " .
                    "[{$this->_blue}%s{$this->_reset} - " .
                    "{$this->_red}%s{$this->_reset}{$temp_unit}] " .
                    "[humid: {$this->_green}%s{$this->_reset}%%] " .
                    "[wind: {$this->_yellow}%s{$this->_reset}{$wind_unit}] " .
                    "[{$this->_magenta}%s{$this->_reset}]",
                    $c->nick,
                    $row['name'],
                    $row['temp_min'],
                    $row['temp_max'],
                    $row['humidity'],
                    $row['wind'],
                    $row['desc']
                ));
            } else {
                $c->hank->chat($c->server, $c->target, sprintf(
                    $this->_("%s: %s forecast", __CLASS__),
                    $c->nick,
                    $rows[0]['name']
                ));
                foreach ($rows as $row) {
                    $tz_name = "Etc/GMT" . ($opts['tz'] > 0 ? '+' : '') . $opts['tz'];
                    $dt = new DateTime('now', new DateTimeZone($tz_name));
                    $dt->setTimestamp((int)$row['ts']);
                    $c->hank->chat($c->server, $c->target, sprintf(
                        "%s: [{$this->_blue}%s{$this->_reset} - " .
                        "{$this->_red}%s{$this->_reset}{$temp_unit}] " .
                        "[humid: {$this->_green}%s{$this->_reset}%%] " .
                        "[wind: {$this->_yellow}%s{$this->_reset}{$wind_unit}] " .
                        "[{$this->_magenta}%s{$this->_reset}]",
                        $dt->format('D'),
                        $row['temp_min'],
                        $row['temp_max'],
                        $row['humidity'],
                        $row['wind'],
                        $row['desc']
                    ));
                }
            }
        });
    }

    function getOptsAndRemember($c): array {
        $default_opts = [
            'type' => 'zip',
            'zip' => '10013',
            'cnt' => 5,
            'units' => 'imperial',
            'tz' => -5
        ];
        $stored_opts = $this->getOptsFromDb($c) ?? [];
        $parsed_opts = [];

        $parts = preg_split('/\s+/', $c->cmd_param);
        $q = [];
        foreach ($parts as $k => $v) {
            if (preg_match('/^\d{5}$/', $v)) {
                $parsed_opts['zip'] = $v;
            } else if ($v === '-i') {
                $parsed_opts['units'] = 'imperial';
            } else if ($v === '-c') {
                $parsed_opts['units'] = 'metric';
            } else if (preg_match('/^-\d+$/', $v)) {
                $parsed_opts['cnt'] = (int)substr($v, 1);
            } else if (preg_match('/^-t[-]?\d+$/', $v)) {
                $parsed_opts['tz'] = max(-14, min(12, (int)substr($v, 2)));
            } else {
                $q[] = $v;
            }
        }
        $parsed_opts['q'] = implode(' ', $q);
        if (!empty($parsed_opts['q'])) {
            $parsed_opts['type'] = 'like';
            unset($parsed_opts['zip']);
        } else if (!empty($parsed_opts['zip'])) {
            $parsed_opts['type'] = 'zip';
            unset($parsed_opts['q']);
        } else {
            unset($parsed_opts['q']);
            unset($parsed_opts['zip']);
        }

        $opts = array_merge($default_opts, $stored_opts, $parsed_opts);

        if (empty($opts['type'])) {
            unset($opts['q']);
            unset($opts['zip']);
        } else if ($opts['type'] === 'zip') {
            unset($opts['q']);
        } else if ($opts['type'] === 'like') {
            unset($opts['zip']);
        }

        $this->storeOptsToDb($c, $opts);
        return $opts;
    }

    function getOptsFromDb($c) {
        $sql = 'select * from weather ' .
            'where server_channel = ? and nick = ?';
        $sql_in = [ $c->server_channel, $c->nick ];
        $stmt = $this->db->prepare($sql);
        $stmt->execute($sql_in);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? @json_decode($row['opts'], true) : null;
    }

    function storeOptsToDb($c, $opts) {
        $sql = 'insert or replace into weather ' .
            '(server_channel, nick, opts, ts) ' .
            'values (?, ?, ?, ?)';
        $sql_in = [
            $c->server_channel,
            $c->nick,
            json_encode($opts),
            $c->ts
        ];
        $stmt = $this->db->prepare($sql);
        $stmt->execute($sql_in);
    }
}
