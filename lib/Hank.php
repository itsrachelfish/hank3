<?php
declare(strict_types=1);

class Hank {
    use Strings;

    const PLUGIN_NAME = 'hank';
    const PLUGIN_AUTHOR = 'ceph';
    const PLUGIN_VERSION = '3.3.0';
    const PLUGIN_LICENSE = 'GPL3';
    const PLUGIN_CHARSET = '';
    const PLUGIN_DESC = 'A shitty IRC bot that abuses curl and other shell commands, and websites in general.';

    private $options = [
        'strings_path' => '',
    ];
    private $modules = [];
    private $command_callbacks = [];
    private $catchalls = [];

    function run() {
        $this->ensureWeechat();
        $this->registerPlugin();
        $this->setPluginOptions();
        $this->maybeLoadStrings();
        $this->initModules();
        $this->hookChat();
    }

    function ensureWeechat() {
        if (!function_exists('weechat_register')) {
            throw new RuntimeException('Expected weechat_register present');
        }
    }

    function registerPlugin() {
        if (!weechat_register(
            self::PLUGIN_NAME,
            self::PLUGIN_AUTHOR,
            self::PLUGIN_VERSION,
            self::PLUGIN_LICENSE,
            self::PLUGIN_DESC,
            [ $this, 'shutdownPlugin' ],
            self::PLUGIN_CHARSET
        )) {
            throw new RuntimeException('Expected weechat_register success');
        }
    }

    function setPluginOptions() {
        foreach ($this->options as $k => $v) {
            if (!weechat_config_is_set_plugin($k)) {
                weechat_config_set_plugin($k, $v);
            }
            $this->options[$k] = weechat_config_get_plugin($k);
        }
    }

    function maybeLoadStrings() {
        if (strlen($this->options['strings_path']) > 0) {
            $this->loadStrings($this->options['strings_path']);
        }
    }

    function initModules() {
        foreach ($this->getClasses('Module') as $class) {
            $module = new $class();
            $module->init($this);
            $this->modules[] = $module;
        }
    }

    function registerCommand(string $cmd_name, callable $callback) {
        $this->command_callbacks[$cmd_name] = $callback;
    }

    function registerCatchall(callable $callback) {
        $this->catchalls[] = $callback;
    }

    function getModules(): array {
        return $this->modules;
    }

    function hookChat() {
        weechat_hook_signal('*,irc_in2_privmsg', function ($callback_data, $source, $type_data, $signal_data) {
            $hank = $this;
            list($server, ) = explode(',', $source, 2);
            $info = weechat_info_get_hashtable('irc_message_parse', [ 'message' => $signal_data ]);
            $channel = $info['channel'];
            $nick = $info['nick'];
            $text = $info['text'];
            $server_channel = "$server,$channel";
            $my_nick = weechat_info_get('irc_nick', $server);
            $is_channel = $channel !== $my_nick;
            $target = $is_channel ? $channel : $nick;
            $buffer = weechat_info_get('irc_buffer', $server_channel);
            $ts = time();
            list($cmd_name, $cmd_func, $cmd_param) = $this->extractCommand($text);
            $context = (object)compact(
                'hank', 'server', 'channel', 'server_channel', 'nick', 'text',
                'target', 'my_nick', 'buffer', 'cmd_name', 'cmd_func', 'cmd_param',
                'ts', 'is_channel'
            );
            $this->process($context);
            return WEECHAT_RC_OK;
        }, '');
    }

    function process($c) {
        foreach ($this->catchalls as $catchall) {
            if (false === call_user_func($catchall, $c)) {
                // If catchall returns FALSE, stop processing
                return;
            }
        }
        if ($c->cmd_func) {
            call_user_func($c->cmd_func, $c);
        }
    }

    function chat(string $server, string $target, string $msg, $wait = null) {
        $lines = explode("\n", $msg);
        $wait_cmd = $wait ? "/wait {$wait} " : '';
        foreach ($lines as $line) {
            weechat_command('', "{$wait_cmd}/msg -server {$server} {$target} {$line}");
        }
    }

    function extractCommand(string $text): array {
        $cmd_pieces = preg_split('/\s+/', $text, 2);
        $cmd_name = $cmd_pieces[0];
        $cmd_param = $cmd_pieces[1] ?? '';
        if (isset($this->command_callbacks[$cmd_name])) {
            return [$cmd_name, $this->command_callbacks[$cmd_name], $cmd_param];
        }
        return [null, null, null];
    }

    function getClasses(string $subdir = ''): array {
        $cmd_fmt = "find %s -type f -name '*.php'";
        $cmd = sprintf($cmd_fmt, escapeshellarg(__DIR__ . "/{$subdir}"));
        $output = [];
        $exit_code = 1;
        exec($cmd, $output, $exit_code);
        if ($exit_code !== 0) {
            throw new RuntimeException('Expected find exit_code=0');
        }
        $classes = array_map(function(string $path): string {
            // Map path to class name
            $rel_path = substr($path, strlen(__DIR__) + 1);
            $rel_path_minus_ext = substr($rel_path, 0, -1 * strlen('.php'));
            return str_replace('/', '_', $rel_path_minus_ext);
        }, array_filter($output));
        sort($classes);
        return $classes;
    }

    function shutdownPlugin() {
        foreach ($this->modules as $module) {
            if (method_exists($module, 'deinit')) {
                $module->deinit();
            }
        }
        foreach ($this->getClasses() as $class) {
            forget_class($class);
        }
    }
}
