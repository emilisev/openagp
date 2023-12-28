<?php

namespace App\Helpers;

class StringToColor {

    public function handle($_string) {
        return $this->hsl2rgb($this->getHue($_string) / 0xFFFFFFFF, 0.6, 1);
    }

    private function getHue($_string) {
        return unpack('L', hash('adler32', $_string, true))[1];
    }

    private function hsl2rgb($H, $S, $V) {
        $H *= 6;
        $h = intval($H);
        $H -= $h;
        $V *= 255;
        $m = $V * (1 - $S);
        $x = $V * (1 - $S * (1 - $H));
        $y = $V * (1 - $S * $H);
        $a = [[$V, $x, $m],
            [$y, $V, $m],
            [$m, $V, $x],
            [$m, $y, $V],
            [$x, $m, $V],
            [$V, $m, $y]][$h];
        return sprintf("#%02X%02X%02X", $a[0], $a[1], $a[2]);
    }
}
