<?php

function solution($str, $ending){
    return str_ends_with($str, $ending);
}

test('stringendswith', function () {
    expect(solution("samurai", "ai"))->toBeTrue();
    expect(solution("ninja", "ja"))->toBeTrue();
    expect(solution("sensei", "i"))->toBeTrue();
    expect(solution("abc", "abc"))->toBeTrue();
    expect(solution("abcabc", "bc"))->toBeTrue();
    expect(solution('fails', 'ails'))->toBeTrue();

    expect(solution("sumo", "omo"))->toBeFalse();
    expect(solution("samurai", "ra"))->toBeFalse();
    expect(solution("abc", "abcd"))->toBeFalse();
    expect(solution('ails', 'fails'))->toBeFalse();
    expect(solution('this', 'fails'))->toBeFalse();
    expect(solution('this will not pass', '`^$<>()[]*|'))->toBeFalse();
    expect(solution("abc\n", 'abc')/*, 'Watch out for \n in the end'*/)->toBeFalse();
    expect(solution('yes this will pass', ''))->toBeTrue();

});
