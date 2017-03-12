<?php
declare(strict_types=1);

trait Attributes {
    // See https://weechat.org/files/doc/stable/weechat_user.en.html#command_line_colors
    private $_reset         = "\x0f";
    private $_underline     = "\x1f";
    private $_bold          = "\x02";
    private $_white         = "\x0300";
    private $_black         = "\x0301";
    private $_blue          = "\x0302";
    private $_green         = "\x0303";
    private $_lightred      = "\x0304";
    private $_red           = "\x0305";
    private $_magenta       = "\x0306";
    private $_brown         = "\x0307";
    private $_yellow        = "\x0308";
    private $_lightgreen    = "\x0309";
    private $_cyan          = "\x0310";
    private $_lightcyan     = "\x0311";
    private $_lightblue     = "\x0312";
    private $_lightmagenta  = "\x0313";
    private $_darkgray      = "\x0314";
    private $_gray          = "\x0315";
    private $_color_white         = "00";
    private $_color_black         = "01";
    private $_color_blue          = "02";
    private $_color_green         = "03";
    private $_color_lightred      = "04";
    private $_color_red           = "05";
    private $_color_magenta       = "06";
    private $_color_brown         = "07";
    private $_color_yellow        = "08";
    private $_color_lightgreen    = "09";
    private $_color_cyan          = "10";
    private $_color_lightcyan     = "11";
    private $_color_lightblue     = "12";
    private $_color_lightmagenta  = "13";
    private $_color_darkgray      = "14";
    private $_color_gray          = "15";
}
