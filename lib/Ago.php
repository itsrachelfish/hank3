<?php
declare(strict_types=1);

trait Ago {
    private $_units = [
        12 * 30 * 24 * 60 * 60 => 'y',
        30 * 24 * 60 * 60      => 'mo',
        7  * 24 * 60 * 60      => 'wk',
        24 * 60 * 60           => 'd',
        60 * 60                => 'h',
        60                     => 'm',
        1                      => 's'
    ];
    function ago(int $now, int $ts_past): string {
        $elapsed = $now - $ts_past;
        if ($elapsed < 1) {
            return '1s';
        }
        $parts = [];
        foreach ($this->_units as $secs => $str) {
            $quotient = intdiv($elapsed, $secs);
            if ($quotient >= 1) {
                $parts[] = "{$quotient}{$str}";
                $elapsed -= $quotient * $secs;
            }
        }
        return implode(' ', $parts);
    }
    function fromAgo(string $ago): int {
        $map = array_flip($this->_units);
        $re = '/(\d+)(' . implode('|', array_keys($map)) . ')\b/';
        $match = [];
        $dur = 0;
        if (!preg_match_all($re, $ago, $match)) {
            return -1;
        }
        foreach ($match[1] as $i => $val) {
            $unit = $match[2][$i];
            $dur += $map[$unit] * (int)$val;
        }
        return $dur;
    }
}
