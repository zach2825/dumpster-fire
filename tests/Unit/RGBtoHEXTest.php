<?php

function rgb($r,$g,$b){
    $rgb = array_map(function($int) {
        return roundRgbInt($int);
    }, [$r, $g, $b]);

    $hex = array_reduce($rgb, function($carry, $item) {
        return $carry . toHexDigits($item);
    }, '');

    return $hex;
}

function roundRgbInt($int) {
    if($int < 0) return 0;

    if($int > 255) return 255;

    return $int;
}

function toHexDigits($int) {
    return hexVal((int) ($int / 16)) .  // first hex digit
        hexVal($int % 16);                // second hex digit
}

function hexVal($int) {
    if($int < 10) return $int;

    return [
               '10' => "A",
               '11' => "B",
               '12' => "C",
               '13' => "D",
               '14' => "E",
               '15' => "F",
           ][(string) $int];
}

function rgbToHex($r, $g, $b)
{
    return sprintf("%02X%02X%02X", $r > 255 ? 255 : $r, $g > 255 ? 255 : $g, $b > 255 ? 255 : $b);
}



test('ConvertRGBToHEX', function () {
    $counter = 1_000_000;

    while($counter-- > 0) {
        expect(rgbToHex(255, 255, 255))->toEqual('FFFFFF');
        expect(rgbToHex(255, 255, 300))->toEqual('FFFFFF');
        expect(rgbToHex(0, 0, 0))->toEqual('000000');
        expect(rgbToHex(148, 0, 211))->toEqual('9400D3');
    }
});

test('testRGB', function () {
    $counter = 1_000_000;

    while($counter-- > 0) {
        expect(rgb(255, 255, 255))->toEqual('FFFFFF');
        expect(rgb(255, 255, 300))->toEqual('FFFFFF');
        expect(rgb(0, 0, 0))->toEqual('000000');
        expect(rgb(148, 0, 211))->toEqual('9400D3');
    }
});
