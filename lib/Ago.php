<?php
declare(strict_types=1);

trait Ago {
    function ago(int $now, int $ts): string {
        $elapsed = $now - $ts;
        if ($elapsed < 1) {
            return '1s';
        }
        $unit = [
            12 * 30 * 24 * 60 * 60 => 'y',
            30 * 24 * 60 * 60      => 'mo',
            7  * 24 * 60 * 60      => 'wk',
            24 * 60 * 60           => 'd',
            60 * 60                => 'h',
            60                     => 'm',
            1                      => 's'
        ];
        $parts = [];
        foreach ($unit as $secs => $str) {
            $quotient = intdiv($elapsed, $secs);
            if ($quotient >= 1) {
                $parts[] = "{$quotient}{$str}";
                $elapsed -= $quotient * $secs;
            }
        }
        return implode(' ', $parts);
    }
}
